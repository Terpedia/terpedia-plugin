<?php
/**
 * Plugin Name: Terpedia
 * Description: Comprehensive terpene encyclopedia with AI agents and research tools
 * Version: 2.0.4
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
    }
    
    public function enqueue_assets() {
        // Get dynamic version from version manager
        require_once plugin_dir_path(__FILE__) . 'version-manager.php';
        $plugin_version = TerpediaPluginVersionManager::getCurrentVersion();
        
        wp_enqueue_style('terpedia-css', plugin_dir_url(__FILE__) . 'assets/terpedia.css', array(), $plugin_version);
        wp_enqueue_script('terpedia-js', plugin_dir_url(__FILE__) . 'assets/terpedia.js', array('jquery'), $plugin_version, true);
        
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
        
        register_post_type('podcast', array(
            'labels' => array(
                'name' => 'Podcasts',
                'singular_name' => 'Podcast'
            ),
            'public' => true,
            'has_archive' => true,
            'supports' => array('title', 'editor', 'custom-fields', 'thumbnail'),
            'menu_icon' => 'dashicons-microphone'
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
            'post_type' => 'podcast',
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
    
    public function newsletter_shortcode($atts) {
        $newsletters = get_posts(array(
            'post_type' => 'newsletter',
            'posts_per_page' => 4
        ));
        
        $output = '<div class="terpedia-newsletters" style="margin: 20px 0;"><h3 style="color: #2c5aa0;">📧 Terpene Times Newsletter</h3><div class="newsletter-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 20px; margin-top: 15px;">';
        
        if ($newsletters) {
            foreach ($newsletters as $post) {
                $issue_number = get_post_meta($post->ID, 'issue_number', true) ?: '1';
                $publish_date = get_post_meta($post->ID, 'publish_date', true) ?: date('Y-m-d');
                $subscriber_count = get_post_meta($post->ID, 'subscriber_count', true) ?: '2,847';
                
                $output .= '<div class="newsletter-card" style="border: 1px solid #ddd; border-radius: 8px; padding: 20px; background: linear-gradient(135deg, #ffeaa7 0%, #fab1a0 100%);">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                        <span style="background: rgba(0,0,0,0.1); padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold;">Issue #' . esc_html($issue_number) . '</span>
                        <span style="font-size: 12px; opacity: 0.8;">' . date('M j, Y', strtotime($publish_date)) . '</span>
                    </div>
                    <h4 style="margin: 10px 0; color: #2d3436;"><a href="' . get_permalink($post->ID) . '" style="color: #2d3436; text-decoration: none;">' . esc_html($post->post_title) . '</a></h4>
                    <p style="color: #636e72; font-size: 14px; margin: 10px 0;">' . wp_trim_words($post->post_content, 18) . '</p>
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 15px;">
                        <span style="font-size: 12px; color: #636e72;">📊 ' . esc_html($subscriber_count) . ' subscribers</span>
                        <button style="background: #2d3436; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer;">📖 Read</button>
                    </div>
                </div>';
            }
        } else {
            // Default newsletter issues
            $default_issues = array(
                array('title' => 'Breakthrough: Myrcene Shows 89% Success Rate', 'issue' => '49', 'date' => '2024-12-15', 'subscribers' => '3,247', 'desc' => 'Clinical trial results show unprecedented success rates for myrcene in chronic pain management.'),
                array('title' => 'Cannabis Industry Report: Terpene Market Surge', 'issue' => '48', 'date' => '2024-12-08', 'subscribers' => '3,198', 'desc' => 'Market analysis reveals 340% growth in terpene isolate demand over the past year.'),
                array('title' => 'New Research: Pinene and Cognitive Function', 'issue' => '47', 'date' => '2024-12-01', 'subscribers' => '3,156', 'desc' => 'Stanford study demonstrates pinene\'s potential in treating age-related cognitive decline.')
            );
            
            foreach ($default_issues as $issue) {
                $output .= '<div class="newsletter-card" style="border: 1px solid #ddd; border-radius: 8px; padding: 20px; background: linear-gradient(135deg, #ffeaa7 0%, #fab1a0 100%);">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                        <span style="background: rgba(0,0,0,0.1); padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold;">Issue #' . esc_html($issue['issue']) . '</span>
                        <span style="font-size: 12px; opacity: 0.8;">' . date('M j, Y', strtotime($issue['date'])) . '</span>
                    </div>
                    <h4 style="margin: 10px 0; color: #2d3436;">' . esc_html($issue['title']) . '</h4>
                    <p style="color: #636e72; font-size: 14px; margin: 10px 0;">' . esc_html($issue['desc']) . '</p>
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 15px;">
                        <span style="font-size: 12px; color: #636e72;">📊 ' . esc_html($issue['subscribers']) . ' subscribers</span>
                        <button style="background: #2d3436; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer;">📖 Read</button>
                    </div>
                </div>';
            }
        }
        
        $output .= '</div></div>';
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
            if (!username_exists($agent['username'])) {
                $user_id = wp_create_user($agent['username'], wp_generate_password(), $agent['email']);
                if (!is_wp_error($user_id)) {
                    wp_update_user(array(
                        'ID' => $user_id,
                        'display_name' => $agent['name'],
                        'description' => $agent['specialty']
                    ));
                    
                    update_user_meta($user_id, 'terpedia_agent', true);
                    update_user_meta($user_id, 'agent_specialty', $agent['specialty']);
                }
            }
        }
    }
    
    public function activate() {
        $this->register_post_types();
        flush_rewrite_rules();
        $this->create_sample_content();
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
}

new TerpediaPlugin();

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
?>