<?php
function generateRandomString($length = 10)
{
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}

function getSlidersController()
{
    $api_status = true;
    $api_message = 'Home Sliders Fetched Successfully';

    $args = array(
        'post_type' => 'home_banners',
    );
    $data = get_posts($args);

    $sliders = [];

    if (is_array($data) || is_object($data)) {
        foreach ($data as $item) {
            $data = [];

            $data['post_id'] = $item->ID;
            $data['slider_title'] = $item->post_title;
            $data['slider_description'] = $item->post_content;

            $post_image = get_the_post_thumbnail_url($item->ID);
            $data['image'] = $post_image;

            $sliders[] = $data;
        }
    }

    $response['message'] = $api_message;
    $response['status'] = $api_status;

    $response['data'] = $sliders;

    return new WP_REST_Response($response);
}

function getReviewsController()
{
    $api_status = true;
    $api_message = 'Reviews Fetched Successfully';

    $args = array(
        'post_type' => 'reviews',
    );
    $data = get_posts($args);

    $reviews = [];

    if (is_array($data) || is_object($data)) {
        foreach ($data as $item) {
            $data = [];

            $data['post_id'] = $item->ID;

            $post_image = get_the_post_thumbnail_url($item->ID);
            $data['image'] = $post_image;

            $reviews[] = $data;
        }
    }

    $response['message'] = $api_message;
    $response['status'] = $api_status;

    $response['data'] = $reviews;

    return new WP_REST_Response($response);
}

function getAllCategories()
{
    $api_status = true;
    $api_message = 'Categories Fetched Successfully';

    $parent_terms = get_terms(array(
        'taxonomy' => 'product_cat',
        'hide_empty' => false,
        'parent' => 0,
    ));

    $categories = [];

    if (is_array($parent_terms) || is_object($parent_terms)) {
        foreach ($parent_terms as $pterm) {
            $data = [];
            $data["parent"] = $pterm;
            $parent = (object)$data["parent"];

            $is_banner = get_term_meta($pterm->term_id, 'is_banner', true);
            $parent->is_banner = $is_banner != "" ?  true : false;

            $is_menu = get_term_meta($pterm->term_id, 'is_menu', true);
            $parent->is_menu = $is_menu != "" ?  true : false;

            $thumbnail_id = get_woocommerce_term_meta($pterm->term_id, 'thumbnail_id', true);
            $image = wp_get_attachment_url($thumbnail_id);
            $parent->image = $image;

            $terms = get_terms('product_cat', array('parent' => $pterm->term_id, 'orderby' => 'slug', 'hide_empty' => false));

            $data['parent'] = $parent;
            $data['childs'] = $terms;

            $categories[] = $data;
        }
    }

    $response['message'] = $api_message;
    $response['status'] = $api_status;

    $response['data'] = $categories;

    return new WP_REST_Response($response);
}

function getProductsByCategory(WP_REST_Request $request)
{
    try {
        $slug = $request->get_param('slug');
        if (!$slug || $slug === "") {
            throw new Exception("Invalid Query");
        }

        $category = get_term_by('slug', $slug, 'product_cat');

        $category = (object)$category;

        $thumbnail_id = get_woocommerce_term_meta($category->term_id, 'thumbnail_id', true);
        $image = wp_get_attachment_url($thumbnail_id);
        $category->image = $image;

        $args = array(
            'post_type'             => 'product',
            'post_status'           => 'publish',
            'posts_per_page'        => -1,
            'tax_query'             => array(
                array(
                    'taxonomy'      => 'product_cat',
                    'field' => 'slug',
                    'terms'         => $slug,
                ),
            )
        );

        // for filter
        $filters = $request['filters'];
        if ($filters && !empty($filters) && is_array($filters)) {
            foreach ($filters as $filter_item) {
                $item = array(
                    'taxonomy'        => $filter_item['key'],
                    'field'           => 'slug',
                    'terms'           =>  $filter_item['filters'],
                    'operator'        => 'IN',
                );

                $args['tax_query'][] = $item;
            }
        }

        $meta_query =  array();
        $price_filter = $request->get_param('price_filter');
        if (
            $price_filter &&
            !empty($price_filter)
        ) {
            $args['orderby'] = 'meta_value_num';
            $args['meta_key'] = '_price';
            $args['order'] = $price_filter === 'low' ? 'ASC' : 'DESC';
        }

        $price_to = $request->get_param('to');
        $price_from = $request->get_param('from');

        if (
            $price_from &&
            !empty($price_from)
        ) {
            $meta_query[]['relation'] = 'AND';
            $meta_query[] = array(
                'key'     => '_price',
                'value'   => $price_from,
                'compare' => '>=',
                'type'    => 'NUMERIC'
            );
        }

        if (
            $price_to &&
            !empty($price_to)
        ) {
            $meta_query[] = array(
                'key'     => '_price',
                'value'   => $price_to,
                'compare' => '<=',
                'type'    => 'NUMERIC'
            );
        }

        $args['meta_query'] = $meta_query;
        // for filter

        $filters = get_posts($args);

        $products = array();

        foreach ($filters as $filter) {
            $data =  array();
            $product_id = $filter->ID;

            $product = wc_get_product($product_id);

            $data['id'] = $product_id;
            $data['name'] = $filter->post_title;
            $data['slug'] = $filter->post_name;
            $data['type'] = $product->get_type();
            $data['price'] = $product->get_price();

            $image = wp_get_attachment_url(get_post_thumbnail_id($product_id));
            $data['image'] = $image ? $image : 'https://api.urbanshop.pk/wp-content/uploads/woocommerce-placeholder-150x150.png';

            $products[] = $data;
        }

        return new WP_REST_Response(array('status' => true, 'products' => $products, 'category' => $category));
    } catch (Exception $e) {
        return new WP_REST_Response(array('status' => false, 'message' => $e->getMessage()));
    }
}

function getBestSellingProduct(WP_REST_Request $request)
{
    try {
        $args = array(
            'post_type'             => 'product',
            'post_status'           => 'publish',
            'posts_per_page'        => -1,
            'meta_key' => 'total_sales',
            'orderby' => 'meta_value_num',
        );

        $filters = get_posts($args);

        $products = array();

        foreach ($filters as $filter) {
            $data =  array();
            $product_id = $filter->ID;

            $product = wc_get_product($product_id);

            $data['id'] = $product_id;
            $data['name'] = $filter->post_title;
            $data['slug'] = $filter->post_name;
            $data['type'] = $product->get_type();
            $data['price'] = $product->get_price();

            $image = wp_get_attachment_url(get_post_thumbnail_id($product_id));
            $data['image'] = $image ? $image : 'https://api.urbanshop.pk/wp-content/uploads/woocommerce-placeholder-150x150.png';

            $products[] = $data;
        }

        return new WP_REST_Response(array('status' => true, 'products' => $products));
    } catch (Exception $e) {
        return new WP_REST_Response(array('status' => false, 'message' => $e->getMessage()));
    }
}

function getProductsDetail(WP_REST_Request $request)
{
    try {
        $slug = $request->get_param('slug');
        if (!$slug || $slug === "") {
            throw new Exception("Invalid Query");
        }

        $product_obj = get_page_by_path($slug, OBJECT, 'product');
        if (!$product_obj) {
            throw new Exception("Product Not Found");
        }

        $product_id = $product_obj->ID;

        $__product = get_product($product_id);

        $product = (object) [];
        $product->id = (int) $product_id;
        $product->title = $__product->name;
        $product->slug = $__product->slug;
        $product->type = $__product->product_type;
        $product->is_sale = $__product->is_on_sale();
        $product->short_description = $__product->get_short_description();
        $product->description = $__product->get_description();

        $product_variations = null;
        $attributes = null;

        // check product type
        if ($__product->is_type('variable')) {
            $product->sale_price = $__product->get_variation_sale_price('min', true);
            $product->price = $__product->get_variation_regular_price('max', true);
            $variations =  $__product->get_available_variations();
            $attributes =  $__product->get_attributes();

            $variationsArray = array();
            foreach ($attributes as $attr => $attr_deets) {
                $variationArray = array();

                $attribute_label = wc_attribute_label($attr);
                $variationArray["attribute_label"] = $attribute_label;

                if (isset($attributes[$attr]) || isset($attributes['pa_' . $attr])) {
                    $attribute = isset($attributes[$attr]) ? $attributes[$attr] : $attributes['pa_' . $attr];
                    if ($attribute['is_taxonomy'] && $attribute['is_visible']) {
                        $variationArray["attribute_name"] = $attribute['name'];
                        $variationNames = array();

                        foreach ($variations as $variation) {
                            if (!empty($variation['attributes']['attribute_' . $attribute['name']])) {
                                $__variations = array();

                                $taxonomy = $attribute['name'];
                                $meta = get_post_meta($variation['variation_id'], 'attribute_' . $taxonomy, true);
                                $term = get_term_by('slug', $meta, $taxonomy);

                                $__variations['variation_id'] = $variation['variation_id'];
                                $__variations['variation_name'] = $term->name;
                                $__variations['variation_price'] = $variation['display_regular_price'];

                                $variationNames[] = $__variations;
                            }
                        }

                        $variationArray["variations"] = $variationNames;
                    }
                }

                $variationsArray[] = $variationArray;
            }

            $product_variations = $variationsArray;
        } else {
            $product->sale_price = $__product->get_sale_price();
            $product->price = $__product->get_regular_price();
        }

        $product->available_variations = $product_variations;
        $product->attributes = $attributes;

        $image = wp_get_attachment_url(get_post_thumbnail_id($product_id));
        $product->image = $image ? $image : 'https://api.urbanshop.pk/wp-content/uploads/woocommerce-placeholder-150x150.png';

        $attachment_ids = $__product->get_gallery_image_ids();
        $gallery = array();
        foreach ($attachment_ids as $attachment_id) {
            $gallery[] = wp_get_attachment_url($attachment_id);
        }

        $product->gallery = $gallery;

        $terms = wp_get_post_terms($product_id, 'product_cat');

        $related_args = array(
            'post_type' => 'product',
            'posts_per_page' => 10,
            'post_status' => 'publish',
            'post__not_in' => array($product_id),
            'orderby' => 'rand',
            'tax_query' => array(
                array(
                    'taxonomy' => 'product_cat',
                    'field' => 'slug',
                    'terms' => array_column($terms, 'slug')
                )
            )
        );
        $filters = get_posts($related_args);
        $related_products = array();

        foreach ($filters as $filter) {
            $data =  array();
            $product_id = $filter->ID;

            $__product = wc_get_product($product_id);

            $data['id'] = $product_id;
            $data['name'] = $filter->post_title;
            $data['slug'] = $filter->post_name;
            $data['type'] = $__product->get_type();
            $data['price'] = $__product->get_price();

            $image = wp_get_attachment_url(get_post_thumbnail_id($product_id));
            $data['image'] = $image ? $image : 'https://api.urbanshop.pk/wp-content/uploads/woocommerce-placeholder-150x150.png';

            $related_products[] = $data;
        }

        return new WP_REST_Response(array('status' => true, 'product' => $product, 'related_products' => $related_products));
    } catch (Exception $e) {
        return new WP_REST_Response(array('status' => false, 'message' => $e->getMessage()));
    }
}

function createOrder(WP_REST_Request $request)
{
    try {
        $items = $request['items'];
        if (!is_array(($items)) || count($items) < 1) {
            throw new Exception("Empty Items");
        }

        $first_name = $request['first_name'];
        $email = $request['email'];
        $phone = $request['phone'];
        $phone_2 = $request['phone_2'];
        $address_1 = $request['address_1'];

        if (empty($first_name)) {
            throw new Exception("Please add first name");
        }

        if (empty($phone)) {
            throw new Exception("Please add phone name");
        }

        if (empty($address_1)) {
            throw new Exception("Please add Address 1 name");
        }


        $address = array(
            'first_name' => $first_name,
            'email'      => $email,
            'phone'      => $phone,
            'address_1'  => $address_1,
        );

        $order = wc_create_order();

        foreach ($items as $item) {
            $order->add_product(get_product($item['product']), $item['qty']);
        }

        $order->set_address($address, 'shipping');
        $order->set_address($address, 'billing');

        $order->calculate_totals();
        $order_id = $order->save();

        update_post_meta($order_id, 'phone_2', $phone_2);

        return new WP_REST_Response(array('status' => true, 'message' => 'Order has been placed successfully.', 'order_id' => $order_id));
    } catch (Exception $e) {
        return new WP_REST_Response(array('status' => false, 'message' => $e->getMessage()));
    }
}

function getProductsByKeyword(WP_REST_Request $request)
{
    try {
        $query = $request->get_param('query');
        // if (!$query || $query === "") {
        //     throw new Exception("Invalid Query");
        // }

        $args = array(
            'post_type'             => 'product',
            'post_status'           => 'publish',
            'posts_per_page'        => -1,
            's' => $query,
        );

        // for filter
        $filters = $request['filters'];
        if ($filters && !empty($filters) && is_array($filters)) {
            foreach ($filters as $filter_item) {
                $item = array(
                    'taxonomy'        => $filter_item['key'],
                    'field'           => 'slug',
                    'terms'           =>  $filter_item['filters'],
                    'operator'        => 'IN',
                );

                $args['tax_query'][] = $item;
            }
        }

        $meta_query =  array();
        $price_filter = $request->get_param('price_filter');
        if (
            $price_filter &&
            !empty($price_filter)
        ) {
            $args['orderby'] = 'meta_value_num';
            $args['meta_key'] = '_price';
            $args['order'] = $price_filter === 'low' ? 'ASC' : 'DESC';
        }

        $price_to = $request->get_param('to');
        $price_from = $request->get_param('from');

        if (
            $price_from &&
            !empty($price_from)
        ) {
            $meta_query[]['relation'] = 'AND';
            $meta_query[] = array(
                'key'     => '_price',
                'value'   => $price_from,
                'compare' => '>=',
                'type'    => 'NUMERIC'
            );
        }

        if (
            $price_to &&
            !empty($price_to)
        ) {
            $meta_query[] = array(
                'key'     => '_price',
                'value'   => $price_to,
                'compare' => '<=',
                'type'    => 'NUMERIC'
            );
        }

        $args['meta_query'] = $meta_query;
        // for filter

        $filters = get_posts($args);

        $products = array();

        foreach ($filters as $filter) {
            $data =  array();
            $product_id = $filter->ID;

            $product = wc_get_product($product_id);

            $data['id'] = $product_id;
            $data['name'] = $filter->post_title;
            $data['slug'] = $filter->post_name;
            $data['type'] = $product->get_type();
            $data['price'] = $product->get_price();

            $image = wp_get_attachment_url(get_post_thumbnail_id($product_id));
            $data['image'] = $image ? $image : 'https://api.urbanshop.pk/wp-content/uploads/woocommerce-placeholder-150x150.png';

            $products[] = $data;
        }

        return new WP_REST_Response(array('status' => true, 'products' => $products));
    } catch (Exception $e) {
        return new WP_REST_Response(array('status' => false, 'message' => $e->getMessage()));
    }
}

function getAllAttributes(WP_REST_Request $request)
{
    try {
        $attribute_taxonomies = wc_get_attribute_taxonomies();
        $taxonomy_terms = array();

        if ($attribute_taxonomies) :
            foreach ($attribute_taxonomies as $tax) :
                $list = (object) [];
                $list->label = $tax->attribute_label;
                $list->name = $tax->attribute_name;
                $list->taxonomy = 'pa_' . $tax->attribute_name;

                if (taxonomy_exists(wc_attribute_taxonomy_name($tax->attribute_name))) :
                    $list->options = get_terms(wc_attribute_taxonomy_name($tax->attribute_name), 'hide_empty=0');
                endif;

                $taxonomy_terms[] = $list;
            endforeach;
        endif;

        return new WP_REST_Response(array('status' => true, 'filters' => $taxonomy_terms));
    } catch (Exception $e) {
        return new WP_REST_Response(array('status' => false, 'message' => $e->getMessage()));
    }
}
