<?php
namespace TwentyFourPay\TwentyFourPayGateway\Controller\Payment;

use Magento\Framework\App\Action\Action;
use Magento\Framework\Exception\PaymentException;

require_once __DIR__ . "/../../libs/24pay/CountryCodesConverter.php";
require_once __DIR__ . "/../../libs/24pay/Service24PayRequestBuilder.class.php";

class Form extends CoreClass {

  /**
   * Constructor
   *
   */
  public function __construct(\TwentyFourPay\TwentyFourPayGateway\Model\Config $config, \Magento\Framework\Message\ManagerInterface $messageManager, \Magento\Framework\App\Action\Context $context) {
    parent::__construct($config, $messageManager, $context);
  }

  protected function getMsTxnId($orderId) {
    return date("His") . $orderId;
  }

  protected function getRequestBuilder($requestParams = array()) {
    return new \Service24PayRequestBuilder($this->config->get24PayService() , $requestParams);
  }

  protected function generateRequestForms($requestParams = array()) {
    $requestBuilder = $this->getRequestBuilder($requestParams);

    $html = "";
    $formFields = $requestBuilder->generateRequestFormFields();

    $service = $this->config->get24PayService();

    $html .= '<form id="t24payForm" class="twentyfourpay-gateway twentyfourpay-gateway-universal" action="' . $this->config->getGatewayUrl() . '" method="post">' . $formFields . '<!--<button type="submit" style="background: url(\'' . $this->config->getIconUrl() . '\') no-repeat center / 100%">' . '<img src="' . $this->config->getIconUrl() . '" alt="24-pay Paygate Universal" width="150px">' . '</button>-->' . '</form>';

    return $html;
  }

  public function fixCountryCode($countryCode) {
    return convert_country_code_from_isoa2_to_isoa3($countryCode);
  }

  protected function getPostForm($order_id) {
    $order = $this->getOrder($order_id);

    //if (!$order_id) die("Invalid order ID");

    $address = $order->getBillingAddress();

    //if (!$address) die("No billing address available");

    if ($address && ($address->getCustomerId() != null)) $clientId = $address->getCustomerId();
    else $clientId = "guest";

    $locale = $this->getLocale();
    $locale_string = explode('_', $locale->getLocale(), 2);

    $currency = $order->getOrderCurrency()->getCurrencyCode();

    $paymentData = array(
      "NURL" => $this->config->getIpnUrl(),
      "RURL" => $this->config->getResultUrl(),
      "MsTxnId" => $this->getMsTxnId($order->getId()) ,
      "CurrAlphaCode" => $currency ? $currency : 'EUR' ,
      "Amount" => $order->getGrandTotal(), //$order->getTotalDue() , 
      "LangCode" => strtoupper($locale_string[0]),
      "ClientId" => $clientId,
      "FirstName" => $address ? $address->getFirstname() : 'NoName' ,
      "FamilyName" => $address ? $address->getLastname(): 'Unknown' ,
      "Email" => $address ? $address->getEmail() : 'nomail@example.com' ,
      //"Phone" => $address->getTelephone() ,
      //"Street" => implode(", ", $address->getStreet()) ,
      //"Zip" => $address->getPostcode() ,
      //"City" => $address->getCity() ,
      "Country" => $this->fixCountryCode($address ? $address->getCountryId() : 'SK'),
      "Debug" => $this->config->isDebug(),
      "PreAuthProvided" => $this->config->isPreAuthProvided()
    );

    $notifyEmail = $this->config->getNotifyEmail();
    if ($notifyEmail) {
      $paymentData['NotifyEmail'] = $notifyEmail;
    }

    return $this->generateRequestForms($paymentData);
  }

  /**
   * @return \Magento\Framework\Controller\ResultInterface
   */
  public function execute() {

    // TODO[security]: Extract from session
    $order_id = (int)$this->getRequest()->getParam('order_id', NULL);
    if (!$order_id) {
      die('No order ID');
    }

    $order = $this->getOrder($order_id);
    if (!$order->getId()) {
      die('No order');
    }      

    $response = $this->createResponse();
    $form = $this->getPostForm($order_id);

    $response->setContents($form);

    return $response;

  }
}