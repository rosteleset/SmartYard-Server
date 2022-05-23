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

    let pageLength = params.itemsPerPage?params.itemsPerPage:25;
    let pagesCount = params.pagesCount?params.pagesCount:10;
    let currentPage = 1;
    let startPage = params.startPage?params.startPage:1;

    h += `<div class="card-body table-responsive p-0">`;
    if (params.filter) {
        h += `<table class="table table-hover ${filterInput}-search-table">`;
    } else {
        h += `<table class="table table-hover">`;
    }
    h += `<thead>`;

    let rows = [];

    if (typeof params.rows === "function") {
        rows = params.rows();
    }

    let hasDropDowns = false;
    let hasDropDownIcons = false;

    for (let i in rows) {
        if (rows[i].dropDown && rows[i].dropDown.items) {
            hasDropDowns = true;
            for (let j in rows[i].dropDown.items) {
                if (rows[i].dropDown.items[j].icon) {
                    hasDropDownIcons = true;
                }
            }
        }
        if (hasDropDowns && hasDropDownIcons) {
            break;
        }
    }

    h += `<tr>`;
    for (let i in params.columns) {
        if (params.columns[i].fullWidth) {
            h += `<th nowrap style="width: 100%">${params.columns[i].title}</th>`;
        } else {
            h += `<th nowrap>${params.columns[i].title}</th>`;
        }
    }
    if (hasDropDowns) {
        h += `<th>&nbsp;</th>`;
    }
    h += `</tr>`;
    h += `</thead>`;

    let tableClass = md5(guid());
    let clickableClass = md5(guid());

    h += `<tbody id="${tableClass}">`;

    function tbody(from, length) {
        let h = '';

        for (let i = from; i < Math.min(rows.length, from + length); i++) {
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
                h += `<button class="btn dropdown-toggle btn-xs dropdown-toggle-no-icon" type="button" id="${ddId}" data-toggle="dropdown" data-boundary="window" aria-haspopup="true" aria-expanded="false">`;
                if (rows[i].dropDown.icon) {
                    h += `<i class="fa-fw ${rows[i].dropDown.icon}"></i>`;
                } else {
                    h += `<i class="fa-fw fas fa-bars"></i>`;
                }
                h += `</button>`;
                h += `<ul class="dropdown-menu dropdown-menu-right" aria-labelledby="${ddId}">`;
                for (let j in rows[i].dropDown.items) {
                    if (typeof rows[i].dropDown.items[j].click === "function") {
                        h += `<li class="pointer dropdown-item`;
                        if (rows[i].dropDown.items[j].text) {
                            h += " " + rows[i].dropDown.items[j].text;
                        }
                        if (rows[i].dropDown.items[j].disabled) {
                            h += ` disabled opacity-disabled`;
                        } else {
                            h += ` menuItem-${tableClass}`;
                        }
                        h += `" rowId="${i}" dropDownId="${j}" uid="${rows[i].uid}" action="${rows[i].dropDown.items[j].action}">`;
                        if (rows[i].dropDown.items[j].icon) {
                            h += `<i class="${rows[i].dropDown.items[j].icon} fa-fw mr-2"></i>`;
                        } else {
                            if (hasDropDownIcons) {
                                h += `<i class="fa fa-fw mr-2"></i>`;
                            }
                        }
                        h += `${rows[i].dropDown.items[j].title}</li>`;
                    } else
                    if (rows[i].dropDown.items[j].title === "-") {
                        h += `<li class="dropdown-divider"></li>`;
                    }
                }
                h += `</ul>`;
                h += `</div>`;
                h += `</td>`;
            } else {
                if (hasDropDowns) {
                    h += `<td>&nbsp;</td>`;
                }
            }
            h += `</tr>`;
        }

        return h;
    }

    h += tbody((startPage - 1) * pageLength, pageLength)

    h += `</tbody>`;

    function pager(page) {
        page = parseInt(page);

        currentPage = page;

        let h = '';

        let pages = Math.ceil(rows.length / pageLength);
        let delta = Math.floor(pagesCount / 2);
        let first = Math.max(page - delta, 1);
        let preFirst = Math.max(0, 1 - page + delta);
        let last = Math.min(page + delta, pages);
        let postLast = Math.max(pages, page + delta) - pages;

        if (last + preFirst - first + postLast >= pagesCount) {
            if (first > 1) {
                first++;
            } else {
                last--;
            }
        }

        h += `<li class="page-item pointer ${tableClass}-navButton" page="1"><span class="page-link" aria-label="Prev"><span aria-hidden="true">&laquo;</span><span class="sr-only">Prev</span></span></li>`;
        for (let i = Math.max(first - postLast, 1); i <= Math.min(last + preFirst, pages); i++) {
            if (currentPage == i) {
                h += `<li class="page-item pointer font-weight-bold ${tableClass}-navButton" page="${i}"><span class="page-link">${i}</span></li>`;
            } else {
                h += `<li class="page-item pointer ${tableClass}-navButton" page="${i}"><span class="page-link">${i}</span></li>`;
            }
        }
        h += `<li class="page-item pointer ${tableClass}-navButton" page="${pages}"><span class="page-link" aria-label="Next"><span aria-hidden="true">&raquo;</span><span class="sr-only">Next</span></></li>`;

        return h;
    }

    if (Math.ceil(rows.length / pageLength) > 1) {
        h += `<tfoot>`;
        h += `<tr>`;

        let colCount = params.columns.length;
        if (hasDropDowns) {
            colCount++;
        }
        h += `<td colspan="${colCount}">`;

        h += `<nav>`;
        h += `<ul class="pagination mb-0 ml-0" id="${tableClass}-pager">`;

        h += pager(currentPage);

        h += `</ul>`;
        h += `</nav>`;
        h += `</td>`;
        h += `</tr>`;
        h += `</tfoot>`;
    }

    h += `</table>`;
    h += `</div>`;
    h += `</div>`;

    function doPager() {
        let page = $(this).attr("page");
        $("#" + tableClass + "-pager").html(pager(page));
        $(`.${tableClass}-navButton`).off("click").on("click", doPager);

        $("#" + tableClass).html(tbody((currentPage - 1) * pageLength, pageLength));

        if (typeof params.pageChange === "function") {
            params.pageChange(page);
        }
    }

    if (params.target) {
        $(params.target).html(h);

        if (addButton && params.addButton && typeof params.addButton.click === "function") {
            $("#" + addButton).off("click").on("click", params.addButton.click);
        }

        $(".menuItem-" + tableClass).off("click").on("click", function () {
            rows[parseInt($(this).attr("rowId"))].dropDown.items[parseInt($(this).attr("dropDownId"))].click($(this).attr("uid"), $(this).attr("action"));
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

        $(`.${tableClass}-navButton`).off("click").on("click", doPager);
    } else {
        return [ h, addButton, filterInput, tableClass, clickableClass, ];
    }
}
