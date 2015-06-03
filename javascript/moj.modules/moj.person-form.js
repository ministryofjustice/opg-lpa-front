// Reusables module for LPA
// Dependencies: moj, _, jQuery

(function () {
  'use strict';

  moj.Modules.PersonForm = {
    selector: '.js-PersonForm',

    init: function () {
      _.bindAll(this, 'render', 'formEvents');
      this.cacheEls();
      this.bindEvents();
    },

    cacheEls: function () {
      this.$els = $(this.selector);
    },

    bindEvents: function () {
      // default moj render event
      moj.Events.on('render', this.render);
      // custom render event
      moj.Events.on('TitleSwitch.render', this.render);
    },

    render: function (e, params) {
      var wrap = params !== undefined && params.wrap !== undefined ? params.wrap : 'body';
      $(this.selector, wrap).each(this.formEvents);
    },

    formEvents: function (i, el) {
      var $form = $(el),
          $submitBtn = $('input[type="submit"]', $form),
          donorCannotSign = $('#donor_cannot_sign', $form).is(':checked'),
          $allFields = $('label.required + input, label.required ~ select', $form),
          $addressFields = $('input[name^="address"]', $form),
          allPopulated,
          countAddr;

      // disable submit if empty form
      $submitBtn.attr('disabled', $('#address-addr1', $form).val() === '');

      // Listen for changes to form
      $form
        .on('change.moj.Modules.PersonForm', 'input, select', function () {
          allPopulated = true;

          // Test required fields are populated
          $allFields.each(function () {
            if ($.trim($(this).val()) === '') {
              allPopulated = false;
            }
          });

          // Count populated address fields
          countAddr = $addressFields.filter(function () {
            return this.value.length !== 0;
          }).length;

          // Test address fields - business logic states 2 address fields as min
          if (countAddr < 2) {
            allPopulated = false;
          }

          $submitBtn.attr('disabled', !allPopulated);
        })
        // Relationship: other toggle
        .on('change.moj.Modules.PersonForm', '[name="relationshipToDonor"]', function () {
          var other = $('#relationshipToDonorOther').closest('.group');
          if ($(this).val() === 'Other') {
            other.show().find('input').focus();
          } else {
            other.hide();
          }
        });

      // toggle initial change on donor relationship
      $('[name="relationshipToDonor"]', $form).change().closest('form').data('dirty', false);

      // donor toggle
      if (donorCannotSign) {
        $('#donorsignprompt', $form).show();
      } else {
        $('#donorsignprompt', $form).hide();
      }

      // Initialise details tag within the lightbox
      $('details', $form).details();

      // show free text field on certificate provider form when a statement type was chosen
      $('input:radio[name="certificateProviderStatementType"]').each(function (idx) {
        if ($(this).attr('checked') !== undefined) {
          if (idx === 0) {
            $(':input[name="certificateProviderKnowledgeOfDonor"]').closest('.form-element-textarea-cp-statement').show();
          } else {
            $(':input[name="certificateProviderProfessionalSkills"]').closest('.form-element-textarea-cp-statement').show();
          }
        }
      });
    }


  };

})();