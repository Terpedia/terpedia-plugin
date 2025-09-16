<?php
/**
 * Reactivate plugin to refresh CPT registrations
 * Run this once to force plugin reactivation
 */

// Deactivate and reactivate the plugin
if (is_plugin_active('terpedia-plugin/terpedia.php')) {
    deactivate_plugins('terpedia-plugin/terpedia.php');
    echo "Plugin deactivated<br>";
    
    // Wait a moment
    sleep(1);
    
    activate_plugin('terpedia-plugin/terpedia.php');
    echo "Plugin reactivated<br>";
    
    // Flush rewrite rules
    flush_rewrite_rules();
    echo "Rewrite rules flushed<br>";
    
    echo "✅ Plugin reactivation complete";
} else {
    echo "❌ Plugin is not active";
}
