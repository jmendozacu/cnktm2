<?php

namespace Conekta\Card\Block;

class Form extends \Magento\Checkout\Block\Onepage\Success {

    protected $_template = 'Conekta::Card/succcess.phtml';

    public function test() {
        die('block function called');
    }

}
