define([
    'underscore',
    'Magento_Ui/js/grid/columns/column'
], function (_, Column) {
    'use strict';

    return Column.extend({
        defaults: {
            bodyTmpl: 'Bat_VirtualBank/ui/grid/cells/remaining-accounts'
        },
        getAccountsAvailability: function (row) {
            if (row.remaining_account <= 300) {
                return 'accounts-not-available';
            }
            return 'accounts-available';
        }
    });
});
