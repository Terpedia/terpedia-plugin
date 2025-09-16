<?php
/**
 * CPT Template Management System
 * 
 * Creates template management for each Custom Post Type with:
 * - Dedicated template admin menus per CPT
 * - Template Editor Gutenberg block with field markup
 * - OpenRouter structured output schema generation
 * 
 * @package Terpedia
 */

if (!defined('ABSPATH')) {
    exit;
}

class Terpedia_CPT_Template_Management {
    
    private $template_cpts = array();
    private $openrouter_api;
    
    public function __construct() {
        // Define CPTs that should have templates
        $this->template_cpts = array(
            'terport' => array(
                'label' => 'Terport Templates',
                'menu_icon' => '📚'
            ),
            'terpedia_terproduct' => array(
                'label' => 'Terproduct Templates', 
                'menu_icon' => '🧪'
            ),
            'terpedia_rx' => array(
                'label' => 'Rx Templates',
                'menu_icon' => '💊'
            )
        );
        
        add_action('init', array($this, 'register_template_post_types'));
        add_action('admin_menu', array($this, 'add_template_admin_menus'));
        add_action('add_meta_boxes', array($this, 'add_template_meta_boxes'));
        add_action('save_post', array($this, 'save_template_meta'));
        
        // AJAX handlers for template operations
        add_action('wp_ajax_parse_template_markup', array($this, 'parse_template_markup'));
        add_action('wp_ajax_test_template_generation', array($this, 'test_template_generation'));
        
        // Initialize OpenRouter API
        $this->openrouter_api = new Terpedia_OpenRouter_API();
    }
    
    /**
     * Register template post types for each CPT
     */
    public function register_template_post_types() {
        foreach ($this->template_cpts as $base_cpt => $config) {
            $template_cpt = $base_cpt . '_template';
            
            register_post_type($template_cpt, array(
                'labels' => array(
                    'name' => $config['label'],
                    'singular_name' => rtrim($config['label'], 's'),
                    'add_new' => 'Add New Template',
                    'add_new_item' => 'Add New ' . rtrim($config['label'], 's'),
                    'edit_item' => 'Edit Template',
                    'new_item' => 'New Template',
                    'view_item' => 'View Template',
                    'search_items' => 'Search Templates',
                    'not_found' => 'No templates found',
                    'not_found_in_trash' => 'No templates found in trash'
                ),
                'public' => false,
                'show_ui' => true,
                'show_in_menu' => 'terpedia-settings', // Show under Terpedia menu
                'show_in_rest' => true, // Enable Gutenberg
                'supports' => array('title', 'editor', 'custom-fields'),
                'capability_type' => 'post',
                'hierarchical' => false,
                'rewrite' => false,
                'query_var' => false,
                'menu_icon' => 'dashicons-text-page'
            ));
        }
    }
    
    /**
     * Add template admin menus
     */
    public function add_template_admin_menus() {
        // Template CPTs now appear automatically under Terpedia menu
        // Only add the template guide submenu for each template type
        foreach ($this->template_cpts as $base_cpt => $config) {
            $template_cpt = $base_cpt . '_template';
            
            add_submenu_page(
                'edit.php?post_type=' . $template_cpt,
                'Template Guide',
                'Template Guide',
                'manage_options',
                $template_cpt . '_guide',
                array($this, 'render_template_guide')
            );
        }
    }
    
    /**
     * Add template meta boxes
     */
    public function add_template_meta_boxes() {
        foreach ($this->template_cpts as $base_cpt => $config) {
            $template_cpt = $base_cpt . '_template';
            
            add_meta_box(
                'template_settings',
                '⚙️ Template Settings',
                array($this, 'template_settings_callback'),
                $template_cpt,
                'side',
                'high'
            );
            
            add_meta_box(
                'template_schema_preview',
                '🔍 Generated Schema Preview',
                array($this, 'template_schema_callback'),
                $template_cpt,
                'normal',
                'low'
            );
            
            add_meta_box(
                'template_test',
                '🧪 Test Template',
                array($this, 'template_test_callback'),
                $template_cpt,
                'side',
                'default'
            );
        }
    }
    
    /**
     * Template settings meta box
     */
    public function template_settings_callback($post) {
        wp_nonce_field('template_settings_nonce', 'template_settings_nonce');
        
        $template_description = get_post_meta($post->ID, '_template_description', true);
        $template_category = get_post_meta($post->ID, '_template_category', true);
        $system_prompt = get_post_meta($post->ID, '_system_prompt', true);
        $is_active = get_post_meta($post->ID, '_template_active', true) !== '0';
        
        // Get base CPT from post type
        $base_cpt = str_replace('_template', '', $post->post_type);
        
        ?>
        <div class="template-settings">
            <style>
                .template-setting {
                    margin-bottom: 15px;
                }
                .template-setting label {
                    display: block;
                    font-weight: 600;
                    margin-bottom: 5px;
                }
                .template-setting input,
                .template-setting select,
                .template-setting textarea {
                    width: 100%;
                    padding: 6px 8px;
                }
                .template-setting textarea {
                    resize: vertical;
                    min-height: 80px;
                }
                .template-active-toggle {
                    display: flex;
                    align-items: center;
                    gap: 8px;
                }
            </style>
            
            <div class="template-setting">
                <label for="template_description">📝 Description</label>
                <textarea name="template_description" id="template_description" placeholder="Brief description of what this template generates..."><?php echo esc_textarea($template_description); ?></textarea>
                <small style="color: #666;">Explain what content this template creates and when to use it.</small>
            </div>
            
            <div class="template-setting">
                <label for="template_category">📁 Category</label>
                <input type="text" name="template_category" id="template_category" value="<?php echo esc_attr($template_category); ?>" placeholder="e.g., Research, Analysis, Summary">
                <small style="color: #666;">Optional category for organizing templates.</small>
            </div>
            
            <div class="template-setting">
                <label for="system_prompt">🤖 AI System Prompt</label>
                <textarea name="system_prompt" id="system_prompt" placeholder="You are an expert researcher specializing in..."><?php echo esc_textarea($system_prompt); ?></textarea>
                <small style="color: #666;">Custom system prompt for AI generation (optional - uses CPT default if empty).</small>
            </div>
            
            <div class="template-setting">
                <div class="template-active-toggle">
                    <input type="checkbox" name="template_active" id="template_active" value="1" <?php checked($is_active); ?>>
                    <label for="template_active">✅ Active (available in Templated Text block)</label>
                </div>
            </div>
            
            <div class="template-info" style="background: #f0f8ff; padding: 12px; border-radius: 4px; margin-top: 15px;">
                <strong>💡 Template Markup Guide:</strong>
                <ul style="margin: 8px 0; padding-left: 20px; font-size: 12px;">
                    <li><code>{{field_name}}</code> - Creates an input field</li>
                    <li><code>{{field_name:Field Label}}</code> - Field with custom label</li>
                    <li><code>{{field_name:Field Label:placeholder}}</code> - Field with placeholder</li>
                    <li><code>{{select_field:Label:option1,option2,option3}}</code> - Dropdown select</li>
                    <li><code>{{textarea_field:Label}}</code> - Multi-line text area</li>
                </ul>
                <small>Use the Template Editor block below to create content with these field markers.</small>
            </div>
        </div>
        <?php
    }
    
    /**
     * Template schema preview meta box
     */
    public function template_schema_callback($post) {
        ?>
        <div class="template-schema-preview">
            <style>
                .schema-display {
                    background: #f8f9fa;
                    border: 1px solid #dee2e6;
                    border-radius: 4px;
                    padding: 15px;
                    font-family: monospace;
                    font-size: 12px;
                    white-space: pre-wrap;
                    max-height: 300px;
                    overflow-y: auto;
                }
                .schema-empty {
                    color: #6c757d;
                    font-style: italic;
                    text-align: center;
                    padding: 40px;
                }
            </style>
            
            <div id="schemaPreview" class="schema-display">
                <div class="schema-empty">
                    Save the template to generate OpenRouter schema preview...
                </div>
            </div>
            
            <div style="margin-top: 10px;">
                <button type="button" class="button" id="refreshSchemaBtn">🔄 Refresh Schema</button>
                <small style="margin-left: 10px; color: #666;">Preview how this template maps to OpenRouter structured output</small>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('#refreshSchemaBtn').on('click', function() {
                refreshSchemaPreview();
            });
            
            // Auto-refresh on content change
            $(document).on('input', '#content, input[name^="template_"]', function() {
                clearTimeout(window.schemaRefreshTimeout);
                window.schemaRefreshTimeout = setTimeout(refreshSchemaPreview, 1000);
            });
            
            function refreshSchemaPreview() {
                var content = '';
                
                // Try to get content from Gutenberg
                if (typeof wp !== 'undefined' && wp.data && wp.data.select) {
                    try {
                        content = wp.data.select('core/editor').getEditedPostContent();
                    } catch (e) {
                        // Fall back to classic editor
                        content = $('#content').val() || '';
                    }
                } else {
                    content = $('#content').val() || '';
                }
                
                if (!content.trim()) {
                    $('#schemaPreview').html('<div class="schema-empty">No template content to parse...</div>');
                    return;
                }
                
                $.post(ajaxurl, {
                    action: 'parse_template_markup',
                    content: content,
                    post_id: <?php echo $post->ID; ?>,
                    nonce: '<?php echo wp_create_nonce('template_markup_nonce'); ?>'
                })
                .done(function(response) {
                    if (response.success) {
                        $('#schemaPreview').text(JSON.stringify(response.data.schema, null, 2));
                    } else {
                        $('#schemaPreview').html('<div class="schema-empty" style="color: #dc3545;">Error: ' + response.data + '</div>');
                    }
                })
                .fail(function() {
                    $('#schemaPreview').html('<div class="schema-empty" style="color: #dc3545;">Network error occurred</div>');
                });
            }
            
            // Initial load
            setTimeout(refreshSchemaPreview, 500);
        });
        </script>
        <?php
    }
    
    /**
     * Template test meta box
     */
    public function template_test_callback($post) {
        ?>
        <div class="template-test">
            <style>
                .test-section {
                    margin-bottom: 15px;
                }
                .test-fields {
                    background: #f8f9fa;
                    border: 1px solid #dee2e6;
                    border-radius: 4px;
                    padding: 15px;
                    margin: 10px 0;
                }
                .test-field {
                    margin-bottom: 10px;
                }
                .test-field label {
                    display: block;
                    font-weight: 600;
                    margin-bottom: 3px;
                    font-size: 12px;
                }
                .test-field input,
                .test-field select,
                .test-field textarea {
                    width: 100%;
                    padding: 4px 6px;
                    font-size: 12px;
                }
                .test-results {
                    background: white;
                    border: 1px solid #dee2e6;
                    border-radius: 4px;
                    padding: 10px;
                    max-height: 200px;
                    overflow-y: auto;
                    font-size: 12px;
                    display: none;
                }
            </style>
            
            <div class="test-section">
                <button type="button" class="button button-primary" id="testTemplateBtn" style="width: 100%;">
                    🧪 Test Template Generation
                </button>
            </div>
            
            <div id="testFields" class="test-fields" style="display: none;">
                <div style="font-weight: 600; margin-bottom: 10px;">Fill in test values:</div>
                <!-- Dynamic fields will be populated here -->
            </div>
            
            <div id="testResults" class="test-results">
                <!-- Generated content will appear here -->
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            var testFields = {};
            
            $('#testTemplateBtn').on('click', function() {
                var $btn = $(this);
                
                if ($('#testFields').is(':visible')) {
                    // Execute test
                    executeTemplateTest($btn);
                } else {
                    // Load test fields first
                    loadTestFields($btn);
                }
            });
            
            function loadTestFields($btn) {
                var content = getTemplateContent();
                
                $.post(ajaxurl, {
                    action: 'parse_template_markup',
                    content: content,
                    post_id: <?php echo $post->ID; ?>,
                    nonce: '<?php echo wp_create_nonce('template_markup_nonce'); ?>'
                })
                .done(function(response) {
                    if (response.success && response.data.fields) {
                        testFields = response.data.fields;
                        renderTestFields();
                        $('#testFields').slideDown();
                        $btn.text('▶️ Generate Test Content');
                    } else {
                        alert('Could not parse template fields. Make sure your template has field markers like {{field_name}}');
                    }
                });
            }
            
            function renderTestFields() {
                var $container = $('#testFields');
                var fieldsHtml = '<div style="font-weight: 600; margin-bottom: 10px;">Fill in test values:</div>';
                
                Object.keys(testFields).forEach(function(fieldName) {
                    var field = testFields[fieldName];
                    fieldsHtml += '<div class="test-field">';
                    fieldsHtml += '<label>' + (field.label || fieldName) + ':</label>';
                    
                    if (field.type === 'select' && field.options) {
                        fieldsHtml += '<select name="test_' + fieldName + '">';
                        field.options.forEach(function(option) {
                            fieldsHtml += '<option value="' + option + '">' + option + '</option>';
                        });
                        fieldsHtml += '</select>';
                    } else if (field.type === 'textarea') {
                        fieldsHtml += '<textarea name="test_' + fieldName + '" rows="2" placeholder="' + (field.placeholder || '') + '"></textarea>';
                    } else {
                        fieldsHtml += '<input type="text" name="test_' + fieldName + '" placeholder="' + (field.placeholder || '') + '">';
                    }
                    fieldsHtml += '</div>';
                });
                
                $container.html(fieldsHtml);
            }
            
            function executeTemplateTest($btn) {
                $btn.prop('disabled', true).text('🔄 Generating...');
                
                var fieldValues = {};
                $('#testFields input, #testFields select, #testFields textarea').each(function() {
                    var fieldName = $(this).attr('name').replace('test_', '');
                    fieldValues[fieldName] = $(this).val();
                });
                
                $.post(ajaxurl, {
                    action: 'test_template_generation',
                    template_id: <?php echo $post->ID; ?>,
                    field_values: fieldValues,
                    nonce: '<?php echo wp_create_nonce('template_test_nonce'); ?>'
                })
                .done(function(response) {
                    if (response.success) {
                        $('#testResults').html(response.data.content).slideDown();
                    } else {
                        $('#testResults').html('<div style="color: #dc3545;">Error: ' + response.data + '</div>').slideDown();
                    }
                })
                .fail(function() {
                    $('#testResults').html('<div style="color: #dc3545;">Network error occurred</div>').slideDown();
                })
                .always(function() {
                    $btn.prop('disabled', false).text('🧪 Test Template Generation');
                });
            }
            
            function getTemplateContent() {
                if (typeof wp !== 'undefined' && wp.data && wp.data.select) {
                    try {
                        return wp.data.select('core/editor').getEditedPostContent();
                    } catch (e) {
                        return $('#content').val() || '';
                    }
                }
                return $('#content').val() || '';
            }
        });
        </script>
        <?php
    }
    
    /**
     * Save template meta data
     */
    public function save_template_meta($post_id) {
        if (!isset($_POST['template_settings_nonce']) || 
            !wp_verify_nonce($_POST['template_settings_nonce'], 'template_settings_nonce')) {
            return;
        }
        
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Check if this is a template post type
        $post_type = get_post_type($post_id);
        if (!in_array(str_replace('_template', '', $post_type), array_keys($this->template_cpts))) {
            return;
        }
        
        // Save template meta
        $fields = array('template_description', 'template_category', 'system_prompt');
        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                update_post_meta($post_id, '_' . $field, sanitize_textarea_field($_POST[$field]));
            }
        }
        
        // Save active status
        $active = isset($_POST['template_active']) ? '1' : '0';
        update_post_meta($post_id, '_template_active', $active);
        
        // Parse template content and save schema
        $content = get_post_field('post_content', $post_id);
        $schema = $this->parse_template_content($content);
        update_post_meta($post_id, '_template_schema', $schema);
    }
    
    /**
     * Parse template markup to extract fields and generate schema
     */
    public function parse_template_markup() {
        check_ajax_referer('template_markup_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Access denied');
        }
        
        $content = wp_kses_post($_POST['content'] ?? '');
        $result = $this->parse_template_content($content);
        
        wp_send_json_success($result);
    }
    
    /**
     * Parse template content and extract field definitions
     */
    private function parse_template_content($content) {
        $fields = array();
        $schema_properties = array();
        $required_fields = array();
        
        // Find all field markers: {{field_name}}, {{field_name:Label}}, {{field_name:Label:placeholder}}, etc.
        preg_match_all('/\{\{([^}]+)\}\}/', $content, $matches);
        
        foreach ($matches[1] as $field_definition) {
            $parts = explode(':', $field_definition);
            $field_name = trim($parts[0]);
            $field_label = isset($parts[1]) ? trim($parts[1]) : ucwords(str_replace('_', ' ', $field_name));
            $field_placeholder = isset($parts[2]) ? trim($parts[2]) : '';
            
            // Determine field type based on name patterns and additional syntax
            $field_type = 'text'; // default
            if (strpos($field_name, 'select_') === 0 || (isset($parts[2]) && strpos($parts[2], ',') !== false)) {
                $field_type = 'select';
                $options = isset($parts[2]) ? array_map('trim', explode(',', $parts[2])) : array();
            } elseif (strpos($field_name, 'textarea_') === 0 || strpos($field_name, 'description') !== false) {
                $field_type = 'textarea';
            }
            
            $fields[$field_name] = array(
                'name' => $field_name,
                'label' => $field_label,
                'type' => $field_type,
                'placeholder' => $field_placeholder,
                'required' => true // All fields required by default
            );
            
            if (isset($options)) {
                $fields[$field_name]['options'] = $options;
            }
            
            // Build OpenRouter schema
            $schema_properties[$field_name] = array(
                'type' => 'string',
                'description' => $field_label . ($field_placeholder ? ' (' . $field_placeholder . ')' : '')
            );
            
            if ($field_type === 'select' && isset($options)) {
                $schema_properties[$field_name]['enum'] = $options;
            }
            
            $required_fields[] = $field_name;
        }
        
        // Generate complete OpenRouter structured output schema
        $schema = array(
            'type' => 'json_schema',
            'json_schema' => array(
                'name' => 'template_generated_content',
                'strict' => true,
                'schema' => array(
                    'type' => 'object',
                    'properties' => $schema_properties,
                    'required' => $required_fields,
                    'additionalProperties' => false
                )
            )
        );
        
        return array(
            'fields' => $fields,
            'schema' => $schema,
            'field_count' => count($fields)
        );
    }
    
    /**
     * Test template generation
     */
    public function test_template_generation() {
        check_ajax_referer('template_test_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Access denied');
        }
        
        $template_id = intval($_POST['template_id'] ?? 0);
        $field_values = $_POST['field_values'] ?? array();
        
        $template_post = get_post($template_id);
        if (!$template_post) {
            wp_send_json_error('Template not found');
        }
        
        // Get template content and replace field markers with values
        $content = $template_post->post_content;
        foreach ($field_values as $field_name => $field_value) {
            $content = preg_replace('/\{\{' . preg_quote($field_name) . '(?::[^}]*)?\}\}/', $field_value, $content);
        }
        
        // Generate AI content using OpenRouter
        $system_prompt = get_post_meta($template_id, '_system_prompt', true) ?: $this->get_default_system_prompt($template_post->post_type);
        $schema = get_post_meta($template_id, '_template_schema', true);
        
        if (!$schema) {
            wp_send_json_error('Template schema not found. Save the template first.');
        }
        
        $generated_content = $this->openrouter_api->make_api_request($system_prompt, "Generate content based on this template: " . $content, $schema['schema']);
        
        if (is_wp_error($generated_content)) {
            wp_send_json_error($generated_content->get_error_message());
        }
        
        // Format the response for display
        $formatted_content = '<div style="background: white; border: 1px solid #ddd; border-radius: 4px; padding: 15px;">';
        $formatted_content .= '<h4 style="margin: 0 0 10px 0; color: #ff1493;">Generated Content:</h4>';
        
        if (is_array($generated_content)) {
            foreach ($generated_content as $field_name => $field_content) {
                $formatted_content .= '<div style="margin-bottom: 15px;">';
                $formatted_content .= '<strong>' . ucwords(str_replace('_', ' ', $field_name)) . ':</strong><br>';
                $formatted_content .= '<div style="padding: 8px; background: #f8f9fa; border-radius: 3px; margin-top: 4px;">' . wp_kses_post($field_content) . '</div>';
                $formatted_content .= '</div>';
            }
        } else {
            $formatted_content .= wp_kses_post($generated_content);
        }
        
        $formatted_content .= '</div>';
        
        wp_send_json_success(array(
            'content' => $formatted_content,
            'raw_data' => $generated_content
        ));
    }
    
    /**
     * Get default system prompt for CPT
     */
    private function get_default_system_prompt($template_post_type) {
        $base_cpt = str_replace('_template', '', $template_post_type);
        
        $prompts = array(
            'terport' => 'You are an expert scientific researcher specializing in terpene research. Create accurate, well-researched content with proper citations and scientific rigor.',
            'terpedia_terproduct' => 'You are an expert product analyst specializing in cannabis and terpene products. Provide detailed, objective analysis with focus on quality and composition.',
            'terpedia_rx' => 'You are an expert formulation scientist specializing in terpene therapeutics. Provide precise, scientific guidance with safety considerations.'
        );
        
        return $prompts[$base_cpt] ?? 'You are an expert researcher. Create accurate, well-structured content based on current knowledge.';
    }
    
    /**
     * Render template guide page
     */
    public function render_template_guide() {
        $current_page = $_GET['page'] ?? '';
        $base_cpt = str_replace('_guide', '', $current_page);
        $cpt_name = $this->get_cpt_display_name($base_cpt);
        
        ?>
        <div class="wrap">
            <h1>📖 <?php echo $cpt_name; ?> Template Guide</h1>
            
            <div class="template-guide-content" style="max-width: 800px;">
                <div class="guide-section" style="background: white; padding: 20px; border-radius: 8px; margin: 20px 0; border: 1px solid #ddd;">
                    <h2>🎯 What are Templates?</h2>
                    <p>Templates are reusable content patterns that use AI to generate customized content. They combine:</p>
                    <ul>
                        <li><strong>Rich Text Content</strong> - Your written template with placeholders</li>
                        <li><strong>Field Markers</strong> - Special codes like <code>{{field_name}}</code> that become input fields</li>
                        <li><strong>AI Generation</strong> - OpenRouter fills in the placeholders with intelligent content</li>
                    </ul>
                </div>
                
                <div class="guide-section" style="background: white; padding: 20px; border-radius: 8px; margin: 20px 0; border: 1px solid #ddd;">
                    <h2>✍️ Template Markup Syntax</h2>
                    <div style="background: #f8f9fa; padding: 15px; border-radius: 4px; font-family: monospace;">
                        <div style="margin-bottom: 10px;"><code>{{compound}}</code> → Creates a text input field named "Compound"</div>
                        <div style="margin-bottom: 10px;"><code>{{dosage:Recommended Dosage}}</code> → Text field with custom label</div>
                        <div style="margin-bottom: 10px;"><code>{{method:Administration Method:e.g., oral, topical}}</code> → Text field with placeholder</div>
                        <div style="margin-bottom: 10px;"><code>{{select_category:Category:option1,option2,option3}}</code> → Dropdown select</div>
                        <div><code>{{textarea_description:Detailed Description}}</code> → Multi-line text area</div>
                    </div>
                </div>
                
                <div class="guide-section" style="background: white; padding: 20px; border-radius: 8px; margin: 20px 0; border: 1px solid #ddd;">
                    <h2>📝 Example Template</h2>
                    <p>Here's a sample <?php echo $cpt_name; ?> template:</p>
                    <div style="background: #f8f9fa; padding: 15px; border-radius: 4px; border-left: 4px solid #ff69b4;">
                        <h4>{{compound:Compound Name}} Research Summary</h4>
                        <p>This analysis covers the therapeutic applications of {{compound}} for {{condition:Medical Condition}}.</p>
                        
                        <h5>Research Overview</h5>
                        <p>Based on {{study_period:Study Period:Last 5 years}} of research, {{compound}} shows promise for {{therapeutic_area:Therapeutic Area}}.</p>
                        
                        <h5>Mechanism of Action</h5>
                        <p>{{compound}} primarily works through {{select_mechanism:Primary Mechanism:CB1 receptors,CB2 receptors,Serotonin system,GABA system}} to produce {{expected_effects:Expected Effects}}.</p>
                        
                        <h5>Clinical Evidence</h5>
                        <p>{{textarea_evidence:Clinical Evidence Summary}}</p>
                    </div>
                </div>
                
                <div class="guide-section" style="background: white; padding: 20px; border-radius: 8px; margin: 20px 0; border: 1px solid #ddd;">
                    <h2>🚀 How to Create Templates</h2>
                    <ol>
                        <li><strong>Add New Template</strong> - Click "Add New Template" in the menu</li>
                        <li><strong>Enter Title & Settings</strong> - Give your template a descriptive title</li>
                        <li><strong>Write Template Content</strong> - Use the Template Editor block with field markers</li>
                        <li><strong>Test Template</strong> - Use the test panel to verify it works correctly</li>
                        <li><strong>Activate Template</strong> - Check the "Active" checkbox to make it available</li>
                        <li><strong>Use in Content</strong> - Add "Templated Text" block in your posts to use the template</li>
                    </ol>
                </div>
                
                <div class="guide-section" style="background: #e8f5e8; padding: 20px; border-radius: 8px; margin: 20px 0; border: 1px solid #28a745;">
                    <h2>💡 Best Practices</h2>
                    <ul>
                        <li><strong>Descriptive Field Names</strong> - Use clear, descriptive names like <code>{{primary_compound}}</code> instead of <code>{{x}}</code></li>
                        <li><strong>Helpful Labels</strong> - Provide clear labels: <code>{{dosage:Recommended Dosage Range}}</code></li>
                        <li><strong>Smart Defaults</strong> - Use select fields for standardized options</li>
                        <li><strong>Test Thoroughly</strong> - Always test with realistic data before activating</li>
                        <li><strong>Custom Prompts</strong> - Add specific system prompts for specialized content</li>
                    </ul>
                </div>
                
                <div style="text-align: center; margin: 30px 0;">
                    <a href="post-new.php?post_type=<?php echo $base_cpt; ?>_template" class="button button-primary button-hero" style="background: #ff69b4; border-color: #ff69b4;">
                        ✨ Create Your First <?php echo $cpt_name; ?> Template
                    </a>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Get display name for CPT
     */
    private function get_cpt_display_name($cpt) {
        $names = array(
            'terport' => 'Terport',
            'terpedia_terproduct' => 'Terproduct', 
            'terpedia_rx' => 'Rx Formulation'
        );
        
        return $names[$cpt] ?? ucfirst($cpt);
    }
}

// Initialize the CPT template management system
new Terpedia_CPT_Template_Management();