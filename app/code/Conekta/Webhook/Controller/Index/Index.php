<?php

namespace Conekta\Webhook\Controller\Index;

use \Magento\Framework\App\Config\ScopeConfigInterface as ScopeConfig;

class Index extends \Magento\Framework\App\Action\Action {

    /**
     * Logger
     *
     * @var LoggerInterface
     */
    protected $_logger;


    /**
     *
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Conekta\Webhook\Logger\Logger $logger
     *
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Conekta\Webhook\Logger\Logger $logger,
        ScopeConfig $scopeConfig
    ) {
        $this->_logger     = $logger;
        $this->scopeConfig = $scopeConfig;
        parent::__construct($context);
    }


    /**
     * Controller Index, process Conekta webhook posts
     *
     * @return void
     */
    public function execute() {


        $_input   = @file_get_contents('php://input');
        $_request = json_decode($_input);


        //$this->_logger->addInfo( "Raw input: " . $_input );
        //header("CONEKTA_REQUEST_RAW: "  . $_input);
        //header("CONEKTA_REQUEST_JSON: " . json_encode($_request) );
        //$this->_logger->addInfo( "JSON: "      . json_encode($_request) );


        if (  isset( $_request->type)  ) {
            $_type    = $_request->type;
            /*
            $_object              = $_request->object;
            $_reference_id        = $_request->data->object->reference_id;
            $_payment_method_type = $_request->data->object->payment_method->type;
            $_livemode            = $_request->data->object->livemode;
            $_status              = $_request->data->object->status;
            $_previous_attributes = $_request->data->previous_attributes->status;
            $_webhook_status      = $_request->webhook_status;

          
            header('CONEKTA_CONTROL: '          . 
                "request_object: "              . $_object              . " | " .
                "request_type: "                . $_type                . " | " .
                "request_reference_id: "        . $_reference_id        . " | " .
                "request_payment_method_type: " . $_payment_method_type . " | " .
                "request_livemode: "            . $_livemode            . " | " .
                "request_status: "              . $_status              . " | " .
                "request_previous_attributes: " . $_previous_attributes . " | " .
                "request_webhook_status: "      . $_webhook_status
            );
            */




            if (  $_type == "charge.created" || $_type == "charge.paid"  ) {
                // CC, OXXO or SPEI charge


                if (  isset($_request->data->object->payment_method->type)  ) {
                    $_payment_method_type = $_request->data->object->payment_method->type;  // OXXO or SPEI
                    // SPEI or OXXO
                    if ( $_payment_method_type == "oxxo" && $_type == "charge.created" ) {
                        $this->_processOxxoChargeCreated($_request);
                    }
                    if ( $_payment_method_type == "oxxo" && $_type == "charge.paid" ) {
                        $this->_processOxxoChargePaid($_request);
                    }
                    if ( $_payment_method_type == "spei" && $_type == "charge.created" ) {
                        $this->_processSpeiChargeCreated($_request);
                    }
                    if ( $_payment_method_type == "spei" && $_type == "charge.paid" ) {
                        $this->_processSpeiChargePaid($_request);
                    }
                    $this->_processBadRequest($_request, "OXXO or SPEI charge created or paid, but missing data");                    
                } else {
                    // Credit/debit card
                    if ($_type == "charge.created") {
                        $this->_processCardChargeCreated($_request);
                    } else if (  $_type == "charge.paid"  ) {
                        $_id = $_request->id;
                        if ( $_id == "51cef9faf23668b1f4000001" ) {
                            $this->_processConektaWebhookTest($_request);
                        } else {
                            $this->_processCardChargePaid($_request);
                        }
                    } else {
                        $this->_processBadRequest($_request, "Credit/Debit card charge created or paid, but missing data");
                    }
                }
            } else if (  $_type == "plan.create"  ) {
                
                $this->_processPlanCreate($_request);
            } else if (  $_type == "subscription.created" ) {
                
                $this->_processSubscriptionCreated($_request);
            } else if (  $_type == "charge.chargeback.created" ) {
                
                $this->_processChargeChargebackCreated($_request);
            } else if (  $_type == "charge.chargeback.won"  ) {
                
                $this->_processChargeChargebackWon($_request);
            } else if (  $_type == "charge.chargeback.lost"  ) {
                
                $this->_processChargeChargebackLost($_request);
            } else if (  $_type == "subscription.paid" ) {                    
                
                $this->_processSubscriptionPaid($_request);
            } else if (  $_type == "customer.created"  ) {
                
                $this->_processCustomerCreated($_request);
            } else if (  $_type == "webhook.updated"  ) {
                
                $this->_processWebhookUpdated($_request);
            } else {
                $this->_processBadRequest($_request, "No operation type allowed");
            }
        } else {
            $this->_processBadRequest($_request, "The request is not from Conekta");
        }
    }   // execute































    private function _processCardChargeCreated($_request) {
        //$this->_logger->addInfo( "Credit/debit card charge created: " . json_encode($_request) );

        $_object               = $_request->object;                                 // event
        $_type                 = $_request->type;                                   // charge.created
        $_webhook_status       = $_request->webhook_status;                         // pending

        $_livemode             = $_request->data->object->livemode;                 // boolean
        $_amount               = $_request->data->object->amount;                   // integer
        $_status               = $_request->data->object->status;                   // pending_payment
        $_reference_id         = $_request->data->object->reference_id;             

        //$_payment_method_type  = $_request->data->object->payment_method->type;
        //$_previous_attributes  = $_request->data->previous_attributes->status;      // no siempre definido
        //$_name                 = $_request->data->object->details->name;

        if ( $_object              == "event"           && 
             $_type                == "charge.created"  && 
             $_webhook_status      == "pending"
        ) {

            if ($_status  == "pending_payment" && 
                isset($_reference_id)          && 
                isset($_amount)                && 
                isset($_livemode)
            ) {
                try {
                    $order         = $this->_objectManager->create('Magento\Sales\Model\Order')->load(  $_reference_id  );
                    $_grand_total  = $order->getGrandTotal();
                    $_order_status = $this->scopeConfig->getValue('payment/conekta_card/order_status', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, 0);
                    $_order_status = \Magento\Sales\Model\Order::STATE_CANCELED;
                    /*
                    STATE_NEW             = 'new';
                    STATE_PENDING_PAYMENT = 'pending_payment';
                    STATE_PROCESSING      = 'processing';
                    STATE_COMPLETE        = 'complete';
                    STATE_CLOSED          = 'closed';
                    STATE_CANCELED        = 'canceled';
                    STATE_HOLDED          = 'holded';
                    STATE_PAYMENT_REVIEW  = 'payment_review';
                    */
                    $order->setData('state',  $_order_status);
                    $order->setData('status', $_order_status);
                    $order->save();

                    if ($_livemode) {
                        header("CONEKTA_status:   " . $_order_status);
                        header("CONEKTA_amount:   " . $_amount);
                        header("CONEKTA_grandtotal:" . $_grand_total);
                        //$this->_logger->addInfo( "Reference ID: " . $_reference_id . " is not paid");
                        mail("emendoza@magen4me.com", "Conekta LIVE card charge created", json_encode($_request, JSON_PRETTY_PRINT));
                    } else {
                        header('CONEKTA_DEBUG: ' . $_amount . " // " . $_grand_total );
                        header('CONEKTA_EVENT: Card charge created OK');
                        mail("emendoza@magen4me.com", "Conekta SAND card charge created", json_encode($_request, JSON_PRETTY_PRINT));
                    }
                    //$order->setData('base_total_paid', intval(((float) $_amount) / 100));
                    //$this->_logger->addInfo( "Reference ID: " . $_reference_id . " is not paid");
                    header("HTTP/1.1 200 OK");
                    die();
                } catch (Exception $e) {
                    //$this->_logger->addInfo( "Excepcion en OXXO not Paid: " . $e->getMessage() );
                    header('CONEKTA_EVENT: Card charge created failed. Exception: ' . $e->getMessage() );
                    header("HTTP/1.1 404 Not Found");
                    die();
                }

                //$lastid=$order->getId();
                //$this->_logger->addInfo( "Reference ID: " . $_reference_id . " is not paid");
                header('CONEKTA_EVENT: Card charge created failed');
                header("HTTP/1.1 404 Not Found");
                mail("emendoza@magen4me.com", "Conekta Webhook notification", json_encode($_request, JSON_PRETTY_PRINT));
                die();
            } else {
                //$this->_logger->addInfo( "Reference ID: " . $_reference_id . " is paid");
                header('CONEKTA_EVENT: Card charge paid failed');
                header("HTTP/1.1 404 Not Found");
                mail("emendoza@magen4me.com", "Conekta Wbhk: Card charge created failed", json_encode($_request, JSON_PRETTY_PRINT));
                die();
            }
            
        } else {
            //$this->_logger->addInfo( "Reference ID: " . $_reference_id . " is paid");
            header('CONEKTA_EVENT: Card charge created failed' );
            mail("emendoza@magen4me.com", "Conekta Webhook notification", json_encode($_request, JSON_PRETTY_PRINT));
            header("HTTP/1.1 404 Not Found");
            die();
        }
    }   // _processCardChargeCreated($_request)




















    private function _processOxxoChargeCreated($_request) {


        $_object               = $_request->object;                                 // event
        $_type                 = $_request->type;                                   // charge.created
        $_webhook_status       = $_request->webhook_status;                         // pending

        $_livemode             = $_request->data->object->livemode;                 // boolean
        $_amount               = $_request->data->object->amount;                   // integer
        $_status               = $_request->data->object->status;                   // pending_payment
        $_reference_id         = $_request->data->object->reference_id;

        $_payment_method_type  = $_request->data->object->payment_method->type;
        //$_previous_attributes  = $_request->data->previous_attributes->status;      // no siempre definido
        //$_name                 = $_request->data->object->details->name;

        if ( $_object              == "event"           && 
             $_type                == "charge.created"  && 
             $_payment_method_type == "oxxo"            && 
             $_webhook_status      == "pending"
        ) {

            if ($_status  == "pending_payment" && 
                isset($_reference_id)          && 
                isset($_amount)                && 
                isset($_livemode)
            ) {
                try {
                    $order         = $this->_objectManager->create('Magento\Sales\Model\Order')->load(  $_reference_id  );
                    $_grand_total  = $order->getGrandTotal();
                    $_order_status = $this->scopeConfig->getValue('payment/conekta_oxxo/order_status', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, 0);
                    $_order_status = \Magento\Sales\Model\Order::STATE_CANCELED;
                    /*
                    STATE_NEW             = 'new';
                    STATE_PENDING_PAYMENT = 'pending_payment';
                    STATE_PROCESSING      = 'processing';
                    STATE_COMPLETE        = 'complete';
                    STATE_CLOSED          = 'closed';
                    STATE_CANCELED        = 'canceled';
                    STATE_HOLDED          = 'holded';
                    STATE_PAYMENT_REVIEW  = 'payment_review';
                    */
                    $order->setData('state',  $_order_status);
                    $order->setData('status', $_order_status);
                    $order->save();

                    if ($_livemode) {
                        header("CONEKTA_status:   " . $_order_status);
                        header("CONEKTA_amount:   " . $_amount);
                        header("CONEKTA_grandtotal:" . $_grand_total);
                        //$this->_logger->addInfo( "Reference ID: " . $_reference_id . " is not paid");
                        mail("emendoza@magen4me.com","Conekta LIVE OXXO charge created", json_encode($_request, JSON_PRETTY_PRINT));
                    } else {
                        header('CONEKTA_DEBUG: ' . $_amount . " // " . $_grand_total );
                        header('CONEKTA_EVENT: OXXO charge created OK');
                        mail("emendoza@magen4me.com","Conekta SAND OXXO charge created", json_encode($_request, JSON_PRETTY_PRINT));
                    }
                    //$order->setData('base_total_paid', intval(((float) $_amount) / 100));
                    //$this->_logger->addInfo( "Reference ID: " . $_reference_id . " is not paid");
                    header("HTTP/1.1 200 OK");
                    die();
                } catch (Exception $e) {
                    //$this->_logger->addInfo( "Excepcion en OXXO not Paid: " . $e->getMessage() );
                    header('CONEKTA_EVENT: OXXO charge create failed. Exception: ' . $e->getMessage() );
                    header("HTTP/1.1 404 Not Found");
                    die();
                }

                //$lastid=$order->getId();
                //$this->_logger->addInfo( "Reference ID: " . $_reference_id . " is not paid");
                header('CONEKTA_EVENT: OXXO charge created failed');
                header("HTTP/1.1 404 Not Found");
                mail("emendoza@magen4me.com", "Conekta Webhook notification", json_encode($_request, JSON_PRETTY_PRINT));
                die();
            } else {
                //$this->_logger->addInfo( "Reference ID: " . $_reference_id . " is paid");
                header('CONEKTA_EVENT: OXXO charge created failed');
                header("HTTP/1.1 404 Not Found");
                mail("emendoza@magen4me.com", "Conekta Wbhk: OXXO charge created failed", json_encode($_request, JSON_PRETTY_PRINT));
                die();
            }
            
        } else {
            //$this->_logger->addInfo( "Reference ID: " . $_reference_id . " is paid");
            header('CONEKTA_EVENT: OXXO charge created failed' );
            mail("emendoza@magen4me.com", "Conekta Webhook notification", json_encode($_request, JSON_PRETTY_PRINT));
            header("HTTP/1.1 404 Not Found");
            die();
        }
    }   // _processOxxoChargeCreated($_request)






























    private function _processSpeiChargeCreated($_request) {


        $_object               = $_request->object;                                 // event
        $_type                 = $_request->type;                                   // charge.created
        $_webhook_status       = $_request->webhook_status;                         // pending

        $_livemode             = $_request->data->object->livemode;                 // boolean
        $_amount               = $_request->data->object->amount;                   // integer
        $_status               = $_request->data->object->status;                   // pending_payment
        $_reference_id         = $_request->data->object->reference_id;

        $_payment_method_type  = $_request->data->object->payment_method->type;
        //$_previous_attributes  = $_request->data->previous_attributes->status;      // no siempre definido
        //$_name                 = $_request->data->object->details->name;

        if ( $_object              == "event"           && 
             $_type                == "charge.created"  && 
             $_payment_method_type == "spei"            && 
             $_webhook_status      == "pending"
        ) {

            if ($_status  == "pending_payment" && 
                isset($_reference_id)          && 
                isset($_amount)                && 
                isset($_livemode)
            ) {
                try {
                    $order         = $this->_objectManager->create('Magento\Sales\Model\Order')->load(  $_reference_id  );
                    $_grand_total  = $order->getGrandTotal();
                    $_order_status = $this->scopeConfig->getValue('payment/conekta_spei/order_status', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, 0);
                    $_order_status = \Magento\Sales\Model\Order::STATE_CANCELED;
                    /*
                    STATE_NEW             = 'new';
                    STATE_PENDING_PAYMENT = 'pending_payment';
                    STATE_PROCESSING      = 'processing';
                    STATE_COMPLETE        = 'complete';
                    STATE_CLOSED          = 'closed';
                    STATE_CANCELED        = 'canceled';
                    STATE_HOLDED          = 'holded';
                    STATE_PAYMENT_REVIEW  = 'payment_review';
                    */
                    $order->setData('state',  $_order_status);
                    $order->setData('status', $_order_status);
                    $order->save();

                    if ($_livemode) {
                        header("CONEKTA_status:   " . $_order_status);
                        header("CONEKTA_amount:   " . $_amount);
                        header("CONEKTA_grandtotal:" . $_grand_total);
                        //$this->_logger->addInfo( "Reference ID: " . $_reference_id . " is not paid");
                        mail("emendoza@magen4me.com","Conekta LIVE OXXO charge created", json_encode($_request, JSON_PRETTY_PRINT));
                    } else {
                        header('CONEKTA_DEBUG: ' . $_amount . " // " . $_grand_total );
                        header('CONEKTA_EVENT: SPEI charge created OK');
                        mail("emendoza@magen4me.com", "Conekta SAND SPEI charge created", json_encode($_request, JSON_PRETTY_PRINT));
                    }
                    //$order->setData('base_total_paid', intval(((float) $_amount) / 100));
                    //$this->_logger->addInfo( "Reference ID: " . $_reference_id . " is not paid");
                    header("HTTP/1.1 200 OK");
                    die();
                } catch (Exception $e) {
                    //$this->_logger->addInfo( "Excepcion en SPEI not Paid: " . $e->getMessage() );
                    header('CONEKTA_EVENT: SPEI charge create failed. Exception: ' . $e->getMessage() );
                    header("HTTP/1.1 404 Not Found");
                    die();
                }

                //$lastid=$order->getId();
                //$this->_logger->addInfo( "Reference ID: " . $_reference_id . " is not paid");
                header('CONEKTA_EVENT: SPEI charge created failed');
                header("HTTP/1.1 404 Not Found");
                mail("emendoza@magen4me.com", "Conekta Webhook notification", json_encode($_request, JSON_PRETTY_PRINT));
                die();
            } else {
                //$this->_logger->addInfo( "Reference ID: " . $_reference_id . " is paid");
                header('CONEKTA_EVENT: SPEI charge created failed');
                header("HTTP/1.1 404 Not Found");
                mail("emendoza@magen4me.com", "Conekta Wbhk: SPEI charge created failed", json_encode($_request, JSON_PRETTY_PRINT));
                die();
            }
            
        } else {
            //$this->_logger->addInfo( "Reference ID: " . $_reference_id . " is paid");
            header('CONEKTA_EVENT: SPEI charge created failed' );
            mail("emendoza@magen4me.com", "Conekta Webhook notification", json_encode($_request, JSON_PRETTY_PRINT));
            header("HTTP/1.1 404 Not Found");
            die();
        }

    }   // _processSpeiChargeCreated($_request)










































    private function _processCardChargePaid($_request) {
        try {
            //$this->_objectManager->get('Psr\Log\LoggerInterface')->debug(  "Pago con Tarjeta de credito debito"  );
            $this->_logger->addInfo( "_processCardChargePaid request: " . json_encode($_request) );
            $_type         = $_request->type;
            //$_payment      = $_request->data->object->payment_method->type;
            //$_reference_id = $_request->data->object->reference_id;
            $_status       = $_request->data->object->status;
            $_amount       = $_request->data->object->amount;
            //$_name         = $_request->data->object->details->name;

            if ($_status == "paid") {
                //$this->_logger->addInfo( "Reference ID: " . $_reference_id );

                if (isset($_reference_id) && $_status == "paid" ) {
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

                        $order                = $this->_objectManager->create('Magento\Sales\Model\Order')->load(  $_reference_id  );
                        $_payment_method_code = $order->getPayment()->getMethodInstance()->getCode();
                        $_grand_total         = $order->getGrandTotal();

                        //$this->_logger->addInfo( "Amount  ..........  : " . $_amount );
                        //log $order->getData();
                        $_order_status = $this->scopeConfig->getValue('payment/conekta_card/order_status', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, 0);
                        $_order_status = \Magento\Sales\Model\Order::STATE_CLOSED;
                        //$order->setData('state',  $_order_status);
                        //$order->setData('status', $_order_status);
                        //$order->setData('base_total_paid', intval(((float) $_amount) / 100));
                        $order->save();
                        //$this->_logger->addInfo( "Reference ID: " . $_reference_id . " is not paid");
                        header('CONEKTA: OXXO charge paid OK');
                        header("HTTP/1.1 200 OK");
                        die();
                    } catch (Exception $e) {
                        //$this->_logger->addInfo( "Excepcion en OXXO not Paid: " . $e->getMessage() );
                        header('CONEKTA: Card charge paid failed 1');
                        header("HTTP/1.1 404 Not Found");
                        die();
                    }
                    

                    
                    //$lastid=$order->getId();
                    //$this->_logger->addInfo( "Reference ID: " . $_reference_id . " is not paid");
                    header('CONEKTA: Card charge paid failed 2');
                    header("HTTP/1.1 404 Not Found");
                    mail("emendoza@magen4me.com","Conekta Webhook notification", json_encode($_request, JSON_PRETTY_PRINT));
                    die();
                    //header("HTTP/1.1 404 Not Found");
                } else {
                    //$this->_logger->addInfo( "Reference ID: " . $_reference_id . " is paid");
                    header('CONEKTA: Card charge paid failed 3');
                    header("HTTP/1.1 404 Not Found");
                    mail("emendoza@magen4me.com","Conekta Webhook notification", json_encode($_request, JSON_PRETTY_PRINT));
                    die();
                }
                
            } else {
                //$this->_logger->addInfo( "Reference ID: " . $_reference_id . " is paid");
                header('CONEKTA: Card charge paid failed 4' );
                header("HTTP/1.1 404 Not Found");
                mail("emendoza@magen4me.com","Conekta Webhook notification", json_encode($_request, JSON_PRETTY_PRINT));
                die();
            }
        } catch (Exception $e) {
            $this->_logger->addInfo( "Card paid. Exception: " . $e->getMessage() );
        } finally {
            $this->_logger->addInfo( "Card paid. " . json_encode($_request) );
        }
    }





















    private function _processOxxoChargePaid($_request) {
        //$this->_objectManager->get('Psr\Log\LoggerInterface')->debug(  "Pago con OXXO"  );
        $_type          = $_request->type;
        $_payment       = $_request->data->object->payment_method->type;
        $_reference_id  = $_request->data->object->reference_id;
        $_status        = $_request->data->object->status;
        $_amount        = $_request->data->object->amount;
        $_name          = $_request->data->object->details->name;


        $_order_status = $this->scopeConfig->getValue('payment/conekta_oxxo/order_status', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, 0);

        if ($_status == "paid") {
            //$this->_logger->addInfo( "Reference ID: " . $_reference_id );

            if (isset($_reference_id) && $_status == "paid" ) {
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

                    $order                = $this->_objectManager->create('Magento\Sales\Model\Order')->load(  $_reference_id  );
                    $_payment_method_code = $order->getPayment()->getMethodInstance()->getCode();
                    $_grand_total         = $order->getGrandTotal();

                    //$this->_logger->addInfo( "Amount  ..........  : " . $_amount );
                    //log $order->getData();
                    

                    $_order_status = $this->scopeConfig->getValue('payment/conekta_oxxo/order_status', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, 0);
                    $_order_status = \Magento\Sales\Model\Order::STATE_CLOSED;
                    $order->setData('state',  $_order_status);
                    $order->setData('status', $_order_status);
                    //$order->setData('base_total_paid', intval(((float) $_amount) / 100));
                    $order->save();
                    //$this->_logger->addInfo( "Reference ID: " . $_reference_id . " is not paid");
                    header('CONEKTA: OXXO charge paid OK');
                    header("HTTP/1.1 200 OK");
                    mail("emendoza@magen4me.com","Conekta Webhook notification", json_encode($_request, JSON_PRETTY_PRINT));
                    die();
                } catch (Exception $e) {
                    //$this->_logger->addInfo( "Excepcion en OXXO not Paid: " . $e->getMessage() );
                    header('CONEKTA: OXXO charge paid failed 1');
                    header("HTTP/1.1 404 Not Found");
                    mail("emendoza@magen4me.com","Conekta Webhook notification", json_encode($_request, JSON_PRETTY_PRINT));
                    die();
                }

                //$lastid=$order->getId();
                //$this->_logger->addInfo( "Reference ID: " . $_reference_id . " is not paid");
                header('CONEKTA: OXXO charge paid failed 2');
                header("HTTP/1.1 404 Not Found");
                mail("emendoza@magen4me.com","Conekta Webhook notification", json_encode($_request, JSON_PRETTY_PRINT));
                die();
                //header("HTTP/1.1 404 Not Found");
            } else {
                //$this->_logger->addInfo( "Reference ID: " . $_reference_id . " is paid");
                header('CONEKTA: OXXO charge paid failed 3');
                header("HTTP/1.1 404 Not Found");
                mail("emendoza@magen4me.com","Conekta Webhook notification", json_encode($_request, JSON_PRETTY_PRINT));
                die();
            }

            
        } else {
            //$this->_logger->addInfo( "Reference ID: " . $_reference_id . " is paid");
            header('CONEKTA: OXXO charge paid failed 4' );
            header("HTTP/1.1 404 Not Found");
            mail("emendoza@magen4me.com","Conekta Webhook notification", json_encode($_request, JSON_PRETTY_PRINT));
            die();
        }
    }








    private function _processSpeiChargePaid($_request) {
        //$this->_objectManager->get('Psr\Log\LoggerInterface')->debug(  "Pago con SPEI"  );
        $_type          = $_request->type;
        $_payment       = $_request->data->object->payment_method->type;
        $_reference_id  = $_request->data->object->reference_id;
        $_status        = $_request->data->object->status;
        $_amount        = $_request->data->object->amount;
        $_name          = $_request->data->object->details->name;

        if ($_status == "paid") {
            //$this->_logger->addInfo( "Reference ID: " . $_reference_id );

            if (isset($_reference_id) && $_status == "paid" ) {
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

                    $order                = $this->_objectManager->create('Magento\Sales\Model\Order')->load(  $_reference_id  );
                    $_payment_method_code = $order->getPayment()->getMethodInstance()->getCode();
                    $_grand_total         = $order->getGrandTotal();

                    //$this->_logger->addInfo( "Amount  ..........  : " . $_amount );
                    //log $order->getData();
                    $_order_status = $this->scopeConfig->getValue('payment/conekta_spei/order_status', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, 0);
                    $_order_status = \Magento\Sales\Model\Order::STATE_CLOSED;
                    $order->setData('state',  $_order_status);
                    $order->setData('status', $_order_status);
                    //$order->setData('base_total_paid', intval(((float) $_amount) / 100));
                    $order->save();
                    //$this->_logger->addInfo( "Reference ID: " . $_reference_id . " is not paid");
                    header('CONEKTA: OXXO charge paid OK');
                    header("HTTP/1.1 200 OK");
                    mail("emendoza@magen4me.com","Conekta Webhook notification", json_encode($_request, JSON_PRETTY_PRINT));
                    die();
                } catch (Exception $e) {
                    //$this->_logger->addInfo( "Excepcion en OXXO not Paid: " . $e->getMessage() );
                    header('CONEKTA: SPEI charge paid failed');
                    header("HTTP/1.1 404 Not Found");
                    mail("emendoza@magen4me.com","Conekta Webhook notification", json_encode($_request, JSON_PRETTY_PRINT));
                    die();
                }

                //$lastid=$order->getId();
                //$this->_logger->addInfo( "Reference ID: " . $_reference_id . " is not paid");
                header('CONEKTA: SPEI charge paid failed');
                header("HTTP/1.1 404 Not Found");
                mail("emendoza@magen4me.com","Conekta Webhook notification", json_encode($_request, JSON_PRETTY_PRINT));
                die();
                //header("HTTP/1.1 404 Not Found");
            } else {
                //$this->_logger->addInfo( "Reference ID: " . $_reference_id . " is paid");
                header('CONEKTA: SPEI charge paid failed');
                header("HTTP/1.1 404 Not Found");
                mail("emendoza@magen4me.com","Conekta Webhook notification", json_encode($_request, JSON_PRETTY_PRINT));
                die();
            }
            
        } else {
            //$this->_logger->addInfo( "Reference ID: " . $_reference_id . " is paid");
            header('CONEKTA: SPEI charge paid failed' );
            header("HTTP/1.1 404 Not Found");
            mail("emendoza@magen4me.com","Conekta Webhook notification", json_encode($_request, JSON_PRETTY_PRINT));
            die();
        }
    }   //  _processSpeiChargePaid
















    private function _denyProcess($_request, $_message) {
        $this->_logger->addInfo("Execution denied. Request body:" . json_encode($_request) );
        $this->_logger->addInfo("Execution denied. Message:"      . $_message );
        header("CONEKTA_EVENT: Execution denied");
        header("CONEKTA_MESSAGE: " . $_message);
        header("HTTP/1.1 404 Not Found");
        die();
    }



















    private function _processChargeChargebackCreated($_request) {
        $_message = "Event type is CHARGE CHARGEBACK CREATED but is not implemented";
        //$this->_logger->addInfo( $_message );
        header( "CONEKTA: Charge chargeback created. " . $_message );
        header( "HTTP/1.1 404 Not Found" );
        mail("emendoza@magen4me.com","Conekta Webhook notification", json_encode($_request, JSON_PRETTY_PRINT));
        die();
    }


    private function _processChargeChargebackLost($_request) {
        $_message = "Event type is CHARGE CHARGEBACK LOST but is not implemented";
        //$this->_logger->addInfo( $_message );
        header( "CONEKTA: Charge chargeback lost. " . $_message );
        header( "HTTP/1.1 404 Not Found" );
        mail("emendoza@magen4me.com","Conekta Webhook notification", json_encode($_request, JSON_PRETTY_PRINT));
        die();
    }


    private function _processChargeChargebackWon($_request) {
        $_message = "Event type is CHARGE CHARGEBACK WON but is not implemented";
        //$this->_logger->addInfo( $_message );
        header( "CONEKTA: Charge chargeback won. " . $_message );
        header( "HTTP/1.1 404 Not Found" );
        mail("emendoza@magen4me.com","Conekta Webhook notification", json_encode($_request, JSON_PRETTY_PRINT));
        die();
    }


    private function _processCustomerCreated($_request) {
        $_message = "Event type is CUSTOMER CREATED but is not implemented";
        //$this->_logger->addInfo( $_message );
        header( "CONEKTA: Customer created. " . $_message );
        header( "HTTP/1.1 404 Not Found" );
        mail("emendoza@magen4me.com","Conekta Webhook notification", json_encode($_request, JSON_PRETTY_PRINT));
        die();
    }


    private function _processSubscriptionCreated($_request) {
        $_message = "Event type is SUBSCRIPTION CREATED but is not implemented";
        //$this->_logger->addInfo( $_message );
        header( "CONEKTA: Subscription created. " . $_message );
        header( "HTTP/1.1 404 Not Found" );
        mail("emendoza@magen4me.com","Conekta Webhook notification", json_encode($_request, JSON_PRETTY_PRINT));
        die();
    }


    private function _processSubscriptionPaid($_request) {
        $_message = "Event type is SUBSCRIPTION PAID but is not implemented";
        //$this->_logger->addInfo( $_message );
        header( "CONEKTA: Subscription paid. " . $_message );
        header( "HTTP/1.1 404 Not Found" );
        mail("emendoza@magen4me.com","Conekta Webhook notification", json_encode($_request, JSON_PRETTY_PRINT));
        die();
    }


    private function _processPlanCreate($_request) {
        $_message = "Event type is PLAN CREATE but is not implemented";
        //$this->_logger->addInfo( $_message );
        header( "CONEKTA: Plan create. " . $_message );
        header( "HTTP/1.1 404 Not Found" );
        mail("emendoza@magen4me.com","Conekta Webhook notification", json_encode($_request, JSON_PRETTY_PRINT));
        die();
    }








    private function _processGet($_input, $_request) {
        try {
            header("HTTP/1.1 200 OK");
            //var_dump($_input);
            //echo "<hr/>";
            //var_dump($_request);
            //$this->_logger->addInfo( $_input   );
            //$this->_logger->addInfo( $_request );

            $_status       = "paid";
            $_reference_id = $_GET[ "id" ];
            $_amount       = 100;

            //echo "<pre>";
            if ($_status == 'paid') {
                $order = $this->_objectManager->create('Magento\Sales\Model\Order')->load(  $_reference_id  );

                $_payment_method_code = $order->getPayment()->getMethodInstance()->getCode();
                $_grand_total         = $order->getGrandTotal();

                /*
                /vendor/magento/module-sales/model/order.php

                const STATE_NEW             = 'new';
                const STATE_PENDING_PAYMENT = 'pending_payment';
                const STATE_PROCESSING      = 'processing';
                const STATE_COMPLETE        = 'complete';
                const STATE_CLOSED          = 'closed';
                const STATE_CANCELED        = 'canceled';
                const STATE_HOLDED          = 'holded';
                const STATE_PAYMENT_REVIEW  = 'payment_review';
                */

                /*
                $order->setData('state',  'canceled');
                $order->setData('status', 'canceled');
                //$order->setData('base_total_paid', intval(((float) $_amount) / 100));
                $order->save();
                */
                header("CONEKTA_EVENT: GET request test. Order " . $_GET[ "id" ] . " does exist");
                $this->_logger->addInfo("CONEKTA_EVENT: GET request test. Order " . $_GET[ "id" ] . " does exist");
                die();
            }
        } catch (Exception $e) {
            header('CONEKTA: DEBUG' );
            header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500);
            header("CONEKTA: DEBUG Exception: " .  $e->getMessage() );
            die();
        }
    }









    private function _processBadRequest($_request, $_message = null ) {
        if ( null == $_message ) {
            $_message = "Bad request: " . $_request;
        } else {
            $_message = $_message . ": " . $_request;
        }
        //$this->_logger->addInfo( $_message );
        header('CONEKTA: Bad request. ' . $_message );
        header("HTTP/1.1 404 Not Found");
        mail("emendoza@magen4me.com", "Conekta Webhook notification", json_encode($_request, JSON_PRETTY_PRINT));
        die();
    }



    private function _processConektaWebhookTest($_request) {
        try {
           
            $_id                         = $_request->id;
            $_livemode                   = $_request->livemode;
            $_type                       = $_request->type;
            
            $_object_id                  = $_request->data->object->id;
            $_object_amount              = $_request->data->object->amount;
            $_object_fee                 = $_request->data->object->fee;
            $_object_currency            = $_request->data->object->currency;
            $_object_status              = $_request->data->object->status;
            $_object_livemode            = $_request->data->object->livemode;
            $_object_description         = $_request->data->object->description;
            $_object_error               = $_request->data->object->error;
            $_object_error_message       = $_request->data->object->error_message;
            
            $_payment_method_object      = $_request->data->object->payment_method->object;
            $_payment_method_last4       = $_request->data->object->payment_method->last4;
            $_payment_method_name        = $_request->data->object->payment_method->name;
            $_payment_method_chargeback  = $_request->data->object->payment_method->chargeback;

            $_previous_attributes_status = $_request->data->previous_attributes->status;

            $_message =                                                                              "\n\n" .
                        "ID  ....................................  " . $_id                        . "\n"   .
                        "Livemode  ..............................  " . $_livemode                  . "\n"   .
                        "Type  ..................................  " . $_type                      . "\n\n" .
                        "Object ID  .............................  " . $_object_id                 . "\n"   .
                        "Object amount  .........................  " . $_object_amount             . "\n"   .
                        "Object fee  ............................  " . $_object_fee                . "\n"   .
                        "Object currency  .......................  " . $_object_currency           . "\n"   .
                        "Object status  .........................  " . $_object_status             . "\n"   .
                        "object livemode  .......................  " . $_object_livemode           . "\n"   .
                        "Object description  ....................  " . $_object_description        . "\n"   .
                        "Object error  ..........................  " . $_object_error              . "\n"   .
                        "Object error message  ..................  " . $_object_error_message      . "\n\n" .
                        "Payment object  ........................  " . $_payment_method_object     . "\n"   .
                        "Payment last4  .........................  " . $_payment_method_last4      . "\n"   .
                        "Payment name  ..........................  " . $_payment_method_name       . "\n"   .
                        "Payment chargeback  ....................  " . $_payment_method_chargeback . "\n"   .
                        "Previous attributes status  ............  " . $_previous_attributes_status;

            if ( $_id                         == "51cef9faf23668b1f4000001" &&
                 $_livemode                   == true                       &&
                 $_type                       == "charge.paid"              &&
                 $_object_id                  == "51d5ea80db49596aa9000001" &&
                 $_object_amount              == 10000                      &&
                 $_object_fee                 == 310                        &&
                 $_object_currency            == "MXN"                      &&
                 $_object_status              == "paid"                     &&
                 $_object_livemode            == "true"                     &&
                 $_object_description         == "E-Book: Les Miserables"   &&
                 $_object_error               == null                       &&
                 $_object_error_message       == null                       &&
                 $_payment_method_object      == "card_payment"             &&
                 $_payment_method_last4       == "1111"                     &&
                 $_payment_method_name        == "Arturo Octavio Ortiz"     &&
                 $_payment_method_chargeback  == null                       && 
                 $_previous_attributes_status == "payment_pending"
                ) {
                $_debug_mode = $this->scopeConfig->getValue('payment/conekta_card/debug_mode', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, 0);
                if ($_debug_mode) {
                    $this->_processCardChargePaid($_request);
                    header("CONEKTA_EVENT: Webhook Test OK. Response set to HTTP 404");
                    header("HTTP/1.1 404 Not Found");
                } else {
                    $this->_processCardChargePaid($_request);
                    header("CONEKTA_EVENT: Webhook Test OK. Response set to HTTP 200");
                    header("HTTP/1.1 200 OK");
                }
                mail("emendoza@magen4me.com", "Conekta Webhook notification", json_encode($_request, JSON_PRETTY_PRINT));
                die();
            } else {
                $this->_processCardChargePaid($_request);
            }
        } catch (Exception $e) {
            $this->_logger->addInfo( "Conekta Webhook Test. Exception: " . $e->getMessage() );
        } finally {
            $this->_logger->addInfo( "Conekta Webhook Test. " . json_encode($_request) );
        }
    }

































    private function _processWebhookUpdated($_request) {
        $_output       = "Event type is CHARGE CREATED\n\n";
        //$this->_objectManager->get('Psr\Log\LoggerInterface')->debug(  "Cargo con tarjeta de credito / debito"  );
        mail("emendoza@magen4me.com","Conekta Webhook notification", json_encode($_request, JSON_PRETTY_PRINT));
        header("HTTP/1.1 404 Not Found");
        header('CONEKTA: Webhook updated' );
        die();
    }















    private function _processOxxo($_request) {
        $_output .= "Event type is OXXO\n\n";
        //$this->_objectManager->get('Psr\Log\LoggerInterface')->debug(  $_output  );    die();
        $_reference_id  = $_request->reference_id;

        if ($_reference_id) {
            //echo $_reference_id; 
            $data    = $this->_objectManager->create('Magento\Sales\Model\Order\Payment')->getCollection()->getLastItem();
            $payment = $this->_objectManager->create('Magento\Sales\Model\Order\Payment')->load($data->getEntityId());
            $payment->setData('cc_trans_id', $_reference_id);
            $payment->setData('cc_owner', $_GET['name'])->save();
            $myfile = fopen("var/log/debug.log", "a+") or die("Unable to open file!");
            fwrite($myfile, print_r("record cc_trans_id added", true));
            fwrite($myfile, print_r($data->getEntityId() . "refernce_id" . $_reference_id, true));
            fclose($myfile);

            echo $data->getParentid();
            $_output       .= "Reference ID is " . $_reference_id . "\n\n";
        } else {

            $_input = @file_get_contents('php://input');
            var_dump($_input);
            $event = json_decode($_input);
            //var_dump($event);
            $myfile = fopen("var/log/debug.log", "a+") or die("Unable to open file!");

            /*  \Magento\Framework\App\ObjectManager::getInstance()
              ->get('Psr\Log\LoggerInterface')->debug($_input); */

            $charge = $event->data->object;
            //fwrite($myfile, print_r($charge,true));
            //fwrite($myfile, print_r($charge->status,true));

            if ($charge->status == 'paid') {
                $data = $this->_objectManager->create('Magento\Sales\Model\Order\Payment')->getCollection()->addFieldToFilter('cc_trans_id', $charge->id)->getFirstItem();
                //fwrite($myfile, print_r($data->getParentId(),true));
                //fwrite($myfile, print_r($charge->amount,true));

                $order = $this->_objectManager->create('Magento\Sales\Model\Order')->load($data->getParentId());
                //fwrite($myfile, print_r($order->getData(),true));
                $order->setData('state', 'complete');
                $order->setData('status', 'complete');
                $order->setData('base_total_paid', intval(((float) $charge->amount) / 100));
                $order->save();
                //fwrite($myfile, print_r($charge->amount,true));
                fclose($myfile);
                $_output       .= "CHARGE PAID\n\n";
            }

            // $lastid=$order->getId();
            //var_dump($lastid1->getData());
            //fwrite($myfile, print_r($lastid->getData(),true));

            die('listener action');
        }
    }




    /**
     * Indents a flat JSON string to make it more human-readable.
     *
     * @param string $json The original JSON string to process.
     *
     * @return string Indented version of the original JSON string.
     */
    private function __formatJson($json) {
        $result      = '';
        $pos         = 0;
        $strLen      = strlen($json);
        $indentStr   = '  ';
        $newLine     = "\n";
        $prevChar    = '';
        $outOfQuotes = true;

        for ($i=0; $i<=$strLen; $i++) {

            // Grab the next character in the string.
            $char = substr($json, $i, 1);

            // Are we inside a quoted string?
            if ($char == '"' && $prevChar != '\\') {
                $outOfQuotes = !$outOfQuotes;

            // If this character is the end of an element,
            // output a new line and indent the next line.
            } else if(($char == '}' || $char == ']') && $outOfQuotes) {
                $result .= $newLine;
                $pos --;
                for ($j=0; $j<$pos; $j++) {
                    $result .= $indentStr;
                }
            }

            // Add the character to the result string.
            $result .= $char;

            // If the last character was the beginning of an element,
            // output a new line and indent the next line.
            if (($char == ',' || $char == '{' || $char == '[') && $outOfQuotes) {
                $result .= $newLine;
                if ($char == '{' || $char == '[') {
                    $pos ++;
                }

                for ($j = 0; $j < $pos; $j++) {
                    $result .= $indentStr;
                }
            }

            $prevChar = $char;
        }

        return $result;
    }








    private function _log($_message, $_level) {
        $this->_logger->addInfo( $_message );
    }





















}
