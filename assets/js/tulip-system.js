
/**
 * TULIP System JavaScript
 * Handles interactive elements for the Terpene Universal Link & Information Protocol
 */

jQuery(document).ready(function($) {
    
    // Initialize TULIP tooltips
    initTulipTooltips();
    
    // Initialize fact cards
    initFactCards();
    
    // Initialize search functionality
    initSearchSystem();
    
    /**
     * Initialize TULIP link tooltips
     */
    function initTulipTooltips() {
        $(document).on('mouseenter', '.tulip-link', function() {
            const factId = $(this).data('fact-id');
            const title = $(this).attr('title');
            
            if (!$(this).hasClass('tooltip-initialized')) {
                showTulipTooltip($(this), factId, title);
                $(this).addClass('tooltip-initialized');
            }
        });
        
        $(document).on('click', '.tulip-link', function(e) {
            e.preventDefault();
            const factId = $(this).data('fact-id');
            openFactModal(factId);
        });
    }
    
    /**
     * Show TULIP tooltip
     */
    function showTulipTooltip($element, factId, title) {
        $element.attr('title', '');
        
        const tooltip = $('<div class="tulip-tooltip">')
            .html(`
                <div class="tulip-tooltip-header">
                    <strong>${factId}</strong>
                    <span class="tulip-tooltip-type">TULIP Fact</span>
                </div>
                <div class="tulip-tooltip-content">${title}</div>
                <div class="tulip-tooltip-footer">Click for details</div>
            `)
            .css({
                position: 'absolute',
                background: '#333',
                color: '#fff',
                padding: '8px 12px',
                borderRadius: '4px',
                fontSize: '12px',
                zIndex: 1000,
                maxWidth: '250px',
                boxShadow: '0 2px 8px rgba(0,0,0,0.3)',
                display: 'none'
            });
        
        $('body').append(tooltip);
        
        $element.on('mouseenter', function(e) {
            const offset = $(this).offset();
            tooltip.css({
                top: offset.top - tooltip.outerHeight() - 8,
                left: offset.left + ($(this).outerWidth() / 2) - (tooltip.outerWidth() / 2)
            }).fadeIn(200);
        });
        
        $element.on('mouseleave', function() {
            tooltip.fadeOut(200);
        });
    }
    
    /**
     * Open fact modal
     */
    function openFactModal(factId) {
        $.post(tulip_ajax.ajax_url, {
            action: 'tulip_get_fact_details',
            fact_id: factId,
            nonce: tulip_ajax.nonce
        }, function(response) {
            if (response.success) {
                showFactModal(response.data);
            } else {
                alert('Error loading fact details');
            }
        });
    }
    
    /**
     * Show fact details modal
     */
    function showFactModal(fact) {
        const modal = $(`
            <div class="tulip-modal-overlay">
                <div class="tulip-modal">
                    <div class="tulip-modal-header">
                        <h3>${fact.fact_id}: ${fact.title}</h3>
                        <button class="tulip-modal-close">&times;</button>
                    </div>
                    <div class="tulip-modal-content">
                        <div class="tulip-fact-meta">
                            <span class="tulip-meta-item">
                                <strong>Terpene:</strong> ${fact.terpene_name || 'General'}
                            </span>
                            <span class="tulip-meta-item">
                                <strong>Category:</strong> ${fact.category || 'Uncategorized'}
                            </span>
                            <span class="tulip-meta-item">
                                <strong>Evidence Level:</strong> 
                                <span class="tulip-evidence-level ${fact.evidence_level}">
                                    ${fact.evidence_level.replace('_', ' ').toUpperCase()}
                                </span>
                            </span>
                        </div>
                        <div class="tulip-statement">
                            <h4>Statement:</h4>
                            <p>${fact.statement}</p>
                        </div>
                        ${fact.citations ? `
                            <div class="tulip-citations">
                                <h4>Citations:</h4>
                                <ul>
                                    ${JSON.parse(fact.citations).map(citation => `<li>${citation}</li>`).join('')}
                                </ul>
                            </div>
                        ` : ''}
                        <div class="tulip-actions">
                            <button class="button" onclick="copyFactLink('${fact.fact_id}')">Copy Link</button>
                            <button class="button" onclick="citeFactText('${fact.fact_id}')">Copy Citation</button>
                        </div>
                    </div>
                </div>
            </div>
        `);
        
        $('body').append(modal);
        modal.fadeIn(200);
        
        // Close modal handlers
        modal.find('.tulip-modal-close, .tulip-modal-overlay').on('click', function(e) {
            if (e.target === this) {
                modal.fadeOut(200, function() {
                    $(this).remove();
                });
            }
        });
        
        // Prevent modal content clicks from closing
        modal.find('.tulip-modal').on('click', function(e) {
            e.stopPropagation();
        });
    }
    
    /**
     * Initialize fact cards
     */
    function initFactCards() {
        $('.tulip-fact-card').each(function() {
            const $card = $(this);
            const factId = $card.data('fact-id');
            
            $card.on('click', function() {
                openFactModal(factId);
            });
            
            $card.css('cursor', 'pointer');
        });
    }
    
    /**
     * Initialize search system
     */
    function initSearchSystem() {
        let searchTimeout;
        
        $(document).on('input', '#tulip-search-input', function() {
            const query = $(this).val();
            const $results = $('#tulip-search-results');
            
            clearTimeout(searchTimeout);
            
            if (query.length < 3) {
                $results.empty();
                return;
            }
            
            $results.html('<div class="tulip-searching">Searching TULIP database...</div>');
            
            searchTimeout = setTimeout(function() {
                $.post(tulip_ajax.ajax_url, {
                    action: 'tulip_search_facts',
                    query: query,
                    nonce: tulip_ajax.nonce
                }, function(response) {
                    if (response.success) {
                        $results.html(response.data.html);
                        
                        if (response.data.count === 0) {
                            $results.html('<div class="tulip-no-results">No facts found matching your search.</div>');
                        }
                    } else {
                        $results.html('<div class="tulip-error">Error searching facts.</div>');
                    }
                });
            }, 300);
        });
        
        // Make search results clickable
        $(document).on('click', '.tulip-search-result', function() {
            const factId = $(this).data('fact-id');
            if (factId) {
                openFactModal(factId);
            }
        });
    }
    
    /**
     * Copy fact link to clipboard
     */
    window.copyFactLink = function(factId) {
        const link = `[tulip_fact id="${factId}"]`;
        navigator.clipboard.writeText(link).then(function() {
            alert('Fact shortcode copied to clipboard!');
        });
    };
    
    /**
     * Copy citation text
     */
    window.citeFactText = function(factId) {
        const citation = `${factId} - Terpedia TULIP Database`;
        navigator.clipboard.writeText(citation).then(function() {
            alert('Citation copied to clipboard!');
        });
    };
    
    // Add modal styles
    $('<style>').text(`
        .tulip-modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.6);
            z-index: 10000;
            display: none;
        }
        
        .tulip-modal {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
            max-width: 600px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
        }
        
        .tulip-modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 20px;
            border-bottom: 1px solid #eee;
            background: #f8f9fa;
            border-radius: 8px 8px 0 0;
        }
        
        .tulip-modal-header h3 {
            margin: 0;
            color: #333;
            font-size: 18px;
        }
        
        .tulip-modal-close {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #999;
            padding: 0;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .tulip-modal-close:hover {
            color: #333;
        }
        
        .tulip-modal-content {
            padding: 20px;
        }
        
        .tulip-fact-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 16px;
            margin-bottom: 16px;
            padding-bottom: 16px;
            border-bottom: 1px solid #eee;
        }
        
        .tulip-meta-item {
            font-size: 14px;
        }
        
        .tulip-statement h4,
        .tulip-citations h4 {
            color: #333;
            margin: 16px 0 8px 0;
        }
        
        .tulip-statement p {
            line-height: 1.6;
            color: #555;
        }
        
        .tulip-actions {
            margin-top: 20px;
            padding-top: 16px;
            border-top: 1px solid #eee;
            display: flex;
            gap: 12px;
        }
        
        .tulip-searching,
        .tulip-no-results,
        .tulip-error {
            padding: 12px;
            text-align: center;
            color: #666;
            font-style: italic;
        }
        
        .tulip-tooltip {
            pointer-events: none;
        }
        
        .tulip-tooltip-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 4px;
            font-size: 11px;
        }
        
        .tulip-tooltip-type {
            opacity: 0.8;
        }
        
        .tulip-tooltip-footer {
            margin-top: 4px;
            font-size: 10px;
            opacity: 0.8;
        }
    `).appendTo('head');
});
