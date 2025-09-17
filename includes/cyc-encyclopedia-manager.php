<?php
/**
 * Cyc Encyclopedia Manager
 * Backend management system for the Terpedia Encyclopedia with dynamic content generation
 * Integrates LLM + TerpKB (SPARQL) + RAG over uploaded articles
 * Enhanced with kb.terpedia.com federated database querying
 */

if (!defined('ABSPATH')) {
    exit;
}

// Include required classes for SPARQL integration
require_once dirname(__FILE__) . '/terport-sparql-integration.php';
require_once dirname(__FILE__) . '/openrouter-api-handler.php';

class TerpediaCycEncyclopediaManager {
    
    private $sparql_endpoint;
    private $openrouter_api_key;
    private $vector_db_endpoint;
    private $sparql_integration;
    private $openrouter_api;
    
    public function __construct() {
        $this->sparql_endpoint = get_option('terpedia_sparql_endpoint', 'https://kb.terpedia.com:3030/biodb/sparql');
        $this->openrouter_api_key = get_option('terpedia_openrouter_api_key');
        $this->vector_db_endpoint = get_option('terpedia_vector_db_endpoint', 'http://localhost:8000');
        
        // Initialize SPARQL integration and OpenRouter API
        if (class_exists('Terpedia_Terport_SPARQL_Integration')) {
            $this->sparql_integration = new Terpedia_Terport_SPARQL_Integration();
        }
        if (class_exists('TerpediaOpenRouterHandler')) {
            $this->openrouter_api = new TerpediaOpenRouterHandler();
        }
        
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
        add_action('wp_ajax_cyc_generate_content', array($this, 'ajax_generate_content'));
        add_action('wp_ajax_cyc_search_sparql', array($this, 'ajax_search_sparql'));
        add_action('wp_ajax_cyc_search_rag', array($this, 'ajax_search_rag'));
        add_action('wp_ajax_cyc_add_term', array($this, 'ajax_add_term'));
        add_action('wp_ajax_cyc_update_term', array($this, 'ajax_update_term'));
        add_action('wp_ajax_cyc_delete_term', array($this, 'ajax_delete_term'));
        add_action('wp_ajax_cyc_upload_article', array($this, 'ajax_upload_article'));
        add_action('wp_ajax_cyc_test_connections', array($this, 'ajax_test_connections'));
        add_action('wp_ajax_cyc_regenerate_all_content', array($this, 'ajax_regenerate_all_content'));
        add_action('wp_ajax_cyc_query_kb_terpedia', array($this, 'ajax_query_kb_terpedia'));
        add_action('wp_ajax_cyc_generate_federated_content', array($this, 'ajax_generate_federated_content'));
        
        // Register encyclopedia entry post type
        add_action('init', array($this, 'register_encyclopedia_post_type'));
    }
    
    /**
     * Register encyclopedia entry post type
     */
    public function register_encyclopedia_post_type() {
        register_post_type('encyclopedia_entry', array(
            'labels' => array(
                'name' => 'Encyclopedia Entries',
                'singular_name' => 'Encyclopedia Entry',
                'add_new' => 'Add New Entry',
                'add_new_item' => 'Add New Encyclopedia Entry',
                'edit_item' => 'Edit Encyclopedia Entry',
                'new_item' => 'New Encyclopedia Entry',
                'view_item' => 'View Encyclopedia Entry',
                'search_items' => 'Search Encyclopedia Entries',
                'not_found' => 'No encyclopedia entries found',
                'not_found_in_trash' => 'No encyclopedia entries found in trash'
            ),
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => 'terpedia-main', // Show under Terpedia menu
            'supports' => array('title', 'editor', 'custom-fields', 'thumbnail'),
            'capability_type' => 'post',
            'has_archive' => false,
            'rewrite' => false,
            'menu_icon' => 'dashicons-book-alt'
        ));
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_submenu_page(
            'terpedia-settings',
            'Cyc Encyclopedia Manager',
            'Cyc Encyclopedia',
            'manage_options',
            'terpedia-cyc-manager',
            array($this, 'admin_page')
        );
        
        // Encyclopedia entries now appear automatically under Terpedia menu
    }
    
    /**
     * Initialize admin settings
     */
    public function admin_init() {
        register_setting('terpedia_cyc_settings', 'terpedia_sparql_endpoint');
        register_setting('terpedia_cyc_settings', 'terpedia_vector_db_endpoint');
        register_setting('terpedia_cyc_settings', 'terpedia_cyc_auto_generate');
        register_setting('terpedia_cyc_settings', 'terpedia_cyc_llm_model');
        
        add_settings_section(
            'terpedia_cyc_general',
            'General Settings',
            array($this, 'settings_section_callback'),
            'terpedia_cyc_settings'
        );
        
        add_settings_field(
            'terpedia_sparql_endpoint',
            'SPARQL Endpoint URL',
            array($this, 'sparql_endpoint_callback'),
            'terpedia_cyc_settings',
            'terpedia_cyc_general'
        );
        
        add_settings_field(
            'terpedia_vector_db_endpoint',
            'Vector Database Endpoint',
            array($this, 'vector_db_endpoint_callback'),
            'terpedia_cyc_settings',
            'terpedia_cyc_general'
        );
        
        add_settings_field(
            'terpedia_cyc_auto_generate',
            'Auto-generate Content',
            array($this, 'auto_generate_callback'),
            'terpedia_cyc_settings',
            'terpedia_cyc_general'
        );
        
        add_settings_field(
            'terpedia_cyc_llm_model',
            'LLM Model',
            array($this, 'llm_model_callback'),
            'terpedia_cyc_settings',
            'terpedia_cyc_general'
        );
    }
    
    /**
     * Admin page callback
     */
    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>Cyc Encyclopedia Manager</h1>
            
            <div class="terpedia-cyc-dashboard">
                <!-- Statistics Cards -->
                <div class="terpedia-stats-grid">
                    <div class="terpedia-stat-card">
                        <h3>Total Entries</h3>
                        <div class="stat-number"><?php echo $this->get_total_entries(); ?></div>
                    </div>
                    <div class="terpedia-stat-card">
                        <h3>Auto-generated</h3>
                        <div class="stat-number"><?php echo $this->get_auto_generated_count(); ?></div>
                    </div>
                    <div class="terpedia-stat-card">
                        <h3>Uploaded Articles</h3>
                        <div class="stat-number"><?php echo $this->get_uploaded_articles_count(); ?></div>
                    </div>
                    <div class="terpedia-stat-card">
                        <h3>SPARQL Queries</h3>
                        <div class="stat-number"><?php echo $this->get_sparql_queries_count(); ?></div>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div class="terpedia-quick-actions">
                    <h2>Quick Actions</h2>
                    <div class="action-buttons">
                        <button type="button" class="button button-primary" onclick="openAddTermModal()">
                            <span class="dashicons dashicons-plus-alt"></span> Add New Term
                        </button>
                        <button type="button" class="button button-secondary" onclick="openUploadArticleModal()">
                            <span class="dashicons dashicons-upload"></span> Upload Article
                        </button>
                        <button type="button" class="button button-secondary" onclick="testConnections()">
                            <span class="dashicons dashicons-admin-tools"></span> Test Connections
                        </button>
                        <button type="button" class="button button-secondary" onclick="regenerateAllContent()">
                            <span class="dashicons dashicons-update"></span> Regenerate All Content
                        </button>
                    </div>
                </div>
                
                <!-- Recent Entries -->
                <div class="terpedia-recent-entries">
                    <h2>Recent Entries</h2>
                    <div class="entries-table">
                        <?php $this->display_recent_entries(); ?>
                    </div>
                </div>
                
                <!-- Settings -->
                <div class="terpedia-settings-section">
                    <h2>Settings</h2>
                    <form method="post" action="options.php">
                        <?php
                        settings_fields('terpedia_cyc_settings');
                        do_settings_sections('terpedia_cyc_settings');
                        submit_button();
                        ?>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Add Term Modal -->
        <div id="addTermModal" class="terpedia-modal" style="display: none;">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Add New Encyclopedia Term</h2>
                    <span class="close" onclick="closeModal('addTermModal')">&times;</span>
                </div>
                <div class="modal-body">
                    <form id="addTermForm">
                        <table class="form-table">
                            <tr>
                                <th scope="row">Term Name</th>
                                <td><input type="text" name="term_name" id="term_name" class="regular-text" required /></td>
                            </tr>
                            <tr>
                                <th scope="row">Category</th>
                                <td>
                                    <select name="term_category" id="term_category" class="regular-text">
                                        <option value="Monoterpene">Monoterpene</option>
                                        <option value="Sesquiterpene">Sesquiterpene</option>
                                        <option value="Diterpene">Diterpene</option>
                                        <option value="Triterpene">Triterpene</option>
                                        <option value="Plant Source">Plant Source</option>
                                        <option value="Medical Condition">Medical Condition</option>
                                        <option value="Biochemical System">Biochemical System</option>
                                        <option value="Traditional Medicine">Traditional Medicine</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">Molecular Formula</th>
                                <td><input type="text" name="molecular_formula" id="molecular_formula" class="regular-text" /></td>
                            </tr>
                            <tr>
                                <th scope="row">SMILES</th>
                                <td><input type="text" name="smiles" id="smiles" class="regular-text" /></td>
                            </tr>
                            <tr>
                                <th scope="row">Description</th>
                                <td><textarea name="description" id="description" rows="3" class="large-text"></textarea></td>
                            </tr>
                            <tr>
                                <th scope="row">Auto-generate Content</th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="auto_generate" id="auto_generate" checked />
                                        Generate comprehensive content using LLM + TerpKB + RAG
                                    </label>
                                </td>
                            </tr>
                        </table>
                        <div class="modal-footer">
                            <button type="button" class="button button-secondary" onclick="closeModal('addTermModal')">Cancel</button>
                            <button type="submit" class="button button-primary">Add Term</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Upload Article Modal -->
        <div id="uploadArticleModal" class="terpedia-modal" style="display: none;">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Upload Research Article</h2>
                    <span class="close" onclick="closeModal('uploadArticleModal')">&times;</span>
                </div>
                <div class="modal-body">
                    <form id="uploadArticleForm" enctype="multipart/form-data">
                        <table class="form-table">
                            <tr>
                                <th scope="row">Article Title</th>
                                <td><input type="text" name="article_title" id="article_title" class="regular-text" required /></td>
                            </tr>
                            <tr>
                                <th scope="row">Authors</th>
                                <td><input type="text" name="article_authors" id="article_authors" class="regular-text" /></td>
                            </tr>
                            <tr>
                                <th scope="row">Journal</th>
                                <td><input type="text" name="article_journal" id="article_journal" class="regular-text" /></td>
                            </tr>
                            <tr>
                                <th scope="row">Year</th>
                                <td><input type="number" name="article_year" id="article_year" class="small-text" min="1900" max="2030" /></td>
                            </tr>
                            <tr>
                                <th scope="row">File Upload</th>
                                <td>
                                    <input type="file" name="article_file" id="article_file" accept=".pdf,.txt,.doc,.docx" required />
                                    <p class="description">Upload PDF, TXT, DOC, or DOCX file</p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">Keywords</th>
                                <td>
                                    <input type="text" name="article_keywords" id="article_keywords" class="regular-text" />
                                    <p class="description">Comma-separated keywords for indexing</p>
                                </td>
                            </tr>
                        </table>
                        <div class="modal-footer">
                            <button type="button" class="button button-secondary" onclick="closeModal('uploadArticleModal')">Cancel</button>
                            <button type="submit" class="button button-primary">Upload Article</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <style>
        .terpedia-cyc-dashboard {
            max-width: 1200px;
        }
        
        .terpedia-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .terpedia-stat-card {
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            padding: 20px;
            text-align: center;
        }
        
        .terpedia-stat-card h3 {
            margin: 0 0 10px 0;
            color: #23282d;
            font-size: 14px;
            font-weight: 600;
        }
        
        .stat-number {
            font-size: 32px;
            font-weight: bold;
            color: #0073aa;
        }
        
        .terpedia-quick-actions {
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            padding: 20px;
            margin-bottom: 30px;
        }
        
        .action-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .action-buttons .button {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .terpedia-recent-entries {
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            padding: 20px;
            margin-bottom: 30px;
        }
        
        .entries-table {
            overflow-x: auto;
        }
        
        .entries-table table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .entries-table th,
        .entries-table td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        .entries-table th {
            background-color: #f1f1f1;
            font-weight: 600;
        }
        
        .terpedia-settings-section {
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            padding: 20px;
        }
        
        .terpedia-modal {
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        
        .modal-content {
            background-color: #fff;
            margin: 5% auto;
            padding: 0;
            border-radius: 4px;
            width: 80%;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .modal-header {
            padding: 20px;
            border-bottom: 1px solid #ddd;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-header h2 {
            margin: 0;
        }
        
        .close {
            color: #aaa;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .close:hover {
            color: #000;
        }
        
        .modal-body {
            padding: 20px;
        }
        
        .modal-footer {
            padding: 20px;
            border-top: 1px solid #ddd;
            text-align: right;
        }
        
        .modal-footer .button {
            margin-left: 10px;
        }
        </style>
        
        <script>
        function openAddTermModal() {
            document.getElementById('addTermModal').style.display = 'block';
        }
        
        function openUploadArticleModal() {
            document.getElementById('uploadArticleModal').style.display = 'block';
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
        
        function testConnections() {
            // Test SPARQL and Vector DB connections
            jQuery.post(ajaxurl, {
                action: 'cyc_test_connections',
                nonce: '<?php echo wp_create_nonce('cyc_nonce'); ?>'
            }, function(response) {
                alert('Connection test completed. Check console for details.');
                console.log(response);
            });
        }
        
        function regenerateAllContent() {
            if (confirm('This will regenerate content for all entries. This may take a while. Continue?')) {
                jQuery.post(ajaxurl, {
                    action: 'cyc_regenerate_all_content',
                    nonce: '<?php echo wp_create_nonce('cyc_nonce'); ?>'
                }, function(response) {
                    alert('Content regeneration started. Check console for progress.');
                    console.log(response);
                });
            }
        }
        
        // Handle add term form submission
        jQuery('#addTermForm').on('submit', function(e) {
            e.preventDefault();
            
            var formData = {
                action: 'cyc_add_term',
                nonce: '<?php echo wp_create_nonce('cyc_nonce'); ?>',
                term_name: jQuery('#term_name').val(),
                term_category: jQuery('#term_category').val(),
                molecular_formula: jQuery('#molecular_formula').val(),
                smiles: jQuery('#smiles').val(),
                description: jQuery('#description').val(),
                auto_generate: jQuery('#auto_generate').is(':checked')
            };
            
            jQuery.post(ajaxurl, formData, function(response) {
                if (response.success) {
                    alert('Term added successfully!');
                    closeModal('addTermModal');
                    location.reload();
                } else {
                    alert('Error: ' + response.data);
                }
            });
        });
        
        // Handle upload article form submission
        jQuery('#uploadArticleForm').on('submit', function(e) {
            e.preventDefault();
            
            var formData = new FormData(this);
            formData.append('action', 'cyc_upload_article');
            formData.append('nonce', '<?php echo wp_create_nonce('cyc_nonce'); ?>');
            
            jQuery.ajax({
                url: ajaxurl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        alert('Article uploaded successfully!');
                        closeModal('uploadArticleModal');
                        location.reload();
                    } else {
                        alert('Error: ' + response.data);
                    }
                }
            });
        });
        </script>
        <?php
    }
    
    /**
     * Display recent entries
     */
    private function display_recent_entries() {
        $entries = get_posts(array(
            'post_type' => 'encyclopedia_entry',
            'posts_per_page' => 10,
            'post_status' => 'publish'
        ));
        
        if (empty($entries)) {
            echo '<p>No encyclopedia entries found.</p>';
            return;
        }
        
        echo '<table>';
        echo '<thead><tr><th>Title</th><th>Category</th><th>Last Modified</th><th>Actions</th></tr></thead>';
        echo '<tbody>';
        
        foreach ($entries as $entry) {
            $category = get_post_meta($entry->ID, 'encyclopedia_category', true);
            $auto_generated = get_post_meta($entry->ID, 'auto_generated', true);
            
            echo '<tr>';
            echo '<td><strong>' . esc_html($entry->post_title) . '</strong>';
            if ($auto_generated) {
                echo ' <span class="dashicons dashicons-admin-tools" title="Auto-generated"></span>';
            }
            echo '</td>';
            echo '<td>' . esc_html($category) . '</td>';
            echo '<td>' . esc_html($entry->post_modified) . '</td>';
            echo '<td>';
            echo '<a href="' . get_edit_post_link($entry->ID) . '" class="button button-small">Edit</a> ';
            echo '<a href="/cyc/' . $entry->post_name . '" class="button button-small" target="_blank">View</a>';
            echo '</td>';
            echo '</tr>';
        }
        
        echo '</tbody>';
        echo '</table>';
    }
    
    /**
     * AJAX handler for adding new terms
     */
    public function ajax_add_term() {
        check_ajax_referer('cyc_nonce', 'nonce');
        
        $term_name = sanitize_text_field($_POST['term_name']);
        $category = sanitize_text_field($_POST['term_category']);
        $molecular_formula = sanitize_text_field($_POST['molecular_formula']);
        $smiles = sanitize_text_field($_POST['smiles']);
        $description = sanitize_textarea_field($_POST['description']);
        $auto_generate = isset($_POST['auto_generate']) && $_POST['auto_generate'] === 'true';
        
        // Create encyclopedia entry post
        $post_data = array(
            'post_title' => $term_name,
            'post_name' => sanitize_title($term_name),
            'post_content' => $description,
            'post_status' => 'publish',
            'post_type' => 'encyclopedia_entry'
        );
        
        $post_id = wp_insert_post($post_data);
        
        if (is_wp_error($post_id)) {
            wp_send_json_error('Failed to create encyclopedia entry');
            return;
        }
        
        // Add custom fields
        update_post_meta($post_id, 'encyclopedia_category', $category);
        update_post_meta($post_id, 'molecular_formula', $molecular_formula);
        update_post_meta($post_id, 'smiles', $smiles);
        update_post_meta($post_id, 'auto_generated', $auto_generate);
        update_post_meta($post_id, 'created_via_cyc_manager', true);
        
        // Auto-generate content if requested
        if ($auto_generate) {
            $this->generate_comprehensive_content($post_id, $term_name, $category);
        }
        
        wp_send_json_success('Term added successfully');
    }
    
    /**
     * Generate comprehensive content using LLM + TerpKB + RAG + Federated Databases
     */
    private function generate_comprehensive_content($post_id, $term_name, $category) {
        // Step 1: Query federated SPARQL endpoints via kb.terpedia.com
        $federated_data = array();
        if ($this->sparql_integration) {
            $federated_data = $this->sparql_integration->query_federated_terpene_research($term_name, $category);
        }
        
        // Step 2: Use natural language querying via kb.terpedia.com chat API
        $research_questions = $this->build_research_questions($term_name, $category);
        $kb_chat_data = array();
        if ($this->sparql_integration) {
            foreach ($research_questions as $question) {
                $kb_chat_data[] = $this->sparql_integration->query_natural_language($question);
            }
        }
        
        // Step 3: Search RAG database for relevant articles
        $rag_data = $this->search_rag_database($term_name, $category);
        
        // Step 4: Generate content using OpenRouter with fallback models
        $generated_content = $this->generate_federated_llm_content($term_name, $category, $federated_data, $kb_chat_data, $rag_data);
        
        // Step 5: Update post with generated content
        $this->update_post_with_generated_content($post_id, $generated_content, $federated_data, $kb_chat_data);
        
        return $generated_content;
    }
    
    /**
     * Build research questions for natural language querying
     */
    private function build_research_questions($term_name, $category) {
        $questions = array();
        
        switch (strtolower($category)) {
            case 'monoterpene':
            case 'sesquiterpene':
            case 'diterpene':
            case 'triterpene':
                $questions = array(
                    "What are the therapeutic effects and mechanisms of action of $term_name?",
                    "What are the natural sources and biosynthetic pathways of $term_name?",
                    "What are the pharmacokinetic properties and bioavailability of $term_name?",
                    "What are the drug interactions and safety considerations for $term_name?",
                    "What clinical studies have been conducted on $term_name?"
                );
                break;
                
            case 'plant source':
                $questions = array(
                    "What terpenes are found in $term_name and their concentrations?",
                    "What are the traditional medicinal uses of $term_name?",
                    "What are the extraction methods and processing techniques for $term_name?",
                    "What are the active compounds and their synergistic effects in $term_name?"
                );
                break;
                
            case 'medical condition':
                $questions = array(
                    "Which terpenes are effective for treating $term_name?",
                    "What are the mechanisms by which terpenes help with $term_name?",
                    "What are the dosing protocols for terpenes in $term_name treatment?",
                    "What clinical evidence exists for terpene therapy in $term_name?"
                );
                break;
                
            default:
                $questions = array(
                    "What is the scientific evidence for $term_name in terpene research?",
                    "What are the biological activities and therapeutic potential of $term_name?",
                    "What are the molecular mechanisms and pathways involved with $term_name?"
                );
        }
        
        return $questions;
    }
    
    /**
     * Generate comprehensive encyclopedia content using federated research data
     */
    private function generate_federated_llm_content($term_name, $category, $federated_data, $kb_chat_data, $rag_data) {
        if (!$this->openrouter_api) {
            return $this->generate_fallback_content($term_name, $category);
        }
        
        // Build comprehensive system prompt for encyclopedia entries
        $system_prompt = $this->build_encyclopedia_system_prompt($category);
        
        // Consolidate all research data
        $research_context = $this->consolidate_research_data($federated_data, $kb_chat_data, $rag_data);
        
        $user_prompt = "Generate a comprehensive encyclopedia entry for '$term_name' (Category: $category) using the following research data:\n\n";
        $user_prompt .= "Research Context:\n" . json_encode($research_context, JSON_PRETTY_PRINT) . "\n\n";
        $user_prompt .= "Please create a detailed, scientifically accurate entry that includes:\n";
        $user_prompt .= "1. Overview and classification\n";
        $user_prompt .= "2. Chemical structure and properties\n";
        $user_prompt .= "3. Natural sources and biosynthesis\n";
        $user_prompt .= "4. Biological activities and mechanisms\n";
        $user_prompt .= "5. Therapeutic applications\n";
        $user_prompt .= "6. Pharmacokinetics and safety\n";
        $user_prompt .= "7. Current research and clinical evidence\n";
        $user_prompt .= "8. References to key studies\n";
        
        $messages = array(
            array('role' => 'system', 'content' => $system_prompt),
            array('role' => 'user', 'content' => $user_prompt)
        );
        
        // Use OpenRouter with fallback models
        $response = $this->generate_with_fallback_models($messages, 'encyclopedia_entry');
        
        if (is_wp_error($response)) {
            error_log('Cyc Encyclopedia AI Error: ' . $response->get_error_message());
            return $this->generate_fallback_content($term_name, $category);
        }
        
        if (isset($response['choices'][0]['message']['content'])) {
            return $response['choices'][0]['message']['content'];
        }
        
        return $this->generate_fallback_content($term_name, $category);
    }
    
    /**
     * Build encyclopedia-specific system prompt
     */
    private function build_encyclopedia_system_prompt($category) {
        $base_prompt = "You are Dr. Cyrus, a leading expert in terpenes and natural products chemistry, creating comprehensive encyclopedia entries for Terpedia.com. ";
        
        switch (strtolower($category)) {
            case 'monoterpene':
            case 'sesquiterpene':
            case 'diterpene':
            case 'triterpene':
                return $base_prompt . "Focus on chemical structure, stereochemistry, biosynthetic pathways, biological activities, therapeutic mechanisms, pharmacokinetics, natural sources, and clinical evidence. Emphasize molecular mechanisms and structure-activity relationships.";
                
            case 'plant source':
                return $base_prompt . "Focus on botanical classification, terpene profiles, traditional uses, extraction methods, active compounds, synergistic effects, cultivation, and standardization. Include geographical distribution and seasonal variations.";
                
            case 'medical condition':
                return $base_prompt . "Focus on pathophysiology, terpene interventions, mechanisms of action, clinical evidence, dosing protocols, drug interactions, safety profiles, and therapeutic outcomes. Emphasize evidence-based applications.";
                
            case 'biochemical system':
                return $base_prompt . "Focus on molecular pathways, enzyme interactions, metabolic processes, regulatory mechanisms, and how terpenes modulate these systems. Include pathway diagrams and molecular interactions.";
                
            default:
                return $base_prompt . "Provide comprehensive, scientifically accurate information with emphasis on molecular mechanisms, biological activities, and practical applications. Use proper scientific terminology while maintaining accessibility.";
        }
    }
    
    /**
     * Search RAG database for relevant articles
     */
    private function search_rag_database($term_name, $category) {
        $search_payload = array(
            'query' => $term_name . ' ' . $category,
            'limit' => 10,
            'threshold' => 0.7
        );
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->vector_db_endpoint . '/search');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($search_payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Accept: application/json'
        ));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code === 200) {
            return json_decode($response, true);
        }
        
        return null;
    }
    
    /**
     * Generate content using OpenRouter with fallback models
     */
    private function generate_with_fallback_models($messages, $content_type) {
        if (!$this->openrouter_api) {
            return new WP_Error('no_openrouter', 'OpenRouter API not available');
        }
        
        $models = $this->get_model_fallback_hierarchy();
        $last_error = '';
        
        foreach ($models as $model_config) {
            $options = array(
                'model' => $model_config['model'],
                'max_tokens' => $model_config['max_tokens'],
                'temperature' => 0.3 // Lower temperature for more factual content
            );
            
            $response = $this->openrouter_api->chat_completion($messages, $options);
            
            if (!is_wp_error($response) && isset($response['choices'][0]['message']['content'])) {
                // Success! Add metadata about which model was used
                $response['model_used'] = $model_config['model'];
                $response['model_type'] = $model_config['type'];
                return $response;
            }
            
            // Log the error and try next model
            $error_msg = is_wp_error($response) ? $response->get_error_message() : 'Unknown response format';
            $last_error .= "Model {$model_config['model']} failed: {$error_msg}; ";
            
            // Add small delay between attempts
            if ($model_config['type'] === 'premium') {
                sleep(1);
            }
        }
        
        return new WP_Error('all_models_failed', 'All models failed: ' . $last_error);
    }
    
    /**
     * Get model fallback hierarchy for encyclopedia content
     */
    private function get_model_fallback_hierarchy() {
        return array(
            // Try premium models first for high-quality encyclopedia content
            array(
                'model' => 'anthropic/claude-3.5-sonnet',
                'max_tokens' => 6000,
                'type' => 'premium'
            ),
            array(
                'model' => 'openai/gpt-4o-mini',
                'max_tokens' => 5000,
                'type' => 'premium'
            ),
            array(
                'model' => 'anthropic/claude-3-haiku',
                'max_tokens' => 4000,
                'type' => 'premium'
            ),
            // Fall back to free models
            array(
                'model' => 'meta-llama/llama-3.1-8b-instruct:free',
                'max_tokens' => 3000,
                'type' => 'free'
            ),
            array(
                'model' => 'google/gemma-2-9b-it:free',
                'max_tokens' => 2000,
                'type' => 'free'
            )
        );
    }
    
    /**
     * Consolidate research data from multiple sources
     */
    private function consolidate_research_data($federated_data, $kb_chat_data, $rag_data) {
        $consolidated = array(
            'federated_databases' => array(),
            'kb_chat_responses' => array(),
            'rag_articles' => array(),
            'data_sources' => array(),
            'total_data_points' => 0,
            'consolidation_timestamp' => current_time('mysql')
        );
        
        // Process federated database results
        if (!empty($federated_data)) {
            foreach ($federated_data as $source => $data) {
                if (isset($data['results']) && is_array($data['results'])) {
                    $consolidated['federated_databases'][$source] = $data;
                    $consolidated['data_sources'][] = ucfirst(str_replace('_', ' ', $source));
                    $consolidated['total_data_points'] += $data['count'] ?? count($data['results']);
                }
            }
        }
        
        // Process kb.terpedia.com chat API responses
        if (!empty($kb_chat_data)) {
            foreach ($kb_chat_data as $response) {
                if (isset($response['response']) && !isset($response['error'])) {
                    $consolidated['kb_chat_responses'][] = $response;
                    $consolidated['total_data_points']++;
                }
            }
            $consolidated['data_sources'][] = 'kb.terpedia.com Natural Language Queries';
        }
        
        // Process RAG database results
        if (!empty($rag_data) && isset($rag_data['results'])) {
            $consolidated['rag_articles'] = $rag_data['results'];
            $consolidated['data_sources'][] = 'Uploaded Research Articles';
            $consolidated['total_data_points'] += count($rag_data['results']);
        }
        
        // Add standard federated sources
        $consolidated['data_sources'] = array_merge(
            $consolidated['data_sources'],
            array('UniProt Proteins', 'Gene Ontology', 'Disease Ontology', 'Wikidata Compounds', 'MeSH Terms')
        );
        $consolidated['data_sources'] = array_unique($consolidated['data_sources']);
        
        return $consolidated;
    }
    
    /**
     * Generate fallback content when LLM is unavailable
     */
    private function generate_fallback_content($term_name, $category) {
        return "
        <h2>Overview</h2>
        <p>$term_name is a $category with various biological activities and therapeutic potential.</p>
        
        <h2>Chemical Properties</h2>
        <p>This compound exhibits characteristic properties of $category compounds, including specific molecular structure and chemical behavior.</p>
        
        <h2>Natural Sources</h2>
        <p>$term_name is found in various natural sources, contributing to the aromatic and therapeutic properties of plants.</p>
        
        <h2>Therapeutic Effects</h2>
        <p>Research suggests that $term_name may have several therapeutic applications, though more studies are needed to fully understand its mechanisms of action.</p>
        
        <h2>Research Status</h2>
        <p>Current research is ongoing to better understand the full therapeutic potential and safety profile of $term_name.</p>
        
        <p><em>Note: This entry was auto-generated. Please consult current scientific literature for the most up-to-date information.</em></p>
        ";
    }
    
    /**
     * Update post with generated content and metadata
     */
    private function update_post_with_generated_content($post_id, $content, $federated_data = null, $kb_chat_data = null) {
        $post_data = array(
            'ID' => $post_id,
            'post_content' => $content
        );
        
        wp_update_post($post_data);
        
        // Mark as auto-generated with enhanced metadata
        update_post_meta($post_id, 'auto_generated', true);
        update_post_meta($post_id, 'generated_at', current_time('mysql'));
        update_post_meta($post_id, 'generated_via_federated_kb', true);
        
        // Store research metadata
        if ($federated_data) {
            update_post_meta($post_id, '_federated_data_sources', array_keys($federated_data));
            update_post_meta($post_id, '_sparql_queries_executed', count($federated_data));
        }
        
        if ($kb_chat_data) {
            update_post_meta($post_id, '_kb_chat_queries_executed', count($kb_chat_data));
        }
        
        update_post_meta($post_id, '_knowledge_base_version', $this->get_kb_version());
        update_post_meta($post_id, '_generation_method', 'federated_sparql_kb_rag');
    }
    
    /**
     * Get knowledge base version info
     */
    private function get_kb_version() {
        $response = wp_remote_get('https://kb.terpedia.com/api/health');
        if (!is_wp_error($response)) {
            $data = json_decode(wp_remote_retrieve_body($response), true);
            return $data['version'] ?? 'unknown';
        }
        return 'unknown';
    }
    
    /**
     * AJAX handler for uploading articles
     */
    public function ajax_upload_article() {
        check_ajax_referer('cyc_nonce', 'nonce');
        
        if (!isset($_FILES['article_file']) || $_FILES['article_file']['error'] !== UPLOAD_ERR_OK) {
            wp_send_json_error('File upload failed');
            return;
        }
        
        $file = $_FILES['article_file'];
        $title = sanitize_text_field($_POST['article_title']);
        $authors = sanitize_text_field($_POST['article_authors']);
        $journal = sanitize_text_field($_POST['article_journal']);
        $year = intval($_POST['article_year']);
        $keywords = sanitize_text_field($_POST['article_keywords']);
        
        // Process and store the article
        $article_id = $this->process_uploaded_article($file, $title, $authors, $journal, $year, $keywords);
        
        if ($article_id) {
            wp_send_json_success('Article uploaded and processed successfully');
        } else {
            wp_send_json_error('Failed to process article');
        }
    }
    
    /**
     * Process uploaded article
     */
    private function process_uploaded_article($file, $title, $authors, $journal, $year, $keywords) {
        // Create article post
        $post_data = array(
            'post_title' => $title,
            'post_content' => 'Article content will be extracted and processed.',
            'post_status' => 'publish',
            'post_type' => 'research_article'
        );
        
        $post_id = wp_insert_post($post_data);
        
        if (is_wp_error($post_id)) {
            return false;
        }
        
        // Add metadata
        update_post_meta($post_id, 'article_authors', $authors);
        update_post_meta($post_id, 'article_journal', $journal);
        update_post_meta($post_id, 'article_year', $year);
        update_post_meta($post_id, 'article_keywords', $keywords);
        update_post_meta($post_id, 'article_file_path', $file['tmp_name']);
        
        // Extract text content and add to vector database
        $this->extract_and_index_article($post_id, $file);
        
        return $post_id;
    }
    
    /**
     * Extract text content and add to vector database
     */
    private function extract_and_index_article($post_id, $file) {
        // Extract text based on file type
        $content = $this->extract_text_from_file($file);
        
        if (empty($content)) {
            return false;
        }
        
        // Add to vector database
        $payload = array(
            'title' => get_the_title($post_id),
            'content' => $content,
            'metadata' => array(
                'post_id' => $post_id,
                'authors' => get_post_meta($post_id, 'article_authors', true),
                'journal' => get_post_meta($post_id, 'article_journal', true),
                'year' => get_post_meta($post_id, 'article_year', true),
                'keywords' => get_post_meta($post_id, 'article_keywords', true)
            )
        );
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->vector_db_endpoint . '/add');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Accept: application/json'
        ));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return $http_code === 200;
    }
    
    /**
     * Extract text from uploaded file
     */
    private function extract_text_from_file($file) {
        $file_type = wp_check_filetype($file['name']);
        $content = '';
        
        switch ($file_type['ext']) {
            case 'txt':
                $content = file_get_contents($file['tmp_name']);
                break;
            case 'pdf':
                // Use a PDF extraction library or service
                $content = $this->extract_pdf_text($file['tmp_name']);
                break;
            case 'doc':
            case 'docx':
                // Use a DOC extraction library or service
                $content = $this->extract_doc_text($file['tmp_name']);
                break;
        }
        
        return $content;
    }
    
    /**
     * Extract text from PDF (placeholder - implement with actual PDF library)
     */
    private function extract_pdf_text($file_path) {
        // This would use a library like Smalot\PdfParser or similar
        // For now, return placeholder
        return "PDF content extraction not yet implemented. Please use TXT files for now.";
    }
    
    /**
     * Extract text from DOC/DOCX (placeholder - implement with actual library)
     */
    private function extract_doc_text($file_path) {
        // This would use a library like PhpOffice\PhpWord or similar
        // For now, return placeholder
        return "DOC content extraction not yet implemented. Please use TXT files for now.";
    }
    
    // Settings callbacks
    public function settings_section_callback() {
        echo '<p>Configure the Cyc Encyclopedia Manager settings below.</p>';
    }
    
    public function sparql_endpoint_callback() {
        $value = get_option('terpedia_sparql_endpoint', 'http://localhost:8890/sparql');
        echo '<input type="url" name="terpedia_sparql_endpoint" value="' . esc_attr($value) . '" class="regular-text" />';
        echo '<p class="description">URL of your SPARQL endpoint (e.g., Virtuoso, Apache Jena)</p>';
    }
    
    public function vector_db_endpoint_callback() {
        $value = get_option('terpedia_vector_db_endpoint', 'http://localhost:8000');
        echo '<input type="url" name="terpedia_vector_db_endpoint" value="' . esc_attr($value) . '" class="regular-text" />';
        echo '<p class="description">URL of your vector database endpoint for RAG functionality</p>';
    }
    
    public function auto_generate_callback() {
        $value = get_option('terpedia_cyc_auto_generate', true);
        echo '<input type="checkbox" name="terpedia_cyc_auto_generate" value="1" ' . checked(1, $value, false) . ' />';
        echo '<p class="description">Automatically generate comprehensive content for new terms</p>';
    }
    
    public function llm_model_callback() {
        $value = get_option('terpedia_cyc_llm_model', 'anthropic/claude-3.5-sonnet');
        $models = array(
            'anthropic/claude-3.5-sonnet' => 'Claude 3.5 Sonnet',
            'anthropic/claude-3-haiku' => 'Claude 3 Haiku',
            'openai/gpt-4o' => 'GPT-4o',
            'openai/gpt-4o-mini' => 'GPT-4o Mini',
            'meta-llama/llama-3.1-70b-instruct' => 'Llama 3.1 70B'
        );
        
        echo '<select name="terpedia_cyc_llm_model" class="regular-text">';
        foreach ($models as $model_id => $model_name) {
            echo '<option value="' . esc_attr($model_id) . '" ' . selected($value, $model_id, false) . '>' . esc_html($model_name) . '</option>';
        }
        echo '</select>';
        echo '<p class="description">LLM model to use for content generation</p>';
    }
    
    // Statistics methods
    private function get_total_entries() {
        return wp_count_posts('encyclopedia_entry')->publish;
    }
    
    private function get_auto_generated_count() {
        global $wpdb;
        return $wpdb->get_var("
            SELECT COUNT(*) 
            FROM {$wpdb->postmeta} pm 
            JOIN {$wpdb->posts} p ON pm.post_id = p.ID 
            WHERE pm.meta_key = 'auto_generated' 
            AND pm.meta_value = '1' 
            AND p.post_type = 'encyclopedia_entry' 
            AND p.post_status = 'publish'
        ");
    }
    
    private function get_uploaded_articles_count() {
        return wp_count_posts('research_article')->publish;
    }
    
    private function get_sparql_queries_count() {
        // This would track SPARQL queries in a custom table
        return 0; // Placeholder
    }
    
    /**
     * AJAX handler for testing connections
     */
    public function ajax_test_connections() {
        check_ajax_referer('cyc_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $results = array(
            'sparql_endpoint' => $this->test_sparql_connection(),
            'vector_db' => $this->test_vector_db_connection(),
            'openrouter_api' => $this->test_openrouter_connection(),
            'kb_terpedia_chat' => $this->test_kb_terpedia_connection()
        );
        
        wp_send_json_success($results);
    }
    
    /**
     * AJAX handler for regenerating all content
     */
    public function ajax_regenerate_all_content() {
        check_ajax_referer('cyc_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $entries = get_posts(array(
            'post_type' => 'encyclopedia_entry',
            'posts_per_page' => -1,
            'post_status' => 'publish'
        ));
        
        $regenerated = 0;
        $errors = array();
        
        foreach ($entries as $entry) {
            $category = get_post_meta($entry->ID, 'encyclopedia_category', true);
            
            try {
                $this->generate_comprehensive_content($entry->ID, $entry->post_title, $category);
                $regenerated++;
            } catch (Exception $e) {
                $errors[] = "Failed to regenerate {$entry->post_title}: " . $e->getMessage();
            }
        }
        
        wp_send_json_success(array(
            'regenerated' => $regenerated,
            'total' => count($entries),
            'errors' => $errors
        ));
    }
    
    /**
     * AJAX handler for querying kb.terpedia.com
     */
    public function ajax_query_kb_terpedia() {
        check_ajax_referer('cyc_nonce', 'nonce');
        
        $query = sanitize_textarea_field($_POST['query']);
        
        if (empty($query)) {
            wp_send_json_error('Query is required');
        }
        
        if (!$this->sparql_integration) {
            wp_send_json_error('SPARQL integration not available');
        }
        
        $result = $this->sparql_integration->query_natural_language($query);
        
        if (isset($result['error'])) {
            wp_send_json_error($result['error']);
        }
        
        wp_send_json_success($result);
    }
    
    /**
     * AJAX handler for generating federated content
     */
    public function ajax_generate_federated_content() {
        check_ajax_referer('cyc_nonce', 'nonce');
        
        $term_name = sanitize_text_field($_POST['term_name']);
        $category = sanitize_text_field($_POST['category']);
        $post_id = intval($_POST['post_id']);
        
        if (empty($term_name) || empty($category)) {
            wp_send_json_error('Term name and category are required');
        }
        
        try {
            $content = $this->generate_comprehensive_content($post_id, $term_name, $category);
            wp_send_json_success(array(
                'content' => $content,
                'message' => 'Content generated successfully using federated knowledge base'
            ));
        } catch (Exception $e) {
            wp_send_json_error('Failed to generate content: ' . $e->getMessage());
        }
    }
    
    /**
     * Test SPARQL endpoint connection
     */
    private function test_sparql_connection() {
        $test_query = "SELECT * WHERE { ?s ?p ?o } LIMIT 1";
        
        $response = wp_remote_post($this->sparql_endpoint, array(
            'body' => array(
                'query' => $test_query,
                'format' => 'json'
            ),
            'headers' => array(
                'Accept' => 'application/sparql-results+json'
            ),
            'timeout' => 10
        ));
        
        if (is_wp_error($response)) {
            return array('status' => 'error', 'message' => $response->get_error_message());
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        return array(
            'status' => $status_code === 200 ? 'success' : 'error',
            'message' => $status_code === 200 ? 'SPARQL endpoint accessible' : 'SPARQL endpoint returned status ' . $status_code
        );
    }
    
    /**
     * Test vector database connection
     */
    private function test_vector_db_connection() {
        $response = wp_remote_get($this->vector_db_endpoint . '/health', array('timeout' => 10));
        
        if (is_wp_error($response)) {
            return array('status' => 'error', 'message' => $response->get_error_message());
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        return array(
            'status' => $status_code === 200 ? 'success' : 'error',
            'message' => $status_code === 200 ? 'Vector database accessible' : 'Vector database returned status ' . $status_code
        );
    }
    
    /**
     * Test OpenRouter API connection
     */
    private function test_openrouter_connection() {
        if (!$this->openrouter_api) {
            return array('status' => 'error', 'message' => 'OpenRouter API not initialized');
        }
        
        $result = $this->openrouter_api->test_connection();
        
        return array(
            'status' => $result['success'] ? 'success' : 'error',
            'message' => $result['success'] ? 'OpenRouter API accessible' : $result['error']
        );
    }
    
    /**
     * Test kb.terpedia.com connection
     */
    private function test_kb_terpedia_connection() {
        $response = wp_remote_get('https://kb.terpedia.com/api/health', array('timeout' => 10));
        
        if (is_wp_error($response)) {
            return array('status' => 'error', 'message' => $response->get_error_message());
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        return array(
            'status' => $status_code === 200 ? 'success' : 'error',
            'message' => $status_code === 200 ? 'kb.terpedia.com accessible' : 'kb.terpedia.com returned status ' . $status_code
        );
    }
}

// Initialize the Cyc Encyclopedia Manager
new TerpediaCycEncyclopediaManager();
