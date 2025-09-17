/**
 * Case Management System JavaScript
 * Handles chat, vital signs, interventions, and general case management functionality
 */

// Global variables
let currentCaseId = null;
let chatMessagesContainer = null;
let vitalSignsChart = null;
let chartInstance = null;

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    initializeCaseManagement();
});

/**
 * Initialize case management system
 */
function initializeCaseManagement() {
    // Get current case ID
    const messagesContainer = document.getElementById('chat-messages');
    if (messagesContainer) {
        currentCaseId = messagesContainer.dataset.caseId;
        chatMessagesContainer = messagesContainer;
        loadChatMessages();
    }
    
    // Initialize forms
    initializeForms();
    
    // Initialize chat
    initializeChat();
    
    // Initialize vital signs
    initializeVitalSigns();
    
    // Initialize interventions
    initializeInterventions();
    
    // Set up auto-save
    setupAutoSave();
}

/**
 * Initialize form handling
 */
function initializeForms() {
    const caseForm = document.getElementById('case-form');
    if (caseForm) {
        caseForm.addEventListener('submit', function(e) {
            e.preventDefault();
            saveCaseData();
        });
        
        // Auto-save on significant changes
        const inputs = caseForm.querySelectorAll('input, textarea, select');
        inputs.forEach(input => {
            let timeoutId;
            input.addEventListener('input', function() {
                clearTimeout(timeoutId);
                timeoutId = setTimeout(() => {
                    saveCaseData(true); // silent save
                }, 2000);
            });
        });
    }
}

/**
 * Initialize chat functionality
 */
function initializeChat() {
    const chatInput = document.getElementById('chat-input');
    if (chatInput) {
        chatInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });
        
        // Auto-resize textarea
        chatInput.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = this.scrollHeight + 'px';
        });
    }
    
    // Set up chat polling for real-time updates
    if (currentCaseId) {
        setInterval(loadChatMessages, 30000); // Poll every 30 seconds
    }
}

/**
 * Initialize vital signs functionality
 */
function initializeVitalSigns() {
    const vitalsForm = document.getElementById('vitals-form');
    if (vitalsForm) {
        vitalsForm.addEventListener('submit', function(e) {
            e.preventDefault();
            saveVitalSigns();
        });
    }
    
    // Initialize charts
    initializeVitalSignsChart();
    
    // Chart tab switching
    const chartTabs = document.querySelectorAll('.chart-tab');
    chartTabs.forEach(tab => {
        tab.addEventListener('click', function() {
            const chartType = this.dataset.chart;
            switchVitalSignsChart(chartType);
            
            // Update active tab
            chartTabs.forEach(t => t.classList.remove('active'));
            this.classList.add('active');
        });
    });
}

/**
 * Initialize interventions functionality
 */
function initializeInterventions() {
    const interventionForm = document.getElementById('intervention-form');
    if (interventionForm) {
        interventionForm.addEventListener('submit', function(e) {
            e.preventDefault();
            saveIntervention();
        });
    }
    
    // Load existing interventions
    if (currentCaseId) {
        loadInterventions();
    }
}

/**
 * Set up auto-save functionality
 */
function setupAutoSave() {
    // Save draft every 30 seconds
    setInterval(() => {
        const form = document.getElementById('case-form');
        if (form && hasUnsavedChanges(form)) {
            saveCaseData(true); // silent save
        }
    }, 30000);
}

/**
 * Case Management Functions
 */

function createNewCase() {
    const name = prompt('Enter patient name:');
    if (name && name.trim()) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '/wp-admin/admin-ajax.php';
        
        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'terpedia_create_case';
        
        const nameInput = document.createElement('input');
        nameInput.type = 'hidden';
        nameInput.name = 'patient_name';
        nameInput.value = name.trim();
        
        form.appendChild(actionInput);
        form.appendChild(nameInput);
        document.body.appendChild(form);
        form.submit();
    }
}

function editCase(caseId) {
    const form = document.querySelector('#case-form');
    if (form) {
        form.classList.add('editing');
        const nameInput = form.querySelector('input[name="patient_name"]');
        if (nameInput) {
            nameInput.focus();
        }
    }
}

function exportCase(caseId) {
    showNotification('Preparing case export...', 'info');
    window.open(`/wp-admin/admin-ajax.php?action=terpedia_export_case&case_id=${caseId}`, '_blank');
}

function resetForm() {
    const form = document.getElementById('case-form');
    if (form && confirm('Are you sure you want to reset all changes?')) {
        form.reset();
        showNotification('Form reset successfully', 'success');
    }
}

function saveCaseData(silent = false) {
    const form = document.getElementById('case-form');
    if (!form) return;
    
    const formData = new FormData(form);
    const caseId = form.dataset.caseId;
    
    formData.append('action', 'terpedia_save_case_data');
    formData.append('case_id', caseId);
    
    if (!silent) {
        showLoading('Saving case...');
    }
    
    fetch('/wp-admin/admin-ajax.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        if (data.success) {
            if (!silent) {
                showNotification('Case saved successfully!', 'success');
            }
            markFormAsSaved(form);
        } else {
            showNotification('Error saving case: ' + (data.data || 'Unknown error'), 'error');
        }
    })
    .catch(error => {
        hideLoading();
        showNotification('Network error while saving case', 'error');
        console.error('Save error:', error);
    });
}

/**
 * Chat Functions
 */

function sendMessage() {
    const input = document.getElementById('chat-input');
    const message = input.value.trim();
    if (!message || !currentCaseId) return;
    
    // Add message to chat immediately
    addMessageToChat('user', message);
    input.value = '';
    
    // Send via AJAX
    const formData = new FormData();
    formData.append('action', 'terpedia_send_case_message');
    formData.append('case_id', currentCaseId);
    formData.append('message', message);
    formData.append('user_type', 'human');
    
    fetch('/wp-admin/admin-ajax.php', {
        method: 'POST',
        body: formData
    })
    .catch(error => {
        console.error('Error sending message:', error);
        showNotification('Failed to send message', 'error');
    });
}

function askAI() {
    const input = document.getElementById('chat-input');
    const message = input.value.trim() || 'Please provide insights about this case.';
    
    if (!currentCaseId) return;
    
    addMessageToChat('user', message);
    const aiMessageId = addMessageToChat('ai', 'Thinking...', true);
    input.value = '';
    
    const formData = new FormData();
    formData.append('action', 'terpedia_send_case_message');
    formData.append('case_id', currentCaseId);
    formData.append('message', message);
    formData.append('user_type', 'ai_request');
    
    fetch('/wp-admin/admin-ajax.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.data.ai_response) {
            updateMessage(aiMessageId, data.data.ai_response);
        } else {
            updateMessage(aiMessageId, 'Sorry, I couldn\'t generate a response. Please try again.');
        }
    })
    .catch(error => {
        console.error('AI request error:', error);
        updateMessage(aiMessageId, 'Error connecting to AI service.');
    });
}

function addMessageToChat(type, message, isTemporary = false) {
    if (!chatMessagesContainer) return null;
    
    const messageDiv = document.createElement('div');
    const messageId = 'msg_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
    messageDiv.id = messageId;
    messageDiv.className = `message ${type}` + (isTemporary ? ' temporary' : '');
    
    const now = new Date();
    const timeStr = now.toLocaleTimeString();
    
    messageDiv.innerHTML = `
        <div class='message-content'>${escapeHtml(message)}</div>
        <div class='message-time'>${timeStr}</div>
    `;
    
    chatMessagesContainer.appendChild(messageDiv);
    chatMessagesContainer.scrollTop = chatMessagesContainer.scrollHeight;
    
    return messageId;
}

function updateMessage(messageId, newContent) {
    const messageEl = document.getElementById(messageId);
    if (messageEl) {
        const contentEl = messageEl.querySelector('.message-content');
        if (contentEl) {
            contentEl.innerHTML = escapeHtml(newContent);
        }
        messageEl.classList.remove('temporary');
    }
}

function loadChatMessages() {
    if (!currentCaseId || !chatMessagesContainer) return;
    
    fetch(`/wp-admin/admin-ajax.php?action=terpedia_get_case_messages&case_id=${currentCaseId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                chatMessagesContainer.innerHTML = '';
                
                data.data.forEach(message => {
                    addMessageToChat(message.user_type, message.message);
                });
            }
        })
        .catch(error => {
            console.error('Error loading messages:', error);
        });
}

/**
 * Vital Signs Functions
 */

function saveVitalSigns() {
    const form = document.getElementById('vitals-form');
    if (!form || !currentCaseId) return;
    
    const formData = new FormData(form);
    formData.append('action', 'terpedia_save_vital_signs');
    formData.append('case_id', currentCaseId);
    
    showLoading('Saving vital signs...');
    
    fetch('/wp-admin/admin-ajax.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        if (data.success) {
            showNotification('Vital signs saved!', 'success');
            form.reset();
            loadVitalSignsData();
        } else {
            showNotification('Error saving vital signs: ' + (data.data || 'Unknown error'), 'error');
        }
    })
    .catch(error => {
        hideLoading();
        showNotification('Network error saving vital signs', 'error');
        console.error('Vital signs save error:', error);
    });
}

function initializeVitalSignsChart() {
    const chartCanvas = document.getElementById('vitals-chart');
    if (!chartCanvas || !window.Chart) return;
    
    const ctx = chartCanvas.getContext('2d');
    
    chartInstance = new Chart(ctx, {
        type: 'line',
        data: {
            labels: [],
            datasets: [{
                label: 'Heart Rate',
                data: [],
                borderColor: '#007cba',
                backgroundColor: 'rgba(0, 124, 186, 0.1)',
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: {
                    type: 'time',
                    time: {
                        unit: 'day'
                    }
                },
                y: {
                    beginAtZero: true
                }
            },
            plugins: {
                legend: {
                    display: true
                }
            }
        }
    });
    
    // Load initial data
    loadVitalSignsData();
}

function switchVitalSignsChart(chartType) {
    if (!chartInstance) return;
    
    const datasets = {
        'heart_rate': {
            label: 'Heart Rate (BPM)',
            borderColor: '#007cba',
            backgroundColor: 'rgba(0, 124, 186, 0.1)'
        },
        'blood_pressure': {
            label: 'Blood Pressure',
            borderColor: '#ef4444',
            backgroundColor: 'rgba(239, 68, 68, 0.1)'
        },
        'weight': {
            label: 'Weight',
            borderColor: '#10b981',
            backgroundColor: 'rgba(16, 185, 129, 0.1)'
        },
        'temperature': {
            label: 'Temperature',
            borderColor: '#f59e0b',
            backgroundColor: 'rgba(245, 158, 11, 0.1)'
        }
    };
    
    if (datasets[chartType]) {
        chartInstance.data.datasets[0] = {
            ...chartInstance.data.datasets[0],
            ...datasets[chartType]
        };
        chartInstance.update();
        loadVitalSignsData(chartType);
    }
}

function loadVitalSignsData(chartType = 'heart_rate') {
    if (!currentCaseId) return;
    
    fetch(`/wp-admin/admin-ajax.php?action=terpedia_get_vital_signs&case_id=${currentCaseId}&chart_type=${chartType}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && chartInstance) {
                chartInstance.data.labels = data.data.labels;
                chartInstance.data.datasets[0].data = data.data.values;
                chartInstance.update();
            }
        })
        .catch(error => {
            console.error('Error loading vital signs:', error);
        });
}

/**
 * Interventions Functions
 */

function saveIntervention() {
    const form = document.getElementById('intervention-form');
    if (!form || !currentCaseId) return;
    
    const formData = new FormData(form);
    formData.append('action', 'terpedia_save_intervention');
    formData.append('case_id', currentCaseId);
    
    showLoading('Saving intervention...');
    
    fetch('/wp-admin/admin-ajax.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        if (data.success) {
            showNotification('Intervention saved!', 'success');
            form.reset();
            loadInterventions();
        } else {
            showNotification('Error saving intervention: ' + (data.data || 'Unknown error'), 'error');
        }
    })
    .catch(error => {
        hideLoading();
        showNotification('Network error saving intervention', 'error');
        console.error('Intervention save error:', error);
    });
}

function loadInterventions() {
    if (!currentCaseId) return;
    
    fetch(`/wp-admin/admin-ajax.php?action=terpedia_get_interventions&case_id=${currentCaseId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderInterventions(data.data);
            }
        })
        .catch(error => {
            console.error('Error loading interventions:', error);
        });
}

function renderInterventions(interventions) {
    const container = document.getElementById('interventions-list');
    if (!container) return;
    
    if (interventions.length === 0) {
        container.innerHTML = '<div class="no-interventions"><h3>No interventions recorded</h3><p>Add an intervention to get started.</p></div>';
        return;
    }
    
    container.innerHTML = interventions.map(intervention => `
        <div class="intervention-card">
            <div class="intervention-header">
                <div class="intervention-type">${escapeHtml(intervention.intervention_type)}</div>
                <div class="intervention-date">${formatDate(intervention.intervention_date)}</div>
            </div>
            <div class="intervention-category category-${intervention.intervention_category}">
                ${escapeHtml(intervention.intervention_category)}
            </div>
            <div class="intervention-description">
                ${escapeHtml(intervention.description)}
            </div>
            ${intervention.outcome ? `
                <div class="intervention-outcome">
                    <strong>Outcome:</strong> ${escapeHtml(intervention.outcome)}
                </div>
            ` : ''}
        </div>
    `).join('');
}

/**
 * Utility Functions
 */

function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 5000);
}

function showLoading(message = 'Loading...') {
    const loading = document.createElement('div');
    loading.id = 'loading-overlay';
    loading.innerHTML = `
        <div style="
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            color: white;
            font-weight: 600;
        ">
            <div style="background: white; padding: 20px; border-radius: 8px; color: #333; display: flex; align-items: center; gap: 10px;">
                <div class="loading"></div>
                ${message}
            </div>
        </div>
    `;
    
    document.body.appendChild(loading);
}

function hideLoading() {
    const loading = document.getElementById('loading-overlay');
    if (loading) {
        loading.remove();
    }
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString() + ' ' + date.toLocaleTimeString();
}

function hasUnsavedChanges(form) {
    // Simple check for form changes
    // In a real implementation, you'd track original values
    return form.classList.contains('editing');
}

function markFormAsSaved(form) {
    form.classList.remove('editing');
}

// Global functions for inline event handlers
window.createNewCase = createNewCase;
window.editCase = editCase;
window.exportCase = exportCase;
window.resetForm = resetForm;
window.sendMessage = sendMessage;
window.askAI = askAI;