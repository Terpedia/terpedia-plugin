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
        add_action('wp_ajax_analyze_terpene_profile', array($this, 'ajax_analyze_terpenes'));
        add_action('wp_ajax_nopriv_analyze_terpene_profile', array($this, 'ajax_analyze_terpenes'));
        
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
        
        // Flush rewrite rules if needed
        $this->maybe_flush_rewrite_rules();
    }
    
    /**
     * Flush rewrite rules if our custom rules don't exist
     */
    private function maybe_flush_rewrite_rules() {
        $rules = get_option('rewrite_rules');
        if (!$rules || !isset($rules['^add-terproduct/?$'])) {
            flush_rewrite_rules(false);
        }
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
        <div class="terpedia-camera-interface">
            <!-- Camera Section (Top Half) -->
            <div class="camera-section">
                <div class="camera-header">
                    <h1>📱 Terproduct Scanner</h1>
                    <p>Tap the camera view to capture and analyze products</p>
                </div>
                
                <!-- Live Camera Feed -->
                <div class="camera-container">
                    <video id="camera-stream" autoplay playsinline muted></video>
                    <canvas id="capture-canvas" style="display: none;"></canvas>
                    
                    <!-- Tap to capture overlay -->
                    <div class="capture-overlay" id="capture-overlay">
                        <div class="crosshair">
                            <div class="crosshair-h"></div>
                            <div class="crosshair-v"></div>
                        </div>
                        <div class="capture-hint">
                            <span class="pulse-dot"></span>
                            Tap to capture
                        </div>
                    </div>
                    
                    <!-- No extra controls - only tap to capture -->
                    
                    <!-- Processing indicator -->
                    <div class="processing-indicator" id="processing-indicator" style="display: none;">
                        <div class="spinner"></div>
                        <span id="processing-text">Analyzing...</span>
                    </div>
                </div>
                
                <!-- Recent captures -->
                <div class="capture-thumbnails" id="capture-thumbnails">
                    <!-- Thumbnails will be added dynamically -->
                </div>
            </div>
            
            <!-- Analysis Results Section (Bottom Half) -->
            <div class="analysis-section">
                <div class="analysis-header">
                    <h2>🤖 AI Analysis Results</h2>
                    <div class="analysis-status" id="analysis-status">
                        <span class="status-text">Ready to analyze</span>
                        <div class="confidence-meter" id="confidence-meter" style="display: none;">
                            <div class="confidence-bar">
                                <div class="confidence-fill" style="width: 0%;"></div>
                            </div>
                            <span class="confidence-value">0%</span>
                        </div>
                    </div>
                </div>
                
                <div class="analysis-results" id="analysis-results">
                    <div class="analysis-placeholder">
                        <div class="placeholder-icon">🔍</div>
                        <p>Capture a product to see AI analysis</p>
                        <small>Product name, manufacturer, ingredients, and terpenes will appear here</small>
                    </div>
                </div>
                
                <!-- Auto-generated product card -->
                <div class="product-card" id="product-card" style="display: none;">
                    <div class="product-info">
                        <div class="product-header">
                            <h3 class="product-name" id="detected-name">Product Name</h3>
                            <span class="product-brand" id="detected-brand">Brand</span>
                        </div>
                        
                        <div class="product-details">
                            <div class="detail-group">
                                <label>📦 Product Type</label>
                                <span id="detected-type">Unknown</span>
                            </div>
                            
                            <div class="detail-group">
                                <label>📏 Quantity</label>
                                <span id="detected-quantity">Unknown</span>
                            </div>
                            
                            <div class="detail-group">
                                <label>🧪 Primary Ingredients</label>
                                <div class="ingredients-list" id="detected-ingredients">
                                    <!-- Ingredients will be populated -->
                                </div>
                            </div>
                            
                            <div class="detail-group">
                                <label>🌿 Detected Terpenes</label>
                                <div class="terpenes-list" id="detected-terpenes">
                                    <!-- Terpenes will be populated -->
                                </div>
                            </div>
                        </div>
                        
                        <div class="auto-save-status" id="auto-save-status" style="display: none;">
                            <!-- Auto-save status will be displayed here -->
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Hidden form for data submission -->
            <form id="product-data-form" style="display: none;">
                <?php wp_nonce_field('terpedia_frontend_nonce', 'frontend_nonce'); ?>
                <input type="hidden" id="analysis-data" name="analysis_data">
                <input type="hidden" id="photo-data" name="photo_data">
                <input type="hidden" id="confidence-score" name="confidence_score">
            </form>
        </div>
        
        <style>
        .terpedia-camera-interface {
            display: flex;
            flex-direction: column;
            height: 100vh;
            background: #000;
            color: #fff;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        
        .camera-section {
            flex: 1;
            display: flex;
            flex-direction: column;
            background: #000;
        }
        
        .camera-header {
            padding: 1rem;
            text-align: center;
            background: rgba(0,0,0,0.8);
            border-bottom: 1px solid #333;
        }
        
        .camera-header h1 {
            margin: 0 0 0.5rem 0;
            font-size: 1.5rem;
            color: #fff;
        }
        
        .camera-header p {
            margin: 0;
            color: #ccc;
            font-size: 0.9rem;
        }
        
        .camera-container {
            flex: 1;
            position: relative;
            overflow: hidden;
        }
        
        #camera-stream {
            width: 100%;
            height: 100%;
            object-fit: cover;
            cursor: pointer;
        }
        
        .capture-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            pointer-events: none;
        }
        
        .crosshair {
            position: relative;
            width: 200px;
            height: 200px;
            border: 2px solid rgba(255,255,255,0.8);
            border-radius: 8px;
        }
        
        .crosshair-h, .crosshair-v {
            position: absolute;
            background: rgba(255,255,255,0.6);
        }
        
        .crosshair-h {
            top: 50%;
            left: 25%;
            right: 25%;
            height: 1px;
            transform: translateY(-50%);
        }
        
        .crosshair-v {
            left: 50%;
            top: 25%;
            bottom: 25%;
            width: 1px;
            transform: translateX(-50%);
        }
        
        .capture-hint {
            margin-top: 2rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #fff;
            font-size: 1.1rem;
        }
        
        .pulse-dot {
            width: 8px;
            height: 8px;
            background: #00ff00;
            border-radius: 50%;
            animation: pulse 1.5s infinite;
        }
        
        @keyframes pulse {
            0% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.5; transform: scale(1.2); }
            100% { opacity: 1; transform: scale(1); }
        }
        
        .camera-controls {
            position: absolute;
            top: 1rem;
            right: 1rem;
            display: flex;
            gap: 0.5rem;
        }
        
        .camera-controls button {
            width: 44px;
            height: 44px;
            border: none;
            background: rgba(0,0,0,0.6);
            color: white;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .processing-indicator {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1rem;
            background: rgba(0,0,0,0.8);
            padding: 2rem;
            border-radius: 8px;
        }
        
        .spinner {
            width: 32px;
            height: 32px;
            border: 3px solid rgba(255,255,255,0.3);
            border-top: 3px solid #fff;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .capture-thumbnails {
            padding: 0.5rem;
            display: flex;
            gap: 0.5rem;
            overflow-x: auto;
            background: rgba(0,0,0,0.5);
        }
        
        .thumbnail {
            width: 60px;
            height: 60px;
            border-radius: 4px;
            overflow: hidden;
            flex-shrink: 0;
            border: 2px solid transparent;
        }
        
        .thumbnail.active {
            border-color: #00ff00;
        }
        
        .thumbnail img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .analysis-section {
            flex: 1;
            background: #fff;
            color: #333;
            overflow-y: auto;
        }
        
        .analysis-header {
            padding: 1rem;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .analysis-header h2 {
            margin: 0;
            font-size: 1.3rem;
        }
        
        .analysis-status {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .confidence-meter {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .confidence-bar {
            width: 80px;
            height: 8px;
            background: #eee;
            border-radius: 4px;
            overflow: hidden;
        }
        
        .confidence-fill {
            height: 100%;
            background: linear-gradient(90deg, #ff4444, #ffaa44, #44ff44);
            transition: width 0.3s ease;
        }
        
        .analysis-results {
            padding: 1rem;
        }
        
        .analysis-placeholder {
            text-align: center;
            color: #666;
            padding: 2rem;
        }
        
        .placeholder-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
        
        .product-card {
            margin: 1rem;
            background: #f9f9f9;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .product-info {
            padding: 1rem;
        }
        
        .product-header {
            margin-bottom: 1rem;
        }
        
        .product-name {
            margin: 0 0 0.25rem 0;
            font-size: 1.4rem;
            color: #333;
        }
        
        .product-brand {
            color: #666;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .product-details {
            display: grid;
            gap: 1rem;
        }
        
        .detail-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 0.25rem;
            color: #555;
        }
        
        .ingredients-list, .terpenes-list {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }
        
        .ingredient-tag, .terpene-tag {
            background: #e3f2fd;
            color: #1976d2;
            padding: 0.25rem 0.5rem;
            border-radius: 12px;
            font-size: 0.8rem;
        }
        
        .terpene-tag {
            background: #e8f5e8;
            color: #2e7d32;
        }
        
        .product-actions {
            margin-top: 1.5rem;
            text-align: center;
        }
        
        .btn-save {
            background: #4caf50;
            color: white;
            border: none;
            padding: 0.75rem 2rem;
            border-radius: 25px;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-save:hover {
            background: #45a049;
            transform: translateY(-1px);
        }
        
        .save-status {
            margin-top: 0.5rem;
            font-size: 0.9rem;
        }
        
        .save-status.success {
            color: #4caf50;
        }
        
        .save-status.error {
            color: #f44336;
        }
        </style>
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
        
        // Check if OpenRouter handler exists
        if (!class_exists('Terpedia_OpenRouter_API_Handler')) {
            wp_send_json_error('AI analysis system not available');
            return;
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
                    // Use vision API if available, otherwise use standard API
                    if (method_exists($openrouter_handler, 'analyze_product_image')) {
                        $result = $openrouter_handler->analyze_product_image($temp_file);
                    } else {
                        // Fallback to text-based analysis
                        $prompt = "Analyze this cannabis product image. Extract:\n1. Product Name\n2. Brand/Manufacturer\n3. Product Type\n4. Ingredients list\n5. Quantity/Size\n\nProvide response as: Product Name: [name]\nBrand: [brand]\nType: [type]\nIngredients: [list]\nQuantity: [amount]";
                        $result = $openrouter_handler->make_api_call($prompt, array(
                            'model' => 'meta-llama/llama-3.2-11b-vision-instruct:free',
                            'temperature' => 0.3
                        ));
                    }
                    
                    if ($result && !isset($result['error'])) {
                        $analysis_results[] = $result;
                        
                        // Extract structured data
                        $parsed = $this->parse_analysis_result($result['content'] ?? '');
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
     * AJAX handler for terpene analysis based on ingredients
     */
    public function ajax_analyze_terpenes() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'terpedia_frontend_nonce')) {
            wp_send_json_error('Invalid nonce');
        }
        
        // Get ingredients
        $ingredients = isset($_POST['ingredients']) ? json_decode($_POST['ingredients'], true) : array();
        
        if (empty($ingredients)) {
            wp_send_json_error('No ingredients provided');
        }
        
        // Use existing OpenRouter analysis for terpene prediction
        $openrouter_handler = new Terpedia_OpenRouter_API_Handler();
        
        try {
            // Create a prompt for terpene analysis based on ingredients
            $ingredients_text = implode(', ', $ingredients);
            $prompt = "Based on these cannabis product ingredients: {$ingredients_text}\n\nAnalyze and predict the likely terpene profile. For each terpene, provide:\n1. Terpene name\n2. Estimated concentration percentage\n3. Source/reason for presence\n\nFocus on the most common cannabis terpenes: limonene, myrcene, linalool, pinene, caryophyllene, humulene, terpinolene, ocimene.\n\nProvide response in JSON format: {\"terpenes\": [{\"name\": \"Limonene\", \"concentration\": \"2.3%\", \"source\": \"citrus terpenes in ingredients\"}]}";
            
            // Make API call
            $response = $openrouter_handler->make_api_call($prompt, array(
                'model' => 'meta-llama/llama-3.2-11b-vision-instruct:free',
                'temperature' => 0.3,
                'max_tokens' => 1000
            ));
            
            if ($response && !isset($response['error'])) {
                // Try to parse JSON response
                $content = $response['content'] ?? '';
                
                // Extract JSON from response
                if (preg_match('/\{[^}]*"terpenes"[^}]*\}/', $content, $matches)) {
                    $json_data = json_decode($matches[0], true);
                    
                    if ($json_data && isset($json_data['terpenes'])) {
                        wp_send_json_success(array(
                            'terpenes' => $json_data['terpenes'],
                            'raw_response' => $content,
                            'confidence' => 75
                        ));
                        return;
                    }
                }
                
                // Fallback: parse text response
                $terpenes = $this->parse_terpenes_from_text($content);
                wp_send_json_success(array(
                    'terpenes' => $terpenes,
                    'raw_response' => $content,
                    'confidence' => 65
                ));
                
            } else {
                throw new Exception('API call failed');
            }
            
        } catch (Exception $e) {
            error_log('Terpene analysis error: ' . $e->getMessage());
            
            // Provide fallback terpenes based on common patterns
            $fallback_terpenes = $this->get_fallback_terpenes($ingredients);
            
            wp_send_json_success(array(
                'terpenes' => $fallback_terpenes,
                'confidence' => 40,
                'note' => 'Using fallback analysis'
            ));
        }
    }
    
    /**
     * Parse terpenes from text response
     */
    private function parse_terpenes_from_text($text) {
        $terpenes = array();
        $common_terpenes = array('limonene', 'myrcene', 'linalool', 'pinene', 'caryophyllene', 'humulene', 'terpinolene', 'ocimene');
        
        foreach ($common_terpenes as $terpene) {
            if (stripos($text, $terpene) !== false) {
                // Try to extract percentage
                $concentration = 'Present';
                if (preg_match('/' . preg_quote($terpene, '/') . '[^\d]*(\d+(?:\.\d+)?)\s*%/i', $text, $matches)) {
                    $concentration = $matches[1] . '%';
                }
                
                $terpenes[] = array(
                    'name' => ucfirst($terpene),
                    'concentration' => $concentration,
                    'source' => 'ingredient_analysis'
                );
            }
        }
        
        return $terpenes;
    }
    
    /**
     * Get fallback terpenes based on ingredient patterns
     */
    private function get_fallback_terpenes($ingredients) {
        $terpenes = array();
        
        // Common ingredient -> terpene mappings
        $mappings = array(
            'citrus' => array('Limonene', '1-3%'),
            'lemon' => array('Limonene', '2-4%'),
            'orange' => array('Limonene', '1-2%'),
            'lavender' => array('Linalool', '1-2%'),
            'pine' => array('Pinene', '1-3%'),
            'pepper' => array('Caryophyllene', '0.5-1%'),
            'clove' => array('Caryophyllene', '1-2%'),
            'hops' => array('Humulene', '0.5-1%'),
            'cannabis' => array('Myrcene', '0.5-2%'),
            'mango' => array('Myrcene', '0.2-0.5%')
        );
        
        foreach ($ingredients as $ingredient) {
            $ingredient_lower = strtolower($ingredient);
            
            foreach ($mappings as $pattern => $terpene_info) {
                if (stripos($ingredient_lower, $pattern) !== false) {
                    $terpenes[] = array(
                        'name' => $terpene_info[0],
                        'concentration' => $terpene_info[1],
                        'source' => 'ingredient_pattern'
                    );
                }
            }
        }
        
        // Add default cannabis terpenes if none found
        if (empty($terpenes)) {
            $terpenes[] = array(
                'name' => 'Myrcene',
                'concentration' => '0.5-1%',
                'source' => 'default_profile'
            );
        }
        
        return $terpenes;
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

// System will be initialized from terpedia.php main file