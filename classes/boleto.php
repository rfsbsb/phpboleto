<?php

class Boleto {

  function render($banco,$dadosboleto) {
    include_once('./layouts/layout_'.$banco.'.php');
  }

  function formata_numero($numero,$loop,$insert,$tipo = "geral") {
    if ($tipo == "geral") {
      $numero = str_replace(",","",$numero);
      while(strlen($numero)<$loop){
        $numero = $insert . $numero;
      }
    }
    if ($tipo == "valor") {
      $numero = str_replace(",","",$numero);
      while(strlen($numero)<$loop){
        $numero = $insert . $numero;
      }
    }
    if ($tipo == "convenio") {
      while(strlen($numero)<$loop){
        $numero = $numero . $insert;
      }
    }
    return $numero;
  }

  function codigo_barra($valor){

    $fino = 1 ;
    $largo = 3 ;
    $altura = 50 ;

    $barcodes[0] = "00110" ;
    $barcodes[1] = "10001" ;
    $barcodes[2] = "01001" ;
    $barcodes[3] = "11000" ;
    $barcodes[4] = "00101" ;
    $barcodes[5] = "10100" ;
    $barcodes[6] = "01100" ;
    $barcodes[7] = "00011" ;
    $barcodes[8] = "10010" ;
    $barcodes[9] = "01010" ;
    for($f1=9;$f1>=0;$f1--){ 
      for($f2=9;$f2>=0;$f2--){  
        $f = ($f1 * 10) + $f2 ;
        $texto = "" ;
        for($i=1;$i<6;$i++){ 
          $texto .=  substr($barcodes[$f1],($i-1),1) . substr($barcodes[$f2],($i-1),1);
        }
        $barcodes[$f] = $texto;
      }
    }
    
    $output = '<img src="imagens/p.png" width="'.$fino.'" height="'.$altura.'" border="0"><img 
    src="imagens/b.png" width="'.$fino.'" height="'.$altura.'" border="0"><img 
    src="imagens/p.png" width="'.$fino.'" height="'.$altura.'" border="0"><img 
    src=imagens/b.png width="'.$fino.'" height="'.$altura.'" border="0">';

    $texto = $valor ;
    if((strlen($texto) % 2) <> 0){
      $texto = "0" . $texto;
    }

    // Draw dos dados
    while (strlen($texto) > 0) {
      $i = round($this->esquerda($texto,2));
      $texto = $this->direita($texto,strlen($texto)-2);
      $f = $barcodes[$i];
      for($i=1;$i<11;$i+=2){
        if (substr($f,($i-1),1) == "0") {
          $f1 = $fino ;
        }else{
          $f1 = $largo ;
        }

        $output .= '<img src="imagens/p.png" width="'.$f1.'" height="'.$altura.'" border="0">';

        if (substr($f,$i,1) == "0") {
          $f2 = $fino ;
        }else{
          $f2 = $largo ;
        }

        $output .= '<img src=imagens/b.png width="'.$f2.'" height="'.$altura.'" border="0">';
      }
    }

    $output .= '<img src="imagens/p.png" width="'.$largo.'" height="'.$altura.'" border="0"><img 
    src=imagens/b.png width="'.$fino.'" height="'.$altura.'" border="0"><img 
    src="imagens/p.png" width="1" height="'.$altura.'" border="0"> ';
    
    return $output;

  }

  function esquerda($entra,$comp){
    return substr($entra,0,$comp);
  }

  function direita($entra,$comp){
    return substr($entra,strlen($entra)-$comp,$comp);
  }

  function fator_vencimento($data) {
    $data = explode("/",$data);
    $ano = $data[2];
    $mes = $data[1];
    $dia = $data[0];
    return(abs(($this->_dateToDays("1997","10","07")) - ($this->_dateToDays($ano, $mes, $dia))));
  }

  function _dateToDays($year,$month,$day) {
    $century = substr($year, 0, 2);
    $year = substr($year, 2, 2);
    if ($month > 2) {
      $month -= 3;
    } else {
      $month += 9;
      if ($year) {
        $year--;
      } else {
        $year = 99;
        $century --;
      }
    }
    return (floor(( 146097 * $century) / 4) + floor(( 1461 * $year) / 4) + floor(( 153 * $month + 2) / 5) + $day + 1721119);
  }

  function modulo_10($num) { 
    $numtotal10 = 0;
    $fator = 2;

    // Separacao dos numeros
    for ($i = strlen($num); $i > 0; $i--) {
      // pega cada numero isoladamente
      $numeros[$i] = substr($num,$i-1,1);
      // Efetua multiplicacao do numero pelo (falor 10)
      $temp = $numeros[$i] * $fator; 
      $temp0=0;
      foreach (preg_split('//',$temp,-1,PREG_SPLIT_NO_EMPTY) as $k=>$v) { 
        $temp0+=$v;
      }
      $parcial10[$i] = $temp0; //$numeros[$i] * $fator;
      // monta sequencia para soma dos digitos no (modulo 10)
      $numtotal10 += $parcial10[$i];
      if ($fator == 2) {
        $fator = 1;
      } else {
        $fator = 2; // intercala fator de multiplicacao (modulo 10)
      }
    }

    // várias linhas removidas, vide função original
    // Calculo do modulo 10
    $resto = $numtotal10 % 10;
    $digito = 10 - $resto;
    if ($resto == 0) {
      $digito = 0;
    }

    return $digito;		
  }

  function modulo_11($num, $base=9, $r=0)  {
    /**
     *   Autor:
     *           Pablo Costa <pablo@users.sourceforge.net>
     *
     *   Função:
     *    Calculo do Modulo 11 para geracao do digito verificador 
     *    de boletos bancarios conforme documentos obtidos 
     *    da Febraban - www.febraban.org.br 
     *
     *   Entrada:
     *     $num: string numérica para a qual se deseja calcularo digito verificador;
     *     $base: valor maximo de multiplicacao [2-$base]
     *     $r: quando especificado um devolve somente o resto
     *
     *   Saída:
     *     Retorna o Digito verificador.
     *
     *   Observações:
     *     - Script desenvolvido sem nenhum reaproveitamento de código pré existente.
     *     - Assume-se que a verificação do formato das variáveis de entrada é feita antes da execução deste script.
     */                                        

    $soma = 0;
    $fator = 2;

    /* Separacao dos numeros */
    for ($i = strlen($num); $i > 0; $i--) {
      // pega cada numero isoladamente
      $numeros[$i] = substr($num,$i-1,1);
      // Efetua multiplicacao do numero pelo falor
      $parcial[$i] = $numeros[$i] * $fator;
      // Soma dos digitos
      $soma += $parcial[$i];
      if ($fator == $base) {
        // restaura fator de multiplicacao para 2 
        $fator = 1;
      }
      $fator++;
    }

    /* Calculo do modulo 11 */
    if ($r == 0) {
      $soma *= 10;
      $digito = $soma % 11;
      if ($digito == 10) {
          $digito = 0;
      }
      return $digito;
    } elseif ($r == 1){
      $resto = $soma % 11;
      return $resto;
    }
  }

  function monta_linha_digitavel($linha) {
    // Posição 	Conteúdo
    // 1 a 3    Número do banco
    // 4        Código da Moeda - 9 para Real
    // 5        Digito verificador do Código de Barras
    // 6 a 19   Valor (12 inteiros e 2 decimais)
    // 20 a 44  Campo Livre definido por cada banco

    // 1. Campo - composto pelo código do banco, código da moéda, as cinco primeiras posições
    // do campo livre e DV (modulo10) deste campo
    $p1 = substr($linha, 0, 4);
    $p2 = substr($linha, 19, 5);
    $p3 = $this->modulo_10("$p1$p2");
    $p4 = "$p1$p2$p3";
    $p5 = substr($p4, 0, 5);
    $p6 = substr($p4, 5);
    $campo1 = "$p5.$p6";

    // 2. Campo - composto pelas posiçoes 6 a 15 do campo livre
    // e livre e DV (modulo10) deste campo
    $p1 = substr($linha, 24, 10);
    $p2 = $this->modulo_10($p1);
    $p3 = "$p1$p2";
    $p4 = substr($p3, 0, 5);
    $p5 = substr($p3, 5);
    $campo2 = "$p4.$p5";

    // 3. Campo composto pelas posicoes 16 a 25 do campo livre
    // e livre e DV (modulo10) deste campo
    $p1 = substr($linha, 34, 10);
    $p2 = $this->modulo_10($p1);
    $p3 = "$p1$p2";
    $p4 = substr($p3, 0, 5);
    $p5 = substr($p3, 5);
    $campo3 = "$p4.$p5";

    // 4. Campo - digito verificador do codigo de barras
    $campo4 = substr($linha, 4, 1);

    // 5. Campo composto pelo valor nominal pelo valor nominal do documento, sem
    // indicacao de zeros a esquerda e sem edicao (sem ponto e virgula). Quando se
    // tratar de valor zerado, a representacao deve ser 000 (tres zeros).
    $campo5 = substr($linha, 5, 14);

    return "$campo1 $campo2 $campo3 $campo4 $campo5"; 
  }

  function geraCodigoBanco($numero) {
    $parte1 = substr($numero, 0, 3);
    $parte2 = $this->modulo_11($parte1);
    return $parte1 . "-" . $parte2;
  }

}
