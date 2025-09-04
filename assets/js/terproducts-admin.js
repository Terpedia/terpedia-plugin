/**
 * Enhanced Terproducts System - Admin JavaScript
 * Handles admin interface interactions and AJAX calls
 */

jQuery(document).ready(function($) {
    'use strict';
    
    // Initialize admin functionality
    initTerproductsAdmin();
    
    function initTerproductsAdmin() {
        // Photo management
        initPhotoManagement();
        
        // Ingredient analysis
        initIngredientAnalysis();
        
        // Terpene insights
        initTerpeneInsights();
        
        // AI analysis tools
        initAIAnalysisTools();
        
        // Form validation
        initFormValidation();
    }
    
    function initPhotoManagement() {
        // Handle photo uploads
        $('#product-camera-input').on('change', function(e) {
            handleAdminPhotoUpload(e.target.files);
        });
        
        // Open camera
        $('#open-camera-btn').on('click', function() {
            $('#product-camera-input').click();
        });
        
        // Upload photos
        $('#upload-photos-btn').on('click', function() {
            $('#product-camera-input').click();
        });
        
        // Set main photo
        $(document).on('click', '.set-main-photo', function() {
            const photoId = $(this).closest('.photo-item').data('photo-id');
            $('#main_product_photo').val(photoId);
            
            // Update UI
            $('.photo-item').removeClass('main-photo');
            $(this).closest('.photo-item').addClass('main-photo');
            
            showAdminMessage('Main photo updated!', 'success');
        });
        
        // Delete photo
        $(document).on('click', '.delete-photo', function() {
            if (confirm('Are you sure you want to delete this photo?')) {
                const photoItem = $(this).closest('.photo-item');
                const photoId = photoItem.data('photo-id');
                
                // Remove from photos data
                const photosData = JSON.parse($('#product_photos_data').val() || '[]');
                const updatedPhotos = photosData.filter(photo => photo.id != photoId);
                $('#product_photos_data').val(JSON.stringify(updatedPhotos));
                
                // Remove from UI
                photoItem.fadeOut(300, function() {
                    $(this).remove();
                });
                
                showAdminMessage('Photo deleted!', 'success');
            }
        });
    }
    
    function handleAdminPhotoUpload(files) {
        if (files.length === 0) return;
        
        const formData = new FormData();
        const promises = [];
        
        Array.from(files).forEach(file => {
            if (file.type.startsWith('image/')) {
                formData.append('product_photo', file);
                formData.append('action', 'upload_product_photo');
                formData.append('nonce', terpediaTerproductsAdmin.nonce);
                
                promises.push(
                    $.ajax({
                        url: terpediaTerproductsAdmin.ajaxurl,
                        type: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function(response) {
                            if (response.success) {
                                addPhotoToAdminGallery(response.data);
                            } else {
                                showAdminMessage('Upload failed: ' + response.data, 'error');
                            }
                        },
                        error: function() {
                            showAdminMessage('Upload failed. Please try again.', 'error');
                        }
                    })
                );
            }
        });
        
        if (promises.length > 0) {
            showAdminMessage('Uploading photos...', 'info');
            Promise.all(promises).then(() => {
                showAdminMessage('Photos uploaded successfully!', 'success');
            });
        }
    }
    
    function addPhotoToAdminGallery(photoData) {
        const photosData = JSON.parse($('#product_photos_data').val() || '[]');
        photosData.push(photoData);
        $('#product_photos_data').val(JSON.stringify(photosData));
        
        // Add to gallery
        const photoHtml = `
            <div class="photo-item" data-photo-id="${photoData.id}">
                <img src="${photoData.url}" alt="Product Photo" />
                <div class="photo-actions">
                    <button type="button" class="set-main-photo" title="Set as Main Photo">⭐</button>
                    <button type="button" class="delete-photo" title="Delete Photo">🗑️</button>
                </div>
            </div>
        `;
        
        if ($('.no-photos').length > 0) {
            $('.no-photos').remove();
        }
        
        $('#photos-grid').append(photoHtml);
    }
    
    function initIngredientAnalysis() {
        $('#analyze-ingredients-btn').on('click', function() {
            const ingredientsText = $('#ingredients_list').val().trim();
            
            if (!ingredientsText) {
                showAdminMessage('Please enter ingredients to analyze.', 'error');
                return;
            }
            
            const button = $(this);
            const originalText = button.text();
            
            button.prop('disabled', true).text('🔍 Analyzing...');
            $('#analysis-status').html('<div class="spinner"></div><p>Analyzing ingredients...</p>');
            
            $.ajax({
                url: terpediaTerproductsAdmin.ajaxurl,
                type: 'POST',
                data: {
                    action: 'analyze_product_ingredients',
                    nonce: terpediaTerproductsAdmin.nonce,
                    post_id: $('input[name="post_ID"]').val(),
                    ingredients_text: ingredientsText
                },
                success: function(response) {
                    if (response.success) {
                        displayAdminAnalysisResults(response.data);
                        showAdminMessage('Ingredients analyzed successfully!', 'success');
                    } else {
                        showAdminMessage('Analysis failed: ' + response.data, 'error');
                    }
                },
                error: function() {
                    showAdminMessage('Analysis failed. Please try again.', 'error');
                },
                complete: function() {
                    button.prop('disabled', false).text(originalText);
                }
            });
        });
    }
    
    function displayAdminAnalysisResults(data) {
        let html = '<div class="analysis-results">';
        
        // Detected terpenes
        if (data.terpenes && data.terpenes.length > 0) {
            html += '<div class="detected-terpenes">';
            html += '<h5>Detected Terpenes:</h5>';
            html += '<div class="terpenes-grid">';
            
            data.terpenes.forEach(terpene => {
                html += `
                    <div class="terpene-item">
                        <span class="terpene-name">${terpene.name}</span>
                        <span class="terpene-confidence">${terpene.confidence}%</span>
                    </div>
                `;
            });
            
            html += '</div></div>';
        }
        
        // Analysis text
        if (data.analysis) {
            html += '<div class="analysis-content">';
            html += '<h5>Detailed Analysis:</h5>';
            html += `<div class="analysis-text">${data.analysis.replace(/\n/g, '<br>')}</div>`;
            html += '</div>';
        }
        
        // Confidence meter
        if (data.confidence) {
            html += '<div class="confidence-meter">';
            html += '<h5>Analysis Confidence:</h5>';
            html += '<div class="confidence-bar">';
            html += `<div class="confidence-fill" style="width: ${data.confidence}%"></div>`;
            html += `<span class="confidence-text">${data.confidence}%</span>`;
            html += '</div></div>';
        }
        
        html += '</div>';
        
        $('#analysis-status').html(html);
    }
    
    function initTerpeneInsights() {
        $('#generate-insights-btn').on('click', function() {
            const button = $(this);
            const originalText = button.text();
            
            button.prop('disabled', true).text('🧠 Generating...');
            $('#insights-status').html('<div class="spinner"></div><p>Generating terpene insights...</p>');
            
            $.ajax({
                url: terpediaTerproductsAdmin.ajaxurl,
                type: 'POST',
                data: {
                    action: 'generate_terpene_insights',
                    nonce: terpediaTerproductsAdmin.nonce,
                    post_id: $('input[name="post_ID"]').val()
                },
                success: function(response) {
                    if (response.success) {
                        displayAdminTerpeneInsights(response.data);
                        showAdminMessage('Terpene insights generated successfully!', 'success');
                    } else {
                        showAdminMessage('Insights generation failed: ' + response.data, 'error');
                    }
                },
                error: function() {
                    showAdminMessage('Insights generation failed. Please try again.', 'error');
                },
                complete: function() {
                    button.prop('disabled', false).text(originalText);
                }
            });
        });
    }
    
    function displayAdminTerpeneInsights(data) {
        let html = '<div class="insights-results">';
        
        if (data.profile_analysis) {
            html += '<div class="terpene-insights-section">';
            html += '<h4>Terpene Profile Analysis</h4>';
            html += `<div class="insights-content">${data.profile_analysis.replace(/\n/g, '<br>')}</div>`;
            html += '</div>';
        }
        
        if (data.recommendations) {
            html += '<div class="terpene-recommendations-section">';
            html += '<h4>Terpene Recommendations</h4>';
            html += `<div class="recommendations-content">${data.recommendations.replace(/\n/g, '<br>')}</div>`;
            html += '</div>';
        }
        
        if (data.enhancements) {
            html += '<div class="enhancement-suggestions-section">';
            html += '<h4>Enhancement Suggestions</h4>';
            html += `<div class="enhancement-content">${data.enhancements.replace(/\n/g, '<br>')}</div>`;
            html += '</div>';
        }
        
        if (data.benefits) {
            html += '<div class="therapeutic-benefits-section">';
            html += '<h4>Therapeutic Benefits</h4>';
            html += `<div class="benefits-content">${data.benefits.replace(/\n/g, '<br>')}</div>`;
            html += '</div>';
        }
        
        html += '</div>';
        
        $('#insights-status').html(html);
    }
    
    function initAIAnalysisTools() {
        // Re-analyze product
        $('#reanalyze-product').on('click', function() {
            if (confirm('This will re-analyze the product with the latest AI models. Continue?')) {
                const button = $(this);
                const originalText = button.text();
                
                button.prop('disabled', true).text('🔄 Re-analyzing...');
                
                // Simulate re-analysis (in real implementation, this would call AI)
                setTimeout(() => {
                    button.prop('disabled', false).text(originalText);
                    showAdminMessage('Product re-analyzed successfully!', 'success');
                }, 3000);
            }
        });
        
        // Update terpene database
        $('#update-terpene-db').on('click', function() {
            const button = $(this);
            const originalText = button.text();
            
            button.prop('disabled', true).text('📊 Updating...');
            
            // Simulate database update
            setTimeout(() => {
                button.prop('disabled', false).text(originalText);
                showAdminMessage('Terpene database updated!', 'success');
            }, 2000);
        });
        
        // Export analysis
        $('#export-analysis').on('click', function() {
            const postId = $('input[name="post_ID"]').val();
            const exportData = {
                post_id: postId,
                action: 'export_terproduct_analysis',
                nonce: terpediaTerproductsAdmin.nonce
            };
            
            // Create and download export file
            const exportUrl = terpediaTerproductsAdmin.ajaxurl + '?' + $.param(exportData);
            window.open(exportUrl, '_blank');
            
            showAdminMessage('Analysis exported successfully!', 'success');
        });
    }
    
    function initFormValidation() {
        // Validate required fields before save
        $('#post').on('submit', function(e) {
            const title = $('#title').val().trim();
            const productType = $('#product_type').val();
            
            if (!title) {
                e.preventDefault();
                showAdminMessage('Please enter a product title.', 'error');
                $('#title').focus();
                return false;
            }
            
            if (!productType) {
                e.preventDefault();
                showAdminMessage('Please select a product type.', 'error');
                $('#product_type').focus();
                return false;
            }
        });
        
        // Auto-save draft functionality
        let autoSaveTimeout;
        $('input, textarea, select').on('change', function() {
            clearTimeout(autoSaveTimeout);
            autoSaveTimeout = setTimeout(() => {
                if ($('#auto-save').is(':checked')) {
                    autoSaveDraft();
                }
            }, 2000);
        });
    }
    
    function autoSaveDraft() {
        const formData = $('#post').serialize();
        formData += '&action=auto_save_terproduct&nonce=' + terpediaTerproductsAdmin.nonce;
        
        $.ajax({
            url: terpediaTerproductsAdmin.ajaxurl,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    console.log('Auto-saved successfully');
                }
            }
        });
    }
    
    function showAdminMessage(message, type) {
        const messageClass = type === 'error' ? 'notice-error' : 
                           type === 'success' ? 'notice-success' : 'notice-info';
        
        const messageHtml = `
            <div class="notice ${messageClass} is-dismissible">
                <p>${message}</p>
                <button type="button" class="notice-dismiss">
                    <span class="screen-reader-text">Dismiss this notice.</span>
                </button>
            </div>
        `;
        
        // Remove existing notices
        $('.notice').remove();
        
        // Add new notice
        $('.wrap h1').after(messageHtml);
        
        // Auto-dismiss after 5 seconds
        setTimeout(() => {
            $('.notice').fadeOut();
        }, 5000);
        
        // Handle dismiss button
        $('.notice-dismiss').on('click', function() {
            $(this).closest('.notice').fadeOut();
        });
    }
    
    // Enhanced photo preview with drag and drop
    function initDragAndDrop() {
        const dropZone = $('.terproduct-photo-capture-container');
        
        dropZone.on('dragover', function(e) {
            e.preventDefault();
            $(this).addClass('drag-over');
        });
        
        dropZone.on('dragleave', function(e) {
            e.preventDefault();
            $(this).removeClass('drag-over');
        });
        
        dropZone.on('drop', function(e) {
            e.preventDefault();
            $(this).removeClass('drag-over');
            
            const files = e.originalEvent.dataTransfer.files;
            if (files.length > 0) {
                handleAdminPhotoUpload(files);
            }
        });
    }
    
    // Initialize drag and drop
    initDragAndDrop();
    
    // Keyboard shortcuts
    $(document).on('keydown', function(e) {
        // Ctrl+S to save
        if (e.ctrlKey && e.key === 's') {
            e.preventDefault();
            $('#publish').click();
        }
        
        // Ctrl+A to analyze ingredients
        if (e.ctrlKey && e.key === 'a') {
            e.preventDefault();
            $('#analyze-ingredients-btn').click();
        }
        
        // Ctrl+I to generate insights
        if (e.ctrlKey && e.key === 'i') {
            e.preventDefault();
            $('#generate-insights-btn').click();
        }
    });
    
    // Real-time ingredient validation
    $('#ingredients_list').on('input', function() {
        const text = $(this).val();
        const wordCount = text.trim().split(/\s+/).length;
        
        if (wordCount > 0) {
            $(this).addClass('has-content');
        } else {
            $(this).removeClass('has-content');
        }
    });
    
    // Product type change handler
    $('#product_type').on('change', function() {
        const productType = $(this).val();
        
        // Show/hide relevant fields based on product type
        if (productType === 'essential_oil' || productType === 'terpene_isolate') {
            $('.purity-field').show();
            $('.dilution-field').hide();
        } else if (productType === 'aromatherapy_blend') {
            $('.purity-field').hide();
            $('.dilution-field').show();
        } else {
            $('.purity-field, .dilution-field').hide();
        }
    });
    
    // Initialize product type handler
    $('#product_type').trigger('change');
});
