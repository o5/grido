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

        onInit: [],

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

            for (var i in this.onInit) {
                this.onInit[i](this);
            }

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
                        isAjax && (that.ajax.doRequest(this.href, this, event) || stop(event));

                    } else if (hasConfirm) {
                        stop(event);

                    } else if (isAjax) {
                        that.ajax.doRequest(this.href, this, event) || stop(event);
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
                        window.location = location;
                    } else if (page) {
                        window.alert('Page is out of range.');
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

        initEditable: function()
        {
            var that = this;
            $('td[class*="grid-cell-"]', this.$element)
                .off('dblclick.grido')
                .on('dblclick.grido', function(event) {
                    if (helpers.isCtrl(event) && !$(this).hasClass('edit')) {
                        this.editable = new Grido.Editable(that).init($(this));
                    }
            });
        },

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
            this.initActiveRows();
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

        initActiveRows: function()
        {
            $(this.selector + ':checked', this.grido.$table).closest('tr').addClass('active');
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
                    if ($(this).hasClass('edit')) {
                        return;
                    }

                    if (event.shiftKey) {
                        that.disableSelection.call(that);
                    }

                    $(that.selector, $(this).closest('tr')).click();

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
            var hasConfirm = this.getSelect().attr('data-grido-confirm-' + this.getSelect().val());
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
            this.registerPopState();

            return this;
        },

        registerSuccessEvent: function()
        {
            var that = this;
            this.grido.$element
                .off('success.ajax.grido')
                .on('success.ajax.grido', function(event, payload) {
                    $.proxy(that.handleSuccessEvent, that)(payload);
                    event.stopImmediatePropagation();
                });
        },

        registerPopState: function()
        {
            var that = this;
            $(window)
                .off('popstate.ajax.grido')
                .on('popstate.ajax.grido', function(event) {
                    $.proxy(that.onPopState, that)(event);
                    event.stopImmediatePropagation();
                });
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

                var query = this.getQueryString(params);

                $.data(document, this.grido.name + '-query', query);
                this.onSuccessEvent(params, query);
            }
        },

        /**
         * @param {Object} params
         * @returns {String}
         */
        getQueryString: function(params)
        {
            var queryString;
            if ($.isEmptyObject(params)) {
                var newParams = {};
                var oldParams = this.getQueryParams();
                for (var key in oldParams) {
                    if (key.indexOf(this.grido.name) !== 0) {
                        newParams[key] = oldParams[key];
                    }
                }

                queryString = $.isEmptyObject(newParams)
                    ? window.location.pathname
                    : '?' + $.param(newParams);

            } else {
                queryString = '?' + $.param($.extend(this.getQueryParams(), params));
            }

            return this.coolUri(queryString);
        },

        /**
         * @param {Object} params - grido params
         * @param {String} url
         */
        onSuccessEvent: function(params, url)
        {
            window.history.pushState(params, document.title, url);
        },

        /**
         * @param Event event
         */
        onPopState: function(event)
        {
            var state = $.data(document, this.grido.name + '-query') || '',
                query = window.location.search;

            if (state !== query) {
                var url = this.getRefreshGridHandlerUrl(this.grido.$element);
                this.doRequest(url);
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
                cool = cool.replace(new RegExp(key, 'g'), val);
            });

            return cool;
        },

        /**
         * Returns url for AJAX call to Refresh grid.
         * @param {jQuery} $form
         * @return {String} Url from data atribute of form
         */
        getRefreshGridHandlerUrl: function($form)
        {
            return $form.data('grido-refresh-handler');
        },

        /**
         * Returns window.location.search as object
         * @link http://jsbin.com/adali3/2
         * @link http://stackoverflow.com/questions/901115/how-can-i-get-query-string-values-in-javascript/2880929#2880929
         */
        getQueryParams: function ()
        {
            var e,
                d = function (s) {
                    return decodeURIComponent(s).replace(/\+/g, " ");
                },
                q = window.location.search.substring(1),
                r = /([^&=]+)=?([^&]*)/g,
                params = {};

            while (e = r.exec(q)) {
                if (e[1].indexOf("[") === -1) {
                    params[d(e[1])] = d(e[2]);
                } else {
                    var b1 = e[1].indexOf("["),
                        aN = e[1].slice(b1 + 1, e[1].indexOf("]", b1)),
                        pN = d(e[1].slice(0, b1));

                    if (typeof params[pN] !== "object") {
                        params[d(pN)] = {};
                    }

                    if (aN) {
                        params[d(pN)][d(aN)] = d(e[2]);
                    } else {
                        Array.prototype.push.call(params[d(pN)], d(e[2]));
                    }
                }
            }

            return params;
        }
    };

    /**
     * Inline editable definition.
     * @author Jakub Kopřiva <kopriva.jakub@gmail.com>
     * @param {Grido} Grido
     */
    Grido.Editable = function(Grido)
    {
        this.grido = Grido;
    };

    Grido.Editable.prototype =
    {
        init: function($td)
        {
            if (this.grido.options.ajax !== true || this.grido.options.editable !== true) {
                return null;
            }

            this.td = $td.addClass('edit');
            this.th = this.getColumnHeader(this.td);

            if (this.getEditHandlerUrl(this.th)) {
                this.value = this.getValue(this.td);
                this.oldValue = this.getOldValue(this.td);
                this.primaryKey = this.getPrimaryKeyValue(this.td.parent());

                this.componentName = this.getComponentName(this.th);
                this.editControlHtml = this.getEditControl(this.componentName, this.getEditControlHandlerUrl(this.th));
                this.renderEditControl(this.td, this.editControlHtml);

                this.editControlObject = this.getEditControlObject(this.td);
                this.setFocus(this.editControlObject);
                this.initBindings(this.editControlObject);
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
        getComponentName: function($th)
        {
            var handler = this.getEditControlHandlerUrl($th).replace('/[\.d]*/g', '');
            handler = handler.match(/[\??\&?](do=)+(.*)/)[2];

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

        /**
         * Returns raw value for editable control.
         * @param {jQuery} $td
         * @returns {String}
         */
        getValue: function($td)
        {
            return $td.data('grido-editable-value');
        },

        /**
         * Returns value in the cell.
         * @param {jQuery} $td
         * @returns {jQuery}|{String}
         */
        getOldValue: function($td)
        {
            return $td.children().length > 0
                ? $td.children()
                : $td.text().trim();
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

            data[componentName + '-value'] = this.value;

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
        saveData: function(oldValue, componentName, primaryKey, $th, $td, oldHtml)
        {
            var data = {},
                that = this,
                newValue = $('[type=checkbox]', this.editControlObject).length
                    ? ~~+ $('[type=checkbox]', this.editControlObject).is(':checked')
                    : this.editControlObject.val();

            if (newValue === oldValue) {
                $td.html(oldHtml);
                return;
            }

            data[componentName + '-id'] = primaryKey;
            data[componentName + '-newValue'] = newValue;
            data[componentName + '-oldValue'] = oldValue;

            $.ajax({
                type: "POST",
                url: this.getEditHandlerUrl($th),
                data: data,
                async: true
            })
            .success(function(data) {
                if (data.updated === true) {
		    if (data.html) {
			$td.html(data.html);
		    } else {
			$td.html(newValue);
		    }
                    $td.data('grido-editable-value', newValue);
                    that.oldValue = newValue;
                    that.flashSuccess($td);
                } else {
                    that.flashError($td);
                    that.revertChanges($td);
                }
            });
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
                    $td.css('background', '');
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
                    $td.css('background', '');
                }
            }, 1 );
        },

        /**
         * Init key bindings to an edited control.
         * @param {jQuery} $control
         */
        initBindings: function($control)
        {
            var that = this;
            var keypress = function(event)
            {
                if (event.keyCode === 13) { //enter
                    if (this.tagName === 'TEXTAREA') {
                        return;
                    }

                    saveData(this);
                    event.preventDefault();
                    return false;
                }
            };
            var keydown = function(event)
            {
                if (event.keyCode === 27) { //esc
                    that.revertChanges(that.td);
                    that.td.removeClass('edit');

                    event.preventDefault();
                    return false;
                } else if (helpers.isCtrl(event) && event.keyCode === 13) { //CTRL/CMD + ENTER
                    saveData(this);

                    event.preventDefault();
                    return false;
                }
            };
            var saveData = function(element)
            {
                if (typeof window.Nette === 'object' && !window.Nette.validateControl(element)) {
                    return false; //validation fail
                } else {
                    that.saveData(that.value, that.componentName, that.primaryKey, that.th, that.td, that.oldValue);
                    that.td.removeClass('edit');
                }
            };

            $control
                .off('keypress.grido')
                 .on('keypress.grido', keypress)
                .off('keydown.grido')
                 .on('keydown.grido',  keydown);
        }
    };

    /*        GRIDO HELPERS       */
    /* ========================== */

    var helpers =
    {
        isCtrl: function(event)
        {
            return (event.ctrlKey || event.metaKey) && !event.altKey;
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
