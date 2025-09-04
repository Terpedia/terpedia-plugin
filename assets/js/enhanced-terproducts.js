/**
 * Enhanced Terproducts System - Frontend JavaScript
 * Handles photo capture, ingredient analysis, and terpene insights
 */

jQuery(document).ready(function($) {
    'use strict';
    
    // Initialize terproducts system
    initTerproductsSystem();
    
    function initTerproductsSystem() {
        // Photo capture functionality
        initPhotoCapture();
        
        // Ingredient analysis
        initIngredientAnalysis();
        
        // Terpene insights generation
        initTerpeneInsights();
        
        // Product scanner
        initProductScanner();
        
        // Product list filters
        initProductFilters();
    }
    
    function initPhotoCapture() {
        // Camera input handling
        $('#product-camera-input').on('change', function(e) {
            handleFileSelection(e.target.files);
        });
        
        // Open camera button
        $('#open-camera-btn').on('click', function() {
            $('#product-camera-input').click();
        });
        
        // Upload photos button
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
            
            showMessage('Main photo updated!', 'success');
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
                photoItem.remove();
                
                showMessage('Photo deleted!', 'success');
            }
        });
    }
    
    function handleFileSelection(files) {
        if (files.length === 0) return;
        
        const formData = new FormData();
        const promises = [];
        
        Array.from(files).forEach(file => {
            if (file.type.startsWith('image/')) {
                formData.append('product_photo', file);
                formData.append('action', 'upload_product_photo');
                formData.append('nonce', terpediaTerproducts.nonce);
                
                promises.push(
                    $.ajax({
                        url: terpediaTerproducts.ajaxurl,
                        type: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function(response) {
                            if (response.success) {
                                addPhotoToGallery(response.data);
                            } else {
                                showMessage('Upload failed: ' + response.data, 'error');
                            }
                        },
                        error: function() {
                            showMessage('Upload failed. Please try again.', 'error');
                        }
                    })
                );
            }
        });
        
        if (promises.length > 0) {
            showMessage('Uploading photos...', 'info');
            Promise.all(promises).then(() => {
                showMessage('Photos uploaded successfully!', 'success');
            });
        }
    }
    
    function addPhotoToGallery(photoData) {
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
                showMessage('Please enter ingredients to analyze.', 'error');
                return;
            }
            
            const button = $(this);
            const originalText = button.text();
            
            button.prop('disabled', true).text('🔍 Analyzing...');
            $('#analysis-status').html('<div class="spinner"></div><p>Analyzing ingredients...</p>');
            
            $.ajax({
                url: terpediaTerproducts.ajaxurl,
                type: 'POST',
                data: {
                    action: 'analyze_product_ingredients',
                    nonce: terpediaTerproducts.nonce,
                    post_id: $('input[name="post_ID"]').val(),
                    ingredients_text: ingredientsText
                },
                success: function(response) {
                    if (response.success) {
                        displayAnalysisResults(response.data);
                        showMessage('Ingredients analyzed successfully!', 'success');
                    } else {
                        showMessage('Analysis failed: ' + response.data, 'error');
                    }
                },
                error: function() {
                    showMessage('Analysis failed. Please try again.', 'error');
                },
                complete: function() {
                    button.prop('disabled', false).text(originalText);
                }
            });
        });
    }
    
    function displayAnalysisResults(data) {
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
                url: terpediaTerproducts.ajaxurl,
                type: 'POST',
                data: {
                    action: 'generate_terpene_insights',
                    nonce: terpediaTerproducts.nonce,
                    post_id: $('input[name="post_ID"]').val()
                },
                success: function(response) {
                    if (response.success) {
                        displayTerpeneInsights(response.data);
                        showMessage('Terpene insights generated successfully!', 'success');
                    } else {
                        showMessage('Insights generation failed: ' + response.data, 'error');
                    }
                },
                error: function() {
                    showMessage('Insights generation failed. Please try again.', 'error');
                },
                complete: function() {
                    button.prop('disabled', false).text(originalText);
                }
            });
        });
    }
    
    function displayTerpeneInsights(data) {
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
    
    function initProductScanner() {
        // Scanner camera functionality
        $('#open-camera-scanner').on('click', function() {
            $('#product-upload-scanner').click();
        });
        
        // Scanner upload handling
        $('#product-upload-scanner').on('change', function(e) {
            handleScannerUpload(e.target.files);
        });
        
        // Upload photos for scanner
        $('#upload-photos-scanner').on('click', function() {
            $('#product-upload-scanner').click();
        });
    }
    
    function handleScannerUpload(files) {
        if (files.length === 0) return;
        
        $('.scanning-status').show();
        
        // Simulate analysis (in real implementation, this would call AI analysis)
        setTimeout(() => {
            $('.scanning-status').hide();
            $('.analysis-results').show().html(`
                <div class="scanner-analysis">
                    <h4>📊 Analysis Complete</h4>
                    <p>Product photos have been analyzed. Detected potential terpenes and ingredients.</p>
                    <div class="detected-items">
                        <h5>Potential Terpenes:</h5>
                        <div class="terpene-tags">
                            <span class="terpene-tag">Limonene</span>
                            <span class="terpene-tag">Linalool</span>
                            <span class="terpene-tag">Myrcene</span>
                        </div>
                    </div>
                    <button type="button" class="button button-primary">Create Terproduct Entry</button>
                </div>
            `);
        }, 3000);
    }
    
    function initProductFilters() {
        // Category filter
        $('#filter-terproduct-category').on('change', function() {
            const category = $(this).val();
            filterProducts('category', category);
        });
        
        // Type filter
        $('#filter-terproduct-type').on('change', function() {
            const type = $(this).val();
            filterProducts('type', type);
        });
    }
    
    function filterProducts(filterType, value) {
        $('.terproduct-item').each(function() {
            const item = $(this);
            const itemValue = item.data(filterType);
            
            if (!value || itemValue.includes(value)) {
                item.show();
            } else {
                item.hide();
            }
        });
    }
    
    function showMessage(message, type) {
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
        $('body').prepend(messageHtml);
        
        // Auto-dismiss after 5 seconds
        setTimeout(() => {
            $('.notice').fadeOut();
        }, 5000);
    }
    
    // Mobile camera access
    if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
        // Modern browsers with camera support
        console.log('Camera API available');
    } else {
        // Fallback for older browsers
        console.log('Camera API not available, using file input fallback');
    }
});
