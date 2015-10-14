/**
 * Grido suggest plugin.
 * @link https://github.com/twitter/typeahead.js
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
        if ($.fn.typeahead === undefined) {
            console.error('Plugin "typeahead.js" is missing! Run `bower install typeahead.js` and load bundled version.');
            return;
        } else if (window.Bloodhound === undefined) {
            console.error('Plugin "Bloodhound" required by "typeahead.js" is missing!');
            return;
        }

        var _this = Grido;
        Grido.$element.find('input.suggest').each(function()
        {
            var url = $(this).data('grido-suggest-handler'),
                wildcard = $(this).data('grido-suggest-replacement');

            var options = {
                limit: $(this).data('grido-suggest-limit'),
                datumTokenizer: window.Bloodhound.tokenizers.obj.whitespace('value'),
                queryTokenizer: window.Bloodhound.tokenizers.whitespace,
                remote: {
                    url: url,
                    wildcard: '-query-'
                }
            };

            if (window.NProgress !== undefined) {
                options.remote.ajax = {
                    beforeSend: $.proxy(window.NProgress.start),
                    complete: $.proxy(window.NProgress.done)
                };
            }

            var source = new window.Bloodhound(options);

            var typeaheadOptions;
            $(this).typeahead(
                typeaheadOptions,
                {
                    source: source
                }
            );

            $(this).on('typeahead:selected', function() {
                _this.sendFilterForm();
            });
        });
    });

})(jQuery, window);
