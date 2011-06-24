<?php

class BoletoBb extends Boleto {

  function __construct($dadosboleto) {
    $codigobanco = "001";
    $codigo_banco_com_dv = $this->geraCodigoBanco($codigobanco);
    $nummoeda = "9";
    $fator_vencimento = $this->fator_vencimento($dadosboleto["data_vencimento"]);

    //valor tem 10 digitos, sem virgula
    $valor = $this->formata_numero($dadosboleto["valor_boleto"],10,0,"valor");
    //agencia é sempre 4 digitos
    $agencia = $this->formata_numero($dadosboleto["agencia"],4,0);
    //conta é sempre 8 digitos
    $conta = $this->formata_numero($dadosboleto["conta"],8,0);
    //carteira 18
    $carteira = $dadosboleto["carteira"];
    //agencia e conta
    $agencia_codigo = $agencia."-". $this->modulo_11($agencia) ." / ". $conta ."-". $this->modulo_11($conta);
    //Zeros: usado quando convenio de 7 digitos
    $livre_zeros='000000';

    // Carteira 18 com Convênio de 8 dígitos
    if ($dadosboleto["formatacao_convenio"] == "8") {
	    $convenio = $this->formata_numero($dadosboleto["convenio"],8,0,"convenio");
	    // Nosso número de até 9 dígitos
	    $nossonumero = $this->formata_numero($dadosboleto["nosso_numero"],9,0);
	    $dv=$this->modulo_11("$codigobanco$nummoeda$fator_vencimento$valor$livre_zeros$convenio$nossonumero$carteira");
	    $linha="$codigobanco$nummoeda$dv$fator_vencimento$valor$livre_zeros$convenio$nossonumero$carteira";
	    //montando o nosso numero que aparecerá no boleto
	    $nossonumero = $convenio . $nossonumero ."-". $this->modulo_11($convenio.$nossonumero);
    }

    // Carteira 18 com Convênio de 7 dígitos
    if ($dadosboleto["formatacao_convenio"] == "7") {
	    $convenio = $this->formata_numero($dadosboleto["convenio"],7,0,"convenio");
	    // Nosso número de até 10 dígitos
	    $nossonumero = $this->formata_numero($dadosboleto["nosso_numero"],10,0);
	    $dv=$this->modulo_11("$codigobanco$nummoeda$fator_vencimento$valor$livre_zeros$convenio$nossonumero$carteira");
	    $linha="$codigobanco$nummoeda$dv$fator_vencimento$valor$livre_zeros$convenio$nossonumero$carteira";
      $nossonumero = $convenio.$nossonumero;
	    //Não existe DV na composição do nosso-número para convênios de sete posições
    }

    // Carteira 18 com Convênio de 6 dígitos
    if ($dadosboleto["formatacao_convenio"] == "6") {
	    $convenio = $this->formata_numero($dadosboleto["convenio"],6,0,"convenio");
	
	    if ($dadosboleto["formatacao_nosso_numero"] == "1") {
		
		    // Nosso número de até 5 dígitos
		    $nossonumero = $this->formata_numero($dadosboleto["nosso_numero"],5,0);
		    $dv = $this->modulo_11("$codigobanco$nummoeda$fator_vencimento$valor$convenio$nossonumero$agencia$conta$carteira");
		    $linha = "$codigobanco$nummoeda$dv$fator_vencimento$valor$convenio$nossonumero$agencia$conta$carteira";
		    //montando o nosso numero que aparecerá no boleto
		    $nossonumero = $convenio . $nossonumero ."-". $this->modulo_11($convenio.$nossonumero);
	    }
	
	    if ($dadosboleto["formatacao_nosso_numero"] == "2") {
		
		    // Nosso número de até 17 dígitos
		    $nservico = "21";
		    $nossonumero = $this->formata_numero($dadosboleto["nosso_numero"],17,0);
		    $dv = $this->modulo_11("$codigobanco$nummoeda$fator_vencimento$valor$convenio$nossonumero$nservico");
		    $linha = "$codigobanco$nummoeda$dv$fator_vencimento$valor$convenio$nossonumero$nservico";
	    }
    }

    $dadosboleto["codigo_barras"] = $this->codigo_barra($linha);
    $dadosboleto["linha_digitavel"] = $this->monta_linha_digitavel($linha);
    $dadosboleto["agencia_codigo"] = $agencia_codigo;
    $dadosboleto["nosso_numero"] = $nossonumero;
    $dadosboleto["codigo_banco_com_dv"] = $codigo_banco_com_dv;
    $this->render('bb',$dadosboleto);
  }

  function modulo_11($num, $base=9, $r=0) {
    $soma = 0;
    $fator = 2; 
    for ($i = strlen($num); $i > 0; $i--) {
      $numeros[$i] = substr($num,$i-1,1);
      $parcial[$i] = $numeros[$i] * $fator;
      $soma += $parcial[$i];
      if ($fator == $base) {
        $fator = 1;
      }
      $fator++;
    }
    if ($r == 0) {
      $soma *= 10;
      $digito = $soma % 11;

      //corrigido
      if ($digito == 10) {
	      $digito = "X";
      }

      /*
      alterado por mim, Daniel Schultz

      Vamos explicar:

      O módulo 11 só gera os digitos verificadores do nossonumero,
      agencia, conta e digito verificador com codigo de barras (aquele que fica sozinho e triste na linha digitável)
      só que é foi um rolo...pq ele nao podia resultar em 0, e o pessoal do phpboleto se esqueceu disso...

      No BB, os dígitos verificadores podem ser X ou 0 (zero) para agencia, conta e nosso numero,
      mas nunca pode ser X ou 0 (zero) para a linha digitável, justamente por ser totalmente numérica.

      Quando passamos os dados para a função, fica assim:

      Agencia = sempre 4 digitos
      Conta = até 8 dígitos
      Nosso número = de 1 a 17 digitos

      A unica variável que passa 17 digitos é a da linha digitada, justamente por ter 43 caracteres

      Entao vamos definir ai embaixo o seguinte...

      se (strlen($num) == 43) { não deixar dar digito X ou 0 }
      */

      if (strlen($num) == "43") {
        //então estamos checando a linha digitável
        if ($digito == "0" or $digito == "X" or $digito > 9) {
	        $digito = 1;
        }
      }
      return $digito;
    } 
    elseif ($r == 1){
      $resto = $soma % 11;
      return $resto;
    }
  }
  
}
