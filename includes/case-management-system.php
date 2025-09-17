<?php
/**
 * Terpedia Case Management System
 * Comprehensive patient health record management with chat, vital signs, and intervention tracking
 */

if (!defined('ABSPATH')) {
    exit;
}

class Terpedia_Case_Management_System {
    
    private $openrouter_api;
    
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        
        // Database setup
        register_activation_hook(__FILE__, array($this, 'create_case_tables'));
        
        // AJAX handlers
        add_action('wp_ajax_terpedia_save_case_data', array($this, 'ajax_save_case_data'));
        add_action('wp_ajax_terpedia_send_case_message', array($this, 'ajax_send_case_message'));
        add_action('wp_ajax_terpedia_get_case_messages', array($this, 'ajax_get_case_messages'));
        add_action('wp_ajax_terpedia_save_vital_signs', array($this, 'ajax_save_vital_signs'));
        add_action('wp_ajax_terpedia_get_vital_signs', array($this, 'ajax_get_vital_signs'));
        add_action('wp_ajax_terpedia_save_intervention', array($this, 'ajax_save_intervention'));
        add_action('wp_ajax_terpedia_get_interventions', array($this, 'ajax_get_interventions'));
        
        // Initialize OpenRouter API
        if (class_exists('TerpediaOpenRouterHandler')) {
            $this->openrouter_api = new TerpediaOpenRouterHandler();
        }
    }
    
    public function init() {
        // Register Case post type
        $this->register_case_cpt();
        
        // Add case rewrite rules
        add_action('init', array($this, 'add_case_rewrite_rules'), 25);
        add_filter('query_vars', array($this, 'add_case_query_vars'));
        
        // Template handling
        add_filter('template_include', array($this, 'case_template_handler'));
    }
    
    /**
     * Register Case Custom Post Type
     */
    public function register_case_cpt() {
        if (!post_type_exists('terpedia_case')) {
            register_post_type('terpedia_case', array(
                'labels' => array(
                    'name' => 'Patient Cases',
                    'singular_name' => 'Patient Case',
                    'add_new' => 'Add New Case',
                    'add_new_item' => 'Add New Patient Case',
                    'edit_item' => 'Edit Patient Case',
                    'new_item' => 'New Patient Case',
                    'view_item' => 'View Patient Case',
                    'search_items' => 'Search Cases',
                    'not_found' => 'No cases found',
                    'not_found_in_trash' => 'No cases found in trash',
                    'all_items' => 'All Cases',
                    'menu_name' => 'Patient Cases'
                ),
                'public' => true,
                'has_archive' => true,
                'supports' => array('title', 'editor', 'author', 'thumbnail', 'excerpt', 'custom-fields', 'revisions'),
                'menu_icon' => 'dashicons-heart',
                'menu_position' => 26,
                'show_in_rest' => true,
                'capability_type' => 'post',
                'hierarchical' => false,
                'rewrite' => array('slug' => 'case'),
                'show_in_menu' => true
            ));
        }
    }
    
    /**
     * Add case rewrite rules
     */
    public function add_case_rewrite_rules() {
        add_rewrite_rule('^cases/?$', 'index.php?post_type=terpedia_case', 'top');
        add_rewrite_rule('^case/([0-9]+)/?$', 'index.php?post_type=terpedia_case&p=$matches[1]', 'top');
        add_rewrite_rule('^case/([0-9]+)/chat/?$', 'index.php?case_id=$matches[1]&case_view=chat', 'top');
        add_rewrite_rule('^case/([0-9]+)/vitals/?$', 'index.php?case_id=$matches[1]&case_view=vitals', 'top');
        add_rewrite_rule('^case/([0-9]+)/interventions/?$', 'index.php?case_id=$matches[1]&case_view=interventions', 'top');
    }
    
    /**
     * Add query vars
     */
    public function add_case_query_vars($vars) {
        $vars[] = 'case_id';
        $vars[] = 'case_view';
        return $vars;
    }
    
    /**
     * Handle case templates
     */
    public function case_template_handler($template) {
        if (is_post_type_archive('terpedia_case')) {
            return $this->render_cases_archive();
        }
        
        if (is_singular('terpedia_case')) {
            return $this->render_single_case();
        }
        
        // Handle special case views
        $case_id = get_query_var('case_id');
        $case_view = get_query_var('case_view');
        
        if ($case_id && $case_view) {
            switch ($case_view) {
                case 'chat':
                    return $this->render_case_chat($case_id);
                case 'vitals':
                    return $this->render_case_vitals($case_id);
                case 'interventions':
                    return $this->render_case_interventions($case_id);
            }
        }
        
        return $template;
    }
    
    /**
     * Create database tables for case management
     */
    public function create_case_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Case messages table
        $table_messages = $wpdb->prefix . 'terpedia_case_messages';
        $sql_messages = "CREATE TABLE $table_messages (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            case_id bigint(20) NOT NULL,
            user_id bigint(20) DEFAULT 0,
            user_type varchar(20) DEFAULT 'human',
            message text NOT NULL,
            timestamp datetime DEFAULT CURRENT_TIMESTAMP,
            message_type varchar(50) DEFAULT 'chat',
            metadata longtext DEFAULT NULL,
            PRIMARY KEY (id),
            KEY case_id (case_id),
            KEY timestamp (timestamp)
        ) $charset_collate;";
        
        // Vital signs table
        $table_vitals = $wpdb->prefix . 'terpedia_case_vitals';
        $sql_vitals = "CREATE TABLE $table_vitals (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            case_id bigint(20) NOT NULL,
            recorded_by bigint(20) NOT NULL,
            recorded_date datetime DEFAULT CURRENT_TIMESTAMP,
            heart_rate int(11) DEFAULT NULL,
            blood_pressure_systolic int(11) DEFAULT NULL,
            blood_pressure_diastolic int(11) DEFAULT NULL,
            weight decimal(8,2) DEFAULT NULL,
            temperature decimal(5,2) DEFAULT NULL,
            respiratory_rate int(11) DEFAULT NULL,
            notes text DEFAULT NULL,
            PRIMARY KEY (id),
            KEY case_id (case_id),
            KEY recorded_date (recorded_date)
        ) $charset_collate;";
        
        // Interventions table
        $table_interventions = $wpdb->prefix . 'terpedia_case_interventions';
        $sql_interventions = "CREATE TABLE $table_interventions (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            case_id bigint(20) NOT NULL,
            recorded_by bigint(20) NOT NULL,
            intervention_date datetime DEFAULT CURRENT_TIMESTAMP,
            intervention_type varchar(100) NOT NULL,
            intervention_category varchar(50) DEFAULT 'treatment',
            description text NOT NULL,
            outcome text DEFAULT NULL,
            follow_up_required tinyint(1) DEFAULT 0,
            follow_up_date datetime DEFAULT NULL,
            status varchar(20) DEFAULT 'active',
            metadata longtext DEFAULT NULL,
            PRIMARY KEY (id),
            KEY case_id (case_id),
            KEY intervention_date (intervention_date),
            KEY status (status)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_messages);
        dbDelta($sql_vitals);
        dbDelta($sql_interventions);
    }
    
    /**
     * Enqueue scripts and styles
     */
    public function enqueue_scripts() {
        if (is_post_type_archive('terpedia_case') || is_singular('terpedia_case') || get_query_var('case_id')) {
            wp_enqueue_style(
                'terpedia-case-management',
                plugin_dir_url(__FILE__) . '../assets/css/case-management.css',
                array(),
                '1.0.0'
            );
            
            wp_enqueue_script(
                'terpedia-case-management',
                plugin_dir_url(__FILE__) . '../assets/js/case-management.js',
                array('jquery'),
                '1.0.0',
                true
            );
            
            // Chart.js for vital signs graphing
            wp_enqueue_script(
                'chartjs',
                'https://cdn.jsdelivr.net/npm/chart.js',
                array(),
                '3.9.1',
                true
            );
            
            // Localize script for AJAX
            wp_localize_script('terpedia-case-management', 'terpedia_case_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('terpedia_case_nonce')
            ));
        }
    }
    
    /**
     * Render cases archive page
     */
    private function render_cases_archive() {
        $html = "<!DOCTYPE html><html><head>";
        $html .= "<title>Patient Cases - Case Management System</title>";
        $html .= "<meta name='viewport' content='width=device-width, initial-scale=1'>";
        $html .= $this->get_case_styles();
        $html .= "</head><body>";
        
        $html .= "<div class='terpedia-case-container'>";
        $html .= "<header class='case-header'>";
        $html .= "<h1>🏥 Patient Case Management System</h1>";
        $html .= "<p>Comprehensive health record management with AI assistance</p>";
        $html .= "<div class='case-actions'>";
        $html .= "<button onclick='createNewCase()' class='btn btn-primary'>+ New Case</button>";
        $html .= "</div>";
        $html .= "</header>";
        
        // Get all cases
        $cases = get_posts(array(
            'post_type' => 'terpedia_case',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'orderby' => 'date',
            'order' => 'DESC'
        ));
        
        $html .= "<div class='cases-grid'>";
        
        foreach ($cases as $case) {
            $case_id = $case->ID;
            $patient_name = get_post_meta($case_id, 'patient_name', true) ?: 'Unknown Patient';
            $species = get_post_meta($case_id, 'species', true) ?: 'Not specified';
            $status = get_post_meta($case_id, 'case_status', true) ?: 'active';
            $last_updated = get_the_modified_date('Y-m-d H:i', $case);
            
            $html .= "<div class='case-card' data-case-id='{$case_id}'>";
            $html .= "<div class='case-card-header'>";
            $html .= "<h3><a href='/case/{$case_id}'>{$patient_name}</a></h3>";
            $html .= "<span class='case-status status-{$status}'>{$status}</span>";
            $html .= "</div>";
            $html .= "<div class='case-card-body'>";
            $html .= "<p><strong>Species:</strong> {$species}</p>";
            $html .= "<p><strong>Case ID:</strong> #{$case_id}</p>";
            $html .= "<p><strong>Last Updated:</strong> {$last_updated}</p>";
            $html .= "</div>";
            $html .= "<div class='case-card-actions'>";
            $html .= "<a href='/case/{$case_id}' class='btn btn-outline'>View Case</a>";
            $html .= "<a href='/case/{$case_id}/chat' class='btn btn-outline'>Chat</a>";
            $html .= "</div>";
            $html .= "</div>";
        }
        
        if (empty($cases)) {
            $html .= "<div class='no-cases'>";
            $html .= "<p>No cases found. Create your first case to get started.</p>";
            $html .= "</div>";
        }
        
        $html .= "</div>"; // cases-grid
        $html .= "</div>"; // container
        
        $html .= $this->get_case_scripts();
        $html .= "</body></html>";
        
        echo $html;
        exit;
    }
    
    /**
     * Render single case page
     */
    private function render_single_case() {
        global $post;
        $case_id = $post->ID;
        
        $patient_name = get_post_meta($case_id, 'patient_name', true) ?: 'Unknown Patient';
        $species = get_post_meta($case_id, 'species', true) ?: '';
        $breed = get_post_meta($case_id, 'breed', true) ?: '';
        $age = get_post_meta($case_id, 'age', true) ?: '';
        $weight = get_post_meta($case_id, 'weight', true) ?: '';
        $owner_name = get_post_meta($case_id, 'owner_name', true) ?: '';
        $owner_contact = get_post_meta($case_id, 'owner_contact', true) ?: '';
        $case_status = get_post_meta($case_id, 'case_status', true) ?: 'active';
        
        $html = "<!DOCTYPE html><html><head>";
        $html .= "<title>Case: {$patient_name} - Case Management</title>";
        $html .= "<meta name='viewport' content='width=device-width, initial-scale=1'>";
        $html .= $this->get_case_styles();
        $html .= "</head><body>";
        
        $html .= "<div class='terpedia-case-container'>";
        
        // Navigation
        $html .= "<nav class='case-nav'>";
        $html .= "<a href='/cases' class='nav-back'>← All Cases</a>";
        $html .= "<div class='case-nav-tabs'>";
        $html .= "<a href='/case/{$case_id}' class='nav-tab active'>Overview</a>";
        $html .= "<a href='/case/{$case_id}/chat' class='nav-tab'>Chat</a>";
        $html .= "<a href='/case/{$case_id}/vitals' class='nav-tab'>Vital Signs</a>";
        $html .= "<a href='/case/{$case_id}/interventions' class='nav-tab'>Interventions</a>";
        $html .= "</div>";
        $html .= "</nav>";
        
        // Case header
        $html .= "<header class='case-detail-header'>";
        $html .= "<div class='case-info'>";
        $html .= "<h1>{$patient_name}</h1>";
        $html .= "<span class='case-status status-{$case_status}'>{$case_status}</span>";
        $html .= "</div>";
        $html .= "<div class='case-actions'>";
        $html .= "<button onclick='editCase({$case_id})' class='btn btn-primary'>Edit Case</button>";
        $html .= "<button onclick='exportCase({$case_id})' class='btn btn-outline'>Export</button>";
        $html .= "</div>";
        $html .= "</header>";
        
        // Case details form
        $html .= "<div class='case-content'>";
        $html .= "<form id='case-form' data-case-id='{$case_id}'>";
        
        $html .= "<div class='form-grid'>";
        
        // Patient Information
        $html .= "<div class='form-section'>";
        $html .= "<h3>Patient Information</h3>";
        $html .= "<div class='form-group'>";
        $html .= "<label>Patient Name</label>";
        $html .= "<input type='text' name='patient_name' value='{$patient_name}' required>";
        $html .= "</div>";
        $html .= "<div class='form-row'>";
        $html .= "<div class='form-group'>";
        $html .= "<label>Species</label>";
        $html .= "<input type='text' name='species' value='{$species}'>";
        $html .= "</div>";
        $html .= "<div class='form-group'>";
        $html .= "<label>Breed</label>";
        $html .= "<input type='text' name='breed' value='{$breed}'>";
        $html .= "</div>";
        $html .= "</div>";
        $html .= "<div class='form-row'>";
        $html .= "<div class='form-group'>";
        $html .= "<label>Age</label>";
        $html .= "<input type='text' name='age' value='{$age}'>";
        $html .= "</div>";
        $html .= "<div class='form-group'>";
        $html .= "<label>Weight</label>";
        $html .= "<input type='text' name='weight' value='{$weight}'>";
        $html .= "</div>";
        $html .= "</div>";
        $html .= "</div>";
        
        // Owner Information
        $html .= "<div class='form-section'>";
        $html .= "<h3>Owner Information</h3>";
        $html .= "<div class='form-group'>";
        $html .= "<label>Owner Name</label>";
        $html .= "<input type='text' name='owner_name' value='{$owner_name}'>";
        $html .= "</div>";
        $html .= "<div class='form-group'>";
        $html .= "<label>Contact Information</label>";
        $html .= "<textarea name='owner_contact'>{$owner_contact}</textarea>";
        $html .= "</div>";
        $html .= "</div>";
        
        $html .= "</div>"; // form-grid
        
        // Case Notes
        $html .= "<div class='form-section'>";
        $html .= "<h3>Case Notes</h3>";
        $html .= "<div class='form-group'>";
        $html .= "<textarea name='case_notes' rows='6' placeholder='Enter case notes, symptoms, observations...'>" . $post->post_content . "</textarea>";
        $html .= "</div>";
        $html .= "</div>";
        
        $html .= "<div class='form-actions'>";
        $html .= "<button type='submit' class='btn btn-primary'>Save Changes</button>";
        $html .= "<button type='button' onclick='resetForm()' class='btn btn-outline'>Reset</button>";
        $html .= "</div>";
        
        $html .= "</form>";
        $html .= "</div>"; // case-content
        $html .= "</div>"; // container
        
        $html .= $this->get_case_scripts();
        $html .= "</body></html>";
        
        echo $html;
        exit;
    }
    
    /**
     * Render case chat interface
     */
    private function render_case_chat($case_id) {
        $case = get_post($case_id);
        if (!$case || $case->post_type !== 'terpedia_case') {
            wp_die('Case not found');
        }
        
        $patient_name = get_post_meta($case_id, 'patient_name', true) ?: 'Unknown Patient';
        
        $html = "<!DOCTYPE html><html><head>";
        $html .= "<title>Chat: {$patient_name} - Case Management</title>";
        $html .= "<meta name='viewport' content='width=device-width, initial-scale=1'>";
        $html .= $this->get_case_styles();
        $html .= "</head><body>";
        
        $html .= "<div class='terpedia-case-container'>";
        
        // Navigation
        $html .= "<nav class='case-nav'>";
        $html .= "<a href='/cases' class='nav-back'>← All Cases</a>";
        $html .= "<div class='case-nav-tabs'>";
        $html .= "<a href='/case/{$case_id}' class='nav-tab'>Overview</a>";
        $html .= "<a href='/case/{$case_id}/chat' class='nav-tab active'>Chat</a>";
        $html .= "<a href='/case/{$case_id}/vitals' class='nav-tab'>Vital Signs</a>";
        $html .= "<a href='/case/{$case_id}/interventions' class='nav-tab'>Interventions</a>";
        $html .= "</div>";
        $html .= "</nav>";
        
        // Chat interface
        $html .= "<div class='chat-container'>";
        $html .= "<div class='chat-header'>";
        $html .= "<h2>💬 Case Discussion: {$patient_name}</h2>";
        $html .= "<div class='chat-status'>";
        $html .= "<span class='ai-status'>🤖 AI Assistant Available</span>";
        $html .= "</div>";
        $html .= "</div>";
        
        $html .= "<div id='chat-messages' class='chat-messages' data-case-id='{$case_id}'>";
        $html .= "<!-- Messages will be loaded via AJAX -->";
        $html .= "</div>";
        
        $html .= "<div class='chat-input-container'>";
        $html .= "<div class='chat-input-form'>";
        $html .= "<textarea id='chat-input' placeholder='Type your message or ask the AI assistant for help...' rows='3'></textarea>";
        $html .= "<div class='chat-actions'>";
        $html .= "<button onclick='sendMessage()' class='btn btn-primary'>Send</button>";
        $html .= "<button onclick='askAI()' class='btn btn-ai'>🤖 Ask AI</button>";
        $html .= "</div>";
        $html .= "</div>";
        $html .= "</div>";
        
        $html .= "</div>"; // chat-container
        $html .= "</div>"; // main container
        
        $html .= $this->get_case_scripts();
        $html .= "</body></html>";
        
        echo $html;
        exit;
    }
    
    /**
     * Render case vital signs page
     */
    private function render_case_vitals($case_id) {
        $case = get_post($case_id);
        if (!$case || $case->post_type !== 'terpedia_case') {
            wp_die('Case not found');
        }
        
        $patient_name = get_post_meta($case_id, 'patient_name', true) ?: 'Unknown Patient';
        
        $html = "<!DOCTYPE html><html><head>";
        $html .= "<title>Vital Signs: {$patient_name} - Case Management</title>";
        $html .= "<meta name='viewport' content='width=device-width, initial-scale=1'>";
        $html .= $this->get_case_styles();
        $html .= "</head><body>";
        
        $html .= "<div class='terpedia-case-container'>";
        
        // Navigation
        $html .= "<nav class='case-nav'>";
        $html .= "<a href='/cases' class='nav-back'>← All Cases</a>";
        $html .= "<div class='case-nav-tabs'>";
        $html .= "<a href='/case/{$case_id}' class='nav-tab'>Overview</a>";
        $html .= "<a href='/case/{$case_id}/chat' class='nav-tab'>Chat</a>";
        $html .= "<a href='/case/{$case_id}/vitals' class='nav-tab active'>Vital Signs</a>";
        $html .= "<a href='/case/{$case_id}/interventions' class='nav-tab'>Interventions</a>";
        $html .= "</div>";
        $html .= "</nav>";
        
        // Vital Signs interface
        $html .= "<div class='vitals-container'>";
        $html .= "<div class='vitals-header'>";
        $html .= "<h2>📊 Vital Signs: {$patient_name}</h2>";
        $html .= "<div class='vitals-actions'>";
        $html .= "<button onclick='toggleVitalsForm()' class='btn btn-primary'>+ Record Vitals</button>";
        $html .= "</div>";
        $html .= "</div>";
        
        // Vital signs entry form
        $html .= "<div id='vitals-form-container' class='vitals-form' style='display: none;'>";
        $html .= "<h3>Record New Vital Signs</h3>";
        $html .= "<form id='vitals-form' data-case-id='{$case_id}'>";
        
        $html .= "<div class='vitals-grid'>";
        $html .= "<div class='form-group'>";
        $html .= "<label>Heart Rate (BPM)</label>";
        $html .= "<input type='number' name='heart_rate' min='0' max='300'>";
        $html .= "</div>";
        $html .= "<div class='form-group'>";
        $html .= "<label>Blood Pressure (Systolic)</label>";
        $html .= "<input type='number' name='blood_pressure_systolic' min='0' max='300'>";
        $html .= "</div>";
        $html .= "<div class='form-group'>";
        $html .= "<label>Blood Pressure (Diastolic)</label>";
        $html .= "<input type='number' name='blood_pressure_diastolic' min='0' max='200'>";
        $html .= "</div>";
        $html .= "<div class='form-group'>";
        $html .= "<label>Weight</label>";
        $html .= "<input type='number' name='weight' step='0.1' min='0'>";
        $html .= "</div>";
        $html .= "<div class='form-group'>";
        $html .= "<label>Temperature (°F)</label>";
        $html .= "<input type='number' name='temperature' step='0.1' min='90' max='110'>";
        $html .= "</div>";
        $html .= "<div class='form-group'>";
        $html .= "<label>Respiratory Rate</label>";
        $html .= "<input type='number' name='respiratory_rate' min='0' max='100'>";
        $html .= "</div>";
        $html .= "</div>";
        
        $html .= "<div class='form-group'>";
        $html .= "<label>Notes</label>";
        $html .= "<textarea name='notes' rows='3' placeholder='Additional observations...'></textarea>";
        $html .= "</div>";
        
        $html .= "<div class='form-actions'>";
        $html .= "<button type='submit' class='btn btn-success'>Save Vital Signs</button>";
        $html .= "<button type='button' onclick='toggleVitalsForm()' class='btn btn-outline'>Cancel</button>";
        $html .= "</div>";
        
        $html .= "</form>";
        $html .= "</div>";
        
        // Charts
        $html .= "<div class='chart-container'>";
        $html .= "<div class='chart-tabs'>";
        $html .= "<button class='chart-tab active' data-chart='heart_rate'>Heart Rate</button>";
        $html .= "<button class='chart-tab' data-chart='blood_pressure'>Blood Pressure</button>";
        $html .= "<button class='chart-tab' data-chart='weight'>Weight</button>";
        $html .= "<button class='chart-tab' data-chart='temperature'>Temperature</button>";
        $html .= "</div>";
        $html .= "<canvas id='vitals-chart'></canvas>";
        $html .= "</div>";
        
        $html .= "</div>"; // vitals-container
        $html .= "</div>"; // main container
        
        $html .= $this->get_case_scripts();
        $html .= "
        <script>
        function toggleVitalsForm() {
            const form = document.getElementById('vitals-form-container');
            form.style.display = form.style.display === 'none' ? 'block' : 'none';
        }
        </script>";
        $html .= "</body></html>";
        
        echo $html;
        exit;
    }
    
    /**
     * Render case interventions page
     */
    private function render_case_interventions($case_id) {
        $case = get_post($case_id);
        if (!$case || $case->post_type !== 'terpedia_case') {
            wp_die('Case not found');
        }
        
        $patient_name = get_post_meta($case_id, 'patient_name', true) ?: 'Unknown Patient';
        
        $html = "<!DOCTYPE html><html><head>";
        $html .= "<title>Interventions: {$patient_name} - Case Management</title>";
        $html .= "<meta name='viewport' content='width=device-width, initial-scale=1'>";
        $html .= $this->get_case_styles();
        $html .= "</head><body>";
        
        $html .= "<div class='terpedia-case-container'>";
        
        // Navigation
        $html .= "<nav class='case-nav'>";
        $html .= "<a href='/cases' class='nav-back'>← All Cases</a>";
        $html .= "<div class='case-nav-tabs'>";
        $html .= "<a href='/case/{$case_id}' class='nav-tab'>Overview</a>";
        $html .= "<a href='/case/{$case_id}/chat' class='nav-tab'>Chat</a>";
        $html .= "<a href='/case/{$case_id}/vitals' class='nav-tab'>Vital Signs</a>";
        $html .= "<a href='/case/{$case_id}/interventions' class='nav-tab active'>Interventions</a>";
        $html .= "</div>";
        $html .= "</nav>";
        
        // Interventions interface
        $html .= "<div class='interventions-container'>";
        $html .= "<div class='vitals-header'>";
        $html .= "<h2>🔬 Interventions: {$patient_name}</h2>";
        $html .= "<div class='vitals-actions'>";
        $html .= "<button onclick='toggleInterventionForm()' class='btn btn-primary'>+ Record Intervention</button>";
        $html .= "</div>";
        $html .= "</div>";
        
        // Intervention entry form
        $html .= "<div id='intervention-form-container' class='vitals-form' style='display: none;'>";
        $html .= "<h3>Record New Intervention</h3>";
        $html .= "<form id='intervention-form' data-case-id='{$case_id}'>";
        
        $html .= "<div class='form-row'>";
        $html .= "<div class='form-group'>";
        $html .= "<label>Intervention Type</label>";
        $html .= "<input type='text' name='intervention_type' required placeholder='e.g., Medication Administration'>";
        $html .= "</div>";
        $html .= "<div class='form-group'>";
        $html .= "<label>Category</label>";
        $html .= "<select name='intervention_category' required>";
        $html .= "<option value=''>Select Category</option>";
        $html .= "<option value='treatment'>Treatment</option>";
        $html .= "<option value='diagnosis'>Diagnosis</option>";
        $html .= "<option value='medication'>Medication</option>";
        $html .= "<option value='procedure'>Procedure</option>";
        $html .= "<option value='monitoring'>Monitoring</option>";
        $html .= "<option value='surgery'>Surgery</option>";
        $html .= "</select>";
        $html .= "</div>";
        $html .= "</div>";
        
        $html .= "<div class='form-group'>";
        $html .= "<label>Description</label>";
        $html .= "<textarea name='description' rows='4' required placeholder='Detailed description of the intervention...'></textarea>";
        $html .= "</div>";
        
        $html .= "<div class='form-group'>";
        $html .= "<label>Outcome (Optional)</label>";
        $html .= "<textarea name='outcome' rows='3' placeholder='Results or outcomes observed...'></textarea>";
        $html .= "</div>";
        
        $html .= "<div class='form-row'>";
        $html .= "<div class='form-group'>";
        $html .= "<label>";
        $html .= "<input type='checkbox' name='follow_up_required' value='1'>";
        $html .= " Follow-up Required";
        $html .= "</label>";
        $html .= "</div>";
        $html .= "<div class='form-group'>";
        $html .= "<label>Follow-up Date (Optional)</label>";
        $html .= "<input type='date' name='follow_up_date'>";
        $html .= "</div>";
        $html .= "</div>";
        
        $html .= "<div class='form-actions'>";
        $html .= "<button type='submit' class='btn btn-success'>Save Intervention</button>";
        $html .= "<button type='button' onclick='toggleInterventionForm()' class='btn btn-outline'>Cancel</button>";
        $html .= "</div>";
        
        $html .= "</form>";
        $html .= "</div>";
        
        // Interventions list
        $html .= "<div id='interventions-list'>";
        $html .= "<!-- Interventions will be loaded via AJAX -->";
        $html .= "</div>";
        
        $html .= "</div>"; // interventions-container
        $html .= "</div>"; // main container
        
        $html .= $this->get_case_scripts();
        $html .= "
        <script>
        function toggleInterventionForm() {
            const form = document.getElementById('intervention-form-container');
            form.style.display = form.style.display === 'none' ? 'block' : 'none';
        }
        </script>";
        $html .= "</body></html>";
        
        echo $html;
        exit;
    }
    
    /**
     * Get CSS styles for case management
     */
    private function get_case_styles() {
        return "
        <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            line-height: 1.6;
            color: #333;
            background: #f5f7fa;
        }
        
        .terpedia-case-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .case-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px;
            border-radius: 12px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .case-header h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }
        
        .case-header p {
            opacity: 0.9;
            font-size: 1.1rem;
        }
        
        .case-actions {
            display: flex;
            gap: 15px;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .btn-primary {
            background: #007cba;
            color: white;
        }
        
        .btn-primary:hover {
            background: #005a87;
        }
        
        .btn-outline {
            background: transparent;
            color: #007cba;
            border: 2px solid #007cba;
        }
        
        .btn-outline:hover {
            background: #007cba;
            color: white;
        }
        
        .btn-ai {
            background: #8b5cf6;
            color: white;
        }
        
        .btn-ai:hover {
            background: #7c3aed;
        }
        
        .cases-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .case-card {
            background: white;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }
        
        .case-card:hover {
            transform: translateY(-2px);
        }
        
        .case-card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }
        
        .case-card h3 {
            margin: 0;
            font-size: 1.3rem;
        }
        
        .case-card h3 a {
            text-decoration: none;
            color: #333;
        }
        
        .case-status {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-active {
            background: #d1fae5;
            color: #065f46;
        }
        
        .status-completed {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .status-critical {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .case-card-body p {
            margin: 8px 0;
            color: #666;
        }
        
        .case-card-actions {
            margin-top: 20px;
            display: flex;
            gap: 10px;
        }
        
        .case-nav {
            background: white;
            border-radius: 8px;
            padding: 0;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
        }
        
        .nav-back {
            padding: 15px 20px;
            text-decoration: none;
            color: #666;
            border-right: 1px solid #eee;
        }
        
        .case-nav-tabs {
            display: flex;
            flex: 1;
        }
        
        .nav-tab {
            padding: 15px 20px;
            text-decoration: none;
            color: #666;
            border-right: 1px solid #eee;
            transition: background 0.2s;
        }
        
        .nav-tab:hover,
        .nav-tab.active {
            background: #f8f9fa;
            color: #007cba;
        }
        
        .case-detail-header {
            background: white;
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .case-info h1 {
            margin: 0 0 10px 0;
            font-size: 2rem;
        }
        
        .case-content {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }
        
        .form-section h3 {
            margin-bottom: 20px;
            color: #333;
            border-bottom: 2px solid #007cba;
            padding-bottom: 10px;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #555;
        }
        
        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 2px solid #e1e5e9;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.2s;
        }
        
        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #007cba;
        }
        
        .form-actions {
            text-align: right;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        
        .chat-container {
            background: white;
            border-radius: 12px;
            height: 70vh;
            display: flex;
            flex-direction: column;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .chat-header {
            padding: 20px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .chat-header h2 {
            margin: 0;
            color: #333;
        }
        
        .ai-status {
            color: #8b5cf6;
            font-weight: 600;
        }
        
        .chat-messages {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
            background: #f8f9fa;
        }
        
        .message {
            margin-bottom: 15px;
            padding: 12px 16px;
            border-radius: 18px;
            max-width: 70%;
            word-wrap: break-word;
        }
        
        .message.user {
            background: #007cba;
            color: white;
            margin-left: auto;
        }
        
        .message.ai {
            background: #8b5cf6;
            color: white;
        }
        
        .message.system {
            background: #e5e7eb;
            color: #374151;
        }
        
        .message-time {
            font-size: 11px;
            opacity: 0.7;
            margin-top: 5px;
        }
        
        .chat-input-container {
            padding: 20px;
            border-top: 1px solid #eee;
        }
        
        .chat-input-form {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        #chat-input {
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            padding: 12px;
            resize: none;
            font-family: inherit;
        }
        
        .chat-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }
        
        .no-cases {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }
        
        @media (max-width: 768px) {
            .case-header {
                flex-direction: column;
                text-align: center;
                gap: 20px;
            }
            
            .case-detail-header {
                flex-direction: column;
                gap: 20px;
                text-align: center;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .case-nav {
                flex-direction: column;
            }
            
            .nav-back {
                border-right: none;
                border-bottom: 1px solid #eee;
            }
            
            .case-nav-tabs {
                flex-direction: column;
            }
            
            .nav-tab {
                border-right: none;
                border-bottom: 1px solid #eee;
            }
        }
        </style>";
    }
    
    /**
     * Get JavaScript for case management
     */
    private function get_case_scripts() {
        return "
        <script>
        // Case management JavaScript
        function createNewCase() {
            const name = prompt('Enter patient name:');
            if (name) {
                window.location.href = '/wp-admin/post-new.php?post_type=terpedia_case&patient_name=' + encodeURIComponent(name);
            }
        }
        
        function editCase(caseId) {
            document.querySelector('#case-form').classList.add('editing');
            document.querySelector('#case-form input[name=\"patient_name\"]').focus();
        }
        
        function exportCase(caseId) {
            window.open('/wp-admin/admin-ajax.php?action=export_case&case_id=' + caseId, '_blank');
        }
        
        function resetForm() {
            document.getElementById('case-form').reset();
        }
        
        // Chat functionality
        function sendMessage() {
            const input = document.getElementById('chat-input');
            const message = input.value.trim();
            if (!message) return;
            
            const caseId = document.querySelector('#chat-messages').dataset.caseId;
            
            // Add message to chat immediately
            addMessageToChat('user', message);
            input.value = '';
            
            // Send via AJAX
            fetch('/wp-admin/admin-ajax.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'terpedia_send_case_message',
                    case_id: caseId,
                    message: message,
                    user_type: 'human'
                })
            });
        }
        
        function askAI() {
            const input = document.getElementById('chat-input');
            const message = input.value.trim() || 'Please provide insights about this case.';
            const caseId = document.querySelector('#chat-messages').dataset.caseId;
            
            addMessageToChat('user', message);
            addMessageToChat('ai', 'Thinking...', true);
            input.value = '';
            
            fetch('/wp-admin/admin-ajax.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'terpedia_send_case_message',
                    case_id: caseId,
                    message: message,
                    user_type: 'ai_request'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data.ai_response) {
                    updateLastMessage(data.data.ai_response);
                }
            });
        }
        
        function addMessageToChat(type, message, isTemporary = false) {
            const messagesContainer = document.getElementById('chat-messages');
            const messageDiv = document.createElement('div');
            messageDiv.className = 'message ' + type + (isTemporary ? ' temporary' : '');
            
            const now = new Date();
            const timeStr = now.toLocaleTimeString();
            
            messageDiv.innerHTML = 
                '<div class=\"message-content\">' + message + '</div>' +
                '<div class=\"message-time\">' + timeStr + '</div>';
            
            messagesContainer.appendChild(messageDiv);
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }
        
        function updateLastMessage(newContent) {
            const messages = document.querySelectorAll('#chat-messages .message.temporary');
            if (messages.length > 0) {
                const lastMessage = messages[messages.length - 1];
                lastMessage.querySelector('.message-content').innerHTML = newContent;
                lastMessage.classList.remove('temporary');
            }
        }
        
        // Load chat messages on page load
        document.addEventListener('DOMContentLoaded', function() {
            const chatMessages = document.getElementById('chat-messages');
            if (chatMessages) {
                loadChatMessages();
            }
            
            // Set up form submission
            const caseForm = document.getElementById('case-form');
            if (caseForm) {
                caseForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    saveCaseData();
                });
            }
            
            // Enter key in chat
            const chatInput = document.getElementById('chat-input');
            if (chatInput) {
                chatInput.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter' && !e.shiftKey) {
                        e.preventDefault();
                        sendMessage();
                    }
                });
            }
        });
        
        function loadChatMessages() {
            const caseId = document.querySelector('#chat-messages').dataset.caseId;
            
            fetch('/wp-admin/admin-ajax.php?action=terpedia_get_case_messages&case_id=' + caseId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const messagesContainer = document.getElementById('chat-messages');
                        messagesContainer.innerHTML = '';
                        
                        data.data.forEach(message => {
                            addMessageToChat(message.user_type, message.message);
                        });
                    }
                });
        }
        
        function saveCaseData() {
            const form = document.getElementById('case-form');
            const formData = new FormData(form);
            const caseId = form.dataset.caseId;
            
            formData.append('action', 'terpedia_save_case_data');
            formData.append('case_id', caseId);
            
            fetch('/wp-admin/admin-ajax.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Case saved successfully!', 'success');
                } else {
                    showNotification('Error saving case: ' + data.data, 'error');
                }
            });
        }
        
        function showNotification(message, type) {
            const notification = document.createElement('div');
            notification.className = 'notification ' + type;
            notification.textContent = message;
            notification.style.cssText = 
                'position: fixed;' +
                'top: 20px;' +
                'right: 20px;' +
                'padding: 15px 20px;' +
                'border-radius: 6px;' +
                'color: white;' +
                'font-weight: 600;' +
                'z-index: 1000;' +
                'background: ' + (type === 'success' ? '#10b981' : '#ef4444') + ';';
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.remove();
            }, 3000);
        }
        </script>";
    }
    
    // AJAX Handlers
    
    /**
     * Save case data via AJAX
     */
    public function ajax_save_case_data() {
        check_ajax_referer('terpedia_case_nonce', 'nonce');
        
        $case_id = intval($_POST['case_id']);
        $patient_name = sanitize_text_field($_POST['patient_name']);
        $species = sanitize_text_field($_POST['species']);
        $breed = sanitize_text_field($_POST['breed']);
        $age = sanitize_text_field($_POST['age']);
        $weight = sanitize_text_field($_POST['weight']);
        $owner_name = sanitize_text_field($_POST['owner_name']);
        $owner_contact = sanitize_textarea_field($_POST['owner_contact']);
        $case_notes = sanitize_textarea_field($_POST['case_notes']);
        
        // Update post content
        wp_update_post(array(
            'ID' => $case_id,
            'post_content' => $case_notes
        ));
        
        // Update meta fields
        update_post_meta($case_id, 'patient_name', $patient_name);
        update_post_meta($case_id, 'species', $species);
        update_post_meta($case_id, 'breed', $breed);
        update_post_meta($case_id, 'age', $age);
        update_post_meta($case_id, 'weight', $weight);
        update_post_meta($case_id, 'owner_name', $owner_name);
        update_post_meta($case_id, 'owner_contact', $owner_contact);
        
        wp_send_json_success('Case saved successfully');
    }
    
    /**
     * Send chat message via AJAX
     */
    public function ajax_send_case_message() {
        $case_id = intval($_POST['case_id']);
        $message = sanitize_textarea_field($_POST['message']);
        $user_type = sanitize_text_field($_POST['user_type']);
        $user_id = get_current_user_id();
        
        global $wpdb;
        $table = $wpdb->prefix . 'terpedia_case_messages';
        
        // Insert human message
        $wpdb->insert(
            $table,
            array(
                'case_id' => $case_id,
                'user_id' => $user_id,
                'user_type' => 'human',
                'message' => $message,
                'timestamp' => current_time('mysql')
            )
        );
        
        $response_data = array('message_saved' => true);
        
        // If AI request, generate AI response
        if ($user_type === 'ai_request' && $this->openrouter_api) {
            $ai_response = $this->generate_ai_case_response($case_id, $message);
            
            if (!is_wp_error($ai_response)) {
                // Save AI response
                $wpdb->insert(
                    $table,
                    array(
                        'case_id' => $case_id,
                        'user_id' => 0,
                        'user_type' => 'ai',
                        'message' => $ai_response,
                        'timestamp' => current_time('mysql')
                    )
                );
                
                $response_data['ai_response'] = $ai_response;
            }
        }
        
        wp_send_json_success($response_data);
    }
    
    /**
     * Get chat messages via AJAX
     */
    public function ajax_get_case_messages() {
        $case_id = intval($_GET['case_id']);
        
        global $wpdb;
        $table = $wpdb->prefix . 'terpedia_case_messages';
        
        $messages = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE case_id = %d ORDER BY timestamp ASC",
            $case_id
        ));
        
        wp_send_json_success($messages);
    }
    
    /**
     * Save vital signs via AJAX
     */
    public function ajax_save_vital_signs() {
        $case_id = intval($_POST['case_id']);
        $heart_rate = !empty($_POST['heart_rate']) ? intval($_POST['heart_rate']) : null;
        $bp_systolic = !empty($_POST['blood_pressure_systolic']) ? intval($_POST['blood_pressure_systolic']) : null;
        $bp_diastolic = !empty($_POST['blood_pressure_diastolic']) ? intval($_POST['blood_pressure_diastolic']) : null;
        $weight = !empty($_POST['weight']) ? floatval($_POST['weight']) : null;
        $temperature = !empty($_POST['temperature']) ? floatval($_POST['temperature']) : null;
        $respiratory_rate = !empty($_POST['respiratory_rate']) ? intval($_POST['respiratory_rate']) : null;
        $notes = sanitize_textarea_field($_POST['notes']);
        $user_id = get_current_user_id();
        
        global $wpdb;
        $table = $wpdb->prefix . 'terpedia_case_vitals';
        
        $result = $wpdb->insert(
            $table,
            array(
                'case_id' => $case_id,
                'recorded_by' => $user_id,
                'recorded_date' => current_time('mysql'),
                'heart_rate' => $heart_rate,
                'blood_pressure_systolic' => $bp_systolic,
                'blood_pressure_diastolic' => $bp_diastolic,
                'weight' => $weight,
                'temperature' => $temperature,
                'respiratory_rate' => $respiratory_rate,
                'notes' => $notes
            )
        );
        
        if ($result === false) {
            wp_send_json_error('Failed to save vital signs');
        }
        
        wp_send_json_success('Vital signs saved successfully');
    }
    
    /**
     * Get vital signs data via AJAX
     */
    public function ajax_get_vital_signs() {
        $case_id = intval($_GET['case_id']);
        $chart_type = sanitize_text_field($_GET['chart_type']) ?: 'heart_rate';
        
        global $wpdb;
        $table = $wpdb->prefix . 'terpedia_case_vitals';
        
        $field_map = array(
            'heart_rate' => 'heart_rate',
            'blood_pressure' => 'blood_pressure_systolic',
            'weight' => 'weight',
            'temperature' => 'temperature'
        );
        
        $field = $field_map[$chart_type] ?? 'heart_rate';
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT recorded_date, {$field} as value FROM $table 
             WHERE case_id = %d AND {$field} IS NOT NULL 
             ORDER BY recorded_date ASC",
            $case_id
        ));
        
        $labels = array();
        $values = array();
        
        foreach ($results as $row) {
            $labels[] = date('Y-m-d', strtotime($row->recorded_date));
            $values[] = floatval($row->value);
        }
        
        wp_send_json_success(array(
            'labels' => $labels,
            'values' => $values
        ));
    }
    
    /**
     * Save intervention via AJAX
     */
    public function ajax_save_intervention() {
        $case_id = intval($_POST['case_id']);
        $intervention_type = sanitize_text_field($_POST['intervention_type']);
        $intervention_category = sanitize_text_field($_POST['intervention_category']);
        $description = sanitize_textarea_field($_POST['description']);
        $outcome = sanitize_textarea_field($_POST['outcome']);
        $follow_up_required = !empty($_POST['follow_up_required']) ? 1 : 0;
        $follow_up_date = !empty($_POST['follow_up_date']) ? sanitize_text_field($_POST['follow_up_date']) : null;
        $user_id = get_current_user_id();
        
        global $wpdb;
        $table = $wpdb->prefix . 'terpedia_case_interventions';
        
        $result = $wpdb->insert(
            $table,
            array(
                'case_id' => $case_id,
                'recorded_by' => $user_id,
                'intervention_date' => current_time('mysql'),
                'intervention_type' => $intervention_type,
                'intervention_category' => $intervention_category,
                'description' => $description,
                'outcome' => $outcome,
                'follow_up_required' => $follow_up_required,
                'follow_up_date' => $follow_up_date,
                'status' => 'active'
            )
        );
        
        if ($result === false) {
            wp_send_json_error('Failed to save intervention');
        }
        
        wp_send_json_success('Intervention saved successfully');
    }
    
    /**
     * Get interventions via AJAX
     */
    public function ajax_get_interventions() {
        $case_id = intval($_GET['case_id']);
        
        global $wpdb;
        $table = $wpdb->prefix . 'terpedia_case_interventions';
        
        $interventions = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE case_id = %d ORDER BY intervention_date DESC",
            $case_id
        ));
        
        wp_send_json_success($interventions);
    }
    
    /**
     * Generate AI response for case discussion
     */
    private function generate_ai_case_response($case_id, $user_message) {
        $case = get_post($case_id);
        $patient_name = get_post_meta($case_id, 'patient_name', true);
        $species = get_post_meta($case_id, 'species', true);
        $case_notes = $case->post_content;
        
        $system_prompt = "You are an expert veterinary AI assistant helping with case management. You have access to comprehensive veterinary knowledge and can provide insights on diagnosis, treatment options, and case management.";
        
        $context_prompt = "Patient: {$patient_name}\nSpecies: {$species}\nCase Notes: {$case_notes}\n\nUser Question: {$user_message}";
        
        $messages = array(
            array('role' => 'system', 'content' => $system_prompt),
            array('role' => 'user', 'content' => $context_prompt)
        );
        
        $response = $this->openrouter_api->chat_completion($messages, array(
            'max_tokens' => 500,
            'temperature' => 0.7
        ));
        
        if (is_wp_error($response)) {
            return "I'm sorry, I'm having trouble connecting to the AI service right now. Please try again later.";
        }
        
        return isset($response['choices'][0]['message']['content']) ? 
            $response['choices'][0]['message']['content'] : 
            "I couldn't generate a response. Please try rephrasing your question.";
    }
}

// Initialize the case management system
new Terpedia_Case_Management_System();