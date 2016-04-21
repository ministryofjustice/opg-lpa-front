// SHAME.JS
// This is a temporary dumping ground which should NEVER go into production

$(document).ready(function () {
  'use strict';

  // ====================================================================================
  // EMPHASISED CHECKBOX AND RADIO BUTTON LABEL STYLES
  // NOTE: Only on older pages. This won't be needed when new styles come in

  var $emphasised = $('.emphasised input');
  $emphasised.filter(':checked').parent().addClass('checked');
  $emphasised.change(function() {
      $emphasised.parent().removeClass('checked');
      $emphasised.filter(':checked').parent().addClass('checked');
  });

  // Remove the no-js class
  $('body').removeClass('no-js');

  // Initiating the SelectionButtons GOVUK module
  var $blockLabels = $(".block-label input[type='radio'], .block-label input[type='checkbox']");
  new GOVUK.SelectionButtons($blockLabels);

});

