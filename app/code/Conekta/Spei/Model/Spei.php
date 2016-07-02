<?php

namespace Conekta\Spei\Model;

/**
 * Pay In Store payment method model
 */
class Spei extends \Magento\Payment\Model\Method\AbstractMethod {

    /**
     * Payment code
     *
     * @var string
     */
    protected $_code = 'spei';

    /**
     * Availability option
     *
     * @var bool
     */
    protected $_isOffline = true;

}
