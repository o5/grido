/**
 * Grido nette.ajax.js plugin.
 *
 * @author Petr Bugyík
 * @param {jQuery} $
 * @param {Document} document
 * @param {Window} window
 * @param {Grido} Grido
 * @param {undefined} undefined
 */
;
(function($, window, undefined) {
    /*jshint laxbreak: true, expr: true */
    "use strict";

    /**
     * @param {string} url
     * @param {Element|null} ussually Anchor or Form
     * @param {event|null} event causing the request
     */
    window.Grido.Ajax.prototype.doRequest = function(url, ui, e)
    {
        if ($.fn.netteAjax === undefined) {
            console.error('Plugin "nette.ajax.js" is missing! Run `bower install nette.ajax.js` and load it.');
            return;
        }

        $.nette.ajax({url: url}, ui, e);
    };

})(jQuery, window);

/**
 * Grido extension for nette.ajax.js
 * @author Petr Bugyík
 * @param {jQuery} $
 */
;(function($) {
    "use strict";

    $.nette.ext('grido',
    {
        load: function()
        {
            this.selector = $('.grido');
            this.selector.grido();
        },

        success: function(payload)
        {
            if (payload.grido) {
                this.selector.trigger('success.ajax.grido', payload);

                //scroll up after ajax update
                $('html, body').animate({scrollTop: 0}, 400);
            }
        }
    });

})(jQuery);
