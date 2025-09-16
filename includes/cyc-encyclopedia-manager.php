<?php
/**
 * Cyc Encyclopedia Manager
 * Backend management system for the Terpedia Encyclopedia with dynamic content generation
 * Integrates LLM + TerpKB (SPARQL) + RAG over uploaded articles
 */

if (!defined('ABSPATH')) {
    exit;
}

class TerpediaCycEncyclopediaManager {
    
    private $sparql_endpoint;
    private $openrouter_api_key;
    private $vector_db_endpoint;
    
    public function __construct() {
        $this->sparql_endpoint = get_option('terpedia_sparql_endpoint', 'http://localhost:8890/sparql');
        $this->openrouter_api_key = get_option('terpedia_openrouter_api_key');
        $this->vector_db_endpoint = get_option('terpedia_vector_db_endpoint', 'http://localhost:8000');
        
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
        add_action('wp_ajax_cyc_generate_content', array($this, 'ajax_generate_content'));
        add_action('wp_ajax_cyc_search_sparql', array($this, 'ajax_search_sparql'));
        add_action('wp_ajax_cyc_search_rag', array($this, 'ajax_search_rag'));
        add_action('wp_ajax_cyc_add_term', array($this, 'ajax_add_term'));
        add_action('wp_ajax_cyc_update_term', array($this, 'ajax_update_term'));
        add_action('wp_ajax_cyc_delete_term', array($this, 'ajax_delete_term'));
        add_action('wp_ajax_cyc_upload_article', array($this, 'ajax_upload_article'));
        
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
     * Generate comprehensive content using LLM + TerpKB + RAG
     */
    private function generate_comprehensive_content($post_id, $term_name, $category) {
        // Step 1: Query SPARQL knowledge base
        $sparql_data = $this->query_sparql_knowledge_base($term_name, $category);
        
        // Step 2: Search RAG database for relevant articles
        $rag_data = $this->search_rag_database($term_name, $category);
        
        // Step 3: Generate content using LLM
        $generated_content = $this->generate_llm_content($term_name, $category, $sparql_data, $rag_data);
        
        // Step 4: Update post with generated content
        $this->update_post_with_generated_content($post_id, $generated_content);
        
        return $generated_content;
    }
    
    /**
     * Query SPARQL knowledge base
     */
    private function query_sparql_knowledge_base($term_name, $category) {
        $sparql_queries = array(
            'terpene_info' => "
                PREFIX terp: <http://terpedia.com/ontology#>
                PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
                
                SELECT ?property ?value WHERE {
                    ?terpene terp:name \"$term_name\" .
                    ?terpene ?property ?value .
                }
            ",
            'molecular_data' => "
                PREFIX terp: <http://terpedia.com/ontology#>
                PREFIX chem: <http://purl.org/dc/terms/>
                
                SELECT ?formula ?smiles ?inchi WHERE {
                    ?terpene terp:name \"$term_name\" .
                    ?terpene terp:molecularFormula ?formula .
                    ?terpene terp:smiles ?smiles .
                    ?terpene terp:inchi ?inchi .
                }
            ",
            'therapeutic_effects' => "
                PREFIX terp: <http://terpedia.com/ontology#>
                PREFIX owl: <http://www.w3.org/2002/07/owl#>
                
                SELECT ?effect ?mechanism WHERE {
                    ?terpene terp:name \"$term_name\" .
                    ?terpene terp:hasTherapeuticEffect ?effect .
                    ?effect terp:mechanism ?mechanism .
                }
            ",
            'natural_sources' => "
                PREFIX terp: <http://terpedia.com/ontology#>
                PREFIX bio: <http://purl.org/obo/owl/GO#>
                
                SELECT ?source ?concentration WHERE {
                    ?terpene terp:name \"$term_name\" .
                    ?terpene terp:foundIn ?source .
                    ?source terp:concentration ?concentration .
                }
            "
        );
        
        $results = array();
        foreach ($sparql_queries as $query_name => $query) {
            $results[$query_name] = $this->execute_sparql_query($query);
        }
        
        return $results;
    }
    
    /**
     * Execute SPARQL query
     */
    private function execute_sparql_query($query) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->sparql_endpoint);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, 'query=' . urlencode($query));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/x-www-form-urlencoded',
            'Accept: application/sparql-results+json'
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
     * Generate content using LLM
     */
    private function generate_llm_content($term_name, $category, $sparql_data, $rag_data) {
        if (empty($this->openrouter_api_key)) {
            return $this->generate_fallback_content($term_name, $category);
        }
        
        $prompt = $this->build_llm_prompt($term_name, $category, $sparql_data, $rag_data);
        
        $payload = array(
            'model' => get_option('terpedia_cyc_llm_model', 'anthropic/claude-3.5-sonnet'),
            'messages' => array(
                array(
                    'role' => 'user',
                    'content' => $prompt
                )
            ),
            'max_tokens' => 4000,
            'temperature' => 0.7
        );
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://openrouter.ai/api/v1/chat/completions');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->openrouter_api_key,
            'HTTP-Referer: ' . home_url(),
            'X-Title: Terpedia Encyclopedia'
        ));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code === 200) {
            $data = json_decode($response, true);
            if (isset($data['choices'][0]['message']['content'])) {
                return $data['choices'][0]['message']['content'];
            }
        }
        
        return $this->generate_fallback_content($term_name, $category);
    }
    
    /**
     * Build LLM prompt with context
     */
    private function build_llm_prompt($term_name, $category, $sparql_data, $rag_data) {
        $prompt = "You are Dr. Cyrus, a leading expert in terpenes and natural products. Generate a comprehensive encyclopedia entry for '$term_name' (Category: $category).\n\n";
        
        $prompt .= "Use the following data sources to create an authoritative, scientifically accurate entry:\n\n";
        
        if ($sparql_data) {
            $prompt .= "SPARQL Knowledge Base Data:\n";
            $prompt .= json_encode($sparql_data, JSON_PRETTY_PRINT) . "\n\n";
        }
        
        if ($rag_data && isset($rag_data['results'])) {
            $prompt .= "Relevant Research Articles:\n";
            foreach ($rag_data['results'] as $result) {
                $prompt .= "- " . $result['title'] . " (Score: " . $result['score'] . ")\n";
                $prompt .= "  " . substr($result['content'], 0, 200) . "...\n\n";
            }
        }
        
        $prompt .= "Please generate a comprehensive encyclopedia entry that includes:\n";
        $prompt .= "1. Introduction and overview\n";
        $prompt .= "2. Chemical properties and structure\n";
        $prompt .= "3. Natural sources and occurrence\n";
        $prompt .= "4. Therapeutic effects and mechanisms\n";
        $prompt .= "5. Traditional uses and applications\n";
        $prompt .= "6. Current research and clinical studies\n";
        $prompt .= "7. Safety considerations\n";
        $prompt .= "8. References and further reading\n\n";
        
        $prompt .= "Make the content scientifically accurate, well-structured, and accessible to both researchers and general readers. Use proper scientific terminology while maintaining readability.";
        
        return $prompt;
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
     * Update post with generated content
     */
    private function update_post_with_generated_content($post_id, $content) {
        $post_data = array(
            'ID' => $post_id,
            'post_content' => $content
        );
        
        wp_update_post($post_data);
        
        // Mark as auto-generated
        update_post_meta($post_id, 'auto_generated', true);
        update_post_meta($post_id, 'generated_at', current_time('mysql'));
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
}

// Initialize the Cyc Encyclopedia Manager
new TerpediaCycEncyclopediaManager();
