<?php

namespace Conekta\Oxxo\Observer;

require_once "Conekta.php";

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
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     */
    function __construct(
            \Magento\Framework\ObjectManagerInterface $objectManager,
            ScopeConfig $scopeConfig
    ) {
        $this->_objectManager=$objectManager;
        $this->scopeConfig = $scopeConfig;
        $sandboxpvt=$this->scopeConfig->getValue('payment/conekta_card/sandboxprivatekey', \Magento\Store\Model\ScopeInterface::SCOPE_STORE,0);
        $quote = $this->_objectManager->get('Magento\Checkout\Model\Session')->getQuote();
        if($sandboxpvt) {
            \Conekta::setApiKey($sandboxpvt);
        } else {
            $pub_key=$this->scopeConfig->getValue('payment/conekta_card/productionpublickey', \Magento\Store\Model\ScopeInterface::SCOPE_STORE,0);
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

        $myfile = fopen("var/log/debug.log", "a+") or die("Unable to open file!");
        $event  = $observer->getEvent();
        $quote  = $event->getQuote();
        $order  = $event->getOrderIds();










           
        fwrite($myfile, print_r($order,true));
        $order = $this->_objectManager->create('Magento\Sales\Model\Order')->load($order[0]);

        $items = $order->getAllVisibleItems();
        $line_items = array();
        $i = 0;
        foreach ($items as $itemId => $item) {
            $name = $item->getName();
            $sku = $item->getSku();
            $price = $item->getPrice();
            $description = $this->_objectManager->create('Magento\Catalog\Model\Product')->load($item->getProductId())->getDescription();
            $product_type = $item->getProductType();
            $line_items = array_merge($line_items, array(
            	array(
                    'name' => $name,
                    'sku' => $sku,
                    'unit_price' => $price,
                    'description' =>$description,
                    'quantity' => 1,
                    'type' => $product_type
                )
            ));
            $i = $i + 1;
        }


        $lastid  = (string)$order->getId();
        $lastid1 = $this->_objectManager->create('Magento\Sales\Model\Order\Payment')->getCollection()->addFieldToFilter('parent_id', $lastid)->getFirstItem();

        $_charge_description = "Order " . $lastid . " with charge created with OXXO. " . date("D M d, Y G:i");

        $payment = $this->_objectManager->create('Magento\Sales\Model\Order\Payment')->load($lastid1->getEntityId());




        if ($payment->getMethod() == 'conekta_oxxo') {
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
                        'company_name' => 'X-Men Inc.',
                        'phone'        => $order->getBillingAddress()->getTelephone(),
                        'email'        => $order->getBillingAddress()->getEmail()
                    )
                )
        	)); 

            
	        $payment->setData('cc_trans_id',$charge->id);
	        $payment->setData('cc_avs_status',$charge->status);
	        $payment->setData('cc_number_enc',$charge->payment_method->barcode);
	        $payment->setData('additional_data',$charge->payment_method->barcode_url);
	        $payment->save();

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

	        $__state  = \Magento\Sales\Model\Order::STATE_NEW;
	        $order->setData('state',  $__state  );
	        $order->setData('status', $__state  );
	        $order->save();
        } else if ($payment->getMethod() == 'conekta_spei') {
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

	        $payment->setData('cc_trans_id',$charge->id);
	        $payment->setData('cc_avs_status',$charge->status);
	        $payment->setData('cc_number_enc',$charge->payment_method->clabe);
	        $payment->setData('additional_data',$charge->payment_method->bank);
	        $payment->save();
	        $order->setData('state',  'pending');

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
	        $__state = \Magento\Sales\Model\Order::STATE_NEW;
	        $order->setData('state',  $__state  );
	        $order->setData('status', $__state  );
	        $order->save();
        } else if ($payment->getMethod() == 'conekta_card') {
            try {
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
                $this->_logger->addInfo("Order created with Conekta Card Payment");
            } catch (Exception $e) {
                $this->_logger->addInfo("Order created with Conekta Card Payment exception: " . $e->getMessage());
            }
        }
        fclose($myfile); 
    }

}















		

        