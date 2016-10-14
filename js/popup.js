/**
 * @file
 * jQuery code.
 * Based on code: Adrian "yEnS" Mato Gondelle, twitter: @adrianmg
 * Modifications for Drupal: Grzegorz Bartman grzegorz.bartman@openbit.pl, Valdeci Gomes valdeci.gomes@mirumagency.com
 */

(function ($, Drupal, drupalSettings) {
// Setting up popup.
// 0 means disabled; 1 means enabled.
    var popupStatus = 0;

    /**
     * Loading popup with jQuery.
     */
    function popup_message_load_popup() {
        // Loads json with modal settings
        $.getJSON('/popup_message/status?popup_path=' + window.location.pathname, function (json) {
            if (json.status == 1) {
                jQuery("#popup-message-background").css({
                    "opacity": "0.7"
                });
                jQuery("#popup-message-background").fadeIn("slow");
                jQuery("#popup-message-window").fadeIn("slow");
                popupStatus = 1;

                // Set cookie
                var timestamp = (+new Date());
                jQuery.cookie("popup_message_displayed", timestamp, {popup_path: '/'});
            }
        });
    }

    /**
     * Disabling popup with jQuery.
     */
    function popup_message_disable_popup() {
        // Disables popup only if it is enabled.
        if (popupStatus == 1) {
            jQuery("#popup-message-background").fadeOut("slow");
            jQuery("#popup-message-window").fadeOut("slow");
            jQuery('#popup-message-content').empty().remove();
            popupStatus = 0;
        }
    }

    /**
     * Centering popup.
     */
    function popup_message_center_popup(width, height) {
        // Request data for centering.
        var windowWidth = document.documentElement.clientWidth;
        var windowHeight = document.documentElement.clientHeight;

        var popupWidth = 0;
        if (typeof width == "undefined") {
            popupWidth = $("#popup-message-window").width();
        }
        else {
            popupWidth = width;
        }
        var popupHeight = 0;
        if (typeof width == "undefined") {
            popupHeight = $("#popup-message-window").height();
        }
        else {
            popupHeight = height;
        }

        // Centering.
        jQuery("#popup-message-window").css({
            "position": "absolute",
            "width": "100%",
            "height": "auto"
        });
        // Only need force for IE6.
        jQuery("#popup-message-background").css({
            "height": windowHeight
        });

    }

    /** 
     * Display popup message.
     */
    function popup_message_display_popup(popup_message_body, url, target) {
        jQuery('body').append("<div id='popup-message-window'><a target="+ target +" href="+ url +"><div id='popup-message-content'>" + popup_message_body + "</div><div class='bottomRightButtonText'>&gt; Enter Site</div></a></div><div id='popup-message-background'></div>");

        // Loading popup.
        popup_message_center_popup(640, 640);
        popup_message_load_popup();

        // Closing popup.
    
        // Click out event!
        jQuery("#popup-message-background").click(function () {
            popup_message_disable_popup();
        });
        // Press Escape event!
        jQuery(document).keypress(function (e) {
            if (e.keyCode == 27 && popupStatus == 1) {
                popup_message_disable_popup();
            }
        });
    }

    /**
     * Helper function for get last element from object.
     * Used if on page is loaded more than one message.
     */
    function popup_message_get_last_object_item(variable_data) {
        if (typeof(variable_data) == 'object') {
            variable_data = variable_data[(variable_data.length - 1)];
        }
        return variable_data;
    }

    Drupal.behaviors.popupMessage = {
        attach: function () {
            var timestamp = (+new Date());
            var check_cookie = drupalSettings.popupMessage.check_cookie;
            check_cookie = popup_message_get_last_object_item(check_cookie);
            var popup_message_cookie = jQuery.cookie("popup_message_displayed"),
                delay = drupalSettings.popupMessage.delay * 1000,

                show_popup = true;
            if (!popup_message_cookie || check_cookie === 0) {
                show_popup = true;
            }
            else {
                popup_message_cookie = parseInt(popup_message_cookie, 10);
                show_popup = timestamp < popup_message_cookie + delay;
            }
            if (show_popup) {
                var run_popup = function () {
                    // Get variables.
                    var popup_message_body = drupalSettings.popupMessage.body,
                        splash_link = drupalSettings.popupMessage.url,
                        target = drupalSettings.popupMessage.target;
                    popup_message_body = popup_message_get_last_object_item(popup_message_body);
                    splash_link = popup_message_get_last_object_item(splash_link);
                    target = popup_message_get_last_object_item(target);

                    popup_message_display_popup(
                        popup_message_body, splash_link, target);
                };

                var trigger_time = (!popup_message_cookie) ? (popup_message_cookie + delay - timestamp) : delay;

                setTimeout(run_popup, trigger_time);
            }
        }
    };
})(jQuery, Drupal, drupalSettings);
