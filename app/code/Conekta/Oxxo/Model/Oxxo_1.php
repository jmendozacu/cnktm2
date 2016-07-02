<?php

namespace Conekta\Oxxo\Model;

/**
 * OXXO cash payment through Conekta
 *
 * @method \Magento\Quote\Api\Data\PaymentMethodExtensionInterface getExtensionAttributes()
 */
class Oxxo extends \Magento\Payment\Model\Method\AbstractMethod {

    const PAYMENT_METHOD_OXXO = 'conekta_oxxo';

    /**
     * Payment method code
     *
     * @var string
     */
    
    protected $_code = self::PAYMENT_METHOD_OXXO;

    /**
     * Bank Transfer payment block paths
     *
     * @var string
     */
    protected $_formBlockType = 'Conekta\Oxxo\Block\Form';

    /**
     * Instructions block path
     *
     * @var string
     */
    //protected $_infoBlockType = 'Magento\Payment\Block\Info\Instructions';

    /**
     * Availability option
     *
     * @var bool
     */
    protected $_isOffline = true;

    /**
     * Get instructions text from config
     *
     * @return string
     */
    /*public function getInstructions()
    {
        return trim($this->getConfigData('instructions'));
    }
    */





}
