/**
 * Grido paginator plugin.
 *
 * @author Petr Bugyík
 * @param {jQuery} $
 * @param {Window} window
 */
;
(function($, window) {
    /*jshint laxbreak: true, expr: true */
    "use strict";

    window.Grido.Grid.prototype.onInit.push(function(Grido)
    {
        if (Grido.$element.hasClass('bootstrap') === false) { // template `bootstrap.latte` is required
            return;
        }

        var tmp;
        var selector = '.paginator input';

        Grido.$element
            .on('keyup', selector, function(e) {
                var code = e.keyCode || e.which;
                if (code === 13) {
                    var $el = $(this);
                    Grido.ajax.doRequest($el.data('grido-link').replace(0, $el.val()));
                    return false;
                }

                var val = parseInt(this.value);
                var min = parseInt($(this).attr('min'));
                var max = parseInt($(this).attr('max'));
                if (isNaN(this.value) || val < min || max < val) {
                    $(this).val(this.value.length > 1
                        ? this.value.substr(0, this.value.length - 1)
                        : $(this).data('grido-current'));

                    return false;
                }
            })
            .on('focus', selector, function() {
                $(this)
                    .val($(this).data('grido-current'))
                    .select();
            })
            .on('blur', selector, function() {
                $(this).val('');
                $(this).attr('placeholder', tmp);
            });
    });

})(jQuery, window);
