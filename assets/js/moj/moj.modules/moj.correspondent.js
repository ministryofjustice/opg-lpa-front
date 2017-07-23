// Dependencies: moj, jQuery

(function () {
    'use strict';

    moj.Modules.Correspondent = {

        init: function () {
            this.changeLanguage();
            this.renderCorrespondentCheckboxes();
        },

        changeLanguage: function() {
            $('input[name="contactInWelsh"]').on('change', function () {
                moj.Modules.Correspondent.renderCorrespondentCheckboxes();
            });
        },

        renderCorrespondentCheckboxes: function() {
            // Only do the following if the Welsh language option is displaying
            var contactInWelsh = $('input[name="contactInWelsh"]:checked');

            if (contactInWelsh.length > 0) {
                //  Make sure to display the contact by phone label is displayed by default
                var contactByPhone = $('#contactByPhone');
                var contactByPhoneLabel = contactByPhone.closest('label');
                contactByPhoneLabel.show();

                //  If Welsh has been selected then hide the phone number inputs
                if (contactInWelsh.val() == 1) {
                    var isChecked = contactByPhone.is(':checked');

                    //  If the phone checkbox is checked then click it to uncheck it first
                    if (isChecked) {
                        contactByPhone.trigger('click');
                    }

                    contactByPhone.data('was-checked', isChecked);
                    contactByPhoneLabel.hide();
                } else if (contactByPhone.data('was-checked')) {
                    //  If the contact by phone input was previously checked then re-check it
                    contactByPhone.trigger('click');
                }
            }
        }
    };
})();