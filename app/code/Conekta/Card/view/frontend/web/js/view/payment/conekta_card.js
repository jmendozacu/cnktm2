define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        Component,
        rendererList
    ) {
        'use strict';
        rendererList.push(
            {
                type: 'conekta_card',
                component: 'Conekta_Card/js/view/payment/method-renderer/conekta_card-method'
            }
        );
        /** Add view logic here if needed */
        return Component.extend({});
    },
    function (Component, additionalValidators, conekta_card_validator) {
        'use strict';
        additionalValidators.registerValidator(conekta_card_validator);
        return Component.extend({});
    }
);