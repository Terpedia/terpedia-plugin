<?php
/**
 * Version Management System for Terpedia Plugin
 */

class TerpediaPluginVersionManager {
    
    const PLUGIN_MAIN_PATH = __DIR__ . '/terpedia.php';
    const VERSION_FILE_PATH = __DIR__ . '/VERSION';
    
    public static function getCurrentVersion() {
        if (file_exists(self::VERSION_FILE_PATH)) {
            return trim(file_get_contents(self::VERSION_FILE_PATH));
        }
        
        // Fallback to plugin file
        $plugin_content = file_get_contents(self::PLUGIN_MAIN_PATH);
        if (preg_match('/Version:\s*([0-9]+\.[0-9]+\.[0-9]+)/', $plugin_content, $matches)) {
            return $matches[1];
        }
        
        return '1.0.0';
    }
    
    public static function incrementVersion($version_type = 'patch') {
        $current_version = self::getCurrentVersion();
        $version_parts = explode('.', $current_version);
        
        $major = intval($version_parts[0]);
        $minor = intval($version_parts[1]);
        $patch = intval($version_parts[2]);
        
        switch ($version_type) {
            case 'major':
                $major++;
                $minor = 0;
                $patch = 0;
                break;
            case 'minor':
                $minor++;
                $patch = 0;
                break;
            case 'patch':
            default:
                $patch++;
                break;
        }
        
        $new_version = "{$major}.{$minor}.{$patch}";
        self::updateVersion($new_version);
        return $new_version;
    }
    
    public static function updateVersion($new_version) {
        // Update VERSION file
        file_put_contents(self::VERSION_FILE_PATH, $new_version);
        
        // Update plugin main file
        $plugin_content = file_get_contents(self::PLUGIN_MAIN_PATH);
        $plugin_content = preg_replace(
            '/Version:\s*[0-9]+\.[0-9]+\.[0-9]+/',
            "Version: {$new_version}",
            $plugin_content
        );
        file_put_contents(self::PLUGIN_MAIN_PATH, $plugin_content);
        
        // Update any wp_enqueue_script/style calls with versions
        $plugin_content = preg_replace(
            "/wp_enqueue_style\('terpedia-css'[^,]+,[^,]+,\s*array\(\),\s*'[^']+'\)/",
            "wp_enqueue_style('terpedia-css', plugin_dir_url(__FILE__) . 'assets/terpedia.css', array(), '{$new_version}')",
            $plugin_content
        );
        $plugin_content = preg_replace(
            "/wp_enqueue_script\('terpedia-js'[^,]+,[^,]+,[^,]+,\s*'[^']+'/",
            "wp_enqueue_script('terpedia-js', plugin_dir_url(__FILE__) . 'assets/terpedia.js', array('jquery'), '{$new_version}'",
            $plugin_content
        );
        file_put_contents(self::PLUGIN_MAIN_PATH, $plugin_content);
        
        echo "Plugin version updated to: {$new_version}\n";
    }
    
    public static function autoIncrementOnDeploy() {
        // Check if this is a deployment environment
        $is_deployment = getenv('GITHUB_ACTIONS') === 'true' || getenv('CI') === 'true';
        
        if ($is_deployment) {
            // Only increment if we're on main/master branch to avoid conflicts
            $branch = getenv('GITHUB_REF_NAME') ?: getenv('GITHUB_HEAD_REF') ?: 'unknown';
            
            if (in_array($branch, ['main', 'master', 'production'])) {
                return self::incrementVersion('patch');
            } else {
                // For feature branches, just return current version
                return self::getCurrentVersion();
            }
        }
        
        return self::getCurrentVersion();
    }
}

// CLI Usage for deployment scripts
if (php_sapi_name() === 'cli') {
    $action = $argv[1] ?? 'current';
    $type = $argv[2] ?? 'patch';
    
    switch ($action) {
        case 'current':
            echo TerpediaPluginVersionManager::getCurrentVersion() . "\n";
            break;
        case 'increment':
            echo TerpediaPluginVersionManager::incrementVersion($type) . "\n";
            break;
        case 'auto':
            echo TerpediaPluginVersionManager::autoIncrementOnDeploy() . "\n";
            break;
        default:
            echo "Usage: php version-manager.php [current|increment|auto] [major|minor|patch]\n";
    }
}
?>