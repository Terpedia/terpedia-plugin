<?php
/**
 * Simple Terport Editor
 * 
 * Clean, WordPress-native interface for Terport creation:
 * Title + Type Dropdown + Description + Generate Button
 * 
 * @package Terpedia
 */

if (!defined('ABSPATH')) {
    exit;
}

class Terpedia_Simple_Terport_Editor {
    
    private $openrouter_api;
    
    public function __construct() {
        add_action('add_meta_boxes', array($this, 'add_simple_meta_boxes'));
        add_action('save_post', array($this, 'save_terport_meta'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_editor_scripts'));
        
        // AJAX handlers
        add_action('wp_ajax_terpedia_generate_terport_content', array($this, 'generate_terport_content'));
        
        // Initialize OpenRouter API
        $this->openrouter_api = new Terpedia_OpenRouter_API();
    }
    
    /**
     * Add simple meta boxes
     */
    public function add_simple_meta_boxes() {
        // Remove the complex field-based template system
        remove_meta_box('terpedia_field_template_selector', 'terport', 'normal');
        remove_meta_box('terpedia_structured_fields', 'terport', 'normal');
        
        // Add simple generation interface above the editor
        add_meta_box(
            'terpedia_simple_generator',
            '🤖 AI Terport Generator',
            array($this, 'simple_generator_callback'),
            'terport',
            'normal',
            'high'
        );
    }
    
    /**
     * Simple generator interface
     */
    public function simple_generator_callback($post) {
        wp_nonce_field('terpedia_simple_generator_nonce', 'terpedia_simple_generator_nonce');
        
        $terport_type = get_post_meta($post->ID, '_terport_type', true);
        $terport_description = get_post_meta($post->ID, '_terport_description', true);
        
        ?>
        <div class="simple-terport-generator">
            <style>
                .simple-terport-generator {
                    background: linear-gradient(135deg, #ff69b4, #ff1493);
                    color: white;
                    padding: 20px;
                    border-radius: 8px;
                    margin-bottom: 10px;
                }
                
                .generator-row {
                    display: grid;
                    grid-template-columns: 150px 1fr;
                    gap: 15px;
                    margin-bottom: 15px;
                    align-items: center;
                }
                
                .generator-row:last-child {
                    margin-bottom: 0;
                }
                
                .generator-label {
                    font-weight: 600;
                    font-size: 14px;
                }
                
                .generator-field select,
                .generator-field input,
                .generator-field textarea {
                    width: 100%;
                    padding: 8px 12px;
                    border: 1px solid rgba(255,255,255,0.3);
                    border-radius: 4px;
                    background: rgba(255,255,255,0.9);
                    color: #333;
                    font-size: 14px;
                }
                
                .generator-field textarea {
                    resize: vertical;
                    min-height: 60px;
                }
                
                .generate-main-btn {
                    background: rgba(255,255,255,0.2);
                    border: 2px solid white;
                    color: white;
                    padding: 15px 30px;
                    border-radius: 6px;
                    font-weight: bold;
                    font-size: 16px;
                    cursor: pointer;
                    transition: all 0.2s;
                    width: 100%;
                    margin-top: 10px;
                }
                
                .generate-main-btn:hover {
                    background: white;
                    color: #ff1493;
                }
                
                .generate-main-btn:disabled {
                    opacity: 0.6;
                    cursor: not-allowed;
                }
                
                .generation-status {
                    margin-top: 10px;
                    padding: 10px;
                    border-radius: 4px;
                    background: rgba(255,255,255,0.1);
                    display: none;
                }
                
                .status-working {
                    background: rgba(255,193,7,0.2);
                    border: 1px solid rgba(255,193,7,0.5);
                }
                
                .status-success {
                    background: rgba(40,167,69,0.2);
                    border: 1px solid rgba(40,167,69,0.5);
                }
                
                .status-error {
                    background: rgba(220,53,69,0.2);
                    border: 1px solid rgba(220,53,69,0.5);
                }
                
                @media (max-width: 768px) {
                    .generator-row {
                        grid-template-columns: 1fr;
                        gap: 8px;
                    }
                }
            </style>
            
            <div class="generator-row">
                <div class="generator-label">📋 Type:</div>
                <div class="generator-field">
                    <select name="terport_type" id="terport_type">
                        <option value="">Select Terport Type...</option>
                        <option value="literature_review" <?php selected($terport_type, 'literature_review'); ?>>📚 Literature Review</option>
                        <option value="product_evaluation" <?php selected($terport_type, 'product_evaluation'); ?>>🔬 Product Evaluation</option>
                        <option value="product_recommendations" <?php selected($terport_type, 'product_recommendations'); ?>>💡 Product Recommendations</option>
                        <option value="clinical_analysis" <?php selected($terport_type, 'clinical_analysis'); ?>>🏥 Clinical Analysis</option>
                        <option value="regulatory_update" <?php selected($terport_type, 'regulatory_update'); ?>>⚖️ Regulatory Update</option>
                        <option value="market_analysis" <?php selected($terport_type, 'market_analysis'); ?>>📈 Market Analysis</option>
                    </select>
                </div>
            </div>
            
            <div class="generator-row">
                <div class="generator-label">📝 Description:</div>
                <div class="generator-field">
                    <textarea name="terport_description" id="terport_description" placeholder="Brief description of what this Terport should cover (e.g., 'Comprehensive analysis of limonene's anti-anxiety effects in recent clinical trials')"><?php echo esc_textarea($terport_description); ?></textarea>
                </div>
            </div>
            
            <button type="button" class="generate-main-btn" id="generateTerportBtn">
                ✨ Generate Terport Content
            </button>
            
            <div class="generation-status" id="generationStatus">
                <div class="status-message"></div>
            </div>
            
            <div style="margin-top: 15px; font-size: 13px; opacity: 0.9;">
                💡 <strong>How it works:</strong> Enter a title above, select the type, add a brief description, then click Generate. 
                The AI will create a comprehensive Terport in the main editor below.
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('#generateTerportBtn').on('click', function() {
                generateTerportContent();
            });
            
            // Auto-save type and description on change
            $('#terport_type, #terport_description').on('change input', function() {
                // Trigger WordPress autosave
                if (typeof wp !== 'undefined' && wp.autosave) {
                    wp.autosave.server.triggerSave();
                }
            });
            
            function generateTerportContent() {
                var title = $('#title').val().trim();
                var type = $('#terport_type').val();
                var description = $('#terport_description').val().trim();
                
                // Validation
                if (!title) {
                    alert('Please enter a title for your Terport first.');
                    $('#title').focus();
                    return;
                }
                
                if (!type) {
                    alert('Please select a Terport type.');
                    $('#terport_type').focus();
                    return;
                }
                
                if (!description) {
                    alert('Please provide a brief description of what this Terport should cover.');
                    $('#terport_description').focus();
                    return;
                }
                
                var $btn = $('#generateTerportBtn');
                var $status = $('#generationStatus');
                
                // Update UI
                $btn.prop('disabled', true).text('🔄 Generating...');
                $status.removeClass('status-success status-error').addClass('status-working').show();
                $status.find('.status-message').text('Analyzing topic and generating comprehensive content...');
                
                // Make AJAX request
                $.post(ajaxurl, {
                    action: 'terpedia_generate_terport_content',
                    post_id: <?php echo $post->ID; ?>,
                    title: title,
                    type: type,
                    description: description,
                    nonce: '<?php echo wp_create_nonce("simple_generator_nonce"); ?>'
                })
                .done(function(response) {
                    if (response.success) {
                        // Insert content into WordPress editor
                        insertContentIntoEditor(response.data.content);
                        
                        $status.removeClass('status-working').addClass('status-success');
                        $status.find('.status-message').text('✅ Terport generated successfully! Content has been added to the editor below.');
                        
                        // Scroll to editor
                        $('html, body').animate({
                            scrollTop: $('#postdivrich').offset().top - 50
                        }, 500);
                        
                    } else {
                        $status.removeClass('status-working').addClass('status-error');
                        $status.find('.status-message').text('❌ Error: ' + (response.data || 'Unknown error occurred'));
                    }
                })
                .fail(function() {
                    $status.removeClass('status-working').addClass('status-error');
                    $status.find('.status-message').text('❌ Network error. Please check your connection and try again.');
                })
                .always(function() {
                    $btn.prop('disabled', false).text('✨ Generate Terport Content');
                    
                    // Hide status after delay
                    setTimeout(function() {
                        $status.fadeOut();
                    }, 5000);
                });
            }
            
            function insertContentIntoEditor(content) {
                // Try Block Editor (Gutenberg) first
                if (typeof wp !== 'undefined' && wp.data && wp.data.dispatch) {
                    try {
                        wp.data.dispatch('core/editor').editPost({
                            content: content
                        });
                        return;
                    } catch (e) {
                        console.log('Block editor not available, falling back to classic editor');
                    }
                }
                
                // Fall back to Classic Editor
                if (typeof tinymce !== 'undefined' && tinymce.get('content')) {
                    tinymce.get('content').setContent(content);
                } else if ($('#content').length) {
                    $('#content').val(content);
                } else {
                    console.error('Could not find editor to insert content');
                    alert('Content generated but could not be inserted automatically. Please check the editor.');
                }
            }
        });
        </script>
        <?php
    }
    
    /**
     * Generate Terport content via AJAX
     */
    public function generate_terport_content() {
        check_ajax_referer('simple_generator_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Access denied');
        }
        
        $post_id = intval($_POST['post_id'] ?? 0);
        $title = sanitize_text_field($_POST['title'] ?? '');
        $type = sanitize_text_field($_POST['type'] ?? '');
        $description = sanitize_textarea_field($_POST['description'] ?? '');
        
        if (!$title || !$type || !$description) {
            wp_send_json_error('Missing required fields');
        }
        
        // Generate content using OpenRouter
        $content = $this->generate_comprehensive_content($title, $type, $description);
        
        if (is_wp_error($content)) {
            wp_send_json_error($content->get_error_message());
        }
        
        // Update post meta
        update_post_meta($post_id, '_terport_type', $type);
        update_post_meta($post_id, '_terport_description', $description);
        update_post_meta($post_id, '_terport_generated_date', time());
        
        wp_send_json_success(array(
            'content' => $content,
            'type' => $type,
            'description' => $description
        ));
    }
    
    /**
     * Generate comprehensive Terport content
     */
    private function generate_comprehensive_content($title, $type, $description) {
        $system_prompt = $this->get_system_prompt_for_type($type);
        
        $user_prompt = "Create a comprehensive {$type} titled '{$title}'. 

Scope: {$description}

Requirements:
1. Generate a complete, well-structured document ready for publication
2. Use proper HTML formatting with headings, lists, and paragraphs
3. Include relevant scientific information and current research
4. Add proper citations where appropriate
5. Make it engaging and informative for both professionals and educated consumers
6. Length should be substantial (2000-4000 words) but concise and valuable
7. Use professional yet accessible language
8. Include actionable insights and conclusions

Create the full content as a single HTML document that can be directly inserted into a WordPress post editor.";

        $response_format = array(
            'type' => 'json_schema',
            'json_schema' => array(
                'name' => 'terport_content_generation',
                'strict' => true,
                'schema' => array(
                    'type' => 'object',
                    'properties' => array(
                        'content' => array(
                            'type' => 'string',
                            'description' => 'Complete HTML content for the Terport, ready for WordPress editor'
                        ),
                        'executive_summary' => array(
                            'type' => 'string',
                            'description' => 'Brief 2-3 sentence summary of the key findings'
                        ),
                        'word_count' => array(
                            'type' => 'integer',
                            'description' => 'Approximate word count of the generated content'
                        )
                    ),
                    'required' => array('content', 'executive_summary', 'word_count'),
                    'additionalProperties' => false
                )
            )
        );
        
        $response = $this->openrouter_api->make_api_request($system_prompt, $user_prompt, $response_format);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        return $response['content'] ?? '';
    }
    
    /**
     * Get system prompt for each type
     */
    private function get_system_prompt_for_type($type) {
        $prompts = array(
            'literature_review' => "You are an expert scientific researcher specializing in terpene and cannabis research. Create comprehensive literature reviews that synthesize current scientific knowledge, identify research gaps, and provide evidence-based conclusions.",
            
            'product_evaluation' => "You are an expert cannabis product analyst with deep knowledge of terpene profiles, quality assessment methodologies, and therapeutic applications. Provide detailed, objective product evaluations based on scientific analysis.",
            
            'product_recommendations' => "You are a trusted cannabis consultant with expertise in terpene therapeutics and product matching. Provide science-based product recommendations with clear rationale and safety considerations.",
            
            'clinical_analysis' => "You are a clinical researcher specializing in cannabinoid and terpene therapeutics. Analyze clinical data, safety profiles, and therapeutic applications with medical precision.",
            
            'regulatory_update' => "You are a regulatory affairs expert in the cannabis and botanical industry. Provide clear, accurate updates on regulatory changes, compliance requirements, and industry implications.",
            
            'market_analysis' => "You are a cannabis industry analyst with expertise in market trends, consumer behavior, and business intelligence. Provide data-driven market insights and strategic analysis."
        );
        
        return $prompts[$type] ?? "You are an expert researcher specializing in terpene science. Provide accurate, well-researched content based on current scientific evidence.";
    }
    
    /**
     * Save Terport meta data
     */
    public function save_terport_meta($post_id) {
        if (!isset($_POST['terpedia_simple_generator_nonce']) || 
            !wp_verify_nonce($_POST['terpedia_simple_generator_nonce'], 'terpedia_simple_generator_nonce')) {
            return;
        }
        
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        if (get_post_type($post_id) !== 'terport') {
            return;
        }
        
        // Save type and description
        if (isset($_POST['terport_type'])) {
            update_post_meta($post_id, '_terport_type', sanitize_text_field($_POST['terport_type']));
        }
        
        if (isset($_POST['terport_description'])) {
            update_post_meta($post_id, '_terport_description', sanitize_textarea_field($_POST['terport_description']));
        }
    }
    
    /**
     * Enqueue editor scripts
     */
    public function enqueue_editor_scripts($hook) {
        global $post;
        
        if (($hook == 'post-new.php' || $hook == 'post.php') && $post && $post->post_type === 'terport') {
            wp_enqueue_script('jquery');
        }
    }
}

// Initialize the simple editor
new Terpedia_Simple_Terport_Editor();