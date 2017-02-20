/*jshint unused: false */
// Reusables module for LPA
// Dependencies: moj, _, jQuery

(function () {
  'use strict';

  moj.Modules.Reusables = {
    selector: '.js-reusable',
    message: 'This will replace the information which you have already entered, are you sure?',

    init: function () {
      _.bindAll(this, 'linkClicked', 'actorSelected');
      this.bindEvents();
    },

    bindEvents: function () {
      $('body')
        .on('click.moj.Modules.Reusables', 'a' + this.selector, this.linkClicked)
        .on('change.moj.Modules.Reusables', 'input[type="radio"]' + this.selector, this.actorSelected);
    },

    // <a> click
    linkClicked: function (e, params) {
      var $el = $(e.target),
        $personForm = $('form.js-PersonForm'),
        url = $el.data('target'),
        _this = this;

      if (this.isFormClean($personForm) || confirm(this.message)) {
        $el.spinner();

        $.get(url, function(data) {
          $el.spinner('off');

          _this.populateForm(data);

          //  Once the data has been populated successfully remove the link
          $el.closest('.use-details-link-panel').remove();
        });
      }
      return false;
    },

    // <radio> change
    actorSelected: function (e, params) {
      var $el = $(e.target),
        $form = $el.closest('form'),
        url = $form.attr('action'),
        requestData,
        _this = this;

      //  Don't try to get data if a value of < 0 ('None of the above') has been selected
      if ($el.val() < 0) {
        moj.Events.trigger('Steps.nextStep');
      } else {
        $el.spinner();

        //  Get the value of the reuse details input
        requestData = {'reuse-details': $el.val()};

        $.get(url, requestData).done(function (data) {
          $el.spinner('off');

          _this.populateForm(data);

          moj.Events.trigger('Steps.nextStep');
        });
      }
    },

    populateForm: function (data) {
      var $el,
        $focus,
        i = 0,
        props,
        property,
        value,
        value2;

      // prepare the data
      for (props in data) {

        if (data.hasOwnProperty(props) && _.isObject(data[props])) {

          value = data[props];

          // if value is an object then flatten it with PHP array notation...
          for (property in value) {

            if (value.hasOwnProperty(property)) {
              value2 = value[property];
              data[props + '[' + property + ']'] = value2;
            }
          }
        }
      }

      // empty existing form element values before populating data into the form.
      $('form.js-PersonForm')
        .find('input[type=text],input[type=email],select')
        .each(function() {
          $(this).val('');
        });

        // Show any fields which were hidden

        $('.js-PostcodeLookup').data('moj.PostcodeLookup').hideSearchForm();
        $('.js-PostcodeLookup').data('moj.PostcodeLookup').toggleAddress();

        // loop over data and change values
        _(data).each(function (value, key) {

        // set el
        $el = $('[name="' + key + '"]');
        // if value is null, set to empty string
        value = (value === null) ? '' : value;
        // make sure the element exists && that new value doesn't match current value
        if ($el.length > 0 && $el.val() !== value) {
          // increment counter
          i += 1;
          // change the value of the element
          if (key === 'canSign') {
            //for donor canSign checkbox
            if ((value === false)) {
              $el.filter('[type=checkbox]').attr('checked', 'checked');
            }
          }
          else {
            $el.val(value).change();
          }
          // if first element changed, save the el
          if (i === 1) {
            $focus = $('[name="' + key + '"]');
          }
        }
      });
      // focus on first changed, or first form element (accessibility)
      if ($focus !== undefined) {
        $focus.focus();
      } else {
        $('input[type=text], select, textarea').filter(':visible').first().focus();
      }
    },

    isFormClean: function (form) {
      var clean = true;
      $('input[type="text"], select:not(.js-reusable), textarea', form).each(function () {
        if ($(this).val() !== '' && $(this).filter('[name*="name-title"]').val() !== 'Mr') {
          clean = false;
        }
      });
      return clean;
    },
  };

})();
