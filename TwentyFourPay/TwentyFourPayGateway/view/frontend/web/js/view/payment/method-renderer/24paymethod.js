define(
    [
        'jquery',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/model/url-builder',
        'mage/storage',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Checkout/js/action/place-order',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Magento_Checkout/js/model/quote',
        'Magento_Customer/js/model/customer'
    ],
    function(
        $,
        Component,
        urlBuilder,
        storage,
        fullScreenLoader,
        placeOrderAction,
        additionalValidators,
        quote,
        customer
    ) {
        'use strict';
        var tfConfig = window.checkoutConfig.payment.t24pay;

        console.log('24Pay initialized');

        return Component.extend({
            defaults: {
                template: 'TwentyFourPay_TwentyFourPayGateway/payment/24pay'
            },

            redirectAfterPlaceOrder: false,

            placeOrder: function(data, event) {
                var self = this;

                if (event) {
                    event.preventDefault();
                }

                console.log('Placing order ...');
                //self.redirectToGateway({});
                //return true;

                if (this.validate() && additionalValidators.validate()) {
                    this.isPlaceOrderActionAllowed(false);

                    this.getPlaceOrderDeferredObject()
                        .fail(
                            function() {
                                console.log('Error placing order!');
                                self.isPlaceOrderActionAllowed(true);
                            }
                        ).done(
                            function(response) {
                                console.log('Order placed: ' + response);
                                var data = JSON.parse(response);
                                self.redirectToGateway(data);
                            }
                        );

                    return true;
                }

                return false;
            },

            /**
             * Redirects the user to 24pay payment page
             */
            redirectToGateway: function(data) {
                console.log('Redirecting: ' + JSON.stringify(data));

                $.get(tfConfig.form_url, {
                    'order_id': data
                }).success(function(response) {

                  var div = document.createElement("div");
                  div.innerHTML = response;
                  document.body.appendChild(div);

                  var form = document.getElementById('t24payForm');
                  form.submit();
                });
            }
        });
    }
);