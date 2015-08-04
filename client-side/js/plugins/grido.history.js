/**
 * Grido history.js plugin.
 * @link https://github.com/browserstate/history.js
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

    window.Grido.Ajax.prototype.onSuccessEvent = function(params, url)
    {
        if (window.History === undefined) {
            console.error('Plugin "history.js" is missing! Run `bower install history.js` and load it.');
            return;
        }

        window.History.pushState(params, document.title, url);
    };

})(jQuery, window, document);
