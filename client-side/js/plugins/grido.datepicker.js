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

    window.Grido.Grid.prototype.onInit.push(function(Grido)
    {
        if ($.fn.daterangepicker === undefined) {
            console.error('Plugin "bootstrap-daterangepicker.js" is missing! Run `bower install bootstrap-daterangepicker` and load it.');
            return;
        }

        var format = Grido.options.datepicker.format.toUpperCase();
        Grido.$element.on('focus', 'input.date', function() {
            $(this).daterangepicker(
            {
                singleDatePicker: true,
                showDropdowns: true,
                format: format
            });
        });
    });

})(jQuery, window);
