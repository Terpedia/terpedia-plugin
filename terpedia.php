<?php
/**
 * Plugin Name: Terpedia
 * Description: Comprehensive terpene encyclopedia with AI agents and research tools
 * Version: 2.0.14
 * Author: Terpedia Team
 */

if (!defined('ABSPATH')) exit;

class TerpediaPlugin {
    
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }
    
    public function init() {
        $this->register_post_types();
        $this->register_shortcodes();
        $this->create_ai_agents();
        $this->register_routes();
        $this->register_api_routes();
        add_filter('the_content', array($this, 'process_text_content'), 10);
        
        // Include enhanced Rx system
        require_once plugin_dir_path(__FILE__) . 'includes/enhanced-rx-system.php';
        require_once plugin_dir_path(__FILE__) . "includes/site-setup.php";
        
        // Include newsletter system
        require_once plugin_dir_path(__FILE__) . 'includes/newsletter-template-manager.php';
        require_once plugin_dir_path(__FILE__) . 'includes/newsletter-automation.php';
        require_once plugin_dir_path(__FILE__) . 'includes/newsletter-data-sources.php';
        
        // Include enhanced podcast system
        require_once plugin_dir_path(__FILE__) . 'includes/enhanced-podcast-system.php';
        
        // Include enhanced terproducts system
        require_once plugin_dir_path(__FILE__) . 'includes/enhanced-terproducts-system.php';
        
        // Include dashboard widget
        require_once plugin_dir_path(__FILE__) . 'includes/terpedia-dashboard-widget.php';
        
        // Include RSS feed manager for agents
        require_once plugin_dir_path(__FILE__) . 'includes/agent-rss-feed-manager.php';
        
        // Include OpenRouter AI Integration System
        if (file_exists(plugin_dir_path(__FILE__) . 'includes/openrouter-api-handler.php')) {
            require_once plugin_dir_path(__FILE__) . 'includes/openrouter-api-handler.php';
        }
        if (file_exists(plugin_dir_path(__FILE__) . 'includes/openrouter-admin-settings.php')) {
            require_once plugin_dir_path(__FILE__) . 'includes/openrouter-admin-settings.php';
        }
        if (file_exists(plugin_dir_path(__FILE__) . 'includes/agent-dm-openrouter-integration.php')) {
            require_once plugin_dir_path(__FILE__) . 'includes/agent-dm-openrouter-integration.php';
        }
        
        // Include Enhanced Terport Editor
        if (file_exists(plugin_dir_path(__FILE__) . 'includes/enhanced-terport-editor.php')) {
            require_once plugin_dir_path(__FILE__) . 'includes/enhanced-terport-editor.php';
        }
        if (file_exists(plugin_dir_path(__FILE__) . 'includes/terport-openrouter-integration.php')) {
            require_once plugin_dir_path(__FILE__) . 'includes/terport-openrouter-integration.php';
        }
        if (file_exists(plugin_dir_path(__FILE__) . 'includes/terport-template-system.php')) {
            require_once plugin_dir_path(__FILE__) . 'includes/terport-template-system.php';
        }
        if (file_exists(plugin_dir_path(__FILE__) . 'includes/default-terport-templates.php')) {
            require_once plugin_dir_path(__FILE__) . 'includes/default-terport-templates.php';
        }
        
        // Initialize OpenRouter admin settings
        if (class_exists('TerpediaOpenRouterAdminSettings')) {
            new TerpediaOpenRouterAdminSettings();
        }
        
        // Add main Terpedia admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));
    }
    
    public function enqueue_assets() {
        wp_enqueue_style('terpedia-css', plugin_dir_url(__FILE__) . 'assets/terpedia.css', array(), '1.0.0');
        wp_enqueue_script('terpedia-js', plugin_dir_url(__FILE__) . 'assets/terpedia.js', array('jquery'), '1.0.0', true);
        
        wp_localize_script('terpedia-js', 'terpedia_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('terpedia_nonce')
        ));
    }
    
    public function register_post_types() {
        register_post_type('terpene', array(
            'labels' => array(
                'name' => 'Terpenes',
                'singular_name' => 'Terpene'
            ),
            'public' => true,
            'has_archive' => true,
            'supports' => array('title', 'editor', 'custom-fields'),
            'menu_icon' => 'dashicons-analytics'
        ));
        
        register_post_type('research', array(
            'labels' => array(
                'name' => 'Research',
                'singular_name' => 'Research'
            ),
            'public' => true,
            'has_archive' => true,
            'supports' => array('title', 'editor', 'custom-fields'),
            'menu_icon' => 'dashicons-book-alt'
        ));
        
        register_post_type('terpedia_podcast', array(
            'labels' => array(
                'name' => 'Podcasts',
                'singular_name' => 'Podcast',
                'add_new' => 'Add New Podcast',
                'add_new_item' => 'Add New Podcast Episode',
                'edit_item' => 'Edit Podcast Episode',
                'new_item' => 'New Podcast Episode',
                'view_item' => 'View Podcast Episode',
                'search_items' => 'Search Podcasts',
                'not_found' => 'No podcasts found',
                'not_found_in_trash' => 'No podcasts found in trash',
                'all_items' => 'All Podcasts',
                'menu_name' => 'Podcasts'
            ),
            'public' => true,
            'has_archive' => true,
            'supports' => array('title', 'editor', 'author', 'thumbnail', 'excerpt', 'comments', 'custom-fields'),
            'menu_icon' => 'dashicons-microphone',
            'menu_position' => 25,
            'show_in_rest' => true,
            'capability_type' => 'post',
            'hierarchical' => false,
            'rewrite' => array('slug' => 'podcast'),
            'show_in_menu' => true
        ));
        
        register_post_type('newsletter', array(
            'labels' => array(
                'name' => 'Newsletters',
                'singular_name' => 'Newsletter'
            ),
            'public' => true,
            'has_archive' => true,
            'supports' => array('title', 'editor', 'custom-fields'),
            'menu_icon' => 'dashicons-email-alt'
        ));
        
        register_post_type('terport', array(
            'labels' => array(
                'name' => 'Terports',
                'singular_name' => 'Terport'
            ),
            'public' => true,
            'has_archive' => true,
            'supports' => array('title', 'editor', 'custom-fields'),
            'menu_icon' => 'dashicons-media-document'
        ));
        
        register_post_type('use', array(
            'labels' => array(
                'name' => 'Use Cases',
                'singular_name' => 'Use Case'
            ),
            'public' => true,
            'has_archive' => true,
            'supports' => array('title', 'editor', 'custom-fields'),
            'menu_icon' => 'dashicons-businessman'
        ));
        
        register_post_type('case', array(
            'labels' => array(
                'name' => 'Patient Cases',
                'singular_name' => 'Patient Case'
            ),
            'public' => true,
            'has_archive' => true,
            'supports' => array('title', 'editor', 'custom-fields'),
            'menu_icon' => 'dashicons-heart'
        ));
    }
    
    public function register_shortcodes() {
        add_shortcode('terpedia_search', array($this, 'search_shortcode'));
        add_shortcode('terpedia_agents', array($this, 'agents_shortcode'));
        add_shortcode('terpedia_recent_research', array($this, 'research_shortcode'));
        add_shortcode('terpedia_featured_terpenes', array($this, 'terpenes_shortcode'));
        add_shortcode('terpedia_terpenes', array($this, 'terpenes_shortcode')); // Alias
        add_shortcode('terpedia_podcast', array($this, 'podcast_shortcode'));
        add_shortcode('terpedia_newsletter', array($this, 'newsletter_shortcode'));
        add_shortcode('terpedia_newsletter_signup', array($this, 'newsletter_signup_shortcode'));
        add_shortcode('terpedia_terports', array($this, 'terports_shortcode'));
    }
    
    public function search_shortcode($atts) {
        return '<div class="terpedia-search-widget">
            <form class="terpedia-search-form" method="get">
                <input type="text" name="s" placeholder="Search terpenes, research..." value="' . get_search_query() . '">
                <button type="submit">🔍 Search</button>
            </form>
        </div>';
    }
    
    public function agents_shortcode($atts) {
        $agents = $this->get_ai_agents();
        $output = '<div class="terpedia-agents-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin: 20px 0;">';
        
        foreach ($agents as $agent) {
            $output .= '<div class="agent-card" style="border: 1px solid #ddd; border-radius: 8px; padding: 20px; background: #f9f9f9; text-align: center;">
                <div class="agent-avatar" style="font-size: 48px; margin-bottom: 10px;">🤖</div>
                <h3 style="margin: 10px 0; color: #2c5aa0;">' . esc_html($agent['name']) . '</h3>
                <p style="color: #666; margin: 10px 0;">' . esc_html($agent['specialty']) . '</p>
                <button class="consult-agent" data-agent="' . esc_attr($agent['id']) . '" style="background: #2c5aa0; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer;">Consult</button>
            </div>';
        }
        
        $output .= '</div>';
        return $output;
    }
    
    public function research_shortcode($atts) {
        $research = get_posts(array(
            'post_type' => 'research',
            'posts_per_page' => 5
        ));
        
        $output = '<div class="terpedia-recent-research" style="margin: 20px 0;"><h3 style="color: #2c5aa0;">📚 Recent Research</h3>';
        
        if ($research) {
            $output .= '<ul style="list-style: none; padding: 0;">';
            foreach ($research as $post) {
                $output .= '<li style="margin: 10px 0; padding: 10px; border-left: 4px solid #2c5aa0; background: #f9f9f9;"><a href="' . get_permalink($post->ID) . '" style="text-decoration: none; color: #2c5aa0; font-weight: bold;">' . esc_html($post->post_title) . '</a><br><small style="color: #666;">' . wp_trim_words($post->post_content, 15) . '</small></li>';
            }
            $output .= '</ul>';
        } else {
            $output .= '<p>No research articles found.</p>';
        }
        
        $output .= '</div>';
        return $output;
    }
    
    public function terpenes_shortcode($atts) {
        $terpenes = get_posts(array(
            'post_type' => 'terpene',
            'posts_per_page' => 6
        ));
        
        $output = '<div class="terpedia-featured-terpenes" style="margin: 20px 0;"><h3 style="color: #2c5aa0;">🧬 Featured Terpenes</h3><div class="terpenes-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin-top: 15px;">';
        
        if ($terpenes) {
            foreach ($terpenes as $post) {
                $formula = get_post_meta($post->ID, 'molecular_formula', true);
                $effects = get_post_meta($post->ID, 'effects', true);
                
                $output .= '<div class="terpene-card" style="border: 1px solid #ddd; border-radius: 8px; padding: 20px; background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);">
                    <h4 style="margin-top: 0; color: #2c5aa0;"><a href="' . get_permalink($post->ID) . '" style="text-decoration: none; color: inherit;">' . esc_html($post->post_title) . '</a></h4>
                    <p style="color: #666; margin: 10px 0;">' . wp_trim_words($post->post_content, 15) . '</p>';
                
                if ($formula) {
                    $output .= '<div style="font-size: 14px; color: #d63384; margin: 5px 0;"><strong>Formula:</strong> ' . esc_html($formula) . '</div>';
                }
                if ($effects) {
                    $output .= '<div style="font-size: 14px; color: #198754; margin: 5px 0;"><strong>Effects:</strong> ' . esc_html($effects) . '</div>';
                }
                
                $output .= '</div>';
            }
        } else {
            // Fallback content
            $default_terpenes = array(
                array('name' => 'Myrcene', 'desc' => 'Sedative terpene found in cannabis and hops.', 'formula' => 'C10H16', 'effects' => 'Sedative, muscle relaxant'),
                array('name' => 'Limonene', 'desc' => 'Citrus terpene with mood-elevating properties.', 'formula' => 'C10H16', 'effects' => 'Anti-anxiety, mood enhancement'),
                array('name' => 'Pinene', 'desc' => 'Pine-scented terpene that may improve focus.', 'formula' => 'C10H16', 'effects' => 'Focus enhancement, alertness')
            );
            
            foreach ($default_terpenes as $terpene) {
                $output .= '<div class="terpene-card" style="border: 1px solid #ddd; border-radius: 8px; padding: 20px; background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);">
                    <h4 style="margin-top: 0; color: #2c5aa0;">' . esc_html($terpene['name']) . '</h4>
                    <p style="color: #666; margin: 10px 0;">' . esc_html($terpene['desc']) . '</p>
                    <div style="font-size: 14px; color: #d63384; margin: 5px 0;"><strong>Formula:</strong> ' . esc_html($terpene['formula']) . '</div>
                    <div style="font-size: 14px; color: #198754; margin: 5px 0;"><strong>Effects:</strong> ' . esc_html($terpene['effects']) . '</div>
                </div>';
            }
        }
        
        $output .= '</div></div>';
        return $output;
    }
    
    public function podcast_shortcode($atts) {
        $podcasts = get_posts(array(
            'post_type' => 'terpedia_podcast',
            'posts_per_page' => 6
        ));
        
        $output = '<div class="terpedia-podcasts" style="margin: 20px 0;"><h3 style="color: #2c5aa0;">🎙️ Terpedia Podcasts</h3><div class="podcast-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-top: 15px;">';
        
        if ($podcasts) {
            foreach ($podcasts as $post) {
                $episode_number = get_post_meta($post->ID, 'episode_number', true) ?: '1';
                $duration = get_post_meta($post->ID, 'duration', true) ?: '30 min';
                $host = get_post_meta($post->ID, 'host', true) ?: 'TerpeneQueen';
                
                $output .= '<div class="podcast-card" style="border: 1px solid #ddd; border-radius: 8px; padding: 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                        <span style="background: rgba(255,255,255,0.2); padding: 4px 8px; border-radius: 4px; font-size: 12px;">Episode ' . esc_html($episode_number) . '</span>
                        <span style="font-size: 12px; opacity: 0.8;">' . esc_html($duration) . '</span>
                    </div>
                    <h4 style="margin: 10px 0; color: white;"><a href="' . get_permalink($post->ID) . '" style="color: white; text-decoration: none;">' . esc_html($post->post_title) . '</a></h4>
                    <p style="opacity: 0.9; font-size: 14px; margin: 10px 0;">' . wp_trim_words($post->post_content, 20) . '</p>
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 15px;">
                        <span style="font-size: 12px;">🎧 Hosted by ' . esc_html($host) . '</span>
                        <button style="background: rgba(255,255,255,0.2); color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer;">▶️ Play</button>
                    </div>
                </div>';
            }
        } else {
            // Default podcast episodes
            $default_episodes = array(
                array('title' => 'The Science of Myrcene', 'host' => 'TerpeneQueen', 'episode' => '42', 'duration' => '28 min', 'desc' => 'Deep dive into myrcene\'s sedative properties and therapeutic applications.'),
                array('title' => 'Limonene and Mental Health', 'host' => 'Dr. Elena Molecular', 'episode' => '43', 'duration' => '31 min', 'desc' => 'Exploring limonene\'s anti-anxiety effects and mood-enhancing capabilities.'),
                array('title' => 'Cannabis Terpenes: Beyond THC', 'host' => 'TerpeneQueen', 'episode' => '44', 'duration' => '45 min', 'desc' => 'Understanding the entourage effect and terpene synergies in cannabis.')
            );
            
            foreach ($default_episodes as $episode) {
                $output .= '<div class="podcast-card" style="border: 1px solid #ddd; border-radius: 8px; padding: 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                        <span style="background: rgba(255,255,255,0.2); padding: 4px 8px; border-radius: 4px; font-size: 12px;">Episode ' . esc_html($episode['episode']) . '</span>
                        <span style="font-size: 12px; opacity: 0.8;">' . esc_html($episode['duration']) . '</span>
                    </div>
                    <h4 style="margin: 10px 0; color: white;">' . esc_html($episode['title']) . '</h4>
                    <p style="opacity: 0.9; font-size: 14px; margin: 10px 0;">' . esc_html($episode['desc']) . '</p>
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 15px;">
                        <span style="font-size: 12px;">🎧 Hosted by ' . esc_html($episode['host']) . '</span>
                        <button style="background: rgba(255,255,255,0.2); color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer;">▶️ Play</button>
                    </div>
                </div>';
            }
        }
        
        $output .= '</div></div>';
        return $output;
    }
    
    /**
     * Newsletter shortcode
     */
    public function newsletter_shortcode($atts) {
        $atts = shortcode_atts(array(
            'id' => '',
            'limit' => 5,
            'template' => ''
        ), $atts);
        
        $args = array(
            'post_type' => 'terpedia_newsletter',
            'posts_per_page' => intval($atts['limit']),
            'post_status' => 'publish'
        );
        
        if ($atts['id']) {
            $args['p'] = intval($atts['id']);
        }
        
        if ($atts['template']) {
            $args['meta_query'] = array(
                array(
                    'key' => '_newsletter_template_id',
                    'value' => intval($atts['template']),
                    'compare' => '='
                )
            );
        }
        
        $newsletters = get_posts($args);
        
        if (empty($newsletters)) {
            return '<div class="terpedia-newsletter-widget"><p>No newsletters found.</p></div>';
        }
        
        $output = '<div class="terpedia-newsletter-widget" style="margin: 20px 0;">';
        $output .= '<h3 style="color: #2c5aa0;">📧 Terpedia Newsletters</h3>';
        $output .= '<div class="newsletter-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-top: 15px;">';
        
        foreach ($newsletters as $newsletter) {
            $template_id = get_post_meta($newsletter->ID, '_newsletter_template_id', true);
            $generated_date = get_post_meta($newsletter->ID, '_newsletter_generated_date', true);
            
            $output .= '<div class="newsletter-card" style="border: 1px solid #ddd; border-radius: 8px; padding: 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">';
            $output .= '<h4 style="margin: 0 0 10px 0; color: white;"><a href="' . get_permalink($newsletter->ID) . '" style="color: white; text-decoration: none;">' . esc_html($newsletter->post_title) . '</a></h4>';
            $output .= '<p style="opacity: 0.9; font-size: 14px; margin: 10px 0;">' . wp_trim_words($newsletter->post_content, 20) . '</p>';
            $output .= '<div style="display: flex; justify-content: space-between; align-items: center; margin-top: 15px;">';
            $output .= '<span style="font-size: 12px;">📅 ' . get_the_date('M j, Y', $newsletter->ID) . '</span>';
            $output .= '<a href="' . get_permalink($newsletter->ID) . '" style="background: rgba(255,255,255,0.2); color: white; text-decoration: none; padding: 6px 12px; border-radius: 4px; font-size: 12px;">Read More</a>';
            $output .= '</div>';
            $output .= '</div>';
        }
        
        $output .= '</div></div>';
        return $output;
    }
    
    /**
     * Newsletter signup shortcode
     */
    public function newsletter_signup_shortcode($atts) {
        $atts = shortcode_atts(array(
            'title' => 'Subscribe to Terpedia Newsletter',
            'description' => 'Get the latest terpene research, industry news, and insights delivered to your inbox.',
            'button_text' => 'Subscribe Now'
        ), $atts);
        
        $output = '<div class="terpedia-newsletter-signup" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; border-radius: 8px; text-align: center; margin: 20px 0;">';
        $output .= '<h3 style="margin: 0 0 15px 0; color: white;">' . esc_html($atts['title']) . '</h3>';
        $output .= '<p style="margin: 0 0 20px 0; opacity: 0.9;">' . esc_html($atts['description']) . '</p>';
        $output .= '<form id="terpedia-newsletter-signup-form" style="display: flex; gap: 10px; justify-content: center; flex-wrap: wrap;">';
        $output .= '<input type="email" name="email" placeholder="Enter your email address" required style="padding: 12px 16px; border: none; border-radius: 4px; min-width: 250px; font-size: 14px;">';
        $output .= '<button type="submit" style="background: rgba(255,255,255,0.2); color: white; border: 2px solid white; padding: 12px 24px; border-radius: 4px; cursor: pointer; font-size: 14px; font-weight: bold;">' . esc_html($atts['button_text']) . '</button>';
        $output .= '</form>';
        $output .= '<div id="newsletter-signup-message" style="margin-top: 15px; font-size: 14px;"></div>';
        $output .= '</div>';
        
        // Add JavaScript for form handling
        $output .= '<script>
        jQuery(document).ready(function($) {
            $("#terpedia-newsletter-signup-form").submit(function(e) {
                e.preventDefault();
                var email = $(this).find("input[name=email]").val();
                var button = $(this).find("button[type=submit]");
                var message = $("#newsletter-signup-message");
                
                button.prop("disabled", true).text("Subscribing...");
                
                $.ajax({
                    url: "' . admin_url('admin-ajax.php') . '",
                    type: "POST",
                    data: {
                        action: "terpedia_newsletter_signup",
                        email: email,
                        nonce: "' . wp_create_nonce('terpedia_newsletter_signup') . '"
                    },
                    success: function(response) {
                        if (response.success) {
                            message.html("<span style=\"color: #90EE90;\">✅ " + response.data + "</span>");
                            $("#terpedia-newsletter-signup-form")[0].reset();
                        } else {
                            message.html("<span style=\"color: #FFB6C1;\">❌ " + response.data + "</span>");
                        }
                    },
                    error: function() {
                        message.html("<span style=\"color: #FFB6C1;\">❌ An error occurred. Please try again.</span>");
                    },
                    complete: function() {
                        button.prop("disabled", false).text("' . esc_js($atts['button_text']) . '");
                    }
                });
            });
        });
        </script>';
        
        return $output;
    }
    
    public function terports_shortcode($atts) {
        $terports = get_posts(array(
            'post_type' => 'terport',
            'posts_per_page' => 6
        ));
        
        $output = '<div class="terpedia-terports" style="margin: 20px 0;"><h3 style="color: #2c5aa0;">📋 Research Terports</h3><div class="terports-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin-top: 15px;">';
        
        if ($terports) {
            foreach ($terports as $post) {
                $research_topic = get_post_meta($post->ID, 'research_topic', true) ?: 'General Research';
                $completion_date = get_post_meta($post->ID, 'completion_date', true) ?: date('Y-m-d');
                $status = get_post_meta($post->ID, 'status', true) ?: 'completed';
                
                $status_colors = array(
                    'completed' => '#00b894',
                    'in_progress' => '#fdcb6e', 
                    'draft' => '#74b9ff'
                );
                $status_color = $status_colors[$status] ?? '#ddd';
                
                $output .= '<div class="terport-card" style="border: 1px solid #ddd; border-radius: 8px; padding: 20px; background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                        <span style="background: ' . $status_color . '; color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px; text-transform: capitalize;">' . esc_html(str_replace('_', ' ', $status)) . '</span>
                        <span style="font-size: 12px; color: #6c757d;">' . date('M j, Y', strtotime($completion_date)) . '</span>
                    </div>
                    <h4 style="margin: 10px 0; color: #2c5aa0;"><a href="' . get_permalink($post->ID) . '" style="color: #2c5aa0; text-decoration: none;">' . esc_html($post->post_title) . '</a></h4>
                    <p style="color: #6c757d; font-size: 14px; margin: 10px 0; font-style: italic;">Topic: ' . esc_html($research_topic) . '</p>
                    <p style="color: #495057; font-size: 14px; margin: 10px 0;">' . wp_trim_words($post->post_content, 15) . '</p>
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 15px;">
                        <span style="font-size: 12px; color: #6c757d;">🔬 AI Generated Report</span>
                        <button style="background: #2c5aa0; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer;">📄 View Report</button>
                    </div>
                </div>';
            }
        } else {
            // Default terports
            $default_terports = array(
                array('title' => 'Myrcene Therapeutic Applications Analysis', 'topic' => 'Myrcene Research', 'status' => 'completed', 'date' => '2024-12-10', 'desc' => 'Comprehensive analysis of myrcene\'s sedative effects and therapeutic potential.'),
                array('title' => 'Cannabis Terpene Interaction Study', 'topic' => 'Entourage Effect', 'status' => 'completed', 'date' => '2024-12-05', 'desc' => 'Investigation of synergistic effects between terpenes and cannabinoids.'),
                array('title' => 'Limonene Anti-Anxiety Mechanisms', 'topic' => 'Limonene Research', 'status' => 'in_progress', 'date' => '2024-12-15', 'desc' => 'Current research on limonene\'s impact on GABA and serotonin pathways.')
            );
            
            foreach ($default_terports as $terport) {
                $status_colors = array(
                    'completed' => '#00b894',
                    'in_progress' => '#fdcb6e', 
                    'draft' => '#74b9ff'
                );
                $status_color = $status_colors[$terport['status']] ?? '#ddd';
                
                $output .= '<div class="terport-card" style="border: 1px solid #ddd; border-radius: 8px; padding: 20px; background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                        <span style="background: ' . $status_color . '; color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px; text-transform: capitalize;">' . esc_html(str_replace('_', ' ', $terport['status'])) . '</span>
                        <span style="font-size: 12px; color: #6c757d;">' . date('M j, Y', strtotime($terport['date'])) . '</span>
                    </div>
                    <h4 style="margin: 10px 0; color: #2c5aa0;">' . esc_html($terport['title']) . '</h4>
                    <p style="color: #6c757d; font-size: 14px; margin: 10px 0; font-style: italic;">Topic: ' . esc_html($terport['topic']) . '</p>
                    <p style="color: #495057; font-size: 14px; margin: 10px 0;">' . esc_html($terport['desc']) . '</p>
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 15px;">
                        <span style="font-size: 12px; color: #6c757d;">🔬 AI Generated Report</span>
                        <button style="background: #2c5aa0; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer;">📄 View Report</button>
                    </div>
                </div>';
            }
        }
        
        $output .= '</div></div>';
        return $output;
    }
    
    public function get_ai_agents() {
        return array(
            array('id' => 'dr_molecular', 'name' => 'Dr. Elena Molecular', 'username' => 'dr_molecular', 'email' => 'molecular@terpedia.com', 'specialty' => 'Molecular Structure Analysis'),
            array('id' => 'prof_pharmakin', 'name' => 'Prof. Pharmakin', 'username' => 'prof_pharmakin', 'email' => 'pharmakin@terpedia.com', 'specialty' => 'Pharmacokinetics & Drug Interactions'),
            array('id' => 'scholar_citeswell', 'name' => 'Scholar Citeswell', 'username' => 'scholar_citeswell', 'email' => 'scholar@terpedia.com', 'specialty' => 'Research Literature Analysis'),
            array('id' => 'dr_formulator', 'name' => 'Dr. Rebecca Chen', 'username' => 'dr_formulator', 'email' => 'formulator@terpedia.com', 'specialty' => 'Terpene Formulation Development'),
            array('id' => 'dr_paws', 'name' => 'Dr. Paws Prescription', 'username' => 'dr_paws', 'email' => 'veterinary@terpedia.com', 'specialty' => 'Veterinary Terpene Applications')
        );
    }
    
    public function create_ai_agents() {
        $agents = $this->get_ai_agents();
        
        foreach ($agents as $agent) {
            $user = get_user_by('login', $agent['username']);
            
            if (!$user) {
                // Create new agent user
                $user_id = wp_create_user($agent['username'], wp_generate_password(), $agent['email']);
                if (!is_wp_error($user_id)) {
                    wp_update_user(array(
                        'ID' => $user_id,
                        'display_name' => $agent['name'],
                        'description' => $agent['specialty']
                    ));
                    
                    update_user_meta($user_id, 'terpedia_agent', true);
                    update_user_meta($user_id, 'agent_specialty', $agent['specialty']);
                    update_user_meta($user_id, 'terpedia_agent_type', 'expert');
                    update_user_meta($user_id, 'terpedia_post_frequency', 'daily');
                    update_user_meta($user_id, 'terpedia_agent_id', $agent['id']);
                    update_user_meta($user_id, 'terpedia_agent_openrouter_enabled', true);
                }
            } else {
                // Update existing agent with DM integration metadata
                $user_id = $user->ID;
                update_user_meta($user_id, 'terpedia_agent', true);
                update_user_meta($user_id, 'agent_specialty', $agent['specialty']);
                update_user_meta($user_id, 'terpedia_agent_type', 'expert');
                update_user_meta($user_id, 'terpedia_agent_id', $agent['id']);
                update_user_meta($user_id, 'terpedia_agent_openrouter_enabled', true);
            }
        }
    }
    
    public function activate() {
        $this->register_post_types();
        flush_rewrite_rules();
        $this->create_sample_content();
        
        // Initialize RSS feed manager to create tables
        if (class_exists('TerpediaAgentRSSManager')) {
            $rss_manager = new TerpediaAgentRSSManager();
            // This will trigger the table creation
        }
    }
    
    public function create_sample_content() {
        // Only create sample content if none exists
        $existing_terpenes = get_posts(array('post_type' => 'terpene', 'posts_per_page' => 1));
        if (!empty($existing_terpenes)) {
            return; // Content already exists
        }
        
        // Create sample terpenes
        $terpenes = array(
            array('title' => 'Myrcene', 'content' => 'Myrcene is a monoterpene found in cannabis, hops, and mangoes. Known for its sedative properties and ability to enhance muscle relaxation.', 'formula' => 'C10H16', 'effects' => 'Sedative, Muscle Relaxant', 'boiling_point' => '167°C'),
            array('title' => 'Limonene', 'content' => 'Limonene is the second most abundant terpene in nature, found in citrus fruits. It has powerful mood-elevating and anti-anxiety properties.', 'formula' => 'C10H16', 'effects' => 'Anti-anxiety, Mood Enhancement', 'boiling_point' => '176°C'),
            array('title' => 'Pinene', 'content' => 'Alpha-pinene is found in pine trees, rosemary, and basil. It may help improve focus, memory retention, and respiratory function.', 'formula' => 'C10H16', 'effects' => 'Focus Enhancement, Memory, Bronchodilator', 'boiling_point' => '156°C')
        );
        
        foreach ($terpenes as $terpene) {
            $post_id = wp_insert_post(array(
                'post_title' => $terpene['title'],
                'post_content' => $terpene['content'],
                'post_type' => 'terpene',
                'post_status' => 'publish'
            ));
            
            if ($post_id) {
                update_post_meta($post_id, 'molecular_formula', $terpene['formula']);
                update_post_meta($post_id, 'effects', $terpene['effects']);
                update_post_meta($post_id, 'boiling_point', $terpene['boiling_point']);
                update_post_meta($post_id, 'featured', 'yes');
            }
        }
        
        // Create sample research
        $research = array(
            array('title' => 'Myrcene and Sleep Quality: Clinical Study 2024', 'content' => 'A comprehensive clinical trial examining myrcene\'s effects on sleep quality in 200 participants over 8 weeks showed significant improvements in sleep onset time and REM sleep duration.', 'authors' => 'Dr. Sarah Chen, Dr. Michael Torres', 'journal' => 'Journal of Cannabis Research'),
            array('title' => 'Limonene Anti-Anxiety Effects in Laboratory Studies', 'content' => 'Laboratory studies demonstrate that limonene reduces anxiety-like behaviors in animal models through modulation of GABA and serotonin pathways.', 'authors' => 'Dr. Elena Vasquez, Research Team', 'journal' => 'Phytotherapy Research')
        );
        
        foreach ($research as $article) {
            $post_id = wp_insert_post(array(
                'post_title' => $article['title'],
                'post_content' => $article['content'],
                'post_type' => 'research',
                'post_status' => 'publish'
            ));
            
            if ($post_id && isset($article['authors'])) {
                update_post_meta($post_id, 'authors', $article['authors']);
                update_post_meta($post_id, 'journal', $article['journal']);
                update_post_meta($post_id, 'publication_date', '2024-12-15');
            }
        }
    }
    

    
    // Register REST API routes  
    public function register_api_routes() {
        add_action('rest_api_init', function() {
            // Encyclopedia API
            register_rest_route('terpedia/v1', '/cyc/(?P<term>[a-zA-Z0-9_-]+)', array(
                'methods' => 'GET',
                'callback' => array($this, 'api_encyclopedia_entry'),
                'permission_callback' => '__return_true'
            ));
            
            register_rest_route('terpedia/v1', '/encyclopedia/search', array(
                'methods' => 'GET',
                'callback' => array($this, 'api_encyclopedia_search'),
                'permission_callback' => '__return_true'
            ));
            
            // Text processing API
            register_rest_route('terpedia/v1', '/keywords', array(
                'methods' => 'GET',
                'callback' => array($this, 'api_get_keywords'),
                'permission_callback' => '__return_true'
            ));
            
            register_rest_route('terpedia/v1', '/text/process', array(
                'methods' => 'POST',
                'callback' => array($this, 'api_process_text'),
                'permission_callback' => '__return_true'
            ));
        });
    }
    
    public function register_routes() {
        add_action('template_redirect', array($this, 'handle_custom_routes'), 1); // Earlier priority
        add_action('parse_request', array($this, 'handle_early_routes')); // Even earlier
    }
    
    // Handle routes that WordPress might interfere with
    public function handle_early_routes($wp) {
        $current_path = trim($_SERVER['REQUEST_URI'], '/');
        
        if (preg_match('/^cyc\/([a-zA-Z0-9_-]+)$/', $current_path, $matches)) {
            // Prevent WordPress from processing this further
            $wp->query_vars = array();
            $wp->request = '';
            
            // Set up our custom handling
            add_action('wp', function() use ($matches) {
                $this->render_encyclopedia_entry($matches[1]);
                exit;
            });
        }
    }
    
    // Direct URL matching approach for custom routes
    public function handle_custom_routes() {
        $current_path = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
        
        // Skip API routes - let WordPress REST API handle them
        if (strpos($current_path, 'wp-json/') === 0) {
            return;
        }
        
        // Debug logging for non-API routes
        error_log("Terpedia: Checking path: " . $current_path);
        
        // Encyclopedia routes (/cyc and /cyc/terpene_name)
        if ($current_path === 'cyc') {
            error_log("Terpedia: Rendering encyclopedia home");
            $this->render_encyclopedia_home();
            exit;
        } elseif (preg_match('/^cyc\/([a-zA-Z0-9_-]+)$/', $current_path, $matches)) {
            // Override WordPress redirects for encyclopedia entries
            remove_action('template_redirect', 'wp_redirect_admin_locations', 1000);
            remove_action('template_redirect', 'redirect_canonical');
            
            error_log("Terpedia: Rendering encyclopedia entry: " . $matches[1]);
            $this->render_encyclopedia_entry($matches[1]);
            exit;
        }
        
        // Specialized listing routes
        switch ($current_path) {
            case 'agents':
                error_log("Terpedia: Rendering agents page");
                $this->render_agents_page();
                exit;
            case 'tersona':
                error_log("Terpedia: Rendering tersona page");
                $this->render_tersona_page();
                exit;
            case 'terports':
                error_log("Terpedia: Rendering terports page");
                $this->render_terports_page();
                exit;
        }
    }
    
    // Text processing filter for content
    public function process_text_content($content) {
        // Process scientific keywords to link to encyclopedia
        $keywords = $this->get_scientific_keywords();
        foreach ($keywords as $keyword) {
            $content = preg_replace(
                '/\b(' . preg_quote($keyword, '/') . ')\b(?![^<]*>)/i',
                '<a href="/cyc/' . strtolower($keyword) . '" class="keyword-link" style="color: #22c55e; text-decoration: underline;">\1</a>',
                $content
            );
        }
        
        // Process URLs to make them clickable
        $content = preg_replace(
            '/(https?:\/\/[^\s<>"\']+)/i',
            '<a href="\1" class="url-link" style="color: #3b82f6; text-decoration: underline;" target="_blank">\1</a>',
            $content
        );
        
        // Process hashtags
        $content = preg_replace(
            '/#([a-zA-Z0-9_]+)/',
            '<a href="/search?q=%23\1" class="hashtag-link" style="color: #3b82f6; text-decoration: underline;">#\1</a>',
            $content
        );
        
        return $content;
    }
    
    // Encyclopedia rendering methods
    public function render_encyclopedia_home() {
        error_log("Terpedia: Starting encyclopedia home render");
        
        // Set proper headers and ensure WordPress template loading
        if (!headers_sent()) {
            status_header(200);
            header('Content-Type: text/html; charset=UTF-8');
        }
        
        // Load WordPress template functions if not available
        if (!function_exists('get_header')) {
            require_once(ABSPATH . 'wp-includes/general-template.php');
require_once plugin_dir_path(__FILE__) . "includes/site-setup.php";
        }
        
        get_header();
        echo '<div class="terpedia-encyclopedia-home" style="max-width: 1200px; margin: 0 auto; padding: 20px;">
            <h1 style="color: #2c5aa0; text-align: center; margin-bottom: 30px;">🧬 Terpedia Encyclopedia</h1>
            <p style="text-align: center; color: #666; font-size: 18px; margin-bottom: 40px;">Comprehensive scientific reference for terpenes and natural compounds</p>
            
            <div class="featured-entries" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                <div class="entry-card" style="border: 1px solid #ddd; border-radius: 8px; padding: 20px; background: #f9f9f9;">
                    <h3><a href="/cyc/myrcene" style="color: #2c5aa0; text-decoration: none;">Myrcene</a></h3>
                    <p>Sedative monoterpene found in cannabis, hops, and mangoes. Known for muscle relaxation effects.</p>
                </div>
                <div class="entry-card" style="border: 1px solid #ddd; border-radius: 8px; padding: 20px; background: #f9f9f9;">
                    <h3><a href="/cyc/limonene" style="color: #2c5aa0; text-decoration: none;">Limonene</a></h3>
                    <p>Common citrus terpene with anti-anxiety and mood-elevating properties.</p>
                </div>
                <div class="entry-card" style="border: 1px solid #ddd; border-radius: 8px; padding: 20px; background: #f9f9f9;">
                    <h3><a href="/cyc/pinene" style="color: #2c5aa0; text-decoration: none;">Pinene</a></h3>
                    <p>Pine-scented terpene that may improve focus and respiratory function.</p>
                </div>
            </div>
        </div>';
        get_footer();
        
        error_log("Terpedia: Encyclopedia home render complete");
    }
    
    public function render_encyclopedia_entry($term) {
        if (!headers_sent()) {
            status_header(200);
            header('Content-Type: text/html; charset=UTF-8');
        }
        
        $entry_data = $this->get_encyclopedia_data($term);
        
        get_header();
        echo '<div class="terpedia-encyclopedia-entry" style="max-width: 800px; margin: 0 auto; padding: 20px;">
            <h1 style="color: #2c5aa0; margin-bottom: 20px;">' . esc_html($entry_data['title']) . '</h1>
            
            <div class="entry-metadata" style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                <p><strong>Molecular Formula:</strong> ' . esc_html($entry_data['formula']) . '</p>
                <p><strong>Effects:</strong> ' . esc_html($entry_data['effects']) . '</p>
                <p><strong>Sources:</strong> ' . esc_html($entry_data['sources']) . '</p>
            </div>
            
            <div class="entry-content" style="line-height: 1.6;">
                ' . wp_kses_post($entry_data['content']) . '
            </div>
            
            <div class="entry-navigation" style="margin-top: 30px; text-align: center;">
                <a href="/cyc" style="background: #2c5aa0; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;">← Back to Encyclopedia</a>
            </div>
        </div>';
        get_footer();
    }
    
    // Specialized page renderers
    public function render_agents_page() {
        if (!headers_sent()) {
            status_header(200);
            header('Content-Type: text/html; charset=UTF-8');
        }
        
        get_header();
        echo '<div class="terpedia-agents-page" style="max-width: 1200px; margin: 0 auto; padding: 20px;">
            <h1 style="color: #2c5aa0; text-align: center; margin-bottom: 30px;">🤖 AI Expert Agents</h1>
            <p style="text-align: center; color: #666; margin-bottom: 40px;">Specialized AI agents for terpene research and analysis</p>
            ' . $this->agents_shortcode(array()) . '
        </div>';
        get_footer();
    }
    
    public function render_tersona_page() {
        if (!headers_sent()) {
            status_header(200);
            header('Content-Type: text/html; charset=UTF-8');
        }
        
        get_header();
        echo '<div class="terpedia-tersona-page" style="max-width: 1200px; margin: 0 auto; padding: 20px;">
            <h1 style="color: #2c5aa0; text-align: center; margin-bottom: 30px;">🧬 Terpene Personas</h1>
            <p style="text-align: center; color: #666; margin-bottom: 40px;">Personified terpenes sharing their unique characteristics</p>
            <div class="tersona-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
                <div class="tersona-card" style="border: 1px solid #ddd; border-radius: 8px; padding: 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; text-align: center;">
                    <div style="font-size: 48px; margin-bottom: 10px;">🌿</div>
                    <h3>Myrcene</h3>
                    <p>The relaxing sleepy terpene</p>
                </div>
                <div class="tersona-card" style="border: 1px solid #ddd; border-radius: 8px; padding: 20px; background: linear-gradient(135deg, #ffeaa7 0%, #fab1a0 100%); color: #333; text-align: center;">
                    <div style="font-size: 48px; margin-bottom: 10px;">🍋</div>
                    <h3>Limonene</h3>
                    <p>The uplifting citrus terpene</p>
                </div>
                <div class="tersona-card" style="border: 1px solid #ddd; border-radius: 8px; padding: 20px; background: linear-gradient(135deg, #74b9ff 0%, #0984e3 100%); color: white; text-align: center;">
                    <div style="font-size: 48px; margin-bottom: 10px;">🌲</div>
                    <h3>Pinene</h3>
                    <p>The focusing forest terpene</p>
                </div>
            </div>
        </div>';
        get_footer();
    }
    
    public function render_terports_page() {
        if (!headers_sent()) {
            status_header(200);
            header('Content-Type: text/html; charset=UTF-8');
        }
        
        get_header();
        echo '<div class="terpedia-terports-page" style="max-width: 1200px; margin: 0 auto; padding: 20px;">
            <h1 style="color: #2c5aa0; text-align: center; margin-bottom: 30px;">📋 Research Terports</h1>
            <p style="text-align: center; color: #666; margin-bottom: 40px;">AI-generated research reports and analysis</p>
            ' . $this->terports_shortcode(array()) . '
        </div>';
        get_footer();
    }
    
    // API endpoints
    public function api_encyclopedia_entry($request) {
        $term = $request['term'];
        $data = $this->get_encyclopedia_data($term);
        return new WP_REST_Response($data, 200);
    }
    
    public function api_encyclopedia_search($request) {
        $query = $request->get_param('q');
        $results = $this->search_encyclopedia($query);
        return new WP_REST_Response($results, 200);
    }
    
    public function api_get_keywords($request) {
        $keywords = $this->get_scientific_keywords();
        return new WP_REST_Response($keywords, 200);
    }
    
    public function api_process_text($request) {
        $text = $request->get_param('text');
        $processed = $this->process_text_content($text);
        return new WP_REST_Response(array('processed_text' => $processed), 200);
    }
    
    // Data helper methods
    public function get_encyclopedia_data($term) {
        $term = strtolower($term);
        
        // Check if we have a post for this terpene
        $posts = get_posts(array(
            'post_type' => 'terpene',
            'name' => $term,
            'posts_per_page' => 1
        ));
        
        if (!empty($posts)) {
            $post = $posts[0];
            return array(
                'title' => $post->post_title,
                'content' => $post->post_content,
                'formula' => get_post_meta($post->ID, 'molecular_formula', true) ?: 'C10H16',
                'effects' => get_post_meta($post->ID, 'effects', true) ?: 'Various therapeutic effects',
                'sources' => get_post_meta($post->ID, 'sources', true) ?: 'Cannabis, hops, other plants'
            );
        }
        
        // Fallback data for common terpenes
        $fallback_data = array(
            'myrcene' => array(
                'title' => 'Myrcene',
                'content' => 'Myrcene is a monoterpene and the most prevalent terpene found in cannabis. It is known for its sedative and muscle-relaxing properties. Research suggests myrcene may enhance the permeability of cell membranes, allowing for better absorption of other compounds.',
                'formula' => 'C10H16',
                'effects' => 'Sedative, muscle relaxant, sleep aid',
                'sources' => 'Cannabis, hops, mangoes, lemongrass, thyme'
            ),
            'limonene' => array(
                'title' => 'Limonene',
                'content' => 'Limonene is the second most abundant terpene in nature and is commonly found in citrus fruits. It exhibits anti-anxiety and mood-elevating properties, and has been shown to modulate neurotransmitter activity in the brain.',
                'formula' => 'C10H16',
                'effects' => 'Anti-anxiety, mood enhancement, stress relief',
                'sources' => 'Citrus fruits, juniper, peppermint, rosemary'
            ),
            'pinene' => array(
                'title' => 'Pinene',
                'content' => 'Alpha-pinene is a bicyclic monoterpene commonly found in pine trees and other conifers. It may help improve focus, memory retention, and has bronchodilator effects that can assist with respiratory function.',
                'formula' => 'C10H16',
                'effects' => 'Focus enhancement, memory, bronchodilator, alertness',
                'sources' => 'Pine trees, rosemary, basil, parsley, dill'
            ),
        );
        
        return $fallback_data[$term] ?? array(
            'title' => ucfirst($term),
            'content' => 'This is a terpene compound with various biological activities. More research is needed to fully understand its properties and therapeutic potential.',
            'formula' => 'C10H16',
            'effects' => 'Various biological activities',
            'sources' => 'Various plant sources'
        );
    }
    
    public function search_encyclopedia($query) {
        $keywords = $this->get_scientific_keywords();
        $results = array();
        
        foreach ($keywords as $keyword) {
            if (stripos($keyword, $query) !== false) {
                $results[] = array(
                    'term' => $keyword,
                    'url' => '/cyc/' . strtolower($keyword),
                    'type' => 'terpene'
                );
            }
        }
        
        return $results;
    }
    
    public function get_scientific_keywords() {
        return array('myrcene', 'limonene', 'pinene', 'caryophyllene', 'linalool', 'humulene', 'terpinolene', 'ocimene', 'cannabinoid', 'THC', 'CBD', 'terpene', 'monoterpene', 'sesquiterpene', 'entourage effect', 'cannabis', 'hemp', 'essential oil', 'aromatherapy', 'therapeutic', 'analgesic', 'anti-inflammatory', 'anxiolytic', 'sedative', 'bronchodilator', 'neuroprotective', 'antioxidant');
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            'Terpedia Settings',
            'Terpedia',
            'manage_options',
            'terpedia-settings',
            array($this, 'admin_page'),
            'dashicons-analytics',
            30
        );
    }
    
    /**
     * Admin page callback
     */
    public function admin_page() {
        echo '<div class="wrap">';
        echo '<h1>Terpedia Settings</h1>';
        echo '<p>Welcome to Terpedia! Your comprehensive terpene encyclopedia with AI agents.</p>';
        echo '<h2>Available Features:</h2>';
        echo '<ul>';
        echo '<li>✅ Terpene Encyclopedia (/cyc)</li>';
        echo '<li>✅ AI Expert Agents (/agents)</li>';
        echo '<li>✅ Research Terports (/terports)</li>';
        echo '<li>✅ OpenRouter AI Integration</li>';
        echo '<li>✅ RSS Feed Management</li>';
        echo '</ul>';
        echo '<p><a href="' . admin_url('admin.php?page=terpedia-openrouter-settings') . '" class="button button-primary">Configure OpenRouter API</a></p>';
        echo '</div>';
    }
}

new TerpediaPlugin();

// Initialize the enhanced Rx system
new Terpedia_Enhanced_Rx_System();

// Initialize the RSS feed manager for agents
new TerpediaAgentRSSManager();

// AJAX handler for agent consultation
add_action('wp_ajax_consult_agent', 'terpedia_consult_agent');
add_action('wp_ajax_nopriv_consult_agent', 'terpedia_consult_agent');

function terpedia_consult_agent() {
    check_ajax_referer('terpedia_nonce', 'nonce');
    
    $agent_id = sanitize_text_field($_POST['agent_id']);
    $query = sanitize_textarea_field($_POST['query']);
    
    $responses = array(
        'dr_molecular' => "Based on molecular analysis, I can help you understand chemical structures and properties. What specific molecular aspect interests you?",
        'prof_pharmakin' => "From a pharmacokinetic perspective, I can analyze absorption, distribution, metabolism, and excretion. What's your question?",
        'scholar_citeswell' => "I can help you find relevant research papers and scientific citations. Let me search the literature.",
        'dr_formulator' => "As a formulation expert, I can advise on incorporating terpenes into products. What are you developing?",
        'dr_paws' => "From a veterinary perspective, I can discuss terpene safety and efficacy for animals. What species?"
    );
    
    $response = isset($responses[$agent_id]) ? $responses[$agent_id] : "Hello! How can I assist with your terpene question?";
    
    wp_send_json_success(array(
        'agent_id' => $agent_id,
        'response' => $response,
        'timestamp' => current_time('mysql')
    ));
}

// AJAX handler for newsletter signup
add_action('wp_ajax_terpedia_newsletter_signup', 'terpedia_newsletter_signup');
add_action('wp_ajax_nopriv_terpedia_newsletter_signup', 'terpedia_newsletter_signup');

function terpedia_newsletter_signup() {
    check_ajax_referer('terpedia_newsletter_signup', 'nonce');
    
    $email = sanitize_email($_POST['email']);
    
    if (!is_email($email)) {
        wp_send_json_error('Please enter a valid email address.');
    }
    
    // Store email in database or send to email service
    global $wpdb;
    $table_name = $wpdb->prefix . 'terpedia_newsletter_subscribers';
    
    // Create table if it doesn't exist
    $charset_collate = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id int(11) NOT NULL AUTO_INCREMENT,
        email varchar(255) NOT NULL,
        status varchar(20) DEFAULT 'active',
        subscribed_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY email (email)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
    
    // Check if email already exists
    $existing = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM $table_name WHERE email = %s",
        $email
    ));
    
    if ($existing) {
        wp_send_json_error('This email is already subscribed to our newsletter.');
    }
    
    // Insert new subscriber
    $result = $wpdb->insert(
        $table_name,
        array(
            'email' => $email,
            'status' => 'active',
            'subscribed_at' => current_time('mysql')
        )
    );
    
    if ($result) {
        // Send welcome email (optional)
        $subject = 'Welcome to Terpedia Newsletter!';
        $message = 'Thank you for subscribing to the Terpedia newsletter. You will receive the latest terpene research, industry news, and insights delivered to your inbox.';
        wp_mail($email, $subject, $message);
        
        wp_send_json_success('Thank you for subscribing! You will receive our newsletter updates.');
    } else {
        wp_send_json_error('An error occurred while subscribing. Please try again.');
    }
}
?>