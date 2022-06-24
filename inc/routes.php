<?php

add_action('rest_api_init', 'register_api_hooks');

function register_api_hooks()
{
    register_rest_route(
        'api',
        '/get_home_sliders/',
        array(
            'methods' => 'GET',
            'callback' => 'getSlidersController',
            'permission_callback' => function ($request) {
                return true;
            },
        )
    );

    register_rest_route(
        'api',
        '/get_reviews/',
        array(
            'methods' => 'GET',
            'callback' => 'getReviewsController',
            'permission_callback' => function ($request) {
                return true;
            },
        )
    );

    register_rest_route(
        'api',
        '/get_all_categories/',
        array(
            'methods' => 'GET',
            'callback' => 'getAllCategories',
            'permission_callback' => function ($request) {
                return true;
            },
        )
    );

    register_rest_route(
        'api',
        '/get_products_by_category/',
        array(
            'methods' => 'GET',
            'callback' => 'getProductsByCategory',
            'permission_callback' => function ($request) {
                return true;
            },
        )
    );

    register_rest_route(
        'api',
        '/get_best_selling_product/',
        array(
            'methods' => 'GET',
            'callback' => 'getBestSellingProduct',
            'permission_callback' => function ($request) {
                return true;
            },
        )
    );

    register_rest_route(
        'api',
        '/product/(?P<slug>[a-zA-Z0-9-]+)/',
        array(
            'methods' => 'GET',
            'callback' => 'getProductsDetail',
            'permission_callback' => function ($request) {
                return true;
            },
        )
    );

    register_rest_route(
        'api',
        '/create_order/',
        array(
            'methods' => 'POST',
            'callback' => 'createOrder',
            'permission_callback' => function ($request) {
                return true;
            },
        )
    );

    register_rest_route(
        'api',
        '/product/query/',
        array(
            'methods' => 'GET',
            'callback' => 'getProductsByKeyword',
            'permission_callback' => function ($request) {
                return true;
            },
        )
    );

    register_rest_route(
        'api',
        '/get-attributes',
        array(
            'methods' => 'GET',
            'callback' => 'getAllAttributes',
            'permission_callback' => function ($request) {
                return true;
            },
        )
    );
}
