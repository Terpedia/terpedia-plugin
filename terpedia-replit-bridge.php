<?php
/**
 * Plugin Name: Terpedia Replit Bridge
 * Plugin URI: https://terpedia.com
 * Description: Complete WordPress plugin that makes Replit functionality available, leveraging BuddyPress extensively for Tersonas and Agents. Uses WordPress MySQL for database and creates CPTs for Podcast, Newsletter, etc.
 * Version: 1.0.0
 * Author: Terpedia Team
 * License: GPL v2 or later
 * Requires at least: 6.0
 * Tested up to: 6.4
 * Requires PHP: 8.0
 * Network: false
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Plugin constants
define('TERPEDIA_REPLIT_VERSION', '1.0.0');
define('TERPEDIA_REPLIT_PLUGIN_URL', plugin_dir_url(__FILE__));
define('TERPEDIA_REPLIT_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('TERPEDIA_REPLIT_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main Plugin Class
 */
class TerpediaReplitBridge {
    
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('plugins_loaded', array($this, 'plugins_loaded'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        // Load plugin files
        $this->load_dependencies();
    }
    
    /**
     * Load required files
     */
    private function load_dependencies() {
        require_once TERPEDIA_REPLIT_PLUGIN_PATH . 'includes/class-database.php';
        require_once TERPEDIA_REPLIT_PLUGIN_PATH . 'includes/class-custom-post-types.php';
        require_once TERPEDIA_REPLIT_PLUGIN_PATH . 'includes/class-buddypress-integration.php';
        require_once TERPEDIA_REPLIT_PLUGIN_PATH . 'includes/class-ai-agents.php';
        require_once TERPEDIA_REPLIT_PLUGIN_PATH . 'includes/class-replit-api.php';
        require_once TERPEDIA_REPLIT_PLUGIN_PATH . 'includes/class-shortcodes.php';
        require_once TERPEDIA_REPLIT_PLUGIN_PATH . 'includes/class-ajax-handlers.php';
        require_once TERPEDIA_REPLIT_PLUGIN_PATH . 'includes/class-admin.php';
        require_once TERPEDIA_REPLIT_PLUGIN_PATH . 'includes/class-theme-integration.php';
    }
    
    /**
     * Initialize plugin
     */
    public function init() {
        // Initialize classes
        new Terpedia_Custom_Post_Types();
        new Terpedia_Shortcodes();
        new Terpedia_Ajax_Handlers();
        
        // Initialize BuddyPress integration if BuddyPress is active
        if (class_exists('BuddyPress')) {
            new Terpedia_BuddyPress_Integration();
        }
        
        // Initialize AI Agents
        new Terpedia_AI_Agents();
        
        // Initialize Replit API
        new Terpedia_Replit_API();
        
        // Initialize Theme Integration
        new Terpedia_Theme_Integration();
        
        // Enqueue scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
    }
    
    /**
     * Plugin loaded
     */
    public function plugins_loaded() {
        // Load textdomain for translations
        load_plugin_textdomain('terpedia-replit', false, dirname(TERPEDIA_REPLIT_PLUGIN_BASENAME) . '/languages');
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Create database tables
        Terpedia_Database::create_tables();
        
        // Create default AI agents and personas
        $this->create_default_agents();
        $this->create_default_personas();
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Set default options
        $this->set_default_options();
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Create default AI agents
     */
    private function create_default_agents() {
        $agents = array(
            array(
                'name' => 'Dr. Molecule Maven',
                'slug' => 'dr-molecule-maven',
                'expertise' => 'Molecular Analysis',
                'description' => 'Expert in molecular structures, chemical analysis, and biochemical pathways.',
                'avatar_url' => TERPEDIA_REPLIT_PLUGIN_URL . 'assets/avatars/dr-molecule-maven.png',
                'specializations' => array('Molecular Modeling', 'Chemical Analysis', 'Pathway Mapping'),
                'experience_years' => 15
            ),
            array(
                'name' => 'Professor Pharmakin',
                'slug' => 'professor-pharmakin',
                'expertise' => 'Pharmacology',
                'description' => 'Leading expert in pharmacological interactions and drug development.',
                'avatar_url' => TERPEDIA_REPLIT_PLUGIN_URL . 'assets/avatars/professor-pharmakin.png',
                'specializations' => array('Drug Interactions', 'Pharmacokinetics', 'Clinical Trials'),
                'experience_years' => 20
            ),
            array(
                'name' => 'Scholar Citeswell',
                'slug' => 'scholar-citeswell',
                'expertise' => 'Research & Citations',
                'description' => 'Research methodology expert and scientific literature specialist.',
                'avatar_url' => TERPEDIA_REPLIT_PLUGIN_URL . 'assets/avatars/scholar-citeswell.png',
                'specializations' => array('Literature Review', 'Research Methods', 'Citation Analysis'),
                'experience_years' => 12
            ),
            array(
                'name' => 'Judge Compliance',
                'slug' => 'judge-compliance',
                'expertise' => 'Regulatory Affairs',
                'description' => 'Regulatory compliance expert for pharmaceutical and nutraceutical industries.',
                'avatar_url' => TERPEDIA_REPLIT_PLUGIN_URL . 'assets/avatars/judge-compliance.png',
                'specializations' => array('FDA Regulations', 'Compliance Auditing', 'Legal Framework'),
                'experience_years' => 18
            ),
            array(
                'name' => 'Dr. Pawscription',
                'slug' => 'dr-pawscription',
                'expertise' => 'Veterinary Medicine',
                'description' => 'Veterinary expert specializing in animal health and terpene applications.',
                'avatar_url' => TERPEDIA_REPLIT_PLUGIN_URL . 'assets/avatars/dr-pawscription.png',
                'specializations' => array('Animal Health', 'Veterinary Pharmacology', 'Pet Wellness'),
                'experience_years' => 14
            ),
            array(
                'name' => 'Sage Holistica',
                'slug' => 'sage-holistica',
                'expertise' => 'Traditional Medicine',
                'description' => 'Traditional medicine expert with deep knowledge of herbal therapies.',
                'avatar_url' => TERPEDIA_REPLIT_PLUGIN_URL . 'assets/avatars/sage-holistica.png',
                'specializations' => array('Herbal Medicine', 'Traditional Therapies', 'Ethnobotany'),
                'experience_years' => 25
            ),
            array(
                'name' => 'Flora Fieldsworth',
                'slug' => 'flora-fieldsworth',
                'expertise' => 'Botany & Plant Science',
                'description' => 'Botanical expert specializing in plant biology and cultivation.',
                'avatar_url' => TERPEDIA_REPLIT_PLUGIN_URL . 'assets/avatars/flora-fieldsworth.png',
                'specializations' => array('Plant Biology', 'Cultivation Methods', 'Plant Genetics'),
                'experience_years' => 16
            ),
            array(
                'name' => 'Aroma Alchemist',
                'slug' => 'aroma-alchemist',
                'expertise' => 'Essential Oils & Aromatherapy',
                'description' => 'Essential oils expert and aromatherapy specialist.',
                'avatar_url' => TERPEDIA_REPLIT_PLUGIN_URL . 'assets/avatars/aroma-alchemist.png',
                'specializations' => array('Essential Oil Production', 'Aromatherapy', 'Scent Formulation'),
                'experience_years' => 13
            ),
            array(
                'name' => 'Blend Mastermind',
                'slug' => 'blend-mastermind',
                'expertise' => 'Formulation Science',
                'description' => 'Formulation expert specializing in terpene blending and optimization.',
                'avatar_url' => TERPEDIA_REPLIT_PLUGIN_URL . 'assets/avatars/blend-mastermind.png',
                'specializations' => array('Formulation Design', 'Blend Optimization', 'Product Development'),
                'experience_years' => 11
            ),
            array(
                'name' => 'Care Companion',
                'slug' => 'care-companion',
                'expertise' => 'Patient Care & Wellness',
                'description' => 'Patient care specialist focused on personalized wellness solutions.',
                'avatar_url' => TERPEDIA_REPLIT_PLUGIN_URL . 'assets/avatars/care-companion.png',
                'specializations' => array('Patient Care', 'Wellness Coaching', 'Personalized Medicine'),
                'experience_years' => 17
            ),
            array(
                'name' => 'Doc Datawise',
                'slug' => 'doc-datawise',
                'expertise' => 'Data Science & Statistics',
                'description' => 'Data science expert specializing in statistical analysis and research.',
                'avatar_url' => TERPEDIA_REPLIT_PLUGIN_URL . 'assets/avatars/doc-datawise.png',
                'specializations' => array('Statistical Analysis', 'Data Mining', 'Research Analytics'),
                'experience_years' => 10
            ),
            array(
                'name' => 'Dr. Ligand Linker',
                'slug' => 'dr-ligand-linker',
                'expertise' => 'Protein Interactions',
                'description' => 'Protein interaction expert and molecular binding specialist.',
                'avatar_url' => TERPEDIA_REPLIT_PLUGIN_URL . 'assets/avatars/dr-ligand-linker.png',
                'specializations' => array('Protein Binding', 'Molecular Interactions', 'Structural Biology'),
                'experience_years' => 19
            ),
            array(
                'name' => 'Agt. Prospector',
                'slug' => 'agt-prospector',
                'expertise' => 'Novel Discovery',
                'description' => 'Discovery specialist focused on finding new terpenes and applications.',
                'avatar_url' => TERPEDIA_REPLIT_PLUGIN_URL . 'assets/avatars/agt-prospector.png',
                'specializations' => array('Novel Compounds', 'Discovery Research', 'Innovation'),
                'experience_years' => 8
            )
        );
        
        foreach ($agents as $agent_data) {
            $this->create_agent_user($agent_data);
        }
    }
    
    /**
     * Create default personas (Tersonas)
     */
    private function create_default_personas() {
        $personas = array(
            array(
                'name' => 'TerpeneQueen',
                'slug' => 'terpene-queen',
                'role' => 'Podcast Host',
                'description' => 'Dynamic podcast host specializing in terpene education and entertainment.',
                'avatar_url' => TERPEDIA_REPLIT_PLUGIN_URL . 'assets/avatars/terpene-queen.png',
                'interests' => array('Podcasting', 'Education', 'Entertainment'),
                'personality_traits' => array('Engaging', 'Knowledgeable', 'Enthusiastic')
            ),
            array(
                'name' => 'Agt. Taxol',
                'slug' => 'agt-taxol',
                'role' => 'Research Specialist',
                'description' => 'Research-focused persona specializing in anti-cancer terpene compounds.',
                'avatar_url' => TERPEDIA_REPLIT_PLUGIN_URL . 'assets/avatars/agt-taxol.png',
                'interests' => array('Cancer Research', 'Taxol Studies', 'Medical Applications'),
                'personality_traits' => array('Analytical', 'Dedicated', 'Precise')
            ),
            array(
                'name' => 'Myrcene Mystic',
                'slug' => 'myrcene-mystic',
                'role' => 'Wellness Guide',
                'description' => 'Wellness-focused persona with expertise in myrcene and relaxation.',
                'avatar_url' => TERPEDIA_REPLIT_PLUGIN_URL . 'assets/avatars/myrcene-mystic.png',
                'interests' => array('Wellness', 'Relaxation', 'Mindfulness'),
                'personality_traits' => array('Calm', 'Wise', 'Nurturing')
            ),
            array(
                'name' => 'Limonene Luna',
                'slug' => 'limonene-luna',
                'role' => 'Mood Specialist',
                'description' => 'Mood enhancement specialist focusing on limonene and positivity.',
                'avatar_url' => TERPEDIA_REPLIT_PLUGIN_URL . 'assets/avatars/limonene-luna.png',
                'interests' => array('Mood Enhancement', 'Positivity', 'Mental Health'),
                'personality_traits' => array('Uplifting', 'Optimistic', 'Supportive')
            ),
            array(
                'name' => 'Pinene Pioneer',
                'slug' => 'pinene-pioneer',
                'role' => 'Focus Expert',
                'description' => 'Focus and alertness specialist with pinene expertise.',
                'avatar_url' => TERPEDIA_REPLIT_PLUGIN_URL . 'assets/avatars/pinene-pioneer.png',
                'interests' => array('Focus', 'Alertness', 'Cognitive Enhancement'),
                'personality_traits' => array('Sharp', 'Focused', 'Driven')
            ),
            array(
                'name' => 'Linalool Sage',
                'slug' => 'linalool-sage',
                'role' => 'Sleep Specialist',
                'description' => 'Sleep and relaxation expert specializing in linalool applications.',
                'avatar_url' => TERPEDIA_REPLIT_PLUGIN_URL . 'assets/avatars/linalool-sage.png',
                'interests' => array('Sleep Health', 'Relaxation', 'Stress Relief'),
                'personality_traits' => array('Peaceful', 'Gentle', 'Restorative')
            ),
            array(
                'name' => 'Caryophyllene Champion',
                'slug' => 'caryophyllene-champion',
                'role' => 'Pain Management Expert',
                'description' => 'Pain management specialist with caryophyllene expertise.',
                'avatar_url' => TERPEDIA_REPLIT_PLUGIN_URL . 'assets/avatars/caryophyllene-champion.png',
                'interests' => array('Pain Management', 'Anti-inflammatory', 'Health Advocacy'),
                'personality_traits' => array('Compassionate', 'Strong', 'Advocate')
            ),
            array(
                'name' => 'Humulene Healer',
                'slug' => 'humulene-healer',
                'role' => 'Anti-inflammatory Specialist',
                'description' => 'Anti-inflammatory expert focusing on humulene therapeutic applications.',
                'avatar_url' => TERPEDIA_REPLIT_PLUGIN_URL . 'assets/avatars/humulene-healer.png',
                'interests' => array('Anti-inflammatory', 'Healing', 'Natural Medicine'),
                'personality_traits' => array('Healing', 'Natural', 'Holistic')
            )
        );
        
        foreach ($personas as $persona_data) {
            $this->create_persona_user($persona_data);
        }
    }
    
    /**
     * Create agent user account with BuddyPress profile
     */
    private function create_agent_user($agent_data) {
        // Check if user already exists
        $existing_user = get_user_by('login', $agent_data['slug']);
        if ($existing_user) {
            return $existing_user->ID;
        }
        
        // Create user
        $user_id = wp_create_user(
            $agent_data['slug'],
            wp_generate_password(),
            $agent_data['slug'] . '@terpedia.ai'
        );
        
        if (is_wp_error($user_id)) {
            return false;
        }
        
        // Update user meta
        update_user_meta($user_id, 'display_name', $agent_data['name']);
        update_user_meta($user_id, 'first_name', explode(' ', $agent_data['name'])[0]);
        update_user_meta($user_id, 'last_name', explode(' ', $agent_data['name'])[1] ?? '');
        update_user_meta($user_id, 'description', $agent_data['description']);
        
        // Terpedia-specific meta
        update_user_meta($user_id, 'terpedia_agent_type', 'expert');
        update_user_meta($user_id, 'terpedia_expertise', $agent_data['expertise']);
        update_user_meta($user_id, 'terpedia_specializations', $agent_data['specializations']);
        update_user_meta($user_id, 'terpedia_experience_years', $agent_data['experience_years']);
        update_user_meta($user_id, 'terpedia_avatar_url', $agent_data['avatar_url']);
        update_user_meta($user_id, 'terpedia_is_available', 1);
        update_user_meta($user_id, 'terpedia_consultation_count', 0);
        update_user_meta($user_id, 'terpedia_rating', 5.0);
        
        // Set user role
        $user = new WP_User($user_id);
        $user->set_role('terpedia_agent');
        
        return $user_id;
    }
    
    /**
     * Create persona user account with BuddyPress profile
     */
    private function create_persona_user($persona_data) {
        // Check if user already exists
        $existing_user = get_user_by('login', $persona_data['slug']);
        if ($existing_user) {
            return $existing_user->ID;
        }
        
        // Create user
        $user_id = wp_create_user(
            $persona_data['slug'],
            wp_generate_password(),
            $persona_data['slug'] . '@terpedia.persona'
        );
        
        if (is_wp_error($user_id)) {
            return false;
        }
        
        // Update user meta
        update_user_meta($user_id, 'display_name', $persona_data['name']);
        update_user_meta($user_id, 'first_name', explode(' ', $persona_data['name'])[0]);
        update_user_meta($user_id, 'last_name', explode(' ', $persona_data['name'])[1] ?? '');
        update_user_meta($user_id, 'description', $persona_data['description']);
        
        // Terpedia-specific meta
        update_user_meta($user_id, 'terpedia_agent_type', 'persona');
        update_user_meta($user_id, 'terpedia_persona_role', $persona_data['role']);
        update_user_meta($user_id, 'terpedia_interests', $persona_data['interests']);
        update_user_meta($user_id, 'terpedia_personality_traits', $persona_data['personality_traits']);
        update_user_meta($user_id, 'terpedia_avatar_url', $persona_data['avatar_url']);
        update_user_meta($user_id, 'terpedia_is_available', 1);
        
        // Set user role
        $user = new WP_User($user_id);
        $user->set_role('terpedia_persona');
        
        return $user_id;
    }
    
    /**
     * Set default plugin options
     */
    private function set_default_options() {
        add_option('terpedia_replit_version', TERPEDIA_REPLIT_VERSION);
        add_option('terpedia_replit_api_endpoint', '');
        add_option('terpedia_replit_api_key', '');
        add_option('terpedia_buddypress_integration', 1);
        add_option('terpedia_ai_enabled', 1);
        add_option('terpedia_chat_enabled', 1);
        add_option('terpedia_podcast_enabled', 1);
        add_option('terpedia_newsletter_enabled', 1);
    }
    
    /**
     * Enqueue frontend scripts and styles
     */
    public function enqueue_scripts() {
        wp_enqueue_style(
            'terpedia-replit-style',
            TERPEDIA_REPLIT_PLUGIN_URL . 'assets/css/frontend.css',
            array(),
            TERPEDIA_REPLIT_VERSION
        );
        
        wp_enqueue_script(
            'terpedia-replit-script',
            TERPEDIA_REPLIT_PLUGIN_URL . 'assets/js/frontend.js',
            array('jquery'),
            TERPEDIA_REPLIT_VERSION,
            true
        );
        
        // Localize script for AJAX
        wp_localize_script('terpedia-replit-script', 'terpedia_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('terpedia_ajax_nonce'),
            'strings' => array(
                'loading' => __('Loading...', 'terpedia-replit'),
                'error' => __('An error occurred. Please try again.', 'terpedia-replit'),
                'success' => __('Success!', 'terpedia-replit')
            )
        ));
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function admin_enqueue_scripts($hook) {
        wp_enqueue_style(
            'terpedia-replit-admin-style',
            TERPEDIA_REPLIT_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            TERPEDIA_REPLIT_VERSION
        );
        
        wp_enqueue_script(
            'terpedia-replit-admin-script',
            TERPEDIA_REPLIT_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            TERPEDIA_REPLIT_VERSION,
            true
        );
    }
}

// Initialize the plugin
new TerpediaReplitBridge();

/**
 * Custom user roles for agents and personas
 */
function terpedia_add_custom_roles() {
    add_role('terpedia_agent', 'Terpedia Agent', array(
        'read' => true,
        'edit_posts' => false,
        'delete_posts' => false,
        'publish_posts' => false,
        'upload_files' => false,
    ));
    
    add_role('terpedia_persona', 'Terpedia Persona', array(
        'read' => true,
        'edit_posts' => false,
        'delete_posts' => false,
        'publish_posts' => false,
        'upload_files' => false,
    ));
}
add_action('init', 'terpedia_add_custom_roles');

/**
 * Plugin compatibility check
 */
function terpedia_replit_compatibility_check() {
    $required_plugins = array(
        'buddypress/bp-loader.php' => 'BuddyPress',
    );
    
    $missing_plugins = array();
    
    foreach ($required_plugins as $plugin_file => $plugin_name) {
        if (!is_plugin_active($plugin_file)) {
            $missing_plugins[] = $plugin_name;
        }
    }
    
    if (!empty($missing_plugins)) {
        add_action('admin_notices', function() use ($missing_plugins) {
            echo '<div class="notice notice-warning is-dismissible">';
            echo '<p><strong>Terpedia Replit Bridge:</strong> The following recommended plugins are not active: ' . implode(', ', $missing_plugins) . '</p>';
            echo '</div>';
        });
    }
}
add_action('admin_init', 'terpedia_replit_compatibility_check');