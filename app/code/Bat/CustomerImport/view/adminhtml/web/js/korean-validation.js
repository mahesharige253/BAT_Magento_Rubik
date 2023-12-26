require(
    [
        'Magento_Ui/js/lib/validation/validator',
        'jquery',
        'mage/translate'
], function(validator, $){
        validator.addRule(
            'korean-alphanumeric-validation',
            function (value) {
                return /^[A-Za-z0-9가-힣\s]*$/.test(value);
            },
            $.mage.__('Please enter a valid value with alphanumeric and Korean characters.')
        );
});