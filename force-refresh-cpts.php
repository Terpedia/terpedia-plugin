<?php
/**
 * Force refresh CPT registrations and clear caches
 * Run this once to ensure CPT menu changes take effect
 */

// Force WordPress to re-register all post types
add_action('init', function() {
    // Clear any cached post type data
    global $wp_post_types;
    
    // Force re-registration of our CPTs
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
    
    // Flush rewrite rules to ensure menu changes take effect
    flush_rewrite_rules();
    
    // Clear any object cache
    if (function_exists('wp_cache_flush')) {
        wp_cache_flush();
    }
    
    echo "✅ CPT registrations refreshed and caches cleared";
}, 1);

