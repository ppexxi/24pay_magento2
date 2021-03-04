<?php
namespace TwentyFourPay\TwentyFourPayGateway\Model;

require_once __DIR__ . "/../libs/24pay/Service24Pay.class.php";

class Config {
  /**
   * @var \Magento\Framework\App\Config\ScopeConfigInterface
   */
  private $scopeConfigInterface;

  private $service = null;

  /**
   * Function used for reading a config value.
   */
  private function getConfigValue($value) {
    return $this->scopeConfigInterface->getValue('payment/t24pay/' . $value);
  }

  public function __construct(\Magento\Framework\App\Config\ScopeConfigInterface $configInterface) {
    $this->scopeConfigInterface = $configInterface;
  }

  public function isEnabled() {
    return (bool)$this->getConfigValue('active');
  }

  public function getTitle() {
    return trim((string)$this->getConfigValue('title'));
  }

  public function isProduction() {
    return (bool)$this->getConfigValue('production');
  }

  public function getMid() {
    return trim((string)$this->getConfigValue('mid'));
  }

  public function getEshopId() {
    return trim((string)$this->getConfigValue('eshopid'));
  }

  public function getKey() {
    return trim((string)$this->getConfigValue('key'));
  }

  public function getNotifyEmail() {
    return trim((string)$this->getConfigValue('notify'));
  }

  public function isPreAuthProvided() {
    return (bool)$this->getConfigValue('preauth');
  }

  public function isDebug() {
    return (bool)$this->getConfigValue('debug');
  }

  public function getGatewayUrl() {
    $service = $this->get24PayService();
    $url = $service->getGatewayUrl("");
    if (!$this->isProduction()) {
      $url = str_replace('admin.', 'test.', $url);
    }

    return $url;
  }

  public function getIconUrl() {
    $service = $this->get24PayService();
    return $service->getGatewayIcon("universal");
  }

  protected function getObjectManager() {
    $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
    return $objectManager;
  }

  protected function getStore() {
    $objectManager = $this->getObjectManager();
    $storeManager = $objectManager->get('\Magento\Store\Model\StoreManagerInterface');
    $store = $storeManager->getStore();
    return $store;
  }

  public function getFormUrl() {
    return $this->getStore()->getBaseUrl() . '24pay/payment/form';
  }

  public function getResultUrl() {
    return $this->getStore()->getBaseUrl() . '24pay/payment/result';
  }

  public function getIpnUrl() {
    return $this->getStore()->getBaseUrl() . '24pay/payment/ipn';
  }

  public function get24PayService() {
    if (!$this->service) {
      $this->service = new \Service24Pay($this->getMid() , $this->getKey() , $this->getEshopId());
    }

    return $this->service;
  }

}
