<?php
/**
 * Terpedia Dashboard Widget
 * Displays plugin & theme versions and last updated information
 */

if (!defined('ABSPATH')) {
    exit;
}

class Terpedia_Dashboard_Widget {
    
    public function __construct() {
        add_action('wp_dashboard_setup', array($this, 'add_dashboard_widget'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_styles'));
    }
    
    /**
     * Add the dashboard widget
     */
    public function add_dashboard_widget() {
        wp_add_dashboard_widget(
            'terpedia_version_info',
            'Terpedia System Information',
            array($this, 'render_dashboard_widget')
        );
    }
    
    /**
     * Render the dashboard widget content
     */
    public function render_dashboard_widget() {
        $plugin_info = $this->get_plugin_info();
        $theme_info = $this->get_theme_info();
        $last_updated = $this->get_last_updated_info();
        
        ?>
        <div class="terpedia-dashboard-widget">
            <div class="terpedia-widget-header">
                <h3 style="margin: 0 0 15px 0; color: #2c5aa0; display: flex; align-items: center;">
                    <span style="font-size: 20px; margin-right: 8px;">🧬</span>
                    Terpedia System Status
                </h3>
            </div>
            
            <div class="terpedia-widget-content">
                <!-- Plugin Information -->
                <div class="terpedia-info-section">
                    <h4 style="margin: 0 0 10px 0; color: #333; font-size: 14px; font-weight: 600;">
                        <span style="color: #2c5aa0;">📦</span> Plugin Information
                    </h4>
                    <div class="terpedia-info-grid">
                        <div class="terpedia-info-item">
                            <span class="terpedia-label">Plugin Name:</span>
                            <span class="terpedia-value"><?php echo esc_html($plugin_info['name']); ?></span>
                        </div>
                        <div class="terpedia-info-item">
                            <span class="terpedia-label">Version:</span>
                            <span class="terpedia-value terpedia-version"><?php echo esc_html($plugin_info['version']); ?></span>
                        </div>
                        <div class="terpedia-info-item">
                            <span class="terpedia-label">Status:</span>
                            <span class="terpedia-value terpedia-status-active">✅ Active</span>
                        </div>
                    </div>
                </div>
                
                <!-- Theme Information -->
                <div class="terpedia-info-section">
                    <h4 style="margin: 15px 0 10px 0; color: #333; font-size: 14px; font-weight: 600;">
                        <span style="color: #2c5aa0;">🎨</span> Active Theme
                    </h4>
                    <div class="terpedia-info-grid">
                        <div class="terpedia-info-item">
                            <span class="terpedia-label">Theme Name:</span>
                            <span class="terpedia-value"><?php echo esc_html($theme_info['name']); ?></span>
                        </div>
                        <div class="terpedia-info-item">
                            <span class="terpedia-label">Version:</span>
                            <span class="terpedia-value terpedia-version"><?php echo esc_html($theme_info['version']); ?></span>
                        </div>
                        <div class="terpedia-info-item">
                            <span class="terpedia-label">Author:</span>
                            <span class="terpedia-value"><?php echo esc_html($theme_info['author']); ?></span>
                        </div>
                    </div>
                </div>
                
                <!-- Last Updated Information -->
                <div class="terpedia-info-section">
                    <h4 style="margin: 15px 0 10px 0; color: #333; font-size: 14px; font-weight: 600;">
                        <span style="color: #2c5aa0;">🕒</span> Last Updated
                    </h4>
                    <div class="terpedia-info-grid">
                        <div class="terpedia-info-item">
                            <span class="terpedia-label">Plugin Updated:</span>
                            <span class="terpedia-value"><?php echo esc_html($last_updated['plugin']); ?></span>
                        </div>
                        <div class="terpedia-info-item">
                            <span class="terpedia-label">Theme Updated:</span>
                            <span class="terpedia-value"><?php echo esc_html($last_updated['theme']); ?></span>
                        </div>
                        <div class="terpedia-info-item">
                            <span class="terpedia-label">WordPress:</span>
                            <span class="terpedia-value"><?php echo esc_html($last_updated['wordpress']); ?></span>
                        </div>
                    </div>
                </div>
                
                <!-- Development Roadmap -->
                <div class="terpedia-roadmap-section" style="margin-top: 20px; padding-top: 15px; border-top: 1px solid #e1e1e1;">
                    <h4 style="margin: 0 0 10px 0; color: #333; font-size: 14px; font-weight: 600;">
                        <span style="color: #2c5aa0;">🎯</span> Development Roadmap
                    </h4>
                    
                    <!-- Critical Tasks -->
                    <div class="roadmap-priority-section" style="margin-bottom: 15px;">
                        <h5 style="margin: 0 0 8px 0; color: #d32f2f; font-size: 12px; font-weight: 600;">
                            🔥 CRITICAL (Next 2 Weeks)
                        </h5>
                        <div class="roadmap-tasks" style="font-size: 11px; line-height: 1.4;">
                            <div class="roadmap-task" style="margin-bottom: 4px; color: #666;">
                                <span style="color: #d32f2f;">●</span> Documentation Alignment - Update README.md to match implementation
                            </div>
                            <div class="roadmap-task" style="margin-bottom: 4px; color: #666;">
                                <span style="color: #d32f2f;">●</span> Core System Completion - Complete AI Agent System (13 agents)
                            </div>
                            <div class="roadmap-task" style="margin-bottom: 4px; color: #666;">
                                <span style="color: #d32f2f;">●</span> Version Sync - Update README version to <?php echo esc_html(TERPEDIA_AI_VERSION); ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- High Priority Tasks -->
                    <div class="roadmap-priority-section" style="margin-bottom: 15px;">
                        <h5 style="margin: 0 0 8px 0; color: #ff9800; font-size: 12px; font-weight: 600;">
                            🚀 HIGH PRIORITY (Next Month)
                        </h5>
                        <div class="roadmap-tasks" style="font-size: 11px; line-height: 1.4;">
                            <div class="roadmap-task" style="margin-bottom: 4px; color: #666;">
                                <span style="color: #ff9800;">●</span> TULIP System - Certified Truth Database
                            </div>
                            <div class="roadmap-task" style="margin-bottom: 4px; color: #666;">
                                <span style="color: #ff9800;">●</span> Content Scanner - Universal auto-linking system
                            </div>
                            <div class="roadmap-task" style="margin-bottom: 4px; color: #666;">
                                <span style="color: #ff9800;">●</span> Enhanced Recipe System - Copy & versioning
                            </div>
                        </div>
                    </div>
                    
                    <!-- Progress Indicators -->
                    <div class="roadmap-progress" style="margin-bottom: 12px;">
                        <div style="font-size: 11px; color: #666; margin-bottom: 6px;">Development Progress:</div>
                        <div style="display: flex; gap: 8px; align-items: center;">
                            <div style="flex: 1; background: #f0f0f0; border-radius: 8px; height: 6px; overflow: hidden;">
                                <div style="background: linear-gradient(to right, #d32f2f 0%, #ff9800 70%, #4caf50 100%); height: 100%; width: 35%; border-radius: 8px;"></div>
                            </div>
                            <span style="font-size: 10px; color: #666;">35% Complete</span>
                        </div>
                    </div>
                    
                    <!-- Quick Links -->
                    <div style="border-top: 1px solid #f0f0f0; padding-top: 10px;">
                        <div style="font-size: 11px;">
                            <a href="https://github.com/terpedia/roadmap" target="_blank" style="color: #2c5aa0; text-decoration: none; margin-right: 12px;">
                                📋 Full Roadmap
                            </a>
                            <a href="https://github.com/terpedia/issues" target="_blank" style="color: #2c5aa0; text-decoration: none;">
                                🐛 Report Issues
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="terpedia-quick-actions" style="margin-top: 20px; padding-top: 15px; border-top: 1px solid #e1e1e1;">
                    <h4 style="margin: 0 0 10px 0; color: #333; font-size: 14px; font-weight: 600;">
                        <span style="color: #2c5aa0;">⚡</span> Quick Actions
                    </h4>
                    <div class="terpedia-action-buttons">
                        <a href="<?php echo admin_url('admin.php?page=terpedia-settings'); ?>" 
                           class="terpedia-action-btn" 
                           style="display: inline-block; margin-right: 10px; margin-bottom: 8px;">
                            <span style="margin-right: 5px;">⚙️</span>Plugin Settings
                        </a>
                        <a href="<?php echo admin_url('themes.php'); ?>" 
                           class="terpedia-action-btn" 
                           style="display: inline-block; margin-right: 10px; margin-bottom: 8px;">
                            <span style="margin-right: 5px;">🎨</span>Themes
                        </a>
                        <a href="<?php echo admin_url('update-core.php'); ?>" 
                           class="terpedia-action-btn" 
                           style="display: inline-block; margin-right: 10px; margin-bottom: 8px;">
                            <span style="margin-right: 5px;">🔄</span>Updates
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Get plugin information
     */
    private function get_plugin_info() {
        if (!function_exists('get_plugin_data')) {
            require_once(ABSPATH . 'wp-admin/includes/plugin.php');
        }
        
        $plugin_file = plugin_dir_path(__FILE__) . '../terpedia.php';
        $plugin_data = get_plugin_data($plugin_file);
        
        return array(
            'name' => $plugin_data['Name'] ?? 'Terpedia',
            'version' => $plugin_data['Version'] ?? TERPEDIA_AI_VERSION,
            'description' => $plugin_data['Description'] ?? 'Comprehensive terpene encyclopedia with AI agents and research tools',
            'author' => $plugin_data['Author'] ?? 'Terpedia Team'
        );
    }
    
    /**
     * Get active theme information
     */
    private function get_theme_info() {
        $theme = wp_get_theme();
        
        return array(
            'name' => $theme->get('Name') ?? 'Unknown Theme',
            'version' => $theme->get('Version') ?? '1.0.0',
            'author' => $theme->get('Author') ?? 'Unknown Author',
            'description' => $theme->get('Description') ?? 'No description available'
        );
    }
    
    /**
     * Get last updated information
     */
    private function get_last_updated_info() {
        // Plugin last updated (use file modification time)
        $plugin_file = plugin_dir_path(__FILE__) . '../terpedia.php';
        $plugin_updated = file_exists($plugin_file) ? 
            date('M j, Y g:i A', filemtime($plugin_file)) : 
            'Unknown';
        
        // Theme last updated (use theme directory modification time)
        $theme = wp_get_theme();
        $theme_dir = $theme->get_stylesheet_directory();
        $theme_updated = file_exists($theme_dir) ? 
            date('M j, Y g:i A', filemtime($theme_dir)) : 
            'Unknown';
        
        // WordPress version
        global $wp_version;
        $wp_updated = 'WordPress ' . $wp_version;
        
        return array(
            'plugin' => $plugin_updated,
            'theme' => $theme_updated,
            'wordpress' => $wp_updated
        );
    }
    
    /**
     * Enqueue admin styles for the dashboard widget and pink menu styling
     */
    public function enqueue_admin_styles($hook) {
        // Always enqueue styles for admin menu styling
        if (strpos($hook, 'terpedia') !== false || $hook === 'index.php' || $hook === 'admin.php') {
            $this->add_terpedia_menu_styles();
        }
        
        if ($hook !== 'index.php') {
            return;
        }
        
        ?>
        <style>
        .terpedia-dashboard-widget {
            background: #fff;
            border-radius: 8px;
            overflow: hidden;
        }
        
        .terpedia-widget-header {
            background: linear-gradient(135deg, #2c5aa0 0%, #1e3a8a 100%);
            color: white;
            padding: 15px 20px;
            margin: -12px -12px 0 -12px;
        }
        
        .terpedia-widget-content {
            padding: 20px;
        }
        
        .terpedia-info-section {
            margin-bottom: 15px;
        }
        
        .terpedia-info-grid {
            display: grid;
            gap: 8px;
        }
        
        .terpedia-info-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 12px;
            background: #f8f9fa;
            border-radius: 6px;
            border-left: 3px solid #2c5aa0;
        }
        
        .terpedia-label {
            font-weight: 600;
            color: #495057;
            font-size: 13px;
        }
        
        .terpedia-value {
            font-weight: 500;
            color: #212529;
            font-size: 13px;
        }
        
        .terpedia-version {
            background: #e3f2fd;
            color: #1976d2;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .terpedia-status-active {
            color: #28a745;
            font-weight: 600;
        }
        
        .terpedia-action-btn {
            background: #2c5aa0;
            color: white;
            text-decoration: none;
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 500;
            transition: all 0.2s ease;
        }
        
        .terpedia-action-btn:hover {
            background: #1e3a8a;
            color: white;
            text-decoration: none;
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(44, 90, 160, 0.3);
        }
        
        .terpedia-action-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .terpedia-info-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 4px;
            }
            
            .terpedia-action-buttons {
                flex-direction: column;
            }
            
            .terpedia-action-btn {
                text-align: center;
                margin-right: 0 !important;
            }
        }
        </style>
        <?php
    }
    
    /**
     * Add pink styling for Terpedia admin menu items
     */
    private function add_terpedia_menu_styles() {
        ?>
        <style>
        /* Pink styling for Terpedia admin menu items */
        #adminmenu .wp-has-submenu.toplevel_page_terpedia-settings a.wp-has-submenu,
        #adminmenu .wp-has-submenu.toplevel_page_terpedia-settings .wp-menu-name,
        #adminmenu .wp-has-submenu.toplevel_page_terpedia-settings .wp-menu-arrow,
        #adminmenu .wp-has-submenu.toplevel_page_terpedia-settings .wp-menu-arrow:after {
            color: #e91e63 !important; /* Pink color */
        }
        
        #adminmenu .wp-has-submenu.toplevel_page_terpedia-settings:hover .wp-menu-name,
        #adminmenu .wp-has-submenu.toplevel_page_terpedia-settings:hover .wp-menu-arrow,
        #adminmenu .wp-has-submenu.toplevel_page_terpedia-settings:hover .wp-menu-arrow:after {
            color: #c2185b !important; /* Darker pink on hover */
        }
        
        #adminmenu .wp-has-submenu.toplevel_page_terpedia-settings.current .wp-menu-name,
        #adminmenu .wp-has-submenu.toplevel_page_terpedia-settings.current .wp-menu-arrow,
        #adminmenu .wp-has-submenu.toplevel_page_terpedia-settings.current .wp-menu-arrow:after {
            color: #ad1457 !important; /* Even darker pink when current */
        }
        
        /* Pink styling for Terpedia submenu items */
        #adminmenu .wp-submenu a[href*="terpedia"] {
            color: #e91e63 !important; /* Pink color for submenu items */
        }
        
        #adminmenu .wp-submenu a[href*="terpedia"]:hover {
            color: #c2185b !important; /* Darker pink on hover */
        }
        
        /* Pink styling for OpenRouter submenu */
        #adminmenu .wp-submenu a[href*="terpedia-openrouter"] {
            color: #e91e63 !important;
        }
        
        #adminmenu .wp-submenu a[href*="terpedia-openrouter"]:hover {
            color: #c2185b !important;
        }
        
        /* Ensure the menu icon stays visible */
        #adminmenu .toplevel_page_terpedia-settings .wp-menu-image:before {
            color: #e91e63 !important;
        }
        
        #adminmenu .toplevel_page_terpedia-settings:hover .wp-menu-image:before {
            color: #c2185b !important;
        }
        
        /* Pink background highlight for active Terpedia menu */
        #adminmenu .toplevel_page_terpedia-settings.current,
        #adminmenu .toplevel_page_terpedia-settings.current:hover {
            background-color: rgba(233, 30, 99, 0.1) !important;
        }
        
        /* Pink border for active Terpedia submenu items */
        #adminmenu .wp-submenu a[href*="terpedia"].current {
            border-left: 3px solid #e91e63 !important;
            background-color: rgba(233, 30, 99, 0.1) !important;
        }
        </style>
        <?php
    }
}

// Initialize the dashboard widget
new Terpedia_Dashboard_Widget();
