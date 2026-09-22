<?php

return [

    'hosts' => [
        env('ELASTICSEARCH_HOST', '127.0.0.1:9200'),
    ],

    'products_index' => env('PRODUCTS_SEARCH_INDEX', 'products'),

];
