/**
 * Recipe Balancing and Management JavaScript
 * Handles mass/volume/percentage calculations and recipe copying
 */

jQuery(document).ready(function($) {
    'use strict';
    
    // Recipe Balancing System
    class RecipeBalancer {
        constructor() {
            this.totalVolume = 100;
            this.totalUnit = 'ml';
            this.ingredients = [];
            this.init();
        }
        
        init() {
            this.bindEvents();
            this.updateTotalPercentage();
        }
        
        bindEvents() {
            // Balance controls
            $('#total_volume, #total_unit').on('change', () => this.updateTotalVolume());
            $('#balance-percentages').on('click', () => this.balancePercentages());
            $('#normalize-to-100').on('click', () => this.normalizeTo100());
            
            // Ingredient table events
            $(document).on('input', '.rx-quantity-input, .rx-percentage-input, .rx-unit-select', (e) => {
                this.handleIngredientChange($(e.target).closest('.ingredient-row'));
            });
            
            $(document).on('click', '.rx-calculate-row', (e) => {
                this.calculateRowFromPercentage($(e.target).closest('.ingredient-row'));
            });
            
            $(document).on('click', '.rx-remove-ingredient', (e) => {
                this.removeIngredient($(e.target).closest('.ingredient-row'));
            });
            
            // Add ingredient
            $('#add-ingredient').on('click', () => this.addIngredient());
            
            // Recipe copying
            $('#copy-recipe-btn').on('click', () => this.copyRecipe());
            
            // Version management
            $('#update-quarterly-version').on('click', () => this.updateQuarterlyVersion());
            $('#regenerate-with-ai').on('click', () => this.regenerateWithAI());
        }
        
        updateTotalVolume() {
            this.totalVolume = parseFloat($('#total_volume').val()) || 100;
            this.totalUnit = $('#total_unit').val();
            this.recalculateAllQuantities();
        }
        
        balancePercentages() {
            const rows = $('.ingredient-row');
            const totalPercentage = this.getTotalPercentage();
            
            if (totalPercentage === 0) {
                this.showMessage('No ingredients to balance', 'error');
                return;
            }
            
            rows.each((index, row) => {
                const $row = $(row);
                const currentPercentage = parseFloat($row.find('.rx-percentage-input').val()) || 0;
                const balancedPercentage = (currentPercentage / totalPercentage) * 100;
                
                $row.find('.rx-percentage-input').val(balancedPercentage.toFixed(1));
                this.calculateRowFromPercentage($row);
            });
            
            this.updateTotalPercentage();
            this.showMessage('Percentages balanced to 100%', 'success');
        }
        
        normalizeTo100() {
            const totalPercentage = this.getTotalPercentage();
            
            if (totalPercentage === 0) {
                this.showMessage('No ingredients to normalize', 'error');
                return;
            }
            
            const rows = $('.ingredient-row');
            rows.each((index, row) => {
                const $row = $(row);
                const currentPercentage = parseFloat($row.find('.rx-percentage-input').val()) || 0;
                const normalizedPercentage = (currentPercentage / totalPercentage) * 100;
                
                $row.find('.rx-percentage-input').val(normalizedPercentage.toFixed(1));
                this.calculateRowFromPercentage($row);
            });
            
            this.updateTotalPercentage();
            this.showMessage('Recipe normalized to 100%', 'success');
        }
        
        handleIngredientChange($row) {
            const inputType = $row.find('input:focus').attr('class');
            
            if (inputType && inputType.includes('rx-percentage-input')) {
                this.calculateRowFromPercentage($row);
            } else if (inputType && inputType.includes('rx-quantity-input')) {
                this.calculateRowFromQuantity($row);
            }
            
            this.updateTotalPercentage();
        }
        
        calculateRowFromPercentage($row) {
            const percentage = parseFloat($row.find('.rx-percentage-input').val()) || 0;
            const quantity = (percentage / 100) * this.totalVolume;
            
            $row.find('.rx-quantity-input').val(quantity.toFixed(2));
        }
        
        calculateRowFromQuantity($row) {
            const quantity = parseFloat($row.find('.rx-quantity-input').val()) || 0;
            const percentage = (quantity / this.totalVolume) * 100;
            
            $row.find('.rx-percentage-input').val(percentage.toFixed(1));
        }
        
        getTotalPercentage() {
            let total = 0;
            $('.rx-percentage-input').each(function() {
                total += parseFloat($(this).val()) || 0;
            });
            return total;
        }
        
        updateTotalPercentage() {
            const total = this.getTotalPercentage();
            const $display = $('#total-percentage-display');
            const $message = $('#balance-status-message');
            
            $display.text(`Total: ${total.toFixed(1)}%`);
            
            if (Math.abs(total - 100) < 0.1) {
                $display.removeClass('error warning').addClass('success');
                $message.text('Recipe is balanced').removeClass('error warning').addClass('success');
            } else if (Math.abs(total - 100) < 5) {
                $display.removeClass('error success').addClass('warning');
                $message.text('Recipe is close to balanced').removeClass('error success').addClass('warning');
            } else {
                $display.removeClass('success warning').addClass('error');
                $message.text('Recipe needs balancing').removeClass('success warning').addClass('error');
            }
        }
        
        addIngredient() {
            const index = $('.ingredient-row').length;
            const $container = $('#ingredients-container');
            
            // Create new row HTML
            const newRow = this.createIngredientRow(index);
            $container.append(newRow);
            
            this.updateTotalPercentage();
        }
        
        createIngredientRow(index) {
            return `
                <tr class="ingredient-row" data-index="${index}">
                    <td>
                        <input type="text" class="rx-form-control rx-ingredient-name" name="formulation[${index}][name]" placeholder="e.g., Myrcene, Lavender Oil" />
                    </td>
                    <td>
                        <select class="rx-form-control rx-ingredient-type-select" name="formulation[${index}][type]">
                            <option value="">Select Type</option>
                            <option value="terpene_isolate">Terpene Isolate</option>
                            <option value="essential_oil">Essential Oil</option>
                            <option value="carrier_oil">Carrier Oil</option>
                            <option value="other">Other</option>
                        </select>
                    </td>
                    <td>
                        <input type="number" class="rx-form-control rx-quantity-input" name="formulation[${index}][quantity]" min="0" step="0.01" placeholder="0.00" />
                    </td>
                    <td>
                        <select class="rx-form-control rx-unit-select" name="formulation[${index}][unit]">
                            <option value="ml">ml</option>
                            <option value="g">g</option>
                            <option value="mg">mg</option>
                            <option value="mcg">mcg</option>
                            <option value="drops">drops</option>
                            <option value="tsp">tsp</option>
                            <option value="tbsp">tbsp</option>
                        </select>
                    </td>
                    <td>
                        <input type="number" class="rx-form-control rx-percentage-input" name="formulation[${index}][percentage]" min="0" max="100" step="0.1" placeholder="0.0" />
                    </td>
                    <td>
                        <input type="number" class="rx-form-control rx-density-input" name="formulation[${index}][density]" min="0" step="0.001" placeholder="0.000" />
                        <small class="density-help">g/ml</small>
                    </td>
                    <td>
                        <input type="text" class="rx-form-control rx-notes-input" name="formulation[${index}][notes]" placeholder="Additional notes..." />
                    </td>
                    <td class="rx-advanced-fields">
                        <input type="number" class="rx-form-control rx-cost-input" name="formulation[${index}][cost]" min="0" step="0.01" placeholder="Cost" />
                    </td>
                    <td class="rx-advanced-fields">
                        <input type="text" class="rx-form-control rx-source-input" name="formulation[${index}][source]" placeholder="Source" />
                    </td>
                    <td class="rx-advanced-fields">
                        <input type="number" class="rx-form-control rx-purity-input" name="formulation[${index}][purity]" min="0" max="100" step="0.1" placeholder="Purity %" />
                    </td>
                    <td class="actions">
                        <button type="button" class="rx-remove-ingredient" title="Remove ingredient">×</button>
                        <button type="button" class="rx-calculate-row" title="Calculate from percentage">%</button>
                    </td>
                </tr>
            `;
        }
        
        removeIngredient($row) {
            if (confirm('Are you sure you want to remove this ingredient?')) {
                $row.remove();
                this.updateTotalPercentage();
            }
        }
        
        copyRecipe() {
            const recipeId = $('.terpedia-recipe-copier').data('recipe-id');
            const newTitle = $('#new-recipe-title').val().trim();
            
            if (!newTitle) {
                this.showMessage('Please enter a title for the new recipe', 'error');
                return;
            }
            
            const $status = $('#copy-status');
            $status.html('<p>Copying recipe...</p>');
            
            $.ajax({
                url: terpediaRx.ajaxurl,
                type: 'POST',
                data: {
                    action: 'copy_recipe',
                    nonce: terpediaRx.nonce,
                    original_id: recipeId,
                    new_title: newTitle
                },
                success: function(response) {
                    if (response.success) {
                        $status.html(`
                            <p class="success">Recipe copied successfully!</p>
                            <p><a href="${response.data.edit_url}" class="button button-primary">Edit New Recipe</a></p>
                        `);
                        $('#new-recipe-title').val('');
                    } else {
                        $status.html(`<p class="error">Error: ${response.data}</p>`);
                    }
                },
                error: function() {
                    $status.html('<p class="error">Error copying recipe. Please try again.</p>');
                }
            });
        }
        
        updateQuarterlyVersion() {
            const currentYear = new Date().getFullYear();
            const currentQuarter = Math.ceil((new Date().getMonth() + 1) / 3);
            const quarterlyVersion = `${currentYear}.${currentQuarter}`;
            
            $('#quarterly_version').val(quarterlyVersion);
            this.showMessage(`Updated to quarterly version ${quarterlyVersion}`, 'success');
        }
        
        regenerateWithAI() {
            if (confirm('This will regenerate the formulation using the latest AI knowledge. Continue?')) {
                // This would trigger the AI regeneration
                this.showMessage('AI regeneration initiated...', 'info');
            }
        }
        
        showMessage(message, type = 'info') {
            const $message = $('#balance-status-message');
            $message.text(message).removeClass('error warning success info').addClass(type);
            
            setTimeout(() => {
                $message.text('').removeClass('error warning success info');
            }, 3000);
        }
        
        recalculateAllQuantities() {
            $('.ingredient-row').each((index, row) => {
                this.calculateRowFromPercentage($(row));
            });
        }
    }
    
    // Initialize the recipe balancer
    if ($('#ingredients-table').length) {
        new RecipeBalancer();
    }
    
    // Advanced fields toggle
    $('#show-advanced-fields').on('change', function() {
        if ($(this).is(':checked')) {
            $('.rx-advanced-fields').show();
        } else {
            $('.rx-advanced-fields').hide();
        }
    });
    
    // Initialize advanced fields visibility
    if (!$('#show-advanced-fields').is(':checked')) {
        $('.rx-advanced-fields').hide();
    }
});
