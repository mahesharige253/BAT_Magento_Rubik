require(
    [
        'Magento_Ui/js/lib/validation/validator',
        'jquery',
        'mage/translate',
        'Magento_Ui/js/lib/validation/utils'
    ], function(validator, $){

        validator.addRule(
            'customer-approved',
            function (value) {
                var approvalStatus = $("select[name='customer[approval_status]']").val();
                if(approvalStatus == 1 || approvalStatus == 4 || approvalStatus == 5){
                    if(value === undefined || value.trim() == ''){
                        return false;
                    } else {
                        return true;
                    }
                }
                else {
                    return true;
                }
            }
            ,$.mage.__('This field is required.')
        );

        validator.addRule(
            'sap-outlet-code-required',
            function (value) {
                var approvalStatus = $("select[name='customer[approval_status]']").val();
                if(approvalStatus == 1){
                    if(value === undefined || value.trim() == ''){
                        return false;
                    } else {
                        return true;
                    }
                }
                else {
                    return true;
                }
            }
            ,$.mage.__('This field is required.')
        );

        validator.addRule(
            'is-market-consent-given',
            function (value) {
                var approvalStatus = $("select[name='customer[approval_status]']").val();
                if((approvalStatus == 1 || approvalStatus == 4 || approvalStatus == 5)
                    && $("select[name='customer[customer][market_consent_given]']").val() == '1'){
                    if(value === undefined || value.trim() == ''){
                        return false;
                    } else {
                        return true;
                    }
                }
                else {
                    return true;
                }
            }
            ,$.mage.__('This field is required.')
        );

    });
