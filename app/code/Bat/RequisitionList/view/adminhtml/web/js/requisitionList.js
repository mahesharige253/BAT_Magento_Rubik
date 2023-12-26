require(
    [
        'jquery',
        'mage/translate',
    ],
    function ($) {
        $(document).ready( function() {
           setTimeout(
            function() 
            {
                $('#requisitionlist_main_rl_type').on('change', function() {
                    if(this.value == "other"){
                        $('#page_tabs_requisitionlist_products').css('display', 'block');
                    }else{
                        $('#page_tabs_requisitionlist_products').css('display', 'none');
                    }

                    if(this.value == "seasonal"){
                        $('.field-seasonal_percentage').css('display', 'block');
                    }else{
                        $('.field-seasonal_percentage').css('display', 'none');
                    }
                });

                if($('#requisitionlist_main_rl_type').val() == "other" || $('#requisitionlist_main_rl_type').val() == ''){
                    $('#page_tabs_requisitionlist_products').css('display', 'block');
                }else{
                    $('#page_tabs_requisitionlist_products').css('display', 'none');
                }

                if($('#requisitionlist_main_rl_type').val() == "seasonal"){
                    $('.field-seasonal_percentage').css('display', 'block');
                }else{
                    $('.field-seasonal_percentage').css('display', 'none');
                }
            }, 300);

        });
    }
);