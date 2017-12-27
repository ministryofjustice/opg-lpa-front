// Form Popup module for LPA
// Dependencies: moj, jQuery

(function () {
    'use strict';

    // Define the class
    var SingleUseLink = function (options) {
        this.settings = $.extend({}, this.defaults, options);
    };

    SingleUseLink.prototype = {
        defaults: {
            selector: '.js-single-use-link'
        },

        init: function () {
            // bind 'this' as this in following methods
            _.bindAll(this, 'btnClick');
            this.bindEvents();
        },

        bindEvents: function () {
            $('body')
            // link click
                .on('click.moj.Modules.SingleUseLink', this.settings.selector, this.btnClick)
        },

        btnClick: function (e) {
            var source = $(e.target);
            var href = source.attr('href');

            // set loading spinner (Disables element and removes href from link)
            source.spinner();

            // Reset href to allow link to be clicked
            source.attr('href', href);
        }
    };

    // Add module to MOJ namespace
    moj.Modules.SingleUseLink = new SingleUseLink();
}());
