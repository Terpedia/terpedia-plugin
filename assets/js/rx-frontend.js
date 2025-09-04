/**
 * Rx Frontend JavaScript
 * Handles the recipe (Rx) frontend display functionality
 */

(function($) {
    'use strict';

    // Rx Frontend namespace
    window.TerpediaRxFrontend = window.TerpediaRxFrontend || {};

    // Main Rx Frontend class
    TerpediaRxFrontend.RxFrontend = function() {
        this.init();
    };

    TerpediaRxFrontend.RxFrontend.prototype = {
        init: function() {
            this.bindEvents();
            this.initializeComponents();
        },

        bindEvents: function() {
            var self = this;

            // Print functionality
            $(document).on('click', '.rx-print-btn', function(e) {
                e.preventDefault();
                self.printFormulation();
            });

            // Export functionality
            $(document).on('click', '.rx-export-btn', function(e) {
                e.preventDefault();
                self.exportFormulation();
            });

            // Copy to clipboard
            $(document).on('click', '.rx-copy-btn', function(e) {
                e.preventDefault();
                self.copyToClipboard();
            });

            // Responsive table handling
            this.handleResponsiveTable();
        },

        initializeComponents: function() {
            // Add print and export buttons if they don't exist
            this.addActionButtons();
            
            // Initialize tooltips
            $('[data-toggle="tooltip"]').tooltip();
            
            // Initialize any charts or visualizations
            this.initializeCharts();
        },

        addActionButtons: function() {
            var $formulation = $('.rx-formulation');
            if ($formulation.length && !$formulation.find('.rx-actions').length) {
                var $actions = $('<div class="rx-actions"></div>');
                $actions.append('<button class="rx-print-btn button">Print Formulation</button>');
                $actions.append('<button class="rx-export-btn button">Export PDF</button>');
                $actions.append('<button class="rx-copy-btn button">Copy to Clipboard</button>');
                $formulation.prepend($actions);
            }
        },

        printFormulation: function() {
            var $printContent = $('.terpedia-rx-display').clone();
            
            // Remove action buttons from print
            $printContent.find('.rx-actions').remove();
            
            var printWindow = window.open('', '_blank');
            printWindow.document.write(`
                <html>
                    <head>
                        <title>Rx Formulation - ${document.title}</title>
                        <style>
                            body { font-family: Arial, sans-serif; margin: 20px; }
                            .rx-ingredients-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
                            .rx-ingredients-table th, .rx-ingredients-table td { 
                                border: 1px solid #000; padding: 8px; text-align: left; 
                            }
                            .rx-ingredients-table th { background: #f0f0f0; }
                            .type-badge { border: 1px solid #000; padding: 2px 6px; }
                            .rx-formulation-summary { margin: 20px 0; }
                            .summary-item { margin: 10px 0; }
                            @media print { body { margin: 0; } }
                        </style>
                    </head>
                    <body>
                        ${$printContent.html()}
                    </body>
                </html>
            `);
            printWindow.document.close();
            printWindow.print();
        },

        exportFormulation: function() {
            // This would integrate with a PDF generation library
            // For now, we'll just trigger the print dialog
            this.printFormulation();
        },

        copyToClipboard: function() {
            var $table = $('.rx-ingredients-table');
            var text = '';
            
            // Get table headers
            $table.find('thead th').each(function() {
                text += $(this).text() + '\t';
            });
            text += '\n';
            
            // Get table data
            $table.find('tbody tr').each(function() {
                $(this).find('td').each(function() {
                    text += $(this).text().trim() + '\t';
                });
                text += '\n';
            });
            
            // Copy to clipboard
            if (navigator.clipboard) {
                navigator.clipboard.writeText(text).then(function() {
                    alert('Formulation copied to clipboard!');
                }).catch(function() {
                    this.fallbackCopyToClipboard(text);
                });
            } else {
                this.fallbackCopyToClipboard(text);
            }
        },

        fallbackCopyToClipboard: function(text) {
            var textArea = document.createElement('textarea');
            textArea.value = text;
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();
            
            try {
                document.execCommand('copy');
                alert('Formulation copied to clipboard!');
            } catch (err) {
                console.error('Failed to copy: ', err);
                alert('Failed to copy to clipboard');
            }
            
            document.body.removeChild(textArea);
        },

        handleResponsiveTable: function() {
            var $table = $('.rx-ingredients-table');
            
            if ($table.length && window.innerWidth < 768) {
                // Make table scrollable on mobile
                $table.wrap('<div class="table-responsive"></div>');
            }
        },

        initializeCharts: function() {
            // Initialize any charts or visualizations
            // This could include pie charts for ingredient percentages
            // or bar charts for cost analysis
        },

        updateSummary: function() {
            var totalPercentage = 0;
            var totalQuantity = 0;
            var totalCost = 0;
            var ingredientCount = 0;
            
            $('.rx-ingredients-table tbody tr').each(function() {
                var $row = $(this);
                var percentage = parseFloat($row.find('.ingredient-percentage').text()) || 0;
                var quantity = parseFloat($row.find('.ingredient-quantity').text()) || 0;
                var cost = parseFloat($row.find('.ingredient-cost').text()) || 0;
                
                totalPercentage += percentage;
                totalQuantity += quantity;
                totalCost += cost;
                ingredientCount++;
            });
            
            // Update summary display
            $('.summary-item .summary-value').each(function() {
                var $this = $(this);
                var label = $this.siblings('.summary-label').text();
                
                if (label.includes('Ingredients')) {
                    $this.text(ingredientCount);
                } else if (label.includes('Percentage')) {
                    $this.text(totalPercentage.toFixed(1) + '%');
                } else if (label.includes('Quantity')) {
                    $this.text(totalQuantity.toFixed(2) + ' ml');
                } else if (label.includes('Cost')) {
                    $this.text('$' + totalCost.toFixed(2));
                }
            });
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        new TerpediaRxFrontend.RxFrontend();
    });

})(jQuery);
