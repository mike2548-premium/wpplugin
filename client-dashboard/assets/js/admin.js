/**
 * Client Dashboard Admin JavaScript
 */

(function($) {
    'use strict';

    const ClientDashboardAdmin = {
        init: function() {
            this.bindEvents();
        },

        bindEvents: function() {
            // Settings form validation
            $('#client_dashboard_settings_form').on('submit', function(e) {
                // Add any validation logic here if needed
            });

            // Feature toggles
            $('.cd-feature-toggle').on('change', function() {
                const feature = $(this).data('feature');
                const enabled = $(this).is(':checked');

                console.log('Feature ' + feature + ' is now ' + (enabled ? 'enabled' : 'disabled'));
            });
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        ClientDashboardAdmin.init();
    });

})(jQuery);
