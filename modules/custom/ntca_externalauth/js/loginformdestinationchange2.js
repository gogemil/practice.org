/**
 * @file
 * Gets the current path, puts it into the destination of the login form
 */

(function ($, Drupal, drupalSettings) {

    'use strict';

    // when the login form pops up, its destination will need to be found and filled out, too, hence the interval
    $(document).ready(function() {
        var sFullPath = document.location.pathname+document.location.search;
        setInterval(function() {
            $('input[name=destination]').val(sFullPath);
        }, 500);
    });

})(jQuery, Drupal, drupalSettings);

/**
 * Check cookies that tell us if we're in the right status
 */
(function ($, Drupal, drupalSettings) {

    'use strict';

    function isLoggedInExternally() {
        // are we logged in externally?
        var sessionID = Cookies.get('ssisid');
        var customerID = Cookies.get('p_cust_id');

        var bSessionIDEmpty = sessionID == '' || sessionID == null || sessionID == undefined;
        var bCustomerIDEmpty = customerID == '' || customerID == null || customerID == undefined;
        if (bSessionIDEmpty || bCustomerIDEmpty) {
            return(false);
        }
        return(true);
    }

    function isLoggedInLocallyWithExtAccount() {
        var sLoggedInLocallyWithExtAcct = Cookies.get('localextlogin');
        // console.log(sLoggedInLocallyWithExtAcct);
        return (sLoggedInLocallyWithExtAcct == 'yes');
    }

    function pageRefreshWithCountdown(sMessage, sUrl) {
        if (sMessage == '') {
            sMessage = 'This site has detected that you have logged in already.';
        }
        if (sUrl == '') {
            sUrl = window.location.href;
        }

        // NTCAON-92 - colorbox is clipping the text in some cases
        $.colorbox({html: "<p style='padding-bottom: 20px; margin-bottom: 20px;'>"+sMessage+" <a href=''>Reload the page</a> or wait <span id='refresh_countdown'>5</span>...</p>"});
        var countdownInterval = setInterval(function() {
            var iCount = parseInt($('#refresh_countdown').html());
            if (iCount == 0) {
                clearInterval(countdownInterval);
                // jump to the same page
                window.location = sUrl;
            } else {
                $('#refresh_countdown').html((iCount-1));
            }
        }, 1000);
    }

    // when the login form pops up, its destination will need to be found and filled out, too, hence the interval
    $(document).ready(function() {

        var bLoggedInLocally = $('body').hasClass('user-logged-in');

        if (isLoggedInExternally() && !bLoggedInLocally) {
//            console.log('Yeah, trying to log in');
            // log in locally, explicitly
            $.ajax({
                url: '/ntca_externalauth/loginwithtoken',
                data: {
                    ssisid: Cookies.get('ssisid'),
                    p_cust_id: Cookies.get('p_cust_id')
                },
                dataType: 'json',
                success: function(data, textStatus, jqXHR) {
                    if (data['bLoginSuccess']) {
//                        console.log('Ext. login successful.');
                        // now pop up a box, saying ('we've logged in, please refresh page') or something
                        var sMessage = 'This site has detected that you have logged in already.';
                        pageRefreshWithCountdown(sMessage, '');
                    } else {
//                        console.log('Failed ext. login');
//                        if (data.sMessage) {
//                            console.log(data.sMessage);
//                        }
                    }
                }
            });
        } else {

            if (!isLoggedInExternally() && isLoggedInLocallyWithExtAccount()) {

                // just log them out
                var sMessage = 'You were logged out at an external site.';
                pageRefreshWithCountdown(sMessage, '/user/logout');

            }
        }

    });

})(jQuery, Drupal, drupalSettings);
