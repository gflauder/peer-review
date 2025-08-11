'use strict';

var parsley = require('parsleyjs');
var timeago = global.__timeago = require('timeago.js');
var selectizeRender = {};
var bootstrap = require('bootstrap');
// Polyfill for MSIE
Number.isInteger = Number.isInteger || function (value) {
    return typeof value === 'number' && isFinite(value) && Math.floor(value) === value;
};

$().ready(function () {
    var lang = $('html').attr('lang') || 'en';
    var excludedInputs = 'input[type=button], input[type=submit], input[type=reset], input[type=hidden], .rich-edit';
    var formExcludedInputs = excludedInputs + ', .input-like input';
    var regexEMail = /^[^@ ]+@[^@ .]+\.[^@ ]+$/;
    var regexNameEMail = /^([^<]+)<([^@ ]+@[^@ .]+\.[^@ ]+)>$/;

/*
    $('.hideaway-focus').on('focus', function () {
        $(this).closest('.hideaway').css({ position: 'relative', left: 0 });
    });
*/
    $('input[name="email"]').on('keydown', function(e) {
        if (e.key === "Tab") {
            $('.hideaway').show();
        }
    });

    // Clear the password value on form submission if not revealed
    $('#login-form').on('submit', function() {
        if ($('.hideaway').is(':hidden')) {
            $('#password').val('');
        }
    });


    setTimeout(function () {
        $('input.input-password').attr('type', 'password');
    }, 5000);

    $('.no-submit').on('keypress keydown keyup', function (ev) {
        if (ev.which === 13) {
            ev.preventDefault();
            ev.stopPropagation();
            return false;
        }
        return true;
    });

    $('form.form-leftright, form.form-tight').each(function () {
        $(this).attr('name', $(this).attr('id')).addClass('form-horizontal');
    });

    $('form.form-leftright > input, form.form-leftright > .btn-group, form.form-leftright > select, form.form-leftright > textarea, form.form-leftright > .input-like')
        .not(formExcludedInputs)
        .each(function () {
            var id = $(this).attr('id');
            var label = $(this).attr('data-label');
            var fgClasses = '';
            var icon = null;

            if ($(this).is('[class*="feedback-"]')) {
                fgClasses = ' has-feedback has-feedback-left';
                icon = $(this).attr('class').match(/\bfeedback-([a-zA-Z0-9_-]+)\b/)[1];
            }

            if (!$(this).hasClass('btn-group') && !$(this).hasClass('input-group')) {
                $(this).addClass('form-control');
            }

            $(this).attr('name', id).wrap('<div class="form-group' + fgClasses + '"></div>').parent().prepend(
                '<label for="' + id + '" class="col-sm-2 control-label">' + label + '</label>'
            );
            $(this).wrap('<div class="col-sm-10"></div>');
            if (icon) {
                $(this).after('<span class="form-control-feedback bi bi-' + icon + '"></span>');
            }
        });

    $('form.form-tight input, form.form-tight select, form.form-tight textarea')
        .not(formExcludedInputs)
        .each(function () {
            var id = $(this).attr('id');
            var label = $(this).attr('data-label');
            var colsize = $(this).attr('data-colsize') || 6;
            var fgClasses = '';
            var icon = null;

            if ($(this).is('[class*="feedback-"]')) {
                fgClasses = ' has-feedback has-feedback-left';
                icon = $(this).attr('class').match(/\bfeedback-([a-zA-Z0-9_-]+)\b/)[1];
            }

            $(this).attr('name', id).addClass('form-control').wrap(
                '<div class="input-block col-sm-' + colsize + fgClasses + '"></div>'
            ).parent().prepend('<label for="' + id + '">' + label + '</label>');
            if (icon) {
                $(this).after('<span class="form-control-feedback bi bi-' + icon + '"></span>');
            }
        });

    $('form.form-leftright button, form.form-tight button[type=submit]')
        .not('.input-like button, .form-group button')
        .each(function () {
            $(this).addClass('btn btn-default').wrap('<div class="form-group"></div>').wrap('<div class="col-sm-offset-2 col-sm-10"></div>');
        });

    $('form.form-compact').each(function () {
        $(this).attr('name', $(this).attr('id')).addClass('form-inline');
    });

    $('form.form-compact input, form.form-compact select, form.form-compact textarea')
        .not(formExcludedInputs)
        .each(function () {
            var id = $(this).attr('id');
            var label = $(this).attr('data-label');

            $(this).attr('name', id).addClass('form-control').wrap('<div class="form-group"></div>').parent().prepend(
                '<label for="' + id + '" class="sr-only">' + label + '</label>'
            );
        });

    $('form.form-compact button').each(function () {
        $(this).addClass('btn btn-default');
    });

    $('.fileupload-single input[type="file"]').each(function () {
        var widget = $(this).closest('.fileupload-single');
        var nameTag = widget.find('.file-name');
        var button = widget.find('button[type="submit"], input[type="submit"]');

        nameTag.hide();
        $(this).on('change', function () {
            nameTag.text($(this).val()).show();
            button.removeClass('disabled').attr('disabled', false);
        });
    });

    $('.fileupload-multiple input[type="file"]').each(function () {
        var wid = $(this).closest('.fileupload-multiple');
        var id = wid.attr('data-basename') + '_' + wid.attr('data-i');

        wid.find('.file-name, .filelabel-change').hide();
        wid.find('label').attr('for', id);
        $(this).attr('name', id).attr('id', id);

        $(this).on('change', function () {
            var widget = $(this).closest('.fileupload-multiple');
            var newWidget = widget.clone(true, true);
            var newInput = newWidget.find('input[type="file"]');
            var newId;

            newWidget.attr('data-i', parseInt(newWidget.attr('data-i'), 10) + 1);
            newId = newWidget.attr('data-basename') + '_' + newWidget.attr('data-i');
            newWidget.find('label').attr('for', newId);
            newInput.attr('name', newId).attr('id', newId).val(null);

            widget.find('.file-name').text($(this).val()).show();
            widget.find('.filelabel-add, .filelabel-change').toggle();
            widget.after('<br />', newWidget);
        });
    });

    selectizeRender = {
        option_create: function (data, escape) {
            return '<div class="create">+ <strong>' + escape(data.input) + '</strong>&hellip;</div>';
        }
    };

    $('select.advanced').selectize({
        plugins: ['remove_button', 'drag_drop'],
        highlight: false
    });

    $('select.keywords').each(function () {
        var maxItems = $(this).attr('data-maxcount');

        $(this).selectize({
            plugins: ['remove_button', 'drag_drop'],
            highlight: false,
            delimiter: ';',
            create: true,
            createOnBlur: true,
            openOnFocus: false,
            maxItems: maxItems,
            render: selectizeRender
        });
    });

    function itemToUser(item) {
        var match;

        item.text = item.text.trim();
        if (item['email'] === undefined) {
            match = item.text.trim().match(regexNameEMail);
            if (match !== null) {
                item.email = match[2];
                item.name = match[1];
            } else {
                item.email = item.text;
                item.name = null;
            }
        }
        if (item['value'] === undefined || !Number.isInteger(Number(item['value']))) {
            // Don't assign email as value until user is complete
            item.value = item.email;
            item.isTemporary = !item.name; // Mark as temporary if no name
        }


        // ADD validation status to item
        item.isComplete = !!(item.email && item.email.trim() && item.name && item.name.trim());

        return item;
    }

    // ADD: New validation function
    function validateCompleteUser(item) {
        if (!item.email || item.email.trim() === '') {
            return { valid: false, message: 'Email is required' };
        }

        if (!item.name || item.name.trim() === '') {
            return { valid: false, message: 'Name is required' };
        }

        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(item.email)) {
            return { valid: false, message: 'Valid email format required' };
        }

        return { valid: true };
    }

// ADD: Function to validate all users in selectize
    function validateAllSelectizeUsers(selectizeInstance) {
        const items = selectizeInstance.getValue();
        const incompleteUsers = [];

        items.forEach(itemValue => {
            const item = selectizeInstance.options[itemValue];
            if (item && !item.isComplete) {
                incompleteUsers.push(item);
            }
        });

        return {
            valid: incompleteUsers.length === 0,
            incompleteUsers: incompleteUsers
        };
    }

    selectizeRender['item'] = function (item, escape) {
        item = itemToUser(item);
        return '<div class="name_email_tag">' + (item.name ? '<span class="name">' + escape(item.name) + '</span>' : '') + '<span class="email">' + escape(item.email) + '</span></div>';
    };

    selectizeRender['option'] = function (item, escape) {
        var label;
        var caption;
        var extras = '';

        item = itemToUser(item);
        label = item.name || item.email;
        caption = item.name ? item.email : null;

        Object.keys(item).forEach(function (labelProp) {
            var valProp;

            valProp = labelProp.match(/^label(.*)$/);
            if (valProp !== null) {
                valProp = 'val' + valProp[1];
                extras += '<span class="extra">' + item[labelProp] + ': ' + item[valProp] + '</span>';
            }
        });

        return '<div class="name_email_label"><span class="name">' + escape(label) + '</span>' + (caption ? '<span class="email">' + escape(caption) + '</span>' : '') + extras + '</div>';
    };

    $('select.users').each(function () {
        var maxItems = $(this).attr('data-maxcount');

        if (maxItems === undefined) {
            maxItems = null;
        }
        $(this).selectize({
            plugins: ['remove_button', 'drag_drop'],
            highlight: false,
            persist: false,
            maxItems: maxItems,
            render: selectizeRender
        });
    });

    $('select.users-create').each(function () {
        var maxItems = $(this).attr('data-maxcount');
        if (maxItems === undefined) {
            maxItems = null;
        }
        $(this).selectize({
            plugins: ['remove_button', 'drag_drop'],
            highlight: false,
            createOnBlur: true,
            persist: false,
            maxItems: maxItems,
            render: selectizeRender,
            onInitialize: function () {
                var sel = this;

                this.revertSettings.$children.each(function () {
                    $.extend(sel.options[this.value], $(this).data());
                });
            },
            createFilter: function (input) {
                return regexEMail.test(input) || input.match(regexNameEMail) !== null;
            },
            create: function (input) {
                var match = input.match(regexNameEMail);
                var opt = false;

                if (match !== null) {
                    opt = {
                        text: input,
                        value: match[2],
                        email: match[2],
                        name: match[1]
                    };
                }
                if (regexEMail.test(input)) {
                    opt = {
                        text: input,
                        value: input,
                        email: input,
                        name: null
                    };
                }

                return opt;
            },
            onOptionAdd: function (value, data) {
                var sel = this;
                var form = $('#user-modal form');

                // Check if this option already has complete data
                if (data.name && data.email) {
                    return;
                }

                // Remove the incomplete option temporarily
                sel.removeOption(value);
                sel.removeItem(value);

                setTimeout(function () {
                    form.find('input').val(null);
                    form.find('select').prop('selectedIndex', 0);
                    form.parsley().reset();

                    $('#user-modal .modal-title .text').text(value);
                    $('#user-modal input[name=email]').val(value);

                    var myModal = new bootstrap.Modal(document.getElementById('user-modal'), {
                        backdrop: 'static',
                        keyboard: false
                    });

                    var userSaved = false;

                    myModal.show();

                    setTimeout(function () {
                        form.find('input:visible, select:visible').first().focus();
                    }, 350);

                    // Handle modal close/cancel - remove user completely
                    $('#user-modal').on('hidden.bs.modal', function() {
                        if (!userSaved) {
                            sel.removeOption(value);
                            sel.removeItem(value);
                            console.log('User creation cancelled for:', value);
                        }
                        $(this).off('hidden.bs.modal');
                        // Clean up all event handlers
                        $('#user-modal button').off('click.usermodal');
                    });

                    // Handle ALL buttons in modal footer with delegation
                    $('#user-modal').off('click.usermodal').on('click.usermodal', 'button', function(e) {
                        var $button = $(this);
                        console.log('Button clicked:', $button.text(), 'Classes:', $button.attr('class'));

                        // If it's a close/cancel button (not the primary save button)
                        if ($button.hasClass('btn-secondary') ||
                            $button.hasClass('btn-cancel') ||
                            $button.attr('data-bs-dismiss') === 'modal' ||
                            $button.text().toLowerCase().includes('cancel') ||
                            $button.text().toLowerCase().includes('close')) {

                            console.log('Cancel button clicked');
                            userSaved = false;
                            myModal.hide();
                            return;
                        }

                        // If it's the save/submit button
                        if ($button.hasClass('btn-primary') ||
                            $button.attr('type') === 'submit' ||
                            $button.text().toLowerCase().includes('save') ||
                            $button.text().toLowerCase().includes('add')) {

                            console.log('Save button clicked, validating...');

                            if (form.parsley().validate()) {
                                console.log('Validation passed');
                                var outform = $(sel.$wrapper).closest('form');
                                var outbase = $('#user-modal').attr('data-append-base');
                                var outkey = $('#user-modal').attr('data-append-key');
                                var outdata = {};

                                userSaved = true;

                                form.serializeArray().forEach(function (item) {
                                    outdata[item.name] = item.value;
                                });
                                outkey = outdata[outkey];

                                var completeUserData = {
                                    text: data.text,
                                    value: value,
                                    email: data.email,
                                    name: outdata['name'],
                                    isComplete: true,
                                    isTemporary: false
                                };

                                Object.keys(outdata).forEach(function (key) {
                                    outform.append(
                                        $('<input />').attr('type', 'hidden').attr('name', outbase + '[' + outkey + '][' + key + ']').attr('value', outdata[key])
                                    );
                                });

                                sel.addOption(completeUserData);
                                sel.addItem(value);

                                myModal.hide();
                                sel.close();
                                console.log('User created successfully:', outdata.name, '(' + value + ')');
                            } else {
                                console.log('Validation failed');
                                // Show validation errors
                                form.parsley().validate();
                            }
                        }
                    });

                    // Handle close button in header (X button)
                    $('#user-modal .btn-close, #user-modal [data-bs-dismiss="modal"]').off('click.usermodal').on('click.usermodal', function() {
                        console.log('Close (X) button clicked');
                        userSaved = false;
                        myModal.hide();
                    });
                });
            }
        });
    });

    parsley.setLocale(lang);

    $('form').parsley({
        excluded: excludedInputs + ', [disabled]',
        successClass: 'has-success',
        errorClass: 'has-error',
        classHandler: function (el) {
            return $(el.$element).closest('.form-group');
        },
        errorsContainer: function () {},
        errorsWrapper: '<span class="input-error"></span>',
        errorTemplate: '<span></span>'
    });

    // ADD: Form validation before submission
    $('form').on('submit', function(e) {
        var hasIncompleteUsers = false;
        var incompleteList = [];

        $(this).find('select.users-create').each(function() {
            if (this.selectize) {
                var selectizeInstance = this.selectize;
                var validation = validateAllSelectizeUsers(selectizeInstance);

                if (!validation.valid) {
                    hasIncompleteUsers = true;
                    validation.incompleteUsers.forEach(function(user) {
                        incompleteList.push(user.email || 'Unknown email');
                    });
                }
            }
        });

        if (hasIncompleteUsers) {
            e.preventDefault();
            alert('Please complete information for these users:\n• ' + incompleteList.join('\n• '));
            return false;
        }
    });

    new timeago().render($('.timeago'), lang);
});