<?php
/**
 * Theme Integration Class
 * 
 * Handles integration between Terpedia plugin and the scientific theme
 * 
 * @package Terpedia_Replit_Bridge
 * @version 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Terpedia_Theme_Integration {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_loaded', array($this, 'setup_theme'));
        add_filter('theme_root', array($this, 'add_plugin_theme_directory'));
        add_filter('theme_root_uri', array($this, 'add_plugin_theme_directory_uri'));
    }
    
    /**
     * Initialize theme integration
     */
    public function init() {
        // Register theme directory
        $this->register_plugin_theme();
        
        // Add theme support hooks
        add_action('after_setup_theme', array($this, 'theme_setup'));
        
        // Enqueue theme assets
        add_action('wp_enqueue_scripts', array($this, 'enqueue_theme_assets'));
        
        // Add theme customization options
        add_action('customize_register', array($this, 'customize_register'));
    }
    
    /**
     * Setup theme
     */
    public function setup_theme() {
        // Theme is now installed separately via WP-CLI from terpedia-theme directory
        // No automatic theme activation - user should install theme manually
        error_log('Terpedia Plugin: Theme should be installed from terpedia-theme directory using WP-CLI');
    }
    
    /**
     * Register plugin theme directory
     */
    public function register_plugin_theme() {
        // Theme directory no longer in plugin - theme is separate
        // Users should install terpedia-theme via WP-CLI
        return;
    }
    
    /**
     * Add plugin theme directory to theme root
     */
    public function add_plugin_theme_directory($theme_root) {
        $plugin_theme_dir = TERPEDIA_REPLIT_PLUGIN_PATH . 'theme/';
        
        if (is_dir($plugin_theme_dir)) {
            return $plugin_theme_dir;
        }
        
        return $theme_root;
    }
    
    /**
     * Add plugin theme directory URI
     */
    public function add_plugin_theme_directory_uri($theme_root_uri) {
        $plugin_theme_dir = TERPEDIA_REPLIT_PLUGIN_PATH . 'theme/';
        
        if (is_dir($plugin_theme_dir)) {
            return TERPEDIA_REPLIT_PLUGIN_URL . 'theme/';
        }
        
        return $theme_root_uri;
    }
    
    /**
     * Maybe activate plugin theme
     */
    private function maybe_activate_plugin_theme() {
        $plugin_theme_dir = TERPEDIA_REPLIT_PLUGIN_PATH . 'theme/';
        $style_css = $plugin_theme_dir . 'style.css';
        
        if (file_exists($style_css)) {
            // Read theme header information
            $theme_data = get_file_data($style_css, array(
                'Name' => 'Theme Name',
                'Version' => 'Version',
                'Description' => 'Description',
                'Author' => 'Author',
            ));
            
            if (!empty($theme_data['Name'])) {
                // Theme is valid, register it
                add_filter('pre_option_stylesheet', array($this, 'set_plugin_theme_stylesheet'));
                add_filter('pre_option_template', array($this, 'set_plugin_theme_template'));
            }
        }
    }
    
    /**
     * Set plugin theme as stylesheet
     */
    public function set_plugin_theme_stylesheet($stylesheet) {
        return 'terpedia-scientific';
    }
    
    /**
     * Set plugin theme as template
     */
    public function set_plugin_theme_template($template) {
        return 'terpedia-scientific';
    }
    
    /**
     * Theme setup
     */
    public function theme_setup() {
        // Add theme support
        add_theme_support('post-thumbnails');
        add_theme_support('custom-logo');
        add_theme_support('title-tag');
        add_theme_support('html5', array(
            'search-form',
            'comment-form',
            'comment-list',
            'gallery',
            'caption',
        ));
        
        // Register navigation menus
        register_nav_menus(array(
            'primary' => __('Primary Menu', 'terpedia-replit'),
            'footer' => __('Footer Menu', 'terpedia-replit'),
        ));
        
        // Add custom image sizes
        add_image_size('terpene-featured', 800, 400, true);
        add_image_size('research-thumbnail', 300, 200, true);
        add_image_size('agent-avatar', 150, 150, true);
    }
    
    /**
     * Enqueue theme assets
     */
    public function enqueue_theme_assets() {
        $theme_url = TERPEDIA_REPLIT_PLUGIN_URL . 'theme/';
        $version = TERPEDIA_REPLIT_VERSION;
        
        // Enqueue main theme stylesheet
        wp_enqueue_style(
            'terpedia-scientific-style',
            $theme_url . 'style.css',
            array(),
            $version
        );
        
        // Enqueue theme JavaScript
        wp_enqueue_script(
            'terpedia-scientific-script',
            $theme_url . 'assets/js/theme.js',
            array('jquery'),
            $version,
            true
        );
        
        // Localize script for AJAX
        wp_localize_script('terpedia-scientific-script', 'terpedia_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('terpedia_nonce'),
            'plugin_url' => TERPEDIA_REPLIT_PLUGIN_URL,
        ));
        
        // Add custom CSS for color schemes
        $this->add_dynamic_css();
    }
    
    /**
     * Add dynamic CSS based on customizer options
     */
    private function add_dynamic_css() {
        $color_scheme = get_theme_mod('terpedia_color_scheme', 'default');
        
        $colors = array(
            'default' => array('primary' => '#667eea', 'secondary' => '#764ba2'),
            'green' => array('primary' => '#10b981', 'secondary' => '#059669'),
            'purple' => array('primary' => '#8b5cf6', 'secondary' => '#7c3aed'),
            'orange' => array('primary' => '#f59e0b', 'secondary' => '#d97706'),
        );
        
        if (isset($colors[$color_scheme])) {
            $custom_css = "
                :root {
                    --primary-color: {$colors[$color_scheme]['primary']};
                    --secondary-color: {$colors[$color_scheme]['secondary']};
                }
                
                body {
                    background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
                }
                
                .terpene-header {
                    background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
                }
                
                h1 {
                    border-bottom-color: var(--primary-color);
                }
                
                h3 {
                    color: var(--primary-color);
                }
                
                .scientific-keyword {
                    background: linear-gradient(120deg, rgba(" . $this->hex_to_rgb($colors[$color_scheme]['primary']) . ", 0.2) 0%, rgba(" . $this->hex_to_rgb($colors[$color_scheme]['primary']) . ", 0.1) 100%);
                }
            ";
            
            wp_add_inline_style('terpedia-scientific-style', $custom_css);
        }
    }
    
    /**
     * Convert hex color to RGB
     */
    private function hex_to_rgb($hex) {
        $hex = str_replace('#', '', $hex);
        $length = strlen($hex);
        
        if ($length == 6) {
            return implode(', ', array(
                hexdec(substr($hex, 0, 2)),
                hexdec(substr($hex, 2, 2)),
                hexdec(substr($hex, 4, 2))
            ));
        }
        
        return '103, 126, 234'; // Default blue
    }
    
    /**
     * Add customizer options
     */
    public function customize_register($wp_customize) {
        // Add Scientific Theme Section
        $wp_customize->add_section('terpedia_scientific_options', array(
            'title' => __('Scientific Theme Options', 'terpedia-replit'),
            'priority' => 30,
        ));
        
        // Add Color Scheme Option
        $wp_customize->add_setting('terpedia_color_scheme', array(
            'default' => 'default',
            'sanitize_callback' => 'sanitize_text_field',
        ));
        
        $wp_customize->add_control('terpedia_color_scheme', array(
            'label' => __('Color Scheme', 'terpedia-replit'),
            'section' => 'terpedia_scientific_options',
            'type' => 'select',
            'choices' => array(
                'default' => __('Default Blue', 'terpedia-replit'),
                'green' => __('Scientific Green', 'terpedia-replit'),
                'purple' => __('Research Purple', 'terpedia-replit'),
                'orange' => __('Molecular Orange', 'terpedia-replit'),
            ),
        ));
        
        // Add Custom Footer Text
        $wp_customize->add_setting('terpedia_footer_text', array(
            'default' => '',
            'sanitize_callback' => 'sanitize_text_field',
        ));
        
        $wp_customize->add_control('terpedia_footer_text', array(
            'label' => __('Custom Footer Text', 'terpedia-replit'),
            'section' => 'terpedia_scientific_options',
            'type' => 'text',
        ));
        
        // Add Logo Upload
        $wp_customize->add_setting('terpedia_logo', array(
            'default' => '',
            'sanitize_callback' => 'esc_url_raw',
        ));
        
        $wp_customize->add_control(new WP_Customize_Image_Control($wp_customize, 'terpedia_logo', array(
            'label' => __('Terpedia Logo', 'terpedia-replit'),
            'section' => 'terpedia_scientific_options',
            'settings' => 'terpedia_logo',
        )));
        
        // Add Scientific Features Toggle
        $wp_customize->add_setting('terpedia_enable_molecular_display', array(
            'default' => true,
            'sanitize_callback' => 'wp_validate_boolean',
        ));
        
        $wp_customize->add_control('terpedia_enable_molecular_display', array(
            'label' => __('Enable Molecular Data Display', 'terpedia-replit'),
            'section' => 'terpedia_scientific_options',
            'type' => 'checkbox',
        ));
        
        // Add BuddyPress Integration Toggle
        $wp_customize->add_setting('terpedia_enable_buddypress_styling', array(
            'default' => true,
            'sanitize_callback' => 'wp_validate_boolean',
        ));
        
        $wp_customize->add_control('terpedia_enable_buddypress_styling', array(
            'label' => __('Enable BuddyPress Scientific Styling', 'terpedia-replit'),
            'section' => 'terpedia_scientific_options',
            'type' => 'checkbox',
        ));
    }
    
    /**
     * Get theme info
     */
    public function get_theme_info() {
        $theme_dir = TERPEDIA_REPLIT_PLUGIN_PATH . 'theme/';
        $style_css = $theme_dir . 'style.css';
        
        if (file_exists($style_css)) {
            return get_file_data($style_css, array(
                'Name' => 'Theme Name',
                'Version' => 'Version',
                'Description' => 'Description',
                'Author' => 'Author',
            ));
        }
        
        return array();
    }
    
    /**
     * Check if theme is active
     */
    public function is_theme_active() {
        $current_theme = get_option('stylesheet');
        return ($current_theme === 'terpedia-scientific');
    }
    
    /**
     * Activate plugin theme
     */
    public function activate_theme() {
        $theme_dir = TERPEDIA_REPLIT_PLUGIN_PATH . 'theme/';
        
        if (is_dir($theme_dir)) {
            switch_theme('terpedia-scientific');
            return true;
        }
        
        return false;
    }
}

// Initialize theme integration
new Terpedia_Theme_Integration();