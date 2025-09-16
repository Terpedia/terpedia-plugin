<?php
/**
 * Force CPT Refresh - Add to main plugin file
 * This will create an admin page to force refresh CPT registrations
 */

if (!defined('ABSPATH')) {
    exit;
}

class Terpedia_Force_CPT_Refresh {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_page'));
        add_action('admin_init', array($this, 'handle_refresh_action'));
    }
    
    public function add_admin_page() {
        add_submenu_page(
            'terpedia-main',
            'Force CPT Refresh',
            'Force CPT Refresh',
            'manage_options',
            'terpedia-force-cpt-refresh',
            array($this, 'admin_page')
        );
    }
    
    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>Force CPT Refresh</h1>
            <p>This page will force refresh all CPT registrations to ensure menu changes take effect.</p>
            
            <form method="post" action="">
                <?php wp_nonce_field('terpedia_force_refresh', 'terpedia_refresh_nonce'); ?>
                <p>
                    <input type="submit" name="force_refresh" class="button button-primary" value="Force Refresh CPTs" />
                </p>
            </form>
            
            <?php if (isset($_POST['force_refresh']) && wp_verify_nonce($_POST['terpedia_refresh_nonce'], 'terpedia_force_refresh')): ?>
                <div class="notice notice-success">
                    <p><strong>CPT Refresh completed!</strong> Please refresh this page to see the updated menu.</p>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }
    
    public function handle_refresh_action() {
        if (isset($_POST['force_refresh']) && wp_verify_nonce($_POST['terpedia_refresh_nonce'], 'terpedia_force_refresh')) {
            // Force re-register all CPTs
            $this->force_reregister_cpts();
            
            // Flush rewrite rules
            flush_rewrite_rules();
            
            // Clear any caches
            if (function_exists('wp_cache_flush')) {
                wp_cache_flush();
            }
            
            // Redirect to prevent form resubmission
            wp_redirect(add_query_arg('refreshed', '1', admin_url('admin.php?page=terpedia-force-cpt-refresh')));
            exit;
        }
    }
    
    private function force_reregister_cpts() {
        // Force re-registration of all our CPTs
        if (class_exists('Terpedia_Enhanced_Terport_Editor')) {
            $terport_editor = new Terpedia_Enhanced_Terport_Editor();
            $terport_editor->init();
        }
        
        if (class_exists('Terpedia_Enhanced_Terproducts_System')) {
            $terproducts = new Terpedia_Enhanced_Terproducts_System();
            $terproducts->init();
        }
        
        if (class_exists('Terpedia_Enhanced_Rx_System')) {
            $rx = new Terpedia_Enhanced_Rx_System();
            $rx->init();
        }
        
        if (class_exists('Terpedia_Enhanced_Podcast_System')) {
            $podcast = new Terpedia_Enhanced_Podcast_System();
            $podcast->init();
        }
        
        if (class_exists('Terpedia_Newsletter_Automation')) {
            $newsletter = new Terpedia_Newsletter_Automation();
            $newsletter->init();
        }
    }
}

// Initialize the force refresh functionality
new Terpedia_Force_CPT_Refresh();
