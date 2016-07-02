<?php

namespace Conekta\Spei\Model;

/**
 * Pay In Store payment method model
 */
class ConektaSpei extends \Magento\Payment\Model\Method\AbstractMethod {

    /**
     * Payment code
     *
     * @var string
     */
    protected $_code = 'conekta_spei';

    /**
     * Availability option
     *
     * @var bool
     */
    protected $_isOffline = true;

}
