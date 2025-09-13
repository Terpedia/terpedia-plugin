<?php
/**
 * Field-Based Template System for Terports
 * 
 * Provides structured templates with rich text fields that generate
 * structured data requests to OpenRouter for specific content types
 * 
 * @package Terpedia
 */

if (!defined('ABSPATH')) {
    exit;
}

class Terpedia_Field_Based_Template_System {
    
    private $openrouter_api;
    
    public function __construct() {
        add_action('add_meta_boxes', array($this, 'add_field_template_meta_boxes'));
        add_action('save_post', array($this, 'save_field_template_meta'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_field_template_scripts'));
        
        // AJAX handlers
        add_action('wp_ajax_terpedia_generate_structured_content', array($this, 'ajax_generate_structured_content'));
        add_action('wp_ajax_terpedia_get_template_fields', array($this, 'ajax_get_template_fields'));
        
        // Initialize OpenRouter API
        $this->openrouter_api = new Terpedia_OpenRouter_API();
    }
    
    /**
     * Add field-based template meta boxes
     */
    public function add_field_template_meta_boxes() {
        add_meta_box(
            'terpedia_field_template_selector',
            'Template & Content Generation',
            array($this, 'field_template_selector_callback'),
            'terport',
            'normal',
            'high'
        );
        
        add_meta_box(
            'terpedia_structured_fields',
            'Structured Content Fields',
            array($this, 'structured_fields_callback'),
            'terport',
            'normal',
            'high'
        );
        
        add_meta_box(
            'terpedia_terport_visibility',
            'Visibility Settings',
            array($this, 'visibility_settings_callback'),
            'terport',
            'side',
            'high'
        );
    }
    
    /**
     * Template selector and generation interface
     */
    public function field_template_selector_callback($post) {
        wp_nonce_field('terpedia_field_template_nonce', 'terpedia_field_template_nonce');
        
        $selected_template = get_post_meta($post->ID, '_terpedia_field_template_type', true);
        $generation_prompt = get_post_meta($post->ID, '_terpedia_generation_prompt', true);
        
        ?>
        <div class="field-template-system">
            <style>
                .field-template-system {
                    background: #f8f9fa;
                    border: 1px solid #dee2e6;
                    border-radius: 8px;
                    padding: 20px;
                }
                
                .template-selector {
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
                    gap: 15px;
                    margin: 20px 0;
                }
                
                .template-option {
                    background: white;
                    border: 2px solid #e9ecef;
                    border-radius: 8px;
                    padding: 15px;
                    cursor: pointer;
                    transition: all 0.2s;
                }
                
                .template-option:hover {
                    border-color: #ff69b4;
                    box-shadow: 0 2px 8px rgba(255, 105, 180, 0.1);
                }
                
                .template-option.selected {
                    border-color: #ff69b4;
                    background: #fff5f8;
                }
                
                .template-option h4 {
                    margin: 0 0 8px 0;
                    color: #2c5aa0;
                    font-size: 16px;
                }
                
                .template-option p {
                    margin: 0;
                    color: #6c757d;
                    font-size: 14px;
                }
                
                .generation-section {
                    margin-top: 20px;
                    padding-top: 20px;
                    border-top: 1px solid #dee2e6;
                }
                
                .generation-prompt {
                    width: 100%;
                    min-height: 100px;
                    padding: 12px;
                    border: 1px solid #ced4da;
                    border-radius: 4px;
                    font-family: inherit;
                }
                
                .generate-btn {
                    background: #ff69b4;
                    border: none;
                    color: white;
                    padding: 12px 24px;
                    border-radius: 4px;
                    font-weight: 600;
                    cursor: pointer;
                    margin-top: 10px;
                }
                
                .generate-btn:hover {
                    background: #ff1493;
                }
                
                .generate-btn:disabled {
                    background: #6c757d;
                    cursor: not-allowed;
                }
            </style>
            
            <h3>📄 Select Template Type</h3>
            
            <div class="template-selector">
                <div class="template-option <?php echo $selected_template === 'literature_review' ? 'selected' : ''; ?>" 
                     data-template="literature_review">
                    <h4>📚 Literature Review</h4>
                    <p>Comprehensive analysis of scientific studies and research papers on a specific terpene or topic</p>
                </div>
                
                <div class="template-option <?php echo $selected_template === 'product_evaluation' ? 'selected' : ''; ?>" 
                     data-template="product_evaluation">
                    <h4>🔬 Product Evaluation</h4>
                    <p>Detailed analysis of cannabis products including terpene profiles, lab results, and quality assessment</p>
                </div>
                
                <div class="template-option <?php echo $selected_template === 'product_recommendations' ? 'selected' : ''; ?>" 
                     data-template="product_recommendations">
                    <h4>💡 Product Recommendations</h4>
                    <p>Curated recommendations for products based on specific therapeutic goals or terpene preferences</p>
                </div>
            </div>
            
            <input type="hidden" id="selected_template_type" name="field_template_type" value="<?php echo esc_attr($selected_template); ?>">
            
            <div class="generation-section">
                <h4>🤖 AI Content Generation</h4>
                <p>Provide specific details about what you want to generate:</p>
                <textarea class="generation-prompt" name="generation_prompt" placeholder="Example: Generate a literature review on limonene's anti-anxiety effects, focusing on recent clinical studies from 2020-2025"><?php echo esc_textarea($generation_prompt); ?></textarea>
                <button type="button" class="generate-btn" id="generate-structured-content">
                    Generate Structured Content
                </button>
                <div id="generation-status" style="margin-top: 10px;"></div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Template selection
            $('.template-option').on('click', function() {
                $('.template-option').removeClass('selected');
                $(this).addClass('selected');
                $('#selected_template_type').val($(this).data('template'));
                loadTemplateFields($(this).data('template'));
            });
            
            // Generate content
            $('#generate-structured-content').on('click', function() {
                generateStructuredContent();
            });
            
            // Load initial template fields if template is selected
            if ($('#selected_template_type').val()) {
                loadTemplateFields($('#selected_template_type').val());
            }
        });
        
        function loadTemplateFields(templateType) {
            if (!templateType) return;
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'terpedia_get_template_fields',
                    template_type: templateType,
                    nonce: '<?php echo wp_create_nonce("terpedia_template_fields"); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        $('#structured-fields-container').html(response.data.fields_html);
                        initializeRichTextEditors();
                    }
                }
            });
        }
        
        function generateStructuredContent() {
            var templateType = $('#selected_template_type').val();
            var prompt = $('[name="generation_prompt"]').val();
            
            if (!templateType || !prompt.trim()) {
                alert('Please select a template type and provide generation details.');
                return;
            }
            
            var $btn = $('#generate-structured-content');
            var $status = $('#generation-status');
            
            $btn.prop('disabled', true).text('Generating...');
            $status.html('<span style="color: #007cba;">🤖 Generating structured content...</span>');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'terpedia_generate_structured_content',
                    template_type: templateType,
                    prompt: prompt,
                    post_id: <?php echo $post->ID; ?>,
                    nonce: '<?php echo wp_create_nonce("terpedia_structured_content"); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        populateStructuredFields(response.data.content);
                        $status.html('<span style="color: #46b450;">✅ Content generated successfully!</span>');
                    } else {
                        $status.html('<span style="color: #dc3232;">❌ Error: ' + response.data + '</span>');
                    }
                },
                error: function() {
                    $status.html('<span style="color: #dc3232;">❌ Network error occurred</span>');
                },
                complete: function() {
                    $btn.prop('disabled', false).text('Generate Structured Content');
                }
            });
        }
        
        function populateStructuredFields(content) {
            // Populate each field with generated content
            for (var fieldName in content) {
                var field = $('[name="structured_field_' + fieldName + '"]');
                if (field.length) {
                    if (field.hasClass('wp-editor-area')) {
                        // Handle WordPress editor
                        var editorId = field.attr('id');
                        if (typeof tinyMCE !== 'undefined' && tinyMCE.get(editorId)) {
                            tinyMCE.get(editorId).setContent(content[fieldName]);
                        } else {
                            field.val(content[fieldName]);
                        }
                    } else {
                        field.val(content[fieldName]);
                    }
                }
            }
        }
        
        function initializeRichTextEditors() {
            // Initialize WordPress rich text editors for generated fields
            $('.structured-field-editor').each(function() {
                var editorId = $(this).attr('id');
                if (typeof wp !== 'undefined' && wp.editor) {
                    wp.editor.initialize(editorId, {
                        tinymce: {
                            wpautop: true,
                            plugins: 'charmap colorpicker hr lists paste tabfocus textcolor fullscreen wordpress wpautoresize wpeditimage wpemoji wpgallery wplink wptextpattern',
                            toolbar1: 'bold italic underline | bullist numlist | link unlink | wp_adv',
                            toolbar2: 'strikethrough hr | forecolor backcolor | pastetext removeformat | charmap | outdent indent | undo redo | wp_help'
                        }
                    });
                }
            });
        }
        </script>
        <?php
    }
    
    /**
     * Visibility settings callback
     */
    public function visibility_settings_callback($post) {
        wp_nonce_field('terpedia_visibility_nonce', 'terpedia_visibility_nonce');
        
        $visibility = get_post_meta($post->ID, '_terport_visibility', true);
        if (empty($visibility)) {
            $visibility = 'private';
        }
        
        ?>
        <div class="terport-visibility-settings">
            <style>
                .visibility-option {
                    margin-bottom: 12px;
                    padding: 10px;
                    border: 1px solid #ddd;
                    border-radius: 4px;
                    background: #f9f9f9;
                }
                
                .visibility-option.selected {
                    border-color: #ff69b4;
                    background: #fff5f8;
                }
                
                .visibility-option label {
                    display: flex;
                    align-items: center;
                    gap: 8px;
                    cursor: pointer;
                    font-weight: 600;
                }
                
                .visibility-description {
                    margin-top: 4px;
                    font-size: 12px;
                    color: #6c757d;
                }
            </style>
            
            <div class="visibility-option <?php echo $visibility === 'public' ? 'selected' : ''; ?>">
                <label>
                    <input type="radio" name="terport_visibility" value="public" <?php checked($visibility, 'public'); ?>>
                    🌍 Public
                </label>
                <div class="visibility-description">
                    Visible to everyone, appears in archives, searchable
                </div>
            </div>
            
            <div class="visibility-option <?php echo $visibility === 'private' ? 'selected' : ''; ?>">
                <label>
                    <input type="radio" name="terport_visibility" value="private" <?php checked($visibility, 'private'); ?>>
                    🔒 Private
                </label>
                <div class="visibility-description">
                    Only visible to editors and admins
                </div>
            </div>
            
            <?php if ($visibility === 'public'): ?>
                <div style="background: #d1ecf1; border: 1px solid #bee5eb; padding: 10px; border-radius: 4px; margin-top: 10px;">
                    <small>
                        <strong>💬 Chat URL:</strong><br>
                        <code><?php echo home_url("/terport/{$post->ID}/chat/"); ?></code>
                    </small>
                </div>
            <?php endif; ?>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('input[name="terport_visibility"]').on('change', function() {
                $('.visibility-option').removeClass('selected');
                $(this).closest('.visibility-option').addClass('selected');
            });
        });
        </script>
        <?php
    }
    
    /**
     * Structured fields interface
     */
    public function structured_fields_callback($post) {
        $template_type = get_post_meta($post->ID, '_terpedia_field_template_type', true);
        
        ?>
        <div id="structured-fields-container">
            <?php
            if ($template_type) {
                echo $this->get_template_fields_html($template_type, $post->ID);
            } else {
                echo '<p style="color: #6c757d; text-align: center; padding: 40px;">Select a template type above to see structured fields</p>';
            }
            ?>
        </div>
        <?php
    }
    
    /**
     * Get template fields HTML for a specific template type
     */
    public function get_template_fields_html($template_type, $post_id = 0) {
        $fields = $this->get_template_field_definitions($template_type);
        $html = '<div class="structured-fields-wrapper">';
        
        foreach ($fields as $field_name => $field_config) {
            $field_value = $post_id ? get_post_meta($post_id, '_structured_field_' . $field_name, true) : '';
            
            $html .= '<div class="structured-field-group" style="margin-bottom: 25px;">';
            $html .= '<label style="display: block; font-weight: 600; margin-bottom: 8px; color: #2c5aa0;">';
            $html .= esc_html($field_config['label']);
            if (!empty($field_config['required'])) {
                $html .= ' <span style="color: #dc3232;">*</span>';
            }
            $html .= '</label>';
            
            if (!empty($field_config['description'])) {
                $html .= '<p style="margin: 0 0 8px 0; color: #6c757d; font-size: 13px;">' . esc_html($field_config['description']) . '</p>';
            }
            
            if ($field_config['type'] === 'rich_text') {
                $editor_id = 'structured_field_' . $field_name;
                ob_start();
                wp_editor($field_value, $editor_id, array(
                    'textarea_name' => 'structured_field_' . $field_name,
                    'textarea_rows' => 8,
                    'teeny' => false,
                    'media_buttons' => true,
                    'tinymce' => array(
                        'resize' => true,
                        'wp_autoresize_on' => true
                    )
                ));
                $html .= ob_get_clean();
            } elseif ($field_config['type'] === 'textarea') {
                $html .= '<textarea name="structured_field_' . $field_name . '" rows="4" style="width: 100%; padding: 8px; border: 1px solid #ced4da; border-radius: 4px;">' . esc_textarea($field_value) . '</textarea>';
            } else {
                $html .= '<input type="text" name="structured_field_' . $field_name . '" value="' . esc_attr($field_value) . '" style="width: 100%; padding: 8px; border: 1px solid #ced4da; border-radius: 4px;">';
            }
            
            $html .= '</div>';
        }
        
        $html .= '</div>';
        return $html;
    }
    
    /**
     * Get field definitions for each template type
     */
    public function get_template_field_definitions($template_type) {
        $definitions = array(
            'literature_review' => array(
                'executive_summary' => array(
                    'label' => 'Executive Summary',
                    'type' => 'rich_text',
                    'description' => 'Brief overview of key findings and conclusions',
                    'required' => true
                ),
                'research_methodology' => array(
                    'label' => 'Research Methodology',
                    'type' => 'rich_text',
                    'description' => 'Search criteria, databases used, and selection process'
                ),
                'key_findings' => array(
                    'label' => 'Key Findings',
                    'type' => 'rich_text',
                    'description' => 'Main research results and scientific evidence',
                    'required' => true
                ),
                'clinical_evidence' => array(
                    'label' => 'Clinical Evidence',
                    'type' => 'rich_text',
                    'description' => 'Human studies, clinical trials, and medical applications'
                ),
                'mechanism_of_action' => array(
                    'label' => 'Mechanism of Action',
                    'type' => 'rich_text',
                    'description' => 'How the compounds work at molecular and cellular levels'
                ),
                'future_research' => array(
                    'label' => 'Future Research Directions',
                    'type' => 'rich_text',
                    'description' => 'Gaps in knowledge and recommended research priorities'
                ),
                'references' => array(
                    'label' => 'References & Citations',
                    'type' => 'rich_text',
                    'description' => 'Formatted citations for all referenced studies'
                )
            ),
            
            'product_evaluation' => array(
                'product_overview' => array(
                    'label' => 'Product Overview',
                    'type' => 'rich_text',
                    'description' => 'Basic product information, brand, and category',
                    'required' => true
                ),
                'terpene_profile' => array(
                    'label' => 'Terpene Profile Analysis',
                    'type' => 'rich_text',
                    'description' => 'Detailed breakdown of terpene concentrations and ratios',
                    'required' => true
                ),
                'lab_results' => array(
                    'label' => 'Laboratory Results',
                    'type' => 'rich_text',
                    'description' => 'COA analysis, potency, contaminants, and quality metrics'
                ),
                'sensory_evaluation' => array(
                    'label' => 'Sensory Evaluation',
                    'type' => 'rich_text',
                    'description' => 'Aroma, flavor, appearance, and texture assessment'
                ),
                'therapeutic_potential' => array(
                    'label' => 'Therapeutic Potential',
                    'type' => 'rich_text',
                    'description' => 'Expected effects based on terpene profile and ratios'
                ),
                'quality_score' => array(
                    'label' => 'Quality Score & Rating',
                    'type' => 'textarea',
                    'description' => 'Overall quality rating with justification'
                ),
                'recommendations' => array(
                    'label' => 'Usage Recommendations',
                    'type' => 'rich_text',
                    'description' => 'Optimal use cases, dosing, and user guidance'
                )
            ),
            
            'product_recommendations' => array(
                'recommendation_criteria' => array(
                    'label' => 'Recommendation Criteria',
                    'type' => 'rich_text',
                    'description' => 'Target effects, therapeutic goals, or user preferences',
                    'required' => true
                ),
                'top_recommendations' => array(
                    'label' => 'Top Product Recommendations',
                    'type' => 'rich_text',
                    'description' => 'Curated list of recommended products with rationale',
                    'required' => true
                ),
                'terpene_rationale' => array(
                    'label' => 'Terpene-Based Rationale',
                    'type' => 'rich_text',
                    'description' => 'Scientific basis for recommendations based on terpene profiles'
                ),
                'usage_guidelines' => array(
                    'label' => 'Usage Guidelines',
                    'type' => 'rich_text',
                    'description' => 'Dosing recommendations, timing, and consumption methods'
                ),
                'alternative_options' => array(
                    'label' => 'Alternative Options',
                    'type' => 'rich_text',
                    'description' => 'Secondary recommendations and backup choices'
                ),
                'precautions' => array(
                    'label' => 'Precautions & Considerations',
                    'type' => 'rich_text',
                    'description' => 'Safety considerations, contraindications, and warnings'
                ),
                'price_analysis' => array(
                    'label' => 'Value & Price Analysis',
                    'type' => 'textarea',
                    'description' => 'Cost-effectiveness and value proposition of recommendations'
                )
            )
        );
        
        return $definitions[$template_type] ?? array();
    }
    
    /**
     * Save field template meta data
     */
    public function save_field_template_meta($post_id) {
        if (!isset($_POST['terpedia_field_template_nonce']) || 
            !wp_verify_nonce($_POST['terpedia_field_template_nonce'], 'terpedia_field_template_nonce')) {
            return;
        }
        
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Save template type
        if (isset($_POST['field_template_type'])) {
            update_post_meta($post_id, '_terpedia_field_template_type', sanitize_text_field($_POST['field_template_type']));
        }
        
        // Save generation prompt
        if (isset($_POST['generation_prompt'])) {
            update_post_meta($post_id, '_terpedia_generation_prompt', wp_kses_post($_POST['generation_prompt']));
        }
        
        // Save visibility settings
        if (isset($_POST['terport_visibility'])) {
            update_post_meta($post_id, '_terport_visibility', sanitize_text_field($_POST['terport_visibility']));
        }
        
        // Save structured field data
        $template_type = sanitize_text_field($_POST['field_template_type'] ?? '');
        if ($template_type) {
            $field_definitions = $this->get_template_field_definitions($template_type);
            
            foreach ($field_definitions as $field_name => $field_config) {
                $field_key = 'structured_field_' . $field_name;
                if (isset($_POST[$field_key])) {
                    $field_value = $field_config['type'] === 'rich_text' 
                        ? wp_kses_post($_POST[$field_key])
                        : sanitize_textarea_field($_POST[$field_key]);
                    update_post_meta($post_id, '_' . $field_key, $field_value);
                }
            }
        }
    }
    
    /**
     * AJAX: Get template fields for a specific template type
     */
    public function ajax_get_template_fields() {
        check_ajax_referer('terpedia_template_fields', 'nonce');
        
        $template_type = sanitize_text_field($_POST['template_type'] ?? '');
        
        if (empty($template_type)) {
            wp_send_json_error('Invalid template type');
        }
        
        $fields_html = $this->get_template_fields_html($template_type);
        
        wp_send_json_success(array(
            'fields_html' => $fields_html,
            'template_type' => $template_type
        ));
    }
    
    /**
     * AJAX: Generate structured content using OpenRouter
     */
    public function ajax_generate_structured_content() {
        check_ajax_referer('terpedia_structured_content', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $template_type = sanitize_text_field($_POST['template_type'] ?? '');
        $prompt = sanitize_textarea_field($_POST['prompt'] ?? '');
        $post_id = intval($_POST['post_id'] ?? 0);
        
        if (empty($template_type) || empty($prompt)) {
            wp_send_json_error('Missing template type or prompt');
        }
        
        // Generate structured content using OpenRouter
        $content = $this->generate_structured_content($template_type, $prompt);
        
        if (is_wp_error($content)) {
            wp_send_json_error($content->get_error_message());
        }
        
        wp_send_json_success(array(
            'content' => $content,
            'template_type' => $template_type
        ));
    }
    
    /**
     * Generate structured content using OpenRouter API
     */
    private function generate_structured_content($template_type, $prompt) {
        $field_definitions = $this->get_template_field_definitions($template_type);
        
        if (empty($field_definitions)) {
            return new WP_Error('invalid_template', 'Unknown template type');
        }
        
        // Build OpenRouter structured output schema
        $schema_properties = array();
        $required_fields = array();
        
        foreach ($field_definitions as $field_name => $field_config) {
            $schema_properties[$field_name] = array(
                'type' => 'string',
                'description' => $field_config['description'] ?? "Content for {$field_config['label']}"
            );
            
            if (!empty($field_config['required'])) {
                $required_fields[] = $field_name;
            }
        }
        
        $response_format = array(
            'type' => 'json_schema',
            'json_schema' => array(
                'name' => 'terport_structured_content',
                'strict' => true,
                'schema' => array(
                    'type' => 'object',
                    'properties' => $schema_properties,
                    'required' => $required_fields,
                    'additionalProperties' => false
                )
            )
        );
        
        // Create system prompt based on template type
        $system_prompt = $this->get_system_prompt_for_template($template_type);
        
        // Make API request
        $response = $this->openrouter_api->make_api_request($system_prompt, $prompt, $response_format);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        return $response;
    }
    
    /**
     * Get system prompt for each template type
     */
    private function get_system_prompt_for_template($template_type) {
        $prompts = array(
            'literature_review' => "You are an expert scientific researcher specializing in terpene research. Generate comprehensive, well-structured literature reviews with proper scientific methodology and evidence-based conclusions. Focus on peer-reviewed research, clinical studies, and molecular mechanisms.",
            
            'product_evaluation' => "You are an expert cannabis product analyst with deep knowledge of terpene profiles, quality assessment, and therapeutic applications. Provide detailed, objective product evaluations based on scientific analysis of terpene ratios, lab results, and quality indicators.",
            
            'product_recommendations' => "You are a trusted cannabis consultant with expertise in terpene therapeutics and product matching. Provide personalized, science-based product recommendations with clear rationale based on terpene profiles, therapeutic goals, and user needs."
        );
        
        return $prompts[$template_type] ?? "You are an expert terpene researcher. Provide accurate, well-structured content based on scientific evidence.";
    }
    
    /**
     * Enqueue field template scripts and styles
     */
    public function enqueue_field_template_scripts($hook) {
        global $post;
        
        if ($hook == 'post-new.php' || $hook == 'post.php') {
            if ($post && $post->post_type === 'terport') {
                wp_enqueue_script('wp-tinymce');
                wp_enqueue_editor();
            }
        }
    }
}

// Initialize the field-based template system
new Terpedia_Field_Based_Template_System();