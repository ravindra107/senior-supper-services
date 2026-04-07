/**
 * Senior Supper Services - Admin Script
 */

jQuery(document).ready(function($) {
    
    /**
     * Confirm Delete Actions
     */
    $(document).on('click', '.button-link-delete', function(e) {
        if (!confirm('Are you sure you want to delete this item? This action cannot be undone.')) {
            e.preventDefault();
        }
    });
    
    /**
     * Form Validation
     */
    $('.sss-meals-form, .sss-sides-form, .sss-combos-form').on('submit', function(e) {
        let isValid = true;
        let errorMsg = 'Please fill in all required fields:\n';
        
        $(this).find('[required]').each(function() {
            if (!$(this).val()) {
                isValid = false;
                const label = $(this).closest('.form-group').find('label').text();
                errorMsg += '- ' + label + '\n';
            }
        });
        
        if (!isValid) {
            e.preventDefault();
            alert(errorMsg);
        }
    });
    
    /**
     * Price Formatting
     */
    $('input[type="number"][step="0.01"]').on('blur', function() {
        const value = parseFloat($(this).val());
        if (!isNaN(value)) {
            $(this).val(value.toFixed(2));
        }
    });
    
    /**
     * Toggle Checkboxes
     */
    $(document).on('change', '.toggle-all-checkbox', function() {
        const isChecked = $(this).is(':checked');
        $(this).closest('.form-group').find('input[type="checkbox"]').prop('checked', isChecked);
    });
    
    /**
     * Tabs (if needed for complex admin pages)
     */
    $(document).on('click', '.tab-link', function(e) {
        e.preventDefault();
        const tabName = $(this).data('tab');
        
        // Hide all tabs
        $('.tab-content').hide();
        $('.tab-link').removeClass('active');
        
        // Show selected tab
        $('#' + tabName).show();
        $(this).addClass('active');
    });
    
    /**
     * Settings Auto-save (optional)
     */
    let settingsTimeout;
    $('input, select, textarea').on('change', function() {
        clearTimeout(settingsTimeout);
        settingsTimeout = setTimeout(function() {
            // Could add auto-save here if desired
        }, 1000);
    });
    
    /**
     * Prevent Accidental Navigation
     */
    let hasUnsavedChanges = false;
    $('input, select, textarea').on('change', function() {
        hasUnsavedChanges = true;
    });
    
    $('form').on('submit', function() {
        hasUnsavedChanges = false;
    });
    
    $(window).on('beforeunload', function() {
        if (hasUnsavedChanges) {
            return 'You have unsaved changes.';
        }
    });
    
    /**
     * Date Picker Enhancement
     */
    if ($.datepicker) {
        $('input[type="date"]').datepicker({
            dateFormat: 'yy-mm-dd'
        });
    }
    
    /**
     * Table Search/Filter (optional)
     */
    if ($('.wp-list-table').length) {
        $('<div class="table-search"></div>').insertBefore('.wp-list-table');
        
        $('.table-search').html(
            '<input type="text" id="table-search-input" placeholder="Search table..." style="padding: 10px; width: 200px; margin-bottom: 10px; border: 1px solid #ddd; border-radius: 4px;">'
        );
        
        $('#table-search-input').on('keyup', function() {
            const searchTerm = $(this).val().toLowerCase();
            $('.wp-list-table tbody tr').each(function() {
                const rowText = $(this).text().toLowerCase();
                $(this).toggle(rowText.indexOf(searchTerm) > -1);
            });
        });
    }
    
    /**
     * Copy to Clipboard (for shortcode)
     */
    $(document).on('click', '.copy-shortcode', function(e) {
        e.preventDefault();
        const shortcode = '[sss_order_form]';
        
        // Create textarea
        const $temp = $('<textarea>');
        $('body').append($temp);
        $temp.val(shortcode).select();
        document.execCommand('copy');
        $temp.remove();
        
        // Show confirmation
        const $btn = $(this);
        const originalText = $btn.text();
        $btn.text('Copied!');
        setTimeout(function() {
            $btn.text(originalText);
        }, 2000);
    });
    
    /**
     * Side Field Type Change Handler
     */
    $('#field_type').on('change', function() {
        const selectedType = $(this).val();
        const description = '';
        
        if (selectedType === 'checkbox') {
            // Show checkbox description
        } else if (selectedType === 'radio') {
            // Show radio description
        } else if (selectedType === 'dropdown') {
            // Show dropdown description
        }
    });
    
    /**
     * Modal Handler for View Details
     */
    $(document).on('click', '.view-order-details', function(e) {
        e.preventDefault();
        const orderId = $(this).data('order-id');
        
        $.ajax({
            url: sssAdmin.ajaxurl,
            type: 'POST',
            data: {
                action: 'sss_get_order_details',
                order_id: orderId,
                nonce: sssAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#order-details-content').html(response.data.html);
                    $('#order-details-modal').show();
                    $('#order-details-overlay').show();
                }
            },
            error: function() {
                alert('Error loading order details');
            }
        });
    });
    
    /**
     * Close Modal
     */
    $(document).on('click', '#order-details-overlay', function() {
        $('#order-details-modal').hide();
        $('#order-details-overlay').hide();
    });
    
    /**
     * Status Dropdown Change
     */
    $(document).on('change', '.order-status-select', function() {
        const $form = $(this).closest('form');
        $form.submit();
    });
    
    /**
     * Bulk Actions (if needed)
     */
    $(document).on('click', '.bulk-action-btn', function(e) {
        e.preventDefault();
        
        const selectedItems = [];
        $('input[type="checkbox"].item-checkbox:checked').each(function() {
            selectedItems.push($(this).val());
        });
        
        if (selectedItems.length === 0) {
            alert('Please select at least one item');
            return;
        }
        
        const action = $(this).data('action');
        
        if (confirm('Are you sure?')) {
            // Handle bulk action
            console.log('Bulk action: ' + action, selectedItems);
        }
    });
    
    /**
     * Enable/Disable Multiple Items
     */
    $(document).on('change', '.enable-disable-checkbox', function() {
        const itemId = $(this).data('item-id');
        const isEnabled = $(this).is(':checked');
        
        $.ajax({
            url: sssAdmin.ajaxurl,
            type: 'POST',
            data: {
                action: 'sss_toggle_item',
                item_id: itemId,
                status: isEnabled ? 1 : 0,
                nonce: sssAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    // Show notification
                    console.log('Item updated');
                }
            }
        });
    });
    
    /**
     * Smooth Scrolling
     */
    $('a[href*="#"]').on('click', function(e) {
        const target = $(this.getAttribute('href'));
        if (target.length) {
            e.preventDefault();
            $('html, body').stop().animate({
                scrollTop: target.offset().top - 100
            }, 1000);
        }
    });
    
    /**
     * Export Orders (if button exists)
     */
    $(document).on('click', '#export-orders-btn', function(e) {
        e.preventDefault();
        
        const format = $(this).data('format') || 'csv';
        const url = sssAdmin.ajaxurl + '?action=sss_export_orders&format=' + format + '&nonce=' + sssAdmin.nonce;
        
        window.location.href = url;
    });
    
    /**
     * Tooltips (if using tooltips)
     */
    $(document).tooltip({
        position: {
            my: 'center bottom-20',
            at: 'center top'
        }
    });
    
    /**
     * Real-time Validation
     */
    $('#sss_email').on('blur', function() {
        const email = $(this).val();
        
        if (email && !isValidEmail(email)) {
            $(this).css('border-color', '#dc3545');
            $(this).next('.error-message').remove();
            $(this).after('<span class="error-message" style="color: #dc3545; font-size: 0.9em;">Invalid email</span>');
        } else {
            $(this).css('border-color', '#ddd');
            $(this).next('.error-message').remove();
        }
    });
    
    /**
     * Email Validation Helper
     */
    function isValidEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }
    
    /**
     * Keyboard Shortcuts
     */
    $(document).on('keydown', function(e) {
        // Ctrl+S or Cmd+S to save (prevent default)
        if ((e.ctrlKey || e.metaKey) && e.which === 83) {
            e.preventDefault();
            // Could trigger form save here
        }
    });
    
    /**
     * Loading State
     */
    $(document).on('click', '.submit-btn', function() {
        $(this).prop('disabled', true);
        $(this).text('Processing...');
    });
    
    /**
     * Initialize Datepickers if not already done
     */
    $('input[type="date"]').each(function() {
        if (!$(this).hasClass('date-picker-init')) {
            $(this).addClass('date-picker-init');
            // Additional initialization if needed
        }
    });
});

/**
 * WP Admin Bar Customization
 */
if (typeof wp !== 'undefined' && wp.adminBar) {
    // Add custom admin bar items if needed
}
