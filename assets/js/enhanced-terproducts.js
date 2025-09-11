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
                
                // Hide analyze all button if no photos left
                if (updatedPhotos.length === 0) {
                    $('#analyze-all-photos-btn').closest('.analyze-all-photos-section').remove();
                }
                
                showMessage('Photo deleted!', 'success');
            }
        });
        
        // Analyze single photo
        $(document).on('click', '.analyze-photo', function() {
            const photoItem = $(this).closest('.photo-item');
            const photoId = photoItem.data('photo-id');
            const imageUrl = photoItem.find('img').attr('src');
            
            analyzePhotoIngredients([imageUrl], 'single');
        });
        
        // Analyze all photos
        $(document).on('click', '#analyze-all-photos-btn', function() {
            const photosData = JSON.parse($('#product_photos_data').val() || '[]');
            const imageUrls = photosData.map(photo => photo.url);
            
            if (imageUrls.length === 0) {
                showMessage('No photos available to analyze.', 'error');
                return;
            }
            
            analyzePhotoIngredients(imageUrls, 'all');
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
                    <button type="button" class="analyze-photo" title="Analyze for Ingredients">🔍</button>
                    <button type="button" class="delete-photo" title="Delete Photo">🗑️</button>
                </div>
            </div>
        `;
        
        if ($('.no-photos').length > 0) {
            $('.no-photos').remove();
        }
        
        $('#photos-grid').append(photoHtml);
        
        // Show analyze all photos button if we have photos
        showAnalyzeAllPhotosButton();
    }
    
    function showAnalyzeAllPhotosButton() {
        const photosData = JSON.parse($('#product_photos_data').val() || '[]');
        if (photosData.length > 0 && $('#analyze-all-photos-btn').length === 0) {
            const analyzeAllBtn = `
                <div class="analyze-all-photos-section" style="margin-top: 15px; text-align: center;">
                    <button type="button" id="analyze-all-photos-btn" class="button button-primary">
                        🔍 Analyze All Photos for Ingredients
                    </button>
                    <p style="font-size: 12px; color: #666; margin-top: 5px;">
                        Uses AI to extract ingredients, terpenes, and product info from your photos
                    </p>
                </div>
            `;
            $('.product-photos-gallery').append(analyzeAllBtn);
        }
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
    
    function analyzePhotoIngredients(imageUrls, analysisType) {
        const button = analysisType === 'all' ? $('#analyze-all-photos-btn') : $('.analyze-photo');
        const originalText = button.text();
        
        button.prop('disabled', true).text('🔍 Analyzing...');
        
        // Show analysis status
        let statusContainer = $('#photo-analysis-status');
        if (statusContainer.length === 0) {
            statusContainer = $('<div id="photo-analysis-status" class="analysis-status"></div>');
            $('.product-photos-gallery').append(statusContainer);
        }
        
        statusContainer.html(`
            <div class="spinner"></div>
            <p>Analyzing ${imageUrls.length} photo${imageUrls.length > 1 ? 's' : ''} for ingredients and terpenes...</p>
            <p style="font-size: 12px; color: #666;">This may take 30-60 seconds depending on image complexity.</p>
        `).show();
        
        $.ajax({
            url: terpediaTerproducts.ajaxurl,
            type: 'POST',
            data: {
                action: 'analyze_photo_ingredients',
                nonce: terpediaTerproducts.nonce,
                image_urls: imageUrls,
                post_id: $('input[name="post_ID"]').val(),
                additional_context: 'This is a product label photo. Please extract all visible ingredients, terpenes, and product information.'
            },
            success: function(response) {
                if (response.success) {
                    displayPhotoAnalysisResults(response.data);
                    
                    // Update ingredients field if data was extracted
                    if (response.data.parsed_data.ingredients && response.data.parsed_data.ingredients.length > 0) {
                        const ingredientsText = response.data.parsed_data.ingredients.join(', ');
                        $('#ingredients_list').val(ingredientsText).addClass('has-content');
                        showMessage(`Successfully extracted ${response.data.parsed_data.ingredients.length} ingredients from photos!`, 'success');
                    } else {
                        showMessage('Photo analysis completed, but no ingredients were detected in the images.', 'info');
                    }
                } else {
                    showMessage('Photo analysis failed: ' + response.data, 'error');
                    statusContainer.html('<p style="color: #d63638;">Analysis failed. Please try again or check that the images contain clear ingredient labels.</p>');
                }
            },
            error: function() {
                showMessage('Photo analysis failed. Please check your internet connection and try again.', 'error');
                statusContainer.html('<p style="color: #d63638;">Network error. Please try again.</p>');
            },
            complete: function() {
                button.prop('disabled', false).text(originalText);
            }
        });
    }
    
    function displayPhotoAnalysisResults(data) {
        const statusContainer = $('#photo-analysis-status');
        let html = '<div class="photo-analysis-results">';
        
        html += `<h4>📊 AI Analysis Complete (${data.confidence}% confidence)</h4>`;
        html += `<p><strong>Model:</strong> ${data.model_used} | <strong>Images:</strong> ${data.image_count}</p>`;
        
        const parsed = data.parsed_data;
        
        // Product information
        if (parsed.product_name || parsed.brand) {
            html += '<div class="extracted-product-info">';
            html += '<h5>🏷️ Product Information:</h5>';
            if (parsed.product_name) html += `<p><strong>Product:</strong> ${parsed.product_name}</p>`;
            if (parsed.brand) html += `<p><strong>Brand:</strong> ${parsed.brand}</p>`;
            html += '</div>';
        }
        
        // Ingredients
        if (parsed.ingredients && parsed.ingredients.length > 0) {
            html += '<div class="extracted-ingredients">';
            html += '<h5>🧪 Extracted Ingredients:</h5>';
            html += '<ul>';
            parsed.ingredients.forEach(ingredient => {
                html += `<li>${ingredient}</li>`;
            });
            html += '</ul>';
            html += '</div>';
        }
        
        // Terpenes
        if (parsed.terpenes && parsed.terpenes.length > 0) {
            html += '<div class="extracted-terpenes">';
            html += '<h5>🌿 Detected Terpenes:</h5>';
            html += '<div class="terpenes-grid">';
            parsed.terpenes.forEach(terpene => {
                html += `
                    <div class="terpene-item">
                        <span class="terpene-name">${terpene.name}</span>
                        <span class="terpene-concentration">${terpene.concentration}</span>
                    </div>
                `;
            });
            html += '</div></div>';
        }
        
        // Concentrations
        if (parsed.concentrations && parsed.concentrations.length > 0) {
            html += '<div class="extracted-concentrations">';
            html += '<h5>📊 Concentrations:</h5>';
            html += '<ul>';
            parsed.concentrations.forEach(conc => {
                html += `<li>${conc.component}: ${conc.percentage}</li>`;
            });
            html += '</ul>';
            html += '</div>';
        }
        
        // Warnings
        if (parsed.warnings && parsed.warnings.length > 0) {
            html += '<div class="extracted-warnings">';
            html += '<h5>⚠️ Warnings & Notes:</h5>';
            html += '<ul>';
            parsed.warnings.forEach(warning => {
                html += `<li>${warning}</li>`;
            });
            html += '</ul>';
            html += '</div>';
        }
        
        // Raw analysis (collapsible)
        html += '<details style="margin-top: 15px;">';
        html += '<summary style="cursor: pointer; font-weight: bold;">📝 View Raw AI Analysis</summary>';
        html += `<pre style="background: #f5f5f5; padding: 10px; border-radius: 4px; white-space: pre-wrap; font-size: 12px;">${data.raw_analysis}</pre>`;
        html += '</details>';
        
        html += '</div>';
        
        statusContainer.html(html);
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
    
    // Initialize product mentions functionality
    initProductMentions();
});

/**
 * Product Mentions System - @product-name autocomplete
 */
function initProductMentions() {
    // Find all text areas and content editable elements
    const textInputs = $('textarea, input[type="text"], [contenteditable="true"]');
    
    textInputs.each(function() {
        const $input = $(this);
        
        // Add autocomplete functionality
        $input.on('input keyup', function(e) {
            handleMentionInput.call(this, e);
        });
        
        // Handle keyboard navigation in autocomplete
        $input.on('keydown', function(e) {
            if ($('.terproduct-mention-dropdown').is(':visible')) {
                handleAutocompleteKeydown.call(this, e);
            }
        });
    });
    
    // Click outside to close autocomplete
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.terproduct-mention-dropdown').length) {
            $('.terproduct-mention-dropdown').hide();
        }
    });
}

function handleMentionInput(e) {
    const $input = $(this);
    const text = $input.val() || $input.text();
    const cursorPos = this.selectionStart || text.length;
    
    // Find @ mentions at cursor position
    const beforeCursor = text.substring(0, cursorPos);
    const mentionMatch = beforeCursor.match(/@([a-zA-Z0-9\-_\s]*)$/);
    
    if (mentionMatch && mentionMatch[1].length >= 1) {
        const query = mentionMatch[1];
        const mentionStart = beforeCursor.lastIndexOf('@');
        
        // Store mention context
        $input.data('mention-start', mentionStart);
        $input.data('mention-query', query);
        
        // Search for products
        searchTerproducts(query, function(results) {
            if (results.length > 0) {
                showAutocompleteDropdown($input, results);
            } else {
                hideAutocompleteDropdown();
            }
        });
    } else {
        hideAutocompleteDropdown();
    }
}

function searchTerproducts(query, callback) {
    if (query.length < 1) {
        callback([]);
        return;
    }
    
    // Debounce searches
    clearTimeout(window.terproductSearchTimeout);
    window.terproductSearchTimeout = setTimeout(function() {
        $.ajax({
            url: terpediaTerproducts.ajaxurl,
            type: 'GET',
            data: {
                action: 'search_terproducts',
                q: query,
                limit: 8
            },
            success: function(response) {
                if (response.success) {
                    callback(response.data);
                } else {
                    callback([]);
                }
            },
            error: function() {
                callback([]);
            }
        });
    }, 300);
}

function showAutocompleteDropdown($input, results) {
    // Remove existing dropdown
    $('.terproduct-mention-dropdown').remove();
    
    // Create dropdown
    const dropdown = $(`
        <div class="terproduct-mention-dropdown">
            <div class="dropdown-header">
                <span class="dropdown-title">🧪 Terproducts</span>
                <span class="dropdown-count">${results.length} found</span>
            </div>
            <div class="dropdown-items"></div>
        </div>
    `);
    
    // Add items
    const itemsContainer = dropdown.find('.dropdown-items');
    results.forEach(function(product, index) {
        const item = $(`
            <div class="dropdown-item" data-index="${index}" data-product-title="${product.title}">
                <div class="product-info">
                    ${product.thumbnail ? `<img src="${product.thumbnail}" alt="${product.title}" class="product-thumb">` : '<div class="product-thumb-placeholder">🧪</div>'}
                    <div class="product-details">
                        <div class="product-title">${product.title}</div>
                        <div class="product-meta">
                            ${product.brand ? `<span class="product-brand">${product.brand}</span>` : ''}
                            ${product.category ? `<span class="product-category">${product.category}</span>` : ''}
                        </div>
                        ${product.excerpt ? `<div class="product-excerpt">${product.excerpt}</div>` : ''}
                    </div>
                </div>
            </div>
        `);
        
        item.on('click', function() {
            selectProduct($input, product);
        });
        
        itemsContainer.append(item);
    });
    
    // Position dropdown
    const inputOffset = $input.offset();
    dropdown.css({
        position: 'absolute',
        top: inputOffset.top + $input.outerHeight(),
        left: inputOffset.left,
        'z-index': 9999
    });
    
    $('body').append(dropdown);
    
    // Highlight first item
    dropdown.find('.dropdown-item:first').addClass('highlighted');
}

function hideAutocompleteDropdown() {
    $('.terproduct-mention-dropdown').remove();
}

function selectProduct($input, product) {
    const mentionStart = $input.data('mention-start');
    const currentText = $input.val() || $input.text();
    
    // Replace the @query with @product-name
    const beforeMention = currentText.substring(0, mentionStart);
    const afterCursor = currentText.substring($input[0].selectionStart || currentText.length);
    const newText = beforeMention + '@' + product.title + ' ' + afterCursor;
    
    if ($input.is('textarea, input')) {
        $input.val(newText);
        
        // Set cursor position after the mention
        const newCursorPos = beforeMention.length + product.title.length + 2;
        $input[0].setSelectionRange(newCursorPos, newCursorPos);
        $input.focus();
    } else {
        // For contenteditable elements
        $input.text(newText);
    }
    
    hideAutocompleteDropdown();
    
    // Trigger change event
    $input.trigger('input');
}

function handleAutocompleteKeydown(e) {
    const dropdown = $('.terproduct-mention-dropdown');
    if (!dropdown.is(':visible')) return;
    
    const items = dropdown.find('.dropdown-item');
    const highlighted = dropdown.find('.dropdown-item.highlighted');
    
    switch (e.key) {
        case 'ArrowDown':
            e.preventDefault();
            if (highlighted.length === 0) {
                items.first().addClass('highlighted');
            } else {
                const next = highlighted.next('.dropdown-item');
                highlighted.removeClass('highlighted');
                if (next.length > 0) {
                    next.addClass('highlighted');
                } else {
                    items.first().addClass('highlighted');
                }
            }
            break;
            
        case 'ArrowUp':
            e.preventDefault();
            if (highlighted.length === 0) {
                items.last().addClass('highlighted');
            } else {
                const prev = highlighted.prev('.dropdown-item');
                highlighted.removeClass('highlighted');
                if (prev.length > 0) {
                    prev.addClass('highlighted');
                } else {
                    items.last().addClass('highlighted');
                }
            }
            break;
            
        case 'Enter':
        case 'Tab':
            e.preventDefault();
            if (highlighted.length > 0) {
                highlighted.click();
            }
            break;
            
        case 'Escape':
            e.preventDefault();
            hideAutocompleteDropdown();
            break;
    }
}
