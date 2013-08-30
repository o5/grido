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
 * @author Petr Bugyík
 * @param {jQuery} $ (version > 1.7)
 * @param {Window} window
 * @param {Document} document
 */
;(function($, window, document) {
    /*jshint laxbreak: true, expr: true */
    "use strict";

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
            this.initCheckNumeric();
            this.initAjax();
            this.onInit();

            return this;
        },

        /**
         * Attach a change handler to filter elements (select, checkbox).
         */
        initFilters: function()
        {
            $('.filter select, .filter [type=checkbox]', this.$element)
                .off('change.grido')
                .on('change.grido', $.proxy(this.sendFilterForm, this));
        },

        /**
         * Attach a change handler to items-per-page select.
         */
        initItemsPerPage: function()
        {
            $('[name=count]', this.$element)
                .off('change.grido')
                .on('change.grido', function() {
                    $(this).next().trigger('click');
                });
        },

        /**
         * Attach a click handler to action anchors.
         */
        initActions: function()
        {
            $('.actions a', this.$element)
                .off('click.grido')
                .on('click.grido', function(event) {
                    var hasConfirm = $(this).data('grido-confirm');
                    if (hasConfirm && !confirm(hasConfirm)) {
                        event.preventDefault();
                        event.stopImmediatePropagation();
                        return false;
                    }
                });
        },

        /**
         * Attach a click handler to page prompt.
         */
        initPagePrompt: function()
        {
            var _this = this;
            $('.paginator .prompt', this.$element)
                .off('click.grido')
                .on('click.grido', function() {
                    var page = parseInt(prompt($(this).data('grido-prompt')), 10);
                    if (page && page > 0 && page <= parseInt($('.paginator a.btn:last', _this.element).prev().text(), 10)) {
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
         * Checking numeric input.
         */
        initCheckNumeric: function()
        {
            $('input.number', this.$element)
                .off('keyup.grido')
                .on('keyup.grido', function() {
                    var value = $(this).val(),
                        pattern = new RegExp(/[^<>=\\.\\,\-0-9]+/g); //TODO: improve my regex knowledge :)

                    pattern.test(value) && $(this).val(value.replace(pattern, ''));
                });
        },

        initAjax: function()
        {
            this.options.ajax && new Grido.Ajax(this).init();
        },

        onInit: function() {},

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
            $(this.selector, this.grido.$element)
                .off('click.grido')
                .on('click.grido', function(event, data) {
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
            $('tbody td:not(.checker,.actions a)', this.grido.$element)
                .off('click.grido')
                .on('click.grido', function(event) {
                    if (event.shiftKey) {
                        _this.disableSelection.call(_this);
                    }

                    //this trigger will not be work in jQuery > 1.8.3
                    //http://bugs.jquery.com/ticket/13428
                    $('[type=checkbox]', $(this).parent()).trigger('click', [{shiftKey: event.shiftKey}]);

                    if (event.shiftKey) {
                        _this.enableSelection.call(_this);
                    }
                });
        },

        bindClickOnInvertor: function()
        {
            var _this = this;
            $('th.checker [type=checkbox]', this.grido.$element)
                .off('click.grido')
                .on('click.grido', function() {
                    $(_this.selector, _this.grido.$element).each(function() {
                        var val = $(this).prop('checked');
                        $(this).prop('checked', !val);
                        _this.changeRow($(this).closest('tr'), !val);
                    });

                    return false;
                });
        },

        bindChangeOnCheckbox: function()
        {
            var _this = this;
            $(this.selector, this.grido.$element)
                .off('change.grido')
                .on('change.grido', function() {
                    $.proxy(_this.changeRow, _this)($(this).closest('tr'), $(this).prop('checked'));
                });
        },

        bindChangeOnSelect: function()
        {
            var _this = this;
            $('.operations [name="operations[operations]"]', this.grido.$element)
                .off('change.grido')
                .on('change.grido', function() {
                    $(this).val() && $('.operations [type=submit]', _this.grido.$element).click();
                });
        },

        bindClickOnButton: function()
        {
            $('.operations [type=submit]', this.grido.$element)
                .off('click.grido')
                .on('click.grido', $.proxy(this.onSubmit, this));
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

            } else if (document.selection) { //IE < 9
                document.selection.empty();
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
                ? $row.addClass('info')
                : $row.removeClass('info');

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
            this.grido.$element.bind('success.ajax.grido', function(event, payload) {
                $.proxy(_this.handleSuccessEvent, _this)(payload);
                event.stopImmediatePropagation();
            });
        },

        registerHashChangeEvent: function()
        {
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
                this.doRequest(url + '&do=' + this.grido.name + '-refresh');
            }
        },

        /**
         * Load data from the server using a HTTP GET request.
         * @param {string} url
         */
        doRequest: function(url)
        {
            $.get(url);
        }
    };

    /*  GRIDO PLUGIN DEFINITION   */
    /* ========================== */

    var old = $.fn.grido;

    $.fn.grido = function(options) {
        return this.each(function() {
            var $this = $(this);
            if (!$this.data('grido')) {
                $this.data('grido', new Grido.Grid($this, options).init());
            }
        });
    };

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

    window.Grido = Grido;

})(jQuery, window, document);
