<?php

namespace Conekta\Oxxo\Model;

//use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\UrlInterface;
use Magento\Customer\Helper\Session\CurrentCustomer;
use Magento\Payment\Helper\Data as PaymentHelper;
//use Conekta\Laybuy\Block\Form\Laybuy;
//use Conekta\Laybuy\Helper\Checkoutform;
use \Magento\Framework\App\Config\ScopeConfigInterface as ScopeConfig;

class ConfigProvider implements \Magento\Checkout\Model\ConfigProviderInterface {

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
    //Checkoutform $helperForm,
    ResolverInterface $localeResolver, PaymentHelper $paymentHelper, ScopeConfig $scopeConfig, UrlInterface $urlBuilder, CurrentCustomer $currentCustomer
    ) {

        $this->localeResolver = $localeResolver;
        $this->currentCustomer = $currentCustomer;
        $this->paymentHelper = $paymentHelper;
        $this->urlBuilder = $urlBuilder;
        //$this->helperForm=$helperForm;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Returns applicable stored cards
     *
     * @return array
     */
    public function getStoredCards() {
        return $this->vault->currentCustomerStoredCards();
    }

    /**
     * Retrieve available credit card types
     *
     * @return array
     */
    protected function getCcAvailableCcTypes() {
        return $this->dataHelper->getCcAvailableCardTypes();
    }

    /**
     * If card can be saved for further use
     *
     * @return boolean
     */
    public function canSaveCard() {
        if ($this->config->useVault() && $this->customerSession->isLoggedIn()) {
            return true;
        }
        return false;
    }

    /**
     * If 3dsecure is enabled
     *
     * @return boolean
     */
    public function show3dSecure() {
        if ($this->config->is3dSecureEnabled()) {
            return true;
        }
        return false;
    }

    /**
     * Get generate nonce URL
     *
     * @return string
     */
    public function getAjaxGenerateNonceUrl() {
        return $this->urlBuilder->getUrl('braintree/creditcard/generate', ['_secure' => true]);
    }

    /**
     * @return array|void
     */
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
