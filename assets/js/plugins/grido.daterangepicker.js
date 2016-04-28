/**
 * Grido date-range picker plugin.
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

    window.Grido.DateRangePicker =
    {
        /**
         * @returns {boolean}
         */
        isLoaded: function()
        {
            if ($.fn.daterangepicker === undefined) {
                console.error('Plugin "bootstrap-daterangepicker.js" is missing! Run `bower install bootstrap-daterangepicker` and load it.');
                return false;

            } else if (window.moment === undefined) {
                console.error('Plugin "moment.js" required by "bootstrap-daterangepicker.js" is missing!');
                return false;
            }

            return true;
        },

        /**
         * @param Grido
         * @returns {Grido.DateRangePicker}
         */
        init: function(Grido)
        {
            var $input,
                defaults = Grido.options.daterangepicker;

            var options = $.extend({
                autoApply: true,
                showDropdowns: true,
                autoUpdateInput: false,
                ranges: this.getRanges(),
                locale: {
                    format: defaults.format,
                    separator: defaults.separator
                },
            }, defaults.options);

            Grido.$element.on('focus', 'input.daterange', function()
            {
                $input = $(this);
                $input.daterangepicker(options);
                $input.on('apply.daterangepicker', function(e, picker) {
                    $input.val(
                        picker.startDate.format(defaults.format) +
                        defaults.separator +
                        picker.endDate.format(defaults.format)
                    );
                    Grido.sendFilterForm();
                });
            });

            return this;
        },

        /**
         * @returns {{Today: *[], Yesterday: *[], Last 7 Days: *[], Last 30 Days: *[], This Month: *[], Last Month: *[]}}
         */
        getRanges: function()
        {
            return {
                'Today': [window.moment(), window.moment()],
                'Yesterday': [window.moment().subtract(1, 'days'), window.moment().subtract(1, 'days')],
                'Last 7 Days': [window.moment().subtract(6, 'days'), window.moment()],
                'Last 30 Days': [window.moment().subtract(29, 'days'), window.moment()],
                'This Month': [window.moment().startOf('month'), window.moment().endOf('month')],
                'Last Month': [window.moment().subtract(1, 'month').startOf('month'), window.moment().subtract(1, 'month').endOf('month')]
            };
        }
    };

    window.Grido.Grid.prototype.onInit.push(function(Grido)
    {
        var DateRangePicker = window.Grido.DateRangePicker;
        DateRangePicker.isLoaded() && DateRangePicker.init(Grido);
    });

})(jQuery, window);
