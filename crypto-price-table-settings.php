<?php
// Prevent direct access to the file
if (!defined('ABSPATH')) {
    exit;
}

add_action('admin_enqueue_scripts', 'enqueue_crypto_price_table_styles');

function enqueue_crypto_price_table_styles($hook) {
    if ($hook !== 'toplevel_page_crypto-price-table') {
        return;
    }
    $version = '1.0.0'; 
    wp_enqueue_style('crypto-price-table-css', plugins_url('includes/css/crypto-price-table.css', __FILE__), array(), $version);
}

// Function to display the settings page
function crypto_price_table_settings_page() {
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Crypto Price Table Settings', 'crypto-price-table'); ?></h1>
        <div class="cpt-crypto-settings-container">
            <div class="cpt-crypto-settings-box">
                <form method="post" action="options.php">
                    <?php
                    settings_fields('crypto_price_table_settings_group');
                    do_settings_sections('crypto_price_table');
                    submit_button(__('Generate & Copy Shortcode', 'crypto-price-table'));
                    ?>
                </form>
                <?php wp_editor(generate_shortcode_preview(), 'editor1', ['wpautop' => 1, 'media_buttons' => 0, 'textarea_name' => '', 'textarea_rows' => 6, 'tabindex' => null, 'teeny' => 0, 'dfw' => 0, 'tinymce' => 0, 'quicktags' => 0, 'drag_drop_upload' => false]); ?>
            </div>
            <div class="cpt-crypto-preview-box">
                <h2><?php esc_html_e('Preview', 'crypto-price-table'); ?></h2>
                <?php echo do_shortcode(generate_shortcode_preview()); ?>
            </div>
        </div>
    </div>

    <?php
}

// Function to generate shortcode preview
function generate_shortcode_preview() {
    $selected_coins = get_option('crypto_price_table_coins', ['bitcoin', 'ethereum', 'binance-coin']);
    $text_color = get_option('crypto_price_table_text_color', '#000000');
    $table_head_color = get_option('crypto_price_table_table_head_color', '#90EE90');
    $table_body_color = get_option('crypto_price_table_table_body_color', '#ffffff');
    $show_marketcap = get_option('crypto_price_table_marketcap', true); 
    $show_credits = get_option('crypto_price_table_credits', false); 
    $coins_str = implode(',', $selected_coins);
    return '[crypto_price_table coins="' . esc_attr($coins_str) . '" text_color="' . esc_attr($text_color) . '" table_head_color="' . esc_attr($table_head_color) . '" table_body_color="' . esc_attr($table_body_color) . '" show_marketcap="' . ($show_marketcap ? 'true' : 'false') . '" show_credits="' . ($show_credits ? 'true' : 'false') . '"]';
}

// Function to register settings
function crypto_price_table_register_settings() {
    register_setting('crypto_price_table_settings_group', 'crypto_price_table_coins', 'crypto_price_table_sanitize_coins');
    register_setting('crypto_price_table_settings_group', 'crypto_price_table_text_color', 'sanitize_hex_color');
    register_setting('crypto_price_table_settings_group', 'crypto_price_table_table_head_color', 'sanitize_hex_color');
    register_setting('crypto_price_table_settings_group', 'crypto_price_table_table_body_color', 'sanitize_hex_color');
    register_setting('crypto_price_table_settings_group', 'crypto_price_table_marketcap', 'intval'); 
    register_setting('crypto_price_table_settings_group', 'crypto_price_table_credits', 'intval'); 

    add_settings_section(
        'crypto_price_table_main_section',
        __('Main Settings', 'crypto-price-table'),
        'crypto_price_table_main_section_cb',
        'crypto_price_table'
    );

    add_settings_field(
        'crypto_price_table_coins',
        __('Select Cryptocurrencies', 'crypto-price-table'),
        'crypto_price_table_coins_field_cb',
        'crypto_price_table',
        'crypto_price_table_main_section'
    );

    add_settings_field(
        'crypto_price_table_text_color',
        __('Text Color', 'crypto-price-table'),
        'crypto_price_table_text_color_field_cb',
        'crypto_price_table',
        'crypto_price_table_main_section'
    );

    add_settings_field(
        'crypto_price_table_table_head_color',
        __('Table Head Color', 'crypto-price-table'),
        'crypto_price_table_table_head_color_field_cb',
        'crypto_price_table',
        'crypto_price_table_main_section'
    );

    add_settings_field(
        'crypto_price_table_table_body_color',
        __('Table Body Color', 'crypto-price-table'),
        'crypto_price_table_table_body_color_field_cb',
        'crypto_price_table',
        'crypto_price_table_main_section'
    );

    add_settings_field(
        'crypto_price_table_marketcap',
        __('Show Marketcap', 'crypto-price-table'),
        'crypto_price_table_marketcap_cb',
        'crypto_price_table',
        'crypto_price_table_main_section'
    );

    add_settings_field(
        'crypto_price_table_credits',
        __('Show Credits', 'crypto-price-table'),
        'crypto_price_table_credits_cb',
        'crypto_price_table',
        'crypto_price_table_main_section'
    );
}

// Callback function for the main section
function crypto_price_table_main_section_cb() {
    echo '<p>' . esc_html__('Select the cryptocurrencies you want to display.', 'crypto-price-table') . '</p>';
}

// Callback function for the coins field
function crypto_price_table_coins_field_cb() {
    // Ensure the WordPress filesystem API is available
    if ( ! function_exists('WP_Filesystem') ) {
        require_once(ABSPATH . 'wp-admin/includes/file.php');
    }

    // Initialize the WordPress filesystem
    WP_Filesystem();

    global $wp_filesystem;

    // Get all available coins
    $json_file = plugin_dir_path(__FILE__) . 'includes/data/coins.json';
    if ( $wp_filesystem->exists($json_file) ) {
        $json_data = $wp_filesystem->get_contents($json_file);
        $data = json_decode($json_data, true);
        $all_coins = $data['coins'];
    } else {
        $all_coins = [];
    }
    
    // Get Previously Selected Coins
    $selected_coins = get_option('crypto_price_table_coins', ['bitcoin', 'ethereum', 'binance-coin']);
    if (!is_array($selected_coins)) {
        $selected_coins = explode(',', $selected_coins);
    }

    echo '<select id="crypto_price_table_coins" name="crypto_price_table_coins[]" multiple="multiple" style="width: 100%;">';
    foreach ($all_coins as $coin) {
        $selected = in_array($coin, $selected_coins) ? 'selected' : '';
        echo '<option value="' . esc_attr($coin) . '" ' . esc_attr($selected) . '>' . esc_html($coin) . '</option>';
    }
    echo '</select>';
}

// Callback function for the text color field
function crypto_price_table_text_color_field_cb() {
    $text_color = get_option('crypto_price_table_text_color', '#000000'); // Default to black if not set
    echo '<input type="text" id="crypto_price_table_text_color" name="crypto_price_table_text_color" value="' . esc_attr($text_color) . '" class="color-field">';
}

// Callback function for the table head color field
function crypto_price_table_table_head_color_field_cb() {
    $table_head_color = get_option('crypto_price_table_table_head_color', '#90EE90'); // Default to light gray if not set
    echo '<input type="text" id="crypto_price_table_table_head_color" name="crypto_price_table_table_head_color" value="' . esc_attr($table_head_color) . '" class="color-field">';
}

// Callback function for the table body color field
function crypto_price_table_table_body_color_field_cb() {
    $table_body_color = get_option('crypto_price_table_table_body_color', '#ffffff'); // Default to white if not set
    echo '<input type="text" id="crypto_price_table_table_body_color" name="crypto_price_table_table_body_color" value="' . esc_attr($table_body_color) . '" class="color-field">';
}

// Callback function for the table show marketcap
function crypto_price_table_marketcap_cb() {
    $show_marketcap = get_option('crypto_price_table_marketcap', true); // Default to true if not set
    echo '<input type="checkbox" id="crypto_price_table_marketcap" name="crypto_price_table_marketcap" value="1" ' . checked(1, $show_marketcap, false) . '>';
}

// Callback function for the table show credits
function crypto_price_table_credits_cb() {
    $show_credits = get_option('crypto_price_table_credits', false); // Default to False if not set
    echo '<input type="checkbox" id="crypto_price_table_credits" name="crypto_price_table_credits" value="1" ' . checked(1, $show_credits, false) . '>';
}

// Sanitize the coins option to ensure it is always stored as an array
function crypto_price_table_sanitize_coins($input) {
    if (is_array($input)) {
        return $input;
    }
    return explode(',', $input);
}

// Enqueue Select2 and color picker script and style
function crypto_price_table_enqueue_scripts() {
    wp_enqueue_style('wp-color-picker');
    wp_enqueue_script('crypto-price-table-color-picker', plugins_url('includes/js/crypto-price-table-color-picker.js', __FILE__), array('wp-color-picker'), '1.0.0', true);

    // Enqueue Select2
    wp_enqueue_style('select2', plugin_dir_url(__FILE__) . 'includes/css/select2.css', array(), '1.0.0');
    wp_enqueue_script('select2', plugins_url('includes/js/select2.js', __FILE__), array('jquery'), '1.0.0', true);

    // Initialize Select2 and Color Picker
    wp_add_inline_script('select2', 'jQuery(document).ready(function($) { $("#crypto_price_table_coins").select2(); $(".color-field").wpColorPicker(); });');
}
add_action('admin_enqueue_scripts', 'crypto_price_table_enqueue_scripts');

// Hook into admin init to register settings
add_action('admin_init', 'crypto_price_table_register_settings');
?>
