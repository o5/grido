/**
 * This file is part of the Grido (http://grido.bugyik.cz)
 *
 * Copyright (c) 2011 Petr Bugyík (http://petr.bugyik.cz)
 *
 * For the full copyright and license information, please view
 * the file LICENSE.md that was distributed with this source code.
 */

/**
 * Client-side script for Grido.
 *
 * @author Petr Bugyík
 * @param {jQuery} $ (version > 1.7)
 * @param {Window} window
 * @param {Document} document
 * @param {Location} location
 * @param {Navigator} navigator
 */
;(function($, window, document, location, navigator) {
    /*jshint laxbreak: true, expr: true */
    "use strict";

    var Grido = Grido || {};

    /**
     * Grid definition.
     * @param {jQuery} $element
     * @param {Object} options
     */
    Grido.Grid = function($element, options)
    {
        this.$element = $element;
        this.$table = $('table', $element);

        this.name = this.$table.attr('id');
        this.options = $.extend($.fn.grido.defaults, options, this.$table.data('grido-options') || {});
    };

    Grido.Grid.prototype =
    {
        operation: null,

        /**
         * Initial function.
         */
        init: function()
        {
            this.ajax = new Grido.Ajax(this).init();
            this.operation = new Grido.Operation(this).init();

            this.initFilters();
            this.initItemsPerPage();
            this.initActions();
            this.initPagePrompt();
            this.initCheckNumeric();
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
            $('[name=count]', this.$table)
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
            var that = this;
            $('.actions a', this.$table)
                .off('click.nette')
                .off('click.grido')
                .on('click.grido', function(event) {
                    var isAjax = $(this).hasClass('ajax') && that.ajax,
                        hasConfirm = $(this).data('grido-confirm'),
                        stop = function(event) {
                            event.preventDefault();
                            event.stopImmediatePropagation();
                        };

                    if (hasConfirm && confirm(hasConfirm)) {
                        isAjax && (that.ajax.doRequest(this.href) || stop(event));

                    } else if (hasConfirm) {
                        stop(event);

                    } else if (isAjax) {
                        that.ajax.doRequest(this.href) || stop(event);
                    }
                });
        },

        /**
         * Attach a click handler to page prompt.
         */
        initPagePrompt: function()
        {
            var that = this;
            $('.paginator .prompt', this.$table)
                .off('click.grido')
                .on('click.grido', function() {
                    var page = parseInt(prompt($(this).data('grido-prompt')), 10);
                    if (page && page > 0 && page <= parseInt($('.paginator a.btn:last', that.element).prev().text(), 10)) {
                        var location = $(this).data('grido-link').replace('page=0', 'page=' + page);
                        window.location = that.options.ajax ? location.replace('?', '#') : location;
                    }
                });
        },

        /**
         * Checking numeric input.
         */
        initCheckNumeric: function()
        {
            $('.filter input.number', this.$element)
                .off('keyup.grido')
                .on('keyup.grido', function() {
                    var value = $(this).val(),
                        pattern = new RegExp(/[^<>=\\.\\,\-0-9]+/g);

                    pattern.test(value) && $(this).val(value.replace(pattern, ''));
                });
        },

        onInit: function() {},

        /**
         * Sending filter form.
         */
        sendFilterForm: function()
        {
            $('.filter [name="buttons[search]"]', this.$element).click();
        }
    };

    /**
     * Operation definition.
     * @param {Grido} Grido
     */
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
            if (!$('th.checker', this.grido.$table).length) {
                return null;
            }

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
            $(this.selector + ':checked', this.grido.$table).length === 0 && this.controlState('disabled');
        },

        /**
         * Click on checkbox with shift support.
         */
        bindClickOnCheckbox: function()
        {
            var that = this;
            $(this.selector, this.grido.$table)
                .off('click.grido')
                .on('click.grido', function(event, data) {
                    if(event.shiftKey || (data && data.shiftKey)) {
                        var $boxes = $(that.selector, that.grido.$table),
                            start = $boxes.index(this),
                            end = $boxes.index(that.$last);

                        $boxes.slice(Math.min(start, end), Math.max(start, end))
                            .attr('checked', that.$last.checked)
                            .trigger('change');
                    }

                    that.$last = this;
                });
        },

        bindClickOnRow: function()
        {
            var that = this;
            $('tbody td:not(.checker,.actions a)', this.grido.$table)
                .off('click.grido')
                .on('click.grido', function(event) {
                    if (event.shiftKey) {
                        that.disableSelection.call(that);
                    }

                    $('[type=checkbox]', $(this).parent()).click();

                    if (event.shiftKey) {
                        that.enableSelection.call(that);
                    }
                });
        },

        bindClickOnInvertor: function()
        {
            var that = this;
            $('th.checker [type=checkbox]', this.grido.$table)
                .off('click.grido')
                .on('click.grido', function() {
                    $(that.selector, that.grido.$table).each(function() {
                        var val = $(this).prop('checked');
                        $(this).prop('checked', !val);
                        that.changeRow($(this).closest('tr'), !val);
                    });

                    return false;
                });
        },

        bindChangeOnCheckbox: function()
        {
            var that = this;
            $(this.selector, this.grido.$table)
                .off('change.grido')
                .on('change.grido', function() {
                    $.proxy(that.changeRow, that)($(this).closest('tr'), $(this).prop('checked'));
                });
        },

        bindChangeOnSelect: function()
        {
            var that = this;
            $('.operations [name="operations[operations]"]', this.grido.$table)
                .off('change.grido')
                .on('change.grido', function() {
                    $(this).val() && $('.operations [type=submit]', that.grido.$table).click();
                });
        },

        bindClickOnButton: function()
        {
            $('.operations [type=submit]', this.grido.$table)
                .off('click.grido')
                .on('click.grido', $.proxy(this.onSubmit, this));
        },

        disableSelection: function()
        {
            this.grido.$table
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

            this.grido.$table
                .attr('unselectable', 'off')
                .attr('style', null);
        },

        /**
         * Returns operation select.
         * @returns {jQuery}
         */
        getSelect: function()
        {
            return $('.operations [name="operations[operations]"]', this.grido.$table);
        },

        /**
         * @param {jQuery} $row
         * @param {bool} selected
         */
        changeRow: function($row, selected)
        {
            selected
                ? $row.addClass('active')
                : $row.removeClass('active');

            $(this.selector + ':checked', this.grido.$table).length === 0
                ? this.controlState('disabled')
                : this.controlState('enabled');
        },

        onSubmit: function()
        {
            var hasConfirm = this.getSelect().data('grido-' + this.getSelect().val());
            if (hasConfirm) {
                if (confirm(hasConfirm.replace(/%i/g, $(this.selector + ':checked', this.grido.$table).length))) {
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
            var $button = $('[name="buttons[operations]"]', this.grido.$table);
            if (state === 'disabled') {
                this.getSelect().attr('disabled', 'disabled').addClass('disabled');
                $button.addClass('disabled');
            } else {
                this.getSelect().removeAttr('disabled').removeClass('disabled');
                $button.removeClass('disabled');
            }
        }
    };

    /**
     * Ajax definition.
     * @param {Grido} Grido
     */
    Grido.Ajax = function(Grido)
    {
        this.grido = Grido;
    };

    Grido.Ajax.prototype =
    {
        init: function()
        {
            if (!this.grido.options.ajax) {
                return null;
            }

            this.registerSuccessEvent();
            this.registerHashChangeEvent();

            return this;
        },

        registerSuccessEvent: function()
        {
            var that = this;
            this.grido.$element.bind('success.ajax.grido', function(event, payload) {
                $.proxy(that.handleSuccessEvent, that)(payload);
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
            var that = this,
                params = {},
                snippet = 'snippet-' + this.grido.name + '-grid';

            if (payload && payload.snippets && payload.snippets[snippet] && payload.state) { //is ajax update?
                $.each(payload.state, function(key, val) {
                    if ((val || val === 0) && key.indexOf('' + that.grido.name + '-') >= 0) {
                        params[key] = val;
                    }
                });

                var hash = /mozilla/i.test(navigator.userAgent) && !/webkit/i.test(navigator.userAgent)
                    ? $.param(params)
                    : this.coolUri($.param(params));

                $.data(document, this.grido.name + '-state', hash);
                location.hash = hash;
            }
        },

        handleHashChangeEvent: function()
        {
            var state = $.data(document, this.grido.name + '-state') || '',
                hash = location.toString().split('#').splice(1).join('#');

            if (hash.indexOf(this.grido.name + '-') >= 0 && state !== hash) {
                var url = location.toString();
                url = url.indexOf('?') >= 0 ? url.replace('#', '&') : url.replace('#', '?');

                this.doRequest(url + '&do=' + this.grido.name + '-refresh');
            }
        },

        /**
         * Load data from the server using a HTTP GET request.
         * @param {String} url
         */
        doRequest: function(url)
        {
            $.get(url);
        },

        /**
         * Own decodeURIComponent() implementation.
         * @param {String} encodedUri
         */
        coolUri: function(encodedUri)
        {
            var cool = encodedUri,
                replace = {'%5B': '[', '%5D': ']', '%E2%86%91' : '↑', '%E2%86%93' : '↓'};

            $.each(replace, function(key, val) {
                cool = cool.replace(key, val);
            });

            return cool;
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
    return Grido;

})(jQuery, window, document, location, navigator);
