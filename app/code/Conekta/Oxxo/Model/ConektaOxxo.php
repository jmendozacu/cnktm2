<?php

namespace Conekta\Oxxo\Model;

/**
 * Pay In Store payment method model
 */
class ConektaOxxo extends \Magento\Payment\Model\Method\AbstractMethod {

    /**
     * Payment code
     *
     * @var string
     */
    protected $_code = 'conekta_oxxo';

    /**
     * Availability option
     *
     * @var bool
     */
    protected $_isOffline = true;

}
