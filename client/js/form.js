function cardForm(params) {
    let _prefix = "modalForm-" + md5(guid()) + "-";
    let h = "";
    if (params.target) {
        h += `<div class="card mt-2">`;
    } else {
        h = `<div class="card mt-0 mb-0">`;
    }
    if (params.title) {
        h += `<div class="card-header">`;
        h += `<h3 class="card-title">`;
        if (params.topApply) {
            h += `<button class="btn btn-success mr-2 btn-xs modalFormOk" id="modalFormApply" title="${i18n("apply")}"><i class="fas fa-fw fa-check-circle"></i></button> `;
        }
        h += params.title;
        h += `</h3>`;
        h += `<button type="button" class="btn btn-danger btn-xs float-right modalFormCancel" data-dismiss="modal" title="${i18n("cancel")}"><i class="far fa-fw fa-times-circle"></i></button>`;
        h += `</div>`;
    }
    h += `<div class="card-body table-responsive p-0">`;
    if (params.borderless) {
        h += `<table class="table table-hover tform-borderless">`;
    } else {
        h += `<table class="table table-hover">`;
    }
    h += `<tbody>`;

    for (let i in params.fields) {
        switch (params.fields[i].type) {
            case "select":
                if (params.fields[i].hidden) {
                    h += `<tr style="display: none;">`;
                } else {
                    h += `<tr>`;
                }
                h += `<td class="tdform">${params.fields[i].title}</td>`;
                h += `<td class="tdform-right">`;
                h += `<div class="input-group">`;
                h += `<select id="${_prefix}${params.fields[i].id}" class="form-control modalFormField"`;
                if (params.fields[i].readonly) {
                    h += ` readonly="readonly"`;
                    h += ` disabled="disabled"`;
                }
                h += `>`;
                for (let j in params.fields[i].options) {
                    if (params.fields[i].options[j].value == params.fields[i].value) {
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
                h += `</td>`;
                h += `</tr>`;
                break;
            case "select2":
                if (params.fields[i].hidden) {
                    h += `<tr style="display: none;">`;
                } else {
                    h += `<tr>`;
                }
                h += `<td class="tdform">${params.fields[i].title}</td>`;
                h += `<td class="tdform-right">`;
                if (params.fields[i].color) {
                    h += `<div class="select2-${params.fields[i].color}">`;
                } else {
                    h += `<div class="select2-secondary">`;
                }
                h += `<select id="${_prefix}${params.fields[i].id}" class="form-control modalFormField select2`;
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
                    h += `<option value="${params.fields[i].options[j].value}">${params.fields[i].options[j].text}</option>`;
                }
                h += `</select>`;
                h += `</div>`;
                h += `</td>`;
                h += `</tr>`;
                break;
            case "email":
            case "tel":
            case "date":
            case "time":
            case "password":
            default:
                let type = params.fields[i].type?params.fields[i].type:"text";
                h += `<tr>`;
                h += `<td class="tdform">${params.fields[i].title}</td>`;
                h += `<td class="tdform-right">`;
                h += `<div class="input-group">`;
                h += `<input id="${_prefix}${params.fields[i].id}" type="${type}" class="form-control modalFormField" autocomplete="off" value="${params.fields[i].value?params.fields[i].value:""}" placeholder="${params.fields[i].placeholder?params.fields[i].placeholder:""}"`;
                if (params.fields[i].readonly) {
                    h += ` readonly="readonly"`;
                    h += ` disabled="disabled"`;
                }
                h += `>`;
                if (params.fields[i].button) {
                    h += `<div class="input-group-append">`;
                    h += `<span id="${_prefix}${params.fields[i].id}-button" class="input-group-text pointer"><i class="${params.fields[i].button.class}"></i></span>`;
                    h += `</div>`;
                }
                h += `</div>`;
                h += `</td>`;
                h += `</tr>`;
                break;
        }
    }

    h += `</tbody>`;

    if (params.footer) {
        h += `<tfoot>`;
        h += `<tr>`;
        h += `<td colspan="2">`;
        h += `<button type="submit" class="btn btn-primary modalFormOk">${i18n("apply")}</button>`;
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

    function ok() {
        $(".modalFormField").removeClass("is-invalid");
        let invalid = [];
        for (let i in params.fields) {
            if (params.fields[i].validate && typeof params.fields[i].validate === "function") {
                if (!params.fields[i].validate($(`#${_prefix}${params.fields[i].id}`).val())) {
                    invalid.push(`#${_prefix}${params.fields[i].id}`);
                }
            }
        }
        if (invalid.length === 0) {
            if (typeof params.callback === "function") {
                let result = {};
                for (let i in params.fields) {
                    result[params.fields[i].id] = $(`#${_prefix}${params.fields[i].id}`).val();
                }
                if (!params.target) {
                    $('#modal').modal('hide');
                }
                params.callback(result);
            }
        } else {
            for (let i in invalid) {
                $(invalid[i]).addClass("is-invalid");
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
        if (params.fields[i].button && typeof params.fields[i].button.click === "function") {
            $(`#${_prefix}${params.fields[i].id}-button`).off("click").on("click", params.fields[i].button.click);
        }
        if (params.fields[i].type === "select2") {
            $(`#${_prefix}${params.fields[i].id}`).select2({
                language: window.lang["_code"],
                minimumResultsForSearch: params.fields[i].minimumResultsForSearch?params.fields[i].minimumResultsForSearch:0,
            });
            if (typeof params.fields[i].select === "function") {
                $(`#${_prefix}${params.fields[i].id}`).off("select2:select").on("select2:select", function () {
                    params.fields[i].select($(this), params.fields[i].id, _prefix);
                });
            }
            if (params.fields[i].value) {
                $(`#${_prefix}${params.fields[i].id}`).val(params.fields[i].value).trigger("change");
            }
        }
    }

    return target;
}
