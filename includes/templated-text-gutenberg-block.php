<?php
/**
 * Templated Text Gutenberg Block System
 * 
 * Provides a smart Gutenberg block that uses CPT-specific templates
 * and LLM generation to create rich text content
 * 
 * @package Terpedia
 */

if (!defined('ABSPATH')) {
    exit;
}

class Terpedia_Templated_Text_Block {
    
    private $openrouter_api;
    
    public function __construct() {
        add_action('init', array($this, 'register_block'));
        add_action('enqueue_block_editor_assets', array($this, 'enqueue_block_assets'));
        
        // AJAX handlers for template operations
        add_action('wp_ajax_get_cpt_templates', array($this, 'get_cpt_templates'));
        add_action('wp_ajax_generate_templated_content', array($this, 'generate_templated_content'));
        add_action('wp_ajax_save_template', array($this, 'save_template'));
        
        // Add template management to admin menu
        add_action('admin_menu', array($this, 'add_template_admin_page'));
        
        // Initialize OpenRouter API
        $this->openrouter_api = new Terpedia_OpenRouter_API();
    }
    
    /**
     * Register the Gutenberg block
     */
    public function register_block() {
        register_block_type(__DIR__ . '/../blocks/templated-text', array(
            'render_callback' => array($this, 'render_block')
        ));
    }
    
    /**
     * Enqueue block assets
     */
    public function enqueue_block_assets() {
        $asset_file = include(plugin_dir_path(__FILE__) . '../blocks/templated-text/build/index.asset.php');
        
        wp_enqueue_script(
            'terpedia-templated-text-block',
            plugin_dir_url(__FILE__) . '../blocks/templated-text/build/index.js',
            $asset_file['dependencies'] ?? array('wp-blocks', 'wp-block-editor', 'wp-components', 'wp-element', 'wp-i18n'),
            $asset_file['version'] ?? '1.0.0'
        );
        
        wp_enqueue_style(
            'terpedia-templated-text-block',
            plugin_dir_url(__FILE__) . '../blocks/templated-text/build/index.css',
            array('wp-edit-blocks'),
            '1.0.0'
        );
        
        // Pass data to JavaScript
        wp_localize_script('terpedia-templated-text-block', 'terpediaTemplatedText', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('terpedia_templated_text'),
            'currentPostType' => get_current_screen() ? get_current_screen()->post_type : 'post'
        ));
    }
    
    /**
     * Render block on frontend
     */
    public function render_block($attributes, $content) {
        if (empty($attributes['content'])) {
            return '';
        }
        
        $wrapper_attributes = get_block_wrapper_attributes(array(
            'class' => 'templated-text-block'
        ));
        
        return sprintf(
            '<div %s>%s</div>',
            $wrapper_attributes,
            wp_kses_post($attributes['content'])
        );
    }
    
    /**
     * Get templates for specific CPT
     */
    public function get_cpt_templates() {
        check_ajax_referer('terpedia_templated_text', 'nonce');
        
        $post_type = sanitize_text_field($_POST['post_type'] ?? 'post');
        $templates = $this->get_templates_for_cpt($post_type);
        
        wp_send_json_success(array(
            'templates' => $templates,
            'post_type' => $post_type
        ));
    }
    
    /**
     * Generate content using template and LLM
     */
    public function generate_templated_content() {
        check_ajax_referer('terpedia_templated_text', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Access denied');
        }
        
        $template_id = intval($_POST['template_id'] ?? 0);
        $field_values = $_POST['field_values'] ?? array();
        $post_type = sanitize_text_field($_POST['post_type'] ?? 'post');
        $selected_model = sanitize_text_field($_POST['selected_model'] ?? 'openai/gpt-5');
        
        if (!$template_id) {
            wp_send_json_error('No template selected');
        }
        
        $template = $this->get_template($template_id);
        if (!$template) {
            wp_send_json_error('Template not found');
        }
        
        // Generate content using AI
        $content = $this->generate_ai_content($template, $field_values, $post_type, $selected_model);
        
        if (is_wp_error($content)) {
            wp_send_json_error($content->get_error_message());
        }
        
        wp_send_json_success(array(
            'content' => $content,
            'template_name' => $template['name']
        ));
    }
    
    /**
     * Get templates for specific CPT from database
     */
    private function get_templates_for_cpt($post_type) {
        $templates = array();
        $template_cpt = $post_type . '_template';
        
        // Query for active templates for this CPT
        $template_posts = get_posts(array(
            'post_type' => $template_cpt,
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'meta_query' => array(
                array(
                    'key' => '_template_active',
                    'value' => '1',
                    'compare' => '='
                )
            ),
            'orderby' => 'title',
            'order' => 'ASC'
        ));
        
        foreach ($template_posts as $template_post) {
            $template_schema = get_post_meta($template_post->ID, '_template_schema', true);
            $template_description = get_post_meta($template_post->ID, '_template_description', true);
            $template_category = get_post_meta($template_post->ID, '_template_category', true);
            
            if (!$template_schema || !$template_schema['fields']) {
                continue; // Skip templates without proper schema
            }
            
            $templates[] = array(
                'id' => $template_post->ID,
                'name' => $template_post->post_title,
                'description' => $template_description ?: 'AI-generated content template',
                'category' => $template_category ?: 'General',
                'fields' => $template_schema['fields'],
                'schema' => $template_schema['schema'],
                'content_template' => $template_post->post_content,
                'created' => $template_post->post_date
            );
        }
        
        // Add fallback templates if no custom templates exist
        if (empty($templates)) {
            $templates = $this->get_fallback_templates($post_type);
        }
        
        return $templates;
    }
    
    /**
     * Get specific template by ID
     */
    private function get_template($template_id) {
        // First try to get from database
        $template_post = get_post($template_id);
        
        if ($template_post && strpos($template_post->post_type, '_template') !== false) {
            $template_schema = get_post_meta($template_id, '_template_schema', true);
            $template_description = get_post_meta($template_id, '_template_description', true);
            $template_category = get_post_meta($template_id, '_template_category', true);
            
            if ($template_schema && $template_schema['fields']) {
                return array(
                    'id' => $template_id,
                    'name' => $template_post->post_title,
                    'description' => $template_description ?: 'AI-generated content template',
                    'category' => $template_category ?: 'General',
                    'fields' => $template_schema['fields'],
                    'schema' => $template_schema['schema'],
                    'content_template' => $template_post->post_content,
                    'created' => $template_post->post_date
                );
            }
        }
        
        // Fallback to hardcoded templates for compatibility
        $all_cpts = array('terport', 'terpedia_terproduct', 'terpedia_rx', 'post');
        
        foreach ($all_cpts as $cpt) {
            $templates = $this->get_fallback_templates($cpt);
            foreach ($templates as $template) {
                if ($template['id'] === $template_id) {
                    return $template;
                }
            }
        }
        
        return null;
    }
    
    /**
     * Generate AI content using template
     */
    private function generate_ai_content($template, $field_values, $post_type, $selected_model = 'openai/gpt-5') {
        // For new template system, use content template with field replacements
        if (isset($template['content_template']) && isset($template['schema'])) {
            return $this->generate_with_template_content($template, $field_values, $post_type, $selected_model);
        }
        
        // Legacy support for old prompt_template format
        if (isset($template['prompt_template'])) {
            return $this->generate_with_legacy_template($template, $field_values, $post_type, $selected_model);
        }
        
        return new WP_Error('template_error', 'Invalid template format');
    }
    
    /**
     * Generate content using new template system
     */
    private function generate_with_template_content($template, $field_values, $post_type, $selected_model = 'openai/gpt-5') {
        // Replace field placeholders in template content
        $content_template = $template['content_template'];
        
        foreach ($field_values as $field_name => $field_value) {
            // Replace various field formats
            $patterns = array(
                '/\{\{' . preg_quote($field_name) . '\}\}/',
                '/\{\{' . preg_quote($field_name) . ':[^}]*\}\}/'
            );
            
            foreach ($patterns as $pattern) {
                $content_template = preg_replace($pattern, $field_value, $content_template);
            }
        }
        
        // Get system prompt
        $system_prompt = get_post_meta($template['id'], '_system_prompt', true);
        if (!$system_prompt) {
            $system_prompt = $this->get_default_system_prompt_for_cpt($post_type);
        }
        
        // Create comprehensive prompt
        $full_prompt = "Based on this template structure, generate comprehensive content:\n\n" . $content_template . "\n\nExpand each section with relevant, accurate information while maintaining the structure and style.";
        
        // Use template's schema for structured output
        $response = $this->openrouter_api->make_api_request($system_prompt, $full_prompt, $template['schema'], $selected_model);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        // Return HTML content from structured response
        if (is_array($response)) {
            // If structured response, combine all fields into HTML
            $html_content = '';
            foreach ($response as $field_name => $field_content) {
                $html_content .= '<div class="generated-section">';
                $html_content .= '<h4>' . ucwords(str_replace('_', ' ', $field_name)) . '</h4>';
                $html_content .= '<p>' . wp_kses_post($field_content) . '</p>';
                $html_content .= '</div>';
            }
            return $html_content;
        }
        
        return $response;
    }
    
    /**
     * Generate content using legacy template format
     */
    private function generate_with_legacy_template($template, $field_values, $post_type, $selected_model = 'openai/gpt-5') {
        // Replace placeholders in prompt template
        $prompt = $template['prompt_template'];
        foreach ($field_values as $field_name => $field_value) {
            $prompt = str_replace('{' . $field_name . '}', $field_value, $prompt);
        }
        
        $system_prompt = $this->get_default_system_prompt_for_cpt($post_type);
        
        // Set up structured response
        $response_format = array(
            'type' => 'json_schema',
            'json_schema' => array(
                'name' => 'templated_content',
                'strict' => true,
                'schema' => array(
                    'type' => 'object',
                    'properties' => array(
                        'content' => array(
                            'type' => 'string',
                            'description' => 'HTML formatted content ready for WordPress editor'
                        ),
                        'word_count' => array(
                            'type' => 'integer',
                            'description' => 'Approximate word count'
                        )
                    ),
                    'required' => array('content', 'word_count'),
                    'additionalProperties' => false
                )
            )
        );
        
        $response = $this->openrouter_api->make_api_request($system_prompt, $prompt, $response_format, $selected_model);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        return $response['content'] ?? '';
    }
    
    /**
     * Get default system prompt for CPT
     */
    private function get_default_system_prompt_for_cpt($post_type) {
        $prompts = array(
            'terport' => 'You are an expert scientific researcher specializing in terpene research. Create accurate, well-researched content with proper citations and scientific rigor.',
            'terpedia_terproduct' => 'You are an expert product analyst specializing in cannabis and terpene products. Provide detailed, objective analysis with focus on quality and composition.',
            'terpedia_rx' => 'You are an expert formulation scientist specializing in terpene therapeutics. Provide precise, scientific guidance with safety considerations.',
            'default' => 'You are an expert researcher. Create accurate, well-structured content based on current knowledge.'
        );
        
        return $prompts[$post_type] ?? $prompts['default'];
    }
    
    /**
     * Add template management page
     */
    public function add_template_admin_page() {
        add_submenu_page(
            'terpedia-main',
            'Content Templates',
            'Templates',
            'manage_options',
            'terpedia-templates',
            array($this, 'render_template_admin_page')
        );
    }
    
    /**
     * Render template admin page
     */
    public function render_template_admin_page() {
        ?>
        <div class="wrap">
            <h1>📝 Content Templates</h1>
            <p>Manage templates for the Templated Text Gutenberg block across different content types.</p>
            
            <div class="notice notice-info">
                <p><strong>How Templates Work:</strong> Templates are automatically available in the Templated Text block based on the post type you're editing. Each template includes fields that users fill out, which are then used by AI to generate relevant content.</p>
            </div>
            
            <div class="template-overview">
                <?php foreach (array('terport', 'terpedia_terproduct', 'terpedia_rx') as $cpt): ?>
                    <div class="template-section" style="background: white; margin: 20px 0; padding: 20px; border: 1px solid #ccc; border-radius: 8px;">
                        <h2><?php echo $this->get_cpt_display_name($cpt); ?> Templates</h2>
                        <div class="templates-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 15px;">
                            <?php foreach ($this->get_templates_for_cpt($cpt) as $template): ?>
                                <div class="template-card" style="border: 1px solid #ddd; border-radius: 6px; padding: 15px;">
                                    <h4 style="margin: 0 0 8px 0; color: #ff1493;"><?php echo esc_html($template['name']); ?></h4>
                                    <p style="margin: 0 0 10px 0; font-size: 13px; color: #666;"><?php echo esc_html($template['description']); ?></p>
                                    <div style="font-size: 12px;">
                                        <strong>Fields:</strong>
                                        <?php foreach ($template['fields'] as $field): ?>
                                            <span style="background: #f0f0f0; padding: 2px 6px; border-radius: 3px; margin: 2px;"><?php echo esc_html($field['label']); ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="template-usage" style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-top: 20px;">
                <h3>💡 How to Use Templates</h3>
                <ol>
                    <li>When editing a post, add the <strong>Templated Text</strong> block</li>
                    <li>In the block settings sidebar, select a template appropriate for your content type</li>
                    <li>Fill in the template fields with your specific information</li>
                    <li>Click <strong>Generate Content</strong> to create AI-powered content</li>
                    <li>Edit and customize the generated content as needed</li>
                </ol>
                
                <p><strong>Note:</strong> Templates are automatically filtered by post type, so you'll only see relevant templates for the content you're creating.</p>
            </div>
        </div>
        <?php
    }
    
    /**
     * Get display name for CPT
     */
    private function get_cpt_display_name($cpt) {
        $names = array(
            'terport' => 'Terports',
            'terpedia_terproduct' => 'Terproducts',
            'terpedia_rx' => 'Rx Formulations',
            'post' => 'Posts'
        );
        
        return $names[$cpt] ?? ucfirst($cpt);
    }
    
    /**
     * Get fallback templates for CPTs without custom templates
     */
    private function get_fallback_templates($post_type) {
        $templates = array();
        
        // Default templates for all CPTs
        $default_templates = array(
            array(
                'id' => 'summary_' . $post_type,
                'name' => 'Content Summary',
                'description' => 'Generate a concise summary of a topic',
                'category' => 'General',
                'fields' => array(
                    'topic' => array('name' => 'topic', 'label' => 'Topic', 'type' => 'text', 'required' => true),
                    'focus' => array('name' => 'focus', 'label' => 'Focus Area', 'type' => 'text', 'placeholder' => 'What aspect to emphasize')
                ),
                'prompt_template' => 'Create a comprehensive summary of {topic}, focusing on {focus}. Make it informative and well-structured.'
            ),
            array(
                'id' => 'analysis_' . $post_type,
                'name' => 'Analysis & Insights',
                'description' => 'Generate analytical content with insights',
                'category' => 'Analysis',
                'fields' => array(
                    'subject' => array('name' => 'subject', 'label' => 'Subject', 'type' => 'text', 'required' => true),
                    'perspective' => array('name' => 'perspective', 'label' => 'Perspective', 'type' => 'select', 'options' => array('Scientific', 'Clinical', 'Commercial', 'Regulatory'))
                ),
                'prompt_template' => 'Provide a detailed analysis of {subject} from a {perspective} perspective. Include key insights and implications.'
            )
        );
        
        // CPT-specific fallback templates
        switch ($post_type) {
            case 'terport':
                $templates = array(
                    array(
                        'id' => 'literature_review_fallback',
                        'name' => 'Literature Review Section',
                        'description' => 'Generate a literature review section with current research',
                        'category' => 'Research',
                        'fields' => array(
                            'compound' => array('name' => 'compound', 'label' => 'Compound/Terpene', 'type' => 'text', 'required' => true),
                            'therapeutic_area' => array('name' => 'therapeutic_area', 'label' => 'Therapeutic Area', 'type' => 'text', 'placeholder' => 'e.g., anxiety, pain relief'),
                            'time_period' => array('name' => 'time_period', 'label' => 'Research Period', 'type' => 'select', 'options' => array('Last 2 years', 'Last 5 years', 'All available', '2020-2025'))
                        ),
                        'prompt_template' => 'Create a comprehensive literature review section about {compound} for {therapeutic_area}. Focus on research from {time_period}. Include peer-reviewed studies, clinical trials, and mechanism of action. Use scientific language but remain accessible.'
                    )
                );
                break;
                
            case 'terpedia_terproduct':
                $templates = array(
                    array(
                        'id' => 'product_analysis_fallback',
                        'name' => 'Product Analysis',
                        'description' => 'Analyze product composition and quality',
                        'category' => 'Product Analysis',
                        'fields' => array(
                            'product_name' => array('name' => 'product_name', 'label' => 'Product Name', 'type' => 'text', 'required' => true),
                            'product_type' => array('name' => 'product_type', 'label' => 'Product Type', 'type' => 'select', 'options' => array('Flower', 'Extract', 'Edible', 'Topical', 'Tincture')),
                            'focus_area' => array('name' => 'focus_area', 'label' => 'Analysis Focus', 'type' => 'select', 'options' => array('Terpene profile', 'Quality assessment', 'Lab results', 'Therapeutic potential'))
                        ),
                        'prompt_template' => 'Analyze {product_name}, a {product_type} product, with focus on {focus_area}. Provide detailed insights about composition, quality indicators, and potential effects.'
                    )
                );
                break;
                
            case 'terpedia_rx':
                $templates = array(
                    array(
                        'id' => 'formulation_rationale_fallback',
                        'name' => 'Formulation Rationale',
                        'description' => 'Explain why specific terpenes are combined',
                        'category' => 'Formulation',
                        'fields' => array(
                            'target_condition' => array('name' => 'target_condition', 'label' => 'Target Condition', 'type' => 'text', 'required' => true),
                            'primary_terpenes' => array('name' => 'primary_terpenes', 'label' => 'Primary Terpenes', 'type' => 'text', 'required' => true),
                            'ratios' => array('name' => 'ratios', 'label' => 'Ratios/Percentages', 'type' => 'text', 'placeholder' => 'e.g., 2:1:0.5')
                        ),
                        'prompt_template' => 'Explain the scientific rationale for combining {primary_terpenes} in ratios of {ratios} to treat {target_condition}. Include synergistic effects, dosing considerations, and expected outcomes.'
                    )
                );
                break;
        }
        
        return array_merge($templates, $default_templates);
    }
}

// Initialize the templated text block system
new Terpedia_Templated_Text_Block();