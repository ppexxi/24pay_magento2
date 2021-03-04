<?php

namespace TwentyFourPay\TwentyFourPayGateway\Model;

use Magento\Checkout\Model\ConfigProviderInterface;

class ConfigProvider implements ConfigProviderInterface {
  /**
   * @var Config
   */
  private $config;

  public function __construct(Config $config) {
    $this->config = $config;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig() {
    $outConfig = ['payment' => [\TwentyFourPay\TwentyFourPayGateway\Model\Payment::METHOD_CODE => [
      'gateway_url' => $this->config->getGatewayUrl(),
      'result_url' => $this->config->getResultUrl(),
      'form_url' => $this->config->getFormUrl(),
      //'ipn_url' => $this->config->getIpnUrl(),
      'enabled' => $this->config->isEnabled(),
      'title' => $this->config->getTitle(),
      'production' => $this->config->isProduction(),
      //'mid' => $this->config->getMid(),
      //'eshopid' => $this->config->getEshopId(),
      //'key' => $this->config->getKey(),
      //'notify' => $this->config->getNotify(),
      'preauth' => $this->config->isPreAuthProvided(),
      'debug' => $this->config->isDebug()
    ]]];

    return $outConfig;
  }
}
