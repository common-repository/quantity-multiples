<?php

/**
 * Plugin Name:  Quantity Multiples
 * Author URI:   https://salehi-m.ir/
 * Description:  A simple plugin to set purchasable quantity multiples for WooCommerce products. Allow to set a specific multiple for the quantity of products that can be added to the cart. For example, if a multiple of 3 is set, users can add products in quantities of 3, 6, 9, etc.
 * Author:       Mohammad Salehi koleti
 * Text Domain: quantity-multiples
 * Domain Path: /languages
 * Version:      1.0.1
 * Requires PHP: 7.4
 * Requires at least: 6.0
 * License: GPLv3
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class MSQM_Woo_Quantity_Multiples
{

    private string $pluginDir;
    private string $pluginUrl;

    public function __construct()
    {
        $this->pluginDir = WP_PLUGIN_DIR . '/quantity-multiples';
        $this->pluginUrl = plugin_dir_url(__FILE__);

        add_action('plugins_loaded', array($this, 'load_textdomain'));
        add_action('woocommerce_product_options_general_product_data', array($this, 'add_custom_multiples_field'));
        add_action('woocommerce_process_product_meta', array($this, 'save_custom_multiples_field'));
        add_filter('woocommerce_add_to_cart_validation', array($this, 'validate_quantity_on_add_to_cart'), 10, 3);
        add_action('woocommerce_check_cart_items', array($this, 'validate_product_quantity_multiples'));
        add_action('woocommerce_single_product_summary', array($this, 'display_product_multiple_notice'), 20);
        add_action('woocommerce_before_single_product', array($this, 'enqueue_custom_quantity_script'));
    }

    public function load_textdomain()
    {
        load_plugin_textdomain('quantity-multiples', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function add_custom_multiples_field()
    {
        woocommerce_wp_text_input(
            array(
                'id' => '_product_multiple', // Custom field ID
                'label' => __('Purchasable Quantity Multiple', 'quantity-multiples'),
                'desc_tip' => 'true', // Show description tip
                'description' => __('Specify the acceptable quantity multiple for purchasing this product.', 'quantity-multiples'), // Description
                'type' => 'number',
                'custom_attributes' => array(
                    'step' => '1',
                    'min' => '1',
                ),
            )
        );
    }

    public function save_custom_multiples_field($post_id)
    {
        $product_multiple = isset($_POST['_product_multiple']) ? sanitize_text_field($_POST['_product_multiple']) : '';
        update_post_meta($post_id, '_product_multiple', $product_multiple);
    }

    // Front end validation
    public function validate_quantity_on_add_to_cart($passed, $product_id, $quantity)
    {
        $product_multiple = get_post_meta($product_id, '_product_multiple', true);
        if (!empty($product_multiple) && $product_multiple > 1) {
            if ($quantity % $product_multiple !== 0) {
                wc_add_notice(
                    sprintf(
                        // translators: %1$s: product title, %2$d: product multiple
                        __('Quantity of %1$s must be a multiple of %2$d.', 'quantity-multiples'),
                        get_the_title($product_id),
                        $product_multiple
                    ),
                    'error'
                );
                $passed = false;
            }
        }
        return $passed;
    }

    public function validate_product_quantity_multiples()
    {
        foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
            $product_id = $cart_item['product_id'];
            $quantity = $cart_item['quantity'];
            $product_multiple = get_post_meta($product_id, '_product_multiple', true);
            if (!empty($product_multiple) && $product_multiple > 1) {
                if ($quantity % $product_multiple !== 0) {
                    wc_add_notice(
                        sprintf(
                            // translators: %1$s: product title, %2$d: product multiple
                            __('Quantity of %1$s must be a multiple of %2$d.', 'quantity-multiples'),
                            get_the_title($product_id),
                            $product_multiple
                        ),
                        'error'
                    );
                }
            }
        }
    }

    public function display_product_multiple_notice()
    {
        global $product;
        $product_id = $product->get_id();
        $product_multiple = get_post_meta($product_id, '_product_multiple', true);
        if (!empty($product_multiple) && $product_multiple > 1) {
            echo '<p class="product-multiple-notice">' . esc_html(sprintf(
                // translators: %1$s: product title, %2$d: product multiple
                __('Quantity of %1$s must be a multiple of %2$d.', 'quantity-multiples'),
                get_the_title($product_id),
                $product_multiple
            )) . '</p>';
        }
    }

    public function enqueue_custom_quantity_script()
    {
        global $product;
        if (is_a($product, 'WC_Product')) {
            wp_enqueue_script('custom-quantity-script', $this->pluginUrl . 'assets/js/custom-quantity.js', array('jquery'), null, true);
            $product_id = $product->get_id();
            $product_multiple = get_post_meta($product_id, '_product_multiple', true);
            // translators: %d: product multiple
            $error_message = sprintf(__('Quantity must be a multiple of %d.', 'quantity-multiples'), $product_multiple);

            wp_localize_script('custom-quantity-script', 'productMultiple', array(
                'multiple' => !empty($product_multiple) ? intval($product_multiple) : 1,
                'error_message' => $error_message,
            ));
        }
    }
}

new MSQM_Woo_Quantity_Multiples();
