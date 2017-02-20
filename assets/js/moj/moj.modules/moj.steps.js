// Steps module for LPA
// Dependencies: moj, jQuery

(function () {
  'use strict';

  moj.Modules.Steps = {
    selector: '.js-step',
    nextSelector: '.js-next-step',
    prevSelector: '.js-prev-step',

    init: function () {
      _.bindAll(this, 'render', 'nextStep', 'prevStep');
      this.bindEvents();
      this.render(null, {wrap: 'body'});
    },

    bindEvents: function () {
      moj.Events.on('Steps.render', this.render);
      moj.Events.on('Steps.nextStep', this.nextStep);
      moj.Events.on('Steps.prevStep', this.prevStep);
    },

    render: function (e, params) {
      var _this = this;
      var stepPanels = $(this.selector);

      //  Only continue the set up if there is more than one step panel to manage
      if (stepPanels.length > 1) {
        stepPanels.each(function (idx) {
          $(this).data('step', idx + 1);
        });

        //  Show any step links incase they have been hidden and attach the required functions
        $(this.nextSelector).show().removeClass('hidden').on('click', function () {
          _this.nextStep();
        });

        $(this.prevSelector).show().removeClass('hidden').on('click', function () {
          _this.prevStep();
        });

        //  Show the first step
        this.showStep(1);
      }
    },

    showStep: function (step) {
      //  Try to get the requested step and show it if possible
      var stepPanels = $(this.selector);

      if (stepPanels.length > 0) {
        var requestedStep = this.getStep(step);

        if (requestedStep != false) {
          //  Hide all step and then show the requested one
          stepPanels.hide();
          requestedStep.show();
        }
      }
    },

    getStep: function (step) {
      var stepPanel = false;

      $(this.selector).each(function () {
        if ($(this).data('step') == '' + step) {
          stepPanel = $(this);
          return;
        }
      });

      return stepPanel;
    },

    nextStep: function () {
      var currentStep = this.getCurrentStep();

      if (currentStep == false) {
        return;
      }

      this.showStep(currentStep.data('step') + 1);
    },

    prevStep: function () {
      var currentStep = this.getCurrentStep();

      if (currentStep == false) {
        return;
      }

      this.showStep(currentStep.data('step') - 1);
    },

    getCurrentStep: function () {
      var currentStepPanel = $(this.selector + ':visible');

      //  Only return the current step panel if exactly one panel is visible
      if (currentStepPanel.length == 1) {
        return currentStepPanel;
      }

      return false;
    },
  };

})();