function cardTable(params) {
    let h = `<div class="card mt-2">`;
    let filterInput = '';
    if (params.addButton || params.title || params.filter) {
        h += `<div class="card-header">`;
        if (params.addButton || params.title) {
            h += `<h3 class="card-title">`;
            if (params.addButton) {
                h += `<span class="text-primary pointer" id="${params.addButton.id}"><i class="fas fa-fw fa-plus-circle mr-2" title="${params.addButton.title}"></i></span>`;
            }
            if (params.title) {
                h += " " + params.title;
            }
            h += `</h3>`;
        }
        if (params.filter) {
            filterInput = md5(guid());
            h += `<div class="card-tools d-none d-md-block">`;
            h += `<div class="input-group input-group-sm">`;
            h += `<input id="${filterInput}" type="text" class="form-control float-right table-search-input" placeholder="${i18n("filter")}">`;
            h += `<div class="input-group-append">`;
            h += `<button type="submit" class="btn btn-default" id="${filterInput}-search-button"><i class="fas fa-filter"></i></button>`;
            h += `</div>`;
            h += `</div>`;
            h += `</div>`;
        }
        h += `</div>`;
    }
    h += `<div class="card-body table-responsive p-0">`;
    if (params.filter) {
        h += `<table class="table table-hover ${filterInput}-search-table">`;
    } else {
        h += `<table class="table table-hover">`;
    }
    h += `<thead>`;
    h += `<tr>`;
    for (let i in params.columns) {
        if (params.columns[i].fullWidth) {
            h += `<th nowrap style="width: 100%">${params.columns[i].title}</th>`;
        } else {
            h += `<th nowrap>${params.columns[i].title}</th>`;
        }
    }
    h += `</tr>`;
    h += `</thead>`;
    h += `<tbody>`;

    let rows = [];

    if (typeof params.rows === "function") {
        rows = params.rows();
    }

    for (let i = 0; i < rows.length; i++) {
        h += `<tr`;
        if (typeof rows[i].uid !== "undefined") {
            h += ` uid="${rows[i].uid}"`;
        }
        if (rows[i].class) {
            h += ` class="${rows[i].class}"`;
        }
        h += `>`;
        for (let j in rows[i].cols) {
            h += `<td`;
            if (rows[i].cols[j].nowrap) {
                h += ` nowrap`;
            }
            h += `>`;
            h += rows[i].cols[j].data;
            h += "</td>";
        }
        h += `</tr>`;
    }

    h += `</tbody>`;
    h += `</table>`;
    h += `</div>`;
/*
    h += `<div class="card-footer">
        <span class="ml-1 text-sm">${i18n("pager", 1, rows.length)}</span>
        <ul class="pagination pagination-sm m-0 float-right">
        <li class="page-item"><a class="page-link" href="#">«</a></li>
        <li class="page-item"><a class="page-link" href="#">1</a></li>
        <li class="page-item"><a class="page-link" href="#">2</a></li>
        <li class="page-item"><a class="page-link" href="#">3</a></li>
        <li class="page-item"><a class="page-link" href="#">»</a></li>
        </ul>
    </div>`;
*/
    h += `</div>`;

    if (params.target) {
        $(params.target).html(h);

        if (params.filter) {
            $("#" + filterInput).off("keyup").on("keyup", e => {
                let f = $(e.currentTarget).val();
                $.uiTableFilter($("." + filterInput + "-search-table"), f);
            });
            $("#" + filterInput + "-search-button").off("click").on("click", e => {
                let f = $(e.currentTarget).parent().parent().children().first().val();
                $.uiTableFilter($("." + filterInput + "-search-table"), f);
            });
        }
    } else {
        return h;
    }
}
