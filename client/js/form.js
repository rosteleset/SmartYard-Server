function cardForm(params) {
    let _prefix = "modalForm-" + md5(guid()) + "-";
    let h = "";
    if (params.target) {
        h += `<div class="card mt-2">`;
    } else {
        h = `<div class="card mt-0 mb-0">`;
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
    h += `<div class="card-body table-responsive p-0">`;

    h += '<table class="table';
    if (params.borderless) {
        h += " tform-borderless";
    }
    if (!params.noHover) {
        h += " table-hover";
    }
    h += '">';

    h += `<tbody>`;

    if (params.delete) {
        params.fields.push({
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
            ]
        });
    }

    let first = " no-border-top";

    for (let i in params.fields) {
        if (params.fields[i].id === "-") {
            h += "<tr class='mt-0 mb-0 pt-0 pb-0'>";
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
                if (params.fields[i].options[j].id && !params.fields[i].options[j].value) {
                    params.fields[i].options[j].value = params.fields[i].options[j].id;
                }
            }
        }
        if (params.fields[i].hidden) {
            h += `<tr style="display: none;">`;
        } else {
            h += `<tr>`;
        }
        params.fields[i].type = params.fields[i].type?params.fields[i].type:"text";
        if (!params.singleColumn) {
            if (params.fields[i].hint || params.fields[i].type === "multiselect" || params.fields[i].type === "area") {
                h += `<td class="tdform-top${first}">${params.fields[i].title}</td>`;
            } else {
                h += `<td class="tdform${first}">${params.fields[i].title}</td>`;
            }
        }
        h += `<td class="tdform-right${first}">`;

        first = "";

        switch (params.fields[i].type) {
            case "select":
                h += `<div class="input-group">`;
                h += `<select id="${_prefix}${params.fields[i].id}" class="form-control modalFormField"`;
                if (params.fields[i].readonly) {
                    h += ` readonly="readonly"`;
                    h += ` disabled="disabled"`;
                }
                h += `>`;
                for (let j in params.fields[i].options) {
                    if (params.fields[i].options[j].value == params.fields[i].value || params.fields[i].options[j].selected) {
                        h += `<option value="${params.fields[i].options[j].value}" selected>${params.fields[i].options[j].text}</option>`;
                    } else {
                        h += `<option value="${params.fields[i].options[j].value}">${params.fields[i].options[j].text}</option>`;
                    }
                }
                h += `</select>`;
                h += `<div class="input-group-append">`;
                if (params.fields[i].readonly) {
                    h += `<span class="input-group-text disabled" disabled="disabled"><i class="fas fa-fw fa-angle-double-right"></i></span>`;
                } else {
                    h += `<span class="input-group-text pointer cardFormSelectWithRotate"><i class="fas fa-fw fa-angle-double-right"></i></span>`;
                }
                h += `</div>`;
                h += `</div>`;
                break;

            case "select2":
                if (params.fields[i].color) {
                    h += `<div class="select2-${params.fields[i].color} modalFormField">`;
                } else {
                    h += `<div class="select2-secondary modalFormField">`;
                }
                h += `<select id="${_prefix}${params.fields[i].id}" class="form-control select2`;
                h += `"`;
                if (params.fields[i].readonly) {
                    h += ` readonly="readonly"`;
                    h += ` disabled="disabled"`;
                }
                if (params.fields[i].multiple) {
                    h += ` multiple="multiple"`;
                }
                h += `>`;
                for (let j in params.fields[i].options) {
                    if (params.fields[i].options[j].value == params.fields[i].value || params.fields[i].options[j].selected) {
                        h += `<option value="${params.fields[i].options[j].value}" selected>${params.fields[i].options[j].text}</option>`;
                    } else {
                        h += `<option value="${params.fields[i].options[j].value}">${params.fields[i].options[j].text}</option>`;
                    }
                }
                h += `</select>`;
                h += `</div>`;
                break;

            case "multiselect":
                if (params.target) {
                    h += `<div class="overflow-auto pl-1">`;
                } else {
                    // TODO: Do something with this!!! (max-height)
                    h += `<div class="overflow-auto pl-1" style="max-height: 400px;">`;
                    // TODO: Do something with this!!! (max-height)
                }
                for (let j in params.fields[i].options) {
                    let id = md5(guid());
                    let c = params.fields[i].options[j].checked || (typeof params.fields[i].value === "object" && Array.isArray(params.fields[i].value) && params.fields[i].value.indexOf(params.fields[i].options[j].id) >= 0);
                    h += `
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="checkBoxOption-${params.fields[i].id} custom-control-input" id="${id}" data-id="${params.fields[i].options[j].id}"${c?" checked":""}/>
                            <label for="${id}" class="custom-control-label form-check-label">${params.fields[i].options[j].text}</label>
                        </div>
                    `;
                }
                h += `</div>`;
                break;

            case "area":
                h += `<textarea id="${_prefix}${params.fields[i].id}" rows="5" class="form-control modalFormField overflow-auto" autocomplete="off" style="resize: none;" placeholder="${params.fields[i].placeholder?params.fields[i].placeholder:""}"`;
                if (params.fields[i].readonly) {
                    h += ` readonly="readonly"`;
                    h += ` disabled="disabled"`;
                }
                h += `></textarea>`;
                break;

            case "rich":
                h += `<textarea id="${_prefix}${params.fields[i].id}" rows="5" class="form-control modalFormField overflow-auto" autocomplete="off" style="resize: none;" placeholder="${params.fields[i].placeholder?params.fields[i].placeholder:""}"`;
                if (params.fields[i].readonly) {
                    h += ` readonly="readonly"`;
                    h += ` disabled="disabled"`;
                }
                h += `></textarea>`;
                break;

            case "text":
            case "email":
            case "tel":
            case "date":
            case "time":
            case "password":
                if (params.fields[i].button) {
                    h += `<div class="input-group">`;
                }
                h += `<input id="${_prefix}${params.fields[i].id}" type="${params.fields[i].type}" class="form-control modalFormField" autocomplete="off" placeholder="${params.fields[i].placeholder?params.fields[i].placeholder:""}"`;
                if (params.fields[i].readonly) {
                    h += ` readonly="readonly"`;
                    h += ` disabled="disabled"`;
                }
                h += `>`;
                if (params.fields[i].button) {
                    h += `<div class="input-group-append">`;
                    h += `<span id="${_prefix}${params.fields[i].id}-button" class="input-group-text pointer"><i class="fa-fw ${params.fields[i].button.class}"></i></span>`;
                    h += `</div>`;
                    h += `</div>`;
                }
                break;
        }

        if (params.fields[i].hint) {
            h += `<span class="text-secondary text-xs">${params.fields[i].hint}</span>`;
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
        h += `<button type="submit" class="btn btn-primary modalFormOk">${i18n(params.apply)}</button>`;
        if (typeof params.cancel === "function") {
            h += `<button type="cancel" class="btn btn-default float-right modalFormCancel">${i18n("cancel")}</button>`;
        }
        h += `</td>`;
        h += `</tr>`;
        h += `</tfoot>`;
    }

    h += `</table>`;
    h += `</div>`;
    h += `</div>`;

    function getVal(i) {
        switch (params.fields[i].type) {
            case "select":
            case "select2":
            case "email":
            case "tel":
            case "date":
            case "time":
            case "password":
            case "text":
            case "area":
                return $(`#${_prefix}${params.fields[i].id}`).val();

            case "multiselect":
                let o = [];
                $(`.checkBoxOption-${params.fields[i].id}`).each(function () {
                    if ($(this).prop("checked")) {
                        o.push($(this).attr("data-id"));
                    }
                });
                return o;

            case "rich":
                if (!$(`#${_prefix}${params.fields[i].id}`).summernote("isEmpty")) {
                    return $(`#${_prefix}${params.fields[i].id}`).summernote("code");
                } else {
                    return "";
                }
        }
    }

    function ok() {
        $(".modalFormField").removeClass("is-invalid select2-invalid");
        let invalid = [];
        if (!params.delete || $(`#${_prefix}delete`).val() !== "yes") {
            for (let i in params.fields) {
                if (params.fields[i].id === "-") continue;
                if (params.fields[i].validate && typeof params.fields[i].validate === "function") {
                    if (!params.fields[i].validate(getVal(i), _prefix)) {
                        invalid.push(i);
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
            for (let i in invalid) {
                if (params.fields[invalid[i]].type == "select2") {
                    $(`#${_prefix}${params.fields[invalid[i]].id}`).parent().addClass("select2-invalid");
                } else {
                    $(`#${_prefix}${params.fields[invalid[i]].id}`).addClass("is-invalid");
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
        if (params.title) {
            $("#modal").draggable({
                handle: "#modalHeader",
            });
        }
    }

    $(".modalFormOk").off("click").on("click", ok);
    $(".modalFormCancel").off("click").on("click", cancel);

    $(".cardFormSelectWithRotate").off("click").on("click", function () {
        let select = $(this).parent().parent().children().first();
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
    });

    for (let i in params.fields) {
        if (params.fields[i].id === "-") continue;

        if (params.fields[i].value) {
            switch (params.fields[i].type) {
                case "select":
                case "email":
                case "tel":
                case "date":
                case "time":
                case "password":
                case "text":
                case "area":
                    $(`#${_prefix}${params.fields[i].id}`).val(params.fields[i].value);
                    break;

                case "multiselect":
                    $(`.checkBoxOption-${params.fields[i].id}`).prop("checked", false);
                    for (let j in params.fields[i].value) {
                        $(`.checkBoxOption-${params.fields[i].id}[data-id='${params.fields[i].value[j]}']`).prop("checked", true);
                    }
                    break;
            }
        }

        if (params.fields[i].button && typeof params.fields[i].button.click === "function") {
            $(`#${_prefix}${params.fields[i].id}-button`).off("click").on("click", () => {
                params.fields[i].button.click(_prefix);
            });
        }

        if (params.fields[i].type === "select2") {
            let s2p = {
                language: lang["_code"],
            };

            if (params.fields[i].minimumResultsForSearch) {
                s2p.minimumResultsForSearch = params.fields[i].minimumResultsForSearch;
            }

            if (!params.fields[i].createTags) {
                s2p.createTag = () => {
                    return undefined;
                }
            }

            if (params.fields[i].tags) {
                s2p.tags = true;
            }

            if (params.fields[i].ajax) {
                s2p.ajax = params.fields[i].ajax;
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
        }
        if (params.fields[i].type === "rich") {
            $(`#${_prefix}${params.fields[i].id}`).summernote({
                tabDisable: false,
                tabsize: 4,
                height: 300,
                minHeight: null,
                maxHeight: null,
                disableResizeEditor: true,
                lang: (lang["_code"] === "ru") ? "ru-RU" : "en-US",
                toolbar: [
                    ['font', ['bold', 'italic', 'underline', 'clear']],
                    ['fontsize', ['fontsize']],
                    ['color', ['color']],
                ],
            });
            if (params.fields[i].value) {
                $(`#${_prefix}${params.fields[i].id}`).summernote("code", params.fields[i].value);
            }
        }
    }

    return target;
}
