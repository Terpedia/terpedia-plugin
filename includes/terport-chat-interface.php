<?php
/**
 * Terport Chat Interface
 * 
 * Provides interactive chat functionality for discussing and tuning Terports
 * with AI assistance in a two-column layout
 * 
 * @package Terpedia
 */

if (!defined('ABSPATH')) {
    exit;
}

class Terpedia_Terport_Chat_Interface {
    
    private $openrouter_api;
    
    public function __construct() {
        add_action('wp_ajax_terpedia_terport_chat', array($this, 'handle_terport_chat'));
        add_action('wp_ajax_nopriv_terpedia_terport_chat', array($this, 'handle_terport_chat'));
        add_action('wp_ajax_terpedia_update_terport_field', array($this, 'handle_field_update'));
        add_action('wp_ajax_nopriv_terpedia_update_terport_field', array($this, 'handle_field_update'));
        
        // Template redirect for chat interface
        add_filter('template_include', array($this, 'terport_chat_template'));
        
        // Initialize OpenRouter API
        $this->openrouter_api = new Terpedia_OpenRouter_API();
    }
    
    /**
     * Handle chat template routing
     */
    public function terport_chat_template($template) {
        global $wp_query;
        
        if (get_query_var('terport_chat')) {
            $terport_id = get_query_var('terport_id');
            if ($terport_id && get_post_type($terport_id) === 'terport') {
                return $this->render_chat_interface($terport_id);
            }
        }
        
        return $template;
    }
    
    /**
     * Render the two-column chat interface
     */
    public function render_chat_interface($terport_id) {
        $terport = get_post($terport_id);
        if (!$terport) {
            wp_die('Terport not found');
        }
        
        // Check if Terport is public or user has permission
        $is_public = get_post_meta($terport_id, '_terport_visibility', true) === 'public';
        if (!$is_public && !current_user_can('edit_post', $terport_id)) {
            wp_die('Access denied');
        }
        
        // Get structured field data
        $template_type = get_post_meta($terport_id, '_terpedia_field_template_type', true);
        $structured_fields = $this->get_terport_structured_data($terport_id, $template_type);
        
        ?>
        <!DOCTYPE html>
        <html <?php language_attributes(); ?>>
        <head>
            <meta charset="<?php bloginfo('charset'); ?>">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title>Chat: <?php echo esc_html($terport->post_title); ?> | Terpedia</title>
            <?php wp_head(); ?>
            <style>
                body { margin: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; }
                .terport-chat-container {
                    display: grid;
                    grid-template-columns: 400px 1fr;
                    height: 100vh;
                    background: #f8f9fa;
                }
                
                /* Chat Column */
                .chat-column {
                    background: white;
                    border-right: 1px solid #dee2e6;
                    display: flex;
                    flex-direction: column;
                }
                
                .chat-header {
                    padding: 20px;
                    border-bottom: 1px solid #dee2e6;
                    background: linear-gradient(135deg, #ff69b4, #ff1493);
                    color: white;
                }
                
                .chat-header h3 {
                    margin: 0 0 5px 0;
                    font-size: 18px;
                    font-weight: 600;
                }
                
                .chat-header p {
                    margin: 0;
                    opacity: 0.9;
                    font-size: 14px;
                }
                
                .chat-messages {
                    flex: 1;
                    padding: 20px;
                    overflow-y: auto;
                    max-height: calc(100vh - 200px);
                }
                
                .message {
                    margin-bottom: 16px;
                    animation: fadeInUp 0.3s ease;
                }
                
                .message.user {
                    text-align: right;
                }
                
                .message.ai {
                    text-align: left;
                }
                
                .message-bubble {
                    display: inline-block;
                    max-width: 80%;
                    padding: 12px 16px;
                    border-radius: 18px;
                    font-size: 14px;
                    line-height: 1.4;
                }
                
                .message.user .message-bubble {
                    background: #ff69b4;
                    color: white;
                    border-bottom-right-radius: 4px;
                }
                
                .message.ai .message-bubble {
                    background: #e9ecef;
                    color: #2c5aa0;
                    border-bottom-left-radius: 4px;
                }
                
                .chat-input {
                    padding: 20px;
                    border-top: 1px solid #dee2e6;
                    background: white;
                }
                
                .chat-input-group {
                    display: flex;
                    gap: 10px;
                    align-items: flex-end;
                }
                
                .chat-input-field {
                    flex: 1;
                    padding: 12px 16px;
                    border: 1px solid #ced4da;
                    border-radius: 20px;
                    outline: none;
                    resize: none;
                    max-height: 100px;
                    font-family: inherit;
                    font-size: 14px;
                }
                
                .chat-send-btn {
                    width: 40px;
                    height: 40px;
                    border: none;
                    border-radius: 50%;
                    background: #ff69b4;
                    color: white;
                    cursor: pointer;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    transition: background-color 0.2s;
                }
                
                .chat-send-btn:hover {
                    background: #ff1493;
                }
                
                .chat-send-btn:disabled {
                    background: #6c757d;
                    cursor: not-allowed;
                }
                
                /* Terport Content Column */
                .terport-column {
                    padding: 20px;
                    overflow-y: auto;
                    background: white;
                    margin: 20px;
                    border-radius: 8px;
                    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                }
                
                .terport-header {
                    margin-bottom: 30px;
                    padding-bottom: 20px;
                    border-bottom: 2px solid #ff69b4;
                }
                
                .terport-title {
                    margin: 0 0 10px 0;
                    color: #2c5aa0;
                    font-size: 28px;
                    font-weight: 700;
                }
                
                .terport-meta {
                    display: flex;
                    gap: 20px;
                    color: #6c757d;
                    font-size: 14px;
                    align-items: center;
                }
                
                .terport-field {
                    margin-bottom: 30px;
                    background: #f8f9fa;
                    border-radius: 8px;
                    padding: 20px;
                    position: relative;
                    transition: background-color 0.2s;
                }
                
                .terport-field.editable:hover {
                    background: #fff5f8;
                    cursor: pointer;
                }
                
                .terport-field-label {
                    font-weight: 600;
                    color: #ff1493;
                    margin-bottom: 10px;
                    font-size: 16px;
                    display: flex;
                    align-items: center;
                    gap: 8px;
                }
                
                .edit-indicator {
                    opacity: 0;
                    color: #6c757d;
                    font-size: 12px;
                    transition: opacity 0.2s;
                }
                
                .terport-field.editable:hover .edit-indicator {
                    opacity: 1;
                }
                
                .terport-field-content {
                    color: #495057;
                    line-height: 1.6;
                }
                
                .terport-field-content h1, .terport-field-content h2, .terport-field-content h3 {
                    color: #2c5aa0;
                    margin-top: 20px;
                    margin-bottom: 10px;
                }
                
                .terport-field-content ul, .terport-field-content ol {
                    margin: 10px 0;
                    padding-left: 30px;
                }
                
                .terport-field-content p {
                    margin: 10px 0;
                }
                
                @keyframes fadeInUp {
                    from { opacity: 0; transform: translateY(10px); }
                    to { opacity: 1; transform: translateY(0); }
                }
                
                .typing-indicator {
                    display: flex;
                    align-items: center;
                    gap: 4px;
                    padding: 12px 16px;
                    background: #e9ecef;
                    border-radius: 18px;
                    border-bottom-left-radius: 4px;
                    max-width: fit-content;
                }
                
                .typing-dot {
                    width: 4px;
                    height: 4px;
                    border-radius: 50%;
                    background: #6c757d;
                    animation: typingAnimation 1.4s infinite;
                }
                
                .typing-dot:nth-child(2) { animation-delay: 0.2s; }
                .typing-dot:nth-child(3) { animation-delay: 0.4s; }
                
                @keyframes typingAnimation {
                    0%, 60%, 100% { opacity: 0.3; }
                    30% { opacity: 1; }
                }
                
                /* Responsive */
                @media (max-width: 768px) {
                    .terport-chat-container {
                        grid-template-columns: 1fr;
                        grid-template-rows: 1fr 1fr;
                    }
                    
                    .chat-column {
                        border-right: none;
                        border-bottom: 1px solid #dee2e6;
                    }
                }
            </style>
        </head>
        <body>
            <div class="terport-chat-container">
                <!-- Chat Column -->
                <div class="chat-column">
                    <div class="chat-header">
                        <h3>💬 Terport Assistant</h3>
                        <p>Discuss and refine this research report</p>
                    </div>
                    
                    <div class="chat-messages" id="chatMessages">
                        <div class="message ai">
                            <div class="message-bubble">
                                👋 Hi! I'm here to help you discuss and improve this Terport. You can ask me questions about the content, request changes, or have me explain specific sections. What would you like to know?
                            </div>
                        </div>
                    </div>
                    
                    <div class="chat-input">
                        <div class="chat-input-group">
                            <textarea id="chatInput" class="chat-input-field" placeholder="Ask about this Terport or request changes..." rows="1"></textarea>
                            <button id="sendBtn" class="chat-send-btn">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Terport Content Column -->
                <div class="terport-column" id="terportContent">
                    <div class="terport-header">
                        <h1 class="terport-title"><?php echo esc_html($terport->post_title); ?></h1>
                        <div class="terport-meta">
                            <span>📄 <?php echo ucfirst(str_replace('_', ' ', $template_type)); ?></span>
                            <span>📅 <?php echo get_the_date('M j, Y', $terport_id); ?></span>
                            <span>👤 <?php echo get_the_author_meta('display_name', $terport->post_author); ?></span>
                        </div>
                    </div>
                    
                    <div class="terport-fields">
                        <?php foreach ($structured_fields as $field_name => $field_data): ?>
                            <div class="terport-field editable" data-field="<?php echo esc_attr($field_name); ?>">
                                <div class="terport-field-label">
                                    <?php echo esc_html($field_data['label']); ?>
                                    <span class="edit-indicator">✏️ Click to edit via chat</span>
                                </div>
                                <div class="terport-field-content">
                                    <?php echo wp_kses_post($field_data['content'] ?: '<em>No content yet - ask the assistant to generate this section</em>'); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
            <script>
            jQuery(document).ready(function($) {
                const terportId = <?php echo $terport_id; ?>;
                const chatMessages = $('#chatMessages');
                const chatInput = $('#chatInput');
                const sendBtn = $('#sendBtn');
                
                // Auto-resize textarea
                chatInput.on('input', function() {
                    this.style.height = 'auto';
                    this.style.height = (this.scrollHeight) + 'px';
                });
                
                // Send message on Enter (Shift+Enter for new line)
                chatInput.on('keydown', function(e) {
                    if (e.key === 'Enter' && !e.shiftKey) {
                        e.preventDefault();
                        sendMessage();
                    }
                });
                
                // Send button click
                sendBtn.on('click', sendMessage);
                
                // Field click to edit
                $('.terport-field.editable').on('click', function() {
                    const fieldName = $(this).data('field');
                    const fieldLabel = $(this).find('.terport-field-label').text().replace('✏️ Click to edit via chat', '').trim();
                    chatInput.val(`Please update the "${fieldLabel}" section with: `);
                    chatInput.focus();
                });
                
                function sendMessage() {
                    const message = chatInput.val().trim();
                    if (!message) return;
                    
                    // Add user message
                    addMessage(message, 'user');
                    chatInput.val('').trigger('input');
                    
                    // Show typing indicator
                    const typingId = addTypingIndicator();
                    
                    // Disable send button
                    sendBtn.prop('disabled', true);
                    
                    // Send to backend
                    $.post(ajaxurl, {
                        action: 'terpedia_terport_chat',
                        terport_id: terportId,
                        message: message,
                        nonce: '<?php echo wp_create_nonce("terport_chat_nonce"); ?>'
                    })
                    .done(function(response) {
                        removeTypingIndicator(typingId);
                        
                        if (response.success) {
                            addMessage(response.data.reply, 'ai');
                            
                            // Update field if content was modified
                            if (response.data.field_updates) {
                                updateTerportFields(response.data.field_updates);
                            }
                        } else {
                            addMessage('Sorry, I encountered an error. Please try again.', 'ai');
                        }
                    })
                    .fail(function() {
                        removeTypingIndicator(typingId);
                        addMessage('Connection error. Please check your internet connection.', 'ai');
                    })
                    .always(function() {
                        sendBtn.prop('disabled', false);
                        chatInput.focus();
                    });
                }
                
                function addMessage(content, type) {
                    const messageDiv = $('<div class="message ' + type + '"><div class="message-bubble">' + escapeHtml(content) + '</div></div>');
                    chatMessages.append(messageDiv);
                    chatMessages.scrollTop(chatMessages[0].scrollHeight);
                }
                
                function addTypingIndicator() {
                    const typingId = 'typing-' + Date.now();
                    const typingDiv = $('<div class="message ai" id="' + typingId + '"><div class="typing-indicator"><div class="typing-dot"></div><div class="typing-dot"></div><div class="typing-dot"></div></div></div>');
                    chatMessages.append(typingDiv);
                    chatMessages.scrollTop(chatMessages[0].scrollHeight);
                    return typingId;
                }
                
                function removeTypingIndicator(typingId) {
                    $('#' + typingId).remove();
                }
                
                function updateTerportFields(updates) {
                    for (const fieldName in updates) {
                        const fieldElement = $('[data-field="' + fieldName + '"] .terport-field-content');
                        if (fieldElement.length) {
                            fieldElement.html(updates[fieldName]);
                            fieldElement.parent().addClass('updated');
                            setTimeout(() => fieldElement.parent().removeClass('updated'), 2000);
                        }
                    }
                }
                
                function escapeHtml(text) {
                    const div = document.createElement('div');
                    div.textContent = text;
                    return div.innerHTML;
                }
                
                // Initialize chat input focus
                chatInput.focus();
            });
            </script>
            
            <?php wp_footer(); ?>
        </body>
        </html>
        <?php
        exit;
    }
    
    /**
     * Get structured data for a Terport
     */
    private function get_terport_structured_data($terport_id, $template_type) {
        if (!class_exists('Terpedia_Field_Based_Template_System')) {
            return array();
        }
        
        $template_system = new Terpedia_Field_Based_Template_System();
        $field_definitions = $template_system->get_template_field_definitions($template_type);
        
        $structured_data = array();
        foreach ($field_definitions as $field_name => $field_config) {
            $content = get_post_meta($terport_id, '_structured_field_' . $field_name, true);
            $structured_data[$field_name] = array(
                'label' => $field_config['label'],
                'content' => $content,
                'type' => $field_config['type'],
                'description' => $field_config['description'] ?? ''
            );
        }
        
        return $structured_data;
    }
    
    /**
     * Handle chat messages
     */
    public function handle_terport_chat() {
        check_ajax_referer('terport_chat_nonce', 'nonce');
        
        $terport_id = intval($_POST['terport_id'] ?? 0);
        $message = sanitize_textarea_field($_POST['message'] ?? '');
        
        if (!$terport_id || !$message) {
            wp_send_json_error('Missing required parameters');
        }
        
        $terport = get_post($terport_id);
        if (!$terport || $terport->post_type !== 'terport') {
            wp_send_json_error('Invalid Terport');
        }
        
        // Check permissions
        $is_public = get_post_meta($terport_id, '_terport_visibility', true) === 'public';
        if (!$is_public && !current_user_can('edit_post', $terport_id)) {
            wp_send_json_error('Access denied');
        }
        
        // Get Terport context
        $template_type = get_post_meta($terport_id, '_terpedia_field_template_type', true);
        $structured_data = $this->get_terport_structured_data($terport_id, $template_type);
        
        // Generate AI response
        $response = $this->generate_chat_response($message, $terport, $template_type, $structured_data);
        
        if (is_wp_error($response)) {
            wp_send_json_error($response->get_error_message());
        }
        
        wp_send_json_success($response);
    }
    
    /**
     * Generate AI chat response
     */
    private function generate_chat_response($user_message, $terport, $template_type, $structured_data) {
        $system_prompt = "You are an expert terpene researcher and writing assistant. You're helping a user discuss and improve their Terport (terpene research report) titled '{$terport->post_title}'.

The Terport is a {$template_type} with the following structured sections:
" . implode("\n", array_map(function($field_name, $field_data) {
    $content_preview = substr(strip_tags($field_data['content']), 0, 200);
    return "- {$field_data['label']}: " . ($content_preview ?: '[Empty]');
}, array_keys($structured_data), $structured_data)) . "

You can:
1. Answer questions about the content
2. Suggest improvements
3. Explain scientific concepts
4. Update specific sections when requested
5. Provide research guidance

Be conversational, helpful, and scientifically accurate. If asked to update content, provide the new content and indicate which field should be updated.";

        $response_format = array(
            'type' => 'json_schema',
            'json_schema' => array(
                'name' => 'terport_chat_response',
                'strict' => true,
                'schema' => array(
                    'type' => 'object',
                    'properties' => array(
                        'reply' => array(
                            'type' => 'string',
                            'description' => 'The conversational response to the user'
                        ),
                        'field_updates' => array(
                            'type' => 'object',
                            'description' => 'Any field updates to apply to the Terport',
                            'additionalProperties' => array('type' => 'string')
                        ),
                        'suggestions' => array(
                            'type' => 'array',
                            'items' => array('type' => 'string'),
                            'description' => 'Optional suggestions for further discussion'
                        )
                    ),
                    'required' => array('reply'),
                    'additionalProperties' => false
                )
            )
        );
        
        $response = $this->openrouter_api->make_api_request($system_prompt, $user_message, $response_format);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        // Save any field updates
        if (!empty($response['field_updates'])) {
            foreach ($response['field_updates'] as $field_name => $content) {
                update_post_meta($terport->ID, '_structured_field_' . $field_name, wp_kses_post($content));
            }
        }
        
        return $response;
    }
    
    /**
     * Handle field updates
     */
    public function handle_field_update() {
        check_ajax_referer('terport_field_update', 'nonce');
        
        $terport_id = intval($_POST['terport_id'] ?? 0);
        $field_name = sanitize_text_field($_POST['field_name'] ?? '');
        $content = wp_kses_post($_POST['content'] ?? '');
        
        if (!$terport_id || !$field_name) {
            wp_send_json_error('Missing required parameters');
        }
        
        if (!current_user_can('edit_post', $terport_id)) {
            wp_send_json_error('Access denied');
        }
        
        update_post_meta($terport_id, '_structured_field_' . $field_name, $content);
        
        wp_send_json_success(array(
            'message' => 'Field updated successfully',
            'field_name' => $field_name,
            'content' => $content
        ));
    }
}

// Initialize the chat interface
new Terpedia_Terport_Chat_Interface();