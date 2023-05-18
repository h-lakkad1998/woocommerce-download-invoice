<?php
/**
* Plugin Name: Woo invoice download
* Description: A WooCommerce plugin created for downloading invoice from orders in users' My-Account page.
* Version: 1.0
* Author: Hardik Patel / Hardik Lakkad
* Author URI: https://in.linkedin.com/in/hardik-lakkad-097b12147
* Developer E-mail: hardiklakkad2@gmail.com
* Instagram id: hlakkad 
**/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// create the custom field on product admin tab
add_action( 'woocommerce_product_options_general_product_data', 'create_warranty_custom_field' );
function create_warranty_custom_field() {
    // Create a custom text field
    woocommerce_wp_text_input( array(
        'id'            => '_warranty_personalized',
        'type'          => 'text',
        'label'         => __('Personalized text', 'woocommerce' ),
        'description'   => '',
        'desc_tip'      => 'true',
        'placeholder'   =>  __('Please Enter text', 'woocommerce' ),
    ) );
}

// save the data value from this custom field on product admin tab
add_action( 'woocommerce_process_product_meta', 'save_warranty_custom_field' );
function save_warranty_custom_field( $post_id ) {
    $wc_text_field = $_POST['_warranty_personalized'];
    if ( !empty($wc_text_field) ) {
        update_post_meta( $post_id, '_warranty_personalized', esc_attr( $wc_text_field ) );
    }
}
function cfwc_display_custom_box( ) {
    global $post;
    // Check for the custom field value
    $product = wc_get_product( $post->ID );
    $title = $product->get_meta( '_warranty_personalized' );
    if( $title ) {
        echo '<div class="cfwc-custom-field-wrapper">'.$title.'</div>';
       
    }
}
add_action( 'woocommerce_before_add_to_cart_button', 'cfwc_display_custom_box' );

// Store custom field in Cart
add_filter( 'woocommerce_add_cart_item_data', 'store_warranty_custom_field', 10, 2 );

function store_warranty_custom_field( $cart_item_data, $product_id ) {
    $warranty_item = get_post_meta( $product_id , '_warranty_personalized', true );
    if( !empty($warranty_item) ) {
        $cart_item_data[ '_warranty_personalized' ] = $warranty_item;

        // below statement make sure every add to cart action as unique line item
        $cart_item_data['unique_key'] = md5( microtime().rand() );
        WC()->session->set( 'days_manufacture', $warranty_item );
    }
    return $cart_item_data;
}


// Render meta on cart and checkout
add_filter( 'woocommerce_get_item_data', 'rendering_meta_field_on_cart_and_checkout', 10, 2 );

function rendering_meta_field_on_cart_and_checkout( $cart_data, $cart_item ) {
    $custom_items = array();
    // Woo 2.4.2 updates
    if( !empty( $cart_data ) ) {
        $custom_items = $cart_data;
    }
    if( isset( $cart_item['_warranty_personalized'] ) ) {
        $custom_items[] = array( "name" => __( "Personalized Detail", "woocommerce" ), "value" => $cart_item['_warranty_personalized'] );
    }
    return $custom_items;
}

// Add the information in the order as meta data
add_action('woocommerce_add_order_item_meta','add_waranty_to_order_item_meta', 1, 3 );
function add_waranty_to_order_item_meta( $item_id, $values, $cart_item_key ) {
    // Retrieving the product id for the order $item_id
    $product_id = wc_get_order_item_meta( $item_id, '_product_id', true );
    // Getting the warranty value for this product Id
    $warranty = get_post_meta( $product_id, '_warranty_personalized', true );
    // Add the meta data to the order
    wc_add_order_item_meta($item_id, 'Personalized Detail', $warranty, true);
}

if ( ! defined( 'WC_ORDER_PDF_PLUGIN_FILE' ) ) {
    define( 'WC_ORDER_PDF_PLUGIN_FILE', __FILE__ );
}

// Include the main WC_Order_PDF_Download class.
if ( ! class_exists( 'WC_Order_PDF_Download', false ) ) {
    include_once dirname( WC_ORDER_PDF_PLUGIN_FILE ) . '/includes/class-wc-order-pdf-download.php';
}
$wc_order_pdf_download = new WC_Order_PDF_Download();

