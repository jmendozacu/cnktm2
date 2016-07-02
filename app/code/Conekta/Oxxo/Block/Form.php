<?php

namespace Conekta\Oxxo\Block;

class Form extends \Magento\Checkout\Block\Onepage\Success {

    protected $_template = 'Conekta::Oxxo/succcess.phtml';

    public function test() {
        die('block function called');
    }

}
