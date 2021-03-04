<?php
namespace TwentyFourPay\TwentyFourPayGateway\Controller\Payment;

use Magento\Framework\App\Action\Action;
use Magento\Framework\Exception\PaymentException;

/**
 * This controller handles the payment result URL
 */
class Result extends CoreClass {

  /**
   * Constructor
   *
   */
  public function __construct(\TwentyFourPay\TwentyFourPayGateway\Model\Config $config, \Magento\Framework\Message\ManagerInterface $messageManager, \Magento\Framework\App\Action\Context $context) {
    parent::__construct($config, $messageManager, $context);
  }

  protected function parseMsTxnId($MsTxnId) {
    return substr($MsTxnId, 6);
  }

  /**
   * Handle the result URL redirect from 24Pay gateway
   *
   * @return \Magento\Framework\Controller\ResultInterface
   */
  public function execute() {
    $result = trim((string)$this->getRequest()->getParam('Result', NULL));
    $MsTxnId = trim((string)$this->getRequest()->getParam('MsTxnId', NULL));
    $order_id = (int)$this->parseMsTxnId($MsTxnId);

    if (!$result || !$MsTxnId || !$order_id) {
      $this->messageManager->addErrorMessage(__('Invalid response in the process of payment'));
      $this->_redirect('checkout/onepage/failure', ['_secure' => TRUE]);
    }

    $order = $this->getOrder($order_id);
    if (!$order->getId()) {
      $this->messageManager->addErrorMessage(__('Invalid order in the process of payment'));
      $this->_redirect('checkout/onepage/failure', ['_secure' => TRUE]);
    }

    if ($result == 'FAIL') {
      $order->setState(\Magento\Sales\Model\Order::STATE_HOLDED);
      $order->setStatus(\Magento\Sales\Model\Order::STATE_HOLDED);
      $order->save();

      $this->messageManager->addErrorMessage(__('An failure occurred in the process of payment'));
      $this->_redirect('checkout/onepage/failure', ['_secure' => TRUE]);
    }
    else if ($result == 'OK') {
      $order->setState(\Magento\Sales\Model\Order::STATE_PROCESSING);
      $order->setStatus(\Magento\Sales\Model\Order::STATE_PROCESSING);
      $order->save();

      $this->_redirect('checkout/onepage/success', ['_secure' => TRUE]);
    }
    else  if ($result == 'PENDING') {
      $order->setState(\Magento\Sales\Model\Order::STATE_PAYMENT_REVIEW);
      $order->setStatus(\Magento\Sales\Model\Order::STATE_PAYMENT_REVIEW);
      $order->save();

      $this->_redirect('checkout/onepage/success', ['_secure' => TRUE]);
    }
    else  if ($result == 'AUTHORIZED') {
      $order->setState(\Magento\Sales\Model\Order::STATE_PAYMENT_REVIEW);
      $order->setStatus(\Magento\Sales\Model\Order::STATE_PAYMENT_REVIEW);
      $order->save();

      $this->_redirect('checkout/onepage/success', ['_secure' => TRUE]);
    }
    else {
      $this->messageManager->addErrorMessage(__('An unknown result occurred in the process of payment'));
      $this->_redirect('checkout/onepage/failure', ['_secure' => TRUE]);
    }
  }
}