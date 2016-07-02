<?php

namespace Conekta\Webhook\Observer;

require_once "Conekta.php";

use \Magento\Framework\Event\ObserverInterface;
use \Magento\Framework\DataObject;
use \Magento\Framework\Magento\Framework;
use \Magento\Framework\App\Config\ScopeConfigInterface as ScopeConfig;
use \Magento\Payment\Model\InfoInterface;

class Observer extends \Magento\Payment\Model\Method\AbstractMethod implements ObserverInterface {

    /**
     * @var ObjectManagerInterface
     * @var LoggerInterface
     */
    protected $_objectManager;
    protected $_model;
    protected $_payment_data;
    protected $_logger;
    protected $_scopeConfig;

    /**
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Conekta\Webhook\Logger\Logger $logger
     */
    function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Conekta\Webhook\Logger\Logger $logger,
        ScopeConfig $scopeConfig
    ) {
        $this->_objectManager = $objectManager;
        $this->_scopeConfig   = $scopeConfig;
        $this->_logger        = $logger;
        $quote = $this->_objectManager->get('Magento\Checkout\Model\Session')->getQuote();
        $sandboxpvt = $this->_scopeConfig->getValue('payment/conekta_card/sandboxprivatekey', \Magento\Store\Model\ScopeInterface::SCOPE_STORE,0);
        if ($sandboxpvt) {
            $this->_logger->addInfo("API Keys are SANDBOX: " . $sandboxpvt);
            \Conekta::setApiKey($sandboxpvt);
        } else {
            $pub_key = $this->_scopeConfig->getValue('payment/conekta_card/productionpublickey', \Magento\Store\Model\ScopeInterface::SCOPE_STORE,0);
            $this->_logger->addInfo("API Keys are LIVE: " . $pub_key);
            \Conekta::setApiKey($pub_key);
        }
        \Conekta::setLocale('es');
    }



    /**
     * customer register event handler
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer) {
        //$items = $order->getAllVisibleItems();

        $event  = $observer->getEvent();
        $quote  = $event->getQuote();
        $order  = $event->getOrderIds();


        $order = $this->_objectManager->create('Magento\Sales\Model\Order')->load($order[0]);

        $items = $order->getAllVisibleItems();
        
        $line_items = array();
        $i = 0;
        foreach ($items as $itemId => $item) {
            $name         = $item->getName();
            $sku          = $item->getSku();
            $price        = $item->getPrice();
            $description  = $this->_objectManager->create('Magento\Catalog\Model\Product')->load($item->getProductId())->getDescription();
            $product_type = $item->getProductType();
            $line_items = array_merge($line_items, array(
                array(
                    'name'        => $name,
                    'sku'         => $sku,
                    'unit_price'  => $price,
                    'description' => $description,
                    'quantity'    => 1,
                    'type'        => $product_type
                )
            ));
            $i = $i + 1;
        }

        $lastid  = $order->getIncrementId();
        $payment = $order->getPayment();

        $_payment_method_code =             $order->getPayment()->getMethodInstance()->getCode();
        //$_payment_method_code =             $quote->getPayment()->getMethodInstance()->getCode();
        //$_payment_method_code = $event->getOrder()->getPayment()->getMethodInstance()->getCode();

        $this->_logger->addInfo("Items in order: " . count($line_items) );
        $this->_logger->addInfo("Order ID:"   . $order->getId() );
        $this->_logger->addInfo("Order increment ID:"   . $order->getIncrementId() );
        $this->_logger->addInfo("Order payment method is "   . $_payment_method_code );
        $this->_logger->addInfo("\n\n\nPayment object type is "   . gettype($payment) );


        if ($_payment_method_code == 'conekta_oxxo') {
            try {
                $_charge_description = "Order " . $lastid . " with charge created with OXXO. " . date("D M d, Y G:i");
                $_company_name = $this->_scopeConfig->getValue('general/store_information/name', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
                // it will get method
                $charge = \Conekta_Charge::create(array(
                    'description'   => $_charge_description,
                    'reference_id'  => $lastid,
                    'amount'        => intval(((float) $order->getGrandTotal()) * 100), 
                    'currency'      => 'MXN',
                    'cash' => array(
                        'type' => 'oxxo'
                        //"expires_at"=>1465215494
                    ),
                    'details' => array(
                        'name'  => $order->getBillingAddress()->getFirstname().' '.$order->getBillingAddress()->getLastname(),
                        'phone' => $order->getBillingAddress()->getTelephone(),
                        'email' => $order->getBillingAddress()->getEmail(),
                        'customer' => array(
                            'logged_in'            => true,
                            'successful_purchases' => 14,
                            'created_at'           => 1379784950,
                            'updated_at'           => 1379784950,
                            'offline_payments'     => 4,
                            'score'                => 9
                        ),
                        'line_items'=> $line_items,
                        'billing_address'=> array(
                            'street1'      => $order->getBillingAddress()->getStreet(),
                            'street2'      => 'Suite 124',
                            'street3'      => null,
                            'city'         => $order->getBillingAddress()->getCity(),
                            'state'        => $order->getBillingAddress()->getRegion(),
                            'zip'          => $order->getBillingAddress()->getPostcode(),
                            'country'      => $order->getBillingAddress()->getCountryId(),
                            'tax_id'       => 'xmn671212drx',
                            'company_name' => $_company_name,
                            'phone'        => $order->getBillingAddress()->getTelephone(),
                            'email'        => $order->getBillingAddress()->getEmail()
                        )
                    )
                )); 

                $this->_logger->addInfo("Charge is of type " . gettype($charge) );
                $this->_logger->addInfo("Charge is of class " . get_class($charge) );
                //$this->_logger->addInfo("Charge Object: " . serialize($charge) );
                $charge_json = json_encode( json_decode($charge) );
                $this->_logger->addInfo("Charge Object: " . $charge_json );

                $this->_logger->addInfo("OXXO status 1 ........  " . $charge->status);
                $this->_logger->addInfo("OXXO barcode 1 .......  " . $charge->payment_method->barcode);
                $this->_logger->addInfo("OXXO barcode URL 1 ...  " . $charge->payment_method->barcode_url);


                
                if ("pending_payment" == $charge->status) {
                    $this->_logger->addInfo("OXXO status 2 ........  " . $charge->status);
                    $this->_logger->addInfo("OXXO barcode 2 .......  " . $charge->payment_method->barcode);
                    $this->_logger->addInfo("OXXO barcode URL 2 ...  " . $charge->payment_method->barcode_url);
                    $payment->setData('cc_trans_id', $charge->id);
                    $payment->setData('cc_avs_status', $charge->status);
                    $payment->setData('cc_number_enc', $charge->payment_method->barcode);
                    $payment->setData('additional_data', $charge->payment_method->barcode_url);
                    $payment->save();

                    $__state  = \Magento\Sales\Model\Order::STATE_NEW;
                    $order->setData('state',  $__state  );
                    $order->setData('status', $__state  );
                    $order->save();
                    $this->_logger->addInfo("Order created with Conekta OXXO Payment");
                }
            } catch (Exception $e) {
                $this->_logger->addInfo("Order created with Conekta OXXO Payment exception: " . $e->getMessage());
            }
        } else if ($_payment_method_code == 'conekta_spei') {
            try {
                $_charge_description = "Order " . $lastid . " with charge created with SPEI. " . date("D M d, Y G:i");
                $charge = \Conekta_Charge::create(array(
                    'description'  => $_charge_description,
                    'reference_id' => $lastid,
                    'amount'       => intval(((float) $order->getGrandTotal()) * 100), 
                    'currency'     => 'MXN',
                    'bank' => array(
                        'type'=>'spei',    
                    ),
                    'details'=> array(
                        'name'=>$order->getBillingAddress()->getFirstname().' '.$order->getBillingAddress()->getLastname(),
                        'phone'=> $order->getBillingAddress()->getTelephone(),
                        'email'=> $order->getBillingAddress()->getEmail(),
                        'customer'=> array(
                            'logged_in'=> true,
                            'successful_purchases'=> 14,
                            'created_at'=> 1379784950,
                            'updated_at'=> 1379784950,
                            'offline_payments'=> 4,
                            'score'=> 9
                        ),
                        'line_items'=> $line_items,
                        'billing_address'=> array(
                            'street1'      => $order->getBillingAddress()->getStreet(),
                            'street2'      => 'Suite 124',
                            'street3'      => null,
                            'city'         => $order->getBillingAddress()->getCity(),
                            'state'        => $order->getBillingAddress()->getRegion(),
                            'zip'          => $order->getBillingAddress()->getPostcode(),
                            'country'      => $order->getBillingAddress()->getCountryId(),
                            'tax_id'       => 'xmn671212drx',
                            'company_name' => 'X-Men Inc.',
                            'phone'        => $order->getBillingAddress()->getTelephone(),
                            'email'        => $order->getBillingAddress()->getEmail()
                        ))
                    )
                );

                $this->_logger->addInfo("Charge is of type " . gettype($charge) );
                $this->_logger->addInfo("Charge is of class " . get_class($charge) );
                $this->_logger->addInfo("Charge Object: " . serialize($charge) );
                $charge_json = json_encode( json_decode($charge) );
                $this->_logger->addInfo("Charge Object: " . $charge_json );


                if ($charge->status == "pending_payment") {
                    $payment->setData('cc_trans_id', $charge->id);
                    $payment->setData('cc_avs_status', $charge->status);
                    $payment->setData('cc_number_enc', $charge->payment_method->clabe);
                    $payment->setData('additional_data', $charge->payment_method->bank);
                    $payment->save();
                    
                    $__state = \Magento\Sales\Model\Order::STATE_NEW;
                    $order->setData('state',  $__state  );
                    $order->setData('status', $__state  );
                    $order->save();
                    $this->_logger->addInfo("Order created with Conekta SPEI Payment");
                }
            } catch (Exception $e) {
                $this->_logger->addInfo("Order created with Conekta SPEI Payment exception: " . $e->getMessage());
            }
        } else if ($_payment_method_code == 'conekta_card') {
            try {
                $_charge_description = "Order " . $lastid . " with charge created with CARD. " . date("D M d, Y G:i");
                /*
                    const STATE_NEW             = 'new';
                    const STATE_PENDING_PAYMENT = 'pending_payment';
                    const STATE_PROCESSING      = 'processing';
                    const STATE_COMPLETE        = 'complete';
                    const STATE_CLOSED          = 'closed';
                    const STATE_CANCELED        = 'canceled';
                    const STATE_HOLDED          = 'holded';
                    const STATE_PAYMENT_REVIEW  = 'payment_review';
                */
                $_state_new = \Magento\Sales\Model\Order::STATE_NEW;
                $order->setData('state', $_state_new);
                $order->setData('status', $_state_new);
                $order->save();
                $this->_logger->addInfo("Order created with Conekta CARD Payment");
            } catch (Exception $e) {
                $this->_logger->addInfo("Order created with Conekta CARD Payment exception: " . $e->getMessage());
            }
        }
        
    }

}

