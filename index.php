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
    
    // Add cache control headers to prevent caching issues
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
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

if ($path === '/add-terproduct' || $path === '/add-terproduct/') {
    // Clear buffer and display terproduct creation interface
    ob_end_clean();
    
    // Add cache control headers
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Add Terproduct - Terpedia</title>
        <link rel="stylesheet" href="/assets/css/enhanced-terproducts.css">
        <link rel="stylesheet" href="/assets/css/frontend-creator.css">
        <style>
            body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; margin: 0; padding: 0; background: #f0f0f1; }
            .site-header { background: #fff; border-bottom: 1px solid #e1e1e1; padding: 1rem 0; }
            .site-header .container { max-width: 1200px; margin: 0 auto; padding: 0 20px; display: flex; justify-content: space-between; align-items: center; }
            .site-title { font-size: 1.5rem; font-weight: 700; color: #333; text-decoration: none; }
            .site-nav a { color: #666; text-decoration: none; margin-left: 1.5rem; font-weight: 500; }
            .site-nav a:hover { color: #007cba; }
            .content-wrap { max-width: 800px; margin: 2rem auto; padding: 0 20px; }
            .page-header { text-align: center; margin-bottom: 2rem; }
            .page-header h1 { margin: 0 0 0.5rem 0; color: #333; }
            .page-header p { color: #666; margin: 0; }
            
            /* Camera Interface Styles */
            .camera-interface { background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
            .camera-section { padding: 2rem; border-bottom: 1px solid #e1e1e1; }
            .camera-section h3 { margin: 0 0 1rem 0; color: #333; font-size: 1.25rem; }
            .camera-controls { display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap; margin-bottom: 1.5rem; }
            .camera-btn { background: #007cba; color: white; border: none; padding: 1rem 2rem; border-radius: 8px; font-size: 1rem; font-weight: 600; cursor: pointer; transition: all 0.3s ease; }
            .camera-btn:hover { background: #005a87; transform: translateY(-2px); }
            .camera-btn:disabled { background: #ccc; cursor: not-allowed; transform: none; }
            .camera-preview { min-height: 200px; border: 2px dashed #ddd; border-radius: 8px; display: flex; align-items: center; justify-content: center; background: #fafafa; color: #666; font-size: 1.1rem; }
            .camera-preview.has-image { border-style: solid; border-color: #007cba; }
            .camera-preview img { max-width: 100%; max-height: 300px; border-radius: 4px; }
            
            /* Analysis Results */
            .analysis-section { padding: 2rem; }
            .analysis-results { background: #f8f9fa; border-radius: 8px; padding: 1.5rem; }
            .analysis-results h4 { margin: 0 0 1rem 0; color: #333; }
            .analysis-loading { text-align: center; color: #666; padding: 2rem; }
            .analysis-loading .spinner { display: inline-block; width: 20px; height: 20px; border: 3px solid #f3f3f3; border-top: 3px solid #007cba; border-radius: 50%; animation: spin 1s linear infinite; margin-right: 10px; }
            @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
            
            .detection-item { background: white; border-radius: 6px; padding: 1rem; margin-bottom: 1rem; border-left: 4px solid #00a32a; }
            .detection-item h5 { margin: 0 0 0.5rem 0; color: #333; }
            .detection-item p { margin: 0; color: #666; font-size: 0.9rem; }
            .terpene-tags { margin-top: 0.5rem; }
            .terpene-tag { display: inline-block; background: #e8f5e8; color: #2e7d32; padding: 0.25rem 0.5rem; border-radius: 12px; margin: 0.25rem 0.25rem 0 0; font-size: 0.8rem; font-weight: 500; }
            
            .save-section { padding: 1.5rem 2rem; background: #f8f9fa; text-align: center; }
            .save-btn { background: #00a32a; color: white; border: none; padding: 1rem 2rem; border-radius: 8px; font-size: 1rem; font-weight: 600; cursor: pointer; transition: all 0.3s ease; }
            .save-btn:hover { background: #008a20; transform: translateY(-2px); }
            .save-btn:disabled { background: #ccc; cursor: not-allowed; transform: none; }
            
            #product-camera-input { display: none; }
        </style>
    </head>
    <body>
        <header class="site-header">
            <div class="container">
                <a href="/" class="site-title">🧪 Terpedia</a>
                <nav class="site-nav">
                    <a href="/">Home</a>
                    <a href="/terproducts">Terproducts</a>
                    <a href="/add-terproduct" style="color: #007cba;">Add Product</a>
                </nav>
            </div>
        </header>
        
        <main class="content-wrap">
            <div class="page-header">
                <h1>📱 Add New Terproduct</h1>
                <p>Capture product photos and let AI analyze ingredients and terpenes automatically</p>
            </div>
            
            <div class="camera-interface">
                <!-- Camera Section -->
                <div class="camera-section">
                    <h3>📷 Photo Capture</h3>
                    <div class="camera-controls">
                        <button type="button" id="open-camera-btn" class="camera-btn">📷 Open Camera</button>
                        <button type="button" id="upload-photos-btn" class="camera-btn">📁 Upload Photos</button>
                    </div>
                    <div id="camera-preview" class="camera-preview">
                        Tap camera button to capture product photos
                    </div>
                    <input type="file" id="product-camera-input" accept="image/*" capture="environment" multiple />
                </div>
                
                <!-- Analysis Results Section -->
                <div class="analysis-section">
                    <h3>🧠 AI Analysis Results</h3>
                    <div id="analysis-results" class="analysis-results" style="display: none;">
                        <!-- Results will be populated here -->
                    </div>
                    <div id="analysis-placeholder" class="analysis-loading">
                        Upload photos to see AI analysis of ingredients and terpenes
                    </div>
                </div>
                
                <!-- Save Section -->
                <div class="save-section">
                    <button type="button" id="save-product-btn" class="save-btn" disabled>💾 Save Terproduct</button>
                    <p style="margin: 0.5rem 0 0 0; color: #666; font-size: 0.9rem;">Analysis will auto-save when confidence ≥70%</p>
                </div>
            </div>
        </main>
        
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script src="/assets/js/enhanced-terproducts.js"></script>
        <script>
            // Initialize the camera interface
            document.addEventListener('DOMContentLoaded', function() {
                // Camera functionality  
                const openCameraBtn = document.getElementById('open-camera-btn');
                const uploadPhotosBtn = document.getElementById('upload-photos-btn');
                const cameraInput = document.getElementById('product-camera-input');
                
                if (openCameraBtn) {
                    openCameraBtn.addEventListener('click', function() {
                        if (cameraInput) cameraInput.click();
                    });
                }
                
                if (uploadPhotosBtn) {
                    uploadPhotosBtn.addEventListener('click', function() {
                        if (cameraInput) cameraInput.click();
                    });
                }
                
                // Handle file selection
                if (cameraInput) {
                    cameraInput.addEventListener('change', function(e) {
                        const files = e.target.files;
                        if (files.length > 0) {
                            handlePhotoUpload(files);
                        }
                    });
                }
                
                function handlePhotoUpload(files) {
                    const file = files[0];
                    const reader = new FileReader();
                    
                    reader.onload = function(e) {
                        // Show image preview
                        const preview = document.getElementById('camera-preview');
                        if (preview) {
                            preview.classList.add('has-image');
                            preview.innerHTML = `<img src="${e.target.result}" alt="Product Photo">`;
                        }
                        
                        // Start analysis
                        startAnalysis();
                    };
                    
                    reader.readAsDataURL(file);
                }
                
                function startAnalysis() {
                    const placeholder = document.getElementById('analysis-placeholder');
                    const results = document.getElementById('analysis-results');
                    
                    if (placeholder) placeholder.style.display = 'none';
                    if (results) {
                        results.style.display = 'block';
                        results.innerHTML = `
                            <div class="analysis-loading">
                                <div class="spinner"></div>
                                Analyzing product photo for ingredients and terpenes...
                            </div>
                        `;
                    }
                    
                    // Simulate AI analysis (replace with actual API call)
                    setTimeout(() => {
                        showAnalysisResults();
                    }, 3000);
                }
                
                function showAnalysisResults() {
                    const resultsHtml = `
                        <h4>📊 Analysis Complete</h4>
                        <div class="detection-item">
                            <h5>Product Detected: Essential Oil Blend</h5>
                            <p><strong>Brand:</strong> Natural Wellness Co. | <strong>Confidence:</strong> 92%</p>
                            <p><strong>Ingredients:</strong> Lavandula angustifolia oil, Citrus limon peel oil, Natural carrier oil</p>
                            <div class="terpene-tags">
                                <span class="terpene-tag">Linalool (3.2%)</span>
                                <span class="terpene-tag">Limonene (1.8%)</span>
                                <span class="terpene-tag">Pinene (0.9%)</span>
                            </div>
                        </div>
                        <div class="detection-item">
                            <h5>Terpene Profile Analysis</h5>
                            <p>Dominant relaxing terpenes detected. This blend appears optimized for stress relief and sleep support.</p>
                        </div>
                    `;
                    
                    const results = document.getElementById('analysis-results');
                    const saveBtn = document.getElementById('save-product-btn');
                    
                    if (results) results.innerHTML = resultsHtml;
                    if (saveBtn) saveBtn.disabled = false;
                    
                    // Auto-save simulation (confidence ≥70%)
                    setTimeout(() => {
                        if (saveBtn) {
                            saveBtn.textContent = '✅ Auto-Saved';
                            saveBtn.style.background = '#00a32a';
                        }
                        showSaveMessage();
                    }, 2000);
                }
                
                function showSaveMessage() {
                    const saveSection = document.querySelector('.save-section');
                    if (saveSection) {
                        const messageDiv = document.createElement('div');
                        messageDiv.style.cssText = 'background: #d4edda; color: #155724; padding: 1rem; border-radius: 6px; margin-top: 1rem; text-align: center;';
                        messageDiv.innerHTML = '✅ Terproduct saved successfully! <a href="/terproducts" style="color: #155724; font-weight: 600;">View all products</a>';
                        saveSection.appendChild(messageDiv);
                        
                        setTimeout(() => messageDiv.remove(), 5000);
                    }
                }
                
                // Save button functionality
                const saveBtn = document.getElementById('save-product-btn');
                if (saveBtn) {
                    saveBtn.addEventListener('click', function() {
                        if (!this.disabled) {
                            this.textContent = '💾 Saving...';
                            this.disabled = true;
                            setTimeout(() => {
                                this.textContent = '✅ Saved';
                                this.style.background = '#00a32a';
                                showSaveMessage();
                            }, 1000);
                        }
                    });
                }
            });
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