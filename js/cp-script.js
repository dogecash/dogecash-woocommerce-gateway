// Prevent conflict with other libraries
jQuery.noConflict();

(function( $ ) {
    $(function() {

        ////////////////////////////////
        // Countdown timer for transaction verification
        ////////////////////////////////
        var cpCount = parseInt($('input[name="cp_order_remaining_time"]').val());
        var cpCounter = setInterval(timer, 1000);

        function formatTime(seconds) {
            var h = Math.floor(seconds / 3600),
                m = Math.floor(seconds / 60) % 60,
                s = seconds % 60;
            if (h < 10) h = "0" + h;
            if (m < 10) m = "0" + m;
            if (s < 10) s = "0" + s;
            return m + ":" + s;
        }

        function timer() {
            cpCount--;
            if (cpCount < 0) {
              return clearInterval(cpCounter);
            }
            $('.cp-counter').html(formatTime(cpCount));
        }


        ////////////////////////////////
        // Copy button action
        ////////////////////////////////
        $(document).on('click', '.cp-copy-btn', function(e){
            var btn = $(this);
            var input = btn.closest('.cp-input-box').find('input');

            input.select();
            document.execCommand("Copy");

            btn.addClass('cp-copied');
            setTimeout( function(){
                btn.removeClass('cp-copied');
            }, 1000);
        });


        ////////////////////////////////
        // Countdown timer for transaction verification
        ////////////////////////////////
        var cp_interval;
        verifyTransaction();

        function verifyTransaction(){
            clearTimeout(cp_interval);
            cp_interval = null;
            var baseurl = window.location.origin;

            $.ajax({
                url: '/wp-admin/admin-ajax.php',
                type: "POST",
                data: {
                    action: "dogec_verify_payment",
                    order_id: $('input[name="cp_order_id"]').val()
                },
                dataType: "json",
                beforeSend: function(){

                },
                success: function(response) {
                    console.log(response);
                    var order_message = $('.cp-payment-msg');
                    var order_info_holder = $('.cp-payment-info-holder');
                    var order_status = $('.cp-payment-info-status');
                    var counter = $('.cp-counter');

                    // Update status message
                    order_status.html(response.message);

                    // Continue with payment verification requests
                    if (response.status == "waiting" || response.status == "detected" || response.status == "failed" || response.status == "expired") {
                        if(response.status == "expired") {
                        order_message.html('The payment time for order has expired! Do not make any payments as they will be invalid! If you have already made a payment within the allowed time, please wait.')

                        var current_time = time();
                        var max_time = Number(response.maxtime) + (5 * 60)
                        if($max_time < $current_time && $max_time !== 300){
                            location.reload()
                        }
                        }

                        if(response.status == "detected") {
                            clearInterval(cpCounter);
                            counter.html('00:00');
                        }

                        cp_interval = setTimeout(function(){
                          verifyTransaction();
                        }, 10000);
                        return false;
                    }
                    if(response.status == "confirmed") {
                        order_info_holder.addClass('cp-' + response.status);

                        clearInterval(cpCounter);
                        counter.html('00:00');

                        setTimeout( function(){
                            location.reload()
                        }, 2000);

                        return false;
                    }
                }
            });
        }
    });
})(jQuery);
