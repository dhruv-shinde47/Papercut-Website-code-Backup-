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
                type: 'maktapp',
                component: 'Gateway_Maktapp/js/view/payment/method-renderer/gateway-maktapp'
            }
        );
        return Component.extend({});
    }
 );