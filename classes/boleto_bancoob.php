<?php

class BoletoBancoob extends Boleto {

  function __construct($dadosboleto) {
    $codigobanco = "756";
    $codigo_banco_com_dv = $this->geraCodigoBanco($codigobanco);
    $nummoeda = "9";
    $fator_vencimento = $this->fator_vencimento($dadosboleto["data_vencimento"]);

    //valor tem 10 digitos, sem virgula
    $valor = $this->formata_numero($dadosboleto["valor_boleto"],10,0,"valor");
    //agencia é sempre 4 digitos
    $agencia = $this->formata_numero($dadosboleto["agencia"],4,0);
    //conta é sempre 8 digitos
    $conta = $this->formata_numero($dadosboleto["conta"],8,0);

    $carteira = $dadosboleto["carteira"];

    //Zeros: usado quando convenio de 7 digitos
    $livre_zeros='000000';
    $modalidadecobranca = $dadosboleto["modalidade_cobranca"];
    $numeroparcela      = $dadosboleto["numero_parcela"];

    $convenio = $this->formata_numero($dadosboleto["convenio"],7,0);

    //agencia e conta
    $agencia_codigo = $agencia ." / ". $convenio;

    // Nosso número de até 8 dígitos - 2 digitos para o ano e outros 6 numeros sequencias por ano 
    // deve ser gerado no programa boleto_bancoob.php
    $nossonumero = $this->formata_numero($dadosboleto["nosso_numero"],8,0);
    $campolivre  = "$modalidadecobranca$convenio$nossonumero$numeroparcela";

    $dv = $this->modulo_11("$codigobanco$nummoeda$fator_vencimento$valor$carteira$agencia$campolivre");
    $linha = "$codigobanco$nummoeda$dv$fator_vencimento$valor$carteira$agencia$campolivre";

    $dadosboleto["codigo_barras"] = $this->codigo_barra($linha);
    $dadosboleto["linha_digitavel"] = $this->monta_linha_digitavel($linha);
    $dadosboleto["agencia_codigo"] = $agencia_codigo;
    $dadosboleto["nosso_numero"] = $nossonumero;
    $dadosboleto["codigo_banco_com_dv"] = $codigo_banco_com_dv;
    $this->render('bancoob',$dadosboleto);
  }
  
}
