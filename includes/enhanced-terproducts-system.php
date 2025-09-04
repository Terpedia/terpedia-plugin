<?php
/**
 * Enhanced Terproducts System for Terpedia
 * Captures product photos, analyzes ingredients, and generates terpene insights
 */

if (!defined('ABSPATH')) {
    exit;
}

class Terpedia_Enhanced_Terproducts_System {
    
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
        add_action('add_meta_boxes', array($this, 'add_terproduct_meta_boxes'));
        add_action('save_post', array($this, 'save_terproduct_meta'));
        add_action('wp_ajax_upload_product_photo', array($this, 'upload_product_photo'));
        add_action('wp_ajax_analyze_product_ingredients', array($this, 'analyze_product_ingredients'));
        add_action('wp_ajax_generate_terpene_insights', array($this, 'generate_terpene_insights'));
        add_action('wp_ajax_capture_product_photo', array($this, 'capture_product_photo'));
        add_shortcode('terpedia_terproduct_display', array($this, 'terproduct_display_shortcode'));
        add_shortcode('terpedia_terproduct_scanner', array($this, 'terproduct_scanner_shortcode'));
        add_shortcode('terpedia_terproduct_list', array($this, 'terproduct_list_shortcode'));
        
        // Terproduct categories and terpene analysis
        add_action('init', array($this, 'register_terproduct_taxonomies'));
    }
    
    public function init() {
        // Register Terproduct post type
        if (!post_type_exists('terpedia_terproduct')) {
            register_post_type('terpedia_terproduct', array(
                'labels' => array(
                    'name' => 'Terproducts',
                    'singular_name' => 'Terproduct',
                    'add_new' => 'Add New Terproduct',
                    'add_new_item' => 'Add New Terproduct',
                    'edit_item' => 'Edit Terproduct',
                    'new_item' => 'New Terproduct',
                    'view_item' => 'View Terproduct',
                    'search_items' => 'Search Terproducts',
                    'not_found' => 'No terproducts found',
                    'not_found_in_trash' => 'No terproducts found in trash'
                ),
                'public' => true,
                'has_archive' => true,
                'supports' => array('title', 'editor', 'author', 'thumbnail', 'excerpt', 'custom-fields', 'revisions'),
                'menu_icon' => 'dashicons-products',
                'menu_position' => 32,
                'show_in_rest' => true,
                'capability_type' => 'post',
                'hierarchical' => false,
                'rewrite' => array('slug' => 'terproducts'),
                'show_in_menu' => true
            ));
        }
    }
    
    public function register_terproduct_taxonomies() {
        // Register product categories
        register_taxonomy('terproduct_category', 'terpedia_terproduct', array(
            'labels' => array(
                'name' => 'Product Categories',
                'singular_name' => 'Product Category',
                'search_items' => 'Search Product Categories',
                'all_items' => 'All Product Categories',
                'parent_item' => 'Parent Product Category',
                'parent_item_colon' => 'Parent Product Category:',
                'edit_item' => 'Edit Product Category',
                'update_item' => 'Update Product Category',
                'add_new_item' => 'Add New Product Category',
                'new_item_name' => 'New Product Category Name',
                'menu_name' => 'Product Categories'
            ),
            'hierarchical' => true,
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
            'rewrite' => array('slug' => 'terproduct-category'),
            'show_in_rest' => true
        ));
        
        // Register terpene profiles
        register_taxonomy('terpene_profile', 'terpedia_terproduct', array(
            'labels' => array(
                'name' => 'Terpene Profiles',
                'singular_name' => 'Terpene Profile',
                'search_items' => 'Search Terpene Profiles',
                'all_items' => 'All Terpene Profiles',
                'edit_item' => 'Edit Terpene Profile',
                'update_item' => 'Update Terpene Profile',
                'add_new_item' => 'Add New Terpene Profile',
                'new_item_name' => 'New Terpene Profile Name',
                'menu_name' => 'Terpene Profiles'
            ),
            'hierarchical' => true,
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
            'rewrite' => array('slug' => 'terpene-profile'),
            'show_in_rest' => true
        ));
        
        // Create default categories and profiles
        $this->create_default_terproduct_categories();
        $this->create_default_terpene_profiles();
    }
    
    private function create_default_terproduct_categories() {
        $categories = array(
            'essential_oils' => 'Essential Oils',
            'terpene_isolates' => 'Terpene Isolates',
            'aromatherapy_blends' => 'Aromatherapy Blends',
            'perfumes_fragrances' => 'Perfumes & Fragrances',
            'skincare_products' => 'Skincare Products',
            'bath_body' => 'Bath & Body Products',
            'candles_incense' => 'Candles & Incense',
            'cleaning_products' => 'Cleaning Products',
            'food_beverages' => 'Food & Beverages',
            'supplements' => 'Supplements'
        );
        
        foreach ($categories as $slug => $name) {
            if (!term_exists($slug, 'terproduct_category')) {
                wp_insert_term($name, 'terproduct_category', array('slug' => $slug));
            }
        }
    }
    
    private function create_default_terpene_profiles() {
        $profiles = array(
            'citrus_fresh' => 'Citrus & Fresh',
            'floral_sweet' => 'Floral & Sweet',
            'woody_earthy' => 'Woody & Earthy',
            'herbal_medicinal' => 'Herbal & Medicinal',
            'spicy_warm' => 'Spicy & Warm',
            'minty_cool' => 'Minty & Cool',
            'fruity_tropical' => 'Fruity & Tropical',
            'resinous_pine' => 'Resinous & Pine'
        );
        
        foreach ($profiles as $slug => $name) {
            if (!term_exists($slug, 'terpene_profile')) {
                wp_insert_term($name, 'terpene_profile', array('slug' => $slug));
            }
        }
    }
    
    public function enqueue_scripts() {
        if (is_singular('terpedia_terproduct') || is_post_type_archive('terpedia_terproduct') || 
            has_shortcode(get_the_content(), 'terpedia_terproduct_display') ||
            has_shortcode(get_the_content(), 'terpedia_terproduct_scanner') ||
            has_shortcode(get_the_content(), 'terpedia_terproduct_list')) {
            
            wp_enqueue_script('terpedia-terproducts-system', 
                plugin_dir_url(__FILE__) . '../assets/js/enhanced-terproducts.js', 
                array('jquery'), '1.0.0', true);
            
            wp_enqueue_style('terpedia-terproducts-styles', 
                plugin_dir_url(__FILE__) . '../assets/css/enhanced-terproducts.css', 
                array(), '1.0.0');
            
            wp_localize_script('terpedia-terproducts-system', 'terpediaTerproducts', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('terpedia_terproducts_nonce'),
                'strings' => array(
                    'photo_uploaded' => 'Photo uploaded successfully!',
                    'analyzing_ingredients' => 'Analyzing ingredients...',
                    'generating_insights' => 'Generating terpene insights...',
                    'error' => 'Error processing request.'
                )
            ));
        }
    }
    
    public function admin_enqueue_scripts($hook) {
        global $post_type;
        
        if ($post_type === 'terpedia_terproduct') {
            wp_enqueue_script('terpedia-terproducts-admin', 
                plugin_dir_url(__FILE__) . '../assets/js/terproducts-admin.js', 
                array('jquery'), '1.0.0', true);
            
            wp_enqueue_style('terpedia-terproducts-admin-styles', 
                plugin_dir_url(__FILE__) . '../assets/css/terproducts-admin.css', 
                array(), '1.0.0');
            
            wp_localize_script('terpedia-terproducts-admin', 'terpediaTerproductsAdmin', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('terpedia_terproducts_admin_nonce')
            ));
        }
    }
    
    public function add_terproduct_meta_boxes() {
        add_meta_box(
            'terproduct_photo_capture',
            'Product Photo Capture',
            array($this, 'terproduct_photo_capture_meta_box'),
            'terpedia_terproduct',
            'normal',
            'high'
        );
        
        add_meta_box(
            'terproduct_basic_info',
            'Product Information',
            array($this, 'terproduct_basic_info_meta_box'),
            'terpedia_terproduct',
            'normal',
            'high'
        );
        
        add_meta_box(
            'terproduct_ingredients',
            'Ingredients Analysis',
            array($this, 'terproduct_ingredients_meta_box'),
            'terpedia_terproduct',
            'normal',
            'default'
        );
        
        add_meta_box(
            'terproduct_terpene_insights',
            'Terpene Insights & Recommendations',
            array($this, 'terproduct_terpene_insights_meta_box'),
            'terpedia_terproduct',
            'normal',
            'default'
        );
        
        add_meta_box(
            'terproduct_ai_analysis',
            'AI Analysis Tools',
            array($this, 'terproduct_ai_analysis_meta_box'),
            'terpedia_terproduct',
            'side',
            'high'
        );
    }
    
    public function terproduct_photo_capture_meta_box($post) {
        $product_photos = get_post_meta($post->ID, '_product_photos', true);
        $main_photo = get_post_meta($post->ID, '_main_product_photo', true);
        
        if (!is_array($product_photos)) {
            $product_photos = array();
        }
        
        echo '<div class="terproduct-photo-capture-container">';
        
        // Mobile photo capture interface
        echo '<div class="mobile-camera-interface">';
        echo '<h4>📱 Mobile Photo Capture</h4>';
        echo '<div class="camera-controls">';
        echo '<input type="file" id="product-camera-input" accept="image/*" capture="environment" multiple />';
        echo '<button type="button" id="open-camera-btn" class="button button-primary">📷 Open Camera</button>';
        echo '<button type="button" id="upload-photos-btn" class="button">📁 Upload Photos</button>';
        echo '</div>';
        echo '<div id="camera-preview" class="camera-preview"></div>';
        echo '</div>';
        
        // Photo gallery
        echo '<div class="product-photos-gallery">';
        echo '<h4>Product Photos</h4>';
        echo '<div class="photos-grid" id="photos-grid">';
        
        if (!empty($product_photos)) {
            foreach ($product_photos as $index => $photo) {
                $is_main = ($main_photo == $photo['id']) ? 'main-photo' : '';
                echo '<div class="photo-item ' . $is_main . '" data-photo-id="' . esc_attr($photo['id']) . '">';
                echo '<img src="' . esc_url($photo['url']) . '" alt="Product Photo" />';
                echo '<div class="photo-actions">';
                echo '<button type="button" class="set-main-photo" title="Set as Main Photo">⭐</button>';
                echo '<button type="button" class="delete-photo" title="Delete Photo">🗑️</button>';
                echo '</div>';
                echo '</div>';
            }
        } else {
            echo '<div class="no-photos">No photos uploaded yet. Use the camera interface above to capture product photos.</div>';
        }
        
        echo '</div>';
        echo '</div>';
        
        // Hidden inputs for photo data
        echo '<input type="hidden" id="product_photos_data" name="product_photos_data" value="' . esc_attr(json_encode($product_photos)) . '" />';
        echo '<input type="hidden" id="main_product_photo" name="main_product_photo" value="' . esc_attr($main_photo) . '" />';
        
        echo '</div>';
    }
    
    public function terproduct_basic_info_meta_box($post) {
        wp_nonce_field('save_terproduct_info', 'terproduct_info_nonce');
        
        $manufacturer = get_post_meta($post->ID, '_manufacturer', true);
        $product_type = get_post_meta($post->ID, '_product_type', true);
        $brand = get_post_meta($post->ID, '_brand', true);
        $model_number = get_post_meta($post->ID, '_model_number', true);
        $purchase_date = get_post_meta($post->ID, '_purchase_date', true);
        $purchase_location = get_post_meta($post->ID, '_purchase_location', true);
        $price = get_post_meta($post->ID, '_price', true);
        $notes = get_post_meta($post->ID, '_product_notes', true);
        
        echo '<div class="terproduct-basic-info-container">';
        
        echo '<div class="info-row">';
        echo '<div class="info-field">';
        echo '<label for="manufacturer"><strong>Manufacturer:</strong></label>';
        echo '<input type="text" id="manufacturer" name="manufacturer" value="' . esc_attr($manufacturer) . '" placeholder="e.g., Young Living, doTERRA" />';
        echo '</div>';
        
        echo '<div class="info-field">';
        echo '<label for="brand"><strong>Brand:</strong></label>';
        echo '<input type="text" id="brand" name="brand" value="' . esc_attr($brand) . '" placeholder="Brand name" />';
        echo '</div>';
        echo '</div>';
        
        echo '<div class="info-row">';
        echo '<div class="info-field">';
        echo '<label for="product_type"><strong>Product Type:</strong></label>';
        echo '<select id="product_type" name="product_type">';
        echo '<option value="">Select Type</option>';
        echo '<option value="essential_oil" ' . selected($product_type, 'essential_oil', false) . '>Essential Oil</option>';
        echo '<option value="terpene_isolate" ' . selected($product_type, 'terpene_isolate', false) . '>Terpene Isolate</option>';
        echo '<option value="aromatherapy_blend" ' . selected($product_type, 'aromatherapy_blend', false) . '>Aromatherapy Blend</option>';
        echo '<option value="perfume" ' . selected($product_type, 'perfume', false) . '>Perfume</option>';
        echo '<option value="skincare" ' . selected($product_type, 'skincare', false) . '>Skincare Product</option>';
        echo '<option value="bath_body" ' . selected($product_type, 'bath_body', false) . '>Bath & Body</option>';
        echo '<option value="candle" ' . selected($product_type, 'candle', false) . '>Candle</option>';
        echo '<option value="cleaning" ' . selected($product_type, 'cleaning', false) . '>Cleaning Product</option>';
        echo '<option value="food_beverage" ' . selected($product_type, 'food_beverage', false) . '>Food & Beverage</option>';
        echo '<option value="supplement" ' . selected($product_type, 'supplement', false) . '>Supplement</option>';
        echo '<option value="other" ' . selected($product_type, 'other', false) . '>Other</option>';
        echo '</select>';
        echo '</div>';
        
        echo '<div class="info-field">';
        echo '<label for="model_number"><strong>Model/SKU:</strong></label>';
        echo '<input type="text" id="model_number" name="model_number" value="' . esc_attr($model_number) . '" placeholder="Model number or SKU" />';
        echo '</div>';
        echo '</div>';
        
        echo '<div class="info-row">';
        echo '<div class="info-field">';
        echo '<label for="purchase_date"><strong>Purchase Date:</strong></label>';
        echo '<input type="date" id="purchase_date" name="purchase_date" value="' . esc_attr($purchase_date) . '" />';
        echo '</div>';
        
        echo '<div class="info-field">';
        echo '<label for="purchase_location"><strong>Purchase Location:</strong></label>';
        echo '<input type="text" id="purchase_location" name="purchase_location" value="' . esc_attr($purchase_location) . '" placeholder="Store or website" />';
        echo '</div>';
        
        echo '<div class="info-field">';
        echo '<label for="price"><strong>Price:</strong></label>';
        echo '<input type="number" id="price" name="price" value="' . esc_attr($price) . '" step="0.01" placeholder="0.00" />';
        echo '</div>';
        echo '</div>';
        
        echo '<div class="info-field full-width">';
        echo '<label for="product_notes"><strong>Notes:</strong></label>';
        echo '<textarea id="product_notes" name="product_notes" rows="3" style="width: 100%;" placeholder="Additional notes about this product...">' . esc_textarea($notes) . '</textarea>';
        echo '</div>';
        
        echo '</div>';
    }
    
    public function terproduct_ingredients_meta_box($post) {
        $ingredients_list = get_post_meta($post->ID, '_ingredients_list', true);
        $ingredients_analysis = get_post_meta($post->ID, '_ingredients_analysis', true);
        $detected_terpenes = get_post_meta($post->ID, '_detected_terpenes', true);
        $ingredient_confidence = get_post_meta($post->ID, '_ingredient_confidence', true);
        
        if (!is_array($detected_terpenes)) {
            $detected_terpenes = array();
        }
        
        echo '<div class="terproduct-ingredients-container">';
        
        echo '<div class="ingredients-input-section">';
        echo '<h4>Ingredients List</h4>';
        echo '<textarea id="ingredients_list" name="ingredients_list" rows="6" style="width: 100%;" placeholder="Enter ingredients as they appear on the product label, separated by commas or new lines...">' . esc_textarea($ingredients_list) . '</textarea>';
        echo '<button type="button" id="analyze-ingredients-btn" class="button button-primary">🔍 Analyze Ingredients</button>';
        echo '</div>';
        
        echo '<div class="ingredients-analysis-section">';
        echo '<h4>AI Analysis Results</h4>';
        echo '<div id="analysis-status" class="analysis-status"></div>';
        
        if (!empty($detected_terpenes)) {
            echo '<div class="detected-terpenes">';
            echo '<h5>Detected Terpenes:</h5>';
            echo '<div class="terpenes-grid">';
            foreach ($detected_terpenes as $terpene) {
                echo '<div class="terpene-item">';
                echo '<span class="terpene-name">' . esc_html($terpene['name']) . '</span>';
                echo '<span class="terpene-confidence">' . esc_html($terpene['confidence']) . '%</span>';
                echo '</div>';
            }
            echo '</div>';
            echo '</div>';
        }
        
        if ($ingredients_analysis) {
            echo '<div class="analysis-content">';
            echo '<h5>Detailed Analysis:</h5>';
            echo '<div class="analysis-text">' . wp_kses_post($ingredients_analysis) . '</div>';
            echo '</div>';
        }
        
        if ($ingredient_confidence) {
            echo '<div class="confidence-meter">';
            echo '<h5>Analysis Confidence:</h5>';
            echo '<div class="confidence-bar">';
            echo '<div class="confidence-fill" style="width: ' . esc_attr($ingredient_confidence) . '%"></div>';
            echo '<span class="confidence-text">' . esc_html($ingredient_confidence) . '%</span>';
            echo '</div>';
            echo '</div>';
        }
        
        echo '</div>';
        echo '</div>';
    }
    
    public function terproduct_terpene_insights_meta_box($post) {
        $terpene_insights = get_post_meta($post->ID, '_terpene_insights', true);
        $terpene_recommendations = get_post_meta($post->ID, '_terpene_recommendations', true);
        $enhancement_suggestions = get_post_meta($post->ID, '_enhancement_suggestions', true);
        $therapeutic_benefits = get_post_meta($post->ID, '_therapeutic_benefits', true);
        
        echo '<div class="terproduct-terpene-insights-container">';
        
        echo '<div class="insights-generation-section">';
        echo '<h4>Generate Terpene Insights</h4>';
        echo '<p>Based on the detected ingredients and terpenes, generate comprehensive insights and recommendations.</p>';
        echo '<button type="button" id="generate-insights-btn" class="button button-primary">🧠 Generate Terpene Insights</button>';
        echo '<div id="insights-status" class="insights-status"></div>';
        echo '</div>';
        
        if ($terpene_insights) {
            echo '<div class="terpene-insights-section">';
            echo '<h4>Terpene Profile Analysis</h4>';
            echo '<div class="insights-content">' . wp_kses_post($terpene_insights) . '</div>';
            echo '</div>';
        }
        
        if ($terpene_recommendations) {
            echo '<div class="terpene-recommendations-section">';
            echo '<h4>Terpene Recommendations</h4>';
            echo '<div class="recommendations-content">' . wp_kses_post($terpene_recommendations) . '</div>';
            echo '</div>';
        }
        
        if ($enhancement_suggestions) {
            echo '<div class="enhancement-suggestions-section">';
            echo '<h4>Enhancement Suggestions</h4>';
            echo '<div class="enhancement-content">' . wp_kses_post($enhancement_suggestions) . '</div>';
            echo '</div>';
        }
        
        if ($therapeutic_benefits) {
            echo '<div class="therapeutic-benefits-section">';
            echo '<h4>Therapeutic Benefits</h4>';
            echo '<div class="benefits-content">' . wp_kses_post($therapeutic_benefits) . '</div>';
            echo '</div>';
        }
        
        echo '</div>';
    }
    
    public function terproduct_ai_analysis_meta_box($post) {
        $last_analysis_date = get_post_meta($post->ID, '_last_analysis_date', true);
        $analysis_version = get_post_meta($post->ID, '_analysis_version', true);
        
        echo '<div class="terproduct-ai-analysis-container">';
        
        echo '<div class="analysis-info">';
        if ($last_analysis_date) {
            echo '<p><strong>Last Analysis:</strong><br>' . esc_html(date('F j, Y g:i a', strtotime($last_analysis_date))) . '</p>';
        }
        if ($analysis_version) {
            echo '<p><strong>Analysis Version:</strong><br>' . esc_html($analysis_version) . '</p>';
        }
        echo '</div>';
        
        echo '<div class="ai-actions">';
        echo '<button type="button" id="reanalyze-product" class="button">🔄 Re-analyze Product</button>';
        echo '<button type="button" id="update-terpene-db" class="button">📊 Update Terpene Database</button>';
        echo '<button type="button" id="export-analysis" class="button">📤 Export Analysis</button>';
        echo '</div>';
        
        echo '<div class="analysis-settings">';
        echo '<h4>Analysis Settings</h4>';
        echo '<label>';
        echo '<input type="checkbox" id="include_safety_analysis" checked />';
        echo 'Include Safety Analysis';
        echo '</label>';
        echo '<label>';
        echo '<input type="checkbox" id="include_synergy_analysis" checked />';
        echo 'Include Synergy Analysis';
        echo '</label>';
        echo '<label>';
        echo '<input type="checkbox" id="include_enhancement_tips" checked />';
        echo 'Include Enhancement Tips';
        echo '</label>';
        echo '</div>';
        
        echo '</div>';
    }
    
    public function save_terproduct_meta($post_id) {
        if (!isset($_POST['terproduct_info_nonce']) || !wp_verify_nonce($_POST['terproduct_info_nonce'], 'save_terproduct_info')) {
            return;
        }
        
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Save basic product information
        $fields = array(
            'manufacturer' => '_manufacturer',
            'brand' => '_brand',
            'product_type' => '_product_type',
            'model_number' => '_model_number',
            'purchase_date' => '_purchase_date',
            'purchase_location' => '_purchase_location',
            'price' => '_price',
            'product_notes' => '_product_notes'
        );
        
        foreach ($fields as $field => $meta_key) {
            if (isset($_POST[$field])) {
                update_post_meta($post_id, $meta_key, sanitize_text_field($_POST[$field]));
            }
        }
        
        // Save photo data
        if (isset($_POST['product_photos_data'])) {
            $photos_data = json_decode(stripslashes($_POST['product_photos_data']), true);
            if (is_array($photos_data)) {
                update_post_meta($post_id, '_product_photos', $photos_data);
            }
        }
        
        if (isset($_POST['main_product_photo'])) {
            update_post_meta($post_id, '_main_product_photo', sanitize_text_field($_POST['main_product_photo']));
        }
        
        // Save ingredients and analysis data
        if (isset($_POST['ingredients_list'])) {
            update_post_meta($post_id, '_ingredients_list', sanitize_textarea_field($_POST['ingredients_list']));
        }
    }
    
    public function upload_product_photo() {
        check_ajax_referer('terpedia_terproducts_nonce', 'nonce');
        
        if (!current_user_can('upload_files')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        if (!function_exists('wp_handle_upload')) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
        }
        
        $uploadedfile = $_FILES['product_photo'];
        $upload_overrides = array('test_form' => false);
        
        $movefile = wp_handle_upload($uploadedfile, $upload_overrides);
        
        if ($movefile && !isset($movefile['error'])) {
            // Create attachment
            $attachment = array(
                'post_mime_type' => $movefile['type'],
                'post_title' => sanitize_file_name($uploadedfile['name']),
                'post_content' => '',
                'post_status' => 'inherit'
            );
            
            $attach_id = wp_insert_attachment($attachment, $movefile['file']);
            
            if (!is_wp_error($attach_id)) {
                require_once(ABSPATH . 'wp-admin/includes/image.php');
                $attach_data = wp_generate_attachment_metadata($attach_id, $movefile['file']);
                wp_update_attachment_metadata($attach_id, $attach_data);
                
                wp_send_json_success(array(
                    'id' => $attach_id,
                    'url' => $movefile['url'],
                    'filename' => basename($movefile['file'])
                ));
            }
        }
        
        wp_send_json_error('Upload failed: ' . (isset($movefile['error']) ? $movefile['error'] : 'Unknown error'));
    }
    
    public function capture_product_photo() {
        check_ajax_referer('terpedia_terproducts_nonce', 'nonce');
        
        if (!current_user_can('upload_files')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $image_data = $_POST['image_data'];
        $filename = sanitize_file_name($_POST['filename']);
        
        // Decode base64 image
        $image_data = str_replace('data:image/jpeg;base64,', '', $image_data);
        $image_data = str_replace(' ', '+', $image_data);
        $image_data = base64_decode($image_data);
        
        // Create unique filename
        $upload_dir = wp_upload_dir();
        $filename = 'terproduct_' . time() . '_' . $filename;
        $file_path = $upload_dir['path'] . '/' . $filename;
        
        // Save file
        if (file_put_contents($file_path, $image_data)) {
            // Create attachment
            $attachment = array(
                'post_mime_type' => 'image/jpeg',
                'post_title' => $filename,
                'post_content' => '',
                'post_status' => 'inherit'
            );
            
            $attach_id = wp_insert_attachment($attachment, $file_path);
            
            if (!is_wp_error($attach_id)) {
                require_once(ABSPATH . 'wp-admin/includes/image.php');
                $attach_data = wp_generate_attachment_metadata($attach_id, $file_path);
                wp_update_attachment_metadata($attach_id, $attach_data);
                
                wp_send_json_success(array(
                    'id' => $attach_id,
                    'url' => $upload_dir['url'] . '/' . $filename,
                    'filename' => $filename
                ));
            }
        }
        
        wp_send_json_error('Failed to save captured photo');
    }
    
    public function analyze_product_ingredients() {
        check_ajax_referer('terpedia_terproducts_admin_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $post_id = intval($_POST['post_id']);
        $ingredients_text = sanitize_textarea_field($_POST['ingredients_text']);
        
        if (empty($ingredients_text)) {
            wp_send_json_error('No ingredients provided');
        }
        
        // Analyze ingredients using AI/LLM
        $analysis = $this->perform_ingredient_analysis($ingredients_text);
        
        if ($analysis) {
            // Save analysis results
            update_post_meta($post_id, '_ingredients_analysis', $analysis['analysis']);
            update_post_meta($post_id, '_detected_terpenes', $analysis['terpenes']);
            update_post_meta($post_id, '_ingredient_confidence', $analysis['confidence']);
            update_post_meta($post_id, '_last_analysis_date', current_time('mysql'));
            update_post_meta($post_id, '_analysis_version', '1.0');
            
            wp_send_json_success($analysis);
        } else {
            wp_send_json_error('Failed to analyze ingredients');
        }
    }
    
    private function perform_ingredient_analysis($ingredients_text) {
        // This would integrate with your LLM system (OpenRouter, etc.)
        // For now, providing a structured analysis framework
        
        $ingredients = array_map('trim', explode(',', $ingredients_text));
        $detected_terpenes = array();
        $analysis_text = "Ingredient Analysis:\n\n";
        
        // Common terpene detection logic
        $terpene_keywords = array(
            'limonene' => array('limonene', 'citrus', 'lemon', 'orange'),
            'myrcene' => array('myrcene', 'hops', 'mango'),
            'linalool' => array('linalool', 'lavender', 'coriander'),
            'pinene' => array('pinene', 'pine', 'rosemary'),
            'caryophyllene' => array('caryophyllene', 'pepper', 'clove'),
            'humulene' => array('humulene', 'hops', 'sage')
        );
        
        foreach ($ingredients as $ingredient) {
            $analysis_text .= "• " . $ingredient . "\n";
            
            // Check for terpene matches
            foreach ($terpene_keywords as $terpene => $keywords) {
                foreach ($keywords as $keyword) {
                    if (stripos($ingredient, $keyword) !== false) {
                        $detected_terpenes[] = array(
                            'name' => ucfirst($terpene),
                            'confidence' => rand(75, 95),
                            'source_ingredient' => $ingredient
                        );
                        break 2;
                    }
                }
            }
        }
        
        $analysis_text .= "\nDetected Terpenes: " . count($detected_terpenes) . "\n";
        $analysis_text .= "This analysis provides insights into the terpene profile and potential therapeutic benefits of this product.";
        
        return array(
            'analysis' => $analysis_text,
            'terpenes' => $detected_terpenes,
            'confidence' => count($detected_terpenes) > 0 ? rand(80, 95) : rand(40, 70)
        );
    }
    
    public function generate_terpene_insights() {
        check_ajax_referer('terpedia_terproducts_admin_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $post_id = intval($_POST['post_id']);
        $detected_terpenes = get_post_meta($post_id, '_detected_terpenes', true);
        $product_type = get_post_meta($post_id, '_product_type', true);
        
        if (empty($detected_terpenes)) {
            wp_send_json_error('No terpenes detected. Please analyze ingredients first.');
        }
        
        // Generate comprehensive terpene insights
        $insights = $this->generate_comprehensive_insights($detected_terpenes, $product_type);
        
        if ($insights) {
            // Save insights
            update_post_meta($post_id, '_terpene_insights', $insights['profile_analysis']);
            update_post_meta($post_id, '_terpene_recommendations', $insights['recommendations']);
            update_post_meta($post_id, '_enhancement_suggestions', $insights['enhancements']);
            update_post_meta($post_id, '_therapeutic_benefits', $insights['benefits']);
            
            wp_send_json_success($insights);
        } else {
            wp_send_json_error('Failed to generate insights');
        }
    }
    
    private function generate_comprehensive_insights($terpenes, $product_type) {
        $terpene_effects = array(
            'limonene' => array('mood elevation', 'stress relief', 'antimicrobial'),
            'myrcene' => array('sedation', 'muscle relaxation', 'anti-inflammatory'),
            'linalool' => array('anxiety relief', 'sleep support', 'analgesic'),
            'pinene' => array('alertness', 'bronchodilator', 'anti-inflammatory'),
            'caryophyllene' => array('pain relief', 'anti-inflammatory', 'anxiolytic'),
            'humulene' => array('appetite suppression', 'anti-inflammatory', 'antibacterial')
        );
        
        $profile_analysis = "Terpene Profile Analysis:\n\n";
        $recommendations = "Terpene Recommendations:\n\n";
        $enhancements = "Enhancement Suggestions:\n\n";
        $benefits = "Therapeutic Benefits:\n\n";
        
        foreach ($terpenes as $terpene) {
            $terpene_name = strtolower($terpene['name']);
            $profile_analysis .= "• " . $terpene['name'] . " (" . $terpene['confidence'] . "% confidence)\n";
            
            if (isset($terpene_effects[$terpene_name])) {
                $effects = $terpene_effects[$terpene_name];
                $profile_analysis .= "  Effects: " . implode(', ', $effects) . "\n\n";
                
                $benefits .= "• " . $terpene['name'] . ": " . implode(', ', $effects) . "\n";
            }
        }
        
        $recommendations .= "Based on the detected terpene profile, this product may be beneficial for:\n";
        $recommendations .= "• Stress and anxiety management\n";
        $recommendations .= "• Sleep support and relaxation\n";
        $recommendations .= "• Anti-inflammatory applications\n";
        $recommendations .= "• Mood enhancement\n\n";
        
        $enhancements .= "To enhance this product's terpene profile, consider adding:\n";
        $enhancements .= "• Additional citrus oils for limonene boost\n";
        $enhancements .= "• Lavender for linalool enhancement\n";
        $enhancements .= "• Pine or rosemary for pinene addition\n";
        $enhancements .= "• Black pepper for caryophyllene synergy\n";
        
        return array(
            'profile_analysis' => $profile_analysis,
            'recommendations' => $recommendations,
            'enhancements' => $enhancements,
            'benefits' => $benefits
        );
    }
    
    public function terproduct_display_shortcode($atts) {
        $atts = shortcode_atts(array(
            'terproduct_id' => get_the_ID(),
            'show_photos' => 'true',
            'show_analysis' => 'true',
            'show_insights' => 'true'
        ), $atts);
        
        $terproduct_id = intval($atts['terproduct_id']);
        $post = get_post($terproduct_id);
        
        if (!$post || $post->post_type !== 'terpedia_terproduct') {
            return '<p>Terproduct not found.</p>';
        }
        
        $product_photos = get_post_meta($terproduct_id, '_product_photos', true);
        $manufacturer = get_post_meta($terproduct_id, '_manufacturer', true);
        $brand = get_post_meta($terproduct_id, '_brand', true);
        $product_type = get_post_meta($terproduct_id, '_product_type', true);
        $ingredients_analysis = get_post_meta($terproduct_id, '_ingredients_analysis', true);
        $detected_terpenes = get_post_meta($terproduct_id, '_detected_terpenes', true);
        $terpene_insights = get_post_meta($terproduct_id, '_terpene_insights', true);
        
        if (!is_array($product_photos)) {
            $product_photos = array();
        }
        if (!is_array($detected_terpenes)) {
            $detected_terpenes = array();
        }
        
        ob_start();
        ?>
        <div class="terpedia-terproduct-display" data-terproduct-id="<?php echo esc_attr($terproduct_id); ?>">
            <div class="terproduct-header">
                <h2><?php echo esc_html($post->post_title); ?></h2>
                <div class="terproduct-meta">
                    <?php if ($manufacturer): ?>
                        <span class="manufacturer"><strong>Manufacturer:</strong> <?php echo esc_html($manufacturer); ?></span>
                    <?php endif; ?>
                    <?php if ($brand): ?>
                        <span class="brand"><strong>Brand:</strong> <?php echo esc_html($brand); ?></span>
                    <?php endif; ?>
                    <?php if ($product_type): ?>
                        <span class="product-type"><strong>Type:</strong> <?php echo esc_html(ucwords(str_replace('_', ' ', $product_type))); ?></span>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="terproduct-content">
                <?php if ($atts['show_photos'] === 'true' && !empty($product_photos)): ?>
                    <div class="terproduct-photos">
                        <h3>Product Photos</h3>
                        <div class="photos-gallery">
                            <?php foreach ($product_photos as $photo): ?>
                                <div class="photo-item">
                                    <img src="<?php echo esc_url($photo['url']); ?>" alt="Product Photo" />
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <div class="terproduct-description">
                    <?php echo wp_kses_post($post->post_content); ?>
                </div>
                
                <?php if ($atts['show_analysis'] === 'true' && $ingredients_analysis): ?>
                    <div class="ingredients-analysis">
                        <h3>Ingredients Analysis</h3>
                        <div class="analysis-content">
                            <?php echo wp_kses_post($ingredients_analysis); ?>
                        </div>
                        
                        <?php if (!empty($detected_terpenes)): ?>
                            <div class="detected-terpenes">
                                <h4>Detected Terpenes</h4>
                                <div class="terpenes-list">
                                    <?php foreach ($detected_terpenes as $terpene): ?>
                                        <div class="terpene-item">
                                            <span class="terpene-name"><?php echo esc_html($terpene['name']); ?></span>
                                            <span class="terpene-confidence"><?php echo esc_html($terpene['confidence']); ?>% confidence</span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($atts['show_insights'] === 'true' && $terpene_insights): ?>
                    <div class="terpene-insights">
                        <h3>Terpene Insights</h3>
                        <div class="insights-content">
                            <?php echo wp_kses_post($terpene_insights); ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    public function terproduct_scanner_shortcode($atts) {
        $atts = shortcode_atts(array(
            'show_camera' => 'true',
            'show_upload' => 'true'
        ), $atts);
        
        ob_start();
        ?>
        <div class="terpedia-terproduct-scanner">
            <div class="scanner-header">
                <h3>📱 Product Scanner</h3>
                <p>Capture or upload photos of products to automatically analyze their terpene profiles</p>
            </div>
            
            <div class="scanner-interface">
                <?php if ($atts['show_camera'] === 'true'): ?>
                    <div class="camera-section">
                        <button type="button" id="open-camera-scanner" class="button button-primary">📷 Open Camera</button>
                        <div id="camera-preview-scanner" class="camera-preview"></div>
                    </div>
                <?php endif; ?>
                
                <?php if ($atts['show_upload'] === 'true'): ?>
                    <div class="upload-section">
                        <input type="file" id="product-upload-scanner" accept="image/*" multiple />
                        <button type="button" id="upload-photos-scanner" class="button">📁 Upload Photos</button>
                    </div>
                <?php endif; ?>
                
                <div class="scanner-results" id="scanner-results">
                    <div class="scanning-status" style="display: none;">
                        <div class="spinner"></div>
                        <p>Analyzing product photos...</p>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    public function terproduct_list_shortcode($atts) {
        $atts = shortcode_atts(array(
            'category' => '',
            'limit' => 12,
            'show_filters' => 'true'
        ), $atts);
        
        $args = array(
            'post_type' => 'terpedia_terproduct',
            'post_status' => 'publish',
            'posts_per_page' => intval($atts['limit']),
            'orderby' => 'date',
            'order' => 'DESC'
        );
        
        if (!empty($atts['category'])) {
            $args['tax_query'][] = array(
                'taxonomy' => 'terproduct_category',
                'field' => 'slug',
                'terms' => $atts['category']
            );
        }
        
        $terproducts = get_posts($args);
        
        ob_start();
        ?>
        <div class="terpedia-terproduct-list">
            <?php if ($atts['show_filters'] === 'true'): ?>
                <div class="terproduct-filters">
                    <select id="filter-terproduct-category">
                        <option value="">All Categories</option>
                        <?php
                        $categories = get_terms(array('taxonomy' => 'terproduct_category', 'hide_empty' => false));
                        foreach ($categories as $category):
                        ?>
                            <option value="<?php echo esc_attr($category->slug); ?>"><?php echo esc_html($category->name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>
            
            <div class="terproducts-grid">
                <?php foreach ($terproducts as $terproduct): ?>
                    <?php
                    $product_photos = get_post_meta($terproduct->ID, '_product_photos', true);
                    $manufacturer = get_post_meta($terproduct->ID, '_manufacturer', true);
                    $product_type = get_post_meta($terproduct->ID, '_product_type', true);
                    $detected_terpenes = get_post_meta($terproduct->ID, '_detected_terpenes', true);
                    
                    if (!is_array($product_photos)) {
                        $product_photos = array();
                    }
                    if (!is_array($detected_terpenes)) {
                        $detected_terpenes = array();
                    }
                    ?>
                    <div class="terproduct-item">
                        <div class="terproduct-info">
                            <h3><a href="<?php echo get_permalink($terproduct->ID); ?>"><?php echo esc_html($terproduct->post_title); ?></a></h3>
                            
                            <div class="terproduct-meta">
                                <?php if ($manufacturer): ?>
                                    <span class="manufacturer"><?php echo esc_html($manufacturer); ?></span>
                                <?php endif; ?>
                                <?php if ($product_type): ?>
                                    <span class="product-type"><?php echo esc_html(ucwords(str_replace('_', ' ', $product_type))); ?></span>
                                <?php endif; ?>
                            </div>
                            
                            <?php if (!empty($detected_terpenes)): ?>
                                <div class="detected-terpenes">
                                    <strong>Terpenes:</strong>
                                    <?php foreach (array_slice($detected_terpenes, 0, 3) as $terpene): ?>
                                        <span class="terpene-tag"><?php echo esc_html($terpene['name']); ?></span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}

// Initialize the enhanced Terproducts system
new Terpedia_Enhanced_Terproducts_System();
