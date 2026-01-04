/**
 * Generates and renders a dynamic modal or inline form with various field types, validation, and callbacks.
 *
 * @function
 * @param {Object} params - Configuration object for the form.
 * @param {string} [params.target] - Selector or DOM element to render the form into. If not provided, renders as a modal.
 * @param {string} [params.size] - Modal size ('sm', 'lg', 'xl').
 * @param {string} [params.title] - Title of the form/modal.
 * @param {string} [params.apply] - Label for the apply/submit button.
 * @param {boolean} [params.topApply] - If true, shows the apply button at the top.
 * @param {boolean} [params.borderless] - If true, renders the table without borders.
 * @param {boolean} [params.noHover] - If true, disables hover effect on table rows.
 * @param {boolean} [params.singleColumn] - If true, renders the form in a single column layout.
 * @param {boolean} [params.footer] - If true, renders a footer with action buttons.
 * @param {boolean} [params.noFocus] - If true, disables autofocus on the first field.
 * @param {number} [params.timeout] - Timeout in milliseconds to auto-close the modal.
 * @param {string} [params.delete] - If provided, adds a delete confirmation select field.
 * @param {string} [params.deleteTab] - Tab name for the delete field.
 * @param {Function} [params.callback] - Callback function to be called with form values on successful submit.
 * @param {Function} [params.cancel] - Callback function to be called on cancel.
 * @param {Function} [params.tabActivate] - Callback function called when a tab is activated.
 * @param {Function} [params.done] - Callback function called after the form is rendered.
 * @param {Array<Object>} params.fields - Array of field configuration objects. Each field can have:
 *   @param {string} fields[].id - Unique identifier for the field.
 *   @param {string} [fields[].type] - Field type ('text', 'select', 'area', 'multiselect', 'sortable', 'files', 'jstree', etc.).
 *   @param {string} [fields[].title] - Field label.
 *   @param {string} [fields[].hint] - Hint or help text for the field.
 *   @param {string} [fields[].tab] - Tab name for grouping fields.
 *   @param {boolean} [fields[].hidden] - If true, hides the field.
 *   @param {boolean} [fields[].readonly] - If true, makes the field read-only.
 *   @param {boolean} [fields[].disabled] - If true, disables the field.
 *   @param {boolean} [fields[].focus] - If true, autofocuses this field.
 *   @param {any} [fields[].value] - Initial value for the field.
 *   @param {Array<Object>} [fields[].options] - Options for select/multiselect fields.
 *   @param {Function} [fields[].validate] - Validation function for the field. Should return true or an error string.
 *   @param {Function} [fields[].select] - Callback for select field change.
 *   @param {Object} [fields[].button] - Button configuration for fields with an attached button.
 *   @param {Function} [fields[].add] - Callback for adding items (for jstree, etc.).
 *   @param {Function} [fields[].rename] - Callback for renaming items (for jstree, etc.).
 *   @param {Function} [fields[].delete] - Callback for deleting items (for jstree, etc.).
 *   @param {Function} [fields[].search] - Callback for searching (for jstree, multiselect, etc.).
 *   @param {Function} [fields[].renamed] - Callback for renamed event (for jstree).
 *   @param {boolean} [fields[].autoload] - If true, auto-triggers file input (for files field).
 *   @param {string} [fields[].placeholder] - Placeholder text for input fields.
 *   @param {string} [fields[].pattern] - Regex pattern for input validation.
 *   @param {number} [fields[].float] - Step value for number fields.
 *   @param {string} [fields[].language] - Language for code editor fields.
 *   @param {number} [fields[].height] - Height for code/json editor fields.
 *   @param {boolean} [fields[].multiple] - If true, enables multiple selection (for select2).
 *   @param {boolean} [fields[].tags] - If true, enables tag creation (for select2).
 *   @param {Object} [fields[].ajax] - AJAX configuration for select2.
 *   @param {number} [fields[].minimumResultsForSearch] - Minimum results to show search box (for select2).
 *   @param {string} [fields[].color] - Color class for select2.
 *   @param {string} [fields[].class] - Additional CSS class for the field.
 *   @param {string} [fields[].icon] - Icon class for select2/icon fields.
 *   @param {string} [fields[].font] - Font family for font fields.
 *   @param {string} [fields[].state0] - Label for tristate option 0.
 *   @param {string} [fields[].state1] - Label for tristate option 1.
 *   @param {string} [fields[].state2] - Label for tristate option 2.
 *   @param {Array<string>} [fields[].mimeTypes] - Allowed MIME types for files field.
 *   @param {number} [fields[].maxSize] - Maximum file size for files field.
 *   @param {boolean} [fields[].allButtons] - If false, hides check all/uncheck all buttons (for multiselect).
 *   @param {boolean} [fields[].filter] - If true, enables filter input (for multiselect).
 *   @param {boolean} [fields[].search] - If true, enables search input (for jstree).
 *   @param {boolean} [fields[].addRoot] - If true, enables add root button (for jstree).
 *   @param {boolean} [fields[].tabHidden] - If true, hides the field in the current tab.
 *   @param {string} [fields[].return] - Return mode for date fields ('asis' or default).
 *   @param {boolean} [fields[].sec] - If true, returns date as seconds, otherwise as milliseconds.
 *   @param {boolean} [fields[].noHover] - If true, disables hover effect for the field row.
 *   @param {boolean} [fields[].append] - Additional HTML to append to the field.
 *
 * @returns {jQuery|HTMLElement} The jQuery object or DOM element containing the rendered form.
 */

function cardForm(params) {
    try {
        queryLocalFonts().then(array => {
            array.forEach(font => {
                if (availableFonts.indexOf(font.family) < 0) {
                    availableFonts.push(font.family);
                }
            });
        });
    } catch(e) {
        console.log(`Local font access not available: ${e.message}`);
    }

    let _prefix = "modalForm-" + md5(guid()) + "-";

    let h = `<form id="${_prefix}form" autocomplete="off" onsubmit="return false;" action="">`;
    h += `<input autocomplete="off" name="${_prefix}hiddenText" type="text" style="display:none;">`;
    h += `<input autocomplete="off" name="${_prefix}hiddenPassword" type="password" style="display:none;">`;
    h += `<input autocomplete="new-password" name="${_prefix}hiddenNewPassword" type="password" style="display:none;">`;

    let files = {};

    if (params.target) {
        h += `<div class="card mt-2">`;
    } else {
        h += `<div class="card mt-0 mb-0" style="max-height: calc(100vh - (100vh / 10));">`;
        $("#modalBody").parent().removeClass("modal-sm modal-lg modal-xl");
        if ([ "sm", "lg", "xl" ].indexOf(params.size) >= 0) {
            $("#modalBody").parent().addClass("modal-" + params.size);
        }
    }

    if (!params.apply) {
        params.apply = "apply";
    }

    if (params.title) {
        h += `<div class="card-header pointer" id="modalHeader">`;
        h += `<h3 class="card-title text-bold">`;
        if (params.topApply) {
            h += `<button class="btn btn-primary btn-xs btn-tool-rbt-left mr-2 modalFormOk" id="modalFormApply" title="${i18n(params.apply)}"><i class="fas fa-fw fa-check-circle"></i></button> `;
        }
        h += params.title;
        h += `</h3>`;
        h += `<button type="button" class="btn btn-danger btn-xs btn-tool-rbt-right ml-2 float-right modalFormCancel" data-dismiss="modal" title="${i18n("cancel")}"><i class="far fa-fw fa-times-circle"></i></button>`;
        h += `</div>`;
    }

    h += `<div class="card-body table-responsive p-0" style="overflow-x: hidden!important;">`;

    h += '<table class="table';

    if (params.borderless) {
        h += " tform-borderless";
    }

    if (!params.noHover) {
        h += " table-hover";
    }

    h += ' mb-0">';

    h += `<tbody>`;

    if (params.delete) {
        let d = {
            id: "delete",
            type: "select",
            value: "",
            title: params.delete,
            options: [
                {
                    value: "",
                    text: "",
                },
                {
                    value: "yes",
                    text: i18n("yes"),
                },
            ],
        }
        if (params.deleteTab) {
            d.tab = params.deleteTab;
        }
        params.fields.push(d);
    }

    let first = " no-border-top";
    let focus;
    let autofocus;

    let tabs = [];
    let others = false;
    for (let i in params.fields) {
        if (!params.fields[i].hidden) {
            if (params.fields[i].tab && tabs.indexOf(params.fields[i].tab) < 0) {
                tabs.push(params.fields[i].tab);
            }
            if (!params.fields[i].tab) {
                others = true;
            }
        }
    }

    if (others && tabs.length && tabs.indexOf(i18n("other")) < 0) {
        tabs.push(i18n("other"));
    }

    if (tabs.length > 1) {
        h += `<ul class="nav nav-tabs mt-1 ml-1" id="jsform-content-tab" role="tablist">`;
        for (let i in tabs) {
            h += `<li class="nav-item">`;
            h += `<a class="nav-link jsform-nav-link ${(i == 0) ? "active text-bold" : ""} jsform-tab-link" id="jsform-content-tab-${md5(tabs[i])}" data-toggle="pill" href="#" role="tab" aria-selected="${(i == 0) ? "true" : "false"}" aria-controls="jsform-content-${md5(tabs[i])}" data-tab-index="${i}">${tabs[i]}</a>`;
            h += `</li>`;
        }
        h += `</ul>`;

        for (let i in params.fields) {
            if (!params.fields[i].tab) {
                params.fields[i].tab = i18n("other");
            }
        }

        for (let i in params.fields) {
            params.fields[i].tabHidden = tabs.indexOf(params.fields[i].tab) > 0;
        }
    } else {
        tabs = [];
    }

    for (let i in params.fields) {
        if (params.fields[i].type == "jstree") {
            params.fields[i].noHover = true;
        }

        if (params.fields[i].type == "yesno") {
            params.fields[i].type = "select";
            params.fields[i].options = [
                {
                    id: "1",
                    text: i18n("yes"),
                },
                {
                    id: "0",
                    text: i18n("no"),
                },
            ];
        }

        if (params.fields[i].type == "noyes") {
            params.fields[i].type = "select";
            params.fields[i].options = [
                {
                    id: "0",
                    text: i18n("no"),
                },
                {
                    id: "1",
                    text: i18n("yes"),
                },
            ];
        }

        if (params.fields[i].type == "tristate") {
            params.fields[i].type = "select";
            params.fields[i].options = [
                {
                    id: "0",
                    text: params.fields[i].state0,
                },
                {
                    id: "1",
                    text: params.fields[i].state1,
                },
                {
                    id: "2",
                    text: params.fields[i].state2,
                },
            ];
        }

        if (params.fields[i].type == "font") {
            let fonts = [
                {
                    text: i18n("default"),
                    value: "",
                },
            ];

            for (let i in availableFonts) {
                fonts.push({
                    text: availableFonts[i],
                    value: availableFonts[i],
                    font: availableFonts[i],
                });
            }

            params.fields[i].type = "select2";
            params.fields[i].options = fonts;
        }

        if (params.fields[i].type == "color") {
            params.fields[i].type = "select2";
            params.fields[i].options = [ ];

            for (let c in systemColors) {
                if (params.fields[i].noEmpty && !systemColors[c]) continue;
                params.fields[i].options.push({
                    text: systemColors[c] ? i18n("color" + systemColors[c][0].toUpperCase() + systemColors[c].substring(1)) : i18n("default"),
                    value: systemColors[c],
                    icon: "p-1 fas fa-palette" + (systemColors[c] ? " bg-" + systemColors[c] : ""),
                });
            }
        }

        if (params.fields[i].type == "icon") {
            let icons = [
                {
                    text: i18n("withoutIcon"),
                    value: "",
                },
            ];

            for (let i in faIcons) {
                icons.push({
                    icon: faIcons[i].title + " fa-fw",
                    text: faIcons[i].title.split(" fa-")[1] + (faIcons[i].searchTerms.length ? (", " + faIcons[i].searchTerms.join(", ")) : ""),
                    value: faIcons[i].title,
                });
            }

            params.fields[i].type = "select2";
            params.fields[i].options = icons;
        }

        if (params.fields[i].id === "-") {
            h += `<tr class='mt-0 mb-0 pt-0 pb-0 jsform-tabbed-item' data-tab-index='${tabs.indexOf(params.fields[i].tab)}'>`;
            if (params.singleColumn) {
                h += "<td class='mt-0 mb-0 pt-0 pb-0'>";
            } else {
                h += "<td colspan='2' class='mt-0 mb-0 pt-0 pb-0'>";
            }
            h += "<hr class='mt-0 mb-0 pt-0 pb-0'/>";
            h += "</td>";
            h += "</tr>";
            continue;
        }

        if (params.fields[i].options) {
            for (let j in params.fields[i].options) {
                if (params.fields[i].options[j].id && !params.fields[i].options[j].value && params.fields[i].options[j].value !== "") {
                    params.fields[i].options[j].value = params.fields[i].options[j].id;
                }
                if (params.fields[i].options[j].value && !params.fields[i].options[j].id) {
                    params.fields[i].options[j].id = params.fields[i].options[j].value;
                }
                if (!params.fields[i].options[j].id && !params.fields[i].options[j].value && params.fields[i].options[j].value !== "" && params.fields[i].options[j].text) {
                    params.fields[i].options[j].id = params.fields[i].options[j].text;
                    params.fields[i].options[j].value = params.fields[i].options[j].text;
                }
                if (!params.fields[i].options[j].text) {
                    if (params.fields[i].type == "select2") {
                        params.fields[i].options[j].text = "&nbsp;";
                    } else {
                        params.fields[i].options[j].text = "";
                    }
                }
            }
        }

        if (params.fields[i].hidden) {
            h += `<tr style="display: none;" name="${_prefix}${params.fields[i].id}-container" id="${_prefix}${params.fields[i].id}-container">`;
        } else
        if (params.fields[i].tabHidden) {
            h += `<tr style="display: none;" class="jsform-tabbed-item ${params.fields[i].noHover ? 'nohover' : ''}" data-tab-index="${tabs.indexOf(params.fields[i].tab)}" name="${_prefix}${params.fields[i].id}-container" id="${_prefix}${params.fields[i].id}-container">`;
        } else {
            h += `<tr class="jsform-tabbed-item ${params.fields[i].noHover ? 'nohover' : ''}" data-tab-index="${tabs.indexOf(params.fields[i].tab)}" name="${_prefix}${params.fields[i].id}-container" id="${_prefix}${params.fields[i].id}-container">`;
        }

        params.fields[i].type = params.fields[i].type ? params.fields[i].type : "text";

        if (!params.singleColumn) {
            if (params.fields[i].title !== false) {
                if (params.fields[i].hint || params.fields[i].type == "files") {
                    h += `<td class="pb-0 pt-3 tdform${first}" style="vertical-align: top!important;">${params.fields[i].title}</td>`;
                } else {
                    if (params.fields[i].type == "select2") {
                        h += `<td class="tdform${first}" style="vertical-align: top!important; padding-top: 19px!important;">${params.fields[i].title}</td>`;
                    } else {
                        h += `<td class="pt-3 tdform${first}" style="vertical-align: top!important;">${params.fields[i].title}</td>`;
                    }
                }
            }
        }

        if (params.fields[i].title !== false) {
            if (params.fields[i].hint || params.fields[i].type == "files") {
                h += `<td class="pb-0 tdform-right${first}">`;
            } else {
                h += `<td class="tdform-right${first}">`;
            }
        } else {
            if (params.fields[i].hint || params.fields[i].type == "files") {
                h += `<td class="pb-0 tdform-right${first}" colspan='2'>`;
            } else {
                h += `<td class="tdform-right${first}" colspan='2'>`;
            }
        }

        first = "";
        let height = 0;

        if (!focus && params.fields[i].focus && !params.fields[i].hidden && !params.fields[i].disabled && !params.fields[i].readonly) {
            if (tabs.length) {
                if (params.fields[i].tab == tabs[0]) {
                    focus = _prefix + params.fields[i].id;
                }
            } else {
                focus = _prefix + params.fields[i].id;
            }
        }

        if (!autofocus && !params.fields[i].hidden && !params.fields[i].disabled && !params.fields[i].readonly) {
            if (tabs.length) {
                if (params.fields[i].tab == tabs[0]) {
                    autofocus = _prefix + params.fields[i].id;
                }
            } else {
                autofocus = _prefix + params.fields[i].id;
            }
        }

        switch (params.fields[i].type) {
            case "select":
                h += `<div class="input-group">`;
                h += `<select name="${_prefix}${params.fields[i].id}" id="${_prefix}${params.fields[i].id}" data-field-index="${i}" class="form-control modalFormField"`;
                if (params.fields[i].readonly) {
                    h += ` readonly="readonly"`;
                    h += ` disabled="disabled"`;
                }
                h += `>`;
                for (let j in params.fields[i].options) {
                    if (params.fields[i].options[j].value == params.fields[i].value || params.fields[i].options[j].selected) {
                        h += `<option value="${escapeHTML(params.fields[i].options[j].value)}" selected>${escapeHTML(params.fields[i].options[j].text)}</option>`;
                    } else {
                        h += `<option value="${escapeHTML(params.fields[i].options[j].value)}">${escapeHTML(params.fields[i].options[j].text)}</option>`;
                    }
                }
                h += `</select>`;
                h += `<div class="input-group-append">`;
                if (params.fields[i].readonly) {
                    h += `<span class="input-group-text disabled" disabled="disabled"><i class="fas fa-fw fa-angle-double-right"></i></span>`;
                } else {
                    h += `<span class="input-group-text pointer cardFormSelectWithRotate" data-for="${_prefix}${params.fields[i].id}"><i class="fas fa-fw fa-angle-double-right"></i></span>`;
                }
                h += `</div>`;
                h += `</div>`;
                break;

            case "time":
                if ($.browser.mozilla) {
                    if (params.fields[i].button) {
                        h += `<div class="input-group">`;
                    }
                    if (params.fields[i].type == "number") {
                        let float = params.fields[i].float ? params.fields[i].float : "any";
                        h += `<input name="${_prefix}${params.fields[i].id}" id="${_prefix}${params.fields[i].id}" type="${params.fields[i].type}" class="form-control modalFormField" style="cursor: text;" autocomplete="off" placeholder="${escapeHTML(params.fields[i].placeholder ? params.fields[i].placeholder : "")}" step="${float}"`;
                    } else {
                        h += `<input name="${_prefix}${params.fields[i].id}" id="${_prefix}${params.fields[i].id}" type="${params.fields[i].type}" class="form-control modalFormField" style="cursor: text;" autocomplete="off" placeholder="${escapeHTML(params.fields[i].placeholder ? params.fields[i].placeholder : "")}"`;
                    }
                    if (params.fields[i].readonly) {
                        h += ` readonly="readonly"`;
                        h += ` disabled="disabled"`;
                    }
                    if (params.fields[i].pattern) {
                        h += ` pattern="${params.fields[i].pattern}"`;
                    }
                    h += ` />`;
                    if (params.fields[i].button) {
                        h += `<div class="input-group-append">`;
                        h += `<span id="${_prefix}${params.fields[i].id}-button" title="${params.fields[i].button.hint ? params.fields[i].button.hint : ''}" class="input-group-text pointer"><i class="fa-fw ${params.fields[i].button.class}"></i></span>`;
                        h += `</div>`;
                        h += `</div>`;
                    }
                } else {
                    h += `<div class="input-group">`;
                    h += `<input name="${_prefix}${params.fields[i].id}" id="${_prefix}${params.fields[i].id}" type="${params.fields[i].type}" class="form-control modalFormField nopicker" style="cursor: text;" autocomplete="off" placeholder="${escapeHTML(params.fields[i].placeholder ? params.fields[i].placeholder : "")}"`;
                    if (params.fields[i].readonly) {
                        h += ` readonly="readonly"`;
                        h += ` disabled="disabled"`;
                    }
                    if (params.fields[i].pattern) {
                        h += ` pattern="${params.fields[i].pattern}"`;
                    }
                    h += ` />`;
                    h += `<div class="input-group-append">`;
                    if (params.fields[i].readonly) {
                        h += `<span class="input-group-text disabled" disabled="disabled"><i class="far fa-fw fa-clock"></i></span>`;
                    } else {
                        h += `<span class="input-group-text pointer cardFormPicker" data-for="${_prefix}${params.fields[i].id}"><i class="far fa-fw fa-clock"></i></span>`;
                    }
                    h += `</div>`;
                    h += `</div>`;
                }
                break;

            case "date":
            case "datetime-local":
                if ($.browser.mozilla) {
                    if (params.fields[i].button) {
                        h += `<div class="input-group">`;
                    }
                    if (params.fields[i].type == "number") {
                        let float = params.fields[i].float ? params.fields[i].float : "any";
                        h += `<input name="${_prefix}${params.fields[i].id}" id="${_prefix}${params.fields[i].id}" type="${params.fields[i].type}" class="form-control modalFormField" style="cursor: text;" autocomplete="off" placeholder="${escapeHTML(params.fields[i].placeholder ? params.fields[i].placeholder : "")}" step="${float}"`;
                    } else {
                        h += `<input name="${_prefix}${params.fields[i].id}" id="${_prefix}${params.fields[i].id}" type="${params.fields[i].type}" class="form-control modalFormField" style="cursor: text;" autocomplete="off" placeholder="${escapeHTML(params.fields[i].placeholder ? params.fields[i].placeholder : "")}"`;
                    }
                    if (params.fields[i].readonly) {
                        h += ` readonly="readonly"`;
                        h += ` disabled="disabled"`;
                    }
                    if (params.fields[i].pattern) {
                        h += ` pattern="${params.fields[i].pattern}"`;
                    }
                    h += ` />`;
                    if (params.fields[i].button) {
                        h += `<div class="input-group-append">`;
                        h += `<span id="${_prefix}${params.fields[i].id}-button" title="${params.fields[i].button.hint ? params.fields[i].button.hint : ''}" class="input-group-text pointer"><i class="fa-fw ${params.fields[i].button.class}"></i></span>`;
                        h += `</div>`;
                        h += `</div>`;
                    }
                } else {
                    h += `<div class="input-group">`;
                    h += `<input name="${_prefix}${params.fields[i].id}" id="${_prefix}${params.fields[i].id}" type="${params.fields[i].type}" class="form-control modalFormField nopicker" style="cursor: text;" autocomplete="off" placeholder="${escapeHTML(params.fields[i].placeholder ? params.fields[i].placeholder : "")}"`;
                    if (params.fields[i].readonly) {
                        h += ` readonly="readonly"`;
                        h += ` disabled="disabled"`;
                    }
                    if (params.fields[i].pattern) {
                        h += ` pattern="${params.fields[i].pattern}"`;
                    }
                    h += ` />`;
                    h += `<div class="input-group-append">`;
                    if (params.fields[i].readonly) {
                        h += `<span class="input-group-text disabled" disabled="disabled"><i class="far fa-fw fa-calendar"></i></span>`;
                    } else {
                        h += `<span class="input-group-text pointer cardFormPicker" data-for="${_prefix}${params.fields[i].id}"><i class="far fa-fw fa-calendar"></i></span>`;
                    }
                    h += `</div>`;
                    h += `</div>`;
                }
                break;

            case "select2":
                if (params.fields[i].color) {
                    h += `<div class="select2-${params.fields[i].color} modalFormField">`;
                } else {
                    h += `<div class="select2-secondary modalFormField">`;
                }
                h += `<select name="${_prefix}${params.fields[i].id}" id="${_prefix}${params.fields[i].id}" class="form-control select2`;
                h += `"`;
                if (params.fields[i].readonly) {
                    h += ` readonly="readonly"`;
                    h += ` disabled="disabled"`;
                }
                if (params.fields[i].multiple) {
                    h += ` multiple="multiple"`;
                }
                h += `>`;
                if (typeof params.fields[i].options === "object") {
                    for (let j in params.fields[i].options) {
                        if (params.fields[i].options[j].value == params.fields[i].value || params.fields[i].options[j].selected) {
                            h += `<option label="${escapeHTML(params.fields[i].options[j].text)}" value="${escapeHTML(params.fields[i].options[j].value)}" data-icon="${params.fields[i].options[j].icon}" data-class="${params.fields[i].options[j].class}" data-font="${params.fields[i].options[j].font}" selected>${escapeHTML(params.fields[i].options[j].text)}</option>`;
                        } else {
                            h += `<option label="${escapeHTML(params.fields[i].options[j].text)}" value="${escapeHTML(params.fields[i].options[j].value)}" data-icon="${params.fields[i].options[j].icon}" data-class="${params.fields[i].options[j].class}" data-font="${params.fields[i].options[j].font}">${escapeHTML(params.fields[i].options[j].text)}</option>`;
                        }
                    }
                } else {
                    h += params.fields[i].options;
                }
                h += `</select>`;
                h += `</div>`;
                break;

            case "multiselect":
                if (params.fields[i].filter) {
                    h += `<div class="input-group mb-2">`;
                    h += `<input name="${_prefix}${params.fields[i].id}-filter" id="${_prefix}${params.fields[i].id}-filter" type="text" class="form-control modalFormField" style="cursor: text;" autocomplete="off" placeholder="${i18n("filter")}">`;
                    h += `<div class="input-group-append">`;
                    h += `<span id="${_prefix}${params.fields[i].id}-filter-button" title="${i18n("filter")}" class="input-group-text pointer"><i class="fas fa-fw fa-filter"></i></span>`;
                    h += `</div>`;
                    h += `</div>`;
                    if (focus == _prefix + params.fields[i].id) {
                        focus = _prefix + params.fields[i].id + "-filter";
                    };
                    if (autofocus == _prefix + params.fields[i].id) {
                        autofocus = _prefix + params.fields[i].id + "-filter";
                    };
                }
                if (params.target) {
                    h += `<div name="${_prefix}${params.fields[i].id}" id="${_prefix}${params.fields[i].id}" class="overflow-y-auto" style="position: relative; border: solid thin transparent; border-radius: 3px;">`;
                } else {
                    // TODO: Do something with this!!! (max-height)
                    h += `<div name="${_prefix}${params.fields[i].id}" id="${_prefix}${params.fields[i].id}" class="overflow-y-auto" style="max-height: 400px; overflow-y: auto!important; position: relative; border: solid thin transparent; border-radius: 3px;">`;
                    // TODO: Do something with this!!! (max-height)
                }
                for (let j = 0; j < params.fields[i].options.length; j++) {
                    let id = "id-" + md5(guid());
                    let c = params.fields[i].options[j].checked || (typeof params.fields[i].value === "object" && Array.isArray(params.fields[i].value) && params.fields[i].value.indexOf(params.fields[i].options[j].id) >= 0);
                    h += `
                        <div class="custom-control custom-checkbox${(j !== params.fields[i].options.length - 1) ? " mb-3" : ""}">
                        <input type="checkbox" class="ml-1 checkBoxOption-${_prefix}-${params.fields[i].id} custom-control-input multiselect-checkbox" id="${id}" data-id="${params.fields[i].options[j].id}"${c ? " checked" : ""}${(params.fields[i].options[j].disabled || params.fields[i].readonly) ? " disabled" : ""}/>
                        <label for="${id}" class="custom-control-label form-check-label" style="text-wrap: pretty;">${params.fields[i].options[j].text}</label>
                    `;
                    if (params.fields[i].options[j].append) {
                        h += params.fields[i].options[j].append;
                    }
                    h += `
                        </div>
                    `;
                }
                if (params.fields[i].allButtons !== false) {
                    h += `<span style='position: absolute; right: 0px; top: 0px;'>`;
                    h += `<span class="pointer checkAll" title="${i18n("checkAll")}"><i class="far fa-fw fa-check-square pr-3 text-primary"></i></span>`;
                    h += `<span class="pointer unCheckAll" title="${i18n("unCheckAll")}"><i class="far fa-fw fa-square pr-2 text-primary"></i></span>`;
                    h += `</span>`;
                }
                h += `</div>`;
                break;

            case "sortable":
                if (params.target) {
                    h += `<div name="${_prefix}${params.fields[i].id}" id="${_prefix}${params.fields[i].id}" data-field-index="${i}" class="overflow-y-auto pl-0" style="position: relative; border: solid thin transparent; border-radius: 3px;">`;
                } else {
                    // TODO: Do something with this!!! (max-height)
                    h += `<div name="${_prefix}${params.fields[i].id}" id="${_prefix}${params.fields[i].id}" data-field-index="${i}" class="overflow-y-auto pl-0" style="max-height: 400px; overflow-y: scroll!important; position: relative; border: solid thin transparent; border-radius: 3px; padding-right: 8px">`;
                    // TODO: Do something with this!!! (max-height)
                }
                h += renderSortable(i);
                h += '</div>';
                break;

            case "area":
                h += `<textarea name="${_prefix}${params.fields[i].id}" id="${_prefix}${params.fields[i].id}" rows="5" class="form-control modalFormField overflow-auto" autocomplete="off" style="resize: vertical;" placeholder="${escapeHTML(params.fields[i].placeholder ? params.fields[i].placeholder : "")}"`;
                if (params.fields[i].readonly) {
                    h += ` readonly="readonly"`;
                    h += ` disabled="disabled"`;
                }
                h += `></textarea>`;
                break;

            case "rich":
                h += `<textarea name="${_prefix}${params.fields[i].id}" id="${_prefix}${params.fields[i].id}" rows="5" class="form-control modalFormField overflow-auto" autocomplete="off" style="resize: vertical;" placeholder="${escapeHTML(params.fields[i].placeholder ? params.fields[i].placeholder : "")}"`;
                if (params.fields[i].readonly) {
                    h += ` readonly="readonly"`;
                    h += ` disabled="disabled"`;
                }
                h += `></textarea>`;
                break;

            case "code":
            case "json":
                h += `<div id="${_prefix}${params.fields[i].id}-div" style="position: relative;">`;
                h += `<pre class="ace-editor form-control modalFormField" id="${_prefix}${params.fields[i].id}"></pre>`;
                if (params.fields[i].language == "markdown") {
                    h += `<div id='${_prefix}${params.fields[i].id}-preview' style='display: none; border: solid thin #ced4da; border-radius: 0.25rem; overflow-y: auto; padding-left: 4px; padding-top: 4px;'></div>`;
                    h += `<div class='markdown-preview pointer noselect' data-field='${i}' style='font-size: 0.8rem; position: absolute; right: 10px; top: -10px; border: solid thin #ced4da; border-radius: 0.25rem; background: white; padding-left: 4px; padding-right: 4px;'>${i18n("preview")}</div>`;
                }
                h += `</div>`;
                break;

            case "text":
            case "email":
            case "number":
            case "tel":
            case "password":
                if (params.fields[i].button) {
                    h += `<div class="input-group">`;
                }
                if (params.fields[i].type == "number") {
                    let float = params.fields[i].float ? params.fields[i].float : "any";
                    h += `<input name="${_prefix}${params.fields[i].id}" id="${_prefix}${params.fields[i].id}" type="${params.fields[i].type}" class="form-control modalFormField" style="cursor: text;" autocomplete="off" placeholder="${escapeHTML(params.fields[i].placeholder ? params.fields[i].placeholder : "")}" step="${float}"`;
                } else {
                    h += `<input name="${_prefix}${params.fields[i].id}" id="${_prefix}${params.fields[i].id}" type="${params.fields[i].type}" class="form-control modalFormField" style="cursor: text;" autocomplete="off" placeholder="${escapeHTML(params.fields[i].placeholder ? params.fields[i].placeholder : "")}"`;
                }
                if (params.fields[i].readonly) {
                    h += ` readonly="readonly"`;
                    h += ` disabled="disabled"`;
                }
                if (params.fields[i].pattern) {
                    h += ` pattern="${params.fields[i].pattern}"`;
                }
                h += ` />`;
                if (params.fields[i].button) {
                    h += `<div class="input-group-append">`;
                    h += `<span id="${_prefix}${params.fields[i].id}-button" title="${params.fields[i].button.hint ? params.fields[i].button.hint : ''}" class="input-group-text pointer"><i class="fa-fw ${params.fields[i].button.class}"></i></span>`;
                    h += `</div>`;
                    h += `</div>`;
                }
                break;

            case "files":
                h += `<select name="${_prefix}${params.fields[i].id}" id="${_prefix}${params.fields[i].id}" class="form-control" multiple="multiple"></select>`;
                h += `<span id="${_prefix}${params.fields[i].id}-add" class="text-primary hoverable text-xs pl-1" data-for="${_prefix}${params.fields[i].id}" data-mime-types="${escapeHTML(JSON.stringify(params.fields[i].mimeTypes))}" data-max-size="${params.fields[i].maxSize}"><i class="far fa-folder-open" style="margin-right: 5px;"></i>${i18n("add")}</span><span class="text-secondary text-xs ml-2">(${i18n("dblClickToRemove").toLowerCase()})</span>`;
                if (params.fields[i].autoload) {
                    setTimeout(() => {
                        $("#" + _prefix + params.fields[i].id + "-add").click();
                    }, 10);
                }
                break;

            case "empty":
                h += `<div name="${_prefix}${params.fields[i].id}" id="${_prefix}${params.fields[i].id}"></div>`;
                break;

            case "jstree":
                if (params.fields[i].search) {
                    h += `<div class="input-group mb-2">`;
                    h += `<input id="${_prefix}${params.fields[i].id}-search" id="${_prefix}${params.fields[i].id}-search" type="search" class="form-control modalFormField" style="cursor: text;" autocomplete="off" placeholder="${i18n("search")}" data-input-focus="${_prefix}${params.fields[i].id}">`;
                    h += `<div class="input-group-append">`;
                    h += `<span id="${_prefix}${params.fields[i].id}-search-button" title="${i18n("search")}" class="input-group-text pointer"><i class="fas fa-fw fa-search"></i></span>`;
                    h += `</div>`;
                    h += `</div>`;
                    if (focus == _prefix + params.fields[i].id) {
                        focus = _prefix + params.fields[i].id + "-search";
                    };
                    if (autofocus == _prefix + params.fields[i].id) {
                        autofocus = _prefix + params.fields[i].id + "-search";
                    };
                }
                h += `<div name="${_prefix}${params.fields[i].id}" id="${_prefix}${params.fields[i].id}" class="overflow-y-auto p-2" style="max-height: 400px; min-height: 400px; height: 400px; overflow-y: auto!important; position: relative; border: solid thin lightgray; border-radius: 3px;"></div>`;
                if (params.fields[i].add || params.fields[i].rename || params.fields[i].delete) {
                    h += `<div class="mt-2">`;
                    h += `<button id="${_prefix}${params.fields[i].id}-clear" type="button" class="btn btn-secondary mr-2" title="${i18n("clearSelection")}"><i class="fas fa-fw fa-eraser"></i></button>`;
                    if (params.fields[i].addRoot) {
                        h += `<button id="${_prefix}${params.fields[i].id}-addRoot" type="button" class="btn btn-success mr-2" title="${i18n("addRoot")}"><i class="fas fa-fw fa-folder-plus"></i></button>`;
                    }
                    if (params.fields[i].add) {
                        h += `<button id="${_prefix}${params.fields[i].id}-add" type="button" class="btn btn-info mr-2" title="${i18n("add")}"><i class="fas fa-fw fa-plus-circle"></i></button>`;
                    }
                    if (params.fields[i].rename) {
                        h += `<button id="${_prefix}${params.fields[i].id}-rename" type="button" class="btn btn-warning mr-2" title="${i18n("rename")}"><i class="fas fa-fw fa-pencil-alt"></i></button>`;
                    }
                    if (params.fields[i].delete) {
                        h += `<button id="${_prefix}${params.fields[i].id}-delete" type="button" class="btn btn-danger mr-2" title="${i18n("delete")}"><i class="fas fa-fw fa-trash-alt"></i></button>`;
                    }
                    h += `</div>`;
                }
                break;

            case "button":
                h += `<input name="${_prefix}${params.fields[i].id}" id="${_prefix}${params.fields[i].id}" type="${params.fields[i].type}" value="${params.fields[i].button.hint}" class="btn ${params.fields[i].button.class ? params.fields[i].button.class : 'btn-secondary'}" />`;
                break;

            case "none":
                h += params.fields[i].value ? ('<div style="height: 34px;">' + params.fields[i].value + '</div>') : '<div style="height: 34px;">&nbsp;</div>';
                break;
        }

        if (params.fields[i].hint) {
            h += `<span class="text-secondary text-xs pl-1">${params.fields[i].hint}`;
            if (params.fields[i].copyable) {
                h += `<span class='ml-1 form-cc pointer far fa-copy' data-clipboard-text='${params.fields[i].hint}' title='${i18n("copyToClipboard")}'></span>`;
            }
            h += "</span>";
        }

        h += `</td>`;
        h += `</tr>`;
    }

    h += `</tbody>`;

    if (params.footer) {
        h += `<tfoot>`;
        h += `<tr>`;
        if (params.singleColumn) {
            h += `<td>`;
        } else {
            h += `<td colspan="2">`;
        }
        h += `<button type="button" class="btn btn-primary modalFormOk">${i18n(params.apply)}</button>`;
        if (typeof params.cancel === "function") {
            h += `<button type="button" class="btn btn-default float-right modalFormCancel">${i18n("cancel")}</button>`;
        }
        h += `</td>`;
        h += `</tr>`;
        h += `</tfoot>`;
    }

    h += `</table>`;
    h += `</div>`;
    h += `</div>`;

    h += '</form>';

    function s2FormatR(item) {
        let c = '';
        let f = '';

        if (item.element && item.element.dataset && item.element.dataset.class && item.element.dataset.class !== "undefined") {
            c = item.element.dataset.class;
        }

        if (item.element && item.element.dataset && item.element.dataset.font && item.element.dataset.font !== "undefined") {
            f = `font-family: '${item.element.dataset.font}'`;
        }

        if (item.element && item.element.dataset && item.element.dataset.icon && item.element.dataset.icon !== "undefined") {
            return $(`<span class="${c}" style="display: grid; align-items: center; justify-content: start;"><span style="grid-column: 1; width: fit-content;"><i class="${item.element.dataset.icon} mr-2"></i></span><span style="grid-column: 2; ${f}">${item.text}</span></span>`);
        } else {
            return $(`<span class="${c}" style="${f}">${item.text}</span>`);
        }
    }

    function s2FormatS(item) {
        let c = '';
        let f = '';

        if (item.element && item.element.dataset && item.element.dataset.class && item.element.dataset.class !== "undefined") {
            c = item.element.dataset.class;
        }

        if (item.element && item.element.dataset && item.element.dataset.font && item.element.dataset.font !== "undefined") {
            f = `font-family: '${item.element.dataset.font}'`;
        }

        if (item.element && item.element.dataset && item.element.dataset.icon && item.element.dataset.icon !== "undefined") {
            return $(`<span class="${c}" style="display: grid; align-items: top; justify-content: start;"><span style="grid-column: 1; width: fit-content;"><i class="${item.element.dataset.icon} mr-2"></i></span><span style="grid-column: 2; $f">${item.text}</span></span>`);
        } else {
            return $(`<span class="${c}" style="${f}">${item.text}</span>`);
        }
    }

    function renderSortable(i) {
        let field = params.fields[i];
        let h = '';

        if (field.appendable == "input") {
            h += `
                <div class="input-group">
                    <input type="text" class="form-control" id="${_prefix}${params.fields[i].id}-append" data-input-focus="${_prefix}${params.fields[i].id}">
                    <div class="input-group-append">
                        <div class="input-group-text pointer sortablePlus" id="${_prefix}${params.fields[i].id}-append-button"><i class="fas fa-fw fa-plus-square text-success"></i></div>
                    </div>
                </div>
            `;
        }

        if (field.appendable == "select2") {
            h += `
                <div class="input-group">
                    <select class="form-control select2" id="${_prefix}${params.fields[i].id}-append"></select>
                    <div class="input-group-append">
                        <div class="input-group-text pointer sortablePlus" id="${_prefix}${params.fields[i].id}-append-button"><i class="fas fa-fw fa-plus-square text-success"></i></div>
                    </div>
                </div>
            `;
        }

        h += `<div id="${_prefix}${params.fields[i].id}-items">`;

        for (let j = 0; j < field.options.length; j++) {
            h += `
                <div class="input-group form-sortable-item" data-field-option-index="${j}">
            `;
            if (field.checkable) {
                h += `
                    <div class="input-group-prepend">
                        <span class="input-group-text">
                            <input type="checkbox" ${field.options[j].checked ? "checked" : ""}>
                        </span>
                    </div>
                `;
            }
            h += `
                    <input type="text" class="form-control" value="${escapeHTML(field.options[j].text)}" ${!field.editable ? 'readonly' : ''} data-value="${field.options[j].value}">
                    <div class="input-group-append" data-field-option-index=${j}>
                        <div class="input-group-text pointer sortableTrash" data-field-option-index=${j}><i class="far fa-fw fa-trash-alt text-danger" data-field-option-index=${j}></i></div>
                    </div>
                </div>
            `;
        }

        h += '</div>';

        return h;
    }

    function sortable(i) {
        new Sortable(document.getElementById(_prefix + params.fields[i].id + "-items"), {
            animation: 150,

            onEnd: e => {
                let n = 0;
                let options = [];
                $(`#${e.to.id}`).children().each(function () {
                    let el = $(this);
                    if (el.attr("data-field-option-index") !== undefined) {
                        let checked = false;
                        let text = "";
                        let value = "";
                        el.find("*").each(function () {
                            let el = $(this);
                            if (el.attr("type") == "checkbox") {
                                checked = el.prop("checked");
                            }
                            if (el.attr("type") == "text") {
                                text = el.val();
                                value = el.attr("data-value");
                            }
                        });
                        if ($.trim(text)) {
                            options.push({
                                text: $.trim(text),
                                id: value,
                                value: value,
                                checked: checked,
                            });
                        }
                        el.attr('data-field-option-index', n);
                        if (n) {
                            el.addClass("mt-1");
                        } else {
                            el.removeClass("mt-1");
                        }
                        n++;
                    }
                    params.fields[i].options = options;
                });
            },
        });

        if (params.fields[i].appendable == "select2") {
            $("#" + _prefix + params.fields[i].id + "-append").select2({
                language: lang["_code"],
                templateResult: s2FormatR,
                templateSelection: s2FormatS,
                escapeMarkup: function (m) {
                    return m;
                },
                data: params.fields[i].appendableData,
            });
        }
    }

    function assignSortableHandlers(i) {
        $(`#${_prefix}${params.fields[i].id}-append`).off("keypress").on("keypress", e => {
            if (e.keyCode == 13) {
                e.preventDefault();
                $(`#${_prefix}${params.fields[i].id}-append-button`).click();
                setTimeout(() => {
                    $(`#${_prefix}${params.fields[i].id}-append`).focus();
                }, 10);
            }
        });

        $(".sortablePlus").off("click").on("click", () => {
            let value = $("#" + _prefix + params.fields[i].id + "-append").val();
            let text = "";
            if ($.trim(value)) {
                if (params.fields[i].appendable == "select2") {
                    let data = $("#" + _prefix + params.fields[i].id + "-append").select2('data');
                    for (let j in data) {
                        if (data[j].id == value) {
                            text = data[j].text;
                            break;
                        }
                    }
                } else {
                    text = value;
                }
                params.fields[i].options.push({
                    text: text,
                    id: value,
                    value: value,
                    checked: false,
                });
                $("#" + _prefix + params.fields[i].id).html(renderSortable(i));
                sortable(i);
                assignSortableHandlers(i);
            }
        });

        $(".sortableTrash").off("click").on("click", e => {
            let j = parseInt($(e.target).attr("data-field-option-index"));
            params.fields[i].options.splice(j, 1);
            $("#" + _prefix + params.fields[i].id).html(renderSortable(i));
            sortable(i);
            assignSortableHandlers(i);
        });
    }

    function getVal(i) {
        switch (params.fields[i].type) {
            case "select":
            case "select2":
            case "email":
            case "number":
            case "tel":
            case "time":
            case "password":
            case "text":
            case "color":
            case "area":
                if (params.fields[i].type == "number") {
                    return parseFloatEx($(`#${_prefix}${params.fields[i].id}`).val());
                } else {
                    return $(`#${_prefix}${params.fields[i].id}`).val();
                }

            case "date":
                if (params.fields[i].return === "asis") {
                    return $(`#${_prefix}${params.fields[i].id}`).val();
                } else {
                    if (params.fields[i].sec) {
                        return strtotime($(`#${_prefix}${params.fields[i].id}`).val());
                    } else {
                        return strtotime($(`#${_prefix}${params.fields[i].id}`).val()) * 1000;
                    }
                }

            case "datetime-local":
                if (params.fields[i].sec) {
                    return strtotime($(`#${_prefix}${params.fields[i].id}`).val());
                } else {
                    return strtotime($(`#${_prefix}${params.fields[i].id}`).val()) * 1000;
                }

            case "multiselect":
                let o = [];
                $(`.checkBoxOption-${_prefix}-${params.fields[i].id}`).each(function () {
                    if ($(this).prop("checked")) {
                        o.push($(this).attr("data-id"));
                    }
                });
                return o;

            case "rich":
                let rich = $.trim($(`#${_prefix}${params.fields[i].id}`).summernote("code"));
                if ($(`#${_prefix}${params.fields[i].id}`).summernote("isEmpty") || $.trim($(rich).text()) === "") {
                    return "";
                } else {
                    return rich.replace('<p>', '<p style="margin: 0px">');
                }

            case "code":
                return $.trim(params.fields[i].editor.getValue());

            case "json":
                try {
                    return JSON.parse($.trim(params.fields[i].editor.getValue()));
                } catch (e) {
                    return false;
                }

            case "files":
                return files[_prefix + params.fields[i].id];

            case "sortable":
                let options = [];
                $(`#${_prefix}${params.fields[i].id}-items`).children().each(function () {
                    let el = $(this);
                    if (el.attr("data-field-option-index") !== undefined) {
                        let checked = false;
                        let text = "";
                        let value = "";
                        el.find("*").each(function () {
                            let el = $(this);
                            if (el.attr("type") == "checkbox") {
                                checked = el.prop("checked");
                            }
                            if (el.attr("type") == "text") {
                                text = el.val();
                                value = el.attr("data-value");
                            }
                        });
                        if ($.trim(text)) {
                            options.push({
                                text: $.trim(text),
                                id: value,
                                value: value,
                                checked: checked,
                            });
                        }
                    }
                });
                return options;

            case "jstree":
                let node = $(`#${_prefix}${params.fields[i].id}`).jstree().get_selected();

                if (node && node.length) {
                    return node[0];
                }

                return null;
        }
    }

    function ok() {
        $(".modalFormField").removeClass("is-invalid");
        $(".select2-invalid").removeClass("select2-invalid");
        $(".border-color-invalid").removeClass("border-color-invalid");
        let invalid = [];
        if (!params.delete || $(`#${_prefix}delete`).val() !== "yes") {
            for (let i in params.fields) {
                if (params.fields[i].id === "-") continue;
                if (params.fields[i].hidden && $(`#${_prefix}${params.fields[i].id}-container`).attr("data-form-runtime-hide") !== "0") continue;
                if (params.fields[i].validate && typeof params.fields[i].validate === "function") {
                    let v = params.fields[i].validate(getVal(i), _prefix);
                    if (v !== true) {
                        invalid.push(i);
                        if (typeof v == "string") {
                            error(v, i18n("invalidFieldValue"));
                        }
                    }
                }
            }
        }
        if (invalid.length === 0) {
            if (typeof params.callback === "function") {
                let result = {};
                for (let i in params.fields) {
                    if (params.fields[i].id === "-") continue;
                    result[params.fields[i].id] = getVal(i);
                }
                if (!params.target) {
                    $('#modal').modal('hide');
                }
                params.callback(result);
            }
        } else {
            let t = false;
            for (let i in invalid) {
                if (tabs.length > 1 && !t) {
                    t = params.fields[invalid[i]].tab;
                }
                switch (params.fields[invalid[i]].type) {
                    case "select2":
                        $(`#${_prefix}${params.fields[invalid[i]].id}`).parent().addClass("select2-invalid");
                        break;
                    case "rich":
                        $(`#${_prefix}${params.fields[invalid[i]].id}`).next().addClass("border-color-invalid");
                        break;
                    case "multiselect":
                        $(`#${_prefix}${params.fields[invalid[i]].id}`).addClass("border-color-invalid");
                        break;
                    case "code":
                    case "json":
                        $(`#${_prefix}${params.fields[invalid[i]].id}`).addClass("border-color-invalid");
                        break;
                    case "sortable":
                        $(`#${_prefix}${params.fields[invalid[i]].id}`).addClass("border-color-invalid");
                        break;
                    default:
                        $(`#${_prefix}${params.fields[invalid[i]].id}`).addClass("is-invalid");
                        break;
                }
                if (t) {
                    $(`#jsform-content-tab-${md5(t)}`).click();
                }
            }
        }
    }

    function cancel() {
        if (!params.target) {
            $('#modal').modal('hide');
        }
        if (typeof params.cancel === "function") {
            params.cancel();
        }
    }

    let target;

    if (params.target) {
        target = $(params.target).html(h);
    } else {
        target = modal(h);

        $('#modal').attr("data-prefix", _prefix);

        if (params.timeout) {
            setTimeout(() => {
                if ($('#modal').attr("data-prefix") == _prefix) {
                    $('#modal').modal('hide');
                }
            }, params.timeout);
        }
    }

    setTimeout(() => {
        $(".select2-selection__rendered:visible").each(function () {
            let s2 = $(this);
            s2.css("width", s2.css("width"));
            // TODO: FIX IT!!!
            let id = s2.parent().parent().parent().parent().parent().attr("id");
            if ($("#" + id + "-append-button").length) {
                let w = $("#" + id + "-append-button").parent().parent().width() - $("#" + id + "-append-button").outerWidth();
                s2.parent().parent().parent().css("width", w + "px");
            }
        });

        if (params.title) {
            $("#modal").draggable({
                handle: "#modalHeader",
            });
        }

        let f;

        if (autofocus && !focus && !params.noFocus) {
            f = autofocus;
        }

        if (focus) {
            f = focus;
        }

        if (f) {
            if ($(`[data-input-focus="${f}"]`).length) {
                $(`[data-input-focus="${f}"]`).focus();
            } else {
                $("#" + f).focus();
            }
        }

        if (tabs.length && typeof params.tabActivate == "function") {
            params.tabActivate(_prefix, tabs[0], 0);
        }
    }, 150);

    $("#" + _prefix + "form").submit(function(e) { e.preventDefault(); });

    $(".modalFormOk").off("click").on("click", ok);
    $(".modalFormCancel").off("click").on("click", cancel);

    $(".cardFormSelectWithRotate").off("click").on("click", function () {
        let select = $("#" + $(this).attr("data-for"));
        let i = parseInt(select.attr("data-field-index"));
        let val = select.val();
        let first = select.children().first();
        let found = false;
        let next = false;
        select.children().each(function () {
            if (found) {
                next = $(this);
                return false;
            }
            if ($(this).attr("value") == val) {
                found = true;
            }
        });
        if (!next) {
            next = first;
        }
        select.val(next.attr("value"));

        if (typeof params.fields[i].select === "function") {
            params.fields[i].select(select, params.fields[i].id, _prefix);
        }
    });

    $(".cardFormPicker").off("click").on("click", function () {
        let input = $(this).attr("data-for");
        document.querySelector("#" + input).showPicker();
    });

    for (let i in params.fields) {
        if (params.fields[i].id === "-") continue;

        if (params.fields[i].value) {
            switch (params.fields[i].type) {
                case "select":
                case "email":
                case "number":
                case "tel":
                case "time":
                case "password":
                case "text":
                case "color":
                case "area":
                    $(`#${_prefix}${params.fields[i].id}`).val(params.fields[i].value);
                    break;

                case "date":
                    if (params.fields[i].sec) {
                        $(`#${_prefix}${params.fields[i].id}`).val(date('Y-m-d', params.fields[i].value));
                    } else {
                        $(`#${_prefix}${params.fields[i].id}`).val(date('Y-m-d', params.fields[i].value / 1000));
                    }
                    break;

                case "datetime-local":
                    if (params.fields[i].sec) {
                        $(`#${_prefix}${params.fields[i].id}`).val(date('Y-m-d', params.fields[i].value) + 'T' + date('H:i', params.fields[i].value));
                    } else {
                        $(`#${_prefix}${params.fields[i].id}`).val(date('Y-m-d', params.fields[i].value / 1000) + 'T' + date('H:i', params.fields[i].value / 1000));
                    }
                    break;

                case "multiselect":
                    $(`.checkBoxOption-${_prefix}-${params.fields[i].id}`).prop("checked", false);
                    for (let j in params.fields[i].value) {
                        $(`.checkBoxOption-${_prefix}-${params.fields[i].id}[data-id='${params.fields[i].value[j]}']`).prop("checked", true);
                    }
                    break;
            }
        }

        if (params.fields[i].button && typeof params.fields[i].button.click === "function") {
            if (params.fields[i].type == "button") {
                $(`#${_prefix}${params.fields[i].id}`).off("click").on("click", () => {
                    params.fields[i].button.click(_prefix);
                });
            } else {
                $(`#${_prefix}${params.fields[i].id}-button`).off("click").on("click", () => {
                    params.fields[i].button.click(_prefix);
                });
            }
        }

        if (params.fields[i].type == "select") {
            if (typeof params.fields[i].select === "function") {
                $(`#${_prefix}${params.fields[i].id}`).off("change").on("change", function () {
                    params.fields[i].select($(this), params.fields[i].id, _prefix);
                });
            }
        }

        if (params.fields[i].type == "select2") {
            let s2p = {
                language: lang["_code"],
                width: '100%',
            };

            if (params.fields[i].minimumResultsForSearch) {
                s2p.minimumResultsForSearch = params.fields[i].minimumResultsForSearch;
            }

            s2p.templateSelection = s2FormatS;

            if (!params.fields[i].createTags) {
                s2p.createTag = () => {
                    return undefined;
                }
            }

            if (params.fields[i].colorizeTags === true) {
                s2p.templateSelection = function(item, container) {
                    container.addClass("bg-" + systemColor(item.text));
                    return $(`<span class="bg-${systemColor(item.text)}">${item.text}</span>`);
                }
            }

            if (typeof params.fields[i].colorizeTags == "function") {
                s2p.templateSelection = params.fields[i].colorizeTags;
            }

            if (params.fields[i].tags) {
                s2p.tags = params.fields[i].tags;
            }

            if (params.fields[i].ajax) {
                s2p.ajax = params.fields[i].ajax;
            }

            s2p.templateResult = s2FormatR;

            s2p.escapeMarkup = function (m) {
                return m;
            }

            $(`#${_prefix}${params.fields[i].id}`).select2(s2p);

            if (typeof params.fields[i].select === "function") {
                $(`#${_prefix}${params.fields[i].id}`).off("select2:select").on("select2:select", function () {
                    params.fields[i].select($(this), params.fields[i].id, _prefix);
                });
            }

            if (params.fields[i].value) {
                $(`#${_prefix}${params.fields[i].id}`).val(params.fields[i].value).trigger("change");
            }

            $(`#${_prefix}${params.fields[i].id}`).next().css("width", "100%");
        }

        if (params.fields[i].type == "rich") {
            $(`#${_prefix}${params.fields[i].id}`).summernote({
                tabDisable: false,
                tabsize: 4,
                height: 300,
                minHeight: null,
                maxHeight: null,
                disableResizeEditor: true,
                lang: (lang["_code"] === "ru") ? "ru-RU" : "en-US",
                toolbar: [
                    [ 'font', [ 'bold', 'italic', 'underline', 'clear' ]],
                    [ 'fontsize', [ 'fontsize' ]],
                    [ 'color', [ 'color' ]],
                ],
            });
            let width = $(`#${_prefix}${params.fields[i].id}`).next().width();
            if (params.fields[i].value) {
                $(`#${_prefix}${params.fields[i].id}`).summernote("code", params.fields[i].value);
            }
            $(`#${_prefix}${params.fields[i].id}`).next().width(width).find(".note-editable").css("resize", "vertical");
        }

        if (params.fields[i].type == "code") {
            let editor = ace.edit(`${_prefix}${params.fields[i].id}`);

            if (modules.darkmode && modules.darkmode.isDark())
                editor.setTheme("ace/theme/one_dark");
            else
                editor.setTheme("ace/theme/chrome");
            editor.setOptions({
                enableBasicAutocompletion: true,
                enableSnippets: true,
                enableLiveAutocompletion: false
            });

            if (params.fields[i].language) {
                editor.session.setMode("ace/mode/" + params.fields[i].language);
            }

            params.fields[i].editor = editor;
            if (params.fields[i].value) {
                editor.setValue(params.fields[i].value, -1);
                editor.clearSelection();
            }

            editor.setFontSize(14);

            editor.commands.removeCommand("removeline");
            editor.commands.removeCommand("redo");

            editor.commands.addCommand({
                name: "removeline",
                description: "Remove line",
                bindKey: {
                    win: "Ctrl-Y",
                    mac: "Cmd-Y"
                },
                exec: function (editor) { editor.removeLines(); },
                scrollIntoView: "cursor",
                multiSelectAction: "forEachLine"
            });

            editor.commands.addCommand({
                name: "redo",
                description: "Redo",
                bindKey: {
                    win: "Ctrl-Shift-Z",
                    mac: "Command-Shift-Z"
                },
                exec: function (editor) { editor.redo(); }
            });

            let height = params.fields[i].height ? params.fields[i].height : 400;
            $(`#${_prefix}${params.fields[i].id}`).css("height", height + "px").css("resize", "vertical");
            new ResizeObserver(function () {
                editor.resize();
                editor.renderer.updateFull();
            }).observe($(`#${_prefix}${params.fields[i].id}`)[0]);

            if (params.fields[i].language == "markdown") {
                $(".markdown-preview").off("click").on("click", function () {
                    let p = $(this);
                    let i = p.attr("data-field");

                    if ($(`#${_prefix}${params.fields[i].id}-preview:visible`).length) {
                        p.text(i18n("preview"));
                        $(`#${_prefix}${params.fields[i].id}`).show();
                        $(`#${_prefix}${params.fields[i].id}-preview`).hide();
                        params.fields[i].editor.focus();
                    } else {
                        p.text(i18n("editor"));
                        let h = $(`#${_prefix}${params.fields[i].id}-div`).height();
                        $(`#${_prefix}${params.fields[i].id}`).hide();
                        $(`#${_prefix}${params.fields[i].id}-preview`).css("height", h + "px").html(convertLinks(rbtMdRender($.trim(params.fields[i].editor.getValue())))).show();
                    }
                });
            }

            let f;

            if (autofocus && !focus && !params.noFocus) {
                f = autofocus;
            }

            if (focus) {
                f = focus;
            }

            if (f && f == `${_prefix}${params.fields[i].id}`) {
                editor.focus();
            }
        }

        if (params.fields[i].type == "json") {
            let editor = ace.edit(`${_prefix}${params.fields[i].id}`);
            if (modules.darkmode && modules.darkmode.isDark())
                editor.setTheme("ace/theme/one_dark");
            else
                editor.setTheme("ace/theme/chrome");
            editor.setOptions({
                enableBasicAutocompletion: true,
                enableSnippets: true,
                enableLiveAutocompletion: false
            });
            editor.session.setMode("ace/mode/json");
            params.fields[i].editor = editor;
            if (params.fields[i].value) {
                editor.setValue(JSON.stringify(params.fields[i].value, null, 4), -1);
                editor.clearSelection();
            }
            editor.setFontSize(14);
            editor.commands.removeCommand("removeline");
            editor.commands.removeCommand("redo");
            editor.commands.addCommand({
                name: "removeline",
                description: "Remove line",
                bindKey: {
                    win: "Ctrl-Y",
                    mac: "Cmd-Y"
                },
                exec: function (editor) { editor.removeLines(); },
                scrollIntoView: "cursor",
                multiSelectAction: "forEachLine"
            });
            editor.commands.addCommand({
                name: "redo",
                description: "Redo",
                bindKey: {
                    win: "Ctrl-Shift-Z",
                    mac: "Command-Shift-Z"
                },
                exec: function (editor) { editor.redo(); }
            });
            let height = params.fields[i].height ? params.fields[i].height : 400;
            $(`#${_prefix}${params.fields[i].id}`).css("height", height + "px").css("resize", "vertical");
            new ResizeObserver(function () {
                editor.resize();
                editor.renderer.updateFull();
            }).observe($(`#${_prefix}${params.fields[i].id}`)[0]);

            let f;

            if (autofocus && !focus && !params.noFocus) {
                f = autofocus;
            }

            if (focus) {
                f = focus;
            }

            if (f && f == `${_prefix}${params.fields[i].id}`) {
                editor.focus();
            }
        }

        if (params.fields[i].type == "files") {
            $(`#${_prefix}${params.fields[i].id}`).off("dblclick").on("dblclick", function () {
                let id = $(this).attr("id");
                let fileNames = $(this).val();

                if (fileNames.length) {
                    mConfirm(i18n("deleteFile", fileNames.join(', ')), i18n("confirm"), i18n("delete"), () => {
                        for (let i in fileNames) {
                            let found;
                            do {
                                found = false;
                                for (let j in files[id]) {
                                    if (files[id][j].name == fileNames[i]) {
                                        files[id].splice(j, 1);
                                        found = true;
                                        break;
                                    }
                                }
                            } while (found);
                        }

                        $("#" + id).html("");
                        for (let j in files[id]) {
                            $("#" + id).append("<option>" + escapeHTML(files[id][j].name) + "</option>");
                        }
                    });
                }
            });

            $(`#${_prefix}${params.fields[i].id}-add`).off("click").on("click", function () {
                let id = $(this).attr("data-for");

                let mimeTypes;

                try {
                    mimeTypes = JSON.parse($(this).attr("data-mime-types"));
                } catch (e) {
                    //
                }

                let maxSize = parseInt($(this).attr("data-max-size"));

                loadFile(mimeTypes, maxSize, file => {
                    if (file) {
                        let already = false;

                        $("#" + id).each(function () {
                            if ($(this).text() == file.name) {
                                already = true;
                            }
                        });

                        if (!already) {
                            $("#" + id).append("<option>" + escapeHTML(file.name) + "</option>");
                            if (!files[id]) {
                                files[id] = [];
                            }
                            files[id].push(file);
                        } else {
                            error(i18n("fileAlreadyExists"));
                        }
                    }
                });
            });
        }

        if (params.fields[i].type == "jstree") {
            let tree = {
                core: {
                    data: params.fields[i].data,
                    check_callback: true,
                    animation: 0,
                    multiple: false,
                },
                themes: {
                    responsive: false,
                },
                types: {
                    default: {
                        icon: "far fa-fw fa-folder",
                    },
                    file: {
                        icon: "fa fa-file",
                    },
                    folder: {
                        icon: "far fa-fw fa-folder-open",
                    },
                },
                plugins: [
                    "changed",
                    "search",
                    "sort",
                    "types",
                ],
            };

            $(`#${_prefix}${params.fields[i].id}`).jstree(tree);

            $(`#${_prefix}${params.fields[i].id}-addRoot`).off("click").on("click", () => {
                xblur();
                params.fields[i].addRoot($(`#${_prefix}${params.fields[i].id}`));
            });

            $(`#${_prefix}${params.fields[i].id}-clear`).off("click").on("click", () => {
                xblur();
                if (!$(`#${_prefix}${params.fields[i].id}-clear`).hasClass("disabled")) {
                    $(`#${_prefix}${params.fields[i].id}`).jstree().deselect_all();
                }
            });

            $(`#${_prefix}${params.fields[i].id}-add`).off("click").on("click", () => {
                xblur();
                if (!$(`#${_prefix}${params.fields[i].id}-add`).hasClass("disabled")) {
                    params.fields[i].add($(`#${_prefix}${params.fields[i].id}`));
                }
            });

            $(`#${_prefix}${params.fields[i].id}-rename`).off("click").on("click", () => {
                xblur();
                if (!$(`#${_prefix}${params.fields[i].id}-rename`).hasClass("disabled")) {
                    params.fields[i].rename($(`#${_prefix}${params.fields[i].id}`));
                }
            });

            $(`#${_prefix}${params.fields[i].id}-delete`).off("click").on("click", () => {
                xblur();
                if (!$(`#${_prefix}${params.fields[i].id}-delete`).hasClass("disabled")) {
                    params.fields[i].delete($(`#${_prefix}${params.fields[i].id}`));
                }
            });

            if (params.fields[i].renamed) {
                $(`#${_prefix}${params.fields[i].id}`).off("set_text.jstree").on("set_text.jstree", params.fields[i].renamed);
            }

            $(`#${_prefix}${params.fields[i].id}-search`).off("keypress").on("keypress", e => {
                if (e.keyCode === 13) {
                    if (window.currentJsTreeTimeout) {
                        clearTimeout(window.currentJsTreeTimeout);
                        window.currentJsTreeTimeout = false;
                    }
                    e.preventDefault();
                    $(`#${_prefix}${params.fields[i].id}-search-button`).click();
                    return false;
                }
                if (window.currentJsTreeTimeout) {
                    clearTimeout(window.currentJsTreeTimeout);
                    window.currentJsTreeTimeout = false;
                }
                window.currentJsTreeTimeout = setTimeout(() => {
                    $(`#${_prefix}${params.fields[i].id}-search-button`).click();
                    window.currentJsTreeTimeout = false;
                }, 500);
            });

            $(`#${_prefix}${params.fields[i].id}-search`).off("keydown").on("keydown", e => {
                if (e.keyCode == 8 || e.keyCode == 46) {
                    if (window.currentJsTreeTimeout) {
                        clearTimeout(window.currentJsTreeTimeout);
                        window.currentJsTreeTimeout = false;
                    }
                    window.currentJsTreeTimeout = setTimeout(() => {
                        $(`#${_prefix}${params.fields[i].id}-search-button`).click();
                        window.currentJsTreeTimeout = false;
                    }, 500);
                }
            });

            $(`#${_prefix}${params.fields[i].id}-search-button`).off("click").on("click", () => {
                let str = $.trim($(`#${_prefix}${params.fields[i].id}-search`).val());
                if (str.length >= 1 || str.length == 0) {
                    if (params.fields[i].search === true) {
                        $(`#${_prefix}${params.fields[i].id}`).jstree(tree).search(str);
                    } else {
                        params.fields[i].search($(`#${_prefix}${params.fields[i].id}`), str);
                    }
                }
            });

            function jstreectl(enabled) {
                if (enabled) {
                    $(`#${_prefix}${params.fields[i].id}-clear`).removeClass("disabled");
                    $(`#${_prefix}${params.fields[i].id}-add`).removeClass("disabled");
                    $(`#${_prefix}${params.fields[i].id}-rename`).removeClass("disabled");
                    $(`#${_prefix}${params.fields[i].id}-delete`).removeClass("disabled");
                } else {
                    $(`#${_prefix}${params.fields[i].id}-clear`).addClass("disabled");
                    $(`#${_prefix}${params.fields[i].id}-add`).addClass("disabled");
                    $(`#${_prefix}${params.fields[i].id}-rename`).addClass("disabled");
                    $(`#${_prefix}${params.fields[i].id}-delete`).addClass("disabled");
                }
            }

            $(`#${_prefix}${params.fields[i].id}`).off("select_node.jstree").on("select_node.jstree", (e, data) => {
                jstreectl(data && data.selected && data.selected.length);
            });

            $(`#${_prefix}${params.fields[i].id}`).off("deselect_node.jstree").on("deselect_node.jstree", (e, data) => {
                jstreectl(data && data.selected && data.selected.length);
            });

            $(`#${_prefix}${params.fields[i].id}`).off("changed.jstree").on("changed.jstree", (e, data) => {
                jstreectl(data && data.selected && data.selected.length);
            });

            $(`#${_prefix}${params.fields[i].id}`).off("loaded.jstree").on("loaded.jstree", (e, data) => {
                jstreectl(data && data.selected && data.selected.length);
            });

            $(`#${_prefix}${params.fields[i].id}`).off("ready.jstree").on("ready.jstree", (e, data) => {
                jstreectl(data && data.selected && data.selected.length);
            });
        }

        if (params.fields[i].type == "multiselect") {
            function msf(id, filter) {
                if (filter) {
                    filter = filter.toLowerCase();
                    for (let i = 0; i < $("#" + id).children().length; i++) {
                        if ($("#" + id).children()[i].tagName == "DIV") {
                            if ($($("#" + id).children()[i]).text().toLowerCase().indexOf(filter) >= 0) {
                                $($("#" + id).children()[i]).show();
                            } else {
                                $($("#" + id).children()[i]).hide();
                            }
                        }
                    }
                } else {
                    for (let i = 0; i < $("#" + id).children().length; i++) {
                        if ($("#" + id).children()[i].tagName == "DIV") {
                            $($("#" + id).children()[i]).show();
                        }
                    }
                }
            }

            $(`#${_prefix}${params.fields[i].id}-filter-button`).off("click").on("click", function () {
                let i = $(this).attr("id");
                let f = $.trim($("#" + $(i.substring(0, i.length - 7))).val());
                msf(i.substring(0, i.length - 14));
            });

            $(`#${_prefix}${params.fields[i].id}-filter`).off("keypress").on("keypress", function (e) {
                let f = $.trim($(this).val());
                let i = $(this).attr("id");
                if (f) {
                    if (e.keyCode === 13) {
                        $(i + "-button").click();
                        e.preventDefault();
                    }
                }
            });

            $(`#${_prefix}${params.fields[i].id}-filter`).keyup(function (e) {
                let f = $.trim($(this).val());
                let i = $(this).attr("id");
                msf(i.substring(0, i.length - 7), f);
            });
        }

        if (params.fields[i].type == "sortable") {
            sortable(i);
            assignSortableHandlers(i);
        }
    }

    $(".jsform-tab-link").off("click").on("click", function () {
        let i = parseInt($(this).attr("data-tab-index"));
        $(`.jsform-tabbed-item`).hide();
        $(`.jsform-tabbed-item[data-tab-index="${i}"]`).show();
        $(`.jsform-tabbed-item[data-form-runtime-hide="1"]`).hide();
        $(`.jsform-nav-link`).removeClass("text-bold");
        $(`.jsform-nav-link[data-tab-index="${i}"]`).addClass("text-bold");

        setTimeout(() => {
            $(".select2-selection__rendered:visible").each(function () {
                let s2 = $(this);
                s2.css("width", s2.css("width"));
                // TODO: FIX IT!!!
                let id = s2.parent().parent().parent().parent().parent().attr("id");
                if (id && $("#" + id + "-append-button").length) {
                    let w = $("#" + id + "-append-button").parent().parent().width() - $("#" + id + "-append-button").outerWidth();
                    s2.parent().parent().parent().css("width", w + "px");
                }
            });

            if (!params.noFocus) {
                for (let f in params.fields) {
                    if (params.fields[f].tab == tabs[i] && !params.fields[f].readonly && !params.fields[f].disabled && !params.fields[f].hidden) {
                        if (params.fields[f].editor) {
                            params.fields[f].editor.focus();
                        } else {
                            if ($(`[data-input-focus="${_prefix + params.fields[f].id}"]`).length) {
                                $(`[data-input-focus="${_prefix + params.fields[f].id}"]`).focus();
                            } else {
                                $(`#${_prefix + params.fields[f].id}`).focus();
                            }
                        }
                        break;
                    }
                }
            }

            if (typeof params.tabActivate == "function") {
                params.tabActivate(_prefix, tabs[i], i);
            }
        }, 10);
    });

    setTimeout(() => {
        $(".checkAll").parent().css("z-index", parseIntEx($(".checkAll").parent().parent().css("z-index")) + 1);
    }, 10);

    $(".checkAll").off("click").on("click", function () {
        $(this).parent().parent().children().each(function () {
            if ($(this).children()[0].nodeName == "INPUT" && !$(this).children().prop('disabled')) {
                $(this).children().prop("checked", true);
            }
        });
    });

    $(".unCheckAll").off("click").on("click", function () {
        $(this).parent().parent().children().each(function () {
            if ($(this).children()[0].nodeName == "INPUT" && !$(this).children().prop('disabled')) {
                $(this).children().prop("checked", false);
            }
        });
    });

    $(".multiselect-checkbox").off("click").on("click", () => {
        xblur();
    });

    (new ClipboardJS('.form-cc', {
        text: function(trigger) {
            return trigger.getAttribute('data-clipboard-text');
        }
    })).on("success", () => {
        message(i18n("copied"), i18n("clipboard"), 3);
    });


    if (typeof params.done == "function") {
        params.done(_prefix);
    }

    return target;
}
