<?php

/**

 * @package Rest-API-APP

 */

/*

Plugin Name: Rest API APP

Plugin URI: https://codexloopers.com/

Description: Rest Apis for website's app version to integrate the app with website.

Version: 1.0.0

Author: Muhammad Huzaifa

Author URI: https://codexloopers.com/

 */


// wp_enqueue_style('app-css', plugins_url('/css/app.css', __FILE__));
// wp_enqueue_style('bootstrap-css', 'https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css');

// wp_enqueue_script('jquery-custom', plugins_url('/js/jquery.min.js', __FILE__), array('jquery'), 1.1, true);
// wp_enqueue_script('bootstrap-js', 'https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js', array('jquery'), 1.1, true);
// wp_enqueue_script('pdf-js', 'https://cdnjs.cloudflare.com/ajax/libs/jspdf/1.3.5/jspdf.debug.js', array('jquery'), 1.1, true);
// wp_enqueue_script('script', plugins_url('/js/init.js', __FILE__), array('jquery'), 1.1, true);
// wp_localize_script('script', 'my_ajax_object', array('ajax_url' => admin_url('admin-ajax.php')));

include plugin_dir_path(__FILE__) . 'inc/routes.php';
include plugin_dir_path(__FILE__) . 'inc/functions.php';

