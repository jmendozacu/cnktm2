<?php

namespace Conekta\Card\Model;

use Magento\Framework\DataObject;

class ConektaCard extends \Magento\Payment\Model\Method\AbstractMethod {

    const METHOD_CODE = 'conekta_card';

    /**
     * Payment code
     *
     * @var string
     */
    protected $_code = 'conekta_card';

    /**
     * @var string
     */
    protected $_formBlockType = 'Conekta\Card\Block\Form';

    /**
     * Availability option
     *
     * @var bool
     */
    protected $_isGateway = false;

    /**
     * Availability option
     *
     * @var bool
     */
    protected $_canOrder = true;

    /**
     * Availability option
     *
     * @var bool
     */
    protected $logger;
    protected $_canAuthorize = true;

    /**
     * Availability option
     *
     * @var bool
     */
    protected $_canCapture = true;
    protected $_storeManager;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $_urlBuilder;

    /**
     * @var \Magento\Framework\Exception\LocalizedExceptionFactory
     */
    protected $_exception;

    /**
     * @var \Magento\Payment\Helper\Data
     */
    protected $helper;
    protected $block;
    private $_gatewayURL = 'https://secure.nmi.com/api/transact.php';

    const REQUEST_TYPE_AUTH_CAPTURE = 'AUTH_CAPTURE';
    const REQUEST_TYPE_AUTH_ONLY = 'AUTH_ONLY';
    const REQUEST_TYPE_CAPTURE_ONLY = 'CAPTURE_ONLY';

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory
     * @param \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory
     * @param \Magento\Payment\Helper\Data $paymentData
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Payment\Model\Method\Logger $logger
     * @param ProFactory $proFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\UrlInterface $urlBuilder
     * @param array $data
     */
    public function __construct(
    \Magento\Framework\Model\Context $context, \Magento\Framework\Registry $registry, \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory, \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory, \Magento\Payment\Helper\Data $paymentData, \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig, \Magento\Payment\Model\Method\Logger $logger, \Magento\Store\Model\StoreManagerInterface $storeManager, \Magento\Framework\UrlInterface $urlBuilder, \Magento\Framework\ObjectManagerInterface $objectInterface, \Magento\Framework\Exception\LocalizedExceptionFactory $exception, \Psr\Log\LoggerInterface $logger1, \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null, \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null, array $data = []
    ) {
        parent::__construct(
                $context, $registry, $extensionFactory, $customAttributeFactory, $paymentData, $scopeConfig, $logger, null, null, $data
        );
        $this->logger = $logger;
        $this->_storeManager = $storeManager;
        $this->_urlBuilder = $urlBuilder;
        $this->_exception = $exception;
        $this->_scopeConfig = $scopeConfig;
        $this->_objectManager = $objectInterface;
        //\Conekta::setApiKey('key_ecg3u8jcsE8N2xxqrMU9cQ');
        //\Conekta::setApiVersion("1.0.0");
        //\Conekta::setLocale('es');
        //$this->block=$block;
    }

    /**
     * Check whether payment method can be used
     * @param \Magento\Quote\Api\Data\CartInterface|Quote|null $quote
     * @return bool
     */
    public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null) {
        return parent::isAvailable($quote);
    }

    /**
     * Authorize payment
     *
     * @param \Magento\Framework\DataObject|\Magento\Payment\Model\InfoInterface|Payment $payment
     * @param float $amount
     * @return $this
     */
    public function authorize(\Magento\Payment\Model\InfoInterface $payment, $amount) {
        //$this->block->test();

        if ($amount <= 0) {
            throw new \Magento\Framework\Validator\Exception(__('Invalid amount for authorization.'));
        }
        $payment->setAmount($amount);
        $request = $this->_createRequest($payment);
        $send_request = $this->sendRequest($request);

        if ($send_request['response'] == 1) {
            $payment->setTransactionId($send_request['transactionid'])
                    ->setCcApproval($send_request['authcode'])
                    ->setCcTransId($send_request['transactionid'])
                    ->setIsTransactionClosed(0)
                    ->setParentTransactionId(null)
                    ->setCcAvsStatus($send_request['avsresponse'])
                    ->setCcCidStatus($send_request['cvvresponse']);
            return $this;
        } else {
            //$this->helper->parseError($send_request['responsetext']);
            //throw new \Magento\Framework\Validator\Exception('Transaction Declined: ' . $send_request['responsetext']);
        }
        return $this;
    }

    /**
     * Capture payment
     *
     * @param \Magento\Framework\DataObject|\Magento\Payment\Model\InfoInterface|Payment $payment
     * @param float $amount
     * @return $this
     */
    public function capture(\Magento\Payment\Model\InfoInterface $payment, $amount) {
        if ($amount <= 0) {
            throw new \Magento\Framework\Validator\Exception(__('Invalid capture Amount.'));
        }
        $transactionid = $payment->getTransactionId();
        if (!$transactionid) {
            $this->authorizeCapture($payment, $amount);
            $transactionid = $payment->getTransactionId();
        }

        $checkMode = $this->getConfigData('test_mode');
        $username = null;
        $password = null;
        if ($checkMode) {
            $username = 'demo';
            $password = 'password';
        } else {
            $username = $this->getConfigData('username');
            $password = $this->getConfigData('password');
        }
        $data = "";

        $data .= "username=" . urlencode($username) . "&";
        $data .= "password=" . urlencode($password) . "&";

        $data .= "transactionid=" . urlencode($transactionid) . "&";
        if ($amount > 0) {
            $data .= "amount=" . urlencode(number_format($amount, 2, ".", "")) . "&";
        }
        $data .= "type=capture";

        $result = $this->PostInfo($data);

        if (isset($result['response']) && ($result['response'] == 1)) {
            $payment->setStatus(self::STATUS_APPROVED);
            $payment->setLastTransId($result['transactionid']);
            if (!$payment->getParentTransactionId() || $result['transactionid'] != $payment->getParentTransactionId()) {
                $payment->setTransactionId($result['transactionid']);
            }
            return $this;
        } else {
            // $this->helper->parseError($result['transactionid']);
            throw new \Magento\Framework\Validator\Exception('Transaction Declined: ' . $result['transactionid']);
        }
    }

    /**
     * Assign data to info model instance
     *
     * @param array|\Magento\Framework\DataObject $data
     * @return \Magento\Payment\Model\Info
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function assignData(DataObject $data) {
        //die('yes call assign data');
        /* var_dump($data);
          die; */
        // $name=$this->helper->getCustomerName();

        $params = array(
            "card" => array(
                /* "name"      =>$name, */
                "number" => $data->getData('cc_number'),
                "cvc" => $data->getData('cc_cid'),
                "exp_month" => $data->getData('cc_exp_month'),
                "exp_year" => $data->getData('cc_exp_year')
            )
        );

        //$token = $this->createToken($params);


        if (!($data instanceof DataObject)) {
            $data = new DataObject($data);
        }
        $Cardinfo = $this->getInfoInstance();
        $Cardinfo->setCcType($data->getCcType())
                // ->setCcOwner($name)
                ->setCcLast4(substr($data->getCcNumber(), -4))
                ->setCcNumberEnc($data->getCcNumber())
                ->setCcCid($data->getCcCid())
                ->setCcExpMonth($data->getCcExpMonth())
                ->setCcExpYear($data->getCcExpYear());
        return $this;
    }

    protected function createToken($params) {


        //$token = \Conekta_Token::create($params);
        //print_r($token);
    }

    /**
     * Send capture request to gateway for capture authorized transactions
     *
     * @param decimal $amount
     */
    protected function authorizeCapture($payment, $requestedAmount) {
        $payment->setAmount($requestedAmount);
        $request = $this->_createRequest($payment);
        $send_request = $this->sendRequest($request);
        if ($send_request['response'] == 1) {
            $payment->setTransactionId($send_request['transactionid'])
                    ->setCcApproval($send_request['authcode'])
                    ->setCcTransId($send_request['transactionid'])
                    ->setIsTransactionClosed(0)
                    ->setCcAvsStatus($send_request['avsresponse'])
                    ->setCcCidStatus($send_request['cvvresponse']);
            return $this;
        } else {

            $this->helper->parseError($send_request['responsetext']);
            throw new \Magento\Framework\Validator\Exception('Transaction Declined: ' . $send_request['responsetext']);
        }
        return $this;
    }

    public function getErrorUrl($responsetext) {
        echo "Your Order Has been cancelled of " . $responsetext;
        return $this->_urlBuilder->getUrl('checkout/cart/index');
    }

    protected function _getRequest() {
        return $this->_objectManager->create('Conekta\NetMerchantInc\Model\Direct\Request');
    }

    protected function _createRequest(DataObject $payment) {
        $order = $payment->getOrder();
        $request = $this->_getRequest();

        /*         * card Credentials storage */
        $ccNumber = '';
        $expDate = '';
        $ccCid = '';

        $checkMode = $this->getConfigData('test_mode');

        if ($checkMode) {
            $request->setLoginUserName('demo')
                    ->setLoginPassword('password');
        } else {
            $username = $this->getConfigData('username');
            $password = $this->getConfigData('password');
            $request->setLoginUserName($username)
                    ->setLoginPassword($password);
        }
        /*         * Retrieve billing info */
        $billing = $order->getBillingAddress();

        if (!empty($billing)) {
            $request->setBillingFirstname(strval($billing->getFirstname()))
                    ->setBillingLastname(strval($billing->getLastname()))
                    ->setBillingCompany(strval($billing->getCompany()))
                    ->setBillingCity(strval($billing->getCity()))
                    ->setBillingState(strval($billing->getRegion()))
                    ->setBillingZip(strval($billing->getPostcode()))
                    ->setBillingCountry(strval($billing->getCountry()))
                    ->setBillingPhone(strval($billing->getTelephone()));
        }
        /*         * Retrieve Shipping info */
        $shipping = $order->getShippingAddress();
        if (empty($shipping)) {
            $shipping = $billing;
        }

        if (!empty($shipping)) {
            $request->setShippingFirstname(strval($shipping->getFirstname()))
                    ->setShippingLastname(strval($shipping->getLastname()))
                    ->setShippingCompany(strval($shipping->getCompany()))
                    ->setShippingCity(strval($shipping->getCity()))
                    ->setShippingState(strval($shipping->getRegion()))
                    ->setShippingZip(strval($shipping->getPostcode()))
                    ->setShippingCountry(strval($shipping->getCountry()));
        }
        $request->setOrderid($order->getIncrementId());

        if ($payment->getCcNumber()) {
            $ccNumber = $payment->getCcNumber();
            $yr = substr($payment->getCcExpYear(), -2);
            $expDate = sprintf('%02d%02d', $payment->getCcExpMonth(), $yr);
            $ccCid = $payment->getCcCid();
        } else {
            throw new \Magento\Framework\Validator\Exception($this->helper->parseError('Credit Card Number is incorrect'));
            //throw new \Magento\Framework\Validator\Exception('Credit Card Number is incorrect');
        }
        $amount = $payment->getAmount();
        $request->setCreditCardNumber($ccNumber)
                ->setCcExp($expDate)
                ->setCvv($ccCid);

        if ($payment->getAmount()) {
            $request->setAmount($amount);
        }
        return $request;
    }

    public function sendRequest($request) {
        $data = "";
        /*         * Merchant Credentials */
        $data .= "username=" . urlencode($request->getLoginUserName()) . "&";
        $data .= "password=" . urlencode($request->getLoginPassword()) . "&";

        /*         * Credit Card Credentials */
        $data .= "ccnumber=" . urlencode($request->getCreditCardNumber()) . "&";
        $data .= "ccexp=" . urlencode($request->getCcExp()) . "&";
        $data .= "amount=" . urlencode(number_format($request->getAmount(), 2, ".", "")) . "&";
        $data .= "cvv=" . urlencode($request->getCvv()) . "&";

        /*         * Order Info */
        $data = $this->orderInfo($request, $data);

        /*         * Billing Info */
        $data = $this->billingInfo($request, $data);

        /*         * Shipping Info */
        $data = $this->shippingInfo($request, $data);

        return $this->PostInfo($data);
    }

    /**
      Step 2:
      transaction details should be delivered to the Payment Gateway using
      the POST method
     */
    public function PostInfo($data) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->_gatewayURL);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_POST, 1);

        if (!($data = curl_exec($ch))) {
            throw new \Magento\Framework\Validator\Exception('Transaction Declined: error transaction');
            return ERROR;
        }
        curl_close($ch);
        unset($ch);
        $responses = array();
        $data = explode("&", $data);
        for ($i = 0; $i < count($data); $i++) {
            $rdata = explode("=", $data[$i]);
            $responses[$rdata[0]] = $rdata[1];
        }

        /*         * [response] => 1
          [responsetext] => SUCCESS
          [authcode] => 123456
          [transactionid] => 3008705405
          [avsresponse] => N
          [cvvresponse] => N
          [orderid] => 000000111
          [type] => auth
          [response_code] => 100
          print_r($responses);die; */

        /**
          Step 3:
          the transaction responses are re
          turned in the body of the HTTP
          response in a query string
         */
        return $responses;
    }

    public function billingInfo($request, $data) {
        $data .= "firstname=" . urlencode($request->getBillingFirstname()) . "&";
        $data .= "lastname=" . urlencode($request->getBillingLastname()) . "&";
        $data .= "company=" . urlencode($request->getBillingCompany()) . "&";
        $data .= "city=" . urlencode($request->getBillingCity()) . "&";
        $data .= "state=" . urlencode($request->getBillingState()) . "&";
        $data .= "zip=" . urlencode($request->getBillingZip()) . "&";
        $data .= "country=" . urlencode($request->getBillingCountry()) . "&";
        $data .= "phone=" . urlencode($request->getBillingPhone()) . "&";
        $data .= "email=" . urlencode($request->getBillingEmail()) . "&";
        return $data;
    }

    public function orderInfo($request, $data) {
        $data .= "ipaddress=" . urlencode($request->getIpaddress()) . "&";
        $data .= "orderid=" . urlencode($request->getOrderid()) . "&";
        $data .= "tax=" . urlencode(number_format($request->getTax(), 2, ".", "")) . "&";
        $data .= "shipping=" . urlencode(number_format($request->getShipping(), 2, ".", "")) . "&";
        $data .= "ponumber=" . urlencode($request->getPonumber()) . "&";
        return $data;
    }

    public function shippingInfo($request, $data) {
        $data .= "shipping_firstname=" . urlencode($request->getShippingFirstname()) . "&";
        $data .= "shipping_lastname=" . urlencode($request->getShippingLastname()) . "&";
        $data .= "shipping_company=" . urlencode($request->getShippingCompany()) . "&";
        $data .= "shipping_address1=" . urlencode($request->getShippingAddress1()) . "&";
        $data .= "shipping_address2=" . urlencode($request->getShippingAddress2()) . "&";
        $data .= "shipping_city=" . urlencode($request->getShippingCity()) . "&";
        $data .= "shipping_zip=" . urlencode($request->getShippingZip()) . "&";
        $data .= "shipping_country=" . urlencode($request->getShippingCountry()) . "&";
        $data .= "type=auth";
        return $data;
    }

}
