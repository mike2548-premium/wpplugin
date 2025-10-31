/**
 * Client Dashboard Frontend JavaScript
 */

(function($) {
    'use strict';

    const ClientDashboard = {
        init: function() {
            this.bindEvents();
            this.initTooltips();
        },

        bindEvents: function() {
            // Smooth scroll for anchor links
            $('a[href^="#"]').on('click', function(e) {
                const target = $(this.getAttribute('href'));
                if (target.length) {
                    e.preventDefault();
                    $('html, body').stop().animate({
                        scrollTop: target.offset().top - 100
                    }, 600);
                }
            });

            // Auto-hide messages
            $('.cd-message').delay(5000).fadeOut();

            // Confirm delete actions
            $('.cd-confirm-delete').on('click', function(e) {
                if (!confirm(clientDashboard.strings.confirm_delete)) {
                    e.preventDefault();
                    return false;
                }
            });
        },

        initTooltips: function() {
            // Add tooltips to elements with title attribute
            $('[title]').each(function() {
                $(this).attr('data-tooltip', $(this).attr('title'));
                $(this).removeAttr('title');
            });
        },

        showNotification: function(message, type) {
            type = type || 'success';

            const notification = $('<div>')
                .addClass('cd-notification cd-notification-' + type)
                .text(message)
                .appendTo('body')
                .fadeIn();

            setTimeout(function() {
                notification.fadeOut(function() {
                    $(this).remove();
                });
            }, 3000);
        },

        ajaxRequest: function(action, data, callback) {
            data.action = action;
            data.nonce = clientDashboard.nonce;

            $.ajax({
                url: clientDashboard.ajax_url,
                type: 'POST',
                data: data,
                success: function(response) {
                    if (callback && typeof callback === 'function') {
                        callback(response);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', error);
                    ClientDashboard.showNotification(clientDashboard.strings.error, 'error');
                }
            });
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        ClientDashboard.init();
    });

    // Make ClientDashboard available globally
    window.ClientDashboard = ClientDashboard;

})(jQuery);
