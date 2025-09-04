<?php
/**
 * Enhanced Terport Editor with AI Integration
 * 
 * Provides advanced editing capabilities for Terports including:
 * - Type selection and description fields
 * - AI-powered content generation
 * - Multimodal image generation
 * - Template system with structured outputs
 * 
 * @package Terpedia
 */

if (!defined('ABSPATH')) {
    exit;
}

class Terpedia_Enhanced_Terport_Editor {
    
    private $openrouter_api;
    
    public function __construct() {
        add_action('add_meta_boxes', array($this, 'add_terport_meta_boxes'));
        add_action('save_post', array($this, 'save_terport_meta'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // AJAX handlers
        add_action('wp_ajax_terpedia_generate_terport_type', array($this, 'ajax_generate_terport_type'));
        add_action('wp_ajax_terpedia_generate_terport_content', array($this, 'ajax_generate_terport_content'));
        add_action('wp_ajax_terpedia_generate_terport_image', array($this, 'ajax_generate_terport_image'));
        add_action('wp_ajax_terpedia_get_terport_templates', array($this, 'ajax_get_terport_templates'));
        
        // Initialize OpenRouter API
        $this->openrouter_api = new Terpedia_OpenRouter_API();
    }
    
    /**
     * Add meta boxes to Terport editor
     */
    public function add_terport_meta_boxes() {
        add_meta_box(
            'terpedia_terport_ai_fields',
            'AI Terport Configuration',
            array($this, 'terport_ai_fields_callback'),
            'terport',
            'normal',
            'high'
        );
        
        add_meta_box(
            'terpedia_terport_template',
            'Content Template',
            array($this, 'terport_template_callback'),
            'terport',
            'side',
            'high'
        );
    }
    
    /**
     * AI Terport fields meta box callback
     */
    public function terport_ai_fields_callback($post) {
        wp_nonce_field('terpedia_terport_nonce', 'terpedia_terport_nonce');
        
        $terport_type = get_post_meta($post->ID, '_terpedia_terport_type', true);
        $terport_description = get_post_meta($post->ID, '_terpedia_terport_description', true);
        $terport_template_id = get_post_meta($post->ID, '_terpedia_terport_template_id', true);
        $terport_generation_prompt = get_post_meta($post->ID, '_terpedia_terport_generation_prompt', true);
        
        // Get available templates
        $templates = $this->get_terport_templates();
        
        ?>
        <div class="terpedia-terport-editor">
            <style>
                .terpedia-terport-editor {
                    max-width: 100%;
                    margin: 20px 0;
                }
                
                .terport-ai-section {
                    background: #f8f9fa;
                    border: 1px solid #e9ecef;
                    border-radius: 8px;
                    padding: 20px;
                    margin-bottom: 20px;
                }
                
                .terport-ai-section h3 {
                    margin: 0 0 15px 0;
                    color: #2c5aa0;
                    font-size: 16px;
                    font-weight: 600;
                }
                
                .terport-field-row {
                    display: flex;
                    gap: 15px;
                    margin-bottom: 15px;
                    align-items: flex-end;
                }
                
                .terport-field {
                    flex: 1;
                }
                
                .terport-field label {
                    display: block;
                    font-weight: 600;
                    margin-bottom: 5px;
                    color: #495057;
                }
                
                .terport-field input,
                .terport-field select,
                .terport-field textarea {
                    width: 100%;
                    padding: 8px 12px;
                    border: 1px solid #ced4da;
                    border-radius: 4px;
                    font-size: 14px;
                }
                
                .terport-field textarea {
                    min-height: 80px;
                    resize: vertical;
                }
                
                .terport-actions {
                    display: flex;
                    gap: 10px;
                    margin-top: 15px;
                }
                
                .terport-btn {
                    padding: 10px 20px;
                    border: none;
                    border-radius: 4px;
                    font-size: 14px;
                    font-weight: 600;
                    cursor: pointer;
                    transition: all 0.2s;
                }
                
                .terport-btn-primary {
                    background: #007cba;
                    color: white;
                }
                
                .terport-btn-primary:hover {
                    background: #005a87;
                }
                
                .terport-btn-secondary {
                    background: #6c757d;
                    color: white;
                }
                
                .terport-btn-secondary:hover {
                    background: #545b62;
                }
                
                .terport-btn-success {
                    background: #28a745;
                    color: white;
                }
                
                .terport-btn-success:hover {
                    background: #218838;
                }
                
                .terport-btn:disabled {
                    opacity: 0.6;
                    cursor: not-allowed;
                }
                
                .terport-status {
                    margin-top: 10px;
                    padding: 10px;
                    border-radius: 4px;
                    display: none;
                }
                
                .terport-status.loading {
                    background: #d1ecf1;
                    border: 1px solid #bee5eb;
                    color: #0c5460;
                }
                
                .terport-status.success {
                    background: #d4edda;
                    border: 1px solid #c3e6cb;
                    color: #155724;
                }
                
                .terport-status.error {
                    background: #f8d7da;
                    border: 1px solid #f5c6cb;
                    color: #721c24;
                }
                
                .terport-image-preview {
                    margin-top: 15px;
                    text-align: center;
                }
                
                .terport-image-preview img {
                    max-width: 300px;
                    max-height: 200px;
                    border: 1px solid #ddd;
                    border-radius: 4px;
                }
                
                .terport-template-info {
                    background: #e7f3ff;
                    border: 1px solid #b3d9ff;
                    border-radius: 4px;
                    padding: 10px;
                    margin-top: 10px;
                    font-size: 13px;
                    color: #0066cc;
                }
            </style>
            
            <div class="terport-ai-section">
                <h3>🤖 AI Terport Configuration</h3>
                
                <div class="terport-field-row">
                    <div class="terport-field">
                        <label for="terpedia_terport_type">Terport Type</label>
                        <select id="terpedia_terport_type" name="terpedia_terport_type">
                            <option value="">-- Select Type --</option>
                            <option value="research_analysis" <?php selected($terport_type, 'research_analysis'); ?>>Research Analysis</option>
                            <option value="compound_profile" <?php selected($terport_type, 'compound_profile'); ?>>Compound Profile</option>
                            <option value="clinical_study" <?php selected($terport_type, 'clinical_study'); ?>>Clinical Study</option>
                            <option value="market_analysis" <?php selected($terport_type, 'market_analysis'); ?>>Market Analysis</option>
                            <option value="regulatory_update" <?php selected($terport_type, 'regulatory_update'); ?>>Regulatory Update</option>
                            <option value="industry_news" <?php selected($terport_type, 'industry_news'); ?>>Industry News</option>
                        </select>
                    </div>
                    <div class="terport-field">
                        <button type="button" id="generate_type_btn" class="terport-btn terport-btn-secondary">
                            🎯 Auto-Detect Type
                        </button>
                    </div>
                </div>
                
                <div class="terport-field-row">
                    <div class="terport-field">
                        <label for="terpedia_terport_description">Description</label>
                        <textarea id="terpedia_terport_description" name="terpedia_terport_description" 
                                  placeholder="Brief description of the Terport content..."><?php echo esc_textarea($terport_description); ?></textarea>
                    </div>
                    <div class="terport-field">
                        <button type="button" id="generate_description_btn" class="terport-btn terport-btn-secondary">
                            📝 Generate Description
                        </button>
                    </div>
                </div>
                
                <div class="terport-field-row">
                    <div class="terport-field">
                        <label for="terpedia_terport_template_id">Content Template</label>
                        <select id="terpedia_terport_template_id" name="terpedia_terport_template_id">
                            <option value="">-- Select Template --</option>
                            <?php foreach ($templates as $template): ?>
                                <option value="<?php echo $template['id']; ?>" <?php selected($terport_template_id, $template['id']); ?>>
                                    <?php echo esc_html($template['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="terport-field">
                        <button type="button" id="manage_templates_btn" class="terport-btn terport-btn-secondary">
                            ⚙️ Manage Templates
                        </button>
                    </div>
                </div>
                
                <div class="terport-field-row">
                    <div class="terport-field">
                        <label for="terpedia_terport_generation_prompt">Generation Prompt</label>
                        <textarea id="terpedia_terport_generation_prompt" name="terpedia_terport_generation_prompt" 
                                  placeholder="Describe what you want to generate for this Terport..."><?php echo esc_textarea($terport_generation_prompt); ?></textarea>
                    </div>
                </div>
                
                <div class="terport-actions">
                    <button type="button" id="generate_content_btn" class="terport-btn terport-btn-primary">
                        🚀 Generate Content
                    </button>
                    <button type="button" id="generate_image_btn" class="terport-btn terport-btn-success">
                        🖼️ Generate Feature Image
                    </button>
                </div>
                
                <div id="terport_status" class="terport-status"></div>
                
                <div id="terport_image_preview" class="terport-image-preview"></div>
                
                <?php if ($terport_template_id): ?>
                    <div class="terport-template-info">
                        <strong>Selected Template:</strong> <?php echo esc_html($this->get_template_name($terport_template_id)); ?>
                        <br>
                        <strong>Sections:</strong> <?php echo esc_html($this->get_template_sections($terport_template_id)); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            var postId = <?php echo $post->ID; ?>;
            var postTitle = $('#title').val();
            
            // Auto-detect type from title
            $('#generate_type_btn').on('click', function() {
                if (!postTitle) {
                    alert('Please enter a title first.');
                    return;
                }
                
                var $btn = $(this);
                var $status = $('#terport_status');
                
                $btn.prop('disabled', true);
                $status.removeClass().addClass('terport-status loading').text('Analyzing title...').show();
                
                $.post(ajaxurl, {
                    action: 'terpedia_generate_terport_type',
                    post_id: postId,
                    title: postTitle,
                    nonce: '<?php echo wp_create_nonce('terpedia_terport_nonce'); ?>'
                }, function(response) {
                    $btn.prop('disabled', false);
                    
                    if (response.success) {
                        $('#terpedia_terport_type').val(response.data.type);
                        $('#terpedia_terport_description').val(response.data.description);
                        $status.removeClass().addClass('terport-status success').text('Type and description generated successfully!').show();
                    } else {
                        $status.removeClass().addClass('terport-status error').text('Error: ' + response.data).show();
                    }
                });
            });
            
            // Generate description
            $('#generate_description_btn').on('click', function() {
                if (!postTitle) {
                    alert('Please enter a title first.');
                    return;
                }
                
                var $btn = $(this);
                var $status = $('#terport_status');
                
                $btn.prop('disabled', true);
                $status.removeClass().addClass('terport-status loading').text('Generating description...').show();
                
                $.post(ajaxurl, {
                    action: 'terpedia_generate_terport_type',
                    post_id: postId,
                    title: postTitle,
                    description_only: true,
                    nonce: '<?php echo wp_create_nonce('terpedia_terport_nonce'); ?>'
                }, function(response) {
                    $btn.prop('disabled', false);
                    
                    if (response.success) {
                        $('#terpedia_terport_description').val(response.data.description);
                        $status.removeClass().addClass('terport-status success').text('Description generated successfully!').show();
                    } else {
                        $status.removeClass().addClass('terport-status error').text('Error: ' + response.data).show();
                    }
                });
            });
            
            // Generate content
            $('#generate_content_btn').on('click', function() {
                var templateId = $('#terpedia_terport_template_id').val();
                var prompt = $('#terpedia_terport_generation_prompt').val();
                var type = $('#terpedia_terport_type').val();
                var description = $('#terpedia_terport_description').val();
                
                if (!templateId) {
                    alert('Please select a template first.');
                    return;
                }
                
                if (!prompt) {
                    alert('Please enter a generation prompt.');
                    return;
                }
                
                var $btn = $(this);
                var $status = $('#terport_status');
                
                $btn.prop('disabled', true);
                $status.removeClass().addClass('terport-status loading').text('Generating content...').show();
                
                $.post(ajaxurl, {
                    action: 'terpedia_generate_terport_content',
                    post_id: postId,
                    template_id: templateId,
                    prompt: prompt,
                    type: type,
                    description: description,
                    nonce: '<?php echo wp_create_nonce('terpedia_terport_nonce'); ?>'
                }, function(response) {
                    $btn.prop('disabled', false);
                    
                    if (response.success) {
                        // Update the main content editor
                        if (typeof wp !== 'undefined' && wp.data && wp.data.dispatch) {
                            wp.data.dispatch('core/editor').editPost({
                                content: response.data.content
                            });
                        } else {
                            // Fallback for classic editor
                            if (typeof tinyMCE !== 'undefined' && tinyMCE.get('content')) {
                                tinyMCE.get('content').setContent(response.data.content);
                            } else {
                                $('#content').val(response.data.content);
                            }
                        }
                        $status.removeClass().addClass('terport-status success').text('Content generated successfully!').show();
                    } else {
                        $status.removeClass().addClass('terport-status error').text('Error: ' + response.data).show();
                    }
                });
            });
            
            // Generate feature image
            $('#generate_image_btn').on('click', function() {
                var title = $('#title').val();
                var type = $('#terpedia_terport_type').val();
                var description = $('#terpedia_terport_description').val();
                
                if (!title) {
                    alert('Please enter a title first.');
                    return;
                }
                
                var $btn = $(this);
                var $status = $('#terport_status');
                var $preview = $('#terport_image_preview');
                
                $btn.prop('disabled', true);
                $status.removeClass().addClass('terport-status loading').text('Generating feature image...').show();
                
                $.post(ajaxurl, {
                    action: 'terpedia_generate_terport_image',
                    post_id: postId,
                    title: title,
                    type: type,
                    description: description,
                    nonce: '<?php echo wp_create_nonce('terpedia_terport_nonce'); ?>'
                }, function(response) {
                    $btn.prop('disabled', false);
                    
                    if (response.success) {
                        $preview.html('<img src="' + response.data.image_url + '" alt="Generated Feature Image">');
                        $status.removeClass().addClass('terport-status success').text('Feature image generated successfully!').show();
                    } else {
                        $status.removeClass().addClass('terport-status error').text('Error: ' + response.data).show();
                    }
                });
            });
            
            // Manage templates
            $('#manage_templates_btn').on('click', function() {
                window.open('<?php echo admin_url('edit.php?post_type=terpedia_template'); ?>', '_blank');
            });
            
            // Auto-hide status messages
            setTimeout(function() {
                $('#terport_status').fadeOut();
            }, 5000);
        });
        </script>
        <?php
    }
    
    /**
     * Template meta box callback
     */
    public function terport_template_callback($post) {
        $selected_template = get_post_meta($post->ID, '_terpedia_terport_template_id', true);
        $templates = $this->get_terport_templates();
        
        ?>
        <div class="terport-template-sidebar">
            <h4>Template Information</h4>
            <?php if ($selected_template): ?>
                <?php $template = $this->get_template_details($selected_template); ?>
                <div class="template-details">
                    <p><strong>Name:</strong> <?php echo esc_html($template['name']); ?></p>
                    <p><strong>Description:</strong> <?php echo esc_html($template['description']); ?></p>
                    <p><strong>Sections:</strong> <?php echo esc_html($template['sections']); ?></p>
                </div>
            <?php else: ?>
                <p>No template selected. Choose a template to see details.</p>
            <?php endif; ?>
            
            <div class="template-actions">
                <a href="<?php echo admin_url('edit.php?post_type=terpedia_template'); ?>" class="button button-secondary" target="_blank">
                    Manage Templates
                </a>
            </div>
        </div>
        <?php
    }
    
    /**
     * Save Terport meta data
     */
    public function save_terport_meta($post_id) {
        if (!isset($_POST['terpedia_terport_nonce']) || 
            !wp_verify_nonce($_POST['terpedia_terport_nonce'], 'terpedia_terport_nonce')) {
            return;
        }
        
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        if (get_post_type($post_id) !== 'terport') {
            return;
        }
        
        // Save meta fields
        $fields = array(
            '_terpedia_terport_type',
            '_terpedia_terport_description',
            '_terpedia_terport_template_id',
            '_terpedia_terport_generation_prompt'
        );
        
        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                update_post_meta($post_id, $field, sanitize_text_field($_POST[$field]));
            }
        }
    }
    
    /**
     * AJAX: Generate Terport type and description
     */
    public function ajax_generate_terport_type() {
        check_ajax_referer('terpedia_terport_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $post_id = intval($_POST['post_id']);
        $title = sanitize_text_field($_POST['title']);
        $description_only = isset($_POST['description_only']) ? true : false;
        
        if (empty($title)) {
            wp_send_json_error('Title is required');
        }
        
        // Use OpenRouter API to analyze title and generate type/description
        $response = $this->openrouter_api->generate_terport_analysis($title, $description_only);
        
        if (is_wp_error($response)) {
            wp_send_json_error($response->get_error_message());
        }
        
        wp_send_json_success($response);
    }
    
    /**
     * AJAX: Generate Terport content
     */
    public function ajax_generate_terport_content() {
        check_ajax_referer('terpedia_terport_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $post_id = intval($_POST['post_id']);
        $template_id = intval($_POST['template_id']);
        $prompt = sanitize_textarea_field($_POST['prompt']);
        $type = sanitize_text_field($_POST['type']);
        $description = sanitize_textarea_field($_POST['description']);
        
        if (empty($template_id) || empty($prompt)) {
            wp_send_json_error('Template ID and prompt are required');
        }
        
        // Get template and generate content using structured outputs
        $template = $this->get_template_details($template_id);
        if (!$template) {
            wp_send_json_error('Template not found');
        }
        
        $response = $this->openrouter_api->generate_terport_content($template, $prompt, $type, $description);
        
        if (is_wp_error($response)) {
            wp_send_json_error($response->get_error_message());
        }
        
        wp_send_json_success($response);
    }
    
    /**
     * AJAX: Generate Terport feature image
     */
    public function ajax_generate_terport_image() {
        check_ajax_referer('terpedia_terport_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $post_id = intval($_POST['post_id']);
        $title = sanitize_text_field($_POST['title']);
        $type = sanitize_text_field($_POST['type']);
        $description = sanitize_textarea_field($_POST['description']);
        
        if (empty($title)) {
            wp_send_json_error('Title is required');
        }
        
        // Use multimodal LLM to generate feature image
        $response = $this->openrouter_api->generate_terport_image($title, $type, $description);
        
        if (is_wp_error($response)) {
            wp_send_json_error($response->get_error_message());
        }
        
        wp_send_json_success($response);
    }
    
    /**
     * AJAX: Get Terport templates
     */
    public function ajax_get_terport_templates() {
        check_ajax_referer('terpedia_terport_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $templates = $this->get_terport_templates();
        wp_send_json_success($templates);
    }
    
    /**
     * Get available Terport templates
     */
    private function get_terport_templates() {
        $templates = get_posts(array(
            'post_type' => 'terpedia_template',
            'post_status' => 'publish',
            'numberposts' => -1,
            'meta_query' => array(
                array(
                    'key' => '_terpedia_content_type',
                    'value' => 'terport',
                    'compare' => '='
                )
            ),
            'orderby' => 'title',
            'order' => 'ASC'
        ));
        
        $template_list = array();
        foreach ($templates as $template) {
            $template_list[] = array(
                'id' => $template->ID,
                'name' => $template->post_title,
                'description' => wp_trim_words($template->post_content, 20)
            );
        }
        
        return $template_list;
    }
    
    /**
     * Get template details
     */
    private function get_template_details($template_id) {
        $template = get_post($template_id);
        if (!$template || $template->post_type !== 'terpedia_template') {
            return false;
        }
        
        $sections = $this->extract_template_sections($template->post_content);
        
        return array(
            'id' => $template->ID,
            'name' => $template->post_title,
            'description' => wp_trim_words($template->post_content, 30),
            'content' => $template->post_content,
            'sections' => implode(', ', $sections)
        );
    }
    
    /**
     * Extract sections from template content
     */
    private function extract_template_sections($content) {
        preg_match_all('/\{\{([^}]+)\}\}/', $content, $matches);
        return array_unique($matches[1] ?? array());
    }
    
    /**
     * Get template name
     */
    private function get_template_name($template_id) {
        $template = get_post($template_id);
        return $template ? $template->post_title : 'Unknown Template';
    }
    
    /**
     * Get template sections
     */
    private function get_template_sections($template_id) {
        $template = get_post($template_id);
        if (!$template) {
            return 'No sections';
        }
        
        $sections = $this->extract_template_sections($template->post_content);
        return implode(', ', $sections);
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        global $post_type;
        
        if ($post_type === 'terport' && in_array($hook, array('post.php', 'post-new.php'))) {
            wp_enqueue_script('terpedia-terport-editor', plugin_dir_url(__FILE__) . '../assets/js/terport-editor.js', array('jquery'), '1.0.0', true);
            wp_enqueue_style('terpedia-terport-editor', plugin_dir_url(__FILE__) . '../assets/css/terport-editor.css', array(), '1.0.0');
            
            wp_localize_script('terpedia-terport-editor', 'terpediaTerport', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('terpedia_terport_nonce')
            ));
        }
    }
}

// Initialize the enhanced Terport editor
new Terpedia_Enhanced_Terport_Editor();
