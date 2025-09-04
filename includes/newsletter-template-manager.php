<?php
/**
 * Newsletter Template Manager for Terpedia
 * Allows creation and management of newsletter templates with configurable sections
 */

if (!defined('ABSPATH')) {
    exit;
}

class Terpedia_Newsletter_Template_Manager {
    
    private $table_name;
    private $sections_table_name;
    
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'terpedia_newsletter_templates';
        $this->sections_table_name = $wpdb->prefix . 'terpedia_newsletter_sections';
        
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_ajax_save_newsletter_template', array($this, 'save_newsletter_template'));
        add_action('wp_ajax_delete_newsletter_template', array($this, 'delete_newsletter_template'));
        add_action('wp_ajax_generate_newsletter_from_template', array($this, 'generate_newsletter_from_template'));
        add_action('wp_ajax_schedule_newsletter', array($this, 'schedule_newsletter'));
        add_action('terpedia_weekly_newsletter', array($this, 'process_scheduled_newsletter'));
        
        // Create tables on activation
        register_activation_hook(__FILE__, array($this, 'create_tables'));
    }
    
    public function init() {
        $this->create_tables();
    }
    
    /**
     * Create database tables for newsletter templates and sections
     */
    public function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Newsletter templates table
        $sql_templates = "CREATE TABLE IF NOT EXISTS {$this->table_name} (
            id int(11) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            description text,
            frequency enum('daily','weekly','biweekly','monthly') DEFAULT 'weekly',
            is_active tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        // Newsletter sections table
        $sql_sections = "CREATE TABLE IF NOT EXISTS {$this->sections_table_name} (
            id int(11) NOT NULL AUTO_INCREMENT,
            template_id int(11) NOT NULL,
            section_name varchar(255) NOT NULL,
            section_title varchar(255) NOT NULL,
            section_prompt text NOT NULL,
            section_type enum('recent_posts','recent_science','industry_news','agent_spotlight','podcast_highlights','community_corner','market_analysis','research_spotlight','quick_facts','custom') DEFAULT 'custom',
            is_required tinyint(1) DEFAULT 0,
            sort_order int(11) DEFAULT 0,
            word_count int(11) DEFAULT 200,
            data_source varchar(255),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY template_id (template_id),
            FOREIGN KEY (template_id) REFERENCES {$this->table_name}(id) ON DELETE CASCADE
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_templates);
        dbDelta($sql_sections);
        
        // Insert default templates if they don't exist
        $this->create_default_templates();
    }
    
    /**
     * Create default newsletter templates
     */
    private function create_default_templates() {
        global $wpdb;
        
        // Check if templates already exist
        $existing = $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name}");
        if ($existing > 0) {
            return;
        }
        
        // Default Terpene Times Weekly template
        $template_id = $wpdb->insert(
            $this->table_name,
            array(
                'name' => 'Terpene Times Weekly',
                'description' => 'Weekly newsletter covering terpene research, industry news, and community highlights',
                'frequency' => 'weekly',
                'is_active' => 1
            )
        );
        
        if ($template_id) {
            $template_id = $wpdb->insert_id;
            
            // Define default sections
            $sections = array(
                array(
                    'template_id' => $template_id,
                    'section_name' => 'breakthrough_highlight',
                    'section_title' => '🚀 Breakthrough Highlight',
                    'section_prompt' => 'Write a compelling headline story about a recent breakthrough in terpene research, clinical trials, or industry development. Include specific data, quotes from researchers, and implications for the field. 200-300 words.',
                    'section_type' => 'recent_science',
                    'is_required' => 1,
                    'sort_order' => 1,
                    'word_count' => 250,
                    'data_source' => 'pubmed_recent'
                ),
                array(
                    'template_id' => $template_id,
                    'section_name' => 'research_roundup',
                    'section_title' => '🔬 Research Roundup',
                    'section_prompt' => 'Summarize 3-4 recent peer-reviewed studies about terpenes published in the last week. For each study, provide: title, key findings, methodology brief, and practical implications. 300-400 words total.',
                    'section_type' => 'recent_science',
                    'is_required' => 1,
                    'sort_order' => 2,
                    'word_count' => 350,
                    'data_source' => 'pubmed_weekly'
                ),
                array(
                    'template_id' => $template_id,
                    'section_name' => 'industry_intel',
                    'section_title' => '📊 Industry Intel',
                    'section_prompt' => 'Cover recent industry news including company acquisitions, new product launches, regulatory changes, and market trends in the terpene and cannabis space. 200-250 words.',
                    'section_type' => 'industry_news',
                    'is_required' => 1,
                    'sort_order' => 3,
                    'word_count' => 225,
                    'data_source' => 'industry_feeds'
                ),
                array(
                    'template_id' => $template_id,
                    'section_name' => 'agent_spotlight',
                    'section_title' => '🤖 Agent Spotlight',
                    'section_prompt' => 'Feature one of our AI agents or tersonas, highlighting their recent insights, popular forum discussions, or upcoming projects. Include quotes from their recent interactions. 150-200 words.',
                    'section_type' => 'agent_spotlight',
                    'is_required' => 1,
                    'sort_order' => 4,
                    'word_count' => 175,
                    'data_source' => 'agent_activity'
                ),
                array(
                    'template_id' => $template_id,
                    'section_name' => 'recent_posts',
                    'section_title' => '📝 Recent Posts',
                    'section_prompt' => 'Highlight the most engaging and informative posts from our community this week. Include a mix of user-generated content, expert insights, and trending discussions. 150-200 words.',
                    'section_type' => 'recent_posts',
                    'is_required' => 0,
                    'sort_order' => 5,
                    'word_count' => 175,
                    'data_source' => 'recent_posts'
                ),
                array(
                    'template_id' => $template_id,
                    'section_name' => 'podcast_highlights',
                    'section_title' => '🎙️ Podcast Highlights',
                    'section_prompt' => 'Summarize key insights from recent TerpeneQueen podcast episodes, including guest highlights and most popular listener questions. 150-200 words.',
                    'section_type' => 'podcast_highlights',
                    'is_required' => 0,
                    'sort_order' => 6,
                    'word_count' => 175,
                    'data_source' => 'podcast_episodes'
                ),
                array(
                    'template_id' => $template_id,
                    'section_name' => 'community_corner',
                    'section_title' => '👥 Community Corner',
                    'section_prompt' => 'Highlight interesting forum discussions, user questions, and community contributions from the Terpedia platform. Include engagement statistics. 100-150 words.',
                    'section_type' => 'community_corner',
                    'is_required' => 0,
                    'sort_order' => 7,
                    'word_count' => 125,
                    'data_source' => 'forum_activity'
                ),
                array(
                    'template_id' => $template_id,
                    'section_name' => 'quick_facts',
                    'section_title' => '⚡ Quick Facts',
                    'section_prompt' => 'Provide 3-5 interesting, lesser-known facts about terpenes, their effects, or recent discoveries. Make them shareable and educational. 100-150 words.',
                    'section_type' => 'quick_facts',
                    'is_required' => 0,
                    'sort_order' => 8,
                    'word_count' => 125,
                    'data_source' => 'terpene_database'
                )
            );
            
            foreach ($sections as $section) {
                $wpdb->insert($this->sections_table_name, $section);
            }
        }
    }
    
    /**
     * Add admin menu for newsletter template management
     */
    public function add_admin_menu() {
        add_submenu_page(
            'terpedia-admin',
            'Newsletter Templates',
            'Newsletter Templates',
            'manage_options',
            'terpedia-newsletter-templates',
            array($this, 'admin_page')
        );
    }
    
    /**
     * Admin page for managing newsletter templates
     */
    public function admin_page() {
        global $wpdb;
        
        $templates = $wpdb->get_results("SELECT * FROM {$this->table_name} ORDER BY created_at DESC");
        
        ?>
        <div class="wrap">
            <h1>Newsletter Template Manager</h1>
            
            <div class="terpedia-newsletter-admin">
                <div class="newsletter-templates-list">
                    <h2>Newsletter Templates</h2>
                    
                    <div class="template-actions">
                        <button id="create-new-template" class="button button-primary">Create New Template</button>
                        <button id="generate-newsletter" class="button button-secondary">Generate Newsletter Now</button>
                    </div>
                    
                    <div class="templates-grid">
                        <?php foreach ($templates as $template): ?>
                            <div class="template-card" data-template-id="<?php echo $template->id; ?>">
                                <div class="template-header">
                                    <h3><?php echo esc_html($template->name); ?></h3>
                                    <div class="template-actions">
                                        <button class="edit-template button button-small" data-template-id="<?php echo $template->id; ?>">Edit</button>
                                        <button class="delete-template button button-small button-link-delete" data-template-id="<?php echo $template->id; ?>">Delete</button>
                                    </div>
                                </div>
                                <div class="template-info">
                                    <p><strong>Frequency:</strong> <?php echo esc_html(ucfirst($template->frequency)); ?></p>
                                    <p><strong>Status:</strong> <?php echo $template->is_active ? 'Active' : 'Inactive'; ?></p>
                                    <p><strong>Description:</strong> <?php echo esc_html($template->description); ?></p>
                                </div>
                                <div class="template-sections">
                                    <?php
                                    $sections = $wpdb->get_results($wpdb->prepare(
                                        "SELECT * FROM {$this->sections_table_name} WHERE template_id = %d ORDER BY sort_order",
                                        $template->id
                                    ));
                                    ?>
                                    <h4>Sections (<?php echo count($sections); ?>):</h4>
                                    <ul>
                                        <?php foreach ($sections as $section): ?>
                                            <li>
                                                <?php echo esc_html($section->section_title); ?>
                                                <?php if ($section->is_required): ?>
                                                    <span class="required-badge">Required</span>
                                                <?php endif; ?>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Template Editor Modal -->
                <div id="template-editor-modal" class="modal" style="display: none;">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h2 id="modal-title">Create New Template</h2>
                            <span class="close">&times;</span>
                        </div>
                        <div class="modal-body">
                            <form id="template-form">
                                <input type="hidden" id="template-id" name="template_id" value="">
                                
                                <div class="form-group">
                                    <label for="template-name">Template Name:</label>
                                    <input type="text" id="template-name" name="name" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="template-description">Description:</label>
                                    <textarea id="template-description" name="description" rows="3"></textarea>
                                </div>
                                
                                <div class="form-group">
                                    <label for="template-frequency">Frequency:</label>
                                    <select id="template-frequency" name="frequency">
                                        <option value="daily">Daily</option>
                                        <option value="weekly" selected>Weekly</option>
                                        <option value="biweekly">Bi-weekly</option>
                                        <option value="monthly">Monthly</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label>
                                        <input type="checkbox" id="template-active" name="is_active" checked>
                                        Active Template
                                    </label>
                                </div>
                                
                                <div class="sections-container">
                                    <h3>Newsletter Sections</h3>
                                    <div id="sections-list">
                                        <!-- Sections will be populated here -->
                                    </div>
                                    <button type="button" id="add-section" class="button">Add Section</button>
                                </div>
                                
                                <div class="form-actions">
                                    <button type="submit" class="button button-primary">Save Template</button>
                                    <button type="button" class="button cancel-modal">Cancel</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <style>
        .terpedia-newsletter-admin {
            max-width: 1200px;
        }
        
        .template-actions {
            margin-bottom: 20px;
        }
        
        .templates-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .template-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            background: #fff;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .template-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .template-header h3 {
            margin: 0;
            color: #23282d;
        }
        
        .template-info p {
            margin: 5px 0;
            font-size: 14px;
        }
        
        .template-sections {
            margin-top: 15px;
        }
        
        .template-sections h4 {
            margin: 0 0 10px 0;
            font-size: 14px;
            color: #666;
        }
        
        .template-sections ul {
            margin: 0;
            padding-left: 20px;
        }
        
        .template-sections li {
            margin: 5px 0;
            font-size: 13px;
        }
        
        .required-badge {
            background: #0073aa;
            color: white;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 11px;
            margin-left: 5px;
        }
        
        .modal {
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        
        .modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 0;
            border: none;
            width: 90%;
            max-width: 800px;
            max-height: 90vh;
            overflow-y: auto;
            border-radius: 8px;
        }
        
        .modal-header {
            padding: 20px;
            border-bottom: 1px solid #ddd;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-body {
            padding: 20px;
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
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
        }
        
        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .sections-container {
            margin-top: 30px;
        }
        
        .section-item {
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 15px;
            margin-bottom: 15px;
            background: #f9f9f9;
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .section-header h4 {
            margin: 0;
        }
        
        .form-actions {
            margin-top: 30px;
            text-align: right;
        }
        
        .form-actions button {
            margin-left: 10px;
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            // Template management functionality
            $('#create-new-template').click(function() {
                $('#modal-title').text('Create New Template');
                $('#template-form')[0].reset();
                $('#template-id').val('');
                $('#sections-list').empty();
                $('#template-editor-modal').show();
            });
            
            $('.edit-template').click(function() {
                var templateId = $(this).data('template-id');
                loadTemplateForEdit(templateId);
            });
            
            $('.delete-template').click(function() {
                if (confirm('Are you sure you want to delete this template?')) {
                    var templateId = $(this).data('template-id');
                    deleteTemplate(templateId);
                }
            });
            
            $('#add-section').click(function() {
                addSectionItem();
            });
            
            $('.close, .cancel-modal').click(function() {
                $('#template-editor-modal').hide();
            });
            
            $(window).click(function(event) {
                if (event.target.id === 'template-editor-modal') {
                    $('#template-editor-modal').hide();
                }
            });
            
            $('#template-form').submit(function(e) {
                e.preventDefault();
                saveTemplate();
            });
            
            function addSectionItem() {
                var sectionHtml = `
                    <div class="section-item">
                        <div class="section-header">
                            <h4>Section</h4>
                            <button type="button" class="button button-small remove-section">Remove</button>
                        </div>
                        <div class="form-group">
                            <label>Section Name (ID):</label>
                            <input type="text" name="sections[][name]" placeholder="e.g., breakthrough_highlight" required>
                        </div>
                        <div class="form-group">
                            <label>Section Title:</label>
                            <input type="text" name="sections[][title]" placeholder="e.g., 🚀 Breakthrough Highlight" required>
                        </div>
                        <div class="form-group">
                            <label>Section Type:</label>
                            <select name="sections[][type]">
                                <option value="recent_posts">Recent Posts</option>
                                <option value="recent_science">Recent Science</option>
                                <option value="industry_news">Industry News</option>
                                <option value="agent_spotlight">Agent Spotlight</option>
                                <option value="podcast_highlights">Podcast Highlights</option>
                                <option value="community_corner">Community Corner</option>
                                <option value="market_analysis">Market Analysis</option>
                                <option value="research_spotlight">Research Spotlight</option>
                                <option value="quick_facts">Quick Facts</option>
                                <option value="custom">Custom</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Prompt:</label>
                            <textarea name="sections[][prompt]" rows="3" placeholder="Describe what content should be generated for this section..." required></textarea>
                        </div>
                        <div class="form-group">
                            <label>Word Count:</label>
                            <input type="number" name="sections[][word_count]" value="200" min="50" max="1000">
                        </div>
                        <div class="form-group">
                            <label>Data Source:</label>
                            <select name="sections[][data_source]">
                                <option value="">None</option>
                                <option value="pubmed_recent">Recent PubMed Articles</option>
                                <option value="pubmed_weekly">Weekly PubMed Articles</option>
                                <option value="industry_feeds">Industry News Feeds</option>
                                <option value="agent_activity">Agent Activity</option>
                                <option value="recent_posts">Recent Posts</option>
                                <option value="podcast_episodes">Podcast Episodes</option>
                                <option value="forum_activity">Forum Activity</option>
                                <option value="terpene_database">Terpene Database</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>
                                <input type="checkbox" name="sections[][required]" value="1">
                                Required Section
                            </label>
                        </div>
                    </div>
                `;
                $('#sections-list').append(sectionHtml);
            }
            
            $(document).on('click', '.remove-section', function() {
                $(this).closest('.section-item').remove();
            });
            
            function loadTemplateForEdit(templateId) {
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'get_newsletter_template',
                        template_id: templateId
                    },
                    success: function(response) {
                        if (response.success) {
                            var template = response.data;
                            $('#template-id').val(template.id);
                            $('#template-name').val(template.name);
                            $('#template-description').val(template.description);
                            $('#template-frequency').val(template.frequency);
                            $('#template-active').prop('checked', template.is_active == 1);
                            
                            $('#sections-list').empty();
                            template.sections.forEach(function(section) {
                                addSectionItem();
                                var lastSection = $('#sections-list .section-item').last();
                                lastSection.find('input[name*="[name]"]').val(section.section_name);
                                lastSection.find('input[name*="[title]"]').val(section.section_title);
                                lastSection.find('select[name*="[type]"]').val(section.section_type);
                                lastSection.find('textarea[name*="[prompt]"]').val(section.section_prompt);
                                lastSection.find('input[name*="[word_count]"]').val(section.word_count);
                                lastSection.find('select[name*="[data_source]"]').val(section.data_source);
                                lastSection.find('input[name*="[required]"]').prop('checked', section.is_required == 1);
                            });
                            
                            $('#modal-title').text('Edit Template: ' + template.name);
                            $('#template-editor-modal').show();
                        }
                    }
                });
            }
            
            function saveTemplate() {
                var formData = $('#template-form').serialize();
                formData += '&action=save_newsletter_template';
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: formData,
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert('Error saving template: ' + response.data);
                        }
                    }
                });
            }
            
            function deleteTemplate(templateId) {
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'delete_newsletter_template',
                        template_id: templateId
                    },
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert('Error deleting template: ' + response.data);
                        }
                    }
                });
            }
        });
        </script>
        <?php
    }
    
    /**
     * Save newsletter template via AJAX
     */
    public function save_newsletter_template() {
        check_ajax_referer('terpedia_newsletter_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        global $wpdb;
        
        $template_id = intval($_POST['template_id']);
        $name = sanitize_text_field($_POST['name']);
        $description = sanitize_textarea_field($_POST['description']);
        $frequency = sanitize_text_field($_POST['frequency']);
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $sections = $_POST['sections'] ?? array();
        
        if ($template_id) {
            // Update existing template
            $result = $wpdb->update(
                $this->table_name,
                array(
                    'name' => $name,
                    'description' => $description,
                    'frequency' => $frequency,
                    'is_active' => $is_active
                ),
                array('id' => $template_id)
            );
            
            // Delete existing sections
            $wpdb->delete($this->sections_table_name, array('template_id' => $template_id));
        } else {
            // Create new template
            $result = $wpdb->insert(
                $this->table_name,
                array(
                    'name' => $name,
                    'description' => $description,
                    'frequency' => $frequency,
                    'is_active' => $is_active
                )
            );
            
            if ($result) {
                $template_id = $wpdb->insert_id;
            }
        }
        
        // Add sections
        if ($template_id && !empty($sections)) {
            foreach ($sections as $index => $section) {
                $wpdb->insert(
                    $this->sections_table_name,
                    array(
                        'template_id' => $template_id,
                        'section_name' => sanitize_text_field($section['name']),
                        'section_title' => sanitize_text_field($section['title']),
                        'section_prompt' => sanitize_textarea_field($section['prompt']),
                        'section_type' => sanitize_text_field($section['type']),
                        'is_required' => isset($section['required']) ? 1 : 0,
                        'sort_order' => $index + 1,
                        'word_count' => intval($section['word_count']),
                        'data_source' => sanitize_text_field($section['data_source'])
                    )
                );
            }
        }
        
        wp_send_json_success('Template saved successfully');
    }
    
    /**
     * Delete newsletter template via AJAX
     */
    public function delete_newsletter_template() {
        check_ajax_referer('terpedia_newsletter_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        global $wpdb;
        
        $template_id = intval($_POST['template_id']);
        
        $result = $wpdb->delete($this->table_name, array('id' => $template_id));
        
        if ($result) {
            wp_send_json_success('Template deleted successfully');
        } else {
            wp_send_json_error('Failed to delete template');
        }
    }
    
    /**
     * Get all newsletter templates
     */
    public function get_templates() {
        global $wpdb;
        
        $templates = $wpdb->get_results("SELECT * FROM {$this->table_name} WHERE is_active = 1 ORDER BY name");
        
        foreach ($templates as &$template) {
            $template->sections = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$this->sections_table_name} WHERE template_id = %d ORDER BY sort_order",
                $template->id
            ));
        }
        
        return $templates;
    }
    
    /**
     * Get a specific template with its sections
     */
    public function get_template($template_id) {
        global $wpdb;
        
        $template = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE id = %d",
            $template_id
        ));
        
        if ($template) {
            $template->sections = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$this->sections_table_name} WHERE template_id = %d ORDER BY sort_order",
                $template_id
            ));
        }
        
        return $template;
    }
    
    /**
     * Generate newsletter content from template
     */
    public function generate_newsletter_from_template($template_id = null) {
        if (!$template_id) {
            $template_id = intval($_POST['template_id']);
        }
        
        $template = $this->get_template($template_id);
        if (!$template) {
            wp_send_json_error('Template not found');
        }
        
        $newsletter_content = array(
            'template_id' => $template_id,
            'title' => $template->name . ' - ' . date('F j, Y'),
            'date' => current_time('mysql'),
            'sections' => array()
        );
        
        // Generate content for each section
        foreach ($template->sections as $section) {
            $content = $this->generate_section_content($section);
            $newsletter_content['sections'][] = array(
                'section_name' => $section->section_name,
                'section_title' => $section->section_title,
                'content' => $content
            );
        }
        
        // Save newsletter as post
        $post_id = wp_insert_post(array(
            'post_title' => $newsletter_content['title'],
            'post_content' => $this->format_newsletter_html($newsletter_content),
            'post_status' => 'draft',
            'post_type' => 'terpedia_newsletter',
            'meta_input' => array(
                '_newsletter_template_id' => $template_id,
                '_newsletter_generated_date' => current_time('mysql'),
                '_newsletter_sections' => $newsletter_content['sections']
            )
        ));
        
        if ($post_id) {
            wp_send_json_success(array(
                'post_id' => $post_id,
                'edit_url' => admin_url('post.php?post=' . $post_id . '&action=edit'),
                'content' => $newsletter_content
            ));
        } else {
            wp_send_json_error('Failed to create newsletter post');
        }
    }
    
    /**
     * Generate content for a specific section
     */
    private function generate_section_content($section) {
        // Get data based on data source
        $data = $this->get_section_data($section->data_source);
        
        // Use AI to generate content (integrate with existing AI system)
        $prompt = $section->section_prompt;
        if ($data) {
            $prompt .= "\n\nAvailable data: " . json_encode($data);
        }
        
        // For now, return placeholder content
        // TODO: Integrate with AI content generation system
        return "Generated content for {$section->section_title} based on {$section->data_source} data source. This would be AI-generated content following the prompt: " . substr($section->section_prompt, 0, 100) . "...";
    }
    
    /**
     * Get data for a section based on its data source
     */
    private function get_section_data($data_source) {
        switch ($data_source) {
            case 'pubmed_recent':
                return $this->get_recent_pubmed_articles();
            case 'pubmed_weekly':
                return $this->get_weekly_pubmed_articles();
            case 'industry_feeds':
                return $this->get_industry_news();
            case 'agent_activity':
                return $this->get_agent_activity();
            case 'recent_posts':
                return $this->get_recent_posts();
            case 'podcast_episodes':
                return $this->get_recent_podcast_episodes();
            case 'forum_activity':
                return $this->get_forum_activity();
            case 'terpene_database':
                return $this->get_terpene_facts();
            default:
                return null;
        }
    }
    
    /**
     * Get recent PubMed articles
     */
    private function get_recent_pubmed_articles() {
        // TODO: Implement PubMed API integration
        return array(
            array(
                'title' => 'Recent terpene research study',
                'authors' => 'Smith et al.',
                'journal' => 'Nature',
                'date' => date('Y-m-d'),
                'abstract' => 'Study abstract...'
            )
        );
    }
    
    /**
     * Get weekly PubMed articles
     */
    private function get_weekly_pubmed_articles() {
        // TODO: Implement weekly PubMed article aggregation
        return $this->get_recent_pubmed_articles();
    }
    
    /**
     * Get industry news
     */
    private function get_industry_news() {
        // TODO: Implement industry news feed integration
        return array(
            array(
                'title' => 'Industry news headline',
                'source' => 'Industry Source',
                'date' => date('Y-m-d'),
                'summary' => 'News summary...'
            )
        );
    }
    
    /**
     * Get agent activity
     */
    private function get_agent_activity() {
        // TODO: Implement agent activity tracking
        return array(
            array(
                'agent_name' => 'TerpeneQueen',
                'activity_type' => 'forum_post',
                'content' => 'Recent agent activity...',
                'date' => date('Y-m-d')
            )
        );
    }
    
    /**
     * Get recent posts
     */
    private function get_recent_posts() {
        $posts = get_posts(array(
            'numberposts' => 5,
            'post_type' => 'post',
            'post_status' => 'publish',
            'date_query' => array(
                array(
                    'after' => '1 week ago'
                )
            )
        ));
        
        return array_map(function($post) {
            return array(
                'title' => $post->post_title,
                'excerpt' => $post->post_excerpt,
                'url' => get_permalink($post->ID),
                'date' => $post->post_date
            );
        }, $posts);
    }
    
    /**
     * Get recent podcast episodes
     */
    private function get_recent_podcast_episodes() {
        // TODO: Implement podcast episode retrieval
        return array(
            array(
                'title' => 'Recent podcast episode',
                'description' => 'Episode description...',
                'date' => date('Y-m-d')
            )
        );
    }
    
    /**
     * Get forum activity
     */
    private function get_forum_activity() {
        // TODO: Implement forum activity tracking
        return array(
            array(
                'topic' => 'Forum discussion topic',
                'replies' => 5,
                'last_activity' => date('Y-m-d')
            )
        );
    }
    
    /**
     * Get terpene facts
     */
    private function get_terpene_facts() {
        // TODO: Implement terpene database integration
        return array(
            'Limonene is found in citrus fruits and has been shown to have anti-anxiety properties.',
            'Myrcene is the most common terpene in cannabis and has sedative effects.',
            'Pinene can help improve memory and alertness.'
        );
    }
    
    /**
     * Format newsletter content as HTML
     */
    private function format_newsletter_html($newsletter_content) {
        $html = '<div class="terpedia-newsletter">';
        $html .= '<div class="newsletter-header">';
        $html .= '<h1>' . esc_html($newsletter_content['title']) . '</h1>';
        $html .= '<p class="newsletter-date">' . esc_html($newsletter_content['date']) . '</p>';
        $html .= '</div>';
        
        foreach ($newsletter_content['sections'] as $section) {
            $html .= '<div class="newsletter-section">';
            $html .= '<h2>' . esc_html($section['section_title']) . '</h2>';
            $html .= '<div class="section-content">' . wp_kses_post($section['content']) . '</div>';
            $html .= '</div>';
        }
        
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Process scheduled newsletter generation
     */
    public function process_scheduled_newsletter() {
        // Get active templates for weekly frequency
        global $wpdb;
        
        $templates = $wpdb->get_results(
            "SELECT * FROM {$this->table_name} WHERE is_active = 1 AND frequency = 'weekly'"
        );
        
        foreach ($templates as $template) {
            $this->generate_newsletter_from_template($template->id);
        }
    }
}

// Initialize the newsletter template manager
new Terpedia_Newsletter_Template_Manager();
