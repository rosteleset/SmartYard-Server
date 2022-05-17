function cardForm(params) {
    let h = '';
    if (params.target) {
        h += `<div class="card mt-2">`;
    } else {
        h = `<div class="card mt-0 mb-0">`;
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

    h += `<tr>`;
    h += `<td class="tdform">Полное имя пользователя</td>`;
    h += `<td class="tdform-right">`;
    h += `<input type="text" class="form-control form-control-sm">`;
    h += `</td>`;
    h += `</tr>`;

    h += `<tr>`;
    h += `<td class="tdform">Полное имя пользователя</td>`;
    h += `<td class="tdform-right">`;
    h += `<input type="date" class="form-control form-control-sm">`;
    h += `</td>`;
    h += `</tr>`;

    h += `<tr>`;
    h += `<td class="tdform">Полное имя пользователя</td>`;
    h += `<td class="tdform-right">`;
    h += `<input type="time" class="form-control form-control-sm">`;
    h += `</td>`;
    h += `</tr>`;

    h += `<tr>`;
    h += `<td class="tdform">Подключена</td>`;
    h += `<td class="tdform-right">`;
    h += `<select class="form-control form-control-sm">`;
    h += `<option>1</option>`;
    h += `<option>2</option>`;
    h += `<option>3</option>`;
    h += `<option>4</option>`;
    h += `</td>`;
    h += `</tr>`;

    h += `</tbody>`;

    h += `<tfoot>`;
    h += `<tr>`;
    h += `<td colspan="2">`;
    h += `<button type="submit" class="btn btn-primary">${i18n("save")}</button>`;
    h += `<button type="cancel" class="btn btn-default float-right">${i18n("cancel")}</button>`;
    h += `</td>`;
    h += `</tr>`;
    h += `</tfoot>`;

    h += `</table>`;
    h += `</div>`;
    h += `</div>`;

    if (params.target) {
        $(params.target).html(h);
    } else {
        modal(h);
    }
}
