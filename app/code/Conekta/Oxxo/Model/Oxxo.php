<?php

namespace Conekta\Oxxo\Model;

/**
 * Pay In Store payment method model
 */
class Oxxo extends \Magento\Payment\Model\Method\AbstractMethod {

    /**
     * Payment code
     *
     * @var string
     */
    protected $_code = 'oxxo';

    /**
     * Availability option
     *
     * @var bool
     */
    protected $_isOffline = true;

}
