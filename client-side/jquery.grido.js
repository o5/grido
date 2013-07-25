/**
 * This file is part of the Grido (http://grido.bugyik.cz)
 *
 * Copyright (c) 2011 Petr Bugyík (http://petr.bugyik.cz)
 *
 * For the full copyright and license information, please view
 * the file license.md that was distributed with this source code.
 */

/**
 * Client-side script for Grido.
 *
 * @package     Grido
 * @author      Petr Bugyík
 * @depends
 *      jquery.js > 1.7
 *      bootstrap-typeahead.js - https://rawgithub.com/o5/bootstrap/master/js/bootstrap-typeahead.js
 *      jquery.hashchange.js - https://rawgithub.com/fujiy/jquery-hashchange/master/jquery.ba-hashchange.js
 *      jquery.maskedinput.js - https://rawgithub.com/digitalBush/jquery.maskedinput/master/dist/jquery.maskedinput.js
 *      bootstrap-datepicker.js - https://rawgithub.com/Aymkdn/Datepicker-for-Bootstrap/master/bootstrap-datepicker.js
 */

;(function($, window, document, undefined) {

    var Grido = Grido || {};

    /*    GRID CLASS DEFINITION   */
    /* ========================== */

    Grido.Grid = function($element, options)
    {
        this.$element = $element;
        this.name = $element.attr('id');
        this.options = $.extend($.fn.grido.defaults, options, $element.data('grido-options') || {});
    };

    Grido.Grid.prototype =
    {
        operation: null,

        /**
         * Initial function.
         */
        init: function()
        {
            this.initFilters();
            this.initItemsPerPage();
            this.initActions();
            this.initPagePrompt();
            this.initOperation();
            this.initSuggest();
            this.initDatepicker();
            this.initCheckNumeric();
            this.initAjax();

            return this;
        },

        /**
         * Attach a change handler to filter elements (select, checkbox).
         */
        initFilters: function()
        {
            this.$element.on('change', '.filter select, .filter [type=checkbox]',
                $.proxy(this.sendFilterForm, this)
            );
        },

        /**
         * Attach a change handler to items-per-page select.
         */
        initItemsPerPage: function()
        {
            this.$element.on('change', '[name=count]', function() {
                $(this).next().trigger('click');
            });
        },

        /**
         * Attach a click handler to action anchors.
         */
        initActions: function()
        {
            this.$element.on('click', '.actions a', function() {
               var hasConfirm = $(this).data('grido-confirm');
               return hasConfirm ? confirm(hasConfirm) : true;
            });
        },

        /**
         * Attach a click handler to page prompt.
         */
        initPagePrompt: function()
        {
            var _this = this;
            this.$element.on('click', '.paginator .prompt', function() {
                var page = parseInt(prompt($(this).data('grido-prompt')));
                if (page && page > 0 && page <= parseInt($('.paginator a.btn:last', _this.element).prev().text())) {
                    var location = $(this).data('grido-link').replace('page=0', 'page=' + page);
                    window.location = _this.options.ajax ? location.replace('?', '#') : location;
                }
            });
        },

        /**
         * Init operation when exist.
         */
        initOperation: function()
        {
            if ($('th.checker', this.$element).length) {
                this.operation = new Grido.Operation(this).init();
            }
        },

        /**
         * Init suggestion.
         */
        initSuggest: function()
        {
            if ($.fn.typeahead === undefined) {
                console.error('Plugin "bootstrap-typeahead.js" is missing!');
                return;
            }

            var _this = this;
            this.$element
                .on('keyup', 'input.suggest', function(event) {
                    var key = event.keyCode || event.which;
                    if (key === 13) { //enter
                        event.stopPropagation();
                        event.preventDefault();

                        _this.sendFilterForm();
                    }
                })
                .on('focus', 'input.suggest', function() {
                    $(this).typeahead({
                        source: function (query, process) {
                            if (!/\S/.test(query)) {
                                return false;
                            }

                            var link = this.$element.data('grido-suggest-handler'),
                                replacement = this.$element.data('grido-suggest-replacement');

                            return $.get(link.replace(replacement, query), function (items) {
                                //TODO local cache??
                                process(items);
                            }, "json");
                        },

                        updater: function (item) {
                            this.$element.val(item);
                            _this.sendFilterForm();
                        },

                        autoSelect: false //improvement of original bootstrap-typeahead.js
                    });
            });
        },

        /**
         * Init datepicker.
         */
        initDatepicker: function()
        {
            var _this = this;
            this.$element.on('focus', 'input.date', function() {
                $.fn.mask === undefined
                    ? console.error('Plugin "jquery.maskedinput.js" is missing!')
                    : $(this).mask(_this.options.datepicker.mask);

                $.fn.datepicker === undefined
                    ? console.error('Plugin "bootstrap-datepicker.js" is missing!')
                    : $(this).datepicker({format: _this.options.datepicker.format});
            });
        },

        /**
         * Checking numeric input.
         */
        initCheckNumeric: function()
        {
            this.$element.on('keyup', 'input.number', function() {
                var value = $(this).val(),
                    pattern = new RegExp(/[^<>=\\.\\,\-0-9]+/g); //TODO: improve my regex knowledge :)

                pattern.test(value) && $(this).val(value.replace(pattern, ''));
            });
        },

        initAjax: function()
        {
            this.options.ajax && new Grido.Ajax(this).init();
        },

        /**
         * Sending filter form.
         */
        sendFilterForm: function()
        {
            $('[name="buttons[search]"]', this.$element).click();
        }
    };


    /* OPERATION CLASS DEFINITION */
    /* ========================== */

    Grido.Operation = function(Grido)
    {
        this.grido = Grido;
    };

    Grido.Operation.prototype =
    {
        selector: 'td.checker [type=checkbox]',

        //storage for last selected row
        $last: null,

        init: function()
        {
            this.initSelectState();
            this.bindClickOnCheckbox();
            this.bindClickOnRow();
            this.bindClickOnInvertor();
            this.bindChangeOnCheckbox();
            this.bindChangeOnSelect();
            this.bindClickOnButton();

            return this;
        },

        initSelectState: function()
        {
            $(this.selector + ':checked', this.grido.$element).length === 0 && this.controlState('disabled');
        },

        /**
         * Click on checkbox with shift support.
         */
        bindClickOnCheckbox: function()
        {
            var _this = this;
            this.grido.$element.on('click', this.selector, function(event, data) {
                if(event.shiftKey || (data && data.shiftKey)) {
                    var $boxes = $(_this.selector, _this.grido.$element),
                        start = $boxes.index(this),
                        end = $boxes.index(_this.$last);

                    $boxes.slice(Math.min(start, end), Math.max(start, end))
                        .attr('checked', _this.$last.checked)
                        .trigger('change');
                }

                _this.$last = this;
            });
        },

        bindClickOnRow: function()
        {
            var _this = this;
            this.grido.$element.on('click', 'tbody td:not(.checker,.actions)', function(event) {
                $.proxy(_this.disableSelection, _this)();

                //this trigger will not be work in jQuery > 1.8.3
                //http://bugs.jquery.com/ticket/13428
                $('[type=checkbox]', $(this).parent()).trigger('click', [{shiftKey: event.shiftKey}]);
                $.proxy(_this.enableSelection, _this)();
            });
        },

        bindClickOnInvertor: function()
        {
            var _this = this;
            this.grido.$element.on('click', 'th.checker [type=checkbox]', function() {
                $(_this.selector, _this.grido.$element).each(function() {
                    var val = $(this).prop('checked');
                    $(this).prop('checked', !val);
                    _this.changeRow($(this).parent().parent(), !val);
                });

                return false;
            });
        },

        bindChangeOnCheckbox: function()
        {
            var _this = this;
            this.grido.$element.on('change', this.selector, function() {
                $.proxy(_this.changeRow, _this)($(this).parent().parent(), $(this).prop('checked'));
            });
        },

        bindChangeOnSelect: function()
        {
            var _this = this;
            this.grido.$element.on('change', '.operations [name="operations[operations]"]', function() {
                $(this).val() && $('.operations [type=submit]', _this.grido.$element).click();
            });
        },

        bindClickOnButton: function()
        {
            this.grido.$element.on('click', '.operations [type=submit]', $.proxy(this.onSubmit, this));
        },

        disableSelection: function()
        {
            this.grido.$element
                .attr('unselectable', 'on')
                .css('user-select', 'none');
        },

        enableSelection: function()
        {
            if (window.getSelection) {
                var selection = window.getSelection();
                selection.removeAllRanges && selection.removeAllRanges();

            } else if (window.document.selection) {
                window.document.selection.empty();
            }

            this.grido.$element
                .attr('unselectable', 'off')
                .attr('style', null);
        },

        /**
         * Returns operation select.
         * @returns {jQuery}
         */
        getSelect: function()
        {
            return $('.operations [name="operations[operations]"]', this.grido.$element);
        },

        /**
         * @param {jQuery} $row
         * @param {bool} selected
         */
        changeRow: function($row, selected)
        {
            selected
                ? $row.addClass('selected')
                : $row.removeClass('selected');

            $(this.selector + ':checked', this.grido.$element).length === 0
                ? this.controlState('disabled')
                : this.controlState('enabled');
        },

        onSubmit: function()
        {
            var hasConfirm = this.getSelect().data('grido-' + this.getSelect().val());
            if (hasConfirm) {
                if (confirm(hasConfirm.replace(/%i/g, $(this.selector + ':checked', this.grido.$element).length))) {
                    return true;
                }

                this.getSelect().val('');
                return false;
            }

            return true;
        },

        /**
         * @param {String} state
         */
        controlState: function(state)
        {
            var $button = $('[name="buttons[operations]"]', this.grido.$element);
            if (state === 'disabled') {
                this.getSelect().attr('disabled', 'disabled').addClass('disabled');
                $button.addClass('disabled');
            } else {
                this.getSelect().removeAttr('disabled').removeClass('disabled');
                $button.removeClass('disabled');
            }
        }
    };


    /*    AJAX CLASS DEFINITION   */
    /* ========================== */

    Grido.Ajax = function(Grido)
    {
        this.grido = Grido;
    };

    Grido.Ajax.prototype =
    {
        init: function()
        {
            this.registerSuccessEvent();
            this.registerHashChangeEvent();

            return this;
        },

        registerSuccessEvent: function()
        {
            var _this = this;
            this.grido.$element.bind('gridoAjaxSuccess', function(event, payload) {
                $.proxy(_this.handleSuccessEvent, _this)(payload);
                event.stopImmediatePropagation();
            });
        },

        registerHashChangeEvent: function()
        {
            $.fn.hashchange === undefined
                ? console.error('Plugin "jquery.hashchange.js" is missing!')
                : $(window).hashchange($.proxy(this.handleHashChangeEvent, this));

            this.handleHashChangeEvent();
        },

        /**
         * @param {Object} payload
         */
        handleSuccessEvent: function(payload)
        {
            var _this = this,
                params = {},
                snippet = 'snippet-' + this.grido.name + '-grid';

            if (payload && payload.snippets && payload.snippets[snippet] && payload.state) { //is ajax update?
                $.each(payload.state, function(key, val) {
                    if ((val || val === 0) && key.indexOf('' + _this.grido.name + '-') >= 0) {
                        params[key] = val;
                    }
                });

                var hash = decodeURIComponent($.param(params));
                $.data(document, 'grido-state', hash);
                window.location.hash = hash;
            }
        },

        handleHashChangeEvent: function()
        {
            var state = $.data(document, 'grido-state') || '',
                hash = window.location.hash.toString().replace('#', '');

            if (hash.indexOf(this.grido.name + '-') >= 0 && state !== hash) {
                var url = window.location.toString();
                url = url.indexOf('?') >= 0 ? url.replace('#', '&') : url.replace('#', '?');
                url = url + '&do=' + this.grido.name + '-refresh';

                $.fn.netteAjax === undefined
                    ? $.get(url)
                    : $.nette.ajax({url: url});
            }
        }
    };

    /*  GRIDO PLUGIN DEFINITION   */
    /* ========================== */

    var old = $.fn.grido;

    $.fn.grido = function(options) {
        return this.each(function() {
            new Grido.Grid($(this), options).init();
        });
    };

    /*      GRIDO SHORTCUTS       */
    /* ========================== */

    $.fn.grido.Grido = Grido;
    $.fn.grido.Grid = Grido.Grid;
    $.fn.grido.Ajax = Grido.Ajax;
    $.fn.grido.Operation = Grido.Operation;

    /*      GRIDO NO CONFLICT     */
    /* ========================== */

    $.fn.grido.noConflict = function () {
        $.fn.grido = old;
        return this;
    };

    /*      GRIDO DEFAULTS        */
    /* ========================== */

    $.fn.grido.defaults = {
        ajax: true,
        datepicker : {
            mask: '99.99.9999',
            format: 'dd.mm.yyyy'
        }
    };

})(jQuery, window, document);
