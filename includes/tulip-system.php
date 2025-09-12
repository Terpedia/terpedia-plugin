
<?php
/**
 * TULIP - Terpene Universal Link & Information Protocol
 * Certified truth database with cross-referenced facts throughout Terpedia
 */

if (!defined('ABSPATH')) {
    exit;
}

class TerpediaTULIPSystem {
    
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_ajax_tulip_add_fact', array($this, 'ajax_add_fact'));
        add_action('wp_ajax_tulip_verify_fact', array($this, 'ajax_verify_fact'));
        add_action('wp_ajax_tulip_search_facts', array($this, 'ajax_search_facts'));
        add_filter('the_content', array($this, 'process_tulip_links'));
        add_shortcode('tulip_fact', array($this, 'tulip_fact_shortcode'));
        add_shortcode('tulip_search', array($this, 'tulip_search_shortcode'));
    }
    
    public function init() {
        $this->create_tulip_tables();
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }
    
    /**
     * Create TULIP database tables
     */
    private function create_tulip_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // TULIP Facts table
        $facts_table = $wpdb->prefix . 'tulip_facts';
        $facts_sql = "CREATE TABLE $facts_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            fact_id varchar(50) NOT NULL UNIQUE,
            title varchar(255) NOT NULL,
            statement longtext NOT NULL,
            evidence_level enum('verified', 'peer_reviewed', 'preliminary', 'anecdotal') DEFAULT 'preliminary',
            terpene_name varchar(100),
            category varchar(100),
            sources longtext,
            citations longtext,
            verification_notes longtext,
            created_by bigint(20) UNSIGNED,
            verified_by bigint(20) UNSIGNED,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            verified_at datetime,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            status enum('draft', 'pending', 'verified', 'disputed') DEFAULT 'draft',
            view_count int(11) DEFAULT 0,
            link_count int(11) DEFAULT 0,
            PRIMARY KEY (id),
            KEY fact_id (fact_id),
            KEY terpene_name (terpene_name),
            KEY evidence_level (evidence_level),
            KEY status (status),
            KEY category (category)
        ) $charset_collate;";
        
        // TULIP Links table (tracks where facts are referenced)
        $links_table = $wpdb->prefix . 'tulip_links';
        $links_sql = "CREATE TABLE $links_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            fact_id varchar(50) NOT NULL,
            post_id bigint(20) UNSIGNED,
            link_type enum('automatic', 'manual', 'citation') DEFAULT 'automatic',
            context_snippet text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY fact_id (fact_id),
            KEY post_id (post_id),
            KEY link_type (link_type)
        ) $charset_collate;";
        
        // TULIP Verification Log
        $verification_table = $wpdb->prefix . 'tulip_verification_log';
        $verification_sql = "CREATE TABLE $verification_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            fact_id varchar(50) NOT NULL,
            verifier_id bigint(20) UNSIGNED NOT NULL,
            action enum('verify', 'dispute', 'update', 'flag') NOT NULL,
            notes longtext,
            previous_status varchar(20),
            new_status varchar(20),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY fact_id (fact_id),
            KEY verifier_id (verifier_id),
            KEY action (action)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($facts_sql);
        dbDelta($links_sql);
        dbDelta($verification_sql);
        
        // Insert sample TULIP facts
        $this->insert_sample_facts();
    }
    
    /**
     * Insert sample TULIP facts
     */
    private function insert_sample_facts() {
        global $wpdb;
        
        $facts_table = $wpdb->prefix . 'tulip_facts';
        
        // Check if facts already exist
        $existing = $wpdb->get_var("SELECT COUNT(*) FROM $facts_table");
        if ($existing > 0) {
            return;
        }
        
        $sample_facts = array(
            array(
                'fact_id' => 'TULIP-001',
                'title' => 'Myrcene Sedative Effects',
                'statement' => 'Myrcene (β-myrcene) demonstrates sedative properties through interaction with GABA receptors and enhancement of barbiturate sleep time in animal studies.',
                'evidence_level' => 'peer_reviewed',
                'terpene_name' => 'myrcene',
                'category' => 'pharmacology',
                'sources' => json_encode(array(
                    'PubMed:123456' => 'Myrcene and sleep enhancement study',
                    'DOI:10.1000/xyz' => 'GABA receptor interaction research'
                )),
                'citations' => json_encode(array(
                    'Vale TG, et al. Evaluation of the sedative and anxiolytic effects of the essential oil of Lippia alba. Braz J Med Biol Res. 1999;32(4):489-494.',
                    'Rao VS, et al. Myrcene mimics the peripheral analgesic activity of lemongrass tea. J Ethnopharmacol. 1990;34(1):43-48.'
                )),
                'status' => 'verified'
            ),
            array(
                'fact_id' => 'TULIP-002',
                'title' => 'Limonene Mood Enhancement',
                'statement' => 'D-Limonene exhibits anxiolytic and antidepressant-like effects through modulation of serotonin and dopamine pathways.',
                'evidence_level' => 'peer_reviewed',
                'terpene_name' => 'limonene',
                'category' => 'neuropharmacology',
                'sources' => json_encode(array(
                    'PubMed:789012' => 'Limonene anxiolytic effects study',
                    'DOI:10.1000/abc' => 'Serotonin pathway modulation'
                )),
                'citations' => json_encode(array(
                    'Lima NG, et al. Anxiolytic-like activity and GC-MS analysis of (R)-(+)-limonene fragrance. Eur J Pharmacol. 2013;718(1-3):148-155.',
                    'Komori T, et al. Effects of citrus fragrance on immune function and depressive states. Neuroimmunomodulation. 1995;2(3):174-180.'
                )),
                'status' => 'verified'
            ),
            array(
                'fact_id' => 'TULIP-003',
                'title' => 'α-Pinene Memory Enhancement',
                'statement' => 'α-Pinene may counteract short-term memory impairment through acetylcholinesterase inhibition and enhanced neural transmission.',
                'evidence_level' => 'preliminary',
                'terpene_name' => 'pinene',
                'category' => 'cognitive_function',
                'sources' => json_encode(array(
                    'PubMed:345678' => 'Pinene acetylcholinesterase study'
                )),
                'citations' => json_encode(array(
                    'Perry NS, et al. In-vitro activity of S. lavandulaefolia (Spanish sage) relevant to treatment of Alzheimer\'s disease. J Pharm Pharmacol. 2001;53(10):1347-1356.'
                )),
                'status' => 'pending'
            ),
            array(
                'fact_id' => 'TULIP-004',
                'title' => 'β-Caryophyllene CB2 Activation',
                'statement' => 'β-Caryophyllene is a selective agonist of the CB2 cannabinoid receptor, making it the first non-cannabinoid to directly activate cannabinoid receptors.',
                'evidence_level' => 'verified',
                'terpene_name' => 'caryophyllene',
                'category' => 'cannabinoid_system',
                'sources' => json_encode(array(
                    'PubMed:567890' => 'Caryophyllene CB2 receptor binding study'
                )),
                'citations' => json_encode(array(
                    'Gertsch J, et al. Beta-caryophyllene is a dietary cannabinoid. Proc Natl Acad Sci USA. 2008;105(26):9099-9104.',
                    'Klauke AL, et al. The cannabinoid CB2 receptor-selective phytocannabinoid beta-caryophyllene exerts analgesic effects. Eur J Pharmacol. 2014;750:125-132.'
                )),
                'status' => 'verified'
            ),
            array(
                'fact_id' => 'TULIP-005',
                'title' => 'Linalool Anti-Anxiety Properties',
                'statement' => 'Linalool demonstrates anxiolytic effects through modulation of glutamatergic transmission and GABA receptor enhancement.',
                'evidence_level' => 'peer_reviewed',
                'terpene_name' => 'linalool',
                'category' => 'anxiety_treatment',
                'sources' => json_encode(array(
                    'PubMed:678901' => 'Linalool GABA receptor study'
                )),
                'citations' => json_encode(array(
                    'Linck VM, et al. Effects of inhaled Linalool in anxiety, social interaction and aggressive behavior in mice. Phytomedicine. 2010;17(8-9):679-683.'
                )),
                'status' => 'verified'
            )
        );
        
        foreach ($sample_facts as $fact) {
            $wpdb->insert($facts_table, $fact);
        }
    }
    
    /**
     * Add admin menu for TULIP management
     */
    public function add_admin_menu() {
        add_submenu_page(
            'terpedia-main',
            'TULIP Facts Database',
            'TULIP Database',
            'manage_options',
            'tulip-database',
            array($this, 'admin_page')
        );
    }
    
    /**
     * Enqueue scripts and styles
     */
    public function enqueue_scripts() {
        wp_enqueue_script('tulip-system', plugin_dir_url(__FILE__) . '../assets/js/tulip-system.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('tulip-system', plugin_dir_url(__FILE__) . '../assets/css/tulip-system.css', array(), '1.0.0');
        
        wp_localize_script('tulip-system', 'tulip_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('tulip_nonce')
        ));
    }
    
    /**
     * Admin page for TULIP management
     */
    public function admin_page() {
        global $wpdb;
        
        $facts_table = $wpdb->prefix . 'tulip_facts';
        $facts = $wpdb->get_results("SELECT * FROM $facts_table ORDER BY created_at DESC LIMIT 50");
        
        ?>
        <div class="wrap">
            <h1>🌐 TULIP - Terpene Universal Link & Information Protocol</h1>
            <p>Manage certified truth database with cross-referenced facts throughout Terpedia.</p>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 20px 0;">
                <div style="background: #fff; padding: 15px; border-radius: 8px; border-left: 4px solid #0073aa;">
                    <h3>📊 Database Stats</h3>
                    <p><strong>Total Facts:</strong> <?php echo $wpdb->get_var("SELECT COUNT(*) FROM $facts_table"); ?></p>
                    <p><strong>Verified:</strong> <?php echo $wpdb->get_var("SELECT COUNT(*) FROM $facts_table WHERE status = 'verified'"); ?></p>
                    <p><strong>Pending:</strong> <?php echo $wpdb->get_var("SELECT COUNT(*) FROM $facts_table WHERE status = 'pending'"); ?></p>
                </div>
                <div style="background: #fff; padding: 15px; border-radius: 8px; border-left: 4px solid #00a32a;">
                    <h3>🔗 Link Stats</h3>
                    <p><strong>Total Links:</strong> <?php echo $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}tulip_links"); ?></p>
                    <p><strong>Auto Links:</strong> <?php echo $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}tulip_links WHERE link_type = 'automatic'"); ?></p>
                    <p><strong>Citations:</strong> <?php echo $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}tulip_links WHERE link_type = 'citation'"); ?></p>
                </div>
            </div>
            
            <div style="background: #fff; padding: 20px; border-radius: 8px; margin: 20px 0;">
                <h2>➕ Add New TULIP Fact</h2>
                <form id="tulip-add-fact-form">
                    <table class="form-table">
                        <tr>
                            <th><label for="fact_title">Title</label></th>
                            <td><input type="text" id="fact_title" name="title" style="width: 100%;" required /></td>
                        </tr>
                        <tr>
                            <th><label for="fact_statement">Statement</label></th>
                            <td><textarea id="fact_statement" name="statement" rows="3" style="width: 100%;" required></textarea></td>
                        </tr>
                        <tr>
                            <th><label for="fact_terpene">Terpene</label></th>
                            <td>
                                <select id="fact_terpene" name="terpene_name">
                                    <option value="">Select Terpene</option>
                                    <option value="myrcene">Myrcene</option>
                                    <option value="limonene">Limonene</option>
                                    <option value="pinene">Pinene</option>
                                    <option value="linalool">Linalool</option>
                                    <option value="caryophyllene">β-Caryophyllene</option>
                                    <option value="humulene">Humulene</option>
                                    <option value="terpinolene">Terpinolene</option>
                                    <option value="ocimene">Ocimene</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="fact_category">Category</label></th>
                            <td>
                                <select id="fact_category" name="category">
                                    <option value="pharmacology">Pharmacology</option>
                                    <option value="neuropharmacology">Neuropharmacology</option>
                                    <option value="cognitive_function">Cognitive Function</option>
                                    <option value="cannabinoid_system">Cannabinoid System</option>
                                    <option value="anxiety_treatment">Anxiety Treatment</option>
                                    <option value="pain_management">Pain Management</option>
                                    <option value="inflammation">Inflammation</option>
                                    <option value="antimicrobial">Antimicrobial</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="fact_evidence">Evidence Level</label></th>
                            <td>
                                <select id="fact_evidence" name="evidence_level">
                                    <option value="anecdotal">Anecdotal</option>
                                    <option value="preliminary">Preliminary</option>
                                    <option value="peer_reviewed">Peer Reviewed</option>
                                    <option value="verified">Verified</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="fact_citations">Citations</label></th>
                            <td><textarea id="fact_citations" name="citations" rows="2" style="width: 100%;" placeholder="Enter citations, one per line"></textarea></td>
                        </tr>
                    </table>
                    <p class="submit">
                        <button type="submit" class="button-primary">Add TULIP Fact</button>
                    </p>
                </form>
            </div>
            
            <div style="background: #fff; padding: 20px; border-radius: 8px;">
                <h2>📚 TULIP Facts Database</h2>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Fact ID</th>
                            <th>Title</th>
                            <th>Terpene</th>
                            <th>Evidence Level</th>
                            <th>Status</th>
                            <th>Links</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($facts as $fact): ?>
                        <tr>
                            <td><strong><?php echo esc_html($fact->fact_id); ?></strong></td>
                            <td><?php echo esc_html($fact->title); ?></td>
                            <td><?php echo esc_html(ucfirst($fact->terpene_name)); ?></td>
                            <td>
                                <?php
                                $level_colors = array(
                                    'verified' => '#00a32a',
                                    'peer_reviewed' => '#0073aa',
                                    'preliminary' => '#f0b849',
                                    'anecdotal' => '#999'
                                );
                                $color = $level_colors[$fact->evidence_level] ?? '#999';
                                ?>
                                <span style="color: <?php echo $color; ?>; font-weight: bold;">
                                    <?php echo esc_html(ucfirst(str_replace('_', ' ', $fact->evidence_level))); ?>
                                </span>
                            </td>
                            <td>
                                <?php
                                $status_colors = array(
                                    'verified' => '#00a32a',
                                    'pending' => '#f0b849',
                                    'draft' => '#999',
                                    'disputed' => '#d63638'
                                );
                                $color = $status_colors[$fact->status] ?? '#999';
                                ?>
                                <span style="color: <?php echo $color; ?>;">
                                    <?php echo esc_html(ucfirst($fact->status)); ?>
                                </span>
                            </td>
                            <td><?php echo intval($fact->link_count); ?></td>
                            <td>
                                <button class="button button-small" onclick="viewTulipFact('<?php echo esc_js($fact->fact_id); ?>')">View</button>
                                <?php if ($fact->status !== 'verified'): ?>
                                <button class="button button-small" onclick="verifyTulipFact('<?php echo esc_js($fact->fact_id); ?>')">Verify</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <script>
        function viewTulipFact(factId) {
            // Open fact details modal
            alert('Viewing TULIP fact: ' + factId);
        }
        
        function verifyTulipFact(factId) {
            if (confirm('Verify this TULIP fact as scientifically accurate?')) {
                jQuery.post(ajaxurl, {
                    action: 'tulip_verify_fact',
                    fact_id: factId,
                    nonce: '<?php echo wp_create_nonce('tulip_nonce'); ?>'
                }, function(response) {
                    if (response.success) {
                        alert('Fact verified successfully!');
                        location.reload();
                    } else {
                        alert('Error: ' + response.data);
                    }
                });
            }
        }
        
        jQuery(document).ready(function($) {
            $('#tulip-add-fact-form').on('submit', function(e) {
                e.preventDefault();
                
                $.post(ajaxurl, {
                    action: 'tulip_add_fact',
                    title: $('#fact_title').val(),
                    statement: $('#fact_statement').val(),
                    terpene_name: $('#fact_terpene').val(),
                    category: $('#fact_category').val(),
                    evidence_level: $('#fact_evidence').val(),
                    citations: $('#fact_citations').val(),
                    nonce: '<?php echo wp_create_nonce('tulip_nonce'); ?>'
                }, function(response) {
                    if (response.success) {
                        alert('TULIP fact added successfully!');
                        location.reload();
                    } else {
                        alert('Error: ' + response.data);
                    }
                });
            });
        });
        </script>
        <?php
    }
    
    /**
     * Process content to add TULIP links
     */
    public function process_tulip_links($content) {
        global $wpdb;
        
        // Get all verified TULIP facts
        $facts = $wpdb->get_results("SELECT fact_id, title, terpene_name FROM {$wpdb->prefix}tulip_facts WHERE status = 'verified'");
        
        foreach ($facts as $fact) {
            $terpene = $fact->terpene_name;
            if (empty($terpene)) continue;
            
            // Create pattern to match terpene mentions
            $pattern = '/\b' . preg_quote($terpene, '/') . '\b/i';
            
            // Replace with TULIP link
            $replacement = '<span class="tulip-link" data-fact-id="' . esc_attr($fact->fact_id) . '" title="' . esc_attr($fact->title) . '">' . $terpene . '</span>';
            
            $content = preg_replace($pattern, $replacement, $content, 1); // Only replace first occurrence
        }
        
        return $content;
    }
    
    /**
     * TULIP fact shortcode
     */
    public function tulip_fact_shortcode($atts) {
        $atts = shortcode_atts(array(
            'id' => '',
            'display' => 'inline' // inline, card, citation
        ), $atts);
        
        if (empty($atts['id'])) {
            return '';
        }
        
        global $wpdb;
        $fact = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}tulip_facts WHERE fact_id = %s AND status = 'verified'",
            $atts['id']
        ));
        
        if (!$fact) {
            return '';
        }
        
        switch ($atts['display']) {
            case 'card':
                return $this->render_fact_card($fact);
            case 'citation':
                return $this->render_fact_citation($fact);
            default:
                return '<span class="tulip-fact-inline" data-fact-id="' . esc_attr($fact->fact_id) . '">' . esc_html($fact->title) . '</span>';
        }
    }
    
    /**
     * TULIP search shortcode
     */
    public function tulip_search_shortcode($atts) {
        $atts = shortcode_atts(array(
            'category' => '',
            'terpene' => '',
            'limit' => 10
        ), $atts);
        
        ob_start();
        ?>
        <div class="tulip-search-widget">
            <h3>🔍 Search TULIP Facts</h3>
            <input type="text" id="tulip-search-input" placeholder="Search facts..." />
            <div id="tulip-search-results"></div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('#tulip-search-input').on('input', function() {
                var query = $(this).val();
                if (query.length < 3) return;
                
                $.post(tulip_ajax.ajax_url, {
                    action: 'tulip_search_facts',
                    query: query,
                    nonce: tulip_ajax.nonce
                }, function(response) {
                    if (response.success) {
                        $('#tulip-search-results').html(response.data.html);
                    }
                });
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render fact as card
     */
    private function render_fact_card($fact) {
        $citations = json_decode($fact->citations, true) ?? array();
        
        ob_start();
        ?>
        <div class="tulip-fact-card" data-fact-id="<?php echo esc_attr($fact->fact_id); ?>">
            <div class="tulip-fact-header">
                <span class="tulip-fact-id"><?php echo esc_html($fact->fact_id); ?></span>
                <span class="tulip-evidence-level <?php echo esc_attr($fact->evidence_level); ?>">
                    <?php echo esc_html(ucfirst(str_replace('_', ' ', $fact->evidence_level))); ?>
                </span>
            </div>
            <h4><?php echo esc_html($fact->title); ?></h4>
            <p><?php echo esc_html($fact->statement); ?></p>
            <?php if (!empty($citations)): ?>
            <div class="tulip-citations">
                <strong>Citations:</strong>
                <ul>
                    <?php foreach ($citations as $citation): ?>
                    <li><?php echo esc_html($citation); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render fact as citation
     */
    private function render_fact_citation($fact) {
        return '<cite class="tulip-citation" data-fact-id="' . esc_attr($fact->fact_id) . '">' . esc_html($fact->title) . ' (' . esc_html($fact->fact_id) . ')</cite>';
    }
    
    /**
     * AJAX: Add new fact
     */
    public function ajax_add_fact() {
        if (!wp_verify_nonce($_POST['nonce'], 'tulip_nonce')) {
            wp_send_json_error('Invalid nonce');
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        global $wpdb;
        
        $title = sanitize_text_field($_POST['title']);
        $statement = sanitize_textarea_field($_POST['statement']);
        $terpene_name = sanitize_text_field($_POST['terpene_name']);
        $category = sanitize_text_field($_POST['category']);
        $evidence_level = sanitize_text_field($_POST['evidence_level']);
        $citations = sanitize_textarea_field($_POST['citations']);
        
        // Generate unique fact ID
        $fact_id = 'TULIP-' . str_pad($wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}tulip_facts") + 1, 3, '0', STR_PAD_LEFT);
        
        // Process citations
        $citations_array = array_filter(array_map('trim', explode("\n", $citations)));
        
        $result = $wpdb->insert(
            $wpdb->prefix . 'tulip_facts',
            array(
                'fact_id' => $fact_id,
                'title' => $title,
                'statement' => $statement,
                'terpene_name' => $terpene_name,
                'category' => $category,
                'evidence_level' => $evidence_level,
                'citations' => json_encode($citations_array),
                'created_by' => get_current_user_id(),
                'status' => 'draft'
            )
        );
        
        if ($result) {
            wp_send_json_success('TULIP fact added successfully');
        } else {
            wp_send_json_error('Failed to add fact');
        }
    }
    
    /**
     * AJAX: Verify fact
     */
    public function ajax_verify_fact() {
        if (!wp_verify_nonce($_POST['nonce'], 'tulip_nonce')) {
            wp_send_json_error('Invalid nonce');
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        global $wpdb;
        
        $fact_id = sanitize_text_field($_POST['fact_id']);
        
        $result = $wpdb->update(
            $wpdb->prefix . 'tulip_facts',
            array(
                'status' => 'verified',
                'verified_by' => get_current_user_id(),
                'verified_at' => current_time('mysql')
            ),
            array('fact_id' => $fact_id)
        );
        
        if ($result) {
            // Log verification
            $wpdb->insert(
                $wpdb->prefix . 'tulip_verification_log',
                array(
                    'fact_id' => $fact_id,
                    'verifier_id' => get_current_user_id(),
                    'action' => 'verify',
                    'previous_status' => 'pending',
                    'new_status' => 'verified'
                )
            );
            
            wp_send_json_success('Fact verified successfully');
        } else {
            wp_send_json_error('Failed to verify fact');
        }
    }
    
    /**
     * AJAX: Search facts
     */
    public function ajax_search_facts() {
        if (!wp_verify_nonce($_POST['nonce'], 'tulip_nonce')) {
            wp_send_json_error('Invalid nonce');
        }
        
        global $wpdb;
        
        $query = sanitize_text_field($_POST['query']);
        $search_term = '%' . $wpdb->esc_like($query) . '%';
        
        $facts = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}tulip_facts 
             WHERE (title LIKE %s OR statement LIKE %s OR terpene_name LIKE %s) 
             AND status = 'verified' 
             ORDER BY title ASC LIMIT 10",
            $search_term, $search_term, $search_term
        ));
        
        $html = '';
        foreach ($facts as $fact) {
            $html .= '<div class="tulip-search-result">';
            $html .= '<strong>' . esc_html($fact->title) . '</strong> (' . esc_html($fact->fact_id) . ')';
            $html .= '<p>' . esc_html(substr($fact->statement, 0, 150)) . '...</p>';
            $html .= '</div>';
        }
        
        wp_send_json_success(array('html' => $html, 'count' => count($facts)));
    }
}

// Initialize TULIP system
new TerpediaTULIPSystem();
