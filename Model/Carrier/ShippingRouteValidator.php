<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace GlsGroup\Shipping\Model\Carrier;

class ShippingRouteValidator
{
    /**
     * @var \string[][]
     */
    private $routes = [
        'DE' => [
            'AD', 'AL', 'AT', 'BA', 'BE', 'BG', 'CH', 'CY', 'CZ', 'DE', 'DK', 'EE', 'ES', 'FI', 'FO', 'FR', 'GB', 'GI',
            'GR', 'HR', 'HU', 'IE', 'IS', 'IT', 'LI', 'LT', 'LU', 'LV', 'MC', 'ME', 'MK', 'MT', 'NL', 'NO', 'PL', 'PT',
            'RO', 'RS', 'SE', 'SI', 'SK', 'SM', 'TR', 'VA', 'XK'
        ]
    ];

    /**
     * Determine if shipping route is supported by the GLS carrier.
     *
     * @param string $originCountry
     * @param string $destinationCountry
     *
     * @return bool
     */
    public function isValid(string $originCountry, string $destinationCountry): bool
    {
        return isset($this->routes[$originCountry])
            && \in_array($destinationCountry, $this->routes[$originCountry], true);
    }
}
