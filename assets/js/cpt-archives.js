/**
 * Terpedia CPT Archives JavaScript
 * Interactive functionality for archive pages
 */

jQuery(document).ready(function($) {
    
    // Initialize archive functionality
    initArchiveFilters();
    initSearchFunctionality();
    initLoadMore();
    
    /**
     * Initialize archive filters
     */
    function initArchiveFilters() {
        // Category filter for terproducts
        $('#terproduct-category-filter').on('change', function() {
            const selectedCategory = $(this).val();
            const currentUrl = new URL(window.location.href);
            
            if (selectedCategory) {
                currentUrl.searchParams.set('terproduct_category', selectedCategory);
            } else {
                currentUrl.searchParams.delete('terproduct_category');
            }
            
            // Remove pagination when filtering
            currentUrl.searchParams.delete('paged');
            
            window.location.href = currentUrl.toString();
        });
        
        // Real-time search filtering
        $('#archive-search').on('input', debounce(function() {
            const searchTerm = $(this).val().toLowerCase();
            filterArchiveItems(searchTerm);
        }, 300));
    }
    
    /**
     * Filter archive items by search term
     */
    function filterArchiveItems(searchTerm) {
        const $items = $('.product-card, .podcast-card, .rx-card, .newsletter-card');
        
        $items.each(function() {
            const $item = $(this);
            const title = $item.find('h3 a').text().toLowerCase();
            const excerpt = $item.find('.product-excerpt, .podcast-excerpt, .rx-excerpt, .newsletter-excerpt').text().toLowerCase();
            
            if (title.includes(searchTerm) || excerpt.includes(searchTerm)) {
                $item.show();
            } else {
                $item.hide();
            }
        });
        
        // Show/hide empty state
        const visibleItems = $items.filter(':visible').length;
        if (visibleItems === 0 && searchTerm.length > 0) {
            showEmptySearchState(searchTerm);
        } else {
            hideEmptySearchState();
        }
    }
    
    /**
     * Show empty search state
     */
    function showEmptySearchState(searchTerm) {
        if ($('.search-empty-state').length === 0) {
            const emptyState = `
                <div class="search-empty-state" style="text-align: center; padding: 40px; color: #666;">
                    <h3>No results found for "${searchTerm}"</h3>
                    <p>Try adjusting your search terms or browse all items.</p>
                    <button class="clear-search button">Clear Search</button>
                </div>
            `;
            $('.products-grid, .podcasts-list, .rx-grid, .newsletters-list').after(emptyState);
        }
    }
    
    /**
     * Hide empty search state
     */
    function hideEmptySearchState() {
        $('.search-empty-state').remove();
    }
    
    /**
     * Clear search functionality
     */
    $(document).on('click', '.clear-search', function() {
        $('#archive-search').val('');
        $('.product-card, .podcast-card, .rx-card, .newsletter-card').show();
        hideEmptySearchState();
    });
    
    /**
     * Initialize search functionality
     */
    function initSearchFunctionality() {
        // Add search box to archive headers if not exists
        if ($('.archive-search-box').length === 0) {
            const searchBox = `
                <div class="archive-search-box" style="margin: 20px 0;">
                    <input type="text" id="archive-search" placeholder="Search..." 
                           style="width: 100%; max-width: 400px; padding: 10px; border: 2px solid #e1e5e9; border-radius: 8px; font-size: 16px;">
                </div>
            `;
            $('.archive-filters').append(searchBox);
        }
    }
    
    /**
     * Initialize load more functionality
     */
    function initLoadMore() {
        // Convert pagination to load more on mobile
        if ($(window).width() <= 768) {
            const $pagination = $('.archive-pagination');
            const nextLink = $pagination.find('.next').attr('href');
            
            if (nextLink) {
                $pagination.html(`
                    <button class="load-more-btn" data-url="${nextLink}" 
                            style="background: #667eea; color: white; border: none; padding: 15px 30px; border-radius: 8px; font-size: 16px; cursor: pointer;">
                        Load More
                    </button>
                `);
            }
        }
    }
    
    /**
     * Handle load more button
     */
    $(document).on('click', '.load-more-btn', function() {
        const $button = $(this);
        const nextUrl = $button.data('url');
        
        if (!nextUrl) return;
        
        $button.text('Loading...').prop('disabled', true);
        
        $.get(nextUrl)
            .done(function(data) {
                const $newContent = $(data);
                const $newItems = $newContent.find('.product-card, .podcast-card, .rx-card, .newsletter-card');
                
                // Append new items
                if ($('.products-grid').length) {
                    $('.products-grid').append($newItems);
                } else if ($('.podcasts-list').length) {
                    $('.podcasts-list').append($newItems);
                } else if ($('.rx-grid').length) {
                    $('.rx-grid').append($newItems);
                } else if ($('.newsletters-list').length) {
                    $('.newsletters-list').append($newItems);
                }
                
                // Update load more button
                const nextNextLink = $newContent.find('.next').attr('href');
                if (nextNextLink) {
                    $button.data('url', nextNextLink).text('Load More').prop('disabled', false);
                } else {
                    $button.text('No More Items').prop('disabled', true);
                }
                
                // Animate new items
                $newItems.hide().fadeIn(600);
            })
            .fail(function() {
                $button.text('Error Loading').prop('disabled', true);
            });
    });
    
    /**
     * Archive navigation highlighting
     */
    function highlightCurrentArchive() {
        const currentPath = window.location.pathname;
        $('.nav-links a').each(function() {
            const $link = $(this);
            const linkPath = new URL($link.attr('href')).pathname;
            
            if (currentPath.includes(linkPath) && linkPath !== '/cpt-archives/') {
                $link.addClass('current-archive');
            }
        });
    }
    
    // Initialize navigation highlighting
    highlightCurrentArchive();
    
    /**
     * Smooth scroll for archive navigation
     */
    $('.nav-links a').on('click', function(e) {
        if ($(this).attr('href').startsWith('#')) {
            e.preventDefault();
            const target = $($(this).attr('href'));
            if (target.length) {
                $('html, body').animate({
                    scrollTop: target.offset().top - 100
                }, 600);
            }
        }
    });
    
    /**
     * Card hover effects
     */
    $('.cpt-card, .product-card, .podcast-card, .rx-card, .newsletter-card').hover(
        function() {
            $(this).find('.cpt-icon, .product-image').css('transform', 'scale(1.05)');
        },
        function() {
            $(this).find('.cpt-icon, .product-image').css('transform', 'scale(1)');
        }
    );
    
    /**
     * Lazy loading for images
     */
    if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    img.classList.remove('lazy');
                    imageObserver.unobserve(img);
                }
            });
        });
        
        document.querySelectorAll('img[data-src]').forEach(img => {
            imageObserver.observe(img);
        });
    }
    
    /**
     * Stats counter animation for hub page
     */
    function animateCounters() {
        $('.cpt-stats').each(function() {
            const $counter = $(this);
            const countText = $counter.text();
            const countMatch = countText.match(/(\d+)/);
            
            if (countMatch) {
                const finalCount = parseInt(countMatch[1]);
                const $countSpan = $('<span>').text('0');
                $counter.html($countSpan.get(0).outerHTML + countText.replace(/\d+/, ''));
                
                const countOptions = {
                    startVal: 0,
                    endVal: finalCount,
                    duration: 2000,
                    useEasing: true,
                    useGrouping: true,
                    separator: ',',
                    decimal: '.'
                };
                
                // Simple counter animation
                animateValue($counter.find('span').get(0), 0, finalCount, 2000);
            }
        });
    }
    
    /**
     * Animate number values
     */
    function animateValue(element, start, end, duration) {
        const startTimestamp = Date.now();
        const step = (timestamp) => {
            const elapsed = timestamp - startTimestamp;
            const progress = Math.min(elapsed / duration, 1);
            const current = Math.floor(progress * (end - start) + start);
            element.textContent = current.toLocaleString();
            if (progress < 1) {
                requestAnimationFrame(step);
            }
        };
        requestAnimationFrame(step);
    }
    
    // Initialize counter animation on hub page
    if ($('.terpedia-cpt-hub').length) {
        setTimeout(animateCounters, 500);
    }
    
    /**
     * Keyboard navigation
     */
    $(document).on('keydown', function(e) {
        if (e.ctrlKey || e.metaKey) {
            switch(e.key) {
                case 'k': // Ctrl/Cmd + K for search
                    e.preventDefault();
                    $('#archive-search').focus();
                    break;
                case 'h': // Ctrl/Cmd + H for hub
                    e.preventDefault();
                    window.location.href = '/cpt-archives/';
                    break;
            }
        }
    });
    
    /**
     * Utility: Debounce function
     */
    function debounce(func, wait, immediate) {
        let timeout;
        return function executedFunction() {
            const context = this;
            const args = arguments;
            const later = function() {
                timeout = null;
                if (!immediate) func.apply(context, args);
            };
            const callNow = immediate && !timeout;
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
            if (callNow) func.apply(context, args);
        };
    }
    
    // Add keyboard shortcuts info
    if ($('.archive-header').length) {
        $('.archive-header').append(`
            <div class="keyboard-shortcuts" style="margin-top: 15px; font-size: 12px; color: #666; text-align: center;">
                <span title="Keyboard Shortcuts">⌘K Search • ⌘H Hub</span>
            </div>
        `);
    }
});