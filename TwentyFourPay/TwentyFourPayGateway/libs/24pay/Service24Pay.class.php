<?php


/**
 * base class for communication with 24pay gateway server
 */
class Service24Pay {

  protected $installUrl = "https://admin.24-pay.eu/pay_gate/install";

  protected $checkUrl = "https://admin.24-pay.eu/pay_gate/check";

  protected $gatewayBaseUrl = "https://admin.24-pay.eu/pay_gate/paygt";

  protected $mediaBaseUrl = "https://icons.24-pay.sk";

  protected $Mid;

  protected $Key;

  protected $EshopId;

  protected $knownGateways = array(
    "3" => "CardPay",
    "4" => "ComfortPay",
    "1001" => "TatraPay",
    "1002" => "SporoPay",
    "1003" => "VUBePlatby",
    "1004" => "SberbankWEBpay",
    "1005" => "CSOBPayBtn",
    "1006" => "UniPlatba",
    "1007" => "PlatbaOnlinePostovaBanka",
    "1010" => "ZunoPay",
    "1011" => "RaiffeiseEplatby",
    "2001" => "CSOBBankTransfer",
    "2002" => "PrimaBankTransfer",
    "2003" => "SLSPBankTransfer",
    "2004" => "TatraBankTransfer",
    "2005" => "UniCreditBankTransfer",
    "2006" => "VUBBankTransfer",
    "2007" => "OTPBankTransfer",
    "2008" => "PostovaBankTransfer",
    "2009" => "SberBankTransfer",
    "2009" => "ZunoBankTransfer",
    "2010" => "FrioBankTransfer",
    "2011" => "FrioCzBankTransfer",
    "3005" => "Testovacia brána",
    "3006" => "PayPal",
    "3999" => "UniversalPaygate"
  );

  protected $testGatewayId = "3005";

  /**
   * @param string $Mid
   * @param string $Key
   * @param string $EshopId
   */
  public function __construct($Mid, $Key, $EshopId) {
    if (!preg_match("/^[a-zA-Z0-9]{8}$/", $Mid)) throw new Service24PayException("Invalid Mid value");

    $this->Mid = $Mid;

    if (!preg_match("/[a-zA-Z0-9]{64}/", $Key)) throw new Service24PayException("Invalid Key value");

    $this->Key = $Key;

    if (!preg_match("/[0-9]{1,10}/", $Key)) throw new Service24PayException("Invalid EshopId value");

    $this->EshopId = $EshopId;
  }

  public function getKey() {
    return $this->Key;
  }

  public function getMid() {
    return $this->Mid;
  }

  public function getEshopId() {
    return $this->EshopId;
  }

  public function getGatewayName($gatewayId = "") {
    if ($gatewayId == "") return "Universal Paygate";
    else return $this->knownGateways[$gatewayId];
  }

  public function getGatewayUrl($gatewayId = "") {
    return $this->gatewayBaseUrl . $gatewayId;
  }

  public function getGatewayIcon($gatewayId = "") {
    return $this->mediaBaseUrl . '/images/gateway_' . $gatewayId . '.png';
  }

  public function getTestGatewayId() {
    return $this->testGatewayId;
  }

  /**
   * compute (uppercased) Sign value for given message
   * @param  string $message
   * @return string
   */
  public function computeSIGN($message) {
    if (!$message) return false;

    $hash = hash("sha1", $message, true);
    $iv = $this->Mid . strrev($this->Mid);

    $key = pack('H*', $this->Key);

    $crypted = mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $key, $hash, MCRYPT_MODE_CBC, $iv);
    $sign = strtoupper(bin2hex(substr($crypted, 0, 16)));

    return $sign;
  }

  /**
   * make a request to 24pay gateway server to retrieve list of available gateways for current Mid
   * @return array
   */
  public function loadAvailableGateways() {
    $availableGateways = $this->makePostRequest($this->installUrl, array(
      'ESHOP_ID' => $this->EshopId,
      'MID' => $this->Mid
    ));

    return json_decode($availableGateways, true);
  }

  /**
   * make a request to 24pay gateway server to check the validity of sign generated form current Mid and Key values
   * @return bool
   */
  public function checkSignGeneration() {
    $status = $this->makePostRequest($this->checkUrl, array(
      'CHECKSUM' => $this->computeSIGN('Check sign text for MID ' . $this->Mid) ,
      'MID' => $this->Mid
    ));

    return $status === 'OK';
  }

  /**
   * @param  string $url
   * @param  array $data
   * @return string
   */
  public function makePostRequest($url, $data) {
    $curl = curl_init();

    $config = array(
      CURLOPT_URL => $url,
      CURLOPT_POST => true,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_POSTFIELDS => http_build_query($data)
    );

    curl_setopt_array($curl, $config);
    $result = curl_exec($curl);
    curl_close($curl);

    return $result;
  }

  /**
   * shorthand for creating a Service24PayRequest object that uses this Service24Pay object
   * @param  array  $data
   * @return Service24PayRequest
   */
  public function createRequest($data = array()) {
    return new Service24PayRequest($this, $data);
  }

  /**
   * shorthand for creating a Service24PayNotification object that uses this Service24Pay object
   * @param  string $XMLResponse
   * @return Service24PayNotification
   */
  public function parseNotification($XMLResponse = null) {
    return new Service24PayNotification($this, $XMLResponse);
  }

}


class Service24PayException extends Exception {
}

