/**
 * Terpedia Agent Dashboard JavaScript
 */

jQuery(document).ready(function($) {
    
    // Modal functionality
    $('.terpedia-modal-close, .terpedia-modal').on('click', function(e) {
        if (e.target === this) {
            $('.terpedia-modal').hide();
        }
    });
    
    // Add new agent buttons
    $('#add-new-tersona').on('click', function() {
        $('#modal-title').text('Add New Tersona');
        $('#agent-form input[name="agent_type"]').val('terpene');
        $('#tersona-modal').show();
    });
    
    $('#add-new-expert').on('click', function() {
        $('#modal-title').text('Add New Expert');
        $('#agent-form input[name="agent_type"]').val('expert');
        $('#expert-modal').show();
    });
    
    // Edit agent button
    $('.edit-agent').on('click', function() {
        var agentId = $(this).data('agent-id');
        loadAgentData(agentId);
    });
    
    // View profile button
    $('.view-profile').on('click', function() {
        var agentId = $(this).data('agent-id');
        var profileUrl = terpedia_ajax.ajax_url + '?action=view_agent_profile&agent_id=' + agentId;
        window.open(profileUrl, '_blank');
    });
    
    // Toggle agent status
    $('.toggle-status').on('click', function() {
        var agentId = $(this).data('agent-id');
        var currentStatus = $(this).data('status');
        var newStatus = currentStatus === 'active' ? 'inactive' : 'active';
        
        updateAgentStatus(agentId, newStatus);
    });
    
    // Agent form submission
    $('#agent-form').on('submit', function(e) {
        e.preventDefault();
        
        var formData = $(this).serialize();
        var isEdit = $('#agent-form input[name="agent_id"]').length > 0;
        
        if (isEdit) {
            updateAgent(formData);
        } else {
            createAgent(formData);
        }
    });
    
    // Bulk edit functionality
    $('#bulk-edit-tersonae, #bulk-edit-experts').on('click', function() {
        var selectedAgents = $('.terpedia-agent-card input[type="checkbox"]:checked').map(function() {
            return $(this).val();
        }).get();
        
        if (selectedAgents.length === 0) {
            alert('Please select agents to edit.');
            return;
        }
        
        showBulkEditModal(selectedAgents);
    });
    
    // Select all checkbox
    $('#select-all-agents').on('change', function() {
        $('.terpedia-agent-card input[type="checkbox"]').prop('checked', this.checked);
    });
    
    // Individual agent selection
    $('.terpedia-agent-card input[type="checkbox"]').on('change', function() {
        var totalCheckboxes = $('.terpedia-agent-card input[type="checkbox"]').length;
        var checkedCheckboxes = $('.terpedia-agent-card input[type="checkbox"]:checked').length;
        
        $('#select-all-agents').prop('checked', totalCheckboxes === checkedCheckboxes);
    });
    
    /**
     * Load agent data for editing
     */
    function loadAgentData(agentId) {
        $.ajax({
            url: terpedia_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'get_agent_data',
                agent_id: agentId,
                nonce: terpedia_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    populateAgentForm(response.data);
                    $('#modal-title').text('Edit Agent');
                    $('.terpedia-modal').show();
                } else {
                    showNotice('Error loading agent data: ' + response.data.message, 'error');
                }
            },
            error: function() {
                showNotice('Error loading agent data.', 'error');
            }
        });
    }
    
    /**
     * Populate agent form with data
     */
    function populateAgentForm(agentData) {
        $('#agent-form input[name="agent_id"]').remove();
        $('#agent-form').prepend('<input type="hidden" name="agent_id" value="' + agentData.ID + '">');
        
        $('#agent-form input[name="display_name"]').val(agentData.display_name);
        $('#agent-form input[name="user_login"]').val(agentData.user_login);
        $('#agent-form textarea[name="description"]').val(agentData.description);
        
        if (agentData.agent_type === 'terpene') {
            $('#agent-form input[name="terpene_name"]').val(agentData.terpene_name);
            $('#agent-form input[name="molecular_formula"]').val(agentData.molecular_formula);
            $('#agent-form input[name="aroma_profile"]').val(agentData.aroma_profile);
        } else {
            $('#agent-form input[name="expertise"]').val(agentData.expertise);
            $('#agent-form input[name="specializations"]').val(agentData.specializations);
            $('#agent-form input[name="experience_years"]').val(agentData.experience_years);
        }
        
        $('#agent-form textarea[name="system_prompt"]').val(agentData.system_prompt);
        $('#agent-form select[name="voice_provider"]').val(agentData.voice_provider);
        $('#agent-form input[name="voice_id"]').val(agentData.voice_id);
        $('#agent-form input[name="avatar_url"]').val(agentData.avatar_url);
        $('#agent-form input[name="is_available"]').prop('checked', agentData.is_available == 1);
    }
    
    /**
     * Create new agent
     */
    function createAgent(formData) {
        $.ajax({
            url: terpedia_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'create_new_agent',
                nonce: terpedia_ajax.nonce,
                form_data: formData
            },
            success: function(response) {
                if (response.success) {
                    showNotice('Agent created successfully!', 'success');
                    $('.terpedia-modal').hide();
                    location.reload();
                } else {
                    showNotice('Error creating agent: ' + response.data.message, 'error');
                }
            },
            error: function() {
                showNotice('Error creating agent.', 'error');
            }
        });
    }
    
    /**
     * Update existing agent
     */
    function updateAgent(formData) {
        $.ajax({
            url: terpedia_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'update_agent',
                nonce: terpedia_ajax.nonce,
                form_data: formData
            },
            success: function(response) {
                if (response.success) {
                    showNotice('Agent updated successfully!', 'success');
                    $('.terpedia-modal').hide();
                    location.reload();
                } else {
                    showNotice('Error updating agent: ' + response.data.message, 'error');
                }
            },
            error: function() {
                showNotice('Error updating agent.', 'error');
            }
        });
    }
    
    /**
     * Update agent status
     */
    function updateAgentStatus(agentId, status) {
        $.ajax({
            url: terpedia_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'update_agent_status',
                agent_id: agentId,
                status: status,
                nonce: terpedia_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    var button = $('.toggle-status[data-agent-id="' + agentId + '"]');
                    var statusElement = $('.terpedia-agent-status[data-agent-id="' + agentId + '"]');
                    
                    if (status === 'active') {
                        button.text('⏸️ Deactivate').data('status', 'active');
                        statusElement.removeClass('inactive').addClass('active');
                    } else {
                        button.text('▶️ Activate').data('status', 'inactive');
                        statusElement.removeClass('active').addClass('inactive');
                    }
                    
                    showNotice('Agent status updated successfully!', 'success');
                } else {
                    showNotice('Error updating agent status: ' + response.data.message, 'error');
                }
            },
            error: function() {
                showNotice('Error updating agent status.', 'error');
            }
        });
    }
    
    /**
     * Show bulk edit modal
     */
    function showBulkEditModal(agentIds) {
        // Create bulk edit modal content
        var modalContent = `
            <div class="terpedia-modal" id="bulk-edit-modal">
                <div class="terpedia-modal-content">
                    <div class="terpedia-modal-header">
                        <h2>Bulk Edit Agents</h2>
                        <span class="terpedia-modal-close">&times;</span>
                    </div>
                    <div class="terpedia-modal-body">
                        <p>Editing ${agentIds.length} selected agents.</p>
                        <form id="bulk-edit-form">
                            <table class="form-table">
                                <tr>
                                    <th scope="row">Voice Provider</th>
                                    <td>
                                        <select name="bulk_voice_provider">
                                            <option value="">No Change</option>
                                            <option value="openrouter">OpenRouter</option>
                                            <option value="elevenlabs">ElevenLabs</option>
                                            <option value="azure">Azure Speech</option>
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">Status</th>
                                    <td>
                                        <select name="bulk_status">
                                            <option value="">No Change</option>
                                            <option value="active">Active</option>
                                            <option value="inactive">Inactive</option>
                                        </select>
                                    </td>
                                </tr>
                            </table>
                            <div class="terpedia-modal-footer">
                                <button type="submit" class="button button-primary">Apply Changes</button>
                                <button type="button" class="button terpedia-modal-close">Cancel</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        `;
        
        $('body').append(modalContent);
        $('#bulk-edit-modal').show();
        
        // Handle bulk edit form submission
        $('#bulk-edit-form').on('submit', function(e) {
            e.preventDefault();
            
            var formData = $(this).serialize();
            
            $.ajax({
                url: terpedia_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'bulk_update_agents',
                    agent_ids: agentIds,
                    form_data: formData,
                    nonce: terpedia_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        showNotice('Agents updated successfully!', 'success');
                        $('#bulk-edit-modal').remove();
                        location.reload();
                    } else {
                        showNotice('Error updating agents: ' + response.data.message, 'error');
                    }
                },
                error: function() {
                    showNotice('Error updating agents.', 'error');
                }
            });
        });
    }
    
    /**
     * Show notice message
     */
    function showNotice(message, type) {
        var noticeClass = 'terpedia-notice ' + type;
        var notice = '<div class="' + noticeClass + '">' + message + '</div>';
        
        $('.wrap h1').after(notice);
        
        setTimeout(function() {
            $('.terpedia-notice').fadeOut(function() {
                $(this).remove();
            });
        }, 5000);
    }
    
    /**
     * Auto-save functionality for agent forms
     */
    var autoSaveTimeout;
    $('#agent-form input, #agent-form textarea, #agent-form select').on('change', function() {
        clearTimeout(autoSaveTimeout);
        autoSaveTimeout = setTimeout(function() {
            // Auto-save logic could be implemented here
        }, 2000);
    });
    
    /**
     * Image preview for avatar upload
     */
    $('input[name="avatar_url"]').on('change', function() {
        var url = $(this).val();
        if (url) {
            var preview = '<div class="avatar-preview"><img src="' + url + '" style="max-width: 100px; max-height: 100px; border-radius: 50%;" /></div>';
            $(this).after(preview);
        }
    });
    
    /**
     * Form validation
     */
    $('#agent-form').on('submit', function(e) {
        var displayName = $('input[name="display_name"]').val().trim();
        var userLogin = $('input[name="user_login"]').val().trim();
        
        if (!displayName) {
            e.preventDefault();
            showNotice('Display name is required.', 'error');
            return false;
        }
        
        if (!userLogin) {
            e.preventDefault();
            showNotice('Username is required.', 'error');
            return false;
        }
        
        // Validate username format
        if (!/^[a-zA-Z0-9_-]+$/.test(userLogin)) {
            e.preventDefault();
            showNotice('Username can only contain letters, numbers, underscores, and hyphens.', 'error');
            return false;
        }
    });
    
    /**
     * Search and filter functionality
     */
    $('#agent-search').on('keyup', function() {
        var searchTerm = $(this).val().toLowerCase();
        
        $('.terpedia-agent-card').each(function() {
            var agentName = $(this).find('h3').text().toLowerCase();
            var agentType = $(this).find('.terpedia-agent-type').text().toLowerCase();
            var agentDesc = $(this).find('.terpedia-agent-description').text().toLowerCase();
            
            if (agentName.includes(searchTerm) || agentType.includes(searchTerm) || agentDesc.includes(searchTerm)) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    });
    
    /**
     * Sort functionality
     */
    $('#agent-sort').on('change', function() {
        var sortBy = $(this).val();
        var container = $('.terpedia-agents-grid');
        var agents = container.children('.terpedia-agent-card').toArray();
        
        agents.sort(function(a, b) {
            var aVal, bVal;
            
            switch(sortBy) {
                case 'name':
                    aVal = $(a).find('h3').text();
                    bVal = $(b).find('h3').text();
                    break;
                case 'consultations':
                    aVal = parseInt($(a).find('.terpedia-stat strong').first().text()) || 0;
                    bVal = parseInt($(b).find('.terpedia-stat strong').first().text()) || 0;
                    break;
                case 'rating':
                    aVal = parseFloat($(a).find('.terpedia-stat strong').last().text()) || 0;
                    bVal = parseFloat($(b).find('.terpedia-stat strong').last().text()) || 0;
                    break;
                default:
                    return 0;
            }
            
            if (sortBy === 'name') {
                return aVal.localeCompare(bVal);
            } else {
                return bVal - aVal; // Descending order for numbers
            }
        });
        
        container.empty().append(agents);
    });
});
