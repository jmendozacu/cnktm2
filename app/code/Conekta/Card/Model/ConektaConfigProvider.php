<?php

namespace Conekta\Card\Model;

use \Magento\Customer\Helper\Session\CurrentCustomer;
use \Magento\Framework\App\Config\ScopeConfigInterface as ScopeConfig;
use \Magento\Framework\Locale\ResolverInterface;
use \Magento\Framework\UrlInterface;
use \Magento\Payment\Helper\Data as PaymentHelper;

class ConektaConfigProvider implements \Magento\Checkout\Model\ConfigProviderInterface {

    /**
     * @var ResolverInterface
     */
    protected $localeResolver;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Customer\Helper\Session\CurrentCustomer
     */
    protected $currentCustomer;

    /**
     * @var \Magento\Payment\Model\Method\AbstractMethod[]
     */
    protected $methods = [];

    /**
     * @var PaymentHelper
     */
    protected $paymentHelper;

    /**
     * @var UrlInterface
     */
    protected $urlBuilder;
    protected $formLaybuy;
    protected $helperForm;

    const CODE = 'laybuy';

    /**
     * @param ConfigFactory $configFactory
     * @param ResolverInterface $localeResolver
     * @param CurrentCustomer $currentCustomer
     * @param PaypalHelper $paypalHelper
     * @param PaymentHelper $paymentHelper
     */
    public function __construct(
    ResolverInterface $localeResolver, PaymentHelper $paymentHelper, ScopeConfig $scopeConfig, UrlInterface $urlBuilder, CurrentCustomer $currentCustomer
    ) {
        $this->localeResolver = $localeResolver;
        $this->currentCustomer = $currentCustomer;
        $this->paymentHelper = $paymentHelper;
        $this->urlBuilder = $urlBuilder;
        $this->scopeConfig = $scopeConfig;
    }

    public function getConfig() {
        $pvt_key = $this->scopeConfig->getValue('payment/conekta_card/productionprivatekey', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, 0);
        $pub_key = $this->scopeConfig->getValue('payment/conekta_card/productionpublickey', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, 0);
        $sandboxpvt = $this->scopeConfig->getValue('payment/conekta_card/sandboxprivatekey', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, 0);
        $sandboxpub = $this->scopeConfig->getValue('payment/conekta_card/sandboxpublickey', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, 0);

        $config = [
            'conekta' => [
                'prodprivatekey' => $pvt_key,
                'prodpublickey' => $pub_key,
                'sandboxprivate' => $sandboxpvt,
                'sandboxpublic' => $sandboxpub
            ]
        ];

        return $config;
    }

}
