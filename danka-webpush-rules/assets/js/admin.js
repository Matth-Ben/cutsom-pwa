/* Admin JavaScript for Danka WebPush Rules */
(function($) {
    'use strict';
    
    $(document).ready(function() {
        // Toggle post type settings visibility
        $('.post-type-toggle').on('change', function() {
            const $box = $(this).closest('.post-type-box');
            const $inside = $box.find('.inside');
            
            if ($(this).is(':checked')) {
                $inside.slideDown(200);
            } else {
                $inside.slideUp(200);
            }
        });
        
        // Toggle extra fields based on site type
        $('#site_type').on('change', function() {
            const siteType = $(this).val();
            
            // Hide/show extra fields section
            if (siteType === 'generic') {
                $('#extra-fields-section').slideUp(200);
            } else {
                $('#extra-fields-section').slideDown(200);
            }
            
            // Show/hide specific fields
            $('#ecommerce-fields').hide();
            $('#events-fields').hide();
            
            if (siteType === 'ecommerce') {
                $('#ecommerce-fields').show();
            } else if (siteType === 'events') {
                $('#events-fields').show();
            }
        });
        
        // Click on header to toggle checkbox
        $('.post-type-box .postbox-header').on('click', function(e) {
            // Don't toggle if clicking directly on checkbox or label
            if (!$(e.target).is('input[type="checkbox"]') && !$(e.target).is('label')) {
                const $checkbox = $(this).find('.post-type-toggle');
                $checkbox.prop('checked', !$checkbox.is(':checked')).trigger('change');
            }
        });
    });
    
})(jQuery);
