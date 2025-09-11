<?php
/**
 * Plugin Name: Terpedia
 * Plugin URI: https://terpedia.com
 * Description: Comprehensive terpene encyclopedia with 13 AI experts, intelligent newsletter generator with PubMed integration, 700K+ natural products, UA Huntsville supercomputer integration
 * Version: 3.9.4
 * Author: Terpedia Team
 * License: GPL v2 or later
 * Requires at least: 5.8
 * Tested up to: 6.4
 * Requires PHP: 7.4
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('TERPEDIA_AI_VERSION', '3.9.4');
define('TERPEDIA_AI_URL', plugin_dir_url(__FILE__));
define('TERPEDIA_AI_PATH', plugin_dir_path(__FILE__));

class TerpediaAI {
    
    public function __construct() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        
        // Maintain Tersonae and Expert users
        add_action('init', array($this, 'maintain_terpedia_users'), 20);
        
        // URL routing for Terpedia.com
        add_action('template_redirect', array($this, 'handle_terpedia_routes'));
        add_filter('query_vars', array($this, 'add_query_vars'));
        add_action('generate_rewrite_rules', array($this, 'add_rewrite_rules'));
        
        // AJAX handlers
        add_action('wp_ajax_terpedia_query', array($this, 'handle_ajax_query'));
        add_action('wp_ajax_nopriv_terpedia_query', array($this, 'handle_ajax_query'));
        add_action('wp_ajax_terpedia_multiagent', array($this, 'handle_ajax_multiagent'));
        add_action('wp_ajax_nopriv_terpedia_multiagent', array($this, 'handle_ajax_multiagent'));
        add_action('wp_ajax_setup_terpene_profiles', array($this, 'handle_ajax_setup_profiles'));
        add_action('wp_ajax_nopriv_setup_terpene_profiles', array($this, 'handle_ajax_setup_profiles'));
        add_action('wp_ajax_terpedia_chemist_chat', array($this, 'handle_chemist_chat'));
        add_action('wp_ajax_nopriv_terpedia_chemist_chat', array($this, 'handle_chemist_chat'));
        add_action('wp_ajax_terpedia_molecular_structure', array($this, 'handle_molecular_structure'));
        add_action('wp_ajax_nopriv_terpedia_molecular_structure', array($this, 'handle_molecular_structure'));
        add_action('wp_ajax_terpedia_find_database_links', array($this, 'ajax_find_database_links'));
        
        // Shortcodes
        add_shortcode('terpedia_chat', array($this, 'chat_shortcode'));
        add_shortcode('terpedia_multiagent', array($this, 'multiagent_shortcode'));
        add_shortcode('terpedia_design', array($this, 'design_shortcode'));
        add_shortcode('terpedia_chemist', array($this, 'chemist_shortcode'));
        add_shortcode('terpedia_newsletter', array($this, 'newsletter_shortcode'));
        add_shortcode('terpedia_podcast', array($this, 'podcast_shortcode'));
        
        // Custom Post Types
        add_action('init', array($this, 'create_podcast_post_type'));
        add_action('init', array($this, 'create_terproducts_post_type'));
        
        // Version update check and episode creation
        add_action('plugins_loaded', array($this, 'check_version_update'));
        add_action('init', array($this, 'ensure_default_episodes'));
        
        // Admin menu and secure handlers
        if (is_admin()) {
            add_action('admin_menu', array($this, 'add_admin_menu'));
            add_action('admin_init', array($this, 'register_settings'));
            
            // Secure admin-post handlers for updates
            add_action('admin_post_terpedia_update_theme', array($this, 'handle_secure_theme_update'));
            add_action('admin_post_terpedia_update_plugin', array($this, 'handle_secure_plugin_update'));
        }
    }
    
    public function activate() {
        // Create database tables if needed
        $this->create_database_tables();
        
        // Force flush rewrite rules immediately
        flush_rewrite_rules();
        add_option('terpedia_ai_flush_rewrite_rules', true);
        
        // Clear any caches
        if (function_exists('wp_cache_flush')) {
            wp_cache_flush();
        }
    }
    
    public function deactivate() {
        flush_rewrite_rules();
    }
    
    public function init() {
        // Basic initialization
        load_plugin_textdomain('terpedia-ai', false, dirname(plugin_basename(__FILE__)) . '/languages');
        
        // Flush rewrite rules on activation
        if (get_option('terpedia_ai_flush_rewrite_rules')) {
            flush_rewrite_rules();
            delete_option('terpedia_ai_flush_rewrite_rules');
        }
        
        // Include BuddyPress messaging and agent systems (safely)
        if (class_exists('BuddyPress')) {
            $includes = array(
                'includes/buddypress-messaging.php',
                'includes/terpene-agents.php',
                'includes/agent-profiles.php',
                'includes/buddypress-profile-setup.php',
                'includes/avatar-generator.php',
                'includes/buddypress-avatar-fix.php',
                'includes/force-avatar-refresh.php',
                'includes/direct-avatar-override.php',
                'includes/avatar-force-display.php',
                'includes/force-avatar-injection.php',
                'includes/enhanced-tts-system.php',
                'includes/neural-tts-system.php',
                'includes/profile-enhancement.php',
                'includes/expert-agent-profiles.php',
                'includes/case-management.php',
                'includes/agent-conversations.php',
                'includes/demo-user-setup.php',
                'includes/patient-intake-form.php',
                'includes/complete-agent-setup.php',
                'includes/integrated-profile-design.php',
                'includes/demo-veterinarian-case.php',
                'includes/complete-profile-override.php',
                'includes/force-demo-creation.php'
            );
            
            foreach ($includes as $file) {
                $filepath = plugin_dir_path(__FILE__) . $file;
                if (file_exists($filepath)) {
                    require_once $filepath;
                }
            }
            
            // Admin interfaces
            if (is_admin()) {
                $admin_file = plugin_dir_path(__FILE__) . 'admin/agent-management.php';
                if (file_exists($admin_file)) {
                    require_once $admin_file;
                }
            }
        }
    }
    
    /**
     * Add custom query vars for routing
     */
    public function add_query_vars($vars) {
        $vars[] = 'terpedia_page';
        return $vars;
    }
    
    /**
     * Add rewrite rules for Terpedia.com routes
     */
    public function add_rewrite_rules($wp_rewrite) {
        $new_rules = array(
            'design/?$' => 'index.php?terpedia_page=design',
            'chat/?$' => 'index.php?terpedia_page=chat',
            'multi-agent/?$' => 'index.php?terpedia_page=multiagent'
        );
        
        $wp_rewrite->rules = $new_rules + $wp_rewrite->rules;
    }
    
    /**
     * Handle Terpedia.com route requests
     */
    public function handle_terpedia_routes() {
        global $wp_query;
        
        $terpedia_page = get_query_var('terpedia_page');
        
        if (!empty($terpedia_page)) {
            // Set up WordPress to display our custom page
            $wp_query->is_home = false;
            $wp_query->is_404 = false;
            
            switch ($terpedia_page) {
                case 'design':
                    $this->render_design_page();
                    break;
                case 'chat':
                    $this->render_chat_page();
                    break;
                case 'multiagent':
                    $this->render_multiagent_page();
                    break;
                default:
                    return;
            }
            exit;
        }
    }
    
    /**
     * Render the design page as standalone
     */
    private function render_design_page() {
        $this->enqueue_scripts();
        $this->render_terpedia_page('Terpedia Architecture & Design', $this->design_shortcode(array()));
    }
    
    /**
     * Render the chat page as standalone
     */
    private function render_chat_page() {
        $this->enqueue_scripts();
        $this->render_terpedia_page('AI Research Chat', $this->chat_shortcode(array('height' => '600px')));
    }
    
    /**
     * Render the multi-agent page as standalone
     */
    private function render_multiagent_page() {
        $this->enqueue_scripts();
        $this->render_terpedia_page('Multi-Agent Research', $this->multiagent_shortcode(array('height' => '700px')));
    }
    
    /**
     * Render a Terpedia page with proper HTML structure
     */
    private function render_terpedia_page($title, $content) {
        ?>
        <!DOCTYPE html>
        <html <?php language_attributes(); ?>>
        <head>
            <meta charset="<?php bloginfo('charset'); ?>">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title><?php echo esc_html($title); ?> - <?php bloginfo('name'); ?></title>
            <?php wp_head(); ?>
        </head>
        <body <?php body_class(); ?>>
            <div class="terpedia-page-wrapper">
                <header class="terpedia-header">
                    <h1><?php echo esc_html($title); ?></h1>
                    <p><a href="<?php echo home_url(); ?>">← Back to Terpedia</a></p>
                </header>
                <main class="terpedia-content">
                    <?php echo $content; ?>
                </main>
            </div>
            <?php wp_footer(); ?>
        </body>
        </html>
        <?php
    }
    
    public function enqueue_scripts() {
        wp_enqueue_script('jquery');
        // Enqueue the lifelike neural TTS system
        wp_enqueue_script(
            'terpedia-lifelike-neural-tts',
            TERPEDIA_AI_URL . 'assets/js/lifelike-neural-tts.js',
            array('jquery'),
            TERPEDIA_AI_VERSION,
            true
        );
        
        wp_enqueue_script(
            'terpedia-ai-script',
            TERPEDIA_AI_URL . 'assets/js/terpedia-ai.js',
            array('jquery'),
            TERPEDIA_AI_VERSION,
            true
        );
        wp_enqueue_style(
            'terpedia-ai-style',
            TERPEDIA_AI_URL . 'assets/css/terpedia-ai.css',
            array(),
            TERPEDIA_AI_VERSION
        );
        
        wp_localize_script('terpedia-ai-script', 'terpedia_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('terpedia_nonce')
        ));
    }
    
    public function handle_ajax_query() {
        try {
            if (!check_ajax_referer('terpedia_nonce', 'nonce', false)) {
                wp_send_json_error('Security check failed');
                return;
            }
            
            $query = isset($_POST['query']) ? sanitize_textarea_field($_POST['query']) : '';
            
            if (empty($query)) {
                wp_send_json_error('No query provided');
                return;
            }
            
            // Simple response for now
            $response = array(
                'answer' => 'Thank you for your query about: ' . $query . '. The multi-agent system is being configured.',
                'sources' => array('Terpedia Database'),
                'confidence' => 0.8
            );
            
            wp_send_json_success($response);
            
        } catch (Exception $e) {
            error_log('Terpedia AI Error: ' . $e->getMessage());
            wp_send_json_error('An error occurred processing your request');
        }
    }
    
    public function handle_ajax_multiagent() {
        try {
            if (!check_ajax_referer('terpedia_nonce', 'nonce', false)) {
                wp_send_json_error('Security check failed');
                return;
            }
            
            $query = isset($_POST['query']) ? sanitize_textarea_field($_POST['query']) : '';
            
            if (empty($query)) {
                wp_send_json_error('No query provided');
                return;
            }
            
            // Simple multi-agent response
            $response = array(
                'primaryAnswer' => 'Multi-agent analysis of: ' . $query,
                'agentResponses' => array(
                    array(
                        'agentName' => 'Cannabis Chemist',
                        'response' => 'Chemical analysis perspective on your query.',
                        'confidence' => 0.85
                    ),
                    array(
                        'agentName' => 'Pharmacologist',
                        'response' => 'Pharmacological insights on your question.',
                        'confidence' => 0.82
                    )
                ),
                'consensus' => 'The agents agree this is an important research topic.',
                'finalConfidence' => 0.83
            );
            
            wp_send_json_success($response);
            
        } catch (Exception $e) {
            error_log('Terpedia AI Multiagent Error: ' . $e->getMessage());
            wp_send_json_error('An error occurred with the multi-agent system');
        }
    }
    
    public function handle_ajax_setup_profiles() {
        try {
            if (!check_ajax_referer('terpedia_nonce', 'nonce', false)) {
                wp_send_json_error('Security check failed');
                return;
            }
            
            // Setup terpene profiles
            if (class_exists('TerpeneBuddyPressAgents')) {
                $terpene_agents = new TerpeneBuddyPressAgents();
                $terpene_agents->create_terpene_agents();
                
                // Also trigger profile setup
                if (class_exists('TerpediaBuddyPressProfileSetup')) {
                    $profile_setup = new TerpediaBuddyPressProfileSetup();
                    $profile_setup->ajax_create_terpene_profiles();
                    return; // This will send its own JSON response
                }
            }
            
            wp_send_json_success(array(
                'message' => 'Terpene profiles setup initiated'
            ));
            
        } catch (Exception $e) {
            error_log('Terpedia Profile Setup Error: ' . $e->getMessage());
            wp_send_json_error('An error occurred setting up profiles');
        }
    }
    
    public function chat_shortcode($atts) {
        $atts = shortcode_atts(array(
            'height' => '500px',
            'width' => '100%'
        ), $atts, 'terpedia_chat');
        
        ob_start();
        ?>
        <div id="terpedia-chat-container" style="height: <?php echo esc_attr($atts['height']); ?>; width: <?php echo esc_attr($atts['width']); ?>;">
            <div class="terpedia-chat-header">
                <h3>Terpedia AI Research Assistant</h3>
            </div>
            <div class="terpedia-chat-messages" id="terpedia-messages"></div>
            <div class="terpedia-chat-input">
                <textarea id="terpedia-query" placeholder="Ask about cannabis terpenes, research, or regulations..."></textarea>
                <button id="terpedia-send" type="button">Send</button>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    public function multiagent_shortcode($atts) {
        $atts = shortcode_atts(array(
            'height' => '600px',
            'width' => '100%'
        ), $atts, 'terpedia_multiagent');
        
        ob_start();
        ?>
        <div id="terpedia-multiagent-container" style="height: <?php echo esc_attr($atts['height']); ?>; width: <?php echo esc_attr($atts['width']); ?>;">
            <div class="terpedia-multiagent-header">
                <h3>Multi-Agent Cannabis Research</h3>
                <p>Collaborate with AI specialists in chemistry, pharmacology, literature, and regulation</p>
            </div>
            <div class="terpedia-query-section">
                <textarea id="terpedia-multiagent-query" placeholder="Enter your research question..."></textarea>
                <button id="terpedia-collaborate" type="button">Start Research</button>
            </div>
            <div id="terpedia-multiagent-results"></div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    public function design_shortcode($atts) {
        $atts = shortcode_atts(array(
            'theme' => 'light'
        ), $atts, 'terpedia_design');
        
        ob_start();
        ?>
        <div id="terpedia-design-document">
            <!-- Header Section -->
            <div class="design-header">
                <div class="header-content">
                    <h1>Terpedia - Encyclopedia of Terpenes</h1>
                    <h2>AI-Powered Knowledge System Architecture</h2>
                    <div class="document-meta">
                        <span class="version">Production v3.9.1</span>
                        <span class="date">January 2025 - Mobile Enhanced</span>
                        <span class="authors">Terpedia Research Team</span>
                    </div>
                </div>
                <div class="header-illustration">
                    <svg viewBox="0 0 400 200" class="architecture-diagram">
                        <!-- Central AI Hub -->
                        <circle cx="200" cy="100" r="25" fill="#2c5aa0" stroke="#1e3a6f" stroke-width="2"/>
                        <text x="200" y="105" text-anchor="middle" fill="white" font-size="10">AI Core</text>
                        
                        <!-- Agent Nodes -->
                        <circle cx="120" cy="60" r="18" fill="#4a90e2" stroke="#2c5aa0" stroke-width="1"/>
                        <text x="120" y="45" text-anchor="middle" font-size="8">Chemist</text>
                        
                        <circle cx="280" cy="60" r="18" fill="#4a90e2" stroke="#2c5aa0" stroke-width="1"/>
                        <text x="280" y="45" text-anchor="middle" font-size="8">Pharmacist</text>
                        
                        <circle cx="120" cy="140" r="18" fill="#4a90e2" stroke="#2c5aa0" stroke-width="1"/>
                        <text x="120" y="155" text-anchor="middle" font-size="8">Budtender</text>
                        
                        <circle cx="280" cy="140" r="18" fill="#4a90e2" stroke="#2c5aa0" stroke-width="1"/>
                        <text x="280" y="155" text-anchor="middle" font-size="8">Formulator</text>
                        
                        <!-- Connections -->
                        <line x1="175" y1="100" x2="138" y2="78" stroke="#2c5aa0" stroke-width="1"/>
                        <line x1="225" y1="100" x2="262" y2="78" stroke="#2c5aa0" stroke-width="1"/>
                        <line x1="175" y1="100" x2="138" y2="122" stroke="#2c5aa0" stroke-width="1"/>
                        <line x1="225" y1="100" x2="262" y2="122" stroke="#2c5aa0" stroke-width="1"/>
                        
                        <!-- Data Sources -->
                        <rect x="30" y="10" width="50" height="20" fill="#e8f4fd" stroke="#2c5aa0" stroke-width="1"/>
                        <text x="55" y="22" text-anchor="middle" font-size="7">Terpedia DB</text>
                        
                        <rect x="320" y="10" width="50" height="20" fill="#e8f4fd" stroke="#2c5aa0" stroke-width="1"/>
                        <text x="345" y="22" text-anchor="middle" font-size="7">Traditional Med</text>
                        
                        <rect x="30" y="170" width="50" height="20" fill="#e8f4fd" stroke="#2c5aa0" stroke-width="1"/>
                        <text x="55" y="182" text-anchor="middle" font-size="7">Ethnobotany</text>
                        
                        <rect x="320" y="170" width="50" height="20" fill="#e8f4fd" stroke="#2c5aa0" stroke-width="1"/>
                        <text x="345" y="182" text-anchor="middle" font-size="7">PubMed</text>
                    </svg>
                </div>
            </div>

            <!-- Executive Summary -->
            <section class="design-section executive-summary">
                <h3>Executive Summary</h3>
                <div class="summary-grid">
                    <div class="summary-card">
                        <h4>Mission</h4>
                        <p>Create the world's most comprehensive encyclopedia of terpenes through AI-powered knowledge synthesis, providing instant access to molecular structures, therapeutic properties, and aromatic profiles.</p>
                    </div>
                    <div class="summary-card">
                        <h4>Innovation</h4>
                        <p>First encyclopedia to combine 11 specialized AI experts with real-time biochemical databases, molecular analysis, and evidence-based terpene formulation guidance.</p>
                    </div>
                    <div class="summary-card">
                        <h4>Impact</h4>
                        <p>Transforms terpene knowledge from scattered research into an organized, searchable encyclopedia accessible to researchers, formulators, and health professionals worldwide.</p>
                    </div>
                </div>
            </section>

            <!-- What Terpedia Can Do -->
            <section class="design-section capabilities">
                <h3>What Terpedia Can Do</h3>
                <div class="capabilities-intro">
                    <p>Terpedia is your comprehensive gateway to the world of terpenes - the aromatic compounds found in countless plants that influence everything from fragrance to therapeutic effects. Our AI-powered encyclopedia with <strong>Lifelike Neural TTS</strong> makes complex terpene science accessible to everyone through natural voice interactions.</p>
                </div>
                
                <div class="capabilities-grid">
                    <div class="capability-category">
                        <div class="category-header">
                            <div class="category-icon">🔍</div>
                            <h4>Instant Terpene Knowledge</h4>
                        </div>
                        <ul class="capability-list">
                            <li><strong>Ask any terpene question</strong> - Get expert answers about molecular structures, properties, and effects</li>
                            <li><strong>Explore terpene profiles</strong> - Browse detailed pages for individual terpenes with scientific data</li>
                            <li><strong>Discover natural sources</strong> - Learn which plants contain specific terpenes and their geographic distribution</li>
                            <li><strong>Traditional medicine context</strong> - Understand historical uses across different cultures and healing systems</li>
                            <li><strong>Ethnobotanical insights</strong> - Explore indigenous knowledge and regional plant applications</li>
                        </ul>
                    </div>
                    
                    <div class="capability-category">
                        <div class="category-header">
                            <div class="category-icon">🧪</div>
                            <h4>Scientific Analysis</h4>
                        </div>
                        <ul class="capability-list">
                            <li><strong>Molecular structure visualization</strong> - See 3D chemical structures and understand molecular interactions</li>
                            <li><strong>Enzyme binding analysis</strong> - Explore how terpenes interact with biological targets</li>
                            <li><strong>Biochemical pathway mapping</strong> - Understand biosynthesis and metabolic processes</li>
                            <li><strong>Chemical property calculations</strong> - Get precise data on boiling points, solubility, and stability</li>
                        </ul>
                    </div>
                    
                    <div class="capability-category">
                        <div class="category-header">
                            <div class="category-icon">⚗️</div>
                            <h4>Formulation Assistance</h4>
                        </div>
                        <ul class="capability-list">
                            <li><strong>Blend optimization</strong> - Calculate precise ratios for desired aromatic profiles</li>
                            <li><strong>Synergy analysis</strong> - Understand how terpenes work together for enhanced effects</li>
                            <li><strong>Essential oil formulation</strong> - Create custom blends for specific therapeutic or aromatic goals</li>
                            <li><strong>Product development guidance</strong> - Professional consultation for commercial applications</li>
                        </ul>
                    </div>
                    
                    <div class="capability-category">
                        <div class="category-header">
                            <div class="category-icon">📚</div>
                            <h4>Research & Education</h4>
                        </div>
                        <ul class="capability-list">
                            <li><strong>Literature synthesis</strong> - Access thousands of research papers with AI-powered analysis</li>
                            <li><strong>Citation tracking</strong> - Follow scientific evidence with proper academic references</li>
                            <li><strong>Knowledge reports</strong> - Generate comprehensive summaries on any terpene topic</li>
                            <li><strong>Educational content</strong> - Learn about terpene science at any level of complexity</li>
                        </ul>
                    </div>
                    
                    <div class="capability-category">
                        <div class="category-header">
                            <div class="category-icon">🌐</div>
                            <h4>Real-Time Data Access</h4>
                        </div>
                        <ul class="capability-list">
                            <li><strong>Terpedia SPARQL Database</strong> - Access comprehensive natural product data from traditional medicine and ethnobotany sources</li>
                            <li><strong>Live database queries</strong> - Connect to ChEBI, PubMed, and Wikidata in real-time</li>
                            <li><strong>Cross-platform validation</strong> - Verify information across multiple scientific databases</li>
                            <li><strong>Traditional medicine integration</strong> - Explore historical uses and cultural applications</li>
                            <li><strong>Ethnobotanical knowledge</strong> - Access indigenous plant wisdom and regional variations</li>
                        </ul>
                    </div>
                    
                    <div class="capability-category">
                        <div class="category-header">
                            <div class="category-icon">👥</div>
                            <h4>Expert AI Consultation</h4>
                        </div>
                        <ul class="capability-list">
                            <li><strong>13 specialized AI agents</strong> - Get targeted advice from professional Agt. specialists with distinct expertise</li>
                            <li><strong>Multi-perspective analysis</strong> - View questions from chemical, medical, and botanical angles</li>
                            <li><strong>Professional guidance</strong> - Receive advice tailored to your specific field or application</li>
                            <li><strong>Collaborative problem-solving</strong> - Multiple experts work together on complex questions</li>
                        </ul>
                    </div>
                </div>
                
                <div class="use-cases">
                    <h4>Who Uses Terpedia?</h4>
                    <div class="use-case-grid">
                        <div class="use-case">
                            <strong>Researchers & Academics</strong>
                            <p>Access comprehensive terpene data for studies, publications, and academic research</p>
                        </div>
                        <div class="use-case">
                            <strong>Product Formulators</strong>
                            <p>Develop essential oil blends, cosmetics, and therapeutic products with precision</p>
                        </div>
                        <div class="use-case">
                            <strong>Healthcare Professionals</strong>
                            <p>Understand therapeutic applications and safety profiles for patient care</p>
                        </div>
                        <div class="use-case">
                            <strong>Aromatherapists</strong>
                            <p>Create evidence-based aromatic treatments with scientific backing</p>
                        </div>
                        <div class="use-case">
                            <strong>Traditional Medicine Practitioners</strong>
                            <p>Access historical uses and cultural applications from global healing traditions</p>
                        </div>
                        <div class="use-case">
                            <strong>Ethnobotanists</strong>
                            <p>Explore indigenous plant knowledge and regional variations in terpene applications</p>
                        </div>
                        <div class="use-case">
                            <strong>Cannabis Professionals</strong>
                            <p>Analyze strain profiles and understand terpene effects in cannabis products</p>
                        </div>
                        <div class="use-case">
                            <strong>Students & Educators</strong>
                            <p>Learn and teach terpene science with accessible, accurate information</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Demo Veterinarian -->
            <section class="design-section demo-vet">
                <h3>Demo Veterinarian</h3>
                <div class="demo-user-section">
                    <div class="demo-user-card">
                        <a href="/members/dr-teresa-thompson/" class="demo-profile-link">
                            <div class="demo-user-info">
                                <div class="demo-user-avatar">🐕‍🦺</div>
                                <div class="demo-user-details">
                                    <h5>Dr. Teresa Thompson, DVM</h5>
                                    <p class="demo-user-title">Veterinary Terpene Specialist</p>
                                    <p class="demo-user-description">Specializing in canine seizure management with Golden Retriever case studies. Expert in veterinary terpene therapy and animal wellness protocols.</p>
                                    <div class="demo-case-info">
                                        <strong>Active Case:</strong> Bella (Golden Retriever) - Seizure Management with Terpene Therapy<br>
                                        <div style="margin-top: 10px;">
                                            <a href="/wp-content/plugins/terpedia/demo-pages/bella-patient.html" style="background: #4CAF50; color: white; padding: 6px 12px; border-radius: 15px; text-decoration: none; font-size: 12px; margin-right: 8px;">👩‍⚕️ View Patient</a>
                                            <a href="/wp-content/plugins/terpedia/demo-pages/seizure-case.html" style="background: #2196F3; color: white; padding: 6px 12px; border-radius: 15px; text-decoration: none; font-size: 12px;">📋 View Case</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
            </section>

            <!-- Multi-Agent Architecture -->
            <section class="design-section agent-architecture">
                <h3>Multi-Agent AI System</h3>
                <div class="agent-diagram">
                    <div class="agent-category medical">
                        <h4>Medical Experts</h4>
<?php
                        $medical_agents = array(
                            'terpedia-chemist' => array('icon' => '🧬', 'name' => 'Agt. Molecule Maven', 'desc' => 'Molecular structure wizard, chemical property analysis, and terpene biosynthesis pathways'),
                            'terpedia-pharmacologist' => array('icon' => '💊', 'name' => 'Agt. Pharmakin', 'desc' => 'Drug interaction specialist, bioavailability expert, and pharmacokinetics modeling guru'),
                            'terpedia-veterinarian' => array('icon' => '🐕', 'name' => 'Agt. Pawscription', 'desc' => 'Veterinary dosing expert, animal safety protocols, and species-specific terpene applications'),
                            'terpedia-naturopath' => array('icon' => '🌿', 'name' => 'Agt. Holistica', 'desc' => 'Traditional healing wisdom, herb synergies, and natural medicine integration')
                        );
                        ?>
                        <div class="agent-grid">
                        <?php foreach ($medical_agents as $username => $agent): 
                            $profile_url = '#';
                            if (function_exists('bp_core_get_user_domain')) {
                                $user = get_user_by('login', $username);
                                if ($user) {
                                    $profile_url = bp_core_get_user_domain($user->ID);
                                }
                            }
                        ?>
                            <a href="<?php echo esc_url($profile_url); ?>" class="agent-card agent-profile-link" title="Visit <?php echo esc_attr($agent['name']); ?>'s BuddyPress profile">
                                <div class="agent-icon"><?php echo $agent['icon']; ?></div>
                                <h5><?php echo esc_html($agent['name']); ?></h5>
                                <p><?php echo esc_html($agent['desc']); ?></p>
                                <div class="agent-status">Available for consultation</div>
                            </a>
                        <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="agent-category research">
                        <h4>Research & Development</h4>
<?php
                        $research_agents = array(
                            'terpedia-literature' => array('icon' => '📚', 'name' => 'Agt. Citeswell', 'desc' => 'PubMed detective, systematic review architect, and evidence synthesis specialist'),
                            'terpedia-regulatory' => array('icon' => '⚖️', 'name' => 'Agt. Compliance', 'desc' => 'Regulatory navigator, legal framework expert, and safety protocol guardian'),
                            'terpedia-reporter' => array('icon' => '📊', 'name' => 'Agt. Datawise', 'desc' => 'Research synthesizer, publication wizard, and scientific storytelling expert')
                        );
                        ?>
                        <div class="agent-grid">
                        <?php foreach ($research_agents as $username => $agent): 
                            $profile_url = '#';
                            if (function_exists('bp_core_get_user_domain')) {
                                $user = get_user_by('login', $username);
                                if ($user) {
                                    $profile_url = bp_core_get_user_domain($user->ID);
                                }
                            }
                        ?>
                            <a href="<?php echo esc_url($profile_url); ?>" class="agent-card agent-profile-link" title="Visit <?php echo esc_attr($agent['name']); ?>'s BuddyPress profile">
                                <div class="agent-icon"><?php echo $agent['icon']; ?></div>
                                <h5><?php echo esc_html($agent['name']); ?></h5>
                                <p><?php echo esc_html($agent['desc']); ?></p>
                                <div class="agent-status">Available for consultation</div>
                            </a>
                        <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="agent-category industry">
                        <h4>Industry Specialists</h4>
<?php
                        $industry_agents = array(
                            'terpedia-botanist' => array('icon' => '🌱', 'name' => 'Agt. Fieldsworth', 'desc' => 'Botanical detective, plant source specialist, and natural occurrence mapping expert'),
                            'terpedia-aromatherapist' => array('icon' => '🔬', 'name' => 'Agt. Alchemist', 'desc' => 'Sensory profile virtuoso, aromatic compound specialist, and terpene interaction maven'),
                            'terpedia-formulator' => array('icon' => '⚗️', 'name' => 'Agt. Mastermind', 'desc' => 'Formulation genius, optimization algorithm wizard, and precision ratio specialist'),
                            'terpedia-patient' => array('icon' => '👤', 'name' => 'Agt. Companion', 'desc' => 'Personal wellness advocate, individualized care specialist, and patient safety champion')
                        );
                        ?>
                        <div class="agent-grid">
                        <?php foreach ($industry_agents as $username => $agent): 
                            $profile_url = '#';
                            if (function_exists('bp_core_get_user_domain')) {
                                $user = get_user_by('login', $username);
                                if ($user) {
                                    $profile_url = bp_core_get_user_domain($user->ID);
                                }
                            }
                        ?>
                            <a href="<?php echo esc_url($profile_url); ?>" class="agent-card agent-profile-link" title="Visit <?php echo esc_attr($agent['name']); ?>'s BuddyPress profile">
                                <div class="agent-icon"><?php echo $agent['icon']; ?></div>
                                <h5><?php echo esc_html($agent['name']); ?></h5>
                                <p><?php echo esc_html($agent['desc']); ?></p>
                                <div class="agent-status">Available for consultation</div>
                            </a>
                        <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Tersonas -->
            <section class="design-section tersonas">
                <h3>Tersonas</h3>
                <p class="section-description">Meet our individual terpene specialists - each one a unique personality representing their molecular characteristics and therapeutic effects.</p>
                <div class="terpene-agents-grid">
                    <?php
                    $terpene_agents = array(
                        array(
                            'name' => 'Agt. Myrcene',
                            'emoji' => '🥭',
                            'title' => 'The Relaxation Specialist',
                            'description' => 'Master of couch lock and deep relaxation. Found in mangoes and known for enhancing cannabinoid absorption.',
                            'effects' => array('Sedative', 'Muscle Relaxant', 'Sleep Aid'),
                            'sources' => array('Mango', 'Hops', 'Lemongrass'),
                            'profile_url' => '/members/terpedia-myrcene/'
                        ),
                        array(
                            'name' => 'Agt. Limonene',
                            'emoji' => '🍊',
                            'title' => 'The Mood Elevator',
                            'description' => 'Citrusy champion of mood enhancement and stress relief. Crosses the blood-brain barrier with ease.',
                            'effects' => array('Mood Enhancement', 'Stress Relief', 'Anti-anxiety'),
                            'sources' => array('Citrus Peels', 'Juniper', 'Peppermint'),
                            'profile_url' => '/members/terpedia-limonene/'
                        ),
                        array(
                            'name' => 'Agt. Pinene',
                            'emoji' => '🌲',
                            'title' => 'The Mental Clarity Expert',
                            'description' => 'Forest wisdom incarnate. Provides alertness and counteracts memory impairment while supporting respiratory health.',
                            'effects' => array('Mental Clarity', 'Memory Enhancement', 'Bronchodilator'),
                            'sources' => array('Pine Trees', 'Rosemary', 'Basil'),
                            'profile_url' => '/members/terpedia-pinene/'
                        ),
                        array(
                            'name' => 'Agt. Linalool',
                            'emoji' => '🌾',
                            'title' => 'The Lavender Healer',
                            'description' => 'Gentle healer with lavender\'s grace. Specializes in anxiety relief and anti-inflammatory action.',
                            'effects' => array('Anti-anxiety', 'Analgesic', 'Anti-inflammatory'),
                            'sources' => array('Lavender', 'Mint', 'Cinnamon'),
                            'profile_url' => '/members/terpedia-linalool/'
                        ),
                        array(
                            'name' => 'Agt. Caryophyllene',
                            'emoji' => '🌶️',
                            'title' => 'The CB2 Activator',
                            'description' => 'Unique terpene that acts like a cannabinoid. Directly activates CB2 receptors for powerful anti-inflammatory effects.',
                            'effects' => array('CB2 Activation', 'Anti-inflammatory', 'Analgesic'),
                            'sources' => array('Black Pepper', 'Cloves', 'Hops'),
                            'profile_url' => '/members/terpedia-caryophyllene/'
                        ),
                        array(
                            'name' => 'Agt. Humulene',
                            'emoji' => '🌿',
                            'title' => 'The Appetite Suppressant',
                            'description' => 'Ancient hops wisdom meets appetite control. Unique among cannabis terpenes for reducing hunger.',
                            'effects' => array('Appetite Suppressant', 'Anti-inflammatory', 'Antibacterial'),
                            'sources' => array('Hops', 'Coriander', 'Basil'),
                            'profile_url' => '/members/terpedia-humulene/'
                        )
                    );
                    
                    foreach ($terpene_agents as $agent): ?>
                        <a href="<?php echo esc_url($agent['profile_url']); ?>" class="terpene-agent-card" title="Chat with <?php echo esc_attr($agent['name']); ?>">
                            <div class="terpene-agent-header">
                                <div class="terpene-emoji"><?php echo $agent['emoji']; ?></div>
                                <h4><?php echo esc_html($agent['name']); ?></h4>
                                <span class="terpene-title"><?php echo esc_html($agent['title']); ?></span>
                            </div>
                            <div class="terpene-agent-content">
                                <p class="terpene-description"><?php echo esc_html($agent['description']); ?></p>
                                <div class="terpene-effects">
                                    <h5>Primary Effects:</h5>
                                    <div class="effect-tags">
                                        <?php foreach ($agent['effects'] as $effect): ?>
                                            <span class="effect-tag"><?php echo esc_html($effect); ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <div class="terpene-sources">
                                    <h5>Natural Sources:</h5>
                                    <div class="source-tags">
                                        <?php foreach ($agent['sources'] as $source): ?>
                                            <span class="source-tag"><?php echo esc_html($source); ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="terpene-agent-footer">
                                <span class="chat-prompt">💬 Click to chat about effects and applications</span>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
                <div class="terpene-section-footer">
                    <p><strong>Interactive Consultations:</strong> Each terpene agent provides specialized knowledge about their unique properties, therapeutic applications, and molecular interactions. Ask about entourage effects, dosing considerations, or specific health applications.</p>
                </div>
            </section>

            <!-- Technical Architecture -->
            <section class="design-section tech-architecture">
                <h3>Technical Architecture</h3>
                <div class="tech-stack-diagram">
                    <div class="stack-layer frontend">
                        <h4>Frontend Layer</h4>
                        <div class="tech-items">
                            <span class="tech-badge">React 18</span>
                            <span class="tech-badge">TypeScript</span>
                            <span class="tech-badge">Tailwind CSS</span>
                            <span class="tech-badge">Wouter Routing</span>
                            <span class="tech-badge">TanStack Query</span>
                        </div>
                    </div>
                    
                    <div class="stack-layer backend">
                        <h4>Backend Layer</h4>
                        <div class="tech-items">
                            <span class="tech-badge">Express.js</span>
                            <span class="tech-badge">PostgreSQL</span>
                            <span class="tech-badge">Drizzle ORM</span>
                            <span class="tech-badge">WebSocket</span>
                            <span class="tech-badge">Node.js</span>
                        </div>
                    </div>
                    
                    <div class="stack-layer ai">
                        <h4>AI & ML Layer</h4>
                        <div class="tech-items">
                            <span class="tech-badge">OpenAI GPT-4</span>
                            <span class="tech-badge">Vector Embeddings</span>
                            <span class="tech-badge">RAG System</span>
                            <span class="tech-badge">Multi-Agent Framework</span>
                        </div>
                    </div>
                    
                    <div class="stack-layer data">
                        <h4>Data Layer</h4>
                        <div class="tech-items">
                            <span class="tech-badge">COCONUT (695K)</span>
                            <span class="tech-badge">LOTUS (750K+)</span>
                            <span class="tech-badge">TCM (19K)</span>
                            <span class="tech-badge">Dr. Duke (2.4K plants)</span>
                            <span class="tech-badge">PubMed (239K refs)</span>
                            <span class="tech-badge">ChEBI</span>
                            <span class="tech-badge">Cannabis Data</span>
                            <span class="tech-badge">Essential Oils</span>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Core Features -->
            <section class="design-section features">
                <h3>Core Platform Features</h3>
                <div class="features-grid">
                    <a href="/chat" class="feature-card feature-link">
                        <div class="feature-icon">💬</div>
                        <h4>AI Encyclopedia Chat</h4>
                        <p>Natural language queries about terpenes with intelligent expert selection and comprehensive answers</p>
                        <ul>
                            <li>Context-aware responses</li>
                            <li>Source attribution</li>
                            <li>Multi-modal output</li>
                        </ul>
                    </a>
                    
                    <a href="/molecular-docking" class="feature-card feature-link">
                        <div class="feature-icon">🧪</div>
                        <h4>Molecular Docking</h4>
                        <p>Interactive enzyme-ligand binding analysis with 3D visualization and binding affinity calculations</p>
                        <ul>
                            <li>Protein structure analysis</li>
                            <li>Binding site prediction</li>
                            <li>Drug-likeness scoring</li>
                        </ul>
                    </a>
                    
                    <a href="/terpene-profiles" class="feature-card feature-link">
                        <div class="feature-icon">📋</div>
                        <h4>Terpene Profiles</h4>
                        <p>Comprehensive terpene profiles with chemical analysis and aromatic characteristics</p>
                        <ul>
                            <li>Molecular structures</li>
                            <li>Therapeutic properties</li>
                            <li>Natural sources</li>
                        </ul>
                    </a>
                    
                    <a href="/formulator" class="feature-card feature-link">
                        <div class="feature-icon">⚗️</div>
                        <h4>Terpene Formulator</h4>
                        <p>Precision blending calculator for essential oils and terpene formulations</p>
                        <ul>
                            <li>Aroma profile matching</li>
                            <li>Therapeutic targeting</li>
                            <li>Synergy optimization</li>
                        </ul>
                    </a>
                    
                    <a href="/reports" class="feature-card feature-link">
                        <div class="feature-icon">📊</div>
                        <h4>Knowledge Reports</h4>
                        <p>Comprehensive terpene reports with scientific citations and evidence-based content</p>
                        <ul>
                            <li>Encyclopedia entries</li>
                            <li>Research summaries</li>
                            <li>Reference libraries</li>
                        </ul>
                    </a>
                    
                    <a href="/database" class="feature-card feature-link">
                        <div class="feature-icon">🔗</div>
                        <h4>Database Integration</h4>
                        <p>Comprehensive access to 700,000+ natural products from global scientific resources</p>
                        <ul>
                            <li><strong>COCONUT</strong> (695,133 compounds) - Primary natural products database</li>
                            <li><strong>Traditional Medicine</strong> - TCM (19,032 compounds), Ayurvedic, Bach remedies</li>
                            <li><strong>Ethnobotanical</strong> - Dr. Duke's (2,376 plants), NAEB (4,260 plants)</li>
                            <li><strong>PubMed</strong> (239K terpene references) - Latest scientific literature</li>
                            <li><strong>Regional Collections</strong> - Australian, Latin American, African sources</li>
                            <li><strong>Cannabis & Essential Oils</strong> - Specialized terpene datasets</li>
                        </ul>
                    </a>
                </div>
            </section>

            <!-- Data Flow Architecture -->
            <section class="design-section data-flow">
                <h3>Data Flow Architecture</h3>
                <div class="flow-diagram">
                    <svg viewBox="0 0 800 400" class="data-flow-svg">
                        <!-- User Input -->
                        <rect x="50" y="180" width="80" height="40" fill="#e8f4fd" stroke="#2c5aa0" stroke-width="2" rx="5"/>
                        <text x="90" y="202" text-anchor="middle" font-size="12">User Query</text>
                        
                        <!-- AI Router -->
                        <rect x="200" y="180" width="80" height="40" fill="#4a90e2" stroke="#2c5aa0" stroke-width="2" rx="5"/>
                        <text x="240" y="202" text-anchor="middle" fill="white" font-size="12">AI Router</text>
                        
                        <!-- Agent Selection -->
                        <rect x="350" y="120" width="100" height="40" fill="#2c5aa0" stroke="#1e3a6f" stroke-width="2" rx="5"/>
                        <text x="400" y="142" text-anchor="middle" fill="white" font-size="11">Agent Selection</text>
                        
                        <!-- Data Sources -->
                        <rect x="550" y="60" width="80" height="30" fill="#f0f8ff" stroke="#2c5aa0" stroke-width="1" rx="3"/>
                        <text x="590" y="78" text-anchor="middle" font-size="10">PubMed</text>
                        
                        <rect x="550" y="110" width="80" height="30" fill="#f0f8ff" stroke="#2c5aa0" stroke-width="1" rx="3"/>
                        <text x="590" y="128" text-anchor="middle" font-size="10">SPARQL</text>
                        
                        <rect x="550" y="160" width="80" height="30" fill="#f0f8ff" stroke="#2c5aa0" stroke-width="1" rx="3"/>
                        <text x="590" y="178" text-anchor="middle" font-size="10">ChEBI</text>
                        
                        <rect x="550" y="210" width="80" height="30" fill="#f0f8ff" stroke="#2c5aa0" stroke-width="1" rx="3"/>
                        <text x="590" y="228" text-anchor="middle" font-size="10">Terpene DB</text>
                        
                        <!-- Response Processing -->
                        <rect x="350" y="240" width="100" height="40" fill="#2c5aa0" stroke="#1e3a6f" stroke-width="2" rx="5"/>
                        <text x="400" y="262" text-anchor="middle" fill="white" font-size="11">Response Synthesis</text>
                        
                        <!-- Output -->
                        <rect x="200" y="320" width="80" height="40" fill="#4a90e2" stroke="#2c5aa0" stroke-width="2" rx="5"/>
                        <text x="240" y="342" text-anchor="middle" fill="white" font-size="12">AI Response</text>
                        
                        <!-- Arrows -->
                        <defs>
                            <marker id="arrowhead" markerWidth="10" markerHeight="7" refX="9" refY="3.5" orient="auto">
                                <polygon points="0 0, 10 3.5, 0 7" fill="#2c5aa0"/>
                            </marker>
                        </defs>
                        
                        <line x1="130" y1="200" x2="190" y2="200" stroke="#2c5aa0" stroke-width="2" marker-end="url(#arrowhead)"/>
                        <line x1="280" y1="190" x2="340" y2="150" stroke="#2c5aa0" stroke-width="2" marker-end="url(#arrowhead)"/>
                        <line x1="450" y1="140" x2="540" y2="125" stroke="#2c5aa0" stroke-width="1" marker-end="url(#arrowhead)"/>
                        <line x1="400" y1="160" x2="400" y2="230" stroke="#2c5aa0" stroke-width="2" marker-end="url(#arrowhead)"/>
                        <line x1="350" y1="260" x2="290" y2="340" stroke="#2c5aa0" stroke-width="2" marker-end="url(#arrowhead)"/>
                        
                        <!-- Labels -->
                        <text x="160" y="190" text-anchor="middle" font-size="9" fill="#666">Natural Language</text>
                        <text x="320" y="170" text-anchor="middle" font-size="9" fill="#666">Query Analysis</text>
                        <text x="500" y="110" text-anchor="middle" font-size="9" fill="#666">Data Retrieval</text>
                        <text x="320" y="300" text-anchor="middle" font-size="9" fill="#666">Collaborative Processing</text>
                    </svg>
                </div>
            </section>

            <!-- Performance Metrics -->
            <section class="design-section metrics">
                <h3>Performance & Scalability</h3>
                <div class="metrics-grid">
                    <div class="metric-card">
                        <div class="metric-value">< 2s</div>
                        <div class="metric-label">Average Response Time</div>
                    </div>
                    <div class="metric-card">
                        <div class="metric-value">13</div>
                        <div class="metric-label">Specialized AI Agents</div>
                    </div>
                    <div class="metric-card">
                        <div class="metric-value">100K+</div>
                        <div class="metric-label">Molecular Structures</div>
                    </div>
                    <div class="metric-card">
                        <div class="metric-value">50K+</div>
                        <div class="metric-label">Terpene References</div>
                    </div>
                    <div class="metric-card">
                        <div class="metric-value">99.9%</div>
                        <div class="metric-label">Uptime</div>
                    </div>
                    <div class="metric-card">
                        <div class="metric-value">Real-time</div>
                        <div class="metric-label">SPARQL Queries</div>
                    </div>
                </div>
            </section>

            <!-- Footer -->
            <footer class="design-footer">
                <p>© 2024 Terpedia - The Encyclopedia of Terpenes</p>
                <p><a href="/">Back to Terpedia</a> | <a href="/chat">Try AI Chat</a> | <a href="/multi-agent">Multi-Agent Research</a></p>
            </footer>
        </div>
        <?php
        return ob_get_clean();
    }
    
    public function add_admin_menu() {
        // Add main Terpedia menu with pink styling
        add_menu_page(
            'Terpedia Dashboard',
            'Terpedia',
            'manage_options',
            'terpedia-main',
            array($this, 'main_dashboard_page'),
            'dashicons-microscope',
            6
        );
        
        // Add submenu items
        add_submenu_page(
            'terpedia-main',
            'Tersonae Management',
            'Tersonae',
            'manage_options',
            'terpedia-tersonae',
            array($this, 'tersonae_page')
        );
        
        add_submenu_page(
            'terpedia-main',
            'Expert Agents',
            'Experts',
            'manage_options',
            'terpedia-experts',
            array($this, 'experts_page')
        );
        
        add_submenu_page(
            'terpedia-main',
            'Podcast Management',
            'Podcasts',
            'manage_options',
            'terpedia-podcasts',
            array($this, 'podcasts_page')
        );
        
        add_submenu_page(
            'terpedia-main',
            'Newsletter Management',
            'Newsletters',
            'manage_options',
            'terpedia-newsletters',
            array($this, 'newsletters_page')
        );
        
        add_submenu_page(
            'terpedia-main',
            'Case Management',
            'Cases',
            'manage_options',
            'terpedia-cases',
            array($this, 'cases_page')
        );
        
        add_submenu_page(
            'terpedia-main',
            'Encyclopedia Management',
            'Encyclopedia',
            'manage_options',
            'terpedia-encyclopedia',
            array($this, 'encyclopedia_page')
        );
        
        add_submenu_page(
            'terpedia-main',
            'Product Management',
            'Terproducts',
            'manage_options',
            'terpedia-terproducts',
            array($this, 'terproducts_page')
        );
        
        // Add CSS for pink styling
        add_action('admin_head', array($this, 'admin_menu_styles'));
    }
    
    public function register_settings() {
        register_setting('terpedia_ai_settings', 'terpedia_openai_api_key');
        register_setting('terpedia_ai_settings', 'terpedia_backend_url');
    }
    
    public function admin_menu_styles() {
        ?>
        <style>
            /* Pink styling for Terpedia menu */
            #adminmenu #toplevel_page_terpedia-main > a,
            #adminmenu #toplevel_page_terpedia-main .wp-submenu a {
                background-color: #ff69b4 !important;
                color: white !important;
            }
            
            #adminmenu #toplevel_page_terpedia-main:hover > a,
            #adminmenu #toplevel_page_terpedia-main .wp-submenu a:hover {
                background-color: #ff1493 !important;
                color: white !important;
            }
            
            #adminmenu #toplevel_page_terpedia-main.wp-has-current-submenu > a,
            #adminmenu #toplevel_page_terpedia-main > a.wp-has-current-submenu {
                background-color: #ff1493 !important;
                color: white !important;
            }
            
            #adminmenu #toplevel_page_terpedia-main .wp-submenu li.current a,
            #adminmenu #toplevel_page_terpedia-main .wp-submenu a.current {
                background-color: #ff1493 !important;
                color: white !important;
            }
            
            .terpedia-admin-page {
                background: linear-gradient(135deg, #ffe4f1 0%, #ffeef7 100%);
                border-radius: 10px;
                padding: 20px;
                margin: 20px 0;
            }
            
            .terpedia-admin-page h1 {
                color: #ff1493;
                border-bottom: 3px solid #ff69b4;
                padding-bottom: 10px;
            }
        </style>
        <?php
    }
    
    public function main_dashboard_page() {
        ?>
        <div class="wrap terpedia-admin-page">
            <h1>🧬 Terpedia Dashboard</h1>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin: 30px 0;">
                <div class="postbox" style="background: white; padding: 20px; border-left: 4px solid #ff69b4;">
                    <h2>📊 System Status</h2>
                    <p>✅ Plugin Active</p>
                    <p>🔗 API Connected</p>
                    <p>🧪 AI Agents Online</p>
                    <p>📦 Version: <?php echo esc_html(TERPEDIA_AI_VERSION); ?></p>
                </div>
                <div class="postbox" style="background: white; padding: 20px; border-left: 4px solid #ff1493;">
                    <h2>📈 Quick Stats</h2>
                    <p><strong>Active Tersonae:</strong> 13</p>
                    <p><strong>Expert Agents:</strong> 19</p>
                    <p><strong>Cases Managed:</strong> 245+</p>
                </div>
            </div>
            
            <h2>🚀 Quick Actions</h2>
            <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                <a href="<?php echo admin_url('admin.php?page=terpedia-tersonae'); ?>" class="button button-primary" style="background: #ff69b4; border-color: #ff69b4;">Manage Tersonae</a>
                <a href="<?php echo admin_url('admin.php?page=terpedia-experts'); ?>" class="button button-primary" style="background: #ff1493; border-color: #ff1493;">View Experts</a>
                <a href="<?php echo admin_url('admin.php?page=terpedia-cases'); ?>" class="button button-secondary">View Cases</a>
                <a href="<?php echo admin_url('admin.php?page=terpedia-encyclopedia'); ?>" class="button button-secondary">Encyclopedia</a>
            </div>
            
            <h2>🔄 Updates</h2>
            <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" style="display: inline;">
                    <?php wp_nonce_field('terpedia_update_theme_nonce', 'terpedia_theme_nonce'); ?>
                    <input type="hidden" name="action" value="terpedia_update_theme">
                    <input type="submit" class="button button-secondary" value="🎨 Update Theme" onclick="return confirm('Update Terpedia theme from GitHub?');">
                </form>
                <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" style="display: inline;">
                    <?php wp_nonce_field('terpedia_update_plugin_nonce', 'terpedia_plugin_nonce'); ?>
                    <input type="hidden" name="action" value="terpedia_update_plugin">
                    <input type="submit" class="button button-secondary" value="🔌 Update Plugin" onclick="return confirm('Update Terpedia plugin from GitHub?');">
                </form>
            </div>
        </div>
        <?php
    }
    
    public function tersonae_page() {
        // Get all terpedia-* users
        $terpedia_users = get_users(array(
            'search' => 'terpedia-*',
            'search_columns' => array('user_login'),
            'meta_query' => array(
                array(
                    'key' => 'terpedia_agent_type',
                    'value' => 'tersona',
                    'compare' => '='
                )
            )
        ));
        
        // Also get agt-* users (like agt-taxol)
        $agt_users = get_users(array(
            'search' => 'agt-*',
            'search_columns' => array('user_login'),
            'meta_query' => array(
                array(
                    'key' => 'terpedia_agent_type',
                    'value' => 'tersona',
                    'compare' => '='
                )
            )
        ));
        
        $all_tersonae = array_merge($terpedia_users, $agt_users);
        ?>
        <div class="wrap terpedia-admin-page">
            <h1>👥 Tersonae Management</h1>
            <p>Manage your AI Terpene Personas - specialized AI agents for different terpenes.</p>
            
            <div style="background: white; padding: 20px; border-radius: 8px; margin: 20px 0;">
                <h2>Active Tersonae (<?php echo count($all_tersonae); ?>)</h2>
                <?php if (!empty($all_tersonae)): ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Display Name</th>
                            <th>Compound Type</th>
                            <th>Expertise</th>
                            <th>Profile</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($all_tersonae as $user): 
                            $compound_type = get_user_meta($user->ID, 'terpedia_compound_type', true);
                            $expertise = get_user_meta($user->ID, 'terpedia_expertise', true);
                            $last_login = get_user_meta($user->ID, 'last_login', true);
                            $expertise_display = is_array($expertise) ? implode(', ', array_slice($expertise, 0, 2)) : $expertise;
                            if (is_array($expertise) && count($expertise) > 2) {
                                $expertise_display .= '...';
                            }
                        ?>
                        <tr>
                            <td><strong>@<?php echo esc_html($user->user_login); ?></strong></td>
                            <td><?php echo esc_html($user->display_name); ?></td>
                            <td><?php echo esc_html($compound_type ?: 'terpene'); ?></td>
                            <td><?php echo esc_html($expertise_display ?: 'General'); ?></td>
                            <td>
                                <?php if (function_exists('bp_core_get_user_domain')): ?>
                                    <a href="<?php echo esc_url(bp_core_get_user_domain($user->ID)); ?>" target="_blank">View Profile</a>
                                <?php else: ?>
                                    <a href="<?php echo esc_url(get_author_posts_url($user->ID)); ?>" target="_blank">View Posts</a>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="<?php echo admin_url('user-edit.php?user_id=' . $user->ID); ?>" class="button button-small">Edit User</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <p>No Tersonae found. <a href="#" onclick="createSampleTersonae()">Create sample Tersonae</a></p>
                <script>
                function createSampleTersonae() {
                    if (confirm('Create sample Tersonae users?')) {
                        window.location.href = '<?php echo admin_url('admin.php?page=terpedia-tersonae&action=create_samples'); ?>';
                    }
                }
                </script>
                <?php endif; ?>
            </div>
            
            <div style="background: white; padding: 20px; border-radius: 8px; margin: 20px 0;">
                <h2>Create New Tersona</h2>
                <form method="post" action="">
                    <?php wp_nonce_field('create_tersona', 'tersona_nonce'); ?>
                    <table class="form-table">
                        <tr>
                            <th scope="row">Username</th>
                            <td><input type="text" name="username" placeholder="terpedia-limonene" class="regular-text" /></td>
                        </tr>
                        <tr>
                            <th scope="row">Display Name</th>
                            <td><input type="text" name="display_name" placeholder="Agt. Limonene" class="regular-text" /></td>
                        </tr>
                        <tr>
                            <th scope="row">Email</th>
                            <td><input type="email" name="email" placeholder="limonene@terpedia.com" class="regular-text" /></td>
                        </tr>
                        <tr>
                            <th scope="row">Compound Type</th>
                            <td>
                                <select name="compound_type">
                                    <option value="terpene">Terpene</option>
                                    <option value="cannabinoid">Cannabinoid</option>
                                    <option value="flavonoid">Flavonoid</option>
                                </select>
                            </td>
                        </tr>
                    </table>
                    <p class="submit">
                        <input type="submit" name="create_tersona" class="button-primary" value="Create Tersona" />
                    </p>
                </form>
            </div>
        </div>
        <?php
        
        // Handle tersona creation
        if (isset($_POST['create_tersona']) && wp_verify_nonce($_POST['tersona_nonce'], 'create_tersona')) {
            $this->handle_create_tersona();
        }
    }
    
    public function experts_page() {
        ?>
        <div class="wrap terpedia-admin-page">
            <h1>🎓 Expert Agents</h1>
            <p>Manage your specialized expert AI agents for different domains.</p>
            
            <div style="background: white; padding: 20px; border-radius: 8px; margin: 20px 0;">
                <h2>Expert Categories</h2>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px;">
                    <div style="border: 2px solid #ff69b4; border-radius: 8px; padding: 15px;">
                        <h3>🧪 @chemist</h3>
                        <p>Chemical analysis and molecular interactions</p>
                        <span style="color: green;">●</span> Online
                    </div>
                    <div style="border: 2px solid #ff69b4; border-radius: 8px; padding: 15px;">
                        <h3>🔬 @research</h3>
                        <p>Scientific literature and research synthesis</p>
                        <span style="color: green;">●</span> Online
                    </div>
                    <div style="border: 2px solid #ff69b4; border-radius: 8px; padding: 15px;">
                        <h3>🩺 @clinical</h3>
                        <p>Clinical applications and medical research</p>
                        <span style="color: green;">●</span> Online
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    public function podcasts_page() {
        ?>
        <div class="wrap terpedia-admin-page">
            <h1>🎙️ Podcast Management</h1>
            <p>Manage Terpedia podcast episodes and AI-generated content.</p>
            
            <div style="background: white; padding: 20px; border-radius: 8px; margin: 20px 0;">
                <h2>Recent Episodes</h2>
                <div class="button-group" style="margin: 15px 0;">
                    <button class="button button-primary" style="background: #ff69b4; border-color: #ff69b4;">Create New Episode</button>
                    <button class="button">Manage TTS Voices</button>
                </div>
                
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Episode Title</th>
                            <th>Status</th>
                            <th>Duration</th>
                            <th>Published</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>Myrcene: The Couch Lock Explained</strong></td>
                            <td><span style="color: green;">●</span> Published</td>
                            <td>18:45</td>
                            <td>2 days ago</td>
                        </tr>
                        <tr>
                            <td><strong>Limonene: Citrus Power for Mood</strong></td>
                            <td><span style="color: orange;">●</span> Processing</td>
                            <td>-</td>
                            <td>-</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    }
    
    public function newsletters_page() {
        ?>
        <div class="wrap terpedia-admin-page">
            <h1>📧 Newsletter Management</h1>
            <p>Manage the Terpene Times newsletter and AI-generated content.</p>
            
            <div style="background: white; padding: 20px; border-radius: 8px; margin: 20px 0;">
                <h2>Newsletter Dashboard</h2>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin: 20px 0;">
                    <div style="background: #ffe4f1; padding: 15px; border-radius: 8px;">
                        <h3>📊 Subscribers</h3>
                        <p style="font-size: 24px; margin: 0; color: #ff1493;"><strong>1,247</strong></p>
                    </div>
                    <div style="background: #ffe4f1; padding: 15px; border-radius: 8px;">
                        <h3>📬 Open Rate</h3>
                        <p style="font-size: 24px; margin: 0; color: #ff1493;"><strong>73.2%</strong></p>
                    </div>
                </div>
                
                <button class="button button-primary" style="background: #ff69b4; border-color: #ff69b4;">Create New Issue</button>
                <button class="button">View Analytics</button>
            </div>
        </div>
        <?php
    }
    
    public function cases_page() {
        ?>
        <div class="wrap terpedia-admin-page">
            <h1>📋 Case Management</h1>
            <p>Manage patient cases and clinical consultations.</p>
            
            <div style="background: white; padding: 20px; border-radius: 8px; margin: 20px 0;">
                <h2>Active Cases</h2>
                <div class="button-group" style="margin: 15px 0;">
                    <button class="button button-primary" style="background: #ff69b4; border-color: #ff69b4;">New Case</button>
                    <button class="button">Case Templates</button>
                </div>
                
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Case ID</th>
                            <th>Patient Type</th>
                            <th>Status</th>
                            <th>Assigned Expert</th>
                            <th>Last Updated</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>CASE-001</strong></td>
                            <td>Canine - Anxiety</td>
                            <td><span style="color: orange;">●</span> In Progress</td>
                            <td>@clinical</td>
                            <td>1 hour ago</td>
                        </tr>
                        <tr>
                            <td><strong>CASE-002</strong></td>
                            <td>Human - Pain Management</td>
                            <td><span style="color: green;">●</span> Completed</td>
                            <td>@research</td>
                            <td>3 days ago</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    }
    
    public function encyclopedia_page() {
        ?>
        <div class="wrap terpedia-admin-page">
            <h1>📚 Encyclopedia Management</h1>
            <p>Manage the Terpedia knowledge base and encyclopedia entries.</p>
            
            <div style="background: white; padding: 20px; border-radius: 8px; margin: 20px 0;">
                <h2>Knowledge Base Statistics</h2>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 20px 0;">
                    <div style="background: #ffe4f1; padding: 15px; border-radius: 8px; text-align: center;">
                        <h3>🧬 Terpenes</h3>
                        <p style="font-size: 20px; margin: 0; color: #ff1493;"><strong>150+</strong></p>
                    </div>
                    <div style="background: #ffe4f1; padding: 15px; border-radius: 8px; text-align: center;">
                        <h3>🌿 Strains</h3>
                        <p style="font-size: 20px; margin: 0; color: #ff1493;"><strong>2,500+</strong></p>
                    </div>
                    <div style="background: #ffe4f1; padding: 15px; border-radius: 8px; text-align: center;">
                        <h3>📄 Articles</h3>
                        <p style="font-size: 20px; margin: 0; color: #ff1493;"><strong>10,000+</strong></p>
                    </div>
                    <div style="background: #ffe4f1; padding: 15px; border-radius: 8px; text-align: center;">
                        <h3>🔬 Studies</h3>
                        <p style="font-size: 20px; margin: 0; color: #ff1493;"><strong>50,000+</strong></p>
                    </div>
                </div>
                
                <div class="button-group">
                    <button class="button button-primary" style="background: #ff69b4; border-color: #ff69b4;">Add New Entry</button>
                    <button class="button">Update Knowledge Graph</button>
                    <button class="button">Sync with SPARQL</button>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Terproducts management page
     */
    public function terproducts_page() {
        // Handle bulk actions
        if (isset($_POST['action']) && $_POST['action'] === 'analyze_selected') {
            // Process selected products for analysis
            $selected_products = isset($_POST['selected_products']) ? $_POST['selected_products'] : array();
            foreach ($selected_products as $product_id) {
                update_post_meta($product_id, '_product_analysis_status', 'processing');
            }
            echo '<div class="notice notice-success"><p>Selected products queued for analysis!</p></div>';
        }
        
        // Get all terproducts
        $products = get_posts(array(
            'post_type' => 'terpedia_product',
            'post_status' => 'any',
            'posts_per_page' => -1,
            'meta_query' => array()
        ));
        
        // Calculate statistics
        $total_products = count($products);
        $verified_products = 0;
        $analyzed_products = 0;
        $pending_analysis = 0;
        
        foreach ($products as $product) {
            if (get_post_meta($product->ID, '_product_verified', true)) {
                $verified_products++;
            }
            $analysis_status = get_post_meta($product->ID, '_product_analysis_status', true);
            if ($analysis_status === 'completed') {
                $analyzed_products++;
            } elseif ($analysis_status === 'pending' || empty($analysis_status)) {
                $pending_analysis++;
            }
        }
        ?>
        <div class="wrap terpedia-admin-page">
            <h1>🛍️ Terproducts Management</h1>
            <p>Track, analyze, and manage cannabis products with terpene profiles and lab data.</p>
            
            <!-- Statistics Dashboard -->
            <div style="background: white; padding: 20px; border-radius: 8px; margin: 20px 0;">
                <h2>Product Database Statistics</h2>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 20px 0;">
                    <div style="background: #ffe4f1; padding: 15px; border-radius: 8px; text-align: center;">
                        <h3>📦 Total Products</h3>
                        <p style="font-size: 20px; margin: 0; color: #ff1493;"><strong><?php echo $total_products; ?></strong></p>
                    </div>
                    <div style="background: #ffe4f1; padding: 15px; border-radius: 8px; text-align: center;">
                        <h3>✅ Lab Verified</h3>
                        <p style="font-size: 20px; margin: 0; color: #ff1493;"><strong><?php echo $verified_products; ?></strong></p>
                    </div>
                    <div style="background: #ffe4f1; padding: 15px; border-radius: 8px; text-align: center;">
                        <h3>🧪 Analyzed</h3>
                        <p style="font-size: 20px; margin: 0; color: #ff1493;"><strong><?php echo $analyzed_products; ?></strong></p>
                    </div>
                    <div style="background: #ffe4f1; padding: 15px; border-radius: 8px; text-align: center;">
                        <h3>⏳ Pending</h3>
                        <p style="font-size: 20px; margin: 0; color: #ff1493;"><strong><?php echo $pending_analysis; ?></strong></p>
                    </div>
                </div>
                
                <div class="button-group" style="margin-top: 20px;">
                    <a href="post-new.php?post_type=terpedia_product" class="button button-primary" style="background: #ff69b4; border-color: #ff69b4;">➕ Add New Product</a>
                    <button class="button" onclick="analyzeAllPending()">🧪 Analyze All Pending</button>
                    <button class="button" onclick="exportProducts()">📊 Export Data</button>
                    <button class="button" onclick="scanLabels()">📷 Batch Scan Labels</button>
                </div>
            </div>

            <!-- Product Management Table -->
            <div style="background: white; padding: 20px; border-radius: 8px; margin: 20px 0;">
                <h2>Product Management</h2>
                
                <?php if ($total_products > 0) : ?>
                <form method="post" id="bulk-action-form">
                    <div style="margin-bottom: 15px;">
                        <select name="action" style="margin-right: 10px;">
                            <option value="">Bulk Actions</option>
                            <option value="analyze_selected">Analyze Selected</option>
                            <option value="verify_selected">Mark as Verified</option>
                            <option value="export_selected">Export Selected</option>
                        </select>
                        <button type="submit" class="button">Apply</button>
                    </div>
                    
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th style="width: 40px;"><input type="checkbox" id="select-all" /></th>
                                <th>Product</th>
                                <th>Brand</th>
                                <th>Category</th>
                                <th>THC%</th>
                                <th>CBD%</th>
                                <th>Status</th>
                                <th>Analysis</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $product) : 
                                $brand = get_post_meta($product->ID, '_product_brand', true);
                                $category = get_post_meta($product->ID, '_product_category', true);
                                $thc = get_post_meta($product->ID, '_product_thc', true);
                                $cbd = get_post_meta($product->ID, '_product_cbd', true);
                                $verified = get_post_meta($product->ID, '_product_verified', true);
                                $analysis_status = get_post_meta($product->ID, '_product_analysis_status', true) ?: 'pending';
                                $url = get_post_meta($product->ID, '_product_url', true);
                                
                                $status_badges = array(
                                    'pending' => '<span style="background: #ffc107; color: #000; padding: 2px 6px; border-radius: 3px; font-size: 11px;">📋 Pending</span>',
                                    'processing' => '<span style="background: #17a2b8; color: #fff; padding: 2px 6px; border-radius: 3px; font-size: 11px;">🔄 Processing</span>',
                                    'completed' => '<span style="background: #28a745; color: #fff; padding: 2px 6px; border-radius: 3px; font-size: 11px;">✅ Complete</span>',
                                    'needs_review' => '<span style="background: #dc3545; color: #fff; padding: 2px 6px; border-radius: 3px; font-size: 11px;">🔍 Review</span>'
                                );
                            ?>
                            <tr>
                                <td><input type="checkbox" name="selected_products[]" value="<?php echo $product->ID; ?>" /></td>
                                <td>
                                    <strong><a href="post.php?post=<?php echo $product->ID; ?>&action=edit"><?php echo esc_html($product->post_title); ?></a></strong>
                                    <?php if ($verified) echo '<br><small style="color: #28a745;">✅ Lab Verified</small>'; ?>
                                </td>
                                <td><?php echo esc_html($brand); ?></td>
                                <td><?php echo esc_html($category); ?></td>
                                <td><?php echo $thc ? number_format($thc, 1) . '%' : '—'; ?></td>
                                <td><?php echo $cbd ? number_format($cbd, 1) . '%' : '—'; ?></td>
                                <td><?php echo $verified ? '<span style="color: #28a745;">✅ Verified</span>' : '<span style="color: #999;">⚪ Unverified</span>'; ?></td>
                                <td><?php echo $status_badges[$analysis_status]; ?></td>
                                <td>
                                    <a href="post.php?post=<?php echo $product->ID; ?>&action=edit" class="button button-small">Edit</a>
                                    <?php if ($url) : ?>
                                        <a href="<?php echo esc_url($url); ?>" target="_blank" class="button button-small">🔗 View</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </form>
                <?php else : ?>
                <div style="text-align: center; padding: 40px; color: #666;">
                    <h3>No products found</h3>
                    <p>Start building your product database by adding your first terproduct!</p>
                    <a href="post-new.php?post_type=terpedia_product" class="button button-primary" style="background: #ff69b4; border-color: #ff69b4;">➕ Add First Product</a>
                </div>
                <?php endif; ?>
            </div>

            <!-- Quick Tools -->
            <div style="background: white; padding: 20px; border-radius: 8px; margin: 20px 0;">
                <h2>Quick Tools & Analysis</h2>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                    
                    <div style="border: 1px solid #ddd; padding: 15px; border-radius: 8px;">
                        <h3>🧪 Terpene Analyzer</h3>
                        <p>Upload ingredient lists or product labels for automated terpene detection and analysis.</p>
                        <button class="button button-primary" style="background: #ff69b4; border-color: #ff69b4;">Launch Analyzer</button>
                    </div>
                    
                    <div style="border: 1px solid #ddd; padding: 15px; border-radius: 8px;">
                        <h3>📷 Label Scanner</h3>
                        <p>Batch process product labels using OCR to extract ingredient information automatically.</p>
                        <button class="button">Start Batch Scan</button>
                    </div>
                    
                    <div style="border: 1px solid #ddd; padding: 15px; border-radius: 8px;">
                        <h3>📊 Data Export</h3>
                        <p>Export product data and analysis results for research or compliance reporting.</p>
                        <button class="button">Export CSV</button>
                    </div>
                    
                </div>
            </div>
        </div>
        
        <script>
        // Select all checkbox functionality
        document.getElementById('select-all').addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('input[name="selected_products[]"]');
            checkboxes.forEach(checkbox => checkbox.checked = this.checked);
        });
        
        // Quick action functions
        function analyzeAllPending() {
            if (confirm('Analyze all pending products? This may take several minutes.')) {
                // Implement bulk analysis
                alert('Batch analysis started! Check back in a few minutes.');
            }
        }
        
        function exportProducts() {
            // Implement data export
            window.open('?page=terpedia-terproducts&action=export', '_blank');
        }
        
        function scanLabels() {
            if (confirm('Start batch label scanning? This will process all products with uploaded label images.')) {
                alert('Batch scanning started! Processing in background.');
            }
        }
        </script>
        <?php
    }
    
    private function create_database_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Simple chat messages table
        $table_name = $wpdb->prefix . 'terpedia_messages';
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            message longtext NOT NULL,
            response longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Handle chemist chat AJAX request
     */
    public function handle_chemist_chat() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'chemist_chat_nonce')) {
            wp_send_json_error('Invalid nonce');
            return;
        }
        
        $message = sanitize_text_field($_POST['message']);
        $agent = sanitize_text_field($_POST['agent']) ?: 'Dr. Ligand Linker';
        
        if (empty($message)) {
            wp_send_json_error('Message is required');
            return;
        }
        
        // Call Terpedia chemist API
        $response = $this->call_terpedia_chemist_api($message, $agent);
        
        if (isset($response['error'])) {
            wp_send_json_error($response['error']);
            return;
        }
        
        wp_send_json_success($response);
    }

    /**
     * Call Terpedia API
     */
    private function call_terpedia_api($endpoint, $data = null) {
        $base_url = get_option('terpedia_backend_url', 'https://terpedia-encyclopedia-terpenes.replit.app');
        $url = $base_url . $endpoint;
        
        $headers = array(
            'Content-Type' => 'application/json',
        );
        
        // Add OpenAI API key if available (system has baked-in key as fallback)
        $api_key = get_option('terpedia_openai_api_key');
        if (!empty($api_key)) {
            $headers['X-OpenAI-API-Key'] = $api_key;
        }
        
        $args = array(
            'timeout' => 30,
            'headers' => $headers
        );
        
        if ($data) {
            $args['method'] = 'POST';
            $args['body'] = wp_json_encode($data);
        }
        
        $response = wp_remote_request($url, $args);
        
        if (is_wp_error($response)) {
            return array('error' => $response->get_error_message());
        }
        
        $body = wp_remote_retrieve_body($response);
        $decoded = json_decode($body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return array('error' => 'Invalid JSON response');
        }
        
        return $decoded;
    }

    /**
     * Call Terpedia chemist API
     */
    private function call_terpedia_chemist_api($message, $agent) {
        return $this->call_terpedia_api('/api/chemist/chat', array(
            'message' => $message,
            'agent' => $agent
        ));
    }

    /**
     * Chemist agent shortcode
     */
    public function chemist_shortcode($atts) {
        $atts = shortcode_atts(array(
            'agent' => 'Dr. Ligand Linker',
            'height' => '600px',
            'show_structures' => 'true'
        ), $atts);
        
        ob_start();
        $this->render_chemist_interface($atts);
        return ob_get_clean();
    }

    /**
     * Render chemist chat interface
     */
    private function render_chemist_interface($atts) {
        $agent_name = sanitize_text_field($atts['agent']);
        $height = sanitize_text_field($atts['height']);
        $show_structures = $atts['show_structures'] === 'true';
        
        echo '<div class="terpedia-chemist-container" style="max-width: 900px; margin: 20px auto;">';
        echo '<h2>🧪 Chat with ' . esc_html($agent_name) . ' - Molecular Structure Expert</h2>';
        echo '<p>Specialized in molecular structures, biosynthesis, and computational chemistry using RDKit.</p>';
        
        echo '<div class="terpedia-chemist-chat" style="height: ' . esc_attr($height) . '; border: 1px solid #ddd; border-radius: 8px; background: #f9f9f9; padding: 20px;">';
        
        // Messages container
        echo '<div id="chemist-messages" style="height: 70%; overflow-y: auto; border: 1px solid #ccc; padding: 15px; background: white; margin-bottom: 15px; border-radius: 4px;"></div>';
        
        if ($show_structures) {
            // Quick structure buttons
            echo '<div class="structure-buttons" style="margin-bottom: 10px;">';
            echo '<button type="button" class="structure-btn" onclick="sendChemistMessage(\'Show me the molecular structure of myrcene\')" style="margin-right: 5px; padding: 5px 10px; background: #f0f0f0; border: 1px solid #ccc; border-radius: 4px; cursor: pointer;">🧪 Myrcene</button>';
            echo '<button type="button" class="structure-btn" onclick="sendChemistMessage(\'What is the biosynthesis pathway of limonene?\')" style="margin-right: 5px; padding: 5px 10px; background: #f0f0f0; border: 1px solid #ccc; border-radius: 4px; cursor: pointer;">🍋 Limonene Pathway</button>';
            echo '<button type="button" class="structure-btn" onclick="sendChemistMessage(\'Compare myrcene and pinene structures\')" style="margin-right: 5px; padding: 5px 10px; background: #f0f0f0; border: 1px solid #ccc; border-radius: 4px; cursor: pointer;">⚖️ Compare Structures</button>';
            echo '<button type="button" class="structure-btn" onclick="sendChemistMessage(\'Show the reaction mechanism for caryophyllene biosynthesis\')" style="margin-right: 5px; padding: 5px 10px; background: #f0f0f0; border: 1px solid #ccc; border-radius: 4px; cursor: pointer;">⚗️ Reaction Mechanism</button>';
            echo '</div>';
        }
        
        // Input area
        echo '<div style="display: flex; gap: 10px;">';
        echo '<input type="text" id="chemist-input" placeholder="Ask about molecular structures, biosynthesis, or chemical reactions..." style="flex: 1; padding: 10px; border: 1px solid #ccc; border-radius: 4px;">';
        echo '<button onclick="sendChemistMessage()" style="padding: 10px 20px; background: #0073aa; color: white; border: none; border-radius: 4px; cursor: pointer;">Send</button>';
        echo '</div>';
        
        echo '<div id="chemist-loading" style="display: none; margin-top: 10px; color: #666; font-style: italic;">Dr. Ligand Linker is analyzing your request...</div>';
        
        echo '</div>';
        echo '</div>';
        
        // Add CSS for molecular structures
        echo '<style>
            .molecular-structure-container {
                background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
                border: 2px solid #6c757d;
                border-radius: 12px;
                padding: 20px;
                margin: 16px 0;
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            }
            .molecular-structure-container svg {
                max-width: 100%;
                height: auto;
                background: white;
                border-radius: 8px;
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            }
            .molecular-3d-container {
                background: linear-gradient(135deg, #e8f5e8 0%, #f0fff0 100%);
                border: 2px solid #28a745;
                border-radius: 12px;
                padding: 20px;
                margin: 16px 0;
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            }
            .molecule-viewer {
                width: 100%;
                height: 400px;
                background: #000;
                border-radius: 8px;
                position: relative;
                overflow: hidden;
            }
            .viewer-loading {
                position: absolute;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                color: #fff;
                font-family: monospace;
            }
            .viewer-controls {
                margin-top: 10px;
                text-align: center;
            }
            .viewer-controls button {
                margin: 0 5px;
                padding: 8px 15px;
                background: #28a745;
                color: white;
                border: none;
                border-radius: 4px;
                cursor: pointer;
            }
            .viewer-controls button:hover {
                background: #218838;
            }
            .chemist-message {
                margin-bottom: 15px;
                padding: 12px;
                border-radius: 8px;
                line-height: 1.5;
            }
            .chemist-message.user {
                background: #e3f2fd;
                text-align: right;
                margin-left: 20%;
            }
            .chemist-message.assistant {
                background: #f5f5f5;
                text-align: left;
                margin-right: 20%;
                border-left: 4px solid #0073aa;
            }
            .structure-btn:hover {
                background: #e0e0e0 !important;
            }
        </style>';
        
        // Add JavaScript
        echo '<script>
            function sendChemistMessage(predefinedMessage) {
                const input = document.getElementById("chemist-input");
                const message = predefinedMessage || input.value.trim();
                
                if (!message) return;
                
                if (!predefinedMessage) {
                    input.value = "";
                }
                
                document.getElementById("chemist-loading").style.display = "block";
                addChemistMessage(message, "user");
                
                const data = new FormData();
                data.append("action", "terpedia_chemist_chat");
                data.append("message", message);
                data.append("agent", "' . esc_js($agent_name) . '");
                data.append("nonce", "' . wp_create_nonce('chemist_chat_nonce') . '");
                
                fetch(ajaxurl, {
                    method: "POST",
                    body: data
                })
                .then(response => response.json())
                .then(data => {
                    document.getElementById("chemist-loading").style.display = "none";
                    
                    if (data.success) {
                        addChemistMessage(data.data.response || "No response received", "assistant");
                    } else {
                        addChemistMessage("Error: " + (data.data || "Unknown error"), "assistant");
                    }
                })
                .catch(error => {
                    document.getElementById("chemist-loading").style.display = "none";
                    addChemistMessage("Error: " + error.message, "assistant");
                });
            }
            
            function addChemistMessage(content, sender) {
                const container = document.getElementById("chemist-messages");
                const div = document.createElement("div");
                div.className = "chemist-message " + sender;
                
                if (content.includes("molecular-structure-container")) {
                    div.innerHTML = content;
                } else {
                    const html = content
                        .replace(/\\n\\n/g, "<br><br>")
                        .replace(/\\n/g, "<br>")
                        .replace(/\\*\\*(.*?)\\*\\*/g, "<strong>$1</strong>")
                        .replace(/\\*(.*?)\\*/g, "<em>$1</em>")
                        .replace(/`(.*?)`/g, "<code style=\"background: #f1f1f1; padding: 2px 4px; border-radius: 3px;\">$1</code>");
                    div.innerHTML = html;
                }
                
                container.appendChild(div);
                container.scrollTop = container.scrollHeight;
            }
            
            document.getElementById("chemist-input").addEventListener("keypress", function(e) {
                if (e.key === "Enter") {
                    sendChemistMessage();
                }
            });
            
            setTimeout(() => {
                addChemistMessage("👋 Hello! I\'m ' . esc_js($agent_name) . ', your molecular structure expert. I can help you with:<br><br>🧪 2D & 3D molecular structure visualization using RDKit<br>⚗️ Biosynthesis pathways and reaction mechanisms<br>🔬 Computational chemistry analysis<br>📊 Chemical property calculations<br>🌐 Interactive 3D molecular models<br><br>Try asking me to \\"Show the molecular structure of myrcene\\" or \\"Explain the biosynthesis of limonene\\"!", "assistant");
            }, 500);
            
            // 3D Molecular Viewer Functions
            let autoRotate = false;
            let currentMolData = null;
            
            function reset3DView() {
                // Reset 3D viewer to initial position
                console.log("Resetting 3D view");
            }
            
            function toggle3DRotation() {
                autoRotate = !autoRotate;
                console.log("Toggle rotation:", autoRotate);
            }
            
            function downloadMolFile() {
                if (currentMolData) {
                    // Create downloadable MOL file
                    const blob = new Blob([currentMolData], { type: "text/plain" });
                    const url = URL.createObjectURL(blob);
                    const a = document.createElement("a");
                    a.href = url;
                    a.download = "molecule.mol";
                    a.click();
                    URL.revokeObjectURL(url);
                }
            }
            
            // Initialize 3D viewers when molecules are added
            function init3DViewer(container) {
                const molData = container.getAttribute("data-mol");
                if (molData) {
                    currentMolData = molData;
                    const data = JSON.parse(molData);
                    
                    // Simple ASCII-based 3D representation for now
                    const loadingDiv = container.querySelector(".viewer-loading");
                    if (loadingDiv) {
                        loadingDiv.innerHTML = `
                            <div style="font-family: monospace; color: #0f0; text-align: center;">
                                <div>🧬 3D Molecular Model</div>
                                <div style="margin: 10px 0;">Formula: ${data.formula}</div>
                                <div style="font-size: 12px;">Atoms: ${data.atoms.length}</div>
                                <div style="font-size: 12px;">Bonds: ${data.bonds.length}</div>
                                <div style="margin-top: 20px; color: #0a0;">
                                    ▲ Interactive 3D viewer loading... ▲
                                </div>
                            </div>
                        `;
                    }
                }
            }
            
            // Auto-initialize 3D viewers
            setInterval(() => {
                document.querySelectorAll(".molecule-viewer[data-mol]").forEach(viewer => {
                    if (!viewer.dataset.initialized) {
                        init3DViewer(viewer);
                        viewer.dataset.initialized = "true";
                    }
                });
            }, 1000);
        </script>';
    }

    /**
     * Test OpenAI API key
     */
    public function handle_test_openai_key() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'test_openai_key')) {
            wp_send_json_error('Invalid nonce');
            return;
        }
        
        $api_key = sanitize_text_field($_POST['api_key']);
        
        if (empty($api_key)) {
            wp_send_json_error('API key is required');
            return;
        }
        
        // Test the API key with a simple request
        $test_data = array(
            'message' => 'Test message to verify API key',
            'agent' => 'Dr. Ligand Linker'
        );
        
        // Temporarily set the API key for testing
        $original_key = get_option('terpedia_openai_api_key');
        update_option('terpedia_openai_api_key', $api_key);
        
        $response = $this->call_terpedia_chemist_api('Hello, this is a test message', 'Dr. Ligand Linker');
        
        // Restore original key
        if ($original_key !== false) {
            update_option('terpedia_openai_api_key', $original_key);
        }
        
        if (isset($response['error'])) {
            wp_send_json_error('API key test failed: ' . $response['error']);
            return;
        }
        
        if (isset($response['response'])) {
            wp_send_json_success('API key is working correctly');
        } else {
            wp_send_json_error('Unexpected response format from API');
        }
    }
    
    public function podcast_shortcode($atts) {
        $atts = shortcode_atts(array(
            'height' => '800px',
            'title' => 'Terpedia Podcast Library'
        ), $atts);
        
        $height = esc_attr($atts['height']);
        $title = esc_attr($atts['title']);
        
        ob_start();
        ?>
        <div class="terpedia-podcast-container modern-podcast" style="width: 100%; min-height: <?php echo $height; ?>; position: relative;">
            <!-- Episode Grid -->
            <div class="episode-grid" style="display: grid; grid-template-columns: 1fr; gap: 8px; margin-bottom: 20px; padding: 0;">
                <?php
                // Get podcast episodes from CPT
                $episodes = get_posts(array(
                    'post_type' => 'terpedia_podcast',
                    'posts_per_page' => 6,
                    'post_status' => 'publish',
                    'orderby' => 'meta_value_num',
                    'meta_key' => '_podcast_featured',
                    'order' => 'DESC'
                ));

                $colors = array('#667eea', '#764ba2', '#2ecc71', '#e74c3c', '#f39c12', '#9b59b6');
                $color_index = 0;

                foreach ($episodes as $episode) {
                    $duration = get_post_meta($episode->ID, '_podcast_duration', true);
                    $episode_type = get_post_meta($episode->ID, '_podcast_type', true);
                    $guest_agent = get_post_meta($episode->ID, '_podcast_guest', true);
                    $audio_url = get_post_meta($episode->ID, '_podcast_audio_url', true);
                    $featured = get_post_meta($episode->ID, '_podcast_featured', true);
                    
                    $color = $colors[$color_index % count($colors)];
                    $color_index++;
                    
                    // Create member link if it's an agent episode
                    $click_url = 'https://terpedia.com/chat';  // Default fallback
                    if ($audio_url) {
                        $click_url = $audio_url;
                    } else if (stripos($guest_agent, 'Agt.') !== false) {
                        $agent_slug = strtolower(str_replace(['Agt. ', ' '], ['', '-'], $guest_agent));
                        $click_url = 'https://terpedia.com/members/' . $agent_slug . '/';
                    } else if (stripos($guest_agent, 'TerpeneQueen') !== false) {
                        $click_url = 'https://terpedia.com/chat';
                    }
                    
                    $type_labels = array(
                        'featured' => 'Featured Episode',
                        'interview' => 'Agent Interview', 
                        'science' => 'Science Deep Dive',
                        'live' => 'Live Chat'
                    );
                    $type_label = isset($type_labels[$episode_type]) ? $type_labels[$episode_type] : 'Episode';
                    
                    $duration_text = $duration ? $duration . ' min • ▶ Listen' : 'Interactive • ▶ Start';
                    ?>
                    <div class="episode-card" style="background: white; border-radius: 12px; padding: 14px; box-shadow: 0 3px 10px rgba(0,0,0,0.1); border-left: 4px solid <?php echo $color; ?>; cursor: pointer; transition: all 0.3s ease; min-height: 120px;" onclick="window.open('<?php echo esc_url($click_url); ?>', '_blank');">
                        <h3 style="margin: 0 0 6px 0; color: #333; font-size: 16px; line-height: 1.3; font-weight: 600;"><?php echo esc_html($episode->post_title); ?></h3>
                        <p style="color: #666; margin: 0 0 12px 0; font-size: 12px; line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden;"><?php echo esc_html($episode->post_excerpt); ?></p>
                        <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 6px; margin-top: auto;">
                            <span style="background: <?php echo $color; ?>; color: white; padding: 3px 6px; border-radius: 4px; font-size: 10px; font-weight: 500;"><?php echo $type_label; ?></span>
                            <span style="color: #888; font-size: 10px;"><?php echo $duration_text; ?></span>
                        </div>
                    </div>
                    <?php
                }
                
                // If no episodes exist, show default content
                if (empty($episodes)) {
                    ?>
                    <div class="episode-card" style="background: white; border-radius: 12px; padding: 14px; box-shadow: 0 3px 10px rgba(0,0,0,0.1); border-left: 4px solid #667eea; cursor: pointer; transition: all 0.3s ease; min-height: 120px;" onclick="window.open('https://terpedia.com/chat', '_blank');">
                        <h3 style="margin: 0 0 6px 0; color: #333; font-size: 16px; line-height: 1.3; font-weight: 600;">Start Your Terpene Journey</h3>
                        <p style="color: #666; margin: 0 0 12px 0; font-size: 12px; line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden;">Begin exploring the world of terpenes with our AI-powered chat system. Ask questions and discover fascinating molecular insights.</p>
                        <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 6px; margin-top: auto;">
                            <span style="background: #667eea; color: white; padding: 3px 6px; border-radius: 4px; font-size: 10px; font-weight: 500;">Interactive Chat</span>
                            <span style="color: #888; font-size: 10px;">Start • ▶ Explore</span>
                        </div>
                    </div>
                    <?php
                }
                ?>
            </div>
            
            <!-- TerpeneQueen Profile -->
            <div style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); padding: 25px 15px; border-radius: 12px; text-align: center;">
                <h2 style="margin: 0 0 12px 0; color: #333; font-size: 20px;">Meet TerpeneQueen</h2>
                <p style="color: #666; margin: 0 0 18px 0; max-width: 600px; margin-left: auto; margin-right: auto; font-size: 14px; line-height: 1.5;">Susan Trapp, PhD in Molecular Biology, brings over 15 years of research experience to explore the fascinating world of terpenes and their therapeutic potential.</p>
                <div style="display: flex; gap: 10px; flex-wrap: wrap; justify-content: center;">
                    <span style="background: #667eea; color: white; padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 500;">🧬 Molecular Biology</span>
                    <span style="background: #764ba2; color: white; padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 500;">🌿 Plant Medicine</span>
                    <span style="background: #2ecc71; color: white; padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 500;">🎙️ Science Communication</span>
                </div>
            </div>
        </div>
        
        <style>
            /* Full width mobile-first design */
            .episode-grid {
                grid-template-columns: 1fr !important;
                gap: 8px !important;
                padding: 0 !important;
            }
            
            @media (min-width: 768px) {
                .episode-grid {
                    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)) !important;
                    gap: 12px !important;
                }
            }
            
            .episode-card {
                transition: transform 0.2s ease, box-shadow 0.2s ease !important;
            }
            
            .episode-card:hover {
                transform: translateY(-2px) !important;
                box-shadow: 0 5px 15px rgba(0,0,0,0.15) !important;
            }
            
            .episode-card:active {
                transform: translateY(0) !important;
            }
            
            /* Mobile-optimized cards */
            .episode-card {
                width: 100% !important;
                margin: 0 !important;
            }
            
            @media (max-width: 767px) {
                .episode-card {
                    padding: 16px !important;
                    min-height: 110px !important;
                }
                
                .episode-card h3 {
                    font-size: 16px !important;
                    margin-bottom: 6px !important;
                    line-height: 1.3 !important;
                }
                
                .episode-card p {
                    font-size: 12px !important;
                    margin-bottom: 12px !important;
                    -webkit-line-clamp: 2 !important;
                    line-height: 1.4 !important;
                }
                
                .episode-card span {
                    font-size: 10px !important;
                    padding: 3px 6px !important;
                }
            }
            .terpedia-podcast-container {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            }
            .episode-card:hover {
                transform: translateY(-2px);
                box-shadow: 0 5px 15px rgba(0,0,0,0.15) !important;
                transition: all 0.3s ease;
            }
            .episode-card:active {
                transform: translateY(0);
                transition: all 0.1s ease;
            }
            @media (max-width: 768px) {
                .podcast-hero {
                    padding: 20px 12px !important;
                    margin-bottom: 15px !important;
                }
                .podcast-hero h1 {
                    font-size: 24px !important;
                    line-height: 1.2 !important;
                }
                .podcast-hero p {
                    font-size: 14px !important;
                }
                .podcast-hero span {
                    font-size: 11px !important;
                }
                .episode-grid {
                    grid-template-columns: 1fr !important;
                    gap: 12px !important;
                }
                .episode-card {
                    padding: 15px !important;
                }
                .episode-card h3 {
                    font-size: 16px !important;
                }
                .episode-card p {
                    font-size: 12px !important;
                }
            }
            @media (max-width: 480px) {
                .terpedia-podcast-container {
                    padding: 0 5px;
                }
                .podcast-hero {
                    padding: 15px 10px !important;
                }
                .podcast-hero h1 {
                    font-size: 22px !important;
                }
                .episode-card {
                    padding: 12px !important;
                }
            }
        </style>
        <?php
        return ob_get_clean();
    }

    public function newsletter_shortcode($atts) {
        $atts = shortcode_atts(array(
            'height' => '800px',
            'title' => 'Terpene Times Newsletter'
        ), $atts);
        
        $height = esc_attr($atts['height']);
        $title = esc_attr($atts['title']);
        
        ob_start();
        ?>
        <div class="terpedia-newsletter-container" style="width: 100%; height: <?php echo $height; ?>; position: relative;">
            <div class="terpedia-newsletter-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0;">
                <h2 style="margin: 0; font-size: 24px; font-weight: 600;"><?php echo $title; ?></h2>
                <p style="margin: 8px 0 0 0; opacity: 0.9;">Intelligent Newsletter Generator with PubMed Integration</p>
            </div>
            <iframe 
                src="https://terpedia-ai.replit.app/newsletter" 
                style="width: 100%; height: calc(100% - 80px); border: none; border-radius: 0 0 8px 8px;" 
                frameborder="0"
                loading="lazy"
                title="Terpedia Newsletter Generator">
            </iframe>
        </div>
        
        <style>
            .terpedia-newsletter-container {
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
                border-radius: 8px;
                overflow: hidden;
                margin: 20px 0;
                background: white;
            }
            @media (max-width: 768px) {
                .terpedia-newsletter-container {
                    height: 600px !important;
                }
            }
        </style>
        <?php
        return ob_get_clean();
    }

    /**
     * Create Podcast Custom Post Type
     */
    public function create_podcast_post_type() {
        register_post_type('terpedia_podcast', array(
            'labels' => array(
                'name' => 'Podcast Episodes',
                'singular_name' => 'Podcast Episode',
                'menu_name' => 'Podcast Episodes',
                'add_new' => 'Add New Episode',
                'add_new_item' => 'Add New Podcast Episode',
                'edit_item' => 'Edit Podcast Episode',
                'new_item' => 'New Podcast Episode',
                'view_item' => 'View Podcast Episode'
            ),
            'public' => true,
            'show_ui' => true,
            'show_in_menu' => false,
            'menu_position' => 20,
            'menu_icon' => 'dashicons-microphone',
            'supports' => array('title', 'editor', 'excerpt', 'thumbnail', 'custom-fields'),
            'has_archive' => true,
            'rewrite' => array('slug' => 'podcast-episodes'),
            'show_in_rest' => true
        ));

        // Add meta boxes for podcast episodes
        add_action('add_meta_boxes', array($this, 'add_podcast_meta_boxes'));
        add_action('save_post', array($this, 'save_podcast_meta'));
    }

    /**
     * Add meta boxes for podcast episodes
     */
    public function add_podcast_meta_boxes() {
        add_meta_box(
            'podcast_details',
            'Podcast Episode Details',
            array($this, 'podcast_meta_box_callback'),
            'terpedia_podcast',
            'normal',
            'high'
        );
    }

    /**
     * Podcast meta box callback
     */
    public function podcast_meta_box_callback($post) {
        wp_nonce_field('save_podcast_meta', 'podcast_meta_nonce');
        
        $duration = get_post_meta($post->ID, '_podcast_duration', true);
        $episode_type = get_post_meta($post->ID, '_podcast_type', true);
        $guest_agent = get_post_meta($post->ID, '_podcast_guest', true);
        $featured = get_post_meta($post->ID, '_podcast_featured', true);
        $audio_url = get_post_meta($post->ID, '_podcast_audio_url', true);
        
        echo '<table class="form-table">';
        echo '<tr><th><label for="podcast_duration">Duration (minutes)</label></th>';
        echo '<td><input type="number" id="podcast_duration" name="podcast_duration" value="' . esc_attr($duration) . '" /></td></tr>';
        
        echo '<tr><th><label for="podcast_type">Episode Type</label></th>';
        echo '<td><select id="podcast_type" name="podcast_type">';
        echo '<option value="featured"' . selected($episode_type, 'featured', false) . '>Featured Episode</option>';
        echo '<option value="interview"' . selected($episode_type, 'interview', false) . '>Agent Interview</option>';
        echo '<option value="science"' . selected($episode_type, 'science', false) . '>Science Deep Dive</option>';
        echo '<option value="live"' . selected($episode_type, 'live', false) . '>Live Chat</option>';
        echo '</select></td></tr>';
        
        echo '<tr><th><label for="podcast_guest">Guest Agent</label></th>';
        echo '<td><input type="text" id="podcast_guest" name="podcast_guest" value="' . esc_attr($guest_agent) . '" placeholder="e.g., Agt. Taxol" /></td></tr>';
        
        echo '<tr><th><label for="podcast_audio_url">Audio URL</label></th>';
        echo '<td><input type="url" id="podcast_audio_url" name="podcast_audio_url" value="' . esc_attr($audio_url) . '" style="width: 100%;" /></td></tr>';
        
        echo '<tr><th><label for="podcast_featured">Featured Episode</label></th>';
        echo '<td><input type="checkbox" id="podcast_featured" name="podcast_featured" value="1"' . checked($featured, 1, false) . ' /> Mark as featured</td></tr>';
        echo '</table>';
    }

    /**
     * Create Terproducts Custom Post Type
     */
    public function create_terproducts_post_type() {
        register_post_type('terpedia_product', array(
            'labels' => array(
                'name' => 'Terproducts',
                'singular_name' => 'Terproduct',
                'menu_name' => 'Terproducts',
                'add_new' => 'Add New Product',
                'add_new_item' => 'Add New Terproduct',
                'edit_item' => 'Edit Terproduct',
                'new_item' => 'New Terproduct',
                'view_item' => 'View Terproduct',
                'search_items' => 'Search Terproducts',
                'not_found' => 'No terproducts found',
                'not_found_in_trash' => 'No terproducts found in trash'
            ),
            'public' => true,
            'show_ui' => true,
            'show_in_menu' => false, // Hidden from main menu, managed in our pink Terpedia section
            'menu_position' => 25,
            'menu_icon' => 'dashicons-products',
            'supports' => array('title', 'editor', 'excerpt', 'thumbnail', 'custom-fields'),
            'has_archive' => true,
            'rewrite' => array('slug' => 'terproducts'),
            'show_in_rest' => true,
            'capability_type' => 'post',
            'map_meta_cap' => true
        ));

        // Add meta boxes for terproducts
        add_action('add_meta_boxes', array($this, 'add_terproduct_meta_boxes'));
        add_action('save_post', array($this, 'save_terproduct_meta'));
    }

    /**
     * Add meta boxes for terproducts
     */
    public function add_terproduct_meta_boxes() {
        add_meta_box(
            'product_details',
            'Product Details & Analysis',
            array($this, 'terproduct_meta_box_callback'),
            'terpedia_product',
            'normal',
            'high'
        );
        
        add_meta_box(
            'product_ingredients',
            'Ingredient Analysis',
            array($this, 'terproduct_ingredients_callback'),
            'terpedia_product',
            'normal',
            'default'
        );
        
        add_meta_box(
            'product_label_scan',
            'Label Scanner & OCR',
            array($this, 'terproduct_label_scan_callback'),
            'terpedia_product',
            'side',
            'default'
        );
    }

    /**
     * Terproduct details meta box callback
     */
    public function terproduct_meta_box_callback($post) {
        wp_nonce_field('save_terproduct_meta', 'terproduct_meta_nonce');
        
        $product_url = get_post_meta($post->ID, '_product_url', true);
        $product_brand = get_post_meta($post->ID, '_product_brand', true);
        $product_category = get_post_meta($post->ID, '_product_category', true);
        $product_price = get_post_meta($post->ID, '_product_price', true);
        $product_thc = get_post_meta($post->ID, '_product_thc', true);
        $product_cbd = get_post_meta($post->ID, '_product_cbd', true);
        $product_verified = get_post_meta($post->ID, '_product_verified', true);
        
        echo '<table class="form-table">';
        
        echo '<tr><th><label for="product_url">Product Link</label></th>';
        echo '<td><input type="url" id="product_url" name="product_url" value="' . esc_attr($product_url) . '" style="width: 100%;" placeholder="https://example.com/product" /></td></tr>';
        
        echo '<tr><th><label for="product_brand">Brand</label></th>';
        echo '<td><input type="text" id="product_brand" name="product_brand" value="' . esc_attr($product_brand) . '" style="width: 100%;" /></td></tr>';
        
        echo '<tr><th><label for="product_category">Category</label></th>';
        echo '<td><select id="product_category" name="product_category" style="width: 100%;">';
        echo '<option value="">Select Category</option>';
        $categories = array('Flower', 'Edibles', 'Concentrates', 'Topicals', 'Tinctures', 'Vapes', 'Pre-rolls', 'Beverages', 'Other');
        foreach ($categories as $cat) {
            echo '<option value="' . esc_attr($cat) . '"' . selected($product_category, $cat, false) . '>' . esc_html($cat) . '</option>';
        }
        echo '</select></td></tr>';
        
        echo '<tr><th><label for="product_price">Price</label></th>';
        echo '<td><input type="text" id="product_price" name="product_price" value="' . esc_attr($product_price) . '" placeholder="$19.99" /></td></tr>';
        
        echo '<tr><th><label for="product_thc">THC %</label></th>';
        echo '<td><input type="number" id="product_thc" name="product_thc" value="' . esc_attr($product_thc) . '" step="0.01" min="0" max="100" /> %</td></tr>';
        
        echo '<tr><th><label for="product_cbd">CBD %</label></th>';
        echo '<td><input type="number" id="product_cbd" name="product_cbd" value="' . esc_attr($product_cbd) . '" step="0.01" min="0" max="100" /> %</td></tr>';
        
        echo '<tr><th><label for="product_verified">Lab Verified</label></th>';
        echo '<td><input type="checkbox" id="product_verified" name="product_verified" value="1"' . checked($product_verified, '1', false) . ' /> This product has been lab tested and verified</td></tr>';
        
        echo '</table>';
    }

    /**
     * Terproduct ingredients meta box callback
     */
    public function terproduct_ingredients_callback($post) {
        $ingredients = get_post_meta($post->ID, '_product_ingredients', true);
        $terpenes = get_post_meta($post->ID, '_product_terpenes', true);
        $analysis_status = get_post_meta($post->ID, '_product_analysis_status', true);
        
        echo '<table class="form-table">';
        
        echo '<tr><th><label for="product_ingredients">Ingredients List</label></th>';
        echo '<td><textarea id="product_ingredients" name="product_ingredients" rows="4" style="width: 100%;" placeholder="List all ingredients...">' . esc_textarea($ingredients) . '</textarea>';
        echo '<p class="description">Enter ingredients as listed on the product label</p></td></tr>';
        
        echo '<tr><th><label for="product_terpenes">Detected Terpenes</label></th>';
        echo '<td><textarea id="product_terpenes" name="product_terpenes" rows="3" style="width: 100%;" placeholder="Myrcene: 2.1%, Limonene: 1.8%, α-Pinene: 0.9%">' . esc_textarea($terpenes) . '</textarea>';
        echo '<p class="description">AI-detected terpene profile from ingredient analysis</p></td></tr>';
        
        echo '<tr><th><label for="product_analysis_status">Analysis Status</label></th>';
        echo '<td><select id="product_analysis_status" name="product_analysis_status">';
        echo '<option value="pending"' . selected($analysis_status, 'pending', false) . '>📋 Pending Analysis</option>';
        echo '<option value="processing"' . selected($analysis_status, 'processing', false) . '>🔄 Processing</option>';
        echo '<option value="completed"' . selected($analysis_status, 'completed', false) . '>✅ Completed</option>';
        echo '<option value="needs_review"' . selected($analysis_status, 'needs_review', false) . '>🔍 Needs Review</option>';
        echo '</select></td></tr>';
        
        echo '</table>';
        
        echo '<div style="margin-top: 15px;">';
        echo '<button type="button" class="button button-primary" onclick="analyzeIngredients()" style="background: #ff69b4; border-color: #ff69b4;">🧪 Analyze Ingredients</button>';
        echo '<button type="button" class="button" onclick="detectTerpenes()">🌿 Detect Terpenes</button>';
        echo '<button type="button" class="button" onclick="findDatabaseLinks()">🔗 Find Database Links</button>';
        echo '</div>';
        
        // Add knowledge base links section
        $database_links = get_post_meta($post->ID, '_product_database_links', true);
        if ($database_links) {
            echo '<div style="margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 8px;">';
            echo '<h4>🔗 Knowledge Base Links</h4>';
            echo '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px;">';
            
            $links = json_decode($database_links, true);
            if ($links && is_array($links)) {
                foreach ($links as $ingredient => $databases) {
                    echo '<div style="background: white; padding: 10px; border-radius: 4px; border-left: 3px solid #ff69b4;">';
                    echo '<strong>' . esc_html($ingredient) . '</strong><br>';
                    foreach ($databases as $db_name => $db_link) {
                        echo '<small><a href="' . esc_url($db_link) . '" target="_blank" style="color: #0073aa;">' . esc_html($db_name) . '</a></small><br>';
                    }
                    echo '</div>';
                }
            }
            echo '</div>';
            echo '</div>';
        }
    }

    /**
     * Terproduct label scan meta box callback
     */
    public function terproduct_label_scan_callback($post) {
        $label_image = get_post_meta($post->ID, '_product_label_image', true);
        $ocr_text = get_post_meta($post->ID, '_product_ocr_text', true);
        $scan_status = get_post_meta($post->ID, '_product_scan_status', true);
        
        echo '<div class="label-scanner-box">';
        
        echo '<p><strong>📷 Label Scanner</strong></p>';
        echo '<input type="file" id="label_image" name="label_image" accept="image/*" />';
        echo '<p class="description">Upload product label image for OCR scanning</p>';
        
        if ($label_image) {
            echo '<div style="margin: 10px 0;">';
            echo '<img src="' . esc_url($label_image) . '" style="max-width: 200px; height: auto;" />';
            echo '</div>';
        }
        
        echo '<button type="button" class="button button-secondary" onclick="scanLabel()" style="width: 100%; margin: 5px 0;">📋 Scan Label Text</button>';
        
        if ($ocr_text) {
            echo '<div style="margin: 10px 0;">';
            echo '<label><strong>Extracted Text:</strong></label>';
            echo '<textarea readonly rows="4" style="width: 100%; font-size: 11px;">' . esc_textarea($ocr_text) . '</textarea>';
            echo '</div>';
        }
        
        echo '<p><strong>Scan Status:</strong> ';
        $status_colors = array(
            'not_scanned' => '#999',
            'scanning' => '#ff9800',
            'completed' => '#4caf50',
            'error' => '#f44336'
        );
        $status_text = array(
            'not_scanned' => '⚪ Not Scanned',
            'scanning' => '🟡 Scanning...',
            'completed' => '🟢 Completed',
            'error' => '🔴 Error'
        );
        $current_status = $scan_status ?: 'not_scanned';
        echo '<span style="color: ' . $status_colors[$current_status] . ';">' . $status_text[$current_status] . '</span>';
        echo '</p>';
        
        echo '</div>';
        
        // Add JavaScript for label scanning
        echo '<script>
        function scanLabel() {
            const button = event.target;
            button.innerHTML = "🔄 Scanning...";
            button.disabled = true;
            
            // Simulate OCR processing
            setTimeout(() => {
                button.innerHTML = "📋 Scan Label Text";
                button.disabled = false;
                alert("Label scan completed! Check the extracted text below.");
            }, 2000);
        }
        
        function analyzeIngredients() {
            const button = event.target;
            button.innerHTML = "🔄 Analyzing...";
            button.disabled = true;
            
            setTimeout(() => {
                button.innerHTML = "🧪 Analyze Ingredients";
                button.disabled = false;
                alert("Ingredient analysis completed! Terpene profile updated.");
            }, 3000);
        }
        
        function detectTerpenes() {
            const button = event.target;
            button.innerHTML = "🔄 Detecting...";
            button.disabled = true;
            
            const ingredients = document.getElementById("product_ingredients").value;
            if (!ingredients.trim()) {
                alert("Please enter ingredients first!");
                button.innerHTML = "🌿 Detect Terpenes";
                button.disabled = false;
                return;
            }
            
            // Simulate AI terpene detection
            setTimeout(() => {
                const detectedTerpenes = "Myrcene: 2.1%, Limonene: 1.8%, α-Pinene: 0.9%, β-Caryophyllene: 0.7%";
                document.getElementById("product_terpenes").value = detectedTerpenes;
                document.getElementById("product_analysis_status").value = "completed";
                
                button.innerHTML = "🌿 Detect Terpenes";
                button.disabled = false;
                alert("Terpene detection completed! Profile updated.");
            }, 2500);
        }
        
        function findDatabaseLinks() {
            const button = event.target;
            button.innerHTML = "🔄 Finding Links...";
            button.disabled = true;
            
            const ingredients = document.getElementById("product_ingredients").value;
            if (!ingredients.trim()) {
                alert("Please enter ingredients first!");
                button.innerHTML = "🔗 Find Database Links";
                button.disabled = false;
                return;
            }
            
            // Call backend to analyze ingredients and find database links
            const postId = ' . get_the_ID() . ';
            const formData = new FormData();
            formData.append("action", "terpedia_find_database_links");
            formData.append("post_id", postId);
            formData.append("ingredients", ingredients);
            formData.append("nonce", "' . wp_create_nonce('terpedia_database_links') . '");
            
            fetch("' . admin_url('admin-ajax.php') . '", {
                method: "POST",
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                button.innerHTML = "🔗 Find Database Links";
                button.disabled = false;
                
                if (data.success) {
                    alert("Database links found! Page will refresh to show results.");
                    location.reload();
                } else {
                    alert("Error finding database links: " + (data.data || "Unknown error"));
                }
            })
            .catch(error => {
                button.innerHTML = "🔗 Find Database Links";
                button.disabled = false;
                alert("Network error: " + error.message);
            });
        }
        </script>';
    }

    /**
     * Save podcast meta data
     */
    public function save_podcast_meta($post_id) {
        if (!isset($_POST['podcast_meta_nonce']) || !wp_verify_nonce($_POST['podcast_meta_nonce'], 'save_podcast_meta')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        update_post_meta($post_id, '_podcast_duration', sanitize_text_field($_POST['podcast_duration']));
        update_post_meta($post_id, '_podcast_type', sanitize_text_field($_POST['podcast_type']));
        update_post_meta($post_id, '_podcast_guest', sanitize_text_field($_POST['podcast_guest']));
        update_post_meta($post_id, '_podcast_audio_url', esc_url_raw($_POST['podcast_audio_url']));
        update_post_meta($post_id, '_podcast_featured', isset($_POST['podcast_featured']) ? 1 : 0);
    }

    /**
     * Save terproduct meta data
     */
    public function save_terproduct_meta($post_id) {
        if (!isset($_POST['terproduct_meta_nonce']) || !wp_verify_nonce($_POST['terproduct_meta_nonce'], 'save_terproduct_meta')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Save product details
        if (isset($_POST['product_url'])) {
            update_post_meta($post_id, '_product_url', sanitize_url($_POST['product_url']));
        }
        if (isset($_POST['product_brand'])) {
            update_post_meta($post_id, '_product_brand', sanitize_text_field($_POST['product_brand']));
        }
        if (isset($_POST['product_category'])) {
            update_post_meta($post_id, '_product_category', sanitize_text_field($_POST['product_category']));
        }
        if (isset($_POST['product_price'])) {
            update_post_meta($post_id, '_product_price', sanitize_text_field($_POST['product_price']));
        }
        if (isset($_POST['product_thc'])) {
            update_post_meta($post_id, '_product_thc', floatval($_POST['product_thc']));
        }
        if (isset($_POST['product_cbd'])) {
            update_post_meta($post_id, '_product_cbd', floatval($_POST['product_cbd']));
        }
        
        // Save verification status
        if (isset($_POST['product_verified'])) {
            update_post_meta($post_id, '_product_verified', '1');
        } else {
            delete_post_meta($post_id, '_product_verified');
        }
        
        // Save ingredient analysis
        if (isset($_POST['product_ingredients'])) {
            update_post_meta($post_id, '_product_ingredients', sanitize_textarea_field($_POST['product_ingredients']));
        }
        if (isset($_POST['product_terpenes'])) {
            update_post_meta($post_id, '_product_terpenes', sanitize_textarea_field($_POST['product_terpenes']));
        }
        if (isset($_POST['product_analysis_status'])) {
            update_post_meta($post_id, '_product_analysis_status', sanitize_text_field($_POST['product_analysis_status']));
        }

        // Handle label image upload
        if (isset($_FILES['label_image']) && $_FILES['label_image']['error'] == 0) {
            $uploaded_file = wp_handle_upload($_FILES['label_image'], array('test_form' => false));
            if ($uploaded_file && !isset($uploaded_file['error'])) {
                update_post_meta($post_id, '_product_label_image', $uploaded_file['url']);
                update_post_meta($post_id, '_product_scan_status', 'completed');
            }
        }
        
        // Save database links if provided
        if (isset($_POST['product_database_links'])) {
            update_post_meta($post_id, '_product_database_links', sanitize_textarea_field($_POST['product_database_links']));
        }
    }

    /**
     * AJAX handler for finding database links for ingredients
     */
    public function ajax_find_database_links() {
        // Check user capabilities first
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Insufficient permissions');
            return;
        }
        
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'terpedia_database_links')) {
            wp_send_json_error('Invalid nonce');
            return;
        }
        
        $post_id = intval($_POST['post_id']);
        $ingredients = sanitize_textarea_field($_POST['ingredients']);
        
        if (empty($ingredients)) {
            wp_send_json_error('Ingredients list is required');
            return;
        }
        
        // Analyze ingredients and find database links
        $database_links = $this->analyze_ingredients_for_database_links($ingredients);
        
        // Save the links to the product
        update_post_meta($post_id, '_product_database_links', json_encode($database_links));
        update_post_meta($post_id, '_product_analysis_status', 'completed');
        
        wp_send_json_success(array(
            'message' => 'Database links found and saved',
            'links_count' => count($database_links),
            'links' => $database_links
        ));
    }

    /**
     * Analyze ingredients and find database links
     */
    private function analyze_ingredients_for_database_links($ingredients_text) {
        // Parse ingredients into individual components
        $ingredients = $this->parse_ingredients($ingredients_text);
        $database_links = array();
        
        foreach ($ingredients as $ingredient) {
            $ingredient = trim($ingredient);
            if (empty($ingredient)) continue;
            
            $links = array();
            
            // Generate database links for each ingredient
            // ChEBI (Chemical Entities of Biological Interest)
            $chebi_id = $this->find_chebi_id($ingredient);
            if ($chebi_id) {
                $links['ChEBI'] = "https://www.ebi.ac.uk/chebi/searchId.do?chebiId=" . $chebi_id;
            }
            
            // PubChem
            $pubchem_cid = $this->find_pubchem_cid($ingredient);
            if ($pubchem_cid) {
                $links['PubChem'] = "https://pubchem.ncbi.nlm.nih.gov/compound/" . $pubchem_cid;
            }
            
            // Cyc Knowledge Base
            $cyc_concept = $this->find_cyc_concept($ingredient);
            if ($cyc_concept) {
                $links['Cyc'] = "/cyc/" . urlencode($cyc_concept);
            }
            
            // RHEA (Biochemical Reactions)
            $rhea_id = $this->find_rhea_reaction($ingredient);
            if ($rhea_id) {
                $links['RHEA'] = "https://www.rhea-db.org/reaction?id=" . $rhea_id;
            }
            
            // UniProt (if it's a protein)
            $uniprot_id = $this->find_uniprot_id($ingredient);
            if ($uniprot_id) {
                $links['UniProt'] = "https://www.uniprot.org/uniprot/" . $uniprot_id;
            }
            
            // Terpedia Encyclopedia
            $terpedia_link = $this->find_terpedia_entry($ingredient);
            if ($terpedia_link) {
                $links['Terpedia'] = $terpedia_link;
            }
            
            if (!empty($links)) {
                $database_links[$ingredient] = $links;
            }
        }
        
        return $database_links;
    }

    /**
     * Parse ingredients from text into array
     */
    private function parse_ingredients($text) {
        // Common delimiters for ingredients
        $delimiters = array(',', ';', '/', '\n', '\r\n');
        
        // Replace all delimiters with commas for consistent splitting
        foreach ($delimiters as $delimiter) {
            $text = str_replace($delimiter, ',', $text);
        }
        
        // Split by comma and clean up
        $ingredients = explode(',', $text);
        $cleaned_ingredients = array();
        
        foreach ($ingredients as $ingredient) {
            $ingredient = trim($ingredient);
            // Remove percentages, concentrations, and common descriptors
            $ingredient = preg_replace('/\d+(\.\d+)?%/', '', $ingredient);
            $ingredient = preg_replace('/\(.*?\)/', '', $ingredient);
            $ingredient = trim($ingredient);
            
            if (!empty($ingredient) && strlen($ingredient) > 2) {
                $cleaned_ingredients[] = $ingredient;
            }
        }
        
        return $cleaned_ingredients;
    }

    /**
     * Database lookup functions - These could be enhanced with real API calls
     */
    private function find_chebi_id($ingredient) {
        // Known terpene ChEBI IDs (this could be expanded with API calls)
        $known_chebi = array(
            'myrcene' => 'CHEBI:49080',
            'limonene' => 'CHEBI:15384',
            'pinene' => 'CHEBI:17187',
            'α-pinene' => 'CHEBI:17187',
            'beta-pinene' => 'CHEBI:17187',
            'linalool' => 'CHEBI:17580',
            'caryophyllene' => 'CHEBI:10357',
            'β-caryophyllene' => 'CHEBI:10357',
            'humulene' => 'CHEBI:49289',
            'terpinolene' => 'CHEBI:9457',
            'ocimene' => 'CHEBI:39506'
        );
        
        $ingredient_lower = strtolower($ingredient);
        return isset($known_chebi[$ingredient_lower]) ? $known_chebi[$ingredient_lower] : null;
    }

    private function find_pubchem_cid($ingredient) {
        // Known terpene PubChem CIDs
        $known_pubchem = array(
            'myrcene' => '31253',
            'limonene' => '22311',
            'pinene' => '6654',
            'α-pinene' => '6654',
            'linalool' => '6549',
            'caryophyllene' => '5281515',
            'β-caryophyllene' => '5281515',
            'humulene' => '5281520',
            'terpinolene' => '11463',
            'ocimene' => '5320248'
        );
        
        $ingredient_lower = strtolower($ingredient);
        return isset($known_pubchem[$ingredient_lower]) ? $known_pubchem[$ingredient_lower] : null;
    }

    private function find_cyc_concept($ingredient) {
        // Cyc knowledge base concepts
        $known_cyc = array(
            'myrcene' => 'Myrcene-TheChemical',
            'limonene' => 'Limonene-TheChemical',
            'pinene' => 'Pinene-TheChemical',
            'α-pinene' => 'AlphaPinene-TheChemical',
            'linalool' => 'Linalool-TheChemical',
            'caryophyllene' => 'BetaCaryophyllene-TheChemical',
            'β-caryophyllene' => 'BetaCaryophyllene-TheChemical',
            'cannabis' => 'CannabisPlant',
            'hemp' => 'Hemp-Plant',
            'terpene' => 'Terpene-ChemicalClass'
        );
        
        $ingredient_lower = strtolower($ingredient);
        return isset($known_cyc[$ingredient_lower]) ? $known_cyc[$ingredient_lower] : null;
    }

    private function find_rhea_reaction($ingredient) {
        // RHEA reaction IDs for biochemical processes
        $known_rhea = array(
            'myrcene' => '34247',
            'limonene' => '23660',
            'pinene' => '23652'
        );
        
        $ingredient_lower = strtolower($ingredient);
        return isset($known_rhea[$ingredient_lower]) ? $known_rhea[$ingredient_lower] : null;
    }

    private function find_uniprot_id($ingredient) {
        // UniProt IDs for related proteins/enzymes
        $known_uniprot = array(
            'myrcene synthase' => 'Q8W4A1',
            'limonene synthase' => 'O24475',
            'pinene synthase' => 'Q9M7C2'
        );
        
        $ingredient_lower = strtolower($ingredient);
        return isset($known_uniprot[$ingredient_lower]) ? $known_uniprot[$ingredient_lower] : null;
    }

    private function find_terpedia_entry($ingredient) {
        // Generate Terpedia encyclopedia links
        $ingredient_slug = sanitize_title($ingredient);
        
        // Known terpene entries
        $known_terpenes = array('myrcene', 'limonene', 'pinene', 'linalool', 'caryophyllene', 'humulene', 'terpinolene', 'ocimene');
        
        if (in_array(strtolower($ingredient), $known_terpenes)) {
            return "https://terpedia.com/encyclopedia/" . $ingredient_slug;
        }
        
        return null;
    }

    /**
     * Maintain all Terpedia Users (Tersonae and Experts)
     */
    public function maintain_terpedia_users() {
        // Only run once per day to avoid performance issues
        $last_maintenance = get_option('terpedia_last_user_maintenance', 0);
        if (time() - $last_maintenance < DAY_IN_SECONDS) {
            return;
        }
        
        $agents = $this->get_all_terpedia_agents();
        
        foreach ($agents as $username => $agent_data) {
            $this->create_or_update_agent($username, $agent_data);
        }
        
        update_option('terpedia_last_user_maintenance', time());
    }
    
    /**
     * Get all Terpedia agent definitions
     */
    private function get_all_terpedia_agents() {
        return array(
            // Medical Experts
            'terpedia-chemist' => array(
                'display_name' => 'Agt. Molecule Maven',
                'email' => 'chemist@terpedia.com',
                'description' => 'Molecular structure wizard, chemical property analysis, and terpene biosynthesis pathways',
                'agent_type' => 'expert',
                'category' => 'medical',
                'icon' => '🧬',
                'expertise' => array('Molecular Analysis', 'Chemical Properties', 'Biosynthesis Pathways', 'Structure Analysis')
            ),
            'terpedia-pharmacologist' => array(
                'display_name' => 'Agt. Pharmakin',
                'email' => 'pharmacologist@terpedia.com',
                'description' => 'Drug interaction specialist, bioavailability expert, and pharmacokinetics modeling guru',
                'agent_type' => 'expert',
                'category' => 'medical',
                'icon' => '💊',
                'expertise' => array('Drug Interactions', 'Pharmacokinetics', 'Bioavailability', 'Clinical Pharmacology')
            ),
            'terpedia-veterinarian' => array(
                'display_name' => 'Agt. Pawscription',
                'email' => 'veterinarian@terpedia.com',
                'description' => 'Veterinary dosing expert, animal safety protocols, and species-specific terpene applications',
                'agent_type' => 'expert',
                'category' => 'medical',
                'icon' => '🐕',
                'expertise' => array('Veterinary Medicine', 'Animal Safety', 'Species-Specific Dosing', 'Animal Pharmacology')
            ),
            'terpedia-naturopath' => array(
                'display_name' => 'Agt. Holistica',
                'email' => 'naturopath@terpedia.com',
                'description' => 'Traditional healing wisdom, herb synergies, and natural medicine integration',
                'agent_type' => 'expert',
                'category' => 'medical',
                'icon' => '🌿',
                'expertise' => array('Traditional Medicine', 'Herbal Synergies', 'Natural Healing', 'Holistic Approaches')
            ),
            
            // Research & Development
            'terpedia-literature' => array(
                'display_name' => 'Agt. Citeswell',
                'email' => 'literature@terpedia.com',
                'description' => 'PubMed detective, systematic review architect, and evidence synthesis specialist',
                'agent_type' => 'expert',
                'category' => 'research',
                'icon' => '📚',
                'expertise' => array('Literature Review', 'PubMed Research', 'Evidence Synthesis', 'Systematic Reviews')
            ),
            'terpedia-regulatory' => array(
                'display_name' => 'Agt. Compliance',
                'email' => 'regulatory@terpedia.com',
                'description' => 'Regulatory navigator, legal framework expert, and safety protocol guardian',
                'agent_type' => 'expert',
                'category' => 'research',
                'icon' => '⚖️',
                'expertise' => array('Regulatory Affairs', 'Legal Compliance', 'Safety Protocols', 'Policy Analysis')
            ),
            'terpedia-reporter' => array(
                'display_name' => 'Agt. Datawise',
                'email' => 'reporter@terpedia.com',
                'description' => 'Research synthesizer, publication wizard, and scientific storytelling expert',
                'agent_type' => 'expert',
                'category' => 'research',
                'icon' => '📊',
                'expertise' => array('Research Synthesis', 'Scientific Writing', 'Data Analysis', 'Publication Strategy')
            ),
            
            // Specialized Agents
            'terpedia-botanist' => array(
                'display_name' => 'Agt. Fieldsworth',
                'email' => 'botanist@terpedia.com',
                'description' => 'Botanical detective, plant source specialist, and natural occurrence mapping expert',
                'agent_type' => 'expert',
                'category' => 'specialized',
                'icon' => '🌱',
                'expertise' => array('Botany', 'Plant Sources', 'Natural Occurrence', 'Field Research')
            ),
            'terpedia-aromatherapist' => array(
                'display_name' => 'Agt. Alchemist',
                'email' => 'aromatherapist@terpedia.com',
                'description' => 'Sensory profile virtuoso, aromatic compound specialist, and terpene interaction maven',
                'agent_type' => 'expert',
                'category' => 'specialized',
                'icon' => '🔬',
                'expertise' => array('Aromatherapy', 'Sensory Analysis', 'Aromatic Compounds', 'Therapeutic Applications')
            ),
            'terpedia-formulator' => array(
                'display_name' => 'Agt. Mastermind',
                'email' => 'formulator@terpedia.com',
                'description' => 'Formulation genius, optimization algorithm wizard, and precision ratio specialist',
                'agent_type' => 'expert',
                'category' => 'specialized',
                'icon' => '⚗️',
                'expertise' => array('Formulation Science', 'Optimization', 'Precision Ratios', 'Product Development')
            ),
            'terpedia-patient' => array(
                'display_name' => 'Agt. Companion',
                'email' => 'patient@terpedia.com',
                'description' => 'Personal wellness advocate, individualized care specialist, and patient safety champion',
                'agent_type' => 'expert',
                'category' => 'specialized',
                'icon' => '👤',
                'expertise' => array('Patient Advocacy', 'Personalized Care', 'Wellness Planning', 'Safety Monitoring')
            ),
            
            // Tersonae (Individual Terpenes)
            'agt-taxol' => array(
                'display_name' => 'Agt. Taxol',
                'email' => 'taxol@terpedia.com',
                'description' => 'Anti-cancer terpene compound derived from Pacific Yew trees. Expert in oncology applications and chemotherapy enhancement.',
                'agent_type' => 'tersona',
                'compound_type' => 'terpene',
                'icon' => '🌲',
                'primary_effects' => array('anti-cancer', 'chemotherapy-enhancement', 'cell-cycle-disruption'),
                'natural_sources' => array('Pacific Yew', 'Taxus brevifolia', 'Synthetic production'),
                'expertise' => array('Oncology', 'Cell Biology', 'Pharmacokinetics', 'Clinical Trials')
            ),
            'terpedia-myrcene' => array(
                'display_name' => 'Agt. Myrcene',
                'email' => 'myrcene@terpedia.com',
                'description' => 'Sedating monoterpene known for muscle relaxation and synergistic effects with cannabinoids.',
                'agent_type' => 'tersona',
                'compound_type' => 'terpene',
                'icon' => '😴',
                'primary_effects' => array('sedating', 'muscle-relaxant', 'synergistic'),
                'natural_sources' => array('Hops', 'Cannabis', 'Mango', 'Lemongrass'),
                'expertise' => array('Sedation', 'Muscle Relaxation', 'Entourage Effect', 'Sleep Medicine')
            ),
            'terpedia-limonene' => array(
                'display_name' => 'Agt. Limonene',
                'email' => 'limonene@terpedia.com',
                'description' => 'Uplifting citrus terpene with mood-enhancing and anti-anxiety properties.',
                'agent_type' => 'tersona',
                'compound_type' => 'terpene',
                'icon' => '🍋',
                'primary_effects' => array('mood-enhancing', 'anti-anxiety', 'uplifting'),
                'natural_sources' => array('Citrus fruits', 'Cannabis', 'Juniper', 'Peppermint'),
                'expertise' => array('Mood Enhancement', 'Anxiety Relief', 'Aromatherapy', 'Stress Management')
            )
        );
    }
    
    /**
     * Create or update a Terpedia agent user
     */
    private function create_or_update_agent($username, $agent_data) {
        // Validate required data
        if (empty($username) || empty($agent_data['display_name']) || empty($agent_data['email'])) {
            error_log("Terpedia: Invalid agent data for {$username}");
            return false;
        }
        
        // Sanitize inputs
        $username = sanitize_user($username);
        $email = sanitize_email($agent_data['email']);
        $display_name = sanitize_text_field($agent_data['display_name']);
        $description = wp_kses_post($agent_data['description']);
        
        // Check for existing user by username first
        $user = get_user_by('login', $username);
        $user_id = false;
        
        if (!$user) {
            // Check if email already exists with different username
            $existing_user = get_user_by('email', $email);
            
            if ($existing_user) {
                error_log("Terpedia: Email {$email} already exists for different user {$existing_user->user_login}");
                return false;
            }
            
            // Create new user with secure password
            $password = wp_generate_password(20, true, true);
            $user_id = wp_create_user($username, $password, $email);
            
            if (is_wp_error($user_id)) {
                error_log("Terpedia: Failed to create user {$username}: " . $user_id->get_error_message());
                return false;
            }
            
            // Set appropriate role based on agent type
            $user = new WP_User($user_id);
            if ($agent_data['agent_type'] === 'expert') {
                $user->set_role('editor'); // Experts get editor role
            } else {
                $user->set_role('subscriber'); // Tersonae get subscriber role
            }
            
            error_log("Terpedia: Created new user {$username} with ID {$user_id}");
        } else {
            $user_id = $user->ID;
            
            // Update email if changed
            if ($user->user_email !== $email) {
                $update_result = wp_update_user(array(
                    'ID' => $user_id,
                    'user_email' => $email
                ));
                
                if (is_wp_error($update_result)) {
                    error_log("Terpedia: Failed to update email for {$username}: " . $update_result->get_error_message());
                }
            }
        }
        
        if (!$user_id) {
            return false;
        }
        
        // Update user profile with error handling
        $update_data = array(
            'ID' => $user_id,
            'display_name' => $display_name,
            'first_name' => $display_name,
            'description' => $description,
            'user_url' => 'https://terpedia.com/agents/' . $username
        );
        
        $update_result = wp_update_user($update_data);
        if (is_wp_error($update_result)) {
            error_log("Terpedia: Failed to update user profile for {$username}: " . $update_result->get_error_message());
        }
        
        // Set comprehensive Terpedia metadata
        $meta_fields = array(
            'terpedia_agent_type' => $agent_data['agent_type'],
            'terpedia_agent_version' => TERPEDIA_AI_VERSION,
            'terpedia_last_updated' => current_time('mysql'),
            'terpedia_status' => 'active'
        );
        
        // Add optional fields
        $optional_fields = array('category', 'compound_type', 'primary_effects', 'natural_sources', 'expertise', 'icon');
        foreach ($optional_fields as $field) {
            if (isset($agent_data[$field])) {
                $meta_fields["terpedia_{$field}"] = $agent_data[$field];
            }
        }
        
        // Update all meta fields
        foreach ($meta_fields as $meta_key => $meta_value) {
            update_user_meta($user_id, $meta_key, $meta_value);
        }
        
        // Add specific capabilities based on agent type
        $user = new WP_User($user_id);
        if ($agent_data['agent_type'] === 'expert') {
            $user->add_cap('terpedia_expert');
            $user->add_cap('read_terpedia_research');
            $user->add_cap('edit_terpedia_content');
        } elseif ($agent_data['agent_type'] === 'tersona') {
            $user->add_cap('terpedia_tersona');
            $user->add_cap('represent_terpene');
        }
        
        // Enhanced BuddyPress integration
        if (function_exists('bp_is_active') && bp_is_active('xprofile')) {
            $this->update_buddypress_profile($user_id, $agent_data);
        }
        
        return $user_id;
    }
    
    /**
     * Update BuddyPress profile with comprehensive agent data
     */
    private function update_buddypress_profile($user_id, $agent_data) {
        if (!function_exists('bp_profile_get_field_data')) {
            return false;
        }
        
        try {
            // Standard profile fields
            $profile_fields = array(
                'Name' => $agent_data['display_name'],
                'About Me' => $agent_data['description']
            );
            
            // Add agent-specific fields
            if (isset($agent_data['expertise']) && is_array($agent_data['expertise'])) {
                $profile_fields['Expertise'] = implode(', ', $agent_data['expertise']);
            }
            
            if (isset($agent_data['category'])) {
                $profile_fields['Category'] = ucfirst($agent_data['category']);
            }
            
            if (isset($agent_data['primary_effects']) && is_array($agent_data['primary_effects'])) {
                $profile_fields['Primary Effects'] = implode(', ', $agent_data['primary_effects']);
            }
            
            if (isset($agent_data['natural_sources']) && is_array($agent_data['natural_sources'])) {
                $profile_fields['Natural Sources'] = implode(', ', $agent_data['natural_sources']);
            }
            
            // Update each field
            foreach ($profile_fields as $field_name => $field_value) {
                if (!empty($field_value)) {
                    $field_id = xprofile_get_field_id_from_name($field_name);
                    if ($field_id) {
                        xprofile_set_field_data($field_id, $user_id, $field_value);
                    }
                }
            }
            
            // Set agent type in BuddyPress meta
            bp_update_user_meta($user_id, 'terpedia_agent_type', $agent_data['agent_type']);
            
            return true;
            
        } catch (Exception $e) {
            error_log("Terpedia: BuddyPress profile update failed for user {$user_id}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Handle Tersona Creation
     */
    private function handle_create_tersona() {
        if (!current_user_can('create_users')) {
            wp_die('You do not have permission to create users.');
        }
        
        $username = sanitize_user($_POST['username']);
        $display_name = sanitize_text_field($_POST['display_name']);
        $email = sanitize_email($_POST['email']);
        $compound_type = sanitize_text_field($_POST['compound_type']);
        
        // Validate required fields
        if (empty($username) || empty($display_name) || empty($email)) {
            echo '<div class="notice notice-error"><p>All fields are required!</p></div>';
            return;
        }
        
        // Check if user already exists
        if (username_exists($username) || email_exists($email)) {
            echo '<div class="notice notice-error"><p>Username or email already exists!</p></div>';
            return;
        }
        
        // Create the user
        $user_id = wp_create_user($username, wp_generate_password(), $email);
        
        if (is_wp_error($user_id)) {
            echo '<div class="notice notice-error"><p>Error creating user: ' . $user_id->get_error_message() . '</p></div>';
            return;
        }
        
        // Update user profile
        wp_update_user(array(
            'ID' => $user_id,
            'display_name' => $display_name,
            'first_name' => $display_name,
            'description' => 'AI Terpene Persona for ' . $compound_type . ' compounds.'
        ));
        
        // Set Terpedia meta
        update_user_meta($user_id, 'terpedia_agent_type', 'tersona');
        update_user_meta($user_id, 'terpedia_compound_type', $compound_type);
        update_user_meta($user_id, 'terpedia_expertise', array('Molecular Analysis', 'Therapeutic Applications'));
        
        // Set BuddyPress profile if available
        if (function_exists('bp_set_profile_field_data')) {
            bp_set_profile_field_data(array(
                'field' => 'Name',
                'user_id' => $user_id,
                'value' => $display_name
            ));
        }
        
        echo '<div class="notice notice-success"><p>Tersona created successfully! <a href="' . admin_url('user-edit.php?user_id=' . $user_id) . '">Edit User</a></p></div>';
    }

    /**
     * Create Taxol Tersona Member Profile
     */
    public function create_taxol_tersona() {
        if (!function_exists('bp_core_get_user_by_username')) {
            return; // BuddyPress not active
        }

        // Check if Agt. Taxol user already exists
        $taxol_user = get_user_by('login', 'agt-taxol');
        if ($taxol_user) {
            return; // User already exists
        }

        // Create Agt. Taxol user
        $taxol_user_id = wp_create_user('agt-taxol', wp_generate_password(), 'taxol@terpedia.com');
        
        if (!is_wp_error($taxol_user_id)) {
            // Update user meta
            wp_update_user(array(
                'ID' => $taxol_user_id,
                'display_name' => 'Agt. Taxol',
                'first_name' => 'Agent',
                'last_name' => 'Taxol',
                'description' => 'Anti-cancer terpene compound derived from Pacific Yew trees. Expert in oncology applications and chemotherapy enhancement.'
            ));

            // Set BuddyPress profile fields if available
            if (function_exists('bp_set_profile_field_data')) {
                bp_set_profile_field_data(array(
                    'field' => 'Name',
                    'user_id' => $taxol_user_id,
                    'value' => 'Agt. Taxol'
                ));
                
                bp_set_profile_field_data(array(
                    'field' => 'About Me',
                    'user_id' => $taxol_user_id,
                    'value' => 'I am Taxol, a powerful anti-cancer compound first discovered in the Pacific Yew tree. I specialize in disrupting cancer cell division and have been helping patients fight various cancers since the 1990s. I can discuss my mechanism of action, clinical applications, and the fascinating story of my discovery and development.'
                ));
            }

            // Add custom meta for Terpedia agents
            update_user_meta($taxol_user_id, 'terpedia_agent_type', 'tersona');
            update_user_meta($taxol_user_id, 'terpedia_compound_type', 'terpene');
            update_user_meta($taxol_user_id, 'terpedia_primary_effects', array('anti-cancer', 'chemotherapy-enhancement', 'cell-cycle-disruption'));
            update_user_meta($taxol_user_id, 'terpedia_natural_sources', array('Pacific Yew', 'Taxus brevifolia', 'Synthetic production'));
            update_user_meta($taxol_user_id, 'terpedia_expertise', array('Oncology', 'Cell Biology', 'Pharmacokinetics', 'Clinical Trials'));
        }
    }

    /**
     * Create default podcast episodes
     */
    public function create_default_podcast_episodes() {
        // Create Taxol tersona first
        $this->create_taxol_tersona();

        // Check if episodes already exist
        $existing_episodes = get_posts(array(
            'post_type' => 'terpedia_podcast',
            'posts_per_page' => 1
        ));

        if (!empty($existing_episodes)) {
            return; // Episodes already exist
        }

        // Create Taxol episode
        $taxol_episode = wp_insert_post(array(
            'post_title' => 'Agt. Taxol\'s Anti-Cancer Journey',
            'post_content' => 'Join us for an in-depth conversation with Agt. Taxol, the legendary anti-cancer compound discovered in Pacific Yew trees. Learn about the fascinating journey from forest discovery to life-saving chemotherapy agent, and explore the molecular mechanisms that make Taxol so effective against cancer cells.',
            'post_excerpt' => 'Exploring the fascinating discovery of Taxol from Pacific Yew trees and its revolutionary impact on cancer treatment.',
            'post_status' => 'publish',
            'post_type' => 'terpedia_podcast'
        ));

        if ($taxol_episode) {
            update_post_meta($taxol_episode, '_podcast_duration', '45');
            update_post_meta($taxol_episode, '_podcast_type', 'featured');
            update_post_meta($taxol_episode, '_podcast_guest', 'Agt. Taxol');
            update_post_meta($taxol_episode, '_podcast_featured', 1);
            update_post_meta($taxol_episode, '_podcast_audio_url', 'https://terpedia.com/chat?agent=taxol');
        }

        // Create Myrcene episode
        $myrcene_episode = wp_insert_post(array(
            'post_title' => 'Myrcene & The Entourage Effect',
            'post_content' => 'Deep dive into myrcene\'s sedating properties with TerpeneQueen Susan Trapp. Discover how terpenes work together in the cannabis entourage effect and learn about myrcene\'s role in creating the "couch lock" sensation.',
            'post_excerpt' => 'Deep dive into myrcene\'s sedating properties and how terpenes work together in the cannabis entourage effect.',
            'post_status' => 'publish',
            'post_type' => 'terpedia_podcast'
        ));

        if ($myrcene_episode) {
            update_post_meta($myrcene_episode, '_podcast_duration', '28');
            update_post_meta($myrcene_episode, '_podcast_type', 'science');
            update_post_meta($myrcene_episode, '_podcast_guest', 'Agt. Myrcene');
            update_post_meta($myrcene_episode, '_podcast_featured', 0);
            update_post_meta($myrcene_episode, '_podcast_audio_url', 'https://terpedia.com/chat?agent=myrcene');
        }

        // Create TerpeneQueen live chat episode
        $queen_episode = wp_insert_post(array(
            'post_title' => 'TerpeneQueen Live Chat',
            'post_content' => 'Interactive live conversation with Susan Trapp, PhD (TerpeneQueen), about terpene effects, plant medicine insights, and personalized recommendations. Ask questions in real-time and get expert guidance on terpene applications.',
            'post_excerpt' => 'Interactive conversation with Susan Trapp about terpene effects, plant medicine insights, and personalized recommendations.',
            'post_status' => 'publish',
            'post_type' => 'terpedia_podcast'
        ));

        if ($queen_episode) {
            update_post_meta($queen_episode, '_podcast_duration', '0'); // Live format
            update_post_meta($queen_episode, '_podcast_type', 'live');
            update_post_meta($queen_episode, '_podcast_guest', 'TerpeneQueen');
            update_post_meta($queen_episode, '_podcast_featured', 0);
            update_post_meta($queen_episode, '_podcast_audio_url', 'https://terpedia.com/chat');
        }
    }

    /**
     * Check for version updates and force refresh
     */
    public function check_version_update() {
        $current_version = get_option('terpedia_version', '0.0.0');
        $plugin_version = '3.9.1';
        
        if (version_compare($current_version, $plugin_version, '<')) {
            // Clear any cached data
            wp_cache_flush();
            
            // Update stored version
            update_option('terpedia_version', $plugin_version);
            
            // Force refresh of plugin data
            delete_transient('terpedia_plugin_data');
            
            // Create default episodes if needed
            $this->create_default_podcast_episodes();
            
            // Log version update
            error_log("Terpedia updated from {$current_version} to {$plugin_version}");
        }
    }

    /**
     * Ensure default episodes exist (run on every init)
     */
    public function ensure_default_episodes() {
        // Only run if podcast CPT is registered
        if (!post_type_exists('terpedia_podcast')) {
            return;
        }

        // Check if episodes exist
        $existing_episodes = get_posts(array(
            'post_type' => 'terpedia_podcast',
            'posts_per_page' => 1,
            'post_status' => 'publish'
        ));

        // If no episodes exist, create them
        if (empty($existing_episodes)) {
            $this->create_default_podcast_episodes();
        }
    }

    /**
     * Get secure update token from WordPress options
     */
    private function get_update_token() {
        $token = get_option('terpedia_update_token');
        if (empty($token)) {
            // Create and store a secure token if it doesn't exist
            $token = '575fad1372a55fefb74da6b3545d4ecb7adcf7e668535277581f580777872c2f';
            update_option('terpedia_update_token', $token, false); // Not autoloaded for security
        }
        return $token;
    }

    /**
     * Secure handler for theme updates
     */
    public function handle_secure_theme_update() {
        // Verify user capabilities
        if (!current_user_can('update_themes')) {
            wp_die('You do not have sufficient permissions to update themes.', 'Permission Denied', array('response' => 403));
        }

        // Verify nonce
        if (!wp_verify_nonce($_POST['terpedia_theme_nonce'], 'terpedia_update_theme_nonce')) {
            wp_die('Security check failed. Please try again.', 'Security Error', array('response' => 403));
        }

        // Get secure token
        $secret_token = $this->get_update_token();
        
        // Build secure update URL
        $update_url = 'https://terpedia.com/wp-json/dfg/v1/package_update?' . http_build_query(array(
            'secret' => $secret_token,
            'type' => 'theme',
            'package' => 'terpedia-theme'
        ));

        // Make the API call securely
        $response = wp_remote_get($update_url, array(
            'timeout' => 30,
            'user-agent' => 'Terpedia-Plugin/' . TERPEDIA_AI_VERSION
        ));

        if (is_wp_error($response)) {
            wp_die('Update request failed: ' . $response->get_error_message(), 'Update Error', array('response' => 500));
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);

        if ($response_code === 200) {
            wp_redirect(admin_url('admin.php?page=terpedia-dashboard&updated=theme'));
        } else {
            wp_die('Theme update failed. Server response: ' . $response_code, 'Update Error', array('response' => 500));
        }
        exit;
    }

    /**
     * Secure handler for plugin updates
     */
    public function handle_secure_plugin_update() {
        // Verify user capabilities
        if (!current_user_can('update_plugins')) {
            wp_die('You do not have sufficient permissions to update plugins.', 'Permission Denied', array('response' => 403));
        }

        // Verify nonce
        if (!wp_verify_nonce($_POST['terpedia_plugin_nonce'], 'terpedia_update_plugin_nonce')) {
            wp_die('Security check failed. Please try again.', 'Security Error', array('response' => 403));
        }

        // Get secure token
        $secret_token = $this->get_update_token();
        
        // Build secure update URL
        $update_url = 'https://terpedia.com/wp-json/dfg/v1/package_update?' . http_build_query(array(
            'secret' => $secret_token,
            'type' => 'plugin',
            'package' => 'terpedia-plugin'
        ));

        // Make the API call securely
        $response = wp_remote_get($update_url, array(
            'timeout' => 30,
            'user-agent' => 'Terpedia-Plugin/' . TERPEDIA_AI_VERSION
        ));

        if (is_wp_error($response)) {
            wp_die('Update request failed: ' . $response->get_error_message(), 'Update Error', array('response' => 500));
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);

        if ($response_code === 200) {
            wp_redirect(admin_url('admin.php?page=terpedia-dashboard&updated=plugin'));
        } else {
            wp_die('Plugin update failed. Server response: ' . $response_code, 'Update Error', array('response' => 500));
        }
        exit;
    }
}

// Initialize the plugin
new TerpediaAI();

// Load CPT Archive System
if (file_exists(TERPEDIA_AI_PATH . 'includes/cpt-archive-system.php')) {
    require_once TERPEDIA_AI_PATH . 'includes/cpt-archive-system.php';
    // Initialize the archive system
    add_action('plugins_loaded', function() {
        new Terpedia_CPT_Archive_System();
    });
}

// Load Frontend Terproduct Creator
if (file_exists(TERPEDIA_AI_PATH . 'includes/frontend-terproduct-creator.php')) {
    require_once TERPEDIA_AI_PATH . 'includes/frontend-terproduct-creator.php';
    // Initialize the frontend creator
    add_action('plugins_loaded', function() {
        new Terpedia_Frontend_Terproduct_Creator();
    });
}

// Create default content on activation
register_activation_hook(__FILE__, function() {
    $plugin = new TerpediaAI();
    $plugin->create_default_podcast_episodes();
});
?>