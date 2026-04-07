jQuery(document).ready(function($) {
    const form = $('#sss-order-form');
    
    // Show/hide special instructions based on side selection
    $(document).on('change', '.side-checkbox, .side-dropdown, input[type="radio"][name*="sides"]', function() {
        const sideItem = $(this).closest('.side-item');
        const instructionsField = sideItem.find('.special-instructions-field');
        
        if ($(this).is(':checked') || ($(this).is('select') && $(this).val())) {
            instructionsField.slideDown();
        } else if ($(this).attr('type') === 'checkbox') {
            instructionsField.slideUp();
        }
    });
    
    // Calculate and update price summary
    $(document).on('change', 'select, input[type="checkbox"], input[type="radio"]', function() {
        updatePricingSummary();
    });
    
    // Form submission
    form.on('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const data = {
            action: 'sss_submit_form',
            nonce: $('input[name="sss_nonce"]').val(),
            name: $('input[name="name"]').val(),
            email: $('input[name="email"]').val(),
            phone: $('input[name="phone"]').val(),
            address: $('textarea[name="address"]').val()
        };
        
        // Collect day data
        const days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        days.forEach(day => {
            const dayKey = day.toLowerCase();
            const dayData = {};
            
            // Meals
            const meal1 = $(`select[name="${dayKey}[meal_1]"]`).val();
            const meal2 = $(`select[name="${dayKey}[meal_2]"]`).val();
            const quantity = $(`select[name="${dayKey}[quantity]"]`).val();
            
            if (meal1) {
                dayData.meal_1 = meal1;
                dayData.meal_2 = meal2 || null;
                dayData.quantity = quantity || 1;
                dayData.sides = {};
                dayData.instructions = {};
                
                // Sides
                $(`.day-section[data-day="${day}"] .side-item`).each(function() {
                    const sideId = $(this).data('side-id');
                    const checkbox = $(this).find('.side-checkbox:checked').val();
                    const radio = $(this).find('input[type="radio"]:checked').val();
                    const dropdown = $(this).find('.side-dropdown').val();
                    
                    if (checkbox || (radio && radio !== 'no') || dropdown) {
                        dayData.sides[sideId] = checkbox || radio || dropdown;
                        
                        // Get special instructions if any
                        const instructions = $(this).find('textarea').val();
                        if (instructions) {
                            dayData.instructions[sideId] = instructions;
                        }
                    }
                });
                
                // Special instructions for the day
                dayData.special_instructions = $(`textarea[name="${dayKey}[special_instructions]"]`).val() || '';
                
                data[dayKey] = dayData;
            }
        });
        
        // Submit via AJAX
        $.ajax({
            url: sssData.ajaxurl,
            type: 'POST',
            data: data,
            success: function(response) {
                if (response.success) {
                    $('#sss-order-form').hide();
                    $('#sss-success-message').slideDown();
                    
                    // Reset form after 2 seconds
                    setTimeout(function() {
                        form[0].reset();
                        $('#sss-order-form').slideDown();
                        $('#sss-success-message').slideUp();
                    }, 3000);
                } else {
                    showError(response.data.message);
                }
            },
            error: function() {
                showError('An error occurred. Please try again.');
            }
        });
    });
    
    /**
     * Update Pricing Summary
     */
    function updatePricingSummary() {
        const email = $('input[name="email"]').val();
        const days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        let summary = '<table class="pricing-table">';
        let grandTotal = 0;
        
        days.forEach(day => {
            const dayKey = day.toLowerCase();
            const meal1 = $(`select[name="${dayKey}[meal_1]"]`).val();
            
            if (!meal1) return;
            
            const quantity = $(`select[name="${dayKey}[quantity]"]`).val() || 1;
            let dayTotal = 0;
            let items = [];
            
            // Get meal price
            $.ajax({
                url: sssData.ajaxurl,
                type: 'POST',
                data: {
                    action: 'sss_get_meal_price',
                    meal_id: meal1,
                    email: email,
                    nonce: sssData.nonce
                },
                async: false,
                success: function(response) {
                    if (response.success) {
                        const mealPrice = parseFloat(response.data.price);
                        const mealTotal = mealPrice * quantity;
                        dayTotal += mealTotal;
                        items.push(`Meal 1: $${mealPrice.toFixed(2)} × ${quantity} = $${mealTotal.toFixed(2)}`);
                    }
                }
            });
            
            // Get sides
            $(`.day-section[data-day="${day}"] .side-item`).each(function() {
                const checkbox = $(this).find('.side-checkbox:checked').val();
                const radio = $(this).find('input[type="radio"]:checked').val();
                const dropdown = $(this).find('.side-dropdown').val();
                const sideName = $(this).find('label:first').text();
                
                if (checkbox || (radio && radio !== 'no') || dropdown) {
                    const sidePrice = parseFloat($(this).find('.side-checkbox, input[type="radio"], .side-dropdown').data('price') || 0);
                    const sideTotal = sidePrice * quantity;
                    dayTotal += sideTotal;
                    if (sidePrice > 0) {
                        items.push(`${sideName.trim()}: $${sidePrice.toFixed(2)} × ${quantity} = $${sideTotal.toFixed(2)}`);
                    }
                }
            });
            
            if (items.length > 0) {
                summary += `<tr><td colspan="2"><strong>${day}:</strong></td></tr>`;
                items.forEach(item => {
                    summary += `<tr><td colspan="2">${item}</td></tr>`;
                });
                summary += `<tr class="day-total"><td colspan="2"><strong>Day Total: $${dayTotal.toFixed(2)}</strong></td></tr>`;
                grandTotal += dayTotal;
            }
        });
        
        summary += '<tr class="grand-total"><td colspan="2"><strong>Grand Total: $' + grandTotal.toFixed(2) + '</strong></td></tr>';
        summary += '</table>';
        
        $('#sss-pricing-summary').html(summary);
    }
    
    /**
     * Show Error Message
     */
    function showError(message) {
        $('#sss-error-text').text(message);
        $('#sss-error-message').slideDown();
        
        setTimeout(function() {
            $('#sss-error-message').slideUp();
        }, 5000);
    }
});
