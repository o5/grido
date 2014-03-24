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
            this.ajax = new Grido.Ajax(this).init();
            this.operation = new Grido.Operation(this).init();

            this.initFilters();
            this.initItemsPerPage();
            this.initActions();
            this.initPagePrompt();
            this.initCheckNumeric();
            this.initEditable();
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
            var _this = this;
            $('.actions a', this.$table)
                .off('click.nette')
                .off('click.grido')
                .on('click.grido', function(event) {
                    var hasConfirm = $(this).data('grido-confirm');
                    if (hasConfirm && !confirm(hasConfirm)) {
                        event.preventDefault();
                        event.stopImmediatePropagation();
                    } else if (hasConfirm && $(this).hasClass('ajax') && _this.ajax) {
                        _this.ajax.doRequest(this.href);
                        event.preventDefault();
                        event.stopImmediatePropagation();
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

        initEditable: function()
        {
            var _this = this;
            $('td[class*="grid-cell-"]', this.$element)
                .off('dblclick.grido')
                .on('dblclick.grido', function(event) {
                    if (event.metaKey || event.ctrlKey) {
                        this.editable = new Grido.Editable(_this).init($(this));
                    }
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
                    if ($(this).hasClass('edit')) {
                        event.preventDefault();
                        return false;
                    }

                    if (event.shiftKey) {
                        _this.disableSelection.call(_this);
                    }

                    $('[type=checkbox]', $(this).parent()).click();

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
            if (!this.grido.options.ajax) {
                return null;
            }

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

    /*  INLINE EDITABLE DEFINITION  */
    /* ========================== */

    Grido.Editable = function(Grido)
    {
        this.grido = Grido;
    };

    Grido.Editable.prototype =
    {
        init: function($td)
        {
            if (this.grido.options.ajax !== true && this.grido.options.editable !== true) {
                return null;
            }

            var _this = this;

            _this.td = $td;
            _this.th = _this.getColumnHeader(_this.td);

            if (_this.getEditHandlerUrl(_this.th)) {
                _this.tr = _this.td.parent();
                _this.oldValue = _this.getOldValue(_this.td);
                _this.primaryKey = _this.getPrimaryKeyValue(_this.tr);
                _this.componentHandlerName = _this.getComponentHandlerName(_this.th);
                _this.editControlHandlerUrl = _this.getEditControlHandlerUrl(_this.th);
                _this.editControlHtml = _this.getEditControl(_this.componentHandlerName, _this.editControlHandlerUrl);
                _this.renderEditControl(_this.td, _this.editControlHtml);
                _this.editControlObject = _this.getEditControlObject(_this.td);
                _this.setFocus(_this.editControlObject);
                _this.initBindings(_this.editControlObject);
            }
            return this;
        },

        /**
         * Returns column <th> object.
         * @param {jQuery} $td cell
         * @return {jQuery} header cell of column
         */
        getColumnHeader: function($td)
        {
            var headerClass;
            var classList = $td.attr('class').replace('cell','header').split(/\s+/);
            for (var i = 0; i < classList.length; i++) {
                if (classList[i].indexOf('-header-') !== -1) {
                    headerClass = classList[i];
                }
            }
            return $('th[class~="' + headerClass + '"]');
        },

        /**
         * Returns value of primary key.
         * @param {jQuery} $tr row
         * @return {String} Primary key value
         */
        getPrimaryKeyValue: function($tr)
        {
            return $tr.attr('class').match(/[grid\-row\-]([0-9]+)/)[1];
        },

        /**
         * Returns name of component for AJAX params calls.
         * @param {jQuery} $th header cell
         * @return {String} component name for AJAX params calls
         */
        getComponentHandlerName: function($th)
        {
            var handler = this.getEditControlHandlerUrl($th).replace('/[\.d]*/g', '');
            handler = handler.match(/[\?][do=]+(.*)/)[1];

            return handler.match(/(.*)\-edit/)[1];
        },

        /**
         * Returns url for AJAX editable handler.
         * @param {jQuery} $th header cell
         * @return {String} Url from data atribute of header cell
         */
        getEditHandlerUrl: function($th)
        {
            return $th.data('grido-editable-handler');
        },

        /**
         * Returns url for AJAX call to Editable class.
         * @param {jQuery} $th header cell
         * @return {String} Url from data atribute of header cell
         */
        getEditControlHandlerUrl: function($th)
        {
            return $th.data('grido-editablecontrol-handler');
        },

        getOldValue: function($td)
        {
            return $td.data('grido-editable-value');
        },

        /**
         * Returns html of control.
         * @param {String} componentName component name for AJAX call
         * @param {String} handleLink url for AJAX call
         * @return {String} HTML of control
         */
        getEditControl: function(componentName, handleLink)
        {
            var control,
                data = {};

            data[componentName + '-value'] = this.oldValue;

            $.ajax({
                type: "GET",
                url: handleLink,
                data: data,
                async: false
            })
            .success(function(data) {
                control = data;
            });

            return control;
        },

        /**
         * Render html in cell.
         * @param {jQuery} $td cell
         * @param {String} $html HTML
         */
        renderEditControl: function($td, $html)
        {
            $td.html($html);
        },

        /**
         * Returns Children of cell.
         * @param {jQuery} $td cell
         * @return {jQuery} children of cell
         */
        getEditControlObject: function($td)
        {
            return $td.children();
        },

        /**
         * Sets focus in text input if type="text".
         * @param {jQuery} $editControlObject input
         */
        setFocus: function($editControlObject)
        {
            if ($editControlObject[0].type === 'text') {
                $editControlObject.focus();
            }
        },

        /**
         * AJAX call to Editable handler for saving data.
         * @param {String} oldValue value of cell before edit was done
         * @param {String} componentName name of component handler for AJAX params
         * @param {String} primaryKey value of primary key
         * @param {jQuery} $th header cell of column
         * @param {jQuery} $td edited cell
         */
        saveData: function(oldValue, componentName, primaryKey, $th, $td)
        {
            var newValue = this.editControlObject.val();
            if (oldValue === newValue) {
                $td.html(newValue);
                return;
            }

            var data = {};
            data[componentName+'-id'] = primaryKey;
            data[componentName+'-value'] = newValue;
            data[componentName+'-prevValue'] = this.oldValue;

            var that = this;
            $.ajax({
                type: "GET",
                url: this.getEditHandlerUrl($th),
                data: data,
                async: false
            })
            .success(function(data) {
                if (data.updated === true) {
                    $td.html(newValue);
                    $td.data('grido-editable-value', newValue);
                    that.oldValue = newValue;
                    that.flashSuccess($td);
                } else {
                    that.flashError($td);
                    that.revertChanges($td);
                }
            },{that: this});
        },

        /**
         * Revert changes in cell.
         * @param {jQuery} $td edited cell
         */
        revertChanges: function($td)
        {
            $td.html(this.oldValue);
        },

        /**
         * Provide feedback to user, cell was succesfully changed.
         * @param {jQuery} $td edited cell
         */
        flashSuccess: function($td)
        {
            var transp = 0;
            var multiplicator = 1;
            var timer = setInterval(function() {
                transp += (multiplicator * 0.01);
                $td.css('background', 'rgba(196,234,195,'+transp+')');
                if (transp >=1) {
                    multiplicator = -1;
                }
                if (transp <= 0) {
                    clearInterval(timer);
                }
            }, 1 );
        },

        /**
         * Provide feedback to user, cell was not changed.
         * @param {jQuery} $td edited cell
         */
        flashError: function($td)
        {
            var transp = 0;
            var multiplicator = 1;
            var timer = setInterval(function() {
                transp += (multiplicator * 0.01);
                $td.css('background', 'rgba(240,54,69,'+transp+')');
                if (transp >=1) {
                    multiplicator = -1;
                }
                if (transp <= 0) {
                    clearInterval(timer);
                }
            }, 1 );
        },

        /**
         * Init key bindings to an edited control.
         * @param {jQuery} $control
         */
        initBindings: function($control)
        {
            var _this;
            $control.on('keypress.grido', function(event) {
                switch (event.keyCode) {
                    case 13: //enter
                        if (typeof window.Nette === 'object' && !window.Nette.validateControl(this)) {
                            event.preventDefault();
                            break;
                        }

                        _this.saveData(_this.oldValue, _this.componentHandlerName, _this.primaryKey, _this.th, _this.td);
                        _this.td.removeClass('edit');
                        event.preventDefault();

                        break;

                    case 27: //esc
                        _this.revertChanges(_this.td);
                        _this.td.removeClass('edit');
                        event.preventDefault();

                        break;
                }
            });
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
