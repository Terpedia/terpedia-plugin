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
    return 'http://localhost:5000/ajax-handler.php';
}

function wp_create_nonce($action) {
    return 'dev_nonce_' . md5($action);
}

// Mock WordPress functions only if they don't exist
if (!function_exists('add_filter')) {
    function add_filter($tag, $function_to_add, $priority = 10, $accepted_args = 1) {
        return true;
    }
}

if (!function_exists('add_action')) {
    function add_action($tag, $function_to_add, $priority = 10, $accepted_args = 1) {
        return true;
    }
}

if (!function_exists('add_shortcode')) {
    function add_shortcode($tag, $function) {
        return true;
    }
}

if (!function_exists('register_post_type')) {
    function register_post_type($post_type, $args) {
        return true;
    }
}

if (!function_exists('post_type_exists')) {
    function post_type_exists($post_type) {
        return false;
    }
}

if (!function_exists('register_taxonomy')) {
    function register_taxonomy($taxonomy, $object_type, $args) {
        return true;
    }
}

if (!function_exists('term_exists')) {
    function term_exists($term, $taxonomy) {
        return false;
    }
}

if (!function_exists('wp_insert_term')) {
    function wp_insert_term($term, $taxonomy, $args = array()) {
        return array('term_id' => rand(1, 1000));
    }
}

if (!function_exists('current_user_can')) {
    function current_user_can($capability) {
        return true; // Allow all permissions in dev mode
    }
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

// Handle special routes
$request_uri = $_SERVER['REQUEST_URI'] ?? '/';
$path = parse_url($request_uri, PHP_URL_PATH);

if ($path === '/terproducts' || $path === '/terproducts/') {
    // Clear buffer and display terproducts archive
    ob_end_clean();
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Terproducts Archive - Terpedia</title>
        <link rel="stylesheet" href="/assets/css/enhanced-terproducts.css">
        <style>
            body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; margin: 0; padding: 0; background-color: #f0f0f1; line-height: 1.6; }
            .site-header { background: #fff; border-bottom: 1px solid #e1e1e1; padding: 1rem 0; margin-bottom: 2rem; }
            .site-header .container { max-width: 1200px; margin: 0 auto; padding: 0 20px; display: flex; justify-content: space-between; align-items: center; }
            .site-title { font-size: 1.5rem; font-weight: 700; color: #333; text-decoration: none; }
            .site-nav a { color: #666; text-decoration: none; margin-left: 1.5rem; font-weight: 500; }
            .site-nav a:hover { color: #007cba; }
            .content-wrap { max-width: 1200px; margin: 0 auto; padding: 0 20px; min-height: 60vh; }
            .archive-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 3rem 0; text-align: center; margin-bottom: 2rem; border-radius: 8px; }
            .archive-header h1 { margin: 0 0 1rem 0; font-size: 2.5rem; font-weight: 700; }
            .archive-header p { margin: 0; font-size: 1.1rem; opacity: 0.9; }
            .add-product-cta { margin-top: 1.5rem; }
            .add-product-cta a { display: inline-block; background: rgba(255,255,255,0.2); color: white; padding: 0.75rem 2rem; border-radius: 25px; text-decoration: none; font-weight: 600; transition: all 0.3s ease; border: 2px solid rgba(255,255,255,0.3); }
            .add-product-cta a:hover { background: rgba(255,255,255,0.3); transform: translateY(-2px); }
            .products-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 2rem; margin: 2rem 0; }
            .product-card { background: white; border-radius: 8px; padding: 1.5rem; box-shadow: 0 2px 10px rgba(0,0,0,0.1); transition: transform 0.2s ease, box-shadow 0.2s ease; }
            .product-card:hover { transform: translateY(-2px); box-shadow: 0 4px 20px rgba(0,0,0,0.15); }
            .product-image { text-align: center; margin-bottom: 1rem; }
            .placeholder-image { width: 80px; height: 80px; background: #f0f0f1; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 2rem; color: #666; }
            .product-content h3 { margin: 0 0 0.5rem 0; color: #333; }
            .product-content h3 a { color: inherit; text-decoration: none; }
            .product-content h3 a:hover { color: #007cba; }
            .product-meta { margin-bottom: 1rem; font-size: 0.9rem; color: #666; }
            .product-meta span { display: inline-block; margin-right: 1rem; background: #f8f9fa; padding: 0.25rem 0.5rem; border-radius: 4px; }
            .product-excerpt { margin-bottom: 1rem; color: #666; line-height: 1.5; }
            .terpenes-preview { font-size: 0.9rem; }
            .terpene-tag { display: inline-block; background: #e8f5e8; color: #2e7d32; padding: 0.25rem 0.5rem; border-radius: 12px; margin: 0.25rem 0.25rem 0 0; font-size: 0.8rem; font-weight: 500; }
        </style>
    </head>
    <body>
        <header class="site-header">
            <div class="container">
                <a href="/" class="site-title">🧪 Terpedia</a>
                <nav class="site-nav">
                    <a href="/">Home</a>
                    <a href="/terproducts" style="color: #007cba;">Terproducts</a>
                    <a href="/add-terproduct">Add Product</a>
                </nav>
            </div>
        </header>
        
        <main class="content-wrap">
            <div class="archive-header">
                <h1>📦 Terproducts Archive</h1>
                <p>AI-analyzed cannabis products with comprehensive terpene profiles</p>
                <div class="add-product-cta">
                    <a href="/add-terproduct">📱 Add New Product</a>
                </div>
            </div>
            
            <div class="products-grid">
                <?php
                // Sample terproducts with realistic terpene data
                $products = array(
                    array('title' => 'Lavender Essential Oil Blend', 'brand' => 'Natural Wellness Co.', 'confidence' => 92, 'excerpt' => 'A calming essential oil blend with dominant linalool content for relaxation and stress relief.', 'terpenes' => array(array('name' => 'Linalool', 'concentration' => '3.2%'), array('name' => 'Limonene', 'concentration' => '1.8%'), array('name' => 'Pinene', 'concentration' => '0.9%'))),
                    array('title' => 'Citrus Burst Terpene Spray', 'brand' => 'TerpeneFarm', 'confidence' => 87, 'excerpt' => 'Energizing citrus blend with high limonene content for mood enhancement and focus.', 'terpenes' => array(array('name' => 'Limonene', 'concentration' => '4.1%'), array('name' => 'Myrcene', 'concentration' => '1.2%'), array('name' => 'Beta-pinene', 'concentration' => '0.7%'))),
                    array('title' => 'Pine Forest Inhaler', 'brand' => 'Forest Botanicals', 'confidence' => 94, 'excerpt' => 'Refreshing pine blend with high pinene content for respiratory support and mental clarity.', 'terpenes' => array(array('name' => 'Alpha-pinene', 'concentration' => '5.3%'), array('name' => 'Beta-pinene', 'concentration' => '2.1%'), array('name' => 'Eucalyptol', 'concentration' => '1.9%')))
                );
                
                foreach ($products as $product): ?>
                    <div class="product-card">
                        <div class="product-image">
                            <div class="placeholder-image">📦</div>
                        </div>
                        <div class="product-content">
                            <h3><a href="#"><?php echo htmlspecialchars($product['title']); ?></a></h3>
                            <div class="product-meta">
                                <span>Brand: <?php echo htmlspecialchars($product['brand']); ?></span>
                                <span>Analysis: <?php echo $product['confidence']; ?>%</span>
                            </div>
                            <div class="product-excerpt">
                                <p><?php echo htmlspecialchars($product['excerpt']); ?></p>
                            </div>
                            <div class="terpenes-preview">
                                <strong>Key Terpenes:</strong><br>
                                <?php foreach ($product['terpenes'] as $terpene): ?>
                                    <span class="terpene-tag"><?php echo htmlspecialchars($terpene['name'] . ' (' . $terpene['concentration'] . ')'); ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </main>
    </body>
    </html>
    <?php
    exit;
}

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
            <h3>🔧 Development Configuration</h3>
            <div style="background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
                <h4>📋 OpenRouter API Setup:</h4>
                <p><strong>API Key Status:</strong> <span style="color: #856404;">⚠️ Development Mock Mode</span></p>
                <p><strong>Vision Model:</strong> meta-llama/llama-3.2-11b-vision-instruct:free</p>
                <p><strong>Features Available:</strong> Photo Analysis, Ingredient Extraction, Terpene Detection</p>
                <p style="font-size: 12px; color: #666;">
                    <strong>Note:</strong> In this demo, image analysis responses are simulated. 
                    To use real OpenRouter AI vision analysis, set OPENROUTER_API_KEY environment variable.
                </p>
            </div>
        </div>

        <div class="demo-section">
            <h3>📱 Photo Capture & AI Analysis</h3>
            <p>Capture product photos and automatically extract ingredients using AI vision:</p>
            
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