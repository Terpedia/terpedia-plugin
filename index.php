<?php
/**
 * Terpedia Terproducts Development Environment
 * Simple development server for testing Terproducts functionality
 */

// Simulate WordPress environment constants
define('ABSPATH', __DIR__ . '/');
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);

// Mock WordPress functions for development
function plugin_dir_url($file) {
    return 'http://localhost:5000/';
}

function plugin_dir_path($file) {
    return __DIR__ . '/';
}

function add_action($hook, $callback, $priority = 10, $accepted_args = 1) {
    // Mock function for development
}

function add_shortcode($tag, $callback) {
    // Mock function for development
}

function wp_enqueue_script($handle, $src, $deps = array(), $ver = false, $in_footer = false) {
    // Mock function for development
}

function wp_enqueue_style($handle, $src, $deps = array(), $ver = false, $media = 'all') {
    // Mock function for development
}

function admin_url($path) {
    return 'http://localhost:5000/admin-ajax.php';
}

function wp_create_nonce($action) {
    return 'dev_nonce_' . md5($action);
}

function current_user_can($capability) {
    return true; // Allow all permissions in dev mode
}

function register_post_type($post_type, $args) {
    // Mock function for development
}

function register_taxonomy($taxonomy, $object_type, $args) {
    // Mock function for development
}

function term_exists($term, $taxonomy) {
    return false; // Force creation in dev mode
}

function wp_insert_term($term, $taxonomy, $args = array()) {
    return array('term_id' => rand(1, 1000));
}

function get_post_meta($post_id, $key, $single = false) {
    return $single ? '' : array();
}

function update_post_meta($post_id, $key, $value) {
    return true;
}

// Start output buffering
ob_start();

// Include the enhanced terproducts system
require_once __DIR__ . '/includes/enhanced-terproducts-system.php';

// Initialize the system
$terproducts = new Terpedia_Enhanced_Terproducts_System();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terpedia Terproducts - Development Environment</title>
    <link rel="stylesheet" href="/assets/css/enhanced-terproducts.css">
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f0f0f1;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 40px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 20px;
        }
        .demo-section {
            margin: 30px 0;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
            background: #f9f9f9;
        }
        .demo-section h3 {
            color: #0073aa;
            margin-top: 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🧪 Terpedia Terproducts Development Environment</h1>
            <p>Test and develop Terproducts functionality with photo capture and ingredient analysis</p>
        </div>

        <div class="demo-section">
            <h3>📱 Photo Capture & Product Scanner</h3>
            <p>Test the mobile camera interface and photo upload functionality:</p>
            
            <div class="terproduct-photo-capture-container">
                <div class="mobile-camera-interface">
                    <h4>📱 Mobile Photo Capture</h4>
                    <div class="camera-controls">
                        <input type="file" id="product-camera-input" accept="image/*" capture="environment" multiple />
                        <button type="button" id="open-camera-btn" class="button button-primary">📷 Open Camera</button>
                        <button type="button" id="upload-photos-btn" class="button">📁 Upload Photos</button>
                    </div>
                    <div id="camera-preview" class="camera-preview"></div>
                </div>
                
                <div class="product-photos-gallery">
                    <h4>Product Photos</h4>
                    <div class="photos-grid" id="photos-grid">
                        <div class="no-photos">No photos uploaded yet. Use the camera interface above to capture product photos.</div>
                    </div>
                </div>
                
                <input type="hidden" id="product_photos_data" name="product_photos_data" value="[]" />
                <input type="hidden" id="main_product_photo" name="main_product_photo" value="" />
            </div>
        </div>

        <div class="demo-section">
            <h3>🔍 Ingredient Analysis</h3>
            <p>Test AI-powered ingredient analysis:</p>
            
            <div class="terproduct-ingredients-container">
                <div class="ingredients-input-section">
                    <h4>Ingredients List</h4>
                    <textarea id="ingredients_list" name="ingredients_list" rows="6" style="width: 100%;" placeholder="Enter ingredients as they appear on the product label, separated by commas or new lines...

Example:
Lavandula angustifolia (Lavender) oil
Citrus limon (Lemon) peel oil
Eucalyptus globulus leaf oil
Mentha piperita (Peppermint) oil
Rosmarinus officinalis (Rosemary) leaf oil"></textarea>
                    <br><br>
                    <button type="button" id="analyze-ingredients-btn" class="button button-primary">🔍 Analyze Ingredients</button>
                </div>
                
                <div class="ingredients-analysis-section">
                    <h4>AI Analysis Results</h4>
                    <div id="analysis-status" class="analysis-status"></div>
                </div>
            </div>
        </div>

        <div class="demo-section">
            <h3>🧠 Terpene Insights</h3>
            <p>Generate comprehensive terpene insights and recommendations:</p>
            
            <div class="terproduct-terpene-insights-container">
                <div class="insights-generation-section">
                    <h4>Generate Terpene Insights</h4>
                    <p>Based on the detected ingredients and terpenes, generate comprehensive insights and recommendations.</p>
                    <button type="button" id="generate-insights-btn" class="button button-primary">🧠 Generate Terpene Insights</button>
                    <div id="insights-status" class="insights-status"></div>
                </div>
            </div>
        </div>

        <div class="demo-section">
            <h3>📊 System Status</h3>
            <p><strong>✅ Enhanced Terproducts System Loaded</strong></p>
            <p><strong>✅ Photo Capture Interface Active</strong></p>
            <p><strong>✅ Ingredient Analysis Ready</strong></p>
            <p><strong>✅ Terpene Insights Engine Ready</strong></p>
            <p><strong>⚠️ Development Mode:</strong> Some WordPress features are mocked for standalone testing</p>
        </div>
    </div>

    <!-- Hidden form for WordPress compatibility -->
    <form id="post" style="display: none;">
        <input type="hidden" name="post_ID" value="1" />
        <input type="hidden" name="action" value="editpost" />
    </form>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Mock WordPress AJAX variables for development
        window.terpediaTerproducts = {
            ajaxurl: '/ajax-handler.php',
            nonce: 'dev_nonce_12345',
            strings: {
                photo_uploaded: 'Photo uploaded successfully!',
                analyzing_ingredients: 'Analyzing ingredients...',
                generating_insights: 'Generating terpene insights...',
                error: 'Error processing request.'
            }
        };

        window.terpediaTerproductsAdmin = {
            ajaxurl: '/ajax-handler.php',
            nonce: 'dev_nonce_admin_12345'
        };
    </script>
    <script src="/assets/js/enhanced-terproducts.js"></script>
    <script src="/assets/js/terproducts-admin.js"></script>
</body>
</html>

<?php
// Get the output
$content = ob_get_clean();
echo $content;
?>