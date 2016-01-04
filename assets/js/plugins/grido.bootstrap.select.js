/**
 * Grido plugin for bootstrap-select library.
 * @link https://github.com/silviomoreto/bootstrap-select
 *
 * @author Petr Bugyík
 * @param {jQuery} $
 * @param {Window} window
 * @param {Document} document
 * @param {undefined} undefined
 */
;
(function($, window, document, undefined) {
    /*jshint laxbreak: true, expr: true */
    "use strict";

    window.Grido.Grid.prototype.onInit.push(function(Grido)
    {
        if (Grido.$element.hasClass('bootstrap') === false) { // template `bootstrap.latte` is required
            return;
        }

        if ($.fn.selectpicker === undefined) {
            console.error('Plugin "bootstrap-select.js" is missing! Run `bower install bootstrap-select` and load it.');
            return;
        }

        var init = function () {
            $('.filter select').selectpicker({
                noneSelectedText: '',
                style: 'btn-default',
                liveSearch: true
            });
        };

        init();
        $(document).ajaxSuccess(init);
    });

})(jQuery, window, document);
