<?php
/**
 * Plugin Name: Cost Calculator with Currency Converter
 * Plugin URI: https://example.com/cost-calculator
 * Description: A secure cost calculator plugin with real-time currency conversion for major world currencies, including East African currencies.
 * Version: 1.1
 * Author: Edward W. Wandera
 * Author URI: https://example.com
 * License: GPLv2 or later
 * Text Domain: cost-calculator
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define currency options
function cc_get_currencies() {
    return array(
        'USD' => 'United States Dollar',
        'EUR' => 'Euro',
        'GBP' => 'British Pound Sterling',
        'KES' => 'Kenyan Shilling',
        'TZS' => 'Tanzanian Shilling',
        'UGX' => 'Ugandan Shilling',
        'RWF' => 'Rwandan Franc',
        'BIF' => 'Burundian Franc',
        // Add more currencies as needed
    );
}

// Enqueue styles and scripts
function cc_enqueue_scripts() {
    wp_enqueue_style('cost-calculator-style', plugins_url('style.css', __FILE__));
    wp_enqueue_script('cost-calculator-script', plugins_url('script.js', __FILE__), array('jquery'), null, true);
    wp_localize_script('cost-calculator-script', 'cc_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('cc_nonce'),
    ));
}
add_action('wp_enqueue_scripts', 'cc_enqueue_scripts');

// Shortcode to display the calculator
function cc_cost_calculator_shortcode($atts) {
    $atts = shortcode_atts(array(
        'default_currency' => 'USD',
    ), $atts, 'cost_calculator');

    $currencies = cc_get_currencies();
    $default_currency = esc_attr($atts['default_currency']);

    ob_start();
    ?>
    <div class="cost-calculator">
        <label for="cc-amount">Amount:</label>
        <input type="number" id="cc-amount" step="0.01" min="0" required>
        
        <label for="cc-from-currency">From Currency:</label>
        <select id="cc-from-currency">
            <?php foreach ($currencies as $code => $name) : ?>
                <option value="<?php echo esc_attr($code); ?>" <?php selected($code, $default_currency); ?>>
                    <?php echo esc_html("$code - $name"); ?>
                </option>
            <?php endforeach; ?>
        </select>
        
        <label for="cc-to-currency">To Currency:</label>
        <select id="cc-to-currency">
            <?php foreach ($currencies as $code => $name) : ?>
                <option value="<?php echo esc_attr($code); ?>">
                    <?php echo esc_html("$code - $name"); ?>
                </option>
            <?php endforeach; ?>
        </select>
        
        <button id="cc-calculate">Convert</button>
        
        <div id="cc-result"></div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('cost_calculator', 'cc_cost_calculator_shortcode');

// AJAX handler for currency conversion
function cc_currency_conversion() {
    check_ajax_referer('cc_nonce', 'nonce');

    $amount = floatval($_POST['amount']);
    $from_currency = sanitize_text_field($_POST['from_currency']);
    $to_currency = sanitize_text_field($_POST['to_currency']);

    if ($amount <= 0 || empty($from_currency) || empty($to_currency)) {
        wp_send_json_error('Invalid input.');
    }

    // Fetch exchange rates from API
    $api_key = 'e8831c0a39c2404eb3f52628'; //
    $api_url = "https://v6.exchangerate-api.com/v6/{$api_key}/latest/{$from_currency}";
    $response = wp_remote_get($api_url);

    if (is_wp_error($response)) {
        wp_send_json_error('Unable to fetch exchange rates.');
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);
    if ($body['result'] !== 'success') {
        wp_send_json_error('Invalid API response.');
    }

    $exchange_rate = $body['conversion_rates'][$to_currency];
    $converted_amount = $amount * $exchange_rate;

    wp_send_json_success(array(
        'converted_amount' => $converted_amount,
        'to_currency' => $to_currency,
    ));
}
add_action('wp_ajax_cc_currency_conversion', 'cc_currency_conversion');
add_action('wp_ajax_nopriv_cc_currency_conversion', 'cc_currency_conversion');
