<?php


require_once "lib/conekta/lib/Conekta.php";

/**
 * OXXO cash payments Observer
 */
namespace Conekta\Oxxo\Observer;

use \Magento\Framework\Event\ObserverInterface;
use \Magento\Framework\App\Config\ScopeConfigInterface as ScopeConfig;
use \Magento\Framework\DataObject;
use \Magento\Framework\Magento\Framework;
use \Magento\Payment\Model\InfoInterface;

class BeforeOrderPaymentSaveObserver implements ObserverInterface {
//class BeforeOrderPaymentSaveObserver extends \Magento\Payment\Model\Method\AbstractMethod implements ObserverInterface {
  


    protected $_logger;  




    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Conekta\Webhook\Logger\Logger $logger
        )
    {
        $this->_logger = $logger;
        $this->_logger->addInfo(  "\n\n\n\n\n\nObserver OXXO\n\n\n\n\n\n"  );
        parent::__construct($context);
    }









    /**
     * Sets current instructions for OXXO
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $this->_logger->addInfo(  "\n\n\n\n\n\nObserver OXXO\n\n\n\n\n\n"  );

        /** @var \Magento\Sales\Model\Order\Payment $payment */
        /*
        $payment = $observer->getEvent()->getPayment();
        $instructionMethods = [
            Banktransfer::PAYMENT_METHOD_BANKTRANSFER_CODE,
            Cashondelivery::PAYMENT_METHOD_CASHONDELIVERY_CODE
        ];
        if (in_array($payment->getMethod(), $instructionMethods)) {
            $payment->setAdditionalInformation(
                'instructions',
                $payment->getMethodInstance()->getInstructions()
            );
        } elseif ($payment->getMethod() === Checkmo::PAYMENT_METHOD_CHECKMO_CODE) {
            $payment->setAdditionalInformation(
                'payable_to',
                $payment->getMethodInstance()->getPayableTo()
            );
            $payment->setAdditionalInformation(
                'mailing_address',
                $payment->getMethodInstance()->getMailingAddress()
            );
        }
        */
    }
}
