require(
    [
        'jquery',
        'mage/translate',
    ],
    function ($) {
        $('#accountclosure_main_returning_stock').on('change', function() {
            if(this.value == 0){
                $('#return-productId').css('display', 'none');
            }else{
                $('#return-productId').css('display', 'block');
            }
        });

        if($('#accountclosure_main_returning_stock').val() ==0){
            $('#return-productId').css('display', 'none');
        }

        if($("#accountclosure_main_bank_account_card_image").length) {
            $('#accountclosure_main_bank_account_card').css('display','none');
            $('.delete-image').css('display','none');
        }

        }
);