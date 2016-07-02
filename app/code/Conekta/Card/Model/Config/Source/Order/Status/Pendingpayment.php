<?php

namespace Conekta\Card\Model\Config\Source\Order\Status;

use Magento\Sales\Model\Order;
use Magento\Sales\Model\Config\Source\Order\Status;
use Magento\Framework\Option\ArrayInterface;

/**
 * Order Status source model
 */
class Pendingpayment implements ArrayInterface {

    /**
     * {@inheritdoc}
     */
    const Pending    = 'Pending';
    const Processing = 'Processing';
    const Complete   = 'Complete';

    public function toOptionArray() {
        return [
            [
                'value' => $this::Pending,
                'label' => __('Pending'),
            ],
            [
                'value' => $this::Processing,
                'label' => __('Processing'),
            ],
            [
                'value' => $this::Complete,
                'label' => __('Complete')
            ]
        ];
    }

}
