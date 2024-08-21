<?php

/**
 * @version 1.0.0
 */

/*
Plugin Name: Crypto Price Table
Description: Customizable Cryptocurrency Price Table with real-time price update, marketcap and flexible settings. 
Version: 1.0.0
Author: icogems
Author URI: https://icogems.com
License: GPLv2 or later
Text Domain: crypto-price-table
*/

// Prevent direct access to the file
if (!defined('ABSPATH')) {
    exit;
}

// Include the settings page file
require_once(plugin_dir_path(__FILE__) . 'crypto-price-table-settings.php');

// Enqueue styles for the table
function cptpref_crypto_price_table_enqueue_styles() {
    if (!is_singular()) {
        return;
    }
    wp_enqueue_style('crypto-price-table-style', plugin_dir_url(__FILE__) . 'includes/css/crypto-price-table.css', array(), '1.0.0');
}
add_action('wp_enqueue_scripts', 'cptpref_crypto_price_table_enqueue_styles');

// Shortcode function to display the crypto price table
function cptpref_crypto_price_table_shortcode($attr) {
    $attr = shortcode_atts(array(
        'coins' => 'bitcoin,ethereum,binance-coin',
        'text_color' => '#000000',
        'table_head_color' => '#90EE90',
        'table_body_color' => '#ffffff',
        'show_marketcap' => 'true',
        'show_credits' => 'false',
    ), $attr, 'cptpref_crypto_price_table');
    
    wp_enqueue_script('crypto-price-table-script', plugin_dir_url(__FILE__) . 'includes/js/crypto-price-table.js', array('jquery'), '1.0.0', true);
    wp_localize_script('crypto-price-table-script', 'cryptoPriceTable', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('crypto-price-table-nonce'),
        'coins' => $attr['coins'],
        'show_marketcap' => $attr['show_marketcap'],
        'logo_url' => plugin_dir_url(__FILE__) . 'public/logos/',

        'table_body_color' => $attr['table_body_color'],
        'text_color' => $attr['text_color'],
    ));

    $text_color = esc_attr($attr['text_color']);
    $table_head_color = esc_attr($attr['table_head_color']);
    $table_body_color = esc_attr($attr['table_body_color']);
    $show_marketcap = esc_attr($attr['show_marketcap']);
    $show_credits = esc_attr($attr['show_credits']);


    $output = '<div class="cpt-table-container" id="crypto-price-table-container">';
    $output .= '<table id="crypto-price-table" class="cpt-custom-table">';
    $output .= '<thead><tr style="background-color: ' . $table_head_color . ' !important; color: ' . $text_color . ' !important;"><th class="cpt-table-header-cell">Cryptocurrency</th><th class="cpt-table-header-cell">Price (USD)</th>';
    if ($show_marketcap === 'true') {
        $output .= '<th class="cpt-table-header-cell">Market Cap</th>';
    }
    $output .= '</tr></thead>';
    $output .= '<tbody>';

    // Here Data will be populated by JavaScript

    $output .= '</tbody>';
    if ($show_credits === 'true') {
        $output .= '<tfoot><tr><td colspan="3" style="text-align: right; font-size: 12px;">Powered By <a href="https://icogems.com" target="_blank" style="color: orange; text-decoration: none;">ICOGEMS</a></td></tr></tfoot>';
    }
    $output .= '</table>';
    $output .= '</div>';

    return $output;
}
add_shortcode('cptpref_crypto_price_table', 'cptpref_crypto_price_table_shortcode');

// Function to register admin menu
function cptpref_crypto_price_table_admin_menu() {
    add_menu_page(
        'Crypto Price Table Settings',
        'Crypto Price Table',
        'manage_options',
        'crypto-price-table',
        'cptpref_crypto_price_table_settings_page',
        'dashicons-chart-line',
        100
    );
}
add_action('admin_menu', 'cptpref_crypto_price_table_admin_menu');
