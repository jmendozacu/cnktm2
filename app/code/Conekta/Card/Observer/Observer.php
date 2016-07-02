<?php

namespace Conekta\Card\Observer;

use \Magento\Framework\Event\ObserverInterface;
use \Magento\Framework\DataObject;
use \Magento\Framework\Magento\Framework;
use \Magento\Framework\App\Config\ScopeConfigInterface as ScopeConfig;
use \Magento\Payment\Model\InfoInterface;

class Observer extends \Magento\Payment\Model\Method\AbstractMethod implements ObserverInterface {

    /**
     * @var ObjectManagerInterface
     */
    protected $_objectManager;
    protected $model;
    protected $payment_data;

    /**
     * Logger
     *
     * @var LoggerInterface
     */
    protected $_logger;

    /**
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Conekta\Webhook\Logger\Logger $logger
     */
    function __construct(
    \Magento\Framework\ObjectManagerInterface $objectManager, \Conekta\Webhook\Logger\Logger $logger, ScopeConfig $scopeConfig
    ) {
        $this->_objectManager = $objectManager;
        $this->scopeConfig = $scopeConfig;
        $this->_logger = $logger;
        $quote = $this->_objectManager->get('Magento\Checkout\Model\Session')->getQuote();
    }

    /**
     * customer register event handler
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer) {
        $event = $observer->getEvent();
        $quote   = $event->getQuote();
        $order   = $event->getOrderIds();
        $lastid  = $order->getId();


        if ($payment->getMethod()->getCode() == 'conekta_card') {
        try {
            /*
            //  const STATE_NEW             = 'new';
            //  const STATE_PENDING_PAYMENT = 'pending_payment';
            //  const STATE_PROCESSING      = 'processing';
            //  const STATE_COMPLETE        = 'complete';
            //  const STATE_CLOSED          = 'closed';
            //  const STATE_CANCELED        = 'canceled';
            //  const STATE_HOLDED          = 'holded';
            //  const STATE_PAYMENT_REVIEW  = 'payment_review';
            $_state_new = \Magento\Sales\Model\Order::STATE_NEW;
            $order->setData('state', $_state_new);
            $order->setData('status', $_state_new);
            $order->save();
            */
            $_payment_method = $order->getPayment()->getMethodInstance()->getCode();
            if ($_payment_method == "conekta_card" || $_payment_method == "conekta_oxxo") {
            //if ($_payment_method == "conekta_spei" || $_payment_method == "conekta_oxxo") {
            } else {
                throw new \Magento\Framework\Validator\Exception(__($_payment_method ) );
            }
            $this->_logger->addInfo("sales_order_place_before event raised. Order object: " . json_encode($order));
            $this->_logger->addInfo("Order created with Conekta Card Payment" );
            } catch (Exception $e) {
                $this->_logger->addInfo("sales_order_place_before event raised. Exception: " . $e->getMessage());
                $this->_logger->addInfo("Order created with Conekta Card Payment exception: " . $e->getMessage() );
            }
        }
    }

}
