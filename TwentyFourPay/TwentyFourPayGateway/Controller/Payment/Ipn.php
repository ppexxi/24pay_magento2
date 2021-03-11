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
class Ipn extends Result implements \Magento\Framework\App\Action\HttpPostActionInterface, \Magento\Framework\App\CsrfAwareActionInterface {

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

  public function createCsrfValidationException(\Magento\Framework\App\RequestInterface $request): ? \Magento\Framework\App\Request\InvalidRequestException {
    return null;
  }

  public function validateForCsrf(\Magento\Framework\App\RequestInterface $request): ?bool {
    return true;
  }

  /**
   * Function that processes the IPN (Instant Payment Notification) message of the server.
   *
   * @return \Magento\Framework\Controller\ResultInterface
   */
  public function execute() {

    $response_text = trim((string)$this->getRequest()->getPost('params', NULL));
    if (!$response_text) {
      $response_text = trim((string)$this->getRequest()->getParam('params', NULL));
    }

    $response = preg_replace("/<\?.*?\?>/", "", $response_text);

    //return;

    $notification = $this->getNotificationParser($response);

    if ($notification->isValid()) {
      $order_id = $this->parseMsTxnId($notification->getMsTxnId());

      if (!$order_id) {
        http_response_code(400);

        //trigger_error('No Order ID');
        die('No Order ID');
      }

      $order = $this->getOrder($order_id);
      if (!$order->getId()) {
        http_response_code(400);

        //trigger_error('No Order [' . $order_id . ']');
        die('No Order');
      }

      $response = $this->createResponse();
      $response->setContents('');

      //var_dump($order_id); die();
      //var_dump($order->getStatus()); die();

      if (!in_array($order->getStatus(), array(
        \Magento\Sales\Model\Order::STATE_NEW,
        \Magento\Sales\Model\Order::STATE_HOLDED,
        \Magento\Sales\Model\Order::STATE_PENDING_PAYMENT,
        \Magento\Sales\Model\Order::STATE_PAYMENT_REVIEW
      ))) {

        //trigger_error('24Pay (notification): No change [' . $order_id . ']');

        $response->setHttpResponseCode(200);
        return $response;
      }


      if ($notification->transactionHasFailed() && ($order->getStatus() != \Magento\Sales\Model\Order::STATE_HOLDED)) {
        $order->setState(\Magento\Sales\Model\Order::STATE_HOLDED);
        $order->setStatus(\Magento\Sales\Model\Order::STATE_HOLDED);
        
        //trigger_error('24Pay (notification): Payment failed [' . $order_id . ']');
        $order->addStatusHistoryComment('24Pay (notification): Payment failed');
        $order->save();
      }
      else if ($notification->transactionIsOk() && ($order->getStatus() != \Magento\Sales\Model\Order::STATE_PROCESSING)) {
        $order->setState(\Magento\Sales\Model\Order::STATE_PROCESSING);
        $order->setStatus(\Magento\Sales\Model\Order::STATE_PROCESSING);
        
        //trigger_error('24Pay (notification): Payment success [' . $order_id . ']');
        $order->addStatusHistoryComment('24Pay (notification): Payment success');
        $order->save();
      }
      else if ($notification->transactionIsPending() && ($order->getStatus() != \Magento\Sales\Model\Order::STATE_PAYMENT_REVIEW)) {
        $order->setState(\Magento\Sales\Model\Order::STATE_PAYMENT_REVIEW);
        $order->setStatus(\Magento\Sales\Model\Order::STATE_PAYMENT_REVIEW);

        //trigger_error('24Pay (notification): Payment pending [' . $order_id . ']');
        $order->addStatusHistoryComment('24Pay (notification): Payment pending');
        $order->save();
      }
      else {
        /*$order->addStatusHistoryComment('24Pay (notification): Unknown state [error]');
        $order->save();

        http_response_code(400);
        die('Invalid transaction state');*/
      }

      $response->setHttpResponseCode(200);
      return $response;
    }
  }
}
