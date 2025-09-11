<?php
/**
 * Frontend Terproduct Creator
 * Allows users to create terproducts from the frontend with camera access and AI analysis
 */

if (!defined('ABSPATH')) {
    exit;
}

class Terpedia_Frontend_Terproduct_Creator {
    
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('template_redirect', array($this, 'handle_add_product_page'));
        add_filter('query_vars', array($this, 'add_query_vars'));
        
        // AJAX handlers for frontend product creation
        add_action('wp_ajax_create_terproduct_frontend', array($this, 'ajax_create_terproduct'));
        add_action('wp_ajax_nopriv_create_terproduct_frontend', array($this, 'ajax_create_terproduct'));
        add_action('wp_ajax_analyze_frontend_photos', array($this, 'ajax_analyze_photos'));
        add_action('wp_ajax_nopriv_analyze_frontend_photos', array($this, 'ajax_analyze_photos'));
        
        // Add rewrite rules
        add_action('init', array($this, 'add_rewrite_rules'));
    }
    
    public function init() {
        // Add capability check for frontend submissions
        if (!current_user_can('edit_posts')) {
            // Allow non-logged-in users to create products but require moderation
            add_filter('wp_insert_post_data', array($this, 'set_pending_status'), 10, 2);
        }
    }
    
    /**
     * Add rewrite rules for frontend pages
     */
    public function add_rewrite_rules() {
        add_rewrite_rule('^add-terproduct/?$', 'index.php?add_terproduct_page=1', 'top');
        add_rewrite_rule('^add-terproduct/success/?$', 'index.php?add_terproduct_success=1', 'top');
    }
    
    /**
     * Add query vars
     */
    public function add_query_vars($vars) {
        $vars[] = 'add_terproduct_page';
        $vars[] = 'add_terproduct_success';
        return $vars;
    }
    
    /**
     * Enqueue scripts and styles
     */
    public function enqueue_scripts() {
        if (get_query_var('add_terproduct_page') || get_query_var('add_terproduct_success')) {
            // Enqueue existing terproduct styles and scripts
            wp_enqueue_style(
                'terpedia-terproducts',
                plugin_dir_url(__FILE__) . '../assets/css/enhanced-terproducts.css',
                array(),
                '3.9.4'
            );
            
            wp_enqueue_style(
                'terpedia-frontend-creator',
                plugin_dir_url(__FILE__) . '../assets/css/frontend-creator.css',
                array('terpedia-terproducts'),
                '3.9.4'
            );
            
            wp_enqueue_script(
                'terpedia-terproducts',
                plugin_dir_url(__FILE__) . '../assets/js/enhanced-terproducts.js',
                array('jquery'),
                '3.9.4',
                true
            );
            
            wp_enqueue_script(
                'terpedia-frontend-creator',
                plugin_dir_url(__FILE__) . '../assets/js/frontend-creator.js',
                array('jquery', 'terpedia-terproducts'),
                '3.9.4',
                true
            );
            
            // Localize script for AJAX
            wp_localize_script('terpedia-frontend-creator', 'terpediaFrontend', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('terpedia_frontend_nonce'),
                'messages' => array(
                    'analyzing' => 'Analyzing product photos...',
                    'creating' => 'Creating product...',
                    'success' => 'Product created successfully!',
                    'error' => 'An error occurred. Please try again.',
                    'photos_required' => 'Please add at least one photo.',
                    'analysis_required' => 'Please analyze the photos first.',
                )
            ));
        }
    }
    
    /**
     * Handle add product page template
     */
    public function handle_add_product_page() {
        if (get_query_var('add_terproduct_page')) {
            $this->render_add_product_page();
            exit;
        }
        
        if (get_query_var('add_terproduct_success')) {
            $this->render_success_page();
            exit;
        }
    }
    
    /**
     * Render add product page
     */
    private function render_add_product_page() {
        get_header();
        ?>
        <div class="terpedia-frontend-creator">
            <div class="creator-container">
                <div class="creator-header">
                    <h1>📱 Add New Terproduct</h1>
                    <p>Capture photos of your product and let AI analyze its ingredients and terpene profile</p>
                </div>
                
                <form id="frontend-terproduct-form" class="terproduct-creation-form" enctype="multipart/form-data">
                    <?php wp_nonce_field('terpedia_frontend_nonce', 'frontend_nonce'); ?>
                    
                    <!-- Step 1: Photo Capture -->
                    <div class="creation-step active" data-step="1">
                        <h2>Step 1: Capture Product Photos</h2>
                        
                        <div class="photo-capture-section">
                            <div class="camera-interface">
                                <div class="camera-controls">
                                    <button type="button" id="open-camera-frontend" class="btn-primary">
                                        📷 Open Camera
                                    </button>
                                    <button type="button" id="upload-photos-frontend" class="btn-secondary">
                                        📁 Upload Photos
                                    </button>
                                </div>
                                
                                <input type="file" 
                                       id="product-camera-frontend" 
                                       accept="image/*" 
                                       capture="environment" 
                                       multiple 
                                       style="display: none;">
                                       
                                <div class="camera-preview" id="camera-preview-frontend">
                                    <div class="preview-placeholder">
                                        <span class="icon">📸</span>
                                        <p>Tap camera or upload to add photos</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="photo-gallery" id="frontend-photo-gallery">
                                <!-- Photos will be dynamically added here -->
                            </div>
                            
                            <div class="step-actions">
                                <button type="button" id="analyze-photos-btn" class="btn-analyze" disabled>
                                    🤖 Analyze Photos
                                </button>
                                <button type="button" class="next-step-btn" data-next="2" disabled>
                                    Next Step →
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Step 2: AI Analysis Results -->
                    <div class="creation-step" data-step="2">
                        <h2>Step 2: AI Analysis Results</h2>
                        
                        <div class="analysis-section" id="analysis-results">
                            <div class="analysis-placeholder">
                                <p>Complete Step 1 to see AI analysis results</p>
                            </div>
                        </div>
                        
                        <div class="step-actions">
                            <button type="button" class="prev-step-btn" data-prev="1">
                                ← Previous Step
                            </button>
                            <button type="button" class="next-step-btn" data-next="3" disabled>
                                Next Step →
                            </button>
                        </div>
                    </div>
                    
                    <!-- Step 3: Product Details -->
                    <div class="creation-step" data-step="3">
                        <h2>Step 3: Product Details</h2>
                        
                        <div class="product-details-section">
                            <div class="form-group">
                                <label for="product-title">Product Name *</label>
                                <input type="text" 
                                       id="product-title" 
                                       name="product_title" 
                                       required 
                                       placeholder="Enter product name">
                            </div>
                            
                            <div class="form-group">
                                <label for="product-brand">Brand</label>
                                <input type="text" 
                                       id="product-brand" 
                                       name="product_brand" 
                                       placeholder="Enter brand name">
                            </div>
                            
                            <div class="form-group">
                                <label for="product-description">Description</label>
                                <textarea id="product-description" 
                                          name="product_description" 
                                          rows="4" 
                                          placeholder="Describe the product..."></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label for="product-category">Category</label>
                                <select id="product-category" name="product_category">
                                    <option value="">Select a category</option>
                                    <?php $this->render_category_options(); ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="ingredients-list">Ingredients (from AI analysis)</label>
                                <textarea id="ingredients-list" 
                                          name="ingredients_list" 
                                          rows="3" 
                                          readonly 
                                          placeholder="Will be populated from AI analysis"></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label for="user-email">Your Email *</label>
                                <input type="email" 
                                       id="user-email" 
                                       name="user_email" 
                                       required 
                                       placeholder="your@email.com"
                                       value="<?php echo is_user_logged_in() ? wp_get_current_user()->user_email : ''; ?>">
                                <small>We'll notify you when your product is approved</small>
                            </div>
                            
                            <div class="user-consent">
                                <label class="checkbox-label">
                                    <input type="checkbox" id="terms-consent" name="terms_consent" required>
                                    I agree to share this product information and photos with the Terpedia community
                                </label>
                            </div>
                        </div>
                        
                        <div class="step-actions">
                            <button type="button" class="prev-step-btn" data-prev="2">
                                ← Previous Step
                            </button>
                            <button type="submit" id="create-product-btn" class="btn-create">
                                🎯 Create Product
                            </button>
                        </div>
                    </div>
                    
                    <!-- Hidden fields for AI analysis data -->
                    <input type="hidden" id="analysis-data" name="analysis_data">
                    <input type="hidden" id="photo-data" name="photo_data">
                    <input type="hidden" id="confidence-score" name="confidence_score">
                </form>
                
                <!-- Progress indicator -->
                <div class="progress-indicator">
                    <div class="progress-step active" data-step="1">1</div>
                    <div class="progress-step" data-step="2">2</div>
                    <div class="progress-step" data-step="3">3</div>
                </div>
                
                <!-- Loading overlay -->
                <div class="loading-overlay" id="loading-overlay" style="display: none;">
                    <div class="loading-content">
                        <div class="spinner"></div>
                        <p id="loading-message">Processing...</p>
                    </div>
                </div>
            </div>
        </div>
        <?php
        get_footer();
    }
    
    /**
     * Render success page
     */
    private function render_success_page() {
        get_header();
        ?>
        <div class="terpedia-frontend-creator">
            <div class="creator-container">
                <div class="success-message">
                    <div class="success-icon">✅</div>
                    <h1>Product Submitted Successfully!</h1>
                    <p>Thank you for contributing to the Terpedia community. Your terproduct has been submitted for review.</p>
                    
                    <div class="success-details">
                        <h3>What happens next?</h3>
                        <ul>
                            <li>✨ Our AI has analyzed your product photos</li>
                            <li>👥 Community moderators will review your submission</li>
                            <li>📧 You'll receive an email notification when approved</li>
                            <li>🎉 Your product will appear in the Terpedia database</li>
                        </ul>
                    </div>
                    
                    <div class="success-actions">
                        <a href="<?php echo home_url('/add-terproduct/'); ?>" class="btn-primary">
                            Add Another Product
                        </a>
                        <a href="<?php echo get_post_type_archive_link('terpedia_terproduct'); ?>" class="btn-secondary">
                            Browse Terproducts
                        </a>
                        <a href="<?php echo home_url(); ?>" class="btn-secondary">
                            Return Home
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php
        get_footer();
    }
    
    /**
     * Render category options
     */
    private function render_category_options() {
        $categories = get_terms(array(
            'taxonomy' => 'terproduct_category',
            'hide_empty' => false
        ));
        
        if (!is_wp_error($categories) && !empty($categories)) {
            foreach ($categories as $category) {
                echo '<option value="' . esc_attr($category->term_id) . '">' . esc_html($category->name) . '</option>';
            }
        }
    }
    
    /**
     * AJAX handler for photo analysis
     */
    public function ajax_analyze_photos() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'terpedia_frontend_nonce')) {
            wp_send_json_error('Invalid nonce');
        }
        
        // Get photos data
        $photos = isset($_POST['photos']) ? $_POST['photos'] : array();
        
        if (empty($photos)) {
            wp_send_json_error('No photos provided');
        }
        
        // Use existing OpenRouter analysis system
        $openrouter_handler = new Terpedia_OpenRouter_API_Handler();
        
        $analysis_results = array();
        $combined_ingredients = array();
        $combined_terpenes = array();
        $total_confidence = 0;
        
        foreach ($photos as $photo_data) {
            // Decode base64 image data if needed
            if (strpos($photo_data, 'data:image') === 0) {
                $image_data = explode(',', $photo_data)[1];
                $image_binary = base64_decode($image_data);
                
                // Create temporary file for analysis
                $temp_file = tempnam(sys_get_temp_dir(), 'terproduct_') . '.jpg';
                file_put_contents($temp_file, $image_binary);
                
                try {
                    // Analyze with OpenRouter Vision API
                    $result = $openrouter_handler->analyze_product_image($temp_file);
                    
                    if ($result && !isset($result['error'])) {
                        $analysis_results[] = $result;
                        
                        // Extract structured data
                        $parsed = $this->parse_analysis_result($result['content']);
                        $combined_ingredients = array_merge($combined_ingredients, $parsed['ingredients']);
                        $combined_terpenes = array_merge($combined_terpenes, $parsed['terpenes']);
                        $total_confidence += $parsed['confidence'];
                    }
                    
                } catch (Exception $e) {
                    error_log('Frontend photo analysis error: ' . $e->getMessage());
                } finally {
                    // Clean up temp file
                    if (file_exists($temp_file)) {
                        unlink($temp_file);
                    }
                }
            }
        }
        
        // Calculate average confidence
        $avg_confidence = count($analysis_results) > 0 ? $total_confidence / count($analysis_results) : 0;
        
        // Remove duplicates and format results
        $combined_ingredients = array_unique($combined_ingredients);
        $unique_terpenes = array();
        foreach ($combined_terpenes as $terpene) {
            $unique_terpenes[$terpene['name']] = $terpene;
        }
        
        wp_send_json_success(array(
            'raw_analysis' => $analysis_results,
            'ingredients' => array_values($combined_ingredients),
            'terpenes' => array_values($unique_terpenes),
            'confidence' => round($avg_confidence, 1),
            'photo_count' => count($photos),
            'model_used' => 'meta-llama/llama-3.2-11b-vision-instruct:free'
        ));
    }
    
    /**
     * AJAX handler for creating terproduct
     */
    public function ajax_create_terproduct() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'terpedia_frontend_nonce')) {
            wp_send_json_error('Invalid nonce');
        }
        
        // Sanitize and validate input
        $product_title = sanitize_text_field($_POST['product_title']);
        $product_brand = sanitize_text_field($_POST['product_brand']);
        $product_description = sanitize_textarea_field($_POST['product_description']);
        $product_category = intval($_POST['product_category']);
        $ingredients_list = sanitize_textarea_field($_POST['ingredients_list']);
        $user_email = sanitize_email($_POST['user_email']);
        $analysis_data = $_POST['analysis_data'];
        $photo_data = $_POST['photo_data'];
        $confidence_score = floatval($_POST['confidence_score']);
        
        if (empty($product_title) || empty($user_email)) {
            wp_send_json_error('Missing required fields');
        }
        
        if (!isset($_POST['terms_consent'])) {
            wp_send_json_error('Please accept the terms and conditions');
        }
        
        // Create post data
        $post_status = current_user_can('publish_posts') ? 'publish' : 'pending';
        
        $post_data = array(
            'post_title' => $product_title,
            'post_content' => $product_description,
            'post_status' => $post_status,
            'post_type' => 'terpedia_terproduct',
            'post_author' => is_user_logged_in() ? get_current_user_id() : 1, // Default to admin if not logged in
            'meta_input' => array(
                '_extracted_brand' => $product_brand,
                '_ingredients_list' => $ingredients_list,
                '_ingredient_confidence' => $confidence_score,
                '_vision_analysis_raw' => $analysis_data,
                '_product_photos_data' => $photo_data,
                '_submitter_email' => $user_email,
                '_frontend_submission' => true,
                '_submission_date' => current_time('mysql')
            )
        );
        
        // Insert the post
        $post_id = wp_insert_post($post_data);
        
        if (is_wp_error($post_id)) {
            wp_send_json_error('Failed to create product: ' . $post_id->get_error_message());
        }
        
        // Set category if provided
        if ($product_category > 0) {
            wp_set_post_terms($post_id, array($product_category), 'terproduct_category');
        }
        
        // Store analysis data as post meta
        if (!empty($analysis_data)) {
            $parsed_analysis = json_decode($analysis_data, true);
            if ($parsed_analysis) {
                update_post_meta($post_id, '_detected_terpenes', $parsed_analysis['terpenes']);
                update_post_meta($post_id, '_analysis_confidence', $parsed_analysis['confidence']);
            }
        }
        
        // Send notification email if pending
        if ($post_status === 'pending') {
            $this->send_submission_notification($post_id, $user_email, $product_title);
        }
        
        wp_send_json_success(array(
            'post_id' => $post_id,
            'redirect_url' => home_url('/add-terproduct/success/'),
            'status' => $post_status
        ));
    }
    
    /**
     * Parse analysis result to extract structured data
     */
    private function parse_analysis_result($analysis_content) {
        $parsed = array(
            'ingredients' => array(),
            'terpenes' => array(),
            'confidence' => 80
        );
        
        // Extract ingredients
        if (preg_match('/Ingredients?:\s*([^\n]+)/i', $analysis_content, $matches)) {
            $ingredients_text = $matches[1];
            $ingredients = preg_split('/[,;]\s*/', $ingredients_text);
            $parsed['ingredients'] = array_map('trim', array_filter($ingredients));
        }
        
        // Extract terpenes with concentrations
        $terpene_patterns = array(
            'limonene', 'linalool', 'myrcene', 'pinene', 'caryophyllene', 
            'humulene', 'terpinolene', 'ocimene', 'eucalyptol', 'menthol'
        );
        
        foreach ($terpene_patterns as $terpene) {
            if (stripos($analysis_content, $terpene) !== false) {
                $concentration = 'Present';
                
                // Try to extract percentage
                if (preg_match('/' . preg_quote($terpene, '/') . '[^\d]*(\d+(?:\.\d+)?)\s*%/i', $analysis_content, $matches)) {
                    $concentration = $matches[1] . '%';
                }
                
                $parsed['terpenes'][] = array(
                    'name' => ucfirst($terpene),
                    'concentration' => $concentration,
                    'source' => 'vision_analysis'
                );
            }
        }
        
        return $parsed;
    }
    
    /**
     * Send submission notification
     */
    private function send_submission_notification($post_id, $user_email, $product_title) {
        $subject = 'Terproduct Submission Received - ' . $product_title;
        
        $message = "Thank you for submitting a terproduct to Terpedia!\n\n";
        $message .= "Product: " . $product_title . "\n";
        $message .= "Submission ID: " . $post_id . "\n\n";
        $message .= "Your submission is currently being reviewed by our community moderators. ";
        $message .= "You'll receive another email once it's approved and published.\n\n";
        $message .= "View all terproducts: " . get_post_type_archive_link('terpedia_terproduct') . "\n\n";
        $message .= "Thank you for contributing to the Terpedia community!\n";
        
        wp_mail($user_email, $subject, $message);
        
        // Also notify admin
        $admin_email = get_option('admin_email');
        $admin_subject = 'New Terproduct Submission - ' . $product_title;
        $admin_message = "A new terproduct has been submitted for review:\n\n";
        $admin_message .= "Product: " . $product_title . "\n";
        $admin_message .= "Submitter: " . $user_email . "\n";
        $admin_message .= "Edit: " . admin_url('post.php?post=' . $post_id . '&action=edit') . "\n";
        
        wp_mail($admin_email, $admin_subject, $admin_message);
    }
    
    /**
     * Set pending status for non-privileged users
     */
    public function set_pending_status($data, $postarr) {
        if ($data['post_type'] === 'terpedia_terproduct' && isset($postarr['meta_input']['_frontend_submission'])) {
            if (!current_user_can('publish_posts')) {
                $data['post_status'] = 'pending';
            }
        }
        return $data;
    }
}

// Initialize the frontend creator
new Terpedia_Frontend_Terproduct_Creator();