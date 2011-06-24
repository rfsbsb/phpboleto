<?php

class BoletoItau extends Boleto {

  function __construct($dadosboleto) {
    $codigobanco = "341";
    $codigo_banco_com_dv = $this->geraCodigoBanco($codigobanco);
    $nummoeda = "9";
    $fator_vencimento = $this->fator_vencimento($dadosboleto["data_vencimento"]);

    //valor tem 10 digitos, sem virgula
    $valor = $this->formata_numero($dadosboleto["valor_boleto"],10,0,"valor");
    //agencia é 4 digitos
    $agencia = $this->formata_numero($dadosboleto["agencia"],4,0);
    //conta é 5 digitos + 1 do dv
    $conta = $this->formata_numero($dadosboleto["conta"],5,0);
    $conta_dv = $this->formata_numero($dadosboleto["conta_dv"],1,0);
    //carteira 175
    $carteira = $dadosboleto["carteira"];
    //nosso_numero no maximo 8 digitos
    $nnum = $this->formata_numero($dadosboleto["nosso_numero"],8,0);

    $codigo_barras = $codigobanco.$nummoeda.$fator_vencimento.$valor.$carteira.$nnum.$this->modulo_10($agencia.$conta.$carteira.$nnum).$agencia.$conta.$this->modulo_10($agencia.$conta).'000';
    // 43 numeros para o calculo do digito verificador
    $dv = $this->digitoVerificador_barra($codigo_barras);
    // Numero para o codigo de barras com 44 digitos
    $linha = substr($codigo_barras,0,4).$dv.substr($codigo_barras,4,43);

    $nossonumero = $carteira.'/'.$nnum.'-'.$this->modulo_10($agencia.$conta.$carteira.$nnum);
    $agencia_codigo = $agencia." / ". $conta."-".$this->modulo_10($agencia.$conta);

    $dadosboleto["codigo_barras"] = $this->codigo_barra($linha);
    $dadosboleto["linha_digitavel"] = $this->monta_linha_digitavel($linha); // verificar
    $dadosboleto["agencia_codigo"] = $agencia_codigo ;
    $dadosboleto["nosso_numero"] = $nossonumero;
    $dadosboleto["codigo_banco_com_dv"] = $codigo_banco_com_dv;
    $this->render('itau',$dadosboleto);
  }

  function digitoVerificador_barra($numero) {
    $resto2 = $this->modulo_11($numero, 9, 1);
    $digito = 11 - $resto2;
    if ($digito == 0 || $digito == 1 || $digito == 10  || $digito == 11) {
      $dv = 1;
    } else {
      $dv = $digito;
    }
    return $dv;
  }

  function modulo_10($num) { 
    $numtotal10 = 0;
    $fator = 2;

    // Separacao dos numeros
    for ($i = strlen($num); $i > 0; $i--) {
      // pega cada numero isoladamente
      $numeros[$i] = substr($num,$i-1,1);
      // Efetua multiplicacao do numero pelo (falor 10)
      // 2002-07-07 01:33:34 Macete para adequar ao Mod10 do Itaú
      $temp = $numeros[$i] * $fator; 
      $temp0 = 0;
      foreach (preg_split('//',$temp,-1,PREG_SPLIT_NO_EMPTY) as $k=>$v){ 
        $temp0 += $v;     
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
  
}
