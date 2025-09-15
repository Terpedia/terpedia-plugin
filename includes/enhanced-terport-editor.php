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
        add_action('init', array($this, 'register_terport_cpt'));
        add_action('add_meta_boxes', array($this, 'add_terport_meta_boxes'));
        add_action('save_post', array($this, 'save_terport_meta'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // AJAX handlers
        add_action('wp_ajax_terpedia_generate_terport_type', array($this, 'ajax_generate_terport_type'));
        add_action('wp_ajax_terpedia_generate_terport_content', array($this, 'ajax_generate_terport_content'));
        add_action('wp_ajax_terpedia_generate_terport_image', array($this, 'ajax_generate_terport_image'));
        add_action('wp_ajax_terpedia_get_terport_templates', array($this, 'ajax_get_terport_templates'));
        
        // Frontend form handlers (login required)
        add_action('wp_ajax_terpedia_create_user_terport', array($this, 'ajax_create_user_terport'));
        add_shortcode('terport_creation_form', array($this, 'render_terport_creation_form'));
        
        // Initialize OpenRouter API
        $this->openrouter_api = new Terpedia_OpenRouter_API();
    }
    
    /**
     * Register Terport Custom Post Type
     */
    public function register_terport_cpt() {
        register_post_type('terport', array(
            'labels' => array(
                'name' => 'Terports',
                'singular_name' => 'Terport',
                'menu_name' => 'Terports',
                'add_new' => 'Add New Terport',
                'add_new_item' => 'Add New Terport',
                'edit_item' => 'Edit Terport',
                'new_item' => 'New Terport',
                'view_item' => 'View Terport',
                'search_items' => 'Search Terports',
                'not_found' => 'No terports found',
                'not_found_in_trash' => 'No terports found in trash',
                'all_items' => 'All Terports'
            ),
            'public' => true,
            'show_ui' => true,
            'show_in_menu' => 'terpedia-main',
            'menu_position' => 20,
            'menu_icon' => 'dashicons-analytics',
            'supports' => array('title', 'editor', 'excerpt', 'thumbnail', 'custom-fields', 'author'),
            'has_archive' => true,
            'rewrite' => array('slug' => 'terports'),
            'show_in_rest' => true,
            'capability_type' => 'post',
            'map_meta_cap' => true
        ));
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
    
    /**
     * Render frontend Terport creation form
     */
    public function render_terport_creation_form($atts) {
        if (!is_user_logged_in()) {
            return '<div class="terport-form-notice"><p>Please <a href="' . wp_login_url(get_permalink()) . '">log in</a> to create a Terport.</p></div>';
        }
        
        ob_start();
        ?>
        <div class="terport-creation-form-container">
            <form id="terport-creation-form" class="terport-creation-form">
                <?php wp_nonce_field('terpedia_create_terport_nonce', 'terport_nonce'); ?>
                
                <div class="form-header">
                    <h3>🧪 Create Your Research Report</h3>
                    <p>Create a private research report that only you can access.</p>
                </div>
                
                <div class="form-group">
                    <label for="terport_title">Research Report Title *</label>
                    <input type="text" id="terport_title" name="terport_title" required 
                           placeholder="e.g., Limonene Cancer Research Summary" 
                           maxlength="200">
                </div>
                
                <div class="form-group">
                    <label for="terport_type">Research Type *</label>
                    <select id="terport_type" name="terport_type" required>
                        <option value="">Select research type...</option>
                        <option value="cancer_research">Cancer Research</option>
                        <option value="veterinary_research">Veterinary Research</option>
                        <option value="clinical_study">Clinical Study</option>
                        <option value="literature_review">Literature Review</option>
                        <option value="case_study">Case Study</option>
                        <option value="molecular_analysis">Molecular Analysis</option>
                        <option value="product_analysis">Product Analysis</option>
                        <option value="therapeutic_protocol">Therapeutic Protocol</option>
                        <option value="safety_assessment">Safety Assessment</option>
                        <option value="custom_research">Custom Research</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="terport_description">Brief Description (Optional)</label>
                    <textarea id="terport_description" name="terport_description" rows="3" 
                              placeholder="What specific aspects will this research cover?"></textarea>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="submit-btn">
                        <span class="btn-text">Create Research Report</span>
                        <span class="btn-loading" style="display:none;">Creating...</span>
                    </button>
                </div>
                
                <div id="terport-form-response" class="form-response"></div>
            </form>
        </div>
        
        <style>
        .terport-creation-form-container {
            max-width: 600px;
            margin: 20px auto;
            padding: 30px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            border: 1px solid #e1e5e9;
        }
        
        .form-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .form-header h3 {
            margin: 0 0 10px 0;
            color: #2c3e50;
            font-size: 24px;
        }
        
        .form-header p {
            margin: 0;
            color: #6c757d;
            font-size: 14px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #495057;
            font-size: 14px;
        }
        
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s ease;
            box-sizing: border-box;
        }
        
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            outline: none;
            border-color: #007cba;
            box-shadow: 0 0 0 3px rgba(0, 124, 186, 0.1);
        }
        
        .form-actions {
            text-align: center;
            margin-top: 30px;
        }
        
        .submit-btn {
            background: linear-gradient(135deg, #007cba 0%, #005a87 100%);
            color: white;
            border: none;
            padding: 14px 32px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            min-width: 200px;
        }
        
        .submit-btn:hover {
            background: linear-gradient(135deg, #005a87 0%, #003d5c 100%);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 124, 186, 0.3);
        }
        
        .form-response {
            margin-top: 20px;
            padding: 15px;
            border-radius: 8px;
            display: none;
        }
        
        .form-response.success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        
        .form-response.error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        
        .terport-form-notice {
            text-align: center;
            padding: 20px;
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
        }
        </style>
        
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('terport-creation-form');
            if (!form) return;
            
            const submitBtn = form.querySelector('.submit-btn');
            const btnText = submitBtn.querySelector('.btn-text');
            const btnLoading = submitBtn.querySelector('.btn-loading');
            const responseDiv = document.getElementById('terport-form-response');
            
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                submitBtn.disabled = true;
                btnText.style.display = 'none';
                btnLoading.style.display = 'inline';
                responseDiv.style.display = 'none';
                
                const formData = new FormData(form);
                formData.append('action', 'terpedia_create_user_terport');
                
                fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    responseDiv.style.display = 'block';
                    
                    if (data.success) {
                        responseDiv.className = 'form-response success';
                        responseDiv.innerHTML = '<strong>Success!</strong> ' + data.data.message;
                        form.reset();
                        
                        if (data.data.redirect_url) {
                            setTimeout(() => {
                                window.location.href = data.data.redirect_url;
                            }, 2000);
                        }
                    } else {
                        responseDiv.className = 'form-response error';
                        responseDiv.innerHTML = '<strong>Error:</strong> ' + (data.data || 'Something went wrong. Please try again.');
                    }
                })
                .catch(error => {
                    responseDiv.style.display = 'block';
                    responseDiv.className = 'form-response error';
                    responseDiv.innerHTML = '<strong>Error:</strong> Failed to create research report. Please try again.';
                })
                .finally(() => {
                    submitBtn.disabled = false;
                    btnText.style.display = 'inline';
                    btnLoading.style.display = 'none';
                });
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }
    
    /**
     * AJAX handler for creating user Terports
     */
    public function ajax_create_user_terport() {
        // Verify nonce with correct action name
        if (!check_ajax_referer('terpedia_create_terport_nonce', 'terport_nonce', false)) {
            wp_send_json_error('Security check failed');
        }
        
        if (!is_user_logged_in()) {
            wp_send_json_error('You must be logged in to create a research report');
        }
        
        $title = sanitize_text_field($_POST['terport_title']);
        $type = sanitize_text_field($_POST['terport_type']);
        $description = sanitize_textarea_field($_POST['terport_description']);
        
        if (empty($title) || empty($type)) {
            wp_send_json_error('Title and research type are required');
        }
        
        $post_data = array(
            'post_title' => $title,
            'post_content' => $description ? '<p>' . $description . '</p>' : '',
            'post_status' => 'private',
            'post_type' => 'terport',
            'post_author' => get_current_user_id(),
            'meta_input' => array(
                '_terpedia_terport_type' => $type,
                '_terpedia_terport_description' => $description,
                '_terpedia_user_created' => 'yes',
                '_terpedia_creation_date' => current_time('mysql')
            )
        );
        
        $post_id = wp_insert_post($post_data);
        
        if (is_wp_error($post_id) || !$post_id) {
            wp_send_json_error('Failed to create research report');
        }
        
        wp_send_json_success(array(
            'message' => 'Research report created successfully! You can now view and edit it.',
            'post_id' => $post_id,
            'redirect_url' => get_permalink($post_id)
        ));
    }
}

// Initialize the enhanced Terport editor
new Terpedia_Enhanced_Terport_Editor();
