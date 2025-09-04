<?php
/**
 * Terpene PubMed Feeds Admin Interface
 * WordPress admin interface for managing terpene-specific PubMed RSS feeds
 */

if (!defined('ABSPATH')) {
    exit;
}

class TerpenePubMedAdmin {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_submenu_page(
            'terpedia-admin',
            'Terpene PubMed Feeds',
            'PubMed Feeds',
            'manage_options',
            'terpedia-terpene-pubmed',
            array($this, 'admin_page')
        );
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_scripts($hook) {
        if (strpos($hook, 'terpedia-terpene-pubmed') !== false) {
            wp_enqueue_script('jquery');
            wp_enqueue_style('terpedia-pubmed-admin', 
                plugin_dir_url(__FILE__) . '../assets/css/terpene-pubmed-admin.css', 
                array(), '1.0.0');
        }
    }
    
    /**
     * Admin page
     */
    public function admin_page() {
        $terpene_feeds = new TerpenePubMedFeeds();
        $all_feeds = $terpene_feeds->get_all_terpene_pubmed_feeds();
        
        ?>
        <div class="wrap">
            <h1>🌿 Terpene PubMed RSS Feeds</h1>
            
            <div class="terpedia-pubmed-overview">
                <div class="overview-stats">
                    <div class="stat-box">
                        <h3>Active Terpenes</h3>
                        <p class="stat-number"><?php echo count($all_feeds); ?></p>
                    </div>
                    <div class="stat-box">
                        <h3>Total PubMed Feeds</h3>
                        <p class="stat-number"><?php echo array_sum(array_map(function($config) { return count($config['feeds']); }, $all_feeds)); ?></p>
                    </div>
                    <div class="stat-box">
                        <h3>Last Updated</h3>
                        <p class="stat-number"><?php echo date('M j, Y'); ?></p>
                    </div>
                </div>
                
                <div class="overview-actions">
                    <button type="button" id="generate-all-feeds" class="button button-primary">
                        🚀 Generate All PubMed Feeds
                    </button>
                    <button type="button" id="test-all-feeds" class="button button-secondary">
                        🧪 Test All Feeds
                    </button>
                    <button type="button" id="refresh-feeds" class="button button-secondary">
                        🔄 Refresh Feed Data
                    </button>
                </div>
            </div>
            
            <div class="terpene-feeds-container">
                <?php foreach ($all_feeds as $terpene_type => $config): ?>
                    <div class="terpene-feed-card" data-terpene="<?php echo esc_attr($terpene_type); ?>">
                        <div class="terpene-header">
                            <h2>🧬 <?php echo esc_html(ucfirst($terpene_type)); ?> PubMed Feeds</h2>
                            <div class="terpene-actions">
                                <button type="button" class="button button-small test-terpene-feeds" data-terpene="<?php echo esc_attr($terpene_type); ?>">
                                    Test Feeds
                                </button>
                                <button type="button" class="button button-small view-agent-profile" data-agent-id="<?php echo esc_attr($config['agent_id']); ?>">
                                    View Agent
                                </button>
                            </div>
                        </div>
                        
                        <div class="terpene-info">
                            <p><strong>Agent:</strong> <?php echo esc_html($config['agent_name']); ?></p>
                            <p><strong>Keywords:</strong> <?php echo esc_html($config['keywords']); ?></p>
                        </div>
                        
                        <div class="pubmed-feeds-list">
                            <?php foreach ($config['feeds'] as $feed): ?>
                                <div class="pubmed-feed-item">
                                    <div class="feed-header">
                                        <h4><?php echo esc_html($feed['name']); ?></h4>
                                        <div class="feed-actions">
                                            <button type="button" class="button button-small test-single-feed" 
                                                    data-feed-url="<?php echo esc_attr($feed['url']); ?>"
                                                    data-terpene="<?php echo esc_attr($terpene_type); ?>">
                                                Test
                                            </button>
                                            <a href="<?php echo esc_url($feed['url']); ?>" target="_blank" class="button button-small">
                                                View RSS
                                            </a>
                                        </div>
                                    </div>
                                    <div class="feed-details">
                                        <p><strong>URL:</strong> <code><?php echo esc_html($feed['url']); ?></code></p>
                                        <p><strong>Keywords:</strong> <?php echo esc_html($feed['keywords']); ?></p>
                                    </div>
                                    <div class="feed-status" id="status-<?php echo esc_attr(md5($feed['url'])); ?>">
                                        <span class="status-indicator">⏳</span> <span class="status-text">Not tested</span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="terpene-search-suggestions">
                            <h4>🔍 PubMed Search Suggestions for <?php echo esc_html(ucfirst($terpene_type)); ?>:</h4>
                            <ul>
                                <?php 
                                $suggestions = $terpene_feeds->get_terpene_search_suggestions($terpene_type);
                                foreach ($suggestions as $suggestion): 
                                ?>
                                    <li><code><?php echo esc_html($suggestion); ?></code></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="pubmed-feed-generation">
                <h2>🛠️ PubMed Feed Generation Tools</h2>
                
                <div class="generation-tools">
                    <div class="tool-section">
                        <h3>Create Custom PubMed Feed</h3>
                        <form id="custom-pubmed-form">
                            <table class="form-table">
                                <tr>
                                    <th scope="row">Terpene Type</th>
                                    <td>
                                        <select name="terpene_type" id="custom-terpene-type">
                                            <option value="myrcene">Myrcene</option>
                                            <option value="limonene">Limonene</option>
                                            <option value="pinene">Pinene</option>
                                            <option value="caryophyllene">Caryophyllene</option>
                                            <option value="linalool">Linalool</option>
                                            <option value="humulene">Humulene</option>
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">Search Terms</th>
                                    <td>
                                        <input type="text" name="search_terms" id="custom-search-terms" 
                                               placeholder="e.g., myrcene AND pain AND clinical trial" 
                                               style="width: 100%;" />
                                        <p class="description">Use PubMed search syntax (AND, OR, NOT)</p>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">Feed Name</th>
                                    <td>
                                        <input type="text" name="feed_name" id="custom-feed-name" 
                                               placeholder="e.g., Myrcene Pain Research" 
                                               style="width: 100%;" />
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">Item Limit</th>
                                    <td>
                                        <select name="item_limit" id="custom-item-limit">
                                            <option value="10">10 items</option>
                                            <option value="15">15 items</option>
                                            <option value="20" selected>20 items</option>
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">&nbsp;</th>
                                    <td>
                                        <button type="submit" class="button button-primary">Generate PubMed RSS URL</button>
                                    </td>
                                </tr>
                            </table>
                        </form>
                        
                        <div id="custom-feed-result" style="display: none;">
                            <h4>Generated PubMed RSS Feed:</h4>
                            <div class="generated-feed">
                                <p><strong>Feed URL:</strong></p>
                                <input type="text" id="generated-feed-url" readonly style="width: 100%;" />
                                <button type="button" id="copy-feed-url" class="button button-small">Copy URL</button>
                                <a href="#" id="test-generated-feed" target="_blank" class="button button-small">Test Feed</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <style>
        .terpedia-pubmed-overview {
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            padding: 20px;
            margin: 20px 0;
        }
        
        .overview-stats {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .stat-box {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 4px;
            padding: 15px;
            text-align: center;
            min-width: 120px;
        }
        
        .stat-box h3 {
            margin: 0 0 10px 0;
            font-size: 14px;
            color: #666;
        }
        
        .stat-number {
            font-size: 24px;
            font-weight: bold;
            margin: 0;
            color: #0073aa;
        }
        
        .overview-actions {
            display: flex;
            gap: 10px;
        }
        
        .terpene-feed-card {
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            margin: 20px 0;
            padding: 20px;
        }
        
        .terpene-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e9ecef;
        }
        
        .terpene-header h2 {
            margin: 0;
            color: #0073aa;
        }
        
        .terpene-actions {
            display: flex;
            gap: 5px;
        }
        
        .terpene-info {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
        }
        
        .pubmed-feeds-list {
            margin: 15px 0;
        }
        
        .pubmed-feed-item {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 4px;
            padding: 15px;
            margin: 10px 0;
        }
        
        .feed-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .feed-header h4 {
            margin: 0;
            color: #333;
        }
        
        .feed-actions {
            display: flex;
            gap: 5px;
        }
        
        .feed-details {
            margin: 10px 0;
        }
        
        .feed-details code {
            background: #e9ecef;
            padding: 2px 4px;
            border-radius: 3px;
            font-size: 12px;
            word-break: break-all;
        }
        
        .feed-status {
            margin-top: 10px;
            padding: 5px 10px;
            border-radius: 3px;
            background: #f8f9fa;
        }
        
        .status-indicator {
            margin-right: 5px;
        }
        
        .terpene-search-suggestions {
            background: #e7f3ff;
            border: 1px solid #b3d9ff;
            border-radius: 4px;
            padding: 15px;
            margin-top: 15px;
        }
        
        .terpene-search-suggestions h4 {
            margin: 0 0 10px 0;
            color: #0073aa;
        }
        
        .terpene-search-suggestions ul {
            margin: 0;
            padding-left: 20px;
        }
        
        .terpene-search-suggestions li {
            margin: 5px 0;
        }
        
        .terpene-search-suggestions code {
            background: #fff;
            padding: 2px 6px;
            border-radius: 3px;
            border: 1px solid #b3d9ff;
        }
        
        .pubmed-feed-generation {
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            padding: 20px;
            margin: 20px 0;
        }
        
        .generation-tools {
            margin-top: 20px;
        }
        
        .tool-section {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 4px;
            padding: 20px;
        }
        
        .generated-feed {
            background: #fff;
            border: 1px solid #e9ecef;
            border-radius: 4px;
            padding: 15px;
            margin-top: 15px;
        }
        
        .generated-feed input {
            margin: 5px 0;
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            // Generate all feeds
            $('#generate-all-feeds').on('click', function() {
                if (confirm('This will generate PubMed RSS feeds for all terpene agents. Continue?')) {
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'generate_terpene_pubmed_feeds',
                            nonce: '<?php echo wp_create_nonce('terpedia_pubmed_feeds'); ?>'
                        },
                        success: function(response) {
                            if (response.success) {
                                alert('PubMed feeds generated successfully!');
                                location.reload();
                            } else {
                                alert('Error: ' + response.data);
                            }
                        }
                    });
                }
            });
            
            // Test single feed
            $('.test-single-feed').on('click', function() {
                var feedUrl = $(this).data('feed-url');
                var terpene = $(this).data('terpene');
                var statusId = 'status-' + btoa(feedUrl).replace(/[^a-zA-Z0-9]/g, '');
                
                $(this).prop('disabled', true).text('Testing...');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'test_terpene_pubmed_feed',
                        terpene_type: terpene,
                        feed_url: feedUrl,
                        nonce: '<?php echo wp_create_nonce('terpedia_pubmed_feeds'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#' + statusId).html('<span class="status-indicator">✅</span> <span class="status-text">' + response.data.message + '</span>');
                        } else {
                            $('#' + statusId).html('<span class="status-indicator">❌</span> <span class="status-text">' + response.data + '</span>');
                        }
                    },
                    complete: function() {
                        $('.test-single-feed[data-feed-url="' + feedUrl + '"]').prop('disabled', false).text('Test');
                    }
                });
            });
            
            // Custom feed generation
            $('#custom-pubmed-form').on('submit', function(e) {
                e.preventDefault();
                
                var terpeneType = $('#custom-terpene-type').val();
                var searchTerms = $('#custom-search-terms').val();
                var feedName = $('#custom-feed-name').val();
                var itemLimit = $('#custom-item-limit').val();
                
                if (!searchTerms) {
                    alert('Please enter search terms');
                    return;
                }
                
                // Generate a mock PubMed RSS URL (in real implementation, you'd call PubMed API)
                var encodedSearch = encodeURIComponent(searchTerms);
                var mockHash = btoa(encodedSearch).substring(0, 20);
                var feedUrl = 'https://pubmed.ncbi.nlm.nih.gov/rss/search/' + mockHash + '/?limit=' + itemLimit + '&utm_campaign=pubmed-2';
                
                $('#generated-feed-url').val(feedUrl);
                $('#test-generated-feed').attr('href', feedUrl);
                $('#custom-feed-result').show();
            });
            
            // Copy feed URL
            $('#copy-feed-url').on('click', function() {
                $('#generated-feed-url').select();
                document.execCommand('copy');
                $(this).text('Copied!');
                setTimeout(() => $(this).text('Copy URL'), 2000);
            });
        });
        </script>
        <?php
    }
}

// Initialize the admin interface
new TerpenePubMedAdmin();
