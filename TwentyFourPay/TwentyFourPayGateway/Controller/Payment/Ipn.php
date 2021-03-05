<?php
namespace TwentyFourPay\TwentyFourPayGateway\Controller\Payment;

require_once __DIR__ . "/../../libs/24pay/Service24Pay.class.php";
require_once __DIR__ . "/../../libs/24pay/Service24PayNotificationParser.class.php";

use Magento\Framework\App\Action\Action;
use Magento\Framework\Exception\PaymentException;

/**
 * This controller handles the server to server notification
 *
 */
class Ipn extends Result {

  /**
   * Constructor
   *
   */
  public function __construct(\TwentyFourPay\TwentyFourPayGateway\Model\Config $config, \Magento\Framework\Message\ManagerInterface $messageManager, \Magento\Framework\App\Action\Context $context) {

    parent::__construct($config, $messageManager, $context);
  }

  protected function getNotificationParser($XMLResponseString = null) {
    return new \Service24PayNotificationParser($this->config->get24PayService() , $XMLResponseString);
  }

  /**
   * Function that processes the IPN (Instant Payment Notification) message of the server.
   *
   * @return \Magento\Framework\Controller\ResultInterface
   */
  public function execute() {

    $response = preg_replace("/<\?.*?\?>/", "", trim((string)$this->getRequest()->getPost('params', NULL)));

    //return;

    $notification = $this->getNotificationParser($response);

    if ($notification->isValid()) {
      $order_id = $this->parseMsTxnId($notification->getMsTxnId());

      if (!$order_id) {
        die('No Order ID');
      }

      $order = $this->getOrder($order_id);
      if (!$order->getId()) {
        die('No Order');
      }

      if (!in_array($order->getState(), array(
        \Magento\Sales\Model\Order::STATE_NEW,
        \Magento\Sales\Model\Order::STATE_HOLDED,
        \Magento\Sales\Model\Order::STATE_PAYMENT_REVIEW
      ))) {
        die();
      }

      if ($notification->transactionHasFailed()) {
        $order->setState(\Magento\Sales\Model\Order::STATE_HOLDED);
        $order->setStatus(\Magento\Sales\Model\Order::STATE_HOLDED);
        $order->save();
      }
      else if ($notification->transactionIsOk()) {
        $order->setState(\Magento\Sales\Model\Order::STATE_PROCESSING);
        $order->setStatus(\Magento\Sales\Model\Order::STATE_PROCESSING);
        $order->save();
      }
      else if ($notification->transactionIsPending()) {
        $order->setState(\Magento\Sales\Model\Order::STATE_PAYMENT_REVIEW);
        $order->setStatus(\Magento\Sales\Model\Order::STATE_PAYMENT_REVIEW);
        $order->save();
      }
      else {
        die('Invalid transaction state');
      }
    }
  }
}
