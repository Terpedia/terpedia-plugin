/**
 * Enhanced Terport Editor JavaScript
 * 
 * @package Terpedia
 */

(function($) {
    'use strict';
    
    var TerportEditor = {
        
        init: function() {
            this.bindEvents();
            this.initializeComponents();
        },
        
        bindEvents: function() {
            // Auto-detect type from title
            $(document).on('click', '#generate_type_btn', this.generateType);
            
            // Generate description
            $(document).on('click', '#generate_description_btn', this.generateDescription);
            
            // Generate content
            $(document).on('click', '#generate_content_btn', this.generateContent);
            
            // Generate feature image
            $(document).on('click', '#generate_image_btn', this.generateImage);
            
            // Manage templates
            $(document).on('click', '#manage_templates_btn', this.manageTemplates);
            
            // Auto-hide status messages
            $(document).on('click', '.terport-status', this.hideStatus);
            
            // Template type change
            $(document).on('change', '#terpedia_terport_type', this.onTypeChange);
            
            // Template selection change
            $(document).on('change', '#terpedia_terport_template_id', this.onTemplateChange);
        },
        
        initializeComponents: function() {
            // Initialize tooltips
            this.initTooltips();
            
            // Initialize auto-save
            this.initAutoSave();
            
            // Initialize validation
            this.initValidation();
        },
        
        generateType: function(e) {
            e.preventDefault();
            
            var $btn = $(this);
            var postTitle = $('#title').val();
            
            if (!postTitle) {
                TerportEditor.showAlert('Please enter a title first.', 'error');
                return;
            }
            
            TerportEditor.setButtonLoading($btn, 'Analyzing title...');
            TerportEditor.showStatus('Analyzing title...', 'loading');
            
            $.post(terpediaTerport.ajaxurl, {
                action: 'terpedia_generate_terport_type',
                post_id: $('input[name="post_ID"]').val() || 0,
                title: postTitle,
                nonce: terpediaTerport.nonce
            })
            .done(function(response) {
                if (response.success) {
                    $('#terpedia_terport_type').val(response.data.type);
                    $('#terpedia_terport_description').val(response.data.description);
                    TerportEditor.showStatus('Type and description generated successfully!', 'success');
                } else {
                    TerportEditor.showStatus('Error: ' + response.data, 'error');
                }
            })
            .fail(function() {
                TerportEditor.showStatus('Network error occurred. Please try again.', 'error');
            })
            .always(function() {
                TerportEditor.setButtonNormal($btn, '🎯 Auto-Detect Type');
            });
        },
        
        generateDescription: function(e) {
            e.preventDefault();
            
            var $btn = $(this);
            var postTitle = $('#title').val();
            
            if (!postTitle) {
                TerportEditor.showAlert('Please enter a title first.', 'error');
                return;
            }
            
            TerportEditor.setButtonLoading($btn, 'Generating description...');
            TerportEditor.showStatus('Generating description...', 'loading');
            
            $.post(terpediaTerport.ajaxurl, {
                action: 'terpedia_generate_terport_type',
                post_id: $('input[name="post_ID"]').val() || 0,
                title: postTitle,
                description_only: true,
                nonce: terpediaTerport.nonce
            })
            .done(function(response) {
                if (response.success) {
                    $('#terpedia_terport_description').val(response.data.description);
                    TerportEditor.showStatus('Description generated successfully!', 'success');
                } else {
                    TerportEditor.showStatus('Error: ' + response.data, 'error');
                }
            })
            .fail(function() {
                TerportEditor.showStatus('Network error occurred. Please try again.', 'error');
            })
            .always(function() {
                TerportEditor.setButtonNormal($btn, '📝 Generate Description');
            });
        },
        
        generateContent: function(e) {
            e.preventDefault();
            
            var $btn = $(this);
            var templateId = $('#terpedia_terport_template_id').val();
            var prompt = $('#terpedia_terport_generation_prompt').val();
            var type = $('#terpedia_terport_type').val();
            var description = $('#terpedia_terport_description').val();
            
            if (!templateId) {
                TerportEditor.showAlert('Please select a template first.', 'error');
                return;
            }
            
            if (!prompt) {
                TerportEditor.showAlert('Please enter a generation prompt.', 'error');
                return;
            }
            
            TerportEditor.setButtonLoading($btn, 'Generating content...');
            TerportEditor.showStatus('Generating content...', 'loading');
            
            $.post(terpediaTerport.ajaxurl, {
                action: 'terpedia_generate_terport_content',
                post_id: $('input[name="post_ID"]').val() || 0,
                template_id: templateId,
                prompt: prompt,
                type: type,
                description: description,
                nonce: terpediaTerport.nonce
            })
            .done(function(response) {
                if (response.success) {
                    TerportEditor.updateContentEditor(response.data.content);
                    TerportEditor.showStatus('Content generated successfully!', 'success');
                } else {
                    TerportEditor.showStatus('Error: ' + response.data, 'error');
                }
            })
            .fail(function() {
                TerportEditor.showStatus('Network error occurred. Please try again.', 'error');
            })
            .always(function() {
                TerportEditor.setButtonNormal($btn, '🚀 Generate Content');
            });
        },
        
        generateImage: function(e) {
            e.preventDefault();
            
            var $btn = $(this);
            var title = $('#title').val();
            var type = $('#terpedia_terport_type').val();
            var description = $('#terpedia_terport_description').val();
            
            if (!title) {
                TerportEditor.showAlert('Please enter a title first.', 'error');
                return;
            }
            
            TerportEditor.setButtonLoading($btn, 'Generating image...');
            TerportEditor.showStatus('Generating feature image...', 'loading');
            
            $.post(terpediaTerport.ajaxurl, {
                action: 'terpedia_generate_terport_image',
                post_id: $('input[name="post_ID"]').val() || 0,
                title: title,
                type: type,
                description: description,
                nonce: terpediaTerport.nonce
            })
            .done(function(response) {
                if (response.success) {
                    TerportEditor.showImagePreview(response.data.image_url);
                    TerportEditor.showStatus('Feature image generated successfully!', 'success');
                } else {
                    TerportEditor.showStatus('Error: ' + response.data, 'error');
                }
            })
            .fail(function() {
                TerportEditor.showStatus('Network error occurred. Please try again.', 'error');
            })
            .always(function() {
                TerportEditor.setButtonNormal($btn, '🖼️ Generate Feature Image');
            });
        },
        
        manageTemplates: function(e) {
            e.preventDefault();
            window.open(terpediaTerport.admin_url + 'edit.php?post_type=terpedia_terport_template', '_blank');
        },
        
        onTypeChange: function() {
            var type = $(this).val();
            var $templateSelect = $('#terpedia_terport_template_id');
            
            if (type) {
                // Filter templates by type
                TerportEditor.filterTemplatesByType(type);
            }
        },
        
        onTemplateChange: function() {
            var templateId = $(this).val();
            if (templateId) {
                TerportEditor.loadTemplateInfo(templateId);
            }
        },
        
        filterTemplatesByType: function(type) {
            // This would filter templates based on type
            // For now, we'll just show all templates
            console.log('Filtering templates by type:', type);
        },
        
        loadTemplateInfo: function(templateId) {
            $.post(terpediaTerport.ajaxurl, {
                action: 'terpedia_get_terport_template_schema',
                template_id: templateId,
                nonce: terpediaTerport.nonce
            })
            .done(function(response) {
                if (response.success) {
                    TerportEditor.displayTemplateInfo(response.data);
                }
            });
        },
        
        displayTemplateInfo: function(schema) {
            // Display template information in the sidebar
            console.log('Template schema:', schema);
        },
        
        updateContentEditor: function(content) {
            // Update the main content editor
            if (typeof wp !== 'undefined' && wp.data && wp.data.dispatch) {
                // Gutenberg editor
                wp.data.dispatch('core/editor').editPost({
                    content: content
                });
            } else {
                // Classic editor
                if (typeof tinyMCE !== 'undefined' && tinyMCE.get('content')) {
                    tinyMCE.get('content').setContent(content);
                } else {
                    $('#content').val(content);
                }
            }
        },
        
        showImagePreview: function(imageUrl) {
            var $preview = $('#terport_image_preview');
            $preview.html('<img src="' + imageUrl + '" alt="Generated Feature Image" style="max-width: 100%; height: auto;">');
        },
        
        setButtonLoading: function($btn, text) {
            $btn.prop('disabled', true)
                .data('original-text', $btn.text())
                .html('<span class="spinner is-active" style="float: none; margin: 0 5px 0 0;"></span>' + text);
        },
        
        setButtonNormal: function($btn, originalText) {
            $btn.prop('disabled', false)
                .text(originalText || $btn.data('original-text'));
        },
        
        showStatus: function(message, type) {
            var $status = $('#terport_status');
            $status.removeClass('loading success error')
                  .addClass('terport-status ' + type)
                  .text(message)
                  .show();
            
            // Auto-hide after 5 seconds
            setTimeout(function() {
                $status.fadeOut();
            }, 5000);
        },
        
        hideStatus: function() {
            $(this).fadeOut();
        },
        
        showAlert: function(message, type) {
            alert(message); // Simple alert for now
        },
        
        initTooltips: function() {
            // Initialize tooltips for better UX
            $('[data-tooltip]').each(function() {
                var $this = $(this);
                var tooltip = $this.data('tooltip');
                $this.attr('title', tooltip);
            });
        },
        
        initAutoSave: function() {
            // Auto-save form data periodically
            var autoSaveInterval = setInterval(function() {
                TerportEditor.autoSave();
            }, 30000); // Every 30 seconds
            
            // Clear interval when page unloads
            $(window).on('beforeunload', function() {
                clearInterval(autoSaveInterval);
            });
        },
        
        autoSave: function() {
            // Auto-save form data to prevent loss
            var formData = {
                terpedia_terport_type: $('#terpedia_terport_type').val(),
                terpedia_terport_description: $('#terpedia_terport_description').val(),
                terpedia_terport_template_id: $('#terpedia_terport_template_id').val(),
                terpedia_terport_generation_prompt: $('#terpedia_terport_generation_prompt').val()
            };
            
            // Save to localStorage as backup
            localStorage.setItem('terpedia_terport_autosave', JSON.stringify(formData));
        },
        
        restoreAutoSave: function() {
            // Restore auto-saved data
            var savedData = localStorage.getItem('terpedia_terport_autosave');
            if (savedData) {
                try {
                    var data = JSON.parse(savedData);
                    $('#terpedia_terport_type').val(data.terpedia_terport_type || '');
                    $('#terpedia_terport_description').val(data.terpedia_terport_description || '');
                    $('#terpedia_terport_template_id').val(data.terpedia_terport_template_id || '');
                    $('#terpedia_terport_generation_prompt').val(data.terpedia_terport_generation_prompt || '');
                } catch (e) {
                    console.error('Error restoring auto-saved data:', e);
                }
            }
        },
        
        initValidation: function() {
            // Form validation
            $('#terport-template-form').on('submit', function(e) {
                if (!TerportEditor.validateForm()) {
                    e.preventDefault();
                    return false;
                }
            });
        },
        
        validateForm: function() {
            var isValid = true;
            var errors = [];
            
            // Validate required fields
            if (!$('#template_title').val().trim()) {
                errors.push('Template title is required');
                isValid = false;
            }
            
            if (!$('#template_type').val()) {
                errors.push('Template type is required');
                isValid = false;
            }
            
            if (!$('#template_content').val().trim()) {
                errors.push('Template content is required');
                isValid = false;
            }
            
            if (!isValid) {
                TerportEditor.showAlert('Please fix the following errors:\n' + errors.join('\n'), 'error');
            }
            
            return isValid;
        }
    };
    
    // Initialize when document is ready
    $(document).ready(function() {
        TerportEditor.init();
        TerportEditor.restoreAutoSave();
    });
    
    // Expose to global scope for debugging
    window.TerportEditor = TerportEditor;
    
})(jQuery);
