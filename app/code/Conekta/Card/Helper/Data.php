<?php

namespace Conekta\Card\Helper;

use Magento\Framework\DataObject;

class Data extends \Magento\Payment\Helper\Data {

    protected $_storeManager;
    protected $_objectManager;

    public function __construct(
    \Magento\Store\Model\StoreManagerInterface $storeManager, \Magento\Framework\ObjectManagerInterface $objectInterface
    ) {
        $this->_storeManager = $storeManager;
        $this->_objectManager = $objectInterface;
    }


}
