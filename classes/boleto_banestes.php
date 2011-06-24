<?php

class BoletoBanestes extends Boleto {

  function __construct($dadosboleto) {
    $codigobanco = "021";
    $codigo_banco_com_dv = $this->geraCodigoBanco($codigobanco);
    $nummoeda = "9";
    $fator_vencimento = $this->fator_vencimento($dadosboleto["data_vencimento"]);
    $cvt = "5";
    $zero = "00";

    //valor tem 10 digitos, sem virgula
    $valor = $this->formata_numero($dadosboleto["valor_boleto"],10,0,"valor");

    $carteira = $dadosboleto["carteira"];

    //nosso número (sem dv) são 8 digitos
    $nossonumero_sem_dv = substr($dadosboleto["nosso_numero"],0,8);

    //dvs do nosso número
    $nossonumero_dv1 = $this->modulo_11($nossonumero_sem_dv);
    $nossonumero_dv2 = $this->modulo_11($nossonumero_sem_dv.$nossonumero_dv1,10);
    $nossonumero_com_dv=$nossonumero_sem_dv.".".$nossonumero_dv1.$nossonumero_dv2;
    unset($nossonumero_dv1,$nossonumero_dv2);

    //conta corrente (sem dv) são 11 digitos
    $conta = $this->formata_numero($dadosboleto["conta"],11,0);

    // Chave ASBACE 25 dígitos
    $Wtemp=$this->formata_numero($nossonumero_sem_dv,8,0).$conta.$dadosboleto["tipo_cobranca"].$codigobanco;
    $chaveasbace_dv1=$this->modulo_10($Wtemp);
    $chaveasbace_dv2=$this->modulo_11($Wtemp.$chaveasbace_dv1,7);
    $dadosboleto["chave_asbace"]=$Wtemp.$chaveasbace_dv1.$chaveasbace_dv2;
    unset($chaveasbace_dv1,$chaveasbace_dv2);

    // 43 numeros para o calculo do digito verificador
    $dv = $this->digitoVerificador("$codigobanco$nummoeda$fator_vencimento$valor".$dadosboleto['chave_asbace']);
    $linha = "$codigobanco$nummoeda$dv$fator_vencimento$valor".$dadosboleto['chave_asbace']; 


    $dadosboleto["codigo_barras"] = $this->codigo_barra($linha);
    $dadosboleto["linha_digitavel"] = $this->monta_linha_digitavel($linha);
    $dadosboleto["agencia_codigo"] = $conta;
    $dadosboleto["codigo_banco_com_dv"] = $codigo_banco_com_dv;
    $dadosboleto["nosso_numero"] = $nossonumero_com_dv;
    $this->render('banestes',$dadosboleto);
  }

  function digitoVerificador($numero) {
    $digito = $this->modulo_11($numero);
    if (in_array((int)$digito,array(0,1,10,11))) {
      $digito = 1;
    }
    return $digito;
  }

  function formata_numero($numero,$loop,$insert,$tipo = "geral") {
    if ($tipo == "geral") {
      $numero = str_replace(",","",$numero);
      $numero = str_replace(".","",$numero);
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
  
}
