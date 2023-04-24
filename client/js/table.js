function cardTable(params) {
    let h = `<div class="card mt-2">`;
    let filterInput = '';
    let titleButton = '';
    let altButton = '';

    if (params.title) {
        h += `<div class="card-header">`;
        if (params.title.button || params.title.caption || params.title.altButton) {
            h += `<h3 class="card-title">`;
            if (params.title.button) {
                titleButton = md5(guid());
                let icon = params.title.button.icon?params.title.button.icon:"fas fa-plus-circle";
                h += `<button id="${titleButton}" type="button" class="btn btn-primary btn-xs btn-tool-rbt-left mr-2" title="${params.title.button.caption}"><i class="fa-fw ${icon}"></i></button>`;
            }
            if (params.title.caption) {
                h += " " + params.title.caption;
            }
            h += `</h3>`;
        }
        if (params.title.altButton) {
            altButton = md5(guid());
            let icon = params.title.altButton.icon?params.title.altButton.icon:"far fa-fw fa-times-circle";
            h += `<button id="${altButton}" type="button" class="btn btn-info btn-xs btn-tool-rbt-right ml-2 float-right" title="${params.title.altButton.caption}"><i class="fa-fw ${icon}"></i></button>`;
        }
        if (params.title.filter) {
            filterInput = md5(guid());
            h += `<div class="card-tools d-none d-md-block">`;
            h += '<form autocomplete="off">';
            h += `<div class="input-group input-group-sm">`;
            h += `<input id="${filterInput}" type="text" class="form-control float-right table-search-input" placeholder="${i18n("filter")}">`;
            h += `<div class="input-group-append">`;
            h += `<button type="submit" class="btn btn-default" id="${filterInput}-search-button"><i class="fas fa-filter"></i></button>`;
            h += `</div>`;
            h += `</div>`;
            h += '</form>';
            h += `</div>`;
        }
        h += `</div>`;
    }

    let pageLength = params.itemsPerPage?params.itemsPerPage:Number.MAX_VALUE;
    let pagerItemsCount = params.pagerItemsCount?params.pagerItemsCount:10;
    let currentPage = params.startPage?params.startPage:1;

    h += `<div class="card-body table-responsive p-0">`;
    h += `<table class="table table-hover">`;
    h += `<thead>`;

    let rows = [];
    let allRows = [];

    if (typeof params.rows === "function") {
        allRows = params.rows();
    }

    if (params && params.title) {
        doFilter(params.title.filter);
    } else {
        doFilter();
    }

    while (currentPage > Math.ceil(rows.length / pageLength) && currentPage > 1) {
        currentPage--;
    }

    let hasDropDowns = false;
    let hasDropDownIcons = false;

    for (let i in rows) {
        if (rows[i].dropDown && rows[i].dropDown.items) {
            for (let j in rows[i].dropDown.items) {
                if (rows[i].dropDown.items[j].icon) {
                    hasDropDownIcons = true;
                }
                if (!rows[i].dropDown.items[j].disabled && typeof rows[i].dropDown.items[j].click === "function") {
                    hasDropDowns = true;
                }
            }
        }
        if (hasDropDowns && hasDropDownIcons) {
            break;
        }
    }

    h += `<tr>`;
    if (typeof params.edit === "function") {
        h += `<th><i class="fa fa-fw"></i></th>`;
    }
    for (let i in params.columns) {
        if (params.columns[i].hidden) {
            continue;
        }
        if (params.columns[i].fullWidth) {
            h += `<th nowrap style="width: 100%">${params.columns[i].title}</th>`;
        } else {
            h += `<th nowrap>${params.columns[i].title}</th>`;
        }
    }
    if (hasDropDowns) {
        h += `<th><i class="fa fa-fw"></i></th>`;
    }
    h += `</tr>`;
    h += `</thead>`;

    let tableClass = md5(guid());
    let clickableClass = md5(guid());
    let editClass = md5(guid());

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
            if (typeof params.edit === "function") {
                h += `<td class="hoverable pointer ${editClass}" uid="${rows[i].uid}" title="${i18n("edit")}"><i class="far fa-faw fa-edit"></i></td>`;
            }
            for (let j in rows[i].cols) {
                if (rows[i].cols[j].hidden) {
                    continue;
                }
                h += `<td rowId="${i}" colId="${j}" uid="${rows[i].uid}"`;
                let clss = '';
                if (typeof rows[i].cols[j].click === "function") {
                    clss = `hoverable ${clickableClass} `;
                }
                if (rows[i].cols[j].nowrap) {
                    clss += "cut-text ";
                }
                clss = $.trim(clss);
                if (clss) {
                    h += ` class="${clss}"`;
                }
                if (rows[i].cols[j].fullWidth) {
                    h += ` width="100%"`;
                }
                h += `>`;
                if (typeof rows[i].cols[j].click === "string") {
                    h += `<a href="${sprintf(rows[i].cols[j].click, rows[i].uid)}" class="nodec hoverable">${rows[i].cols[j].data}</a>`;
                } else {
                    h += rows[i].cols[j].data;
                }
                h += "</td>";
            }
            if (rows[i].dropDown && hasDropDowns) {
                h += `<td>`;
                let t = '';
                let o = false;
                if (rows[i].dropDown.items.length === 1 && rows[i].dropDown.items[0].icon) {
                    t += `<span class="pointer`;
                    if (rows[i].dropDown.items[0].class) {
                        t += " " + rows[i].dropDown.items[0].class;
                    }
                    if (rows[i].dropDown.items[0].disabled || typeof rows[i].dropDown.items[0].click !== "function") {
                        t += ` disabled opacity-disabled`;
                    } else {
                        t += ` menuItem-${tableClass}`;
                        o = true;
                    }
                    t += `" title="${rows[i].dropDown.items[0].title}" rowId="${i}" dropDownId="0" uid="${rows[i].uid}" action="${rows[i].dropDown.items[0].action}">`;
                    t += `<i class="${rows[i].dropDown.items[0].icon} fa-fw"></i>`;
                    t += `</span>`;
                } else {
                    let ddId = md5(guid());
                    t += `<div class="dropdown">`;
                    t += `<span class="pointer dropdown-toggle dropdown-toggle-no-icon" id="${ddId}" data-toggle="dropdown" data-boundary="window" aria-haspopup="true" aria-expanded="false">`;
                    if (rows[i].dropDown.icon) {
                        t += `<i class="fa-fw ${rows[i].dropDown.icon}"></i>`;
                    } else {
                        t += `<i class="fa-fw fas fa-bars"></i>`;
                    }
                    t += `</span>`;
                    t += `<ul class="dropdown-menu dropdown-menu-right" aria-labelledby="${ddId}">`;
                    for (let j in rows[i].dropDown.items) {
                        if (rows[i].dropDown.items[j].title === "-") {
                            t += `<li class="dropdown-divider"></li>`;
                        } else {
                            t += `<li class="pointer dropdown-item`;
                            if (rows[i].dropDown.items[j].class) {
                                t += " " + rows[i].dropDown.items[j].class;
                            }
                            if (rows[i].dropDown.items[j].disabled || typeof rows[i].dropDown.items[j].click !== "function") {
                                t += ` disabled opacity-disabled`;
                            } else {
                                t += ` menuItem-${tableClass}`;
                                o = true;
                            }
                            t += `" rowId="${i}" dropDownId="${j}" uid="${rows[i].uid}" action="${rows[i].dropDown.items[j].action}">`;
                            if (rows[i].dropDown.items[j].icon) {
                                t += `<i class="${rows[i].dropDown.items[j].icon} fa-fw mr-2"></i>`;
                            } else {
                                if (hasDropDownIcons) {
                                    t += `<i class="fa fa-fw mr-2"></i>`;
                                }
                            }
                            t += `${rows[i].dropDown.items[j].title}</li>`;
                        }
                    }
                    t += `</ul>`;
                    t += `</div>`;
                }
                if (o) {
                    h += t;
                } else {
                    h += '<i class="fa fa-fw"></i>';
                }
                h += `</td>`;
            } else {
                if (hasDropDowns) {
                    h += `<td><i class="fa fa-fw"></i></td>`;
                }
            }
            h += `</tr>`;
        }

        return h;
    }

    h += tbody((currentPage - 1) * pageLength, pageLength)

    h += `</tbody>`;

    function pager(page) {
        page = parseInt(page);

        currentPage = page;

        let h = '';

        let pages = Math.ceil(rows.length / pageLength);
        let delta = Math.floor(pagerItemsCount / 2);
        let first = Math.max(page - delta, 1);
        let preFirst = Math.max(0, 1 - page + delta);
        let last = Math.min(page + delta, pages);
        let postLast = Math.max(pages, page + delta) - pages;

        if (last + preFirst - first + postLast >= pagerItemsCount) {
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

    let tfoot = md5(guid());
    h += `<tfoot id="${tfoot}">`;
    h += `<tr>`;

    let colCount = params.columns.length;
    if (hasDropDowns) {
        colCount++;
    }
    if (typeof params.edit === "function") {
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

    h += `</table>`;
    h += `</div>`;
    h += `</div>`;

    function doPager(_page) {
        let page = $(this).attr("page");
        if (typeof _page === "number") {
            page = _page;
        }
        $("#" + tableClass + "-pager").html(pager(page));
        $(`.${tableClass}-navButton`).off("click").on("click", doPager);
        $("#" + tableClass).html(tbody((currentPage - 1) * pageLength, pageLength));
        addHandlers();
        if (typeof params.pageChange === "function") {
            params.pageChange(page);
        }
        if (Math.ceil(rows.length / pageLength) > 1) {
            $("#" + tfoot).show();
        } else {
            $("#" + tfoot).hide();
        }
    }

    function doFilter(text, apply) {

        function match(row, words) {
            let str = "";
            for (let i in row.cols) {
                str += " " + row.cols[i].data;
            }
            str = $.trim(str.toLowerCase());
            let match = true;
            for (let i in words) {
                if (str.indexOf(words[i]) < 0) {
                    match = false;
                    break;
                }
            }
            return match;
        }

        if (typeof text !== "function" && text && text !== true) {
            rows = [];
            let words = text.toString().trim().toLowerCase().split(/\s+/).filter((value, index, self) => {
                return self.indexOf(value) === index;
            });
            for (let i in allRows) {
                if (match(allRows[i], words)) {
                    rows.push(allRows[i]);
                }
            }
        } else {
            rows = allRows;
        }

        if (apply) {
            doPager(1);
        }
    }

    let filterTimeout = false;

    function addHandlers() {
        $(".menuItem-" + tableClass).off("click").on("click", function () {
            rows[parseInt($(this).attr("rowId"))].dropDown.items[parseInt($(this).attr("dropDownId"))].click($(this).attr("uid"), $(this).attr("action"));
        });

        $("." + clickableClass).off("click").on("click", function () {
            rows[parseInt($(this).attr("rowId"))].cols[parseInt($(this).attr("colId"))].click($(this).attr("uid"));
        });

        $("." + editClass).off("click").on("click", function () {
            params.edit($(this).attr("uid"))
        });
    }

    if (params.target) {
        if (params.mode === "append") {
            $(params.target).append(h);
        } else {
            $(params.target).html(h);
        }

        if (Math.ceil(rows.length / pageLength) > 1) {
            $("#" + tfoot).show();
        } else {
            $("#" + tfoot).hide();
        }

        if (titleButton && params && params.title && params.title.button && typeof params.title.button.click === "function") {
            $("#" + titleButton).off("click").on("click", params.title.button.click);
        }

        if (altButton && params && params.title && params.title.altButton && typeof params.title.altButton.click === "function") {
            $("#" + altButton).off("click").on("click", params.title.altButton.click);
        }

        addHandlers();

        if (params && params.title && params.title.filter) {
            $("#" + filterInput).off("keyup").on("keyup", e => {
                if (filterTimeout) {
                    clearTimeout(filterTimeout);
                }
                filterTimeout = setTimeout(() => {
                    let f = $(e.currentTarget).val();
                    doFilter(f, true);
                    if (typeof params.filterChange === "function") {
                        params.filterChange(f);
                    }
                }, 500);
            });
            $("#" + filterInput + "-search-button").off("click").on("click", e => {
                let f = $(e.currentTarget).parent().parent().children().first().val();
                doFilter(f, true);
                if (typeof params.filterChange === "function") {
                    params.filterChange(f);
                }
            });
            if (params && params.title && params.title.filter && params.title.filter !== true) {
                $("#" + filterInput).val(params.title.filter);
                doFilter(params.title.filter, true);
            }
        }

        $(`.${tableClass}-navButton`).off("click").on("click", doPager);

        return $(params.target);
    } else {
        return h;
    }
}
