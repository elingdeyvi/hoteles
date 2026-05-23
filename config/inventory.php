<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Stock bajo (comando inventory:alert-low-stock)
    |--------------------------------------------------------------------------
    |
    | Si un producto no tiene min_stock en BD, se compara su stock contra este
    | umbral (también sobrescribible con --threshold).
    |
    */
    'low_stock' => [
        'default_threshold' => env('INVENTORY_LOW_STOCK_DEFAULT', '10'),
    ],

];
