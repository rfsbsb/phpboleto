<?php

class BoletoCef extends Boleto {

  function __construct($dadosboleto) {

    $codigobanco = "104";
    $codigo_banco_com_dv = $this->geraCodigoBanco($codigobanco);
    $nummoeda = "9";
    $fator_vencimento = $this->fator_vencimento($dadosboleto["data_vencimento"]);

    //valor tem 10 digitos, sem virgula
    $valor = $this->formata_numero($dadosboleto["valor_boleto"],10,0,"valor");
    //agencia é 4 digitos
    $agencia = $this->formata_numero($dadosboleto["agencia"],4,0);
    //conta é 5 digitos
    $conta = $this->formata_numero($dadosboleto["conta"],5,0);
    //dv da conta
    $conta_dv = $this->formata_numero($dadosboleto["conta_dv"],1,0);
    //carteira é 2 caracteres
    $carteira = $dadosboleto["carteira"];

    //conta cedente (sem dv) com 11 digitos   (Operacao de 3 digitos + Cedente de 8 digitos)
    $conta_cedente = $this->formata_numero($dadosboleto["conta_cedente"],11,0);
    //dv da conta cedente
    $conta_cedente_dv = $this->formata_numero($dadosboleto["conta_cedente_dv"],1,0);

    //nosso número (sem dv) é 10 digitos
    $nnum = $dadosboleto["inicio_nosso_numero"] . $this->formata_numero($dadosboleto["nosso_numero"],8,0);
    //nosso número completo (com dv) com 11 digitos
    $nossonumero = $nnum .'-'. $this->digitoVerificador_nossonumero($nnum);

    // 43 numeros para o calculo do digito verificador do codigo de barras
    $dv = $this->digitoVerificador_barra("$codigobanco$nummoeda$fator_vencimento$valor$nnum$agencia$conta_cedente", 9, 0);
    // Numero para o codigo de barras com 44 digitos
    $linha = "$codigobanco$nummoeda$dv$fator_vencimento$valor$nnum$agencia$conta_cedente";

    $agencia_codigo = $agencia." / ". $conta_cedente ."-". $conta_cedente_dv;

    $dadosboleto["codigo_barras"] = $this->codigo_barra($linha);
    $dadosboleto["linha_digitavel"] = $this->monta_linha_digitavel($linha);
    $dadosboleto["agencia_codigo"] = $agencia_codigo;
    $dadosboleto["nosso_numero"] = $nossonumero;
    $dadosboleto["codigo_banco_com_dv"] = $codigo_banco_com_dv;

    $this->render('cef',$dadosboleto);
  }

  function digitoVerificador_nossonumero($numero) {
    $resto2 = $this->modulo_11($numero, 9, 1);
    $digito = 11 - $resto2;
    if ($digito == 10 || $digito == 11) {
      $dv = 0;
    } else {
      $dv = $digito;
    }
    return $dv;
  }

  function digitoVerificador_barra($numero) {
	  $resto2 = $this->modulo_11($numero, 9, 1);
    if ($resto2 == 0 || $resto2 == 1 || $resto2 == 10) {
      $dv = 1;
    } else {
      $dv = 11 - $resto2;
    }
    return $dv;
  }
  
}
