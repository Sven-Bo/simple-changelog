<?php
/**
 * Plugin Name: Simple Changelog
 * Plugin URI: https://github.com/Sven-Bo/simple-changelog
 * Description: A simple changelog manager for products with a beautiful timeline display.
 * Version: 1.0.2
 * Author: Sven Bosau
 * Author URI: https://pythonandvba.com
 * License: GPL v2 or later
 * Text Domain: simple-changelog
 */

if (!defined('ABSPATH')) {
    exit;
}

define('SCL_VERSION', '1.0.2');
define('SCL_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SCL_PLUGIN_URL', plugin_dir_url(__FILE__));

// Plugin Update Checker
require_once SCL_PLUGIN_DIR . 'plugin-update-checker/plugin-update-checker.php';
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

$scl_update_checker = PucFactory::buildUpdateChecker(
    'https://github.com/Sven-Bo/simple-changelog/',
    __FILE__,
    'simple-changelog'
);
$scl_update_checker->setBranch('main');

class Simple_Changelog {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('init', array($this, 'register_post_type'));
        add_action('admin_init', array($this, 'handle_delete_product'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));
        add_action('wp_enqueue_scripts', array($this, 'frontend_scripts'));
        add_action('wp_ajax_scl_save_changelog', array($this, 'ajax_save_changelog'));
        add_shortcode('changelog', array($this, 'render_shortcode'));
    }
    
    public function handle_delete_product() {
        if (!isset($_GET['page']) || $_GET['page'] !== 'simple-changelog') {
            return;
        }
        if (!isset($_GET['action']) || $_GET['action'] !== 'delete') {
            return;
        }
        if (!isset($_GET['product_id'])) {
            return;
        }
        
        check_admin_referer('scl_delete_product');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        wp_delete_post(intval($_GET['product_id']), true);
        wp_redirect(admin_url('admin.php?page=simple-changelog&deleted=1'));
        exit;
    }
    
    public function register_post_type() {
        register_post_type('scl_product', array(
            'labels' => array(
                'name' => __('Changelog Products', 'simple-changelog'),
                'singular_name' => __('Product', 'simple-changelog'),
                'add_new' => __('Add New Product', 'simple-changelog'),
                'add_new_item' => __('Add New Product', 'simple-changelog'),
                'edit_item' => __('Edit Product', 'simple-changelog'),
            ),
            'public' => false,
            'show_ui' => false,
            'supports' => array('title'),
            'capability_type' => 'post',
        ));
    }
    
    public function add_admin_menu() {
        add_menu_page(
            __('Changelog', 'simple-changelog'),
            __('Changelog', 'simple-changelog'),
            'manage_options',
            'simple-changelog',
            array($this, 'render_admin_page'),
            'dashicons-list-view',
            30
        );
    }
    
    public function admin_scripts($hook) {
        if ('toplevel_page_simple-changelog' !== $hook) {
            return;
        }
        wp_enqueue_style('scl-admin', SCL_PLUGIN_URL . 'assets/css/admin.css', array(), SCL_VERSION);
        wp_enqueue_script('scl-admin', SCL_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), SCL_VERSION, true);
        wp_localize_script('scl-admin', 'scl_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('scl_nonce'),
        ));
    }
    
    public function frontend_scripts() {
        wp_enqueue_style('scl-frontend', SCL_PLUGIN_URL . 'assets/css/frontend.css', array(), SCL_VERSION);
        wp_enqueue_script('scl-frontend', SCL_PLUGIN_URL . 'assets/js/frontend.js', array(), SCL_VERSION, true);
    }
    
    public function render_admin_page() {
        include SCL_PLUGIN_DIR . 'includes/admin-page.php';
    }
    
    public function ajax_save_changelog() {
        check_ajax_referer('scl_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
        $product_name = isset($_POST['product_name']) ? sanitize_text_field($_POST['product_name']) : '';
        $changelog_content = isset($_POST['changelog_content']) ? wp_kses_post($_POST['changelog_content']) : '';
        
        if (empty($product_name)) {
            wp_send_json_error('Product name is required');
        }
        
        $post_data = array(
            'post_title' => $product_name,
            'post_type' => 'scl_product',
            'post_status' => 'publish',
        );
        
        if ($product_id > 0) {
            $post_data['ID'] = $product_id;
            wp_update_post($post_data);
        } else {
            $product_id = wp_insert_post($post_data);
        }
        
        update_post_meta($product_id, '_scl_changelog_content', $changelog_content);
        
        wp_send_json_success(array(
            'product_id' => $product_id,
            'message' => 'Changelog saved successfully',
        ));
    }
    
    public function get_products() {
        return get_posts(array(
            'post_type' => 'scl_product',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
        ));
    }
    
    public function parse_changelog($content) {
        $releases = array();
        $lines = explode("\n", $content);
        $current_release = null;
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;
            
            // Match version header: = 3.0 (01 April 2025) =
            if (preg_match('/^=\s*(.+?)\s*\((.+?)\)\s*=$/', $line, $matches)) {
                if ($current_release) {
                    $releases[] = $current_release;
                }
                $current_release = array(
                    'version' => trim($matches[1]),
                    'date' => trim($matches[2]),
                    'items' => array(),
                );
            } elseif ($current_release && preg_match('/^(\w+):\s*(.+)$/', $line, $matches)) {
                $current_release['items'][] = array(
                    'type' => strtolower(trim($matches[1])),
                    'text' => trim($matches[2]),
                );
            }
        }
        
        if ($current_release) {
            $releases[] = $current_release;
        }
        
        return $releases;
    }
    
    public function render_shortcode($atts) {
        $atts = shortcode_atts(array(
            'id' => 0,
            'limit' => 0,
            'version' => '',
        ), $atts, 'changelog');
        
        $product_id = intval($atts['id']);
        if ($product_id <= 0) {
            return '<p>Please specify a valid product ID.</p>';
        }
        
        $product = get_post($product_id);
        if (!$product || $product->post_type !== 'scl_product') {
            return '<p>Product not found.</p>';
        }
        
        $content = get_post_meta($product_id, '_scl_changelog_content', true);
        $releases = $this->parse_changelog($content);
        
        if (empty($releases)) {
            return '<p>No changelog entries found.</p>';
        }
        
        // Filter by specific version if provided
        if (!empty($atts['version'])) {
            $releases = array_filter($releases, function($r) use ($atts) {
                return $r['version'] === $atts['version'];
            });
            $releases = array_values($releases);
        }
        
        // Prepare display settings
        $limit = intval($atts['limit']);
        $total_releases = count($releases);
        $show_more = $limit > 0 && $total_releases > $limit;
        
        // Limit releases if specified
        $display_releases = $limit > 0 ? array_slice($releases, 0, $limit) : $releases;
        $hidden_releases = $limit > 0 ? array_slice($releases, $limit) : array();
        
        ob_start();
        include SCL_PLUGIN_DIR . 'includes/frontend-template.php';
        return ob_get_clean();
    }
}

// Initialize plugin
Simple_Changelog::get_instance();

// Activation hook
register_activation_hook(__FILE__, function() {
    flush_rewrite_rules();
});

// Deactivation hook
register_deactivation_hook(__FILE__, function() {
    flush_rewrite_rules();
});
