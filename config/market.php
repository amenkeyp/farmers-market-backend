<?php

return [
    /*
     | Default interest rate applied to credit transactions when not overridden.
     | 0.05 means 5% interest. credit_amount = subtotal * (1 + interest_rate)
     */
    'default_interest_rate' => env('MARKET_DEFAULT_INTEREST_RATE', 0.05),

    /*
     | Default commodity rate (FCFA per kg) when no per-product or DB setting is found.
     */
    'default_rate_per_kg' => env('MARKET_DEFAULT_RATE_PER_KG', 0),

    /*
     | Currency
     */
    'currency' => env('MARKET_CURRENCY', 'XOF'),

    /*
     | Minimum monetary unit (FCFA has no decimals in practice but we keep precision internally).
     */
    'rounding_scale' => 2,
];
