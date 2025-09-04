<?php
/**
 * Newsletter Automation System for Terpedia
 * Handles automated newsletter generation and scheduling
 */

if (!defined('ABSPATH')) {
    exit;
}

class Terpedia_Newsletter_Automation {
    
    private $template_manager;
    
    public function __construct() {
        $this->template_manager = new Terpedia_Newsletter_Template_Manager();
        
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_ajax_generate_newsletter_now', array($this, 'generate_newsletter_now'));
        add_action('wp_ajax_schedule_newsletter', array($this, 'schedule_newsletter'));
        add_action('terpedia_weekly_newsletter', array($this, 'process_weekly_newsletter'));
        add_action('terpedia_daily_newsletter', array($this, 'process_daily_newsletter'));
        add_action('terpedia_monthly_newsletter', array($this, 'process_monthly_newsletter'));
        
        // Schedule events if not already scheduled
        $this->schedule_newsletter_events();
    }
    
    public function init() {
        // Register newsletter post type
        $this->register_newsletter_post_type();
    }
    
    /**
     * Register newsletter post type
     */
    private function register_newsletter_post_type() {
        register_post_type('terpedia_newsletter', array(
            'labels' => array(
                'name' => 'Newsletters',
                'singular_name' => 'Newsletter',
                'add_new' => 'Add New Newsletter',
                'add_new_item' => 'Add New Newsletter',
                'edit_item' => 'Edit Newsletter',
                'new_item' => 'New Newsletter',
                'view_item' => 'View Newsletter',
                'search_items' => 'Search Newsletters',
                'not_found' => 'No newsletters found',
                'not_found_in_trash' => 'No newsletters found in trash'
            ),
            'public' => true,
            'has_archive' => true,
            'rewrite' => array('slug' => 'newsletters'),
            'supports' => array('title', 'editor', 'excerpt', 'thumbnail', 'custom-fields'),
            'menu_icon' => 'dashicons-email-alt',
            'show_in_rest' => true
        ));
    }
    
    /**
     * Add admin menu for newsletter automation
     */
    public function add_admin_menu() {
        add_submenu_page(
            'terpedia-admin',
            'Newsletter Automation',
            'Newsletter Automation',
            'manage_options',
            'terpedia-newsletter-automation',
            array($this, 'admin_page')
        );
    }
    
    /**
     * Admin page for newsletter automation
     */
    public function admin_page() {
        $templates = $this->template_manager->get_templates();
        $recent_newsletters = $this->get_recent_newsletters();
        $scheduled_events = $this->get_scheduled_events();
        
        ?>
        <div class="wrap">
            <h1>Newsletter Automation</h1>
            
            <div class="terpedia-newsletter-automation">
                <div class="automation-dashboard">
                    <div class="dashboard-cards">
                        <div class="card">
                            <h3>Quick Actions</h3>
                            <div class="quick-actions">
                                <button id="generate-newsletter-now" class="button button-primary">Generate Newsletter Now</button>
                                <button id="test-template" class="button button-secondary">Test Template</button>
                                <button id="view-schedule" class="button button-secondary">View Schedule</button>
                            </div>
                        </div>
                        
                        <div class="card">
                            <h3>Active Templates</h3>
                            <div class="templates-summary">
                                <?php foreach ($templates as $template): ?>
                                    <div class="template-summary">
                                        <strong><?php echo esc_html($template->name); ?></strong>
                                        <span class="frequency"><?php echo esc_html(ucfirst($template->frequency)); ?></span>
                                        <span class="sections-count"><?php echo count($template->sections); ?> sections</span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <div class="card">
                            <h3>Recent Newsletters</h3>
                            <div class="recent-newsletters">
                                <?php foreach ($recent_newsletters as $newsletter): ?>
                                    <div class="newsletter-item">
                                        <a href="<?php echo get_edit_post_link($newsletter->ID); ?>">
                                            <?php echo esc_html($newsletter->post_title); ?>
                                        </a>
                                        <span class="date"><?php echo get_the_date('M j, Y', $newsletter->ID); ?></span>
                                        <span class="status status-<?php echo $newsletter->post_status; ?>">
                                            <?php echo esc_html(ucfirst($newsletter->post_status)); ?>
                                        </span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <div class="card">
                            <h3>Scheduled Events</h3>
                            <div class="scheduled-events">
                                <?php foreach ($scheduled_events as $event): ?>
                                    <div class="event-item">
                                        <strong><?php echo esc_html($event['hook']); ?></strong>
                                        <span class="next-run">Next: <?php echo esc_html($event['next_run']); ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="automation-settings">
                    <h2>Automation Settings</h2>
                    
                    <form id="automation-settings-form">
                        <table class="form-table">
                            <tr>
                                <th scope="row">Default Template</th>
                                <td>
                                    <select name="default_template" id="default-template">
                                        <option value="">Select Template</option>
                                        <?php foreach ($templates as $template): ?>
                                            <option value="<?php echo $template->id; ?>">
                                                <?php echo esc_html($template->name); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">Auto-publish</th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="auto_publish" value="1">
                                        Automatically publish generated newsletters
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">Email Notifications</th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="email_notifications" value="1">
                                        Send email notifications when newsletters are generated
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">Notification Email</th>
                                <td>
                                    <input type="email" name="notification_email" value="<?php echo get_option('admin_email'); ?>">
                                </td>
                            </tr>
                        </table>
                        
                        <p class="submit">
                            <button type="submit" class="button button-primary">Save Settings</button>
                        </p>
                    </form>
                </div>
                
                <div class="newsletter-generation">
                    <h2>Generate Newsletter</h2>
                    
                    <form id="newsletter-generation-form">
                        <table class="form-table">
                            <tr>
                                <th scope="row">Template</th>
                                <td>
                                    <select name="template_id" id="generation-template" required>
                                        <option value="">Select Template</option>
                                        <?php foreach ($templates as $template): ?>
                                            <option value="<?php echo $template->id; ?>">
                                                <?php echo esc_html($template->name); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">Date Range</th>
                                <td>
                                    <select name="date_range" id="date-range">
                                        <option value="last_week">Last Week</option>
                                        <option value="last_month">Last Month</option>
                                        <option value="custom">Custom Range</option>
                                    </select>
                                </td>
                            </tr>
                            <tr id="custom-date-range" style="display: none;">
                                <th scope="row">Custom Date Range</th>
                                <td>
                                    <input type="date" name="start_date" id="start-date">
                                    <input type="date" name="end_date" id="end-date">
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">Include Sections</th>
                                <td id="sections-selection">
                                    <!-- Sections will be populated here -->
                                </td>
                            </tr>
                        </table>
                        
                        <p class="submit">
                            <button type="submit" class="button button-primary">Generate Newsletter</button>
                        </p>
                    </form>
                </div>
            </div>
        </div>
        
        <style>
        .terpedia-newsletter-automation {
            max-width: 1200px;
        }
        
        .dashboard-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .card {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .card h3 {
            margin-top: 0;
            color: #23282d;
        }
        
        .quick-actions {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        .template-summary {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }
        
        .template-summary:last-child {
            border-bottom: none;
        }
        
        .frequency, .sections-count {
            font-size: 12px;
            color: #666;
            background: #f0f0f0;
            padding: 2px 6px;
            border-radius: 3px;
        }
        
        .newsletter-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }
        
        .newsletter-item:last-child {
            border-bottom: none;
        }
        
        .newsletter-item a {
            text-decoration: none;
            font-weight: 500;
        }
        
        .date {
            font-size: 12px;
            color: #666;
        }
        
        .status {
            font-size: 11px;
            padding: 2px 6px;
            border-radius: 3px;
            text-transform: uppercase;
        }
        
        .status-draft {
            background: #f0f0f0;
            color: #666;
        }
        
        .status-publish {
            background: #d4edda;
            color: #155724;
        }
        
        .event-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }
        
        .event-item:last-child {
            border-bottom: none;
        }
        
        .next-run {
            font-size: 12px;
            color: #666;
        }
        
        .automation-settings,
        .newsletter-generation {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .form-table th {
            width: 200px;
        }
        
        .sections-selection {
            max-height: 200px;
            overflow-y: auto;
            border: 1px solid #ddd;
            padding: 10px;
            background: #f9f9f9;
        }
        
        .section-checkbox {
            display: block;
            margin: 5px 0;
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            // Template selection change
            $('#generation-template').change(function() {
                var templateId = $(this).val();
                if (templateId) {
                    loadTemplateSections(templateId);
                } else {
                    $('#sections-selection').empty();
                }
            });
            
            // Date range change
            $('#date-range').change(function() {
                if ($(this).val() === 'custom') {
                    $('#custom-date-range').show();
                } else {
                    $('#custom-date-range').hide();
                }
            });
            
            // Generate newsletter now
            $('#generate-newsletter-now').click(function() {
                if (confirm('Generate newsletter using the default template?')) {
                    generateNewsletterNow();
                }
            });
            
            // Newsletter generation form
            $('#newsletter-generation-form').submit(function(e) {
                e.preventDefault();
                generateNewsletter();
            });
            
            // Automation settings form
            $('#automation-settings-form').submit(function(e) {
                e.preventDefault();
                saveAutomationSettings();
            });
            
            function loadTemplateSections(templateId) {
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'get_template_sections',
                        template_id: templateId
                    },
                    success: function(response) {
                        if (response.success) {
                            var sectionsHtml = '';
                            response.data.forEach(function(section) {
                                sectionsHtml += '<label class="section-checkbox">';
                                sectionsHtml += '<input type="checkbox" name="sections[]" value="' + section.id + '" checked>';
                                sectionsHtml += ' ' + section.section_title;
                                if (section.is_required) {
                                    sectionsHtml += ' <em>(Required)</em>';
                                }
                                sectionsHtml += '</label>';
                            });
                            $('#sections-selection').html(sectionsHtml);
                        }
                    }
                });
            }
            
            function generateNewsletterNow() {
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'generate_newsletter_now'
                    },
                    beforeSend: function() {
                        $('#generate-newsletter-now').prop('disabled', true).text('Generating...');
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('Newsletter generated successfully!');
                            if (response.data.edit_url) {
                                window.open(response.data.edit_url, '_blank');
                            }
                        } else {
                            alert('Error generating newsletter: ' + response.data);
                        }
                    },
                    complete: function() {
                        $('#generate-newsletter-now').prop('disabled', false).text('Generate Newsletter Now');
                    }
                });
            }
            
            function generateNewsletter() {
                var formData = $('#newsletter-generation-form').serialize();
                formData += '&action=generate_newsletter_from_template';
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: formData,
                    beforeSend: function() {
                        $('button[type="submit"]').prop('disabled', true).text('Generating...');
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('Newsletter generated successfully!');
                            if (response.data.edit_url) {
                                window.open(response.data.edit_url, '_blank');
                            }
                        } else {
                            alert('Error generating newsletter: ' + response.data);
                        }
                    },
                    complete: function() {
                        $('button[type="submit"]').prop('disabled', false).text('Generate Newsletter');
                    }
                });
            }
            
            function saveAutomationSettings() {
                var formData = $('#automation-settings-form').serialize();
                formData += '&action=save_automation_settings';
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: formData,
                    success: function(response) {
                        if (response.success) {
                            alert('Settings saved successfully!');
                        } else {
                            alert('Error saving settings: ' + response.data);
                        }
                    }
                });
            }
        });
        </script>
        <?php
    }
    
    /**
     * Get recent newsletters
     */
    private function get_recent_newsletters() {
        return get_posts(array(
            'post_type' => 'terpedia_newsletter',
            'numberposts' => 5,
            'post_status' => array('publish', 'draft', 'pending')
        ));
    }
    
    /**
     * Get scheduled events
     */
    private function get_scheduled_events() {
        $events = array();
        
        $hooks = array(
            'terpedia_weekly_newsletter' => 'Weekly Newsletter',
            'terpedia_daily_newsletter' => 'Daily Newsletter',
            'terpedia_monthly_newsletter' => 'Monthly Newsletter'
        );
        
        foreach ($hooks as $hook => $name) {
            $next_run = wp_next_scheduled($hook);
            if ($next_run) {
                $events[] = array(
                    'hook' => $name,
                    'next_run' => date('M j, Y H:i', $next_run)
                );
            }
        }
        
        return $events;
    }
    
    /**
     * Schedule newsletter events
     */
    private function schedule_newsletter_events() {
        // Weekly newsletter (Mondays at 9 AM)
        if (!wp_next_scheduled('terpedia_weekly_newsletter')) {
            wp_schedule_event(strtotime('next Monday 9:00'), 'weekly', 'terpedia_weekly_newsletter');
        }
        
        // Daily newsletter (weekdays at 8 AM)
        if (!wp_next_scheduled('terpedia_daily_newsletter')) {
            wp_schedule_event(strtotime('tomorrow 8:00'), 'daily', 'terpedia_daily_newsletter');
        }
        
        // Monthly newsletter (1st of month at 10 AM)
        if (!wp_next_scheduled('terpedia_monthly_newsletter')) {
            wp_schedule_event(strtotime('first day of next month 10:00'), 'monthly', 'terpedia_monthly_newsletter');
        }
    }
    
    /**
     * Generate newsletter now via AJAX
     */
    public function generate_newsletter_now() {
        check_ajax_referer('terpedia_newsletter_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        // Get default template or first active template
        $templates = $this->template_manager->get_templates();
        if (empty($templates)) {
            wp_send_json_error('No active templates found');
        }
        
        $template = $templates[0]; // Use first template as default
        $result = $this->template_manager->generate_newsletter_from_template($template->id);
        
        if ($result) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error('Failed to generate newsletter');
        }
    }
    
    /**
     * Process weekly newsletter
     */
    public function process_weekly_newsletter() {
        $this->process_scheduled_newsletter('weekly');
    }
    
    /**
     * Process daily newsletter
     */
    public function process_daily_newsletter() {
        $this->process_scheduled_newsletter('daily');
    }
    
    /**
     * Process monthly newsletter
     */
    public function process_monthly_newsletter() {
        $this->process_scheduled_newsletter('monthly');
    }
    
    /**
     * Process scheduled newsletter for specific frequency
     */
    private function process_scheduled_newsletter($frequency) {
        global $wpdb;
        
        $templates = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}terpedia_newsletter_templates WHERE is_active = 1 AND frequency = %s",
            $frequency
        ));
        
        foreach ($templates as $template) {
            $this->template_manager->generate_newsletter_from_template($template->id);
        }
        
        // Send notification email if enabled
        if (get_option('terpedia_newsletter_email_notifications', false)) {
            $this->send_generation_notification($frequency, count($templates));
        }
    }
    
    /**
     * Send notification email when newsletters are generated
     */
    private function send_generation_notification($frequency, $count) {
        $email = get_option('terpedia_newsletter_notification_email', get_option('admin_email'));
        $subject = "Terpedia Newsletter Generated - {$frequency}";
        $message = "{$count} newsletter(s) have been automatically generated for the {$frequency} frequency.";
        
        wp_mail($email, $subject, $message);
    }
}

// Initialize the newsletter automation system
new Terpedia_Newsletter_Automation();
