<?php
/**
 * Terport Template System with Structured Outputs
 * 
 * Manages Terport templates that map directly to OpenRouter structured outputs
 * 
 * @package Terpedia
 */

if (!defined('ABSPATH')) {
    exit;
}

class Terpedia_Terport_Template_System {
    
    public function __construct() {
        add_action('init', array($this, 'register_template_post_type'));
        add_action('admin_menu', array($this, 'add_template_management_menu'));
        add_action('wp_ajax_terpedia_save_terport_template', array($this, 'ajax_save_template'));
        add_action('wp_ajax_terpedia_delete_terport_template', array($this, 'ajax_delete_template'));
        add_action('wp_ajax_terpedia_get_terport_template_schema', array($this, 'ajax_get_template_schema'));
    }
    
    /**
     * Register template post type
     */
    public function register_template_post_type() {
        register_post_type('terpedia_terport_template', array(
            'labels' => array(
                'name' => 'Terport Templates',
                'singular_name' => 'Terport Template',
                'add_new' => 'Add New Template',
                'add_new_item' => 'Add New Terport Template',
                'edit_item' => 'Edit Terport Template',
                'new_item' => 'New Terport Template',
                'view_item' => 'View Terport Template',
                'search_items' => 'Search Templates',
                'not_found' => 'No templates found',
                'not_found_in_trash' => 'No templates found in trash',
                'all_items' => 'All Templates',
                'menu_name' => 'Terport Templates'
            ),
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => 'terpedia-settings',
            'supports' => array('title', 'editor'),
            'capability_type' => 'post',
            'hierarchical' => false,
            'rewrite' => false,
            'query_var' => false
        ));
    }
    
    /**
     * Add template management menu
     */
    public function add_template_management_menu() {
        add_submenu_page(
            'edit.php?post_type=terport',
            'Terport Templates',
            'Templates',
            'manage_options',
            'terport-templates',
            array($this, 'render_template_management_page')
        );
    }
    
    /**
     * Render template management page
     */
    public function render_template_management_page() {
        $action = $_GET['action'] ?? 'list';
        $template_id = intval($_GET['template_id'] ?? 0);
        
        ?>
        <div class="wrap">
            <h1>Terport Templates</h1>
            
            <?php if ($action === 'list'): ?>
                <?php $this->render_template_list(); ?>
            <?php elseif ($action === 'edit'): ?>
                <?php $this->render_template_editor($template_id); ?>
            <?php elseif ($action === 'add'): ?>
                <?php $this->render_template_editor(); ?>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Render template list
     */
    private function render_template_list() {
        $templates = get_posts(array(
            'post_type' => 'terpedia_terport_template',
            'post_status' => 'publish',
            'numberposts' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        ));
        
        ?>
        <div class="terport-template-management">
            <div class="template-actions">
                <a href="<?php echo admin_url('edit.php?post_type=terport&page=terport-templates&action=add'); ?>" class="button button-primary">Add New Template</a>
            </div>
            
            <?php if (empty($templates)): ?>
                <div class="notice notice-info">
                    <p>No templates found. <a href="<?php echo admin_url('edit.php?post_type=terport&page=terport-templates&action=add'); ?>">Create your first template</a>.</p>
                </div>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Template Name</th>
                            <th>Type</th>
                            <th>Sections</th>
                            <th>Schema</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($templates as $template): ?>
                            <?php 
                            $template_type = get_post_meta($template->ID, '_terpedia_template_type', true);
                            $sections = $this->extract_template_sections($template->post_content);
                            $schema = get_post_meta($template->ID, '_terpedia_template_schema', true);
                            ?>
                            <tr>
                                <td>
                                    <strong><?php echo esc_html($template->post_title); ?></strong>
                                </td>
                                <td>
                                    <?php echo esc_html(ucfirst(str_replace('_', ' ', $template_type))); ?>
                                </td>
                                <td>
                                    <?php echo esc_html(count($sections)); ?> sections
                                </td>
                                <td>
                                    <?php if ($schema): ?>
                                        <span class="dashicons dashicons-yes-alt" style="color: green;"></span> Valid
                                    <?php else: ?>
                                        <span class="dashicons dashicons-warning" style="color: orange;"></span> Missing
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="<?php echo admin_url('edit.php?post_type=terport&page=terport-templates&action=edit&template_id=' . $template->ID); ?>" class="button button-small">Edit</a>
                                    <button class="button button-small button-link-delete delete-template" data-template-id="<?php echo $template->ID; ?>">Delete</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('.delete-template').on('click', function() {
                if (!confirm('Are you sure you want to delete this template?')) {
                    return;
                }
                
                var templateId = $(this).data('template-id');
                var $row = $(this).closest('tr');
                
                $.post(ajaxurl, {
                    action: 'terpedia_delete_terport_template',
                    template_id: templateId,
                    nonce: '<?php echo wp_create_nonce('terpedia_terport_template_nonce'); ?>'
                }, function(response) {
                    if (response.success) {
                        $row.fadeOut();
                    } else {
                        alert('Error deleting template: ' + response.data);
                    }
                });
            });
        });
        </script>
        <?php
    }
    
    /**
     * Render template editor
     */
    private function render_template_editor($template_id = 0) {
        $template = null;
        if ($template_id > 0) {
            $template = get_post($template_id);
            if (!$template || $template->post_type !== 'terpedia_terport_template') {
                wp_die('Template not found');
            }
        }
        
        $template_type = $template ? get_post_meta($template->ID, '_terpedia_template_type', true) : '';
        $template_schema = $template ? get_post_meta($template->ID, '_terpedia_template_schema', true) : '';
        
        ?>
        <div class="terport-template-editor">
            <form method="post" action="" id="terport-template-form">
                <?php wp_nonce_field('terpedia_terport_template_nonce', 'terpedia_terport_template_nonce'); ?>
                <input type="hidden" name="template_id" value="<?php echo $template_id; ?>">
                
                <div class="template-editor-header">
                    <div class="template-title-section">
                        <label for="template_title" class="template-title-label">Template Name</label>
                        <input type="text" id="template_title" name="template_title" 
                               value="<?php echo $template ? esc_attr($template->post_title) : ''; ?>" 
                               class="template-title-input" required>
                    </div>
                    
                    <div class="template-type-section">
                        <label for="template_type" class="template-type-label">Template Type</label>
                        <select id="template_type" name="template_type" class="template-type-select">
                            <option value="">-- Select Type --</option>
                            <option value="research_analysis" <?php selected($template_type, 'research_analysis'); ?>>Research Analysis</option>
                            <option value="compound_profile" <?php selected($template_type, 'compound_profile'); ?>>Compound Profile</option>
                            <option value="clinical_study" <?php selected($template_type, 'clinical_study'); ?>>Clinical Study</option>
                            <option value="market_analysis" <?php selected($template_type, 'market_analysis'); ?>>Market Analysis</option>
                            <option value="regulatory_update" <?php selected($template_type, 'regulatory_update'); ?>>Regulatory Update</option>
                            <option value="industry_news" <?php selected($template_type, 'industry_news'); ?>>Industry News</option>
                        </select>
                    </div>
                    
                    <div class="template-actions">
                        <button type="submit" name="save_template" class="button button-primary">Save Template</button>
                        <a href="<?php echo admin_url('edit.php?post_type=terport&page=terport-templates'); ?>" class="button">Cancel</a>
                    </div>
                </div>
                
                <div class="template-editor-main">
                    <div class="template-editor-sidebar">
                        <div class="schema-panel">
                            <h3>Structured Output Schema</h3>
                            <div class="schema-info">
                                <p>This template will generate structured JSON output with the following sections:</p>
                                <div id="schema-preview" class="schema-preview">
                                    <!-- Schema preview will be generated here -->
                                </div>
                            </div>
                            <button type="button" id="generate-schema-btn" class="button button-secondary">Generate Schema</button>
                        </div>
                        
                        <div class="sections-panel">
                            <h3>Template Sections</h3>
                            <div id="sections-list" class="sections-list">
                                <!-- Sections will be listed here -->
                            </div>
                        </div>
                    </div>
                    
                    <div class="template-editor-content">
                        <div class="editor-header">
                            <label for="template_content" class="editor-label">Template Content</label>
                            <div class="editor-controls">
                                <button type="button" id="insert-section-btn" class="button button-small">Insert Section</button>
                                <button type="button" id="validate-template-btn" class="button button-small">Validate</button>
                            </div>
                        </div>
                        
                        <div class="codemirror-wrapper">
                            <textarea id="template_content" name="template_content" style="display: none;"><?php echo $template ? esc_textarea($template->post_content) : ''; ?></textarea>
                        </div>
                        
                        <div class="editor-footer">
                            <p class="description">
                                Use <code>{{section_name}}</code> syntax for dynamic content sections. 
                                Each section will be generated using structured outputs.
                            </p>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        
        <style>
        .terport-template-editor {
            max-width: 1400px;
            margin: 20px 0;
        }
        
        .template-editor-header {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
            padding: 20px;
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .template-title-section,
        .template-type-section {
            flex: 1;
        }
        
        .template-title-label,
        .template-type-label {
            display: block;
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .template-title-input,
        .template-type-select {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .template-actions {
            display: flex;
            gap: 10px;
            align-items: flex-end;
        }
        
        .template-editor-main {
            display: flex;
            gap: 20px;
            min-height: 600px;
        }
        
        .template-editor-sidebar {
            width: 350px;
            flex-shrink: 0;
        }
        
        .schema-panel,
        .sections-panel {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        
        .schema-panel h3,
        .sections-panel h3 {
            margin: 0;
            padding: 15px;
            background: #f9f9f9;
            border-bottom: 1px solid #ddd;
            font-size: 14px;
            font-weight: 600;
        }
        
        .schema-info {
            padding: 15px;
        }
        
        .schema-preview {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 4px;
            padding: 10px;
            margin: 10px 0;
            font-family: monospace;
            font-size: 12px;
            max-height: 200px;
            overflow-y: auto;
        }
        
        .sections-list {
            padding: 15px;
            max-height: 300px;
            overflow-y: auto;
        }
        
        .section-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .section-item:last-child {
            border-bottom: none;
        }
        
        .section-name {
            font-family: monospace;
            font-size: 12px;
            color: #0073aa;
        }
        
        .template-editor-content {
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        
        .editor-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .editor-label {
            font-weight: 600;
            font-size: 14px;
        }
        
        .editor-controls {
            display: flex;
            gap: 10px;
        }
        
        .codemirror-wrapper {
            flex: 1;
            border: 1px solid #ddd;
            border-radius: 4px;
            overflow: hidden;
        }
        
        .CodeMirror {
            height: 500px;
            font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
            font-size: 13px;
        }
        
        .editor-footer {
            margin-top: 10px;
            padding: 10px;
            background: #f9f9f9;
            border-radius: 4px;
        }
        
        .editor-footer .description {
            margin: 0;
            font-size: 13px;
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            var editor;
            
            // Initialize CodeMirror
            if (typeof CodeMirror !== 'undefined') {
                editor = CodeMirror.fromTextArea(document.getElementById('template_content'), {
                    mode: 'htmlmixed',
                    lineNumbers: true,
                    theme: 'default',
                    indentUnit: 2,
                    tabSize: 2,
                    lineWrapping: true
                });
            }
            
            // Generate schema when type changes
            $('#template_type').on('change', function() {
                generateSchema();
            });
            
            // Generate schema button
            $('#generate-schema-btn').on('click', function() {
                generateSchema();
            });
            
            // Insert section button
            $('#insert-section-btn').on('click', function() {
                var sectionName = prompt('Enter section name:');
                if (sectionName) {
                    var placeholder = '{{' + sectionName + '}}';
                    if (editor) {
                        editor.replaceSelection(placeholder);
                    } else {
                        var textarea = document.getElementById('template_content');
                        textarea.value += placeholder;
                    }
                    updateSectionsList();
                }
            });
            
            // Validate template
            $('#validate-template-btn').on('click', function() {
                validateTemplate();
            });
            
            // Update sections list when content changes
            if (editor) {
                editor.on('change', function() {
                    updateSectionsList();
                });
            } else {
                $('#template_content').on('input', function() {
                    updateSectionsList();
                });
            }
            
            function generateSchema() {
                var templateType = $('#template_type').val();
                if (!templateType) {
                    alert('Please select a template type first.');
                    return;
                }
                
                var schema = getDefaultSchema(templateType);
                $('#schema-preview').html('<pre>' + JSON.stringify(schema, null, 2) + '</pre>');
            }
            
            function updateSectionsList() {
                var content = editor ? editor.getValue() : $('#template_content').val();
                var sections = extractSections(content);
                
                var html = '';
                sections.forEach(function(section) {
                    html += '<div class="section-item">';
                    html += '<span class="section-name">{{' + section + '}}</span>';
                    html += '<button type="button" class="button button-small insert-section" data-section="' + section + '">Insert</button>';
                    html += '</div>';
                });
                
                $('#sections-list').html(html);
                
                // Bind insert section buttons
                $('.insert-section').on('click', function() {
                    var section = $(this).data('section');
                    var placeholder = '{{' + section + '}}';
                    if (editor) {
                        editor.replaceSelection(placeholder);
                    } else {
                        var textarea = document.getElementById('template_content');
                        textarea.value += placeholder;
                    }
                });
            }
            
            function extractSections(content) {
                var matches = content.match(/\{\{([^}]+)\}\}/g);
                if (!matches) return [];
                
                var sections = [];
                matches.forEach(function(match) {
                    var section = match.replace(/\{\{|\}\}/g, '');
                    if (sections.indexOf(section) === -1) {
                        sections.push(section);
                    }
                });
                
                return sections;
            }
            
            function getDefaultSchema(templateType) {
                var schemas = {
                    'research_analysis': {
                        'type': 'object',
                        'properties': {
                            'executive_summary': {'type': 'string', 'description': 'Comprehensive summary for stakeholders'},
                            'introduction': {'type': 'string', 'description': 'Background and context introduction'},
                            'methodology': {'type': 'string', 'description': 'Research methodology and approach'},
                            'findings': {'type': 'string', 'description': 'Key research findings and results'},
                            'analysis': {'type': 'string', 'description': 'Analysis and interpretation of findings'},
                            'implications': {'type': 'string', 'description': 'Clinical and research implications'},
                            'conclusion': {'type': 'string', 'description': 'Summary and conclusions'},
                            'references': {'type': 'string', 'description': 'Academic references and citations'}
                        },
                        'required': ['executive_summary', 'introduction', 'findings', 'conclusion']
                    },
                    'compound_profile': {
                        'type': 'object',
                        'properties': {
                            'chemical_structure': {'type': 'string', 'description': 'Chemical structure and properties'},
                            'biological_activity': {'type': 'string', 'description': 'Biological activity and mechanisms'},
                            'therapeutic_effects': {'type': 'string', 'description': 'Therapeutic effects and applications'},
                            'safety_profile': {'type': 'string', 'description': 'Safety and toxicity information'},
                            'research_evidence': {'type': 'string', 'description': 'Research evidence and studies'},
                            'clinical_applications': {'type': 'string', 'description': 'Clinical applications and uses'},
                            'interactions': {'type': 'string', 'description': 'Drug interactions and contraindications'},
                            'future_research': {'type': 'string', 'description': 'Future research directions'}
                        },
                        'required': ['chemical_structure', 'biological_activity', 'therapeutic_effects']
                    }
                };
                
                return schemas[templateType] || {
                    'type': 'object',
                    'properties': {
                        'content': {'type': 'string', 'description': 'Main content section'}
                    },
                    'required': ['content']
                };
            }
            
            function validateTemplate() {
                var content = editor ? editor.getValue() : $('#template_content').val();
                var sections = extractSections(content);
                
                if (sections.length === 0) {
                    alert('Template has no sections. Add sections using {{section_name}} syntax.');
                    return;
                }
                
                alert('Template is valid with ' + sections.length + ' sections.');
            }
            
            // Initialize on page load
            updateSectionsList();
            if ($('#template_type').val()) {
                generateSchema();
            }
        });
        </script>
        <?php
    }
    
    /**
     * Extract sections from template content
     */
    private function extract_template_sections($content) {
        preg_match_all('/\{\{([^}]+)\}\}/', $content, $matches);
        return array_unique($matches[1] ?? array());
    }
    
    /**
     * AJAX: Save template
     */
    public function ajax_save_template() {
        check_ajax_referer('terpedia_terport_template_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $template_id = intval($_POST['template_id'] ?? 0);
        $template_title = sanitize_text_field($_POST['template_title']);
        $template_content = wp_kses_post($_POST['template_content']);
        $template_type = sanitize_text_field($_POST['template_type']);
        
        if (empty($template_title) || empty($template_content) || empty($template_type)) {
            wp_send_json_error('Missing required fields');
        }
        
        $post_data = array(
            'post_title' => $template_title,
            'post_content' => $template_content,
            'post_type' => 'terpedia_terport_template',
            'post_status' => 'publish'
        );
        
        if ($template_id > 0) {
            $post_data['ID'] = $template_id;
            $result = wp_update_post($post_data);
        } else {
            $result = wp_insert_post($post_data);
        }
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }
        
        update_post_meta($result, '_terpedia_template_type', $template_type);
        
        // Generate and save schema
        $schema = $this->generate_template_schema($template_content, $template_type);
        update_post_meta($result, '_terpedia_template_schema', $schema);
        
        wp_send_json_success(array(
            'message' => $template_id > 0 ? 'Template updated successfully' : 'Template created successfully',
            'template_id' => $result
        ));
    }
    
    /**
     * AJAX: Delete template
     */
    public function ajax_delete_template() {
        check_ajax_referer('terpedia_terport_template_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $template_id = intval($_POST['template_id'] ?? 0);
        
        if ($template_id <= 0) {
            wp_send_json_error('Invalid template ID');
        }
        
        $template = get_post($template_id);
        if (!$template || $template->post_type !== 'terpedia_terport_template') {
            wp_send_json_error('Template not found');
        }
        
        $result = wp_delete_post($template_id, true);
        
        if (!$result) {
            wp_send_json_error('Failed to delete template');
        }
        
        wp_send_json_success('Template deleted successfully');
    }
    
    /**
     * AJAX: Get template schema
     */
    public function ajax_get_template_schema() {
        check_ajax_referer('terpedia_terport_template_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $template_id = intval($_POST['template_id'] ?? 0);
        
        if ($template_id <= 0) {
            wp_send_json_error('Invalid template ID');
        }
        
        $schema = get_post_meta($template_id, '_terpedia_template_schema', true);
        
        if (!$schema) {
            wp_send_json_error('Schema not found');
        }
        
        wp_send_json_success($schema);
    }
    
    /**
     * Generate template schema
     */
    private function generate_template_schema($content, $type) {
        $sections = $this->extract_template_sections($content);
        
        $properties = array();
        $required = array();
        
        foreach ($sections as $section) {
            $properties[$section] = array(
                'type' => 'string',
                'description' => "Content for the {$section} section"
            );
            $required[] = $section;
        }
        
        return array(
            'type' => 'object',
            'properties' => $properties,
            'required' => $required,
            'additionalProperties' => false
        );
    }
}

// Initialize the template system
new Terpedia_Terport_Template_System();
