/**
 * See LICENSE.md for license details.
 */
/*browser:true*/
/*global define*/
define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/shipping-rates-validator',
        'Magento_Checkout/js/model/shipping-rates-validation-rules',
        'GlsGermany_Shipping/js/model/shipping-rates-validator',
        'GlsGermany_Shipping/js/model/shipping-rates-validation-rules'
    ],
    function (
        Component,
        defaultShippingRatesValidator,
        defaultShippingRatesValidationRules,
        shippingRatesValidator,
        shippingRatesValidationRules
    ) {
        'use strict';

        defaultShippingRatesValidator.registerValidator('glsgermany', shippingRatesValidator);
        defaultShippingRatesValidationRules.registerRules('glsgermany', shippingRatesValidationRules);

        return Component;
    }
);
