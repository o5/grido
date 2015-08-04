/**
 * Grido date range picker plugin.
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

    window.Grido.Grid.prototype.onInit.push(function(Grido)
    {
        if ($.fn.daterangepicker === undefined) {
            console.error('Plugin "bootstrap-daterangepicker.js" is missing! Run `bower install bootstrap-daterangepicker` and load it.');
            return;
        } else if (window.moment === undefined) {
            console.error('Plugin "moment.js" required by "bootstrap-daterangepicker.js" is missing!');
            return;
        }

        var format = Grido.options.datepicker.format.toUpperCase();
        Grido.$element.on('focus', 'input.daterange', function() {
            $(this).daterangepicker(
            {
                format: format,
                showDropdowns: true,
                ranges: {
                    'Today': [window.moment(), window.moment()],
                    'Yesterday': [window.moment().subtract(1, 'days'), window.moment().subtract(1, 'days')],
                    'Last 7 Days': [window.moment().subtract(6, 'days'), window.moment()],
                    'Last 30 Days': [window.moment().subtract(29, 'days'), window.moment()],
                    'This Month': [window.moment().startOf('month'), window.moment().endOf('month')],
                    'Last Month': [window.moment().subtract(1, 'month').startOf('month'), window.moment().subtract(1, 'month').endOf('month')]
                },
                startDate: window.moment().subtract(29, 'days'),
                endDate: window.moment()
            });
        });
    });

})(jQuery, window);
