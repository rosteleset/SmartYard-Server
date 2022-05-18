function cardForm(params) {
    let h = '';
    if (params.target) {
        h += `<div class="card mt-2">`;
    } else {
        if (params.width) {
            h = `<div class="card mt-0 mb-0">`;
        } else {
            h = `<div class="card mt-0 mb-0">`;
        }
    }
    if (params.title) {
        h += `<div class="card-header">`;
        h += `<h3 class="card-title">`;
        h += `<span class="text-success pointer" id="formApplyButton"><i class="fas fa-fw fa-check-circle mr-2" title="${i18n("apply")}"></i></span>`;
        h += " " + params.title;
        h += `</h3>`;
        h += `</div>`;
    }
    h += `<div class="card-body table-responsive p-0">`;
    if (params.borderless) {
        h += `<table class="table table-hover tform-borderless">`;
    } else {
        h += `<table class="table table-hover">`;
    }
    if (params.tableHeader) {
        h += `<thead>`;
        h += `<tr>`;
        h += `<th colspan="2">${params.tableHeader}</th>`;
        h += `</tr>`;
        h += `</thead>`;
    }
    h += `<tbody>`;

    for (let i in params.fields) {
        switch (params.fields[i].type) {
            case "email":
                h += `<tr>`;
                h += `<td class="tdform">${params.fields[i].title}</td>`;
                h += `<td class="tdform-right">`;
                h += `<input id="modalForm-${params.fields[i].id}" type="email" class="form-control form-control-sm" value="${params.fields[i].value?params.fields[i].value:""}" placeholder="${params.fields[i].placeholder?params.fields[i].placeholder:""}"`;
                if (params.fields[i].readonly) {
                    h += ` readonly="readonly"`;
                    h += ` disabled="disabled"`;
                }
                h += `>`;
                h += `</td>`;
                h += `</tr>`;
                break;
            case "tel":
                h += `<tr>`;
                h += `<td class="tdform">${params.fields[i].title}</td>`;
                h += `<td class="tdform-right">`;
                h += `<input id="modalForm-${params.fields[i].id}" type="tel" class="form-control form-control-sm" value="${params.fields[i].value?params.fields[i].value:""}" placeholder="${params.fields[i].placeholder?params.fields[i].placeholder:""}"`;
                if (params.fields[i].readonly) {
                    h += ` readonly="readonly"`;
                    h += ` disabled="disabled"`;
                }
                h += `>`;
                h += `</td>`;
                h += `</tr>`;
                break;
            case "date":
                h += `<tr>`;
                h += `<td class="tdform">${params.fields[i].title}</td>`;
                h += `<td class="tdform-right">`;
                h += `<input id="modalForm-${params.fields[i].id}" type="date" class="form-control form-control-sm" value="${params.fields[i].value?params.fields[i].value:""}" placeholder="${params.fields[i].placeholder?params.fields[i].placeholder:""}"`;
                if (params.fields[i].readonly) {
                    h += ` readonly="readonly"`;
                    h += ` disabled="disabled"`;
                }
                h += `>`;
                h += `</td>`;
                h += `</tr>`;
                break;
            case "time":
                h += `<tr>`;
                h += `<td class="tdform">${params.fields[i].title}</td>`;
                h += `<td class="tdform-right">`;
                h += `<input id="modalForm-${params.fields[i].id}" type="time" class="form-control form-control-sm" value="${params.fields[i].value?params.fields[i].value:""}" placeholder="${params.fields[i].placeholder?params.fields[i].placeholder:""}"`;
                if (params.fields[i].readonly) {
                    h += ` readonly="readonly"`;
                    h += ` disabled="disabled"`;
                }
                h += `>`;
                h += `</td>`;
                h += `</tr>`;
                break;
            case "select":
                h += `<tr>`;
                h += `<td class="tdform">${params.fields[i].title}</td>`;
                h += `<td class="tdform-right">`;
                h += `<select id="modalForm-${params.fields[i].id}" class="form-control form-control-sm"`;
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
                h += `</td>`;
                h += `</tr>`;
                break;
            default:
                h += `<tr>`;
                h += `<td class="tdform">${params.fields[i].title}</td>`;
                h += `<td class="tdform-right">`;
                h += `<input id="modalForm-${params.fields[i].id}" type="text" class="form-control form-control-sm" value="${params.fields[i].value?params.fields[i].value:""}" placeholder="${params.fields[i].placeholder?params.fields[i].placeholder:""}"`;
                if (params.fields[i].readonly) {
                    h += ` readonly="readonly"`;
                    h += ` disabled="disabled"`;
                }
                h += `>`;
                h += `</td>`;
                h += `</tr>`;
                break;
        }
    }

    h += `</tbody>`;

    h += `<tfoot>`;
    h += `<tr>`;
    h += `<td colspan="2">`;
    h += `<button type="submit" id="modalFormOk" class="btn btn-primary">${i18n("save")}</button>`;
    h += `<button type="cancel" id="modalFormCancel" class="btn btn-default float-right">${i18n("cancel")}</button>`;
    h += `</td>`;
    h += `</tr>`;
    h += `</tfoot>`;

    h += `</table>`;
    h += `</div>`;
    h += `</div>`;

    function ok() {
        if (typeof params.callback === "function") {
            let result = {};
            for (let i in params.fields) {
                result[params.fields[i].id] = $(`#modalForm-${params.fields[i].id}`).val();
            }
            $('#modal').modal('hide');
            params.callback(result);
        }
    }

    function cancel() {
        $('#modal').modal('hide');
    }

    if (params.target) {
        $(params.target).html(h);
    } else {
        modal(h);
    }

    $("#modalFormOk").off("click").on("click", ok);
    $("#modalFormCancel").off("click").on("click", cancel);
}
