<?php
/**
 * Plugin Name: Senior Supper Services
 * Plugin URI: https://seniorsupperservices.net/
 * Description: Complete meal delivery order management system for Senior Supper Services
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://yoursite.com
 * License: GPL v2 or later
 * Text Domain: senior-supper-services
 * Domain Path: /languages
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('SSS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SSS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SSS_PLUGIN_VERSION', '1.0.0');
define('SSS_TABLE_PREFIX', $GLOBALS['wpdb']->prefix . 'sss_');

// Require necessary files
require_once SSS_PLUGIN_DIR . 'includes/database/schema.php';
require_once SSS_PLUGIN_DIR . 'includes/admin/admin-pages.php';
require_once SSS_PLUGIN_DIR . 'includes/admin/settings.php';
require_once SSS_PLUGIN_DIR . 'includes/admin/meals-manager.php';
require_once SSS_PLUGIN_DIR . 'includes/admin/sides-manager.php';
require_once SSS_PLUGIN_DIR . 'includes/admin/combos-manager.php';
require_once SSS_PLUGIN_DIR . 'includes/admin/orders-list.php';
require_once SSS_PLUGIN_DIR . 'includes/forms/form-renderer.php';
require_once SSS_PLUGIN_DIR . 'includes/forms/form-processor.php';
require_once SSS_PLUGIN_DIR . 'includes/api/pricing.php';
require_once SSS_PLUGIN_DIR . 'includes/api/orders.php';

/**
 * Main Plugin Class
 */
class Senior_Supper_Services {
    
    public static $instance;
    
    public static function get_instance() {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function __construct() {
        // Activation hook
        register_activation_hook(__FILE__, array($this, 'activate'));
        
        // Deactivation hook
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        // Admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Admin styles and scripts
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // Frontend styles and scripts
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
        
        // Register shortcodes
        add_shortcode('sss_order_form', array($this, 'render_order_form'));
        
        // AJAX endpoints
        add_action('wp_ajax_sss_get_meals', array($this, 'ajax_get_meals'));
        add_action('wp_ajax_nopriv_sss_get_meals', array($this, 'ajax_get_meals'));
        add_action('wp_ajax_sss_calculate_price', array($this, 'ajax_calculate_price'));
        add_action('wp_ajax_nopriv_sss_calculate_price', array($this, 'ajax_calculate_price'));
        add_action('wp_ajax_sss_submit_form', array($this, 'ajax_submit_form'));
        add_action('wp_ajax_nopriv_sss_submit_form', array($this, 'ajax_submit_form'));
    }
    
    /**
     * Plugin Activation
     */
    public function activate() {
        SSS_Database_Schema::create_tables();
        SSS_Database_Schema::create_default_settings();
        flush_rewrite_rules();
    }
    
    /**
     * Plugin Deactivation
     */
    public function deactivate() {
        flush_rewrite_rules();
    }
    
    /**
     * Add Admin Menu
     */
    public function add_admin_menu() {
        add_menu_page(
            'Senior Supper Services',
            'Supper Services',
            'manage_options',
            'sss_dashboard',
            array('SSS_Admin_Pages', 'render_dashboard'),
            'dashicons-utensils',
            25
        );
        
        add_submenu_page(
            'sss_dashboard',
            'Settings',
            'Settings',
            'manage_options',
            'sss_settings',
            array('SSS_Settings', 'render_page')
        );
        
        add_submenu_page(
            'sss_dashboard',
            'Meals',
            'Meals',
            'manage_options',
            'sss_meals',
            array('SSS_Meals_Manager', 'render_page')
        );
        
        add_submenu_page(
            'sss_dashboard',
            'Sides',
            'Sides',
            'manage_options',
            'sss_sides',
            array('SSS_Sides_Manager', 'render_page')
        );
        
        add_submenu_page(
            'sss_dashboard',
            'Combos',
            'Combos',
            'manage_options',
            'sss_combos',
            array('SSS_Combos_Manager', 'render_page')
        );
        
        add_submenu_page(
            'sss_dashboard',
            'Orders',
            'Orders',
            'manage_options',
            'sss_orders',
            array('SSS_Orders_List', 'render_page')
        );
    }
    
    /**
     * Enqueue Admin Scripts and Styles
     */
    public function enqueue_admin_scripts($hook) {
        wp_enqueue_style('sss-admin-style', SSS_PLUGIN_URL . 'assets/css/admin-style.css', array(), SSS_PLUGIN_VERSION);
        wp_enqueue_script('sss-admin-script', SSS_PLUGIN_URL . 'assets/js/admin-script.js', array('jquery'), SSS_PLUGIN_VERSION, true);
        wp_localize_script('sss-admin-script', 'sssAdmin', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('sss_admin_nonce')
        ));
    }
    
    /**
     * Enqueue Frontend Scripts and Styles
     */
    public function enqueue_frontend_scripts() {
        wp_enqueue_style('sss-frontend-style', SSS_PLUGIN_URL . 'assets/css/frontend-style.css', array(), SSS_PLUGIN_VERSION);
        wp_enqueue_script('sss-frontend-script', SSS_PLUGIN_URL . 'assets/js/frontend-script.js', array('jquery'), SSS_PLUGIN_VERSION, true);
        wp_localize_script('sss-frontend-script', 'sssData', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('sss_frontend_nonce')
        ));
    }
    
    /**
     * Render Order Form Shortcode
     */
    public function render_order_form() {
        ob_start();
        include SSS_PLUGIN_DIR . 'templates/order-form.php';
        return ob_get_clean();
    }
    
    /**
     * AJAX: Get Meals for Day
     */
    public function ajax_get_meals() {
        check_ajax_referer('sss_frontend_nonce', 'nonce');
        
        $day = sanitize_text_field($_POST['day']);
        $meals = SSS_Form_Renderer::get_meals_for_day($day);
        
        wp_send_json_success($meals);
    }
    
    /**
     * AJAX: Calculate Price
     */
    public function ajax_calculate_price() {
        check_ajax_referer('sss_frontend_nonce', 'nonce');
        
        $data = array(
            'email' => sanitize_email($_POST['email']),
            'day' => sanitize_text_field($_POST['day']),
            'meals' => isset($_POST['meals']) ? array_map('intval', $_POST['meals']) : array(),
            'sides' => isset($_POST['sides']) ? array_map('intval', $_POST['sides']) : array(),
            'dessert' => isset($_POST['dessert']) ? intval($_POST['dessert']) : 0,
            'quantity' => intval($_POST['quantity'])
        );
        
        $price = SSS_Pricing_Engine::calculate_total($data);
        
        wp_send_json_success(array('price' => $price));
    }
    
    /**
     * AJAX: Submit Form
     */
    public function ajax_submit_form() {
        check_ajax_referer('sss_frontend_nonce', 'nonce');
        
        $order_data = SSS_Form_Processor::process_submission($_POST);
        
        if (is_wp_error($order_data)) {
            wp_send_json_error(array('message' => $order_data->get_error_message()));
        } else {
            wp_send_json_success(array(
                'message' => 'Order submitted successfully!',
                'order_id' => $order_data
            ));
        }
    }
}

// Initialize plugin
add_action('plugins_loaded', function() {
    Senior_Supper_Services::get_instance();
});
