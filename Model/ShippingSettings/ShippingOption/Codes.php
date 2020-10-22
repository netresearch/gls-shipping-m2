<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace GlsGermany\Shipping\Model\ShippingSettings\ShippingOption;

class Codes
{
    // packaging services
    public const PACKAGING_SERVICE_LETTERBOX = 'letterBox';
    public const PACKAGING_SERVICE_SHOP_RETURN = 'shopReturn';

    // packaging inputs
    public const PACKAGING_INPUT_TERMS_OF_TRADE = 'termsOfTrade';

    // checkout services
    public const CHECKOUT_SERVICE_FLEX_DELIVERY = 'flexDelivery';
    public const CHECKOUT_SERVICE_DEPOSIT = 'deposit';
    public const CHECKOUT_SERVICE_GUARANTEED24 = 'guaranteed24';
}
