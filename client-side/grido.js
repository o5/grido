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
 */
;(function($, window, document, location) {
    /*jshint laxbreak: true, expr: true */
    "use strict";

    var Grido = Grido || {};

    /*    GRID DEFINITION   */
    /* ========================== */

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
            this.initFilters();
            this.initItemsPerPage();
            this.initActions();
            this.initPagePrompt();
            this.initOperation();
            this.initCheckNumeric();
            this.initAjax();
            this.initInlineEditing();
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
            $('.actions a', this.$table)
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
            $('.paginator .prompt', this.$table)
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
            if ($('th.checker', this.$table).length) {
                this.operation = new Grido.Operation(this).init();
            }
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
                        pattern = new RegExp(/[^<>=\\.\\,\-0-9]+/g); //TODO: improve my regex knowledge :)

                    pattern.test(value) && $(this).val(value.replace(pattern, ''));
                });
        },

        initAjax: function()
        {
            this.options.ajax && new Grido.Ajax(this).init();
        },

        initInlineEditing: function()
        {
            if (this.options.editable === true && this.options.ajax === true) {
                $('td[class*="grid-cell-"]', this.$element)
                    .off('dblclick.grido')
                    .on('dblclick.grido', function(event) {
                        if (event.metaKey || event.ctrlKey) {
                            var col = $(this);

                            var headerClass;
                            var classList = col.attr('class').replace('cell','header').split(/\s+/);
                            for (var i = 0; i < classList.length; i++) {
                                if (classList[i].indexOf('-header-') !== -1) {
                                  headerClass = classList[i];
                                }
                            }
                            var colNameList = headerClass.split(/\-/);
                            var colName = colNameList[colNameList.length - 1];

                            var header = $('th[class~="'+headerClass+'"]');

                            col.isInlineEditable = function() {
                                var gridoOptions = $(this).closest('table').data("gridoOptions");
                                if (gridoOptions.editable === true) {
                                    if (header.data('grido-editable-handler')) {
                                        return true;
                                    }
                                }
                                return false;
                            };
                            if (col.isInlineEditable()) {
                                var row = $(this).parent();
                                var oldValue = col.html().trim();
                                var rowClass = row.attr('class');

                                var regex = /[grid\-row\-]([0-9]+)/;
                                var matches = rowClass.match(regex);
                                var primaryKey = matches[1];

                                var editControlHandler = header.data('grido-editablecontrol-handler');

                                var handlerCompName = editControlHandler.replace('/[\.d]*/g', '');
                                var regex = /[\?][do=]+(.*)/;
                                var matches = handlerCompName.match(regex);
                                handlerCompName = matches[1];

                                var dataForControl = {};
                                var regex = /(.*)\-edit/;
                                var matches = handlerCompName.match(regex);
                                handlerCompName = matches[1];

                                var d1 = handlerCompName+'-oldValue';
                                dataForControl[d1] = oldValue;

                                var editControl;
                                $.ajax({
                                        type: "GET",
                                        url: editControlHandler,
                                        data: dataForControl,
                                        async: false
                                })
                                .success(function(data) {
                                    editControl = data;
                                });

                                $(this).html(editControl);

                                var editControlObject = $(this).children();

                                editControlObject.save = function() {

                                    var newValue = editControlObject.val();
                                    $(this).parent().html(newValue);
                                    if (oldValue === newValue) {
                                        return;
                                    }
                                    // HANDLE SAVE
                                    var d1 = handlerCompName+'-primaryKey';
                                    var d2 = handlerCompName+'-oldValue';
                                    var d3 = handlerCompName+'-newValue';
                                    var d4 = handlerCompName+'-columnName';
                                    var data = {};
                                    data[d1]=primaryKey;
                                    data[d2]=oldValue;
                                    data[d3]=newValue;
                                    data[d4]=colName;

                                    var editHandler = header.data('grido-editable-handler');

                                    $.ajax({
                                        type: "GET",
                                        url: editHandler,
                                        data: data
                                    })
                                    .success(function(data) {
                                        if (data.updated === 'true') {
                                            var transp = 0;
                                            var multiplicator = 1;
                                            var timer = setInterval(function() {
                                                transp += (multiplicator * 0.01);
                                                col.css('background', 'rgba(196,234,195,'+transp+')');
                                                if (transp >=1) {
                                                    multiplicator = -1;
                                                }
                                                if (transp <= 0) {
                                                    clearInterval(timer);
                                                }
                                            }, 1 );
                                        } else {
                                            var transp = 0;
                                            var multiplicator = 1;
                                            var timer = setInterval(function() {
                                                transp += (multiplicator * 0.01);
                                                col.css('background', 'rgba(240,54,69,'+transp+')');
                                                if (transp >=1) {
                                                    multiplicator = -1;
                                                }
                                                if (transp <= 0) {
                                                    clearInterval(timer);
                                                }
                                            }, 1 );
                                        }
                                    });
                                };
                                editControlObject.storno = function() {
                                    $(this).parent().html(oldValue);
                                };
                                if (editControlObject[0].type === 'text') {
                                    editControlObject.focus();
                                }
                                editControlObject.bind('keypress', function(e) {
                                    if (e.keyCode === 13) {
                                        editControlObject.save();
                                        e.preventDefault();
                                    }
                                    if (e.keyCode === 27) {
                                        editControlObject.storno();
                                        e.preventDefault();
                                    }
                                });
                            }
                        }
                    });
            }
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

    /* OPERATION DEFINITION */
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
            $(this.selector + ':checked', this.grido.$table).length === 0 && this.controlState('disabled');
        },

        /**
         * Click on checkbox with shift support.
         */
        bindClickOnCheckbox: function()
        {
            var _this = this;
            $(this.selector, this.grido.$table)
                .off('click.grido')
                .on('click.grido', function(event, data) {
                    if(event.shiftKey || (data && data.shiftKey)) {
                        var $boxes = $(_this.selector, _this.grido.$table),
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
            $('tbody td:not(.checker,.actions a)', this.grido.$table)
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
            $('th.checker [type=checkbox]', this.grido.$table)
                .off('click.grido')
                .on('click.grido', function() {
                    $(_this.selector, _this.grido.$table).each(function() {
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
            $(this.selector, this.grido.$table)
                .off('change.grido')
                .on('change.grido', function() {
                    $.proxy(_this.changeRow, _this)($(this).closest('tr'), $(this).prop('checked'));
                });
        },

        bindChangeOnSelect: function()
        {
            var _this = this;
            $('.operations [name="operations[operations]"]', this.grido.$table)
                .off('change.grido')
                .on('change.grido', function() {
                    $(this).val() && $('.operations [type=submit]', _this.grido.$table).click();
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

    /*    AJAX DEFINITION   */
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

                var hash = $.browser.mozilla ? $.param(params) : this.coolUri($.param(params));
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
         * @param {string} url
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

})(jQuery, window, document, location);
