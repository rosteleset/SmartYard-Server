function cardTable(params) {
    let h = `<div class="card mt-2">`;
    let filterInput = '';
    let addButton = '';
    if (params.addButton || params.title || params.filter) {
        h += `<div class="card-header">`;
        if (params.addButton || params.title) {
            h += `<h3 class="card-title">`;
            if (params.addButton) {
                addButton = md5(guid());
                h += `<button id="${addButton}" class="btn btn-primary mr-2 btn-xs" title="${params.addButton.title}"><i class="fas fa-fw fa-plus-circle"></i></button>`;
            }
            if (params.title) {
                h += " " + params.title;
            }
            h += `</h3>`;
        }
        if (params.filter) {
            filterInput = md5(guid());
            h += `<div class="card-tools d-none d-md-block col-2">`;
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

    let clickableClass = md5(guid());
    let tableClass = md5(guid());

    for (let i = 0; i < rows.length; i++) {
        h += `<tr`;
        if (rows[i].class) {
            h += ` class="${rows[i].class}"`;
        }
        if (typeof rows[i].uid !== "undefined") {
            h += ` uid="${rows[i].uid}"`;
        }
        h += `>`;
        for (let j in rows[i].cols) {
            h += `<td rowId="${i}" colId="${j}" uid="${rows[i].uid}"`;
            if (rows[i].cols[j].nowrap) {
                h += ` nowrap`;
            }
            if (typeof rows[i].cols[j].click === "function") {
                h += ` class="hoverable ${clickableClass}"`;
            }
            h += `>`;
            h += rows[i].cols[j].data;
            h += "</td>";
        }
        if (rows[i].dropDown) {
            let ddId = md5(guid());
            h += `<td>`;
            h += `<div class="dropdown">`;
            h += `<button class="btn btn-outline-secondary dropdown-toggle btn-xs dropdown-toggle-no-icon" type="button" id="${ddId}" data-toggle="dropdown" data-boundary="window" aria-haspopup="true" aria-expanded="false">`;
            h += `<i class="fas fa-fw fa-ellipsis-v"></i>`;
            h += `</button>`;
            h += `<ul class="dropdown-menu dropdown-menu-right" aria-labelledby="${ddId}">`;
            for (let j in rows[i].dropDown) {
                if (typeof rows[i].dropDown[j].click === "function") {
                    h += `<li class="pointer dropdown-item menuItem-${tableClass}" rowId="${i}" dropDownId="${j}" uid="${rows[i].uid}">${rows[i].dropDown[j].title}</li>`;
                } else
                if (rows[i].dropDown[j].title === "-") {
                    h += `<li class="dropdown-divider"></li>`;
                }
            }
            h += `</ul>`;
            h += `</div>`;
            h += `</td>`;
        }
        h += `</tr>`;
    }

    h += `</tbody>`;
    h += `</table>`;
    h += `</div>`;
    h += `</div>`;

    if (params.target) {
        $(params.target).html(h);

        if (addButton && params.addButton && typeof params.addButton.click === "function") {
            $("#" + addButton).off("click").on("click", params.addButton.click);
        }

        $(".menuItem-" + tableClass).off("click").on("click", function () {
            rows[parseInt($(this).attr("rowId"))].dropDown[parseInt($(this).attr("dropDownId"))].click($(this).attr("uid"));
        });

        $("." + clickableClass).off("click").on("click", function () {
            rows[parseInt($(this).attr("rowId"))].cols[parseInt($(this).attr("colId"))].click($(this).attr("uid"));
        });

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
