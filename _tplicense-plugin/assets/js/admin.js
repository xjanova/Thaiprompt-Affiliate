/**
 * TP License Manager - Admin JavaScript
 */

(function($) {
    'use strict';

    $(document).ready(function() {

        // Add License Modal
        $('#tp-add-license').on('click', function(e) {
            e.preventDefault();
            $('#tp-license-modal').show();
        });

        // Close Modal
        $('.tp-close').on('click', function() {
            $('#tp-license-modal').hide();
        });

        // Generate License Key
        $('#generate-license-key').on('click', function() {
            var licenseKey = generateLicenseKey();
            $('#license_key').val(licenseKey);
        });

        // License Form Submit
        $('#tp-license-form').on('submit', function(e) {
            e.preventDefault();

            var formData = $(this).serialize();

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: formData + '&action=tp_create_license',
                success: function(response) {
                    if (response.success) {
                        alert('License created successfully!');
                        location.reload();
                    } else {
                        alert('Error: ' + response.data);
                    }
                },
                error: function() {
                    alert('An error occurred. Please try again.');
                }
            });
        });

        /**
         * Generate license key
         */
        function generateLicenseKey() {
            var segments = [];
            for (var i = 0; i < 4; i++) {
                segments.push(generateRandomString(4));
            }
            return segments.join('-');
        }

        /**
         * Generate random string
         */
        function generateRandomString(length) {
            var characters = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
            var result = '';
            for (var i = 0; i < length; i++) {
                result += characters.charAt(Math.floor(Math.random() * characters.length));
            }
            return result;
        }
    });

})(jQuery);
