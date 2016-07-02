<?php

namespace Conekta\Spei\Block;

class Form extends \Magento\Checkout\Block\Onepage\Success {

    protected $_template = 'Conekta::Spei/succcess.phtml';

    public function test() {
        die('Block function called');
    }

}
