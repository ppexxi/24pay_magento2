define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function(
        Component,
        rendererList
    ) {
        'use strict';
        rendererList.push({
            type: 't24pay',
            component: 'TwentyFourPay_TwentyFourPayGateway/js/view/payment/method-renderer/24paymethod'
        });
        /** Add view logic here if needed */
        return Component.extend({});
    }
);