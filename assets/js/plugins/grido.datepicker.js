/**
 * Grido date picker plugin.
 * @link https://github.com/dangrossman/bootstrap-daterangepicker
 *
 * @author Petr Bugyík
 * @param {jQuery} $
 * @param {Window} window
 * @param {undefined} undefined
 */
;
(function($, window, undefined) {
    /*jshint laxbreak: true, expr: true */
    "use strict";

    window.Grido.DatePicker =
    {
        /**
         * @returns {boolean}
         */
        isLoaded: function()
        {
            if ($.fn.daterangepicker === undefined) {
                console.error('Plugin "bootstrap-daterangepicker.js" is missing! Run `bower install bootstrap-daterangepicker` and load it.');
                return false;
            }

            return true;
        },

        /**
         * @param Grido
         * @returns {Grido.DatePicker}
         */
        init: function(Grido)
        {
            var $input,
                defaults = Grido.options.datepicker;

            var options = $.extend({
                autoApply: false,
                showDropdowns: true,
                autoUpdateInput: false,
                singleDatePicker: true,
                locale: {
                    format: defaults.format
                }
            }, defaults.options);

            Grido.$element.on('focus', 'input.date', function() {
                $input = $(this);
                $input.daterangepicker(options);
                $input.on('apply.daterangepicker', function(e, picker) {
                    $input.val(picker.startDate.format(defaults.format));
                    Grido.sendFilterForm();
                });
            });

            return this;
        }
    };

    window.Grido.Grid.prototype.onInit.push(function(Grido)
    {
        var DatePicker = window.Grido.DatePicker;
        DatePicker.isLoaded() && DatePicker.init(Grido);
    });

})(jQuery, window);
