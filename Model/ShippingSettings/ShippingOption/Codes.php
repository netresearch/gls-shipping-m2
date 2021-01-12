<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace GlsGroup\Shipping\Model\ShippingSettings\ShippingOption;

class Codes
{
    // package customs
    public const PACKAGING_INPUT_TERMS_OF_TRADE = 'termsOfTrade';

    // packaging services
    public const SERVICE_OPTION_LETTERBOX = 'letterBox';
    public const SERVICE_OPTION_SHOP_RETURN = 'shopReturn';

    // checkout services
    public const SERVICE_OPTION_FLEX_DELIVERY = 'flexDelivery';
    public const SERVICE_OPTION_DEPOSIT = 'deposit';
    public const SERVICE_OPTION_GUARANTEED24 = 'guaranteed24';
}
