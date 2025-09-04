<?php
/**
 * Site Setup for Terpedia
 * This file sets up the site title, description, and other basic settings
 */

if (!defined('ABSPATH')) {
    exit;
}

class Terpedia_Site_Setup {
    
    public function __construct() {
        add_action('init', array($this, 'setup_site_options'));
    }
    
    public function setup_site_options() {
        // Set site title
        if (get_option('blogname') !== 'Terpedia') {
            update_option('blogname', 'Terpedia');
        }
        
        // Set site description
        if (get_option('blogdescription') !== 'The Terpene Encyclopedia') {
            update_option('blogdescription', 'The Terpene Encyclopedia');
        }
        
        // Set site URL if not already set
        if (!get_option('home')) {
            update_option('home', 'https://terpedia.com');
        }
        
        if (!get_option('siteurl')) {
            update_option('siteurl', 'https://terpedia.com');
        }
        
        // Set timezone
        if (get_option('timezone_string') !== 'America/Denver') {
            update_option('timezone_string', 'America/Denver');
        }
        
        // Set date format
        if (get_option('date_format') !== 'F j, Y') {
            update_option('date_format', 'F j, Y');
        }
        
        // Set time format
        if (get_option('time_format') !== 'g:i a') {
            update_option('time_format', 'g:i a');
        }
        
        // Set posts per page
        if (get_option('posts_per_page') !== '10') {
            update_option('posts_per_page', '10');
        }
    }
}

// Initialize the site setup
new Terpedia_Site_Setup();
