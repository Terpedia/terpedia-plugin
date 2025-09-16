<?php
/**
 * Version Endpoint for Terpedia Plugin
 * Provides a public REST API endpoint to check plugin version
 */

class Terpedia_Version_Endpoint {
    
    public function __construct() {
        add_action('rest_api_init', array($this, 'register_version_routes'));
        add_action('init', array($this, 'add_version_rewrite_rule'));
        add_action('template_redirect', array($this, 'handle_version_page'));
    }
    
    /**
     * Register REST API routes
     */
    public function register_version_routes() {
        // Main version endpoint
        register_rest_route('terpedia/v1', '/version', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_version_info'),
            'permission_callback' => '__return_true',
        ));
        
        // Simple version number only
        register_rest_route('terpedia/v1', '/version/number', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_version_number_only'),
            'permission_callback' => '__return_true',
        ));
    }
    
    /**
     * Add custom rewrite rule for /version page
     */
    public function add_version_rewrite_rule() {
        add_rewrite_rule('^version/?$', 'index.php?terpedia_version_page=1', 'top');
        add_rewrite_tag('%terpedia_version_page%', '([^&]+)');
    }
    
    /**
     * Handle /version page template
     */
    public function handle_version_page() {
        if (get_query_var('terpedia_version_page')) {
            $this->render_version_page();
            exit;
        }
    }
    
    /**
     * Get comprehensive version information
     */
    public function get_version_info($request) {
        // Safe path resolution
        $plugin_dir = dirname(dirname(__FILE__));
        
        // Include version manager safely
        $version_manager_path = $plugin_dir . '/version-manager.php';
        if (file_exists($version_manager_path) && !class_exists('TerpediaPluginVersionManager')) {
            require_once $version_manager_path;
        }
        
        // Get version safely
        if (class_exists('TerpediaPluginVersionManager')) {
            $version = TerpediaPluginVersionManager::getCurrentVersion();
        } else {
            $version = '3.11.5'; // Fallback version
        }
        
        // Get plugin data safely
        $plugin_file = $plugin_dir . '/terpedia.php';
        $plugin_data = array();
        
        if (file_exists($plugin_file)) {
            $plugin_data = get_file_data($plugin_file, array(
                'Name' => 'Plugin Name',
                'Version' => 'Version',
                'Description' => 'Description',
                'Author' => 'Author',
                'PluginURI' => 'Plugin URI'
            ), 'plugin');
        }
        
        // Check if plugin is active (safely)
        $is_active = false;
        if (function_exists('is_plugin_active')) {
            $is_active = is_plugin_active(plugin_basename($plugin_file));
        }
        
        return rest_ensure_response(array(
            'plugin_name' => isset($plugin_data['Name']) ? $plugin_data['Name'] : 'Terpedia',
            'version' => $version,
            'header_version' => isset($plugin_data['Version']) ? $plugin_data['Version'] : $version,
            'description' => isset($plugin_data['Description']) ? $plugin_data['Description'] : 'Comprehensive terpene encyclopedia with AI experts',
            'author' => isset($plugin_data['Author']) ? $plugin_data['Author'] : 'Terpedia Team',
            'plugin_uri' => isset($plugin_data['PluginURI']) ? $plugin_data['PluginURI'] : 'https://terpedia.com',
            'wordpress_version' => get_bloginfo('version'),
            'php_version' => PHP_VERSION,
            'is_active' => $is_active,
            'timestamp' => current_time('mysql'),
            'build_date' => file_exists($plugin_file) ? date('Y-m-d H:i:s', filemtime($plugin_file)) : 'Unknown',
            'endpoints' => array(
                'version_info' => home_url('/wp-json/terpedia/v1/version'),
                'version_number' => home_url('/wp-json/terpedia/v1/version/number'),
                'version_page' => home_url('/version')
            )
        ));
    }
    
    /**
     * Get version number only (plain text)
     */
    public function get_version_number_only($request) {
        // Safe path resolution
        $plugin_dir = dirname(dirname(__FILE__));
        
        // Include version manager safely
        $version_manager_path = $plugin_dir . '/version-manager.php';
        if (file_exists($version_manager_path) && !class_exists('TerpediaPluginVersionManager')) {
            require_once $version_manager_path;
        }
        
        // Get version safely
        if (class_exists('TerpediaPluginVersionManager')) {
            $version = TerpediaPluginVersionManager::getCurrentVersion();
        } else {
            $version = '3.11.5'; // Fallback version
        }
        
        return new WP_REST_Response($version, 200, array(
            'Content-Type' => 'text/plain'
        ));
    }
    
    /**
     * Render HTML version page at /version
     */
    private function render_version_page() {
        // Safe path resolution
        $plugin_dir = dirname(dirname(__FILE__));
        
        // Include version manager safely
        $version_manager_path = $plugin_dir . '/version-manager.php';
        if (file_exists($version_manager_path) && !class_exists('TerpediaPluginVersionManager')) {
            require_once $version_manager_path;
        }
        
        // Get version safely
        if (class_exists('TerpediaPluginVersionManager')) {
            $version = TerpediaPluginVersionManager::getCurrentVersion();
        } else {
            $version = '3.11.5'; // Fallback version
        }
        
        // Get plugin data safely
        $plugin_file = $plugin_dir . '/terpedia.php';
        $plugin_data = array();
        
        if (file_exists($plugin_file)) {
            $plugin_data = get_file_data($plugin_file, array(
                'Name' => 'Plugin Name',
                'Version' => 'Version',
                'Description' => 'Description',
                'Author' => 'Author'
            ), 'plugin');
        }
        
        // Simple HTML page
        header('Content-Type: text/html; charset=utf-8');
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Terpedia Plugin Version</title>
            <style>
                body {
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, sans-serif;
                    max-width: 800px;
                    margin: 50px auto;
                    padding: 20px;
                    background: #f8f9fa;
                    color: #333;
                }
                .version-card {
                    background: white;
                    border-radius: 12px;
                    padding: 40px;
                    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
                    border: 1px solid #e9ecef;
                }
                .version-number {
                    font-size: 3rem;
                    font-weight: bold;
                    color: #2c5aa0;
                    margin: 0;
                }
                .plugin-title {
                    font-size: 1.5rem;
                    color: #495057;
                    margin: 10px 0 30px 0;
                }
                .info-grid {
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                    gap: 20px;
                    margin-top: 30px;
                }
                .info-item {
                    background: #f8f9fa;
                    padding: 15px;
                    border-radius: 8px;
                    border-left: 4px solid #2c5aa0;
                }
                .info-label {
                    font-size: 0.875rem;
                    text-transform: uppercase;
                    color: #6c757d;
                    font-weight: 600;
                    margin-bottom: 5px;
                }
                .info-value {
                    font-size: 1rem;
                    color: #495057;
                    font-weight: 500;
                }
                .endpoints {
                    margin-top: 30px;
                    padding: 20px;
                    background: #e7f3ff;
                    border-radius: 8px;
                }
                .endpoints h3 {
                    margin-top: 0;
                    color: #2c5aa0;
                }
                .endpoints a {
                    display: block;
                    color: #0066cc;
                    text-decoration: none;
                    margin: 5px 0;
                    font-family: monospace;
                }
                .endpoints a:hover {
                    text-decoration: underline;
                }
                .status {
                    display: inline-block;
                    padding: 4px 12px;
                    border-radius: 20px;
                    font-size: 0.875rem;
                    font-weight: 600;
                    background: #d4edda;
                    color: #155724;
                }
            </style>
        </head>
        <body>
            <div class="version-card">
                <h1 class="version-number">v<?php echo esc_html($version); ?></h1>
                <div class="plugin-title"><?php echo esc_html($plugin_data['Name'] ?: 'Terpedia Plugin'); ?></div>
                <div class="status">Active</div>
                
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Plugin Version</div>
                        <div class="info-value"><?php echo esc_html($version); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Header Version</div>
                        <div class="info-value"><?php echo esc_html($plugin_data['Version']); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">WordPress</div>
                        <div class="info-value"><?php echo esc_html(get_bloginfo('version')); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">PHP Version</div>
                        <div class="info-value"><?php echo esc_html(PHP_VERSION); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Last Modified</div>
                        <div class="info-value"><?php echo esc_html(date('Y-m-d H:i:s', filemtime(TERPEDIA_AI_PATH . 'terpedia.php'))); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Author</div>
                        <div class="info-value"><?php echo esc_html($plugin_data['Author'] ?: 'Terpedia Team'); ?></div>
                    </div>
                </div>
                
                <div class="endpoints">
                    <h3>🔗 API Endpoints</h3>
                    <a href="<?php echo esc_url(home_url('/wp-json/terpedia/v1/version')); ?>">/wp-json/terpedia/v1/version</a>
                    <a href="<?php echo esc_url(home_url('/wp-json/terpedia/v1/version/number')); ?>">/wp-json/terpedia/v1/version/number</a>
                    <a href="<?php echo esc_url(home_url('/version')); ?>">/version</a>
                </div>
            </div>
        </body>
        </html>
        <?php
    }
}

// Initialize the version endpoint
new Terpedia_Version_Endpoint();