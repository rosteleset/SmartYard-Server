/**
 * Generates a dynamic, interactive HTML table inside a Bootstrap card, supporting features like filtering, pagination, custom buttons, editable rows, and dropdown menus.
 *
 * @function
 * @param {Object} params - Configuration object for the table.
 * @param {string} [params.id] - Optional ID for the table element.
 * @param {string|HTMLElement|jQuery} [params.target] - Selector or element where the table will be rendered.
 * @param {string} [params.mode] - If "append", appends the table to the target; otherwise replaces content.
 * @param {Object} [params.title] - Card header configuration.
 * @param {Object} [params.title.button] - Main button in the header.
 * @param {string} [params.title.button.caption] - Button tooltip/caption.
 * @param {string} [params.title.button.icon] - FontAwesome icon class for the button.
 * @param {Function} [params.title.button.click] - Click handler for the button.
 * @param {string} [params.title.caption] - Title text for the card.
 * @param {boolean|string} [params.title.filter] - Enables filter input; if string, sets initial filter value.
 * @param {Object} [params.title.altButton] - Alternative button in the header.
 * @param {string} [params.title.altButton.caption] - Alt button tooltip/caption.
 * @param {string} [params.title.altButton.icon] - FontAwesome icon class for the alt button.
 * @param {Function} [params.title.altButton.click] - Click handler for the alt button.
 * @param {Array<Object>} params.columns - Array of column definitions.
 * @param {string} params.columns[].title - Column header text.
 * @param {boolean} [params.columns[].hidden] - If true, column is hidden.
 * @param {boolean} [params.columns[].fullWidth] - If true, column takes full width.
 * @param {Function} params.rows - Function returning an array of row objects.
 * @param {number} [params.itemsPerPage] - Number of rows per page (default: all).
 * @param {number} [params.pagerItemsCount] - Number of pager buttons to show (default: 10).
 * @param {number} [params.startPage] - Initial page number (default: 1).
 * @param {Function} [params.edit] - If provided, enables edit icon per row; function called with row UID.
 * @param {Object} [params.dropDownHeader] - Dropdown menu header configuration.
 * @param {string} [params.dropDownHeader.icon] - FontAwesome icon class for the dropdown header.
 * @param {string} [params.dropDownHeader.title] - Tooltip for the dropdown header.
 * @param {string} [params.dropDownHeader.menu] - Custom HTML for dropdown header.
 * @param {Function} [params.dropDownHeader.click] - Click handler for the dropdown header.
 * @param {Function} [params.pageChange] - Callback when page changes; receives new page number.
 * @param {Function} [params.filterChange] - Callback when filter changes; receives filter string.
 * @param {string|HTMLElement|jQuery} [params.append] - Content to append after the table.
 * @returns {jQuery|string} - If `params.target` is provided, returns the jQuery object for the rendered table; otherwise returns the table's HTML or ID.
 */

function cardTable(params) {
    let h = `<div class="card mt-2">`;
    let filterInput = '';
    let titleButton = '';
    let altButton = '';

    if (params.title && (params.title.button || params.title.caption || params.title.altButton)) {
        h += '<div class="card-header">';
        h += '<table cellpadding="0" cellspacing="0"><tr>';
        if (params.title.button) {
            titleButton = "button-" + md5(guid());
            let icon = params.title.button.icon ? params.title.button.icon : "fas fa-plus-circle";
            h += '<td>';
            h += `<button id="${titleButton}" type="button" class="btn btn-primary btn-sm mr-2" title="${params.title.button.caption}"><i class="fa-fw ${icon}"></i></button>`;
            h += '</td>';
        }
        h += '<td width="100%">';
        if (params.title.caption) {
            h += `<h3 class="card-title" style="margin-bottom: 0rem!important">`;
            h += params.title.caption;
            h += `</h3>`;
        } else {
            h += '&nbsp;';
        }
        h += '</td>';
        if (params.title.filter) {
            filterInput = "filter-" + md5(guid());
            h += '<td>';
            h += `<div class="card-tools d-none d-md-block">`;
            h += `<form id="${filterInput}-form" autocomplete="off" onsubmit="return false;" action="">`;
            h += `<input autocomplete="false" name="${filterInput}-hidden" type="text" style="display:none;">`;
            h += `<div class="input-group input-group-sm" style="flex-wrap: nowrap!important;">`;
            h += `<input id="${filterInput}" type="text" class="form-control table-search-input" style="width: 200px!important;" placeholder="${i18n("filter")}">`;
            h += `<div class="input-group-append">`;
            h += `<button type="button" class="btn btn-default" id="${filterInput}-search-button"><i class="fas fa-filter"></i></button>`;
            h += `</div>`;
            h += `</div>`;
            h += '</form>';
            h += `</div>`;
            h += '</td>';
        }
        if (params.title.altButton) {
            altButton = "altButton-" + md5(guid());
            h += '<td>';
            let icon = params.title.altButton.icon?params.title.altButton.icon:"far fa-fw fa-times-circle";
            h += `<button id="${altButton}" type="button" class="btn btn-info btn-sm ml-2" title="${params.title.altButton.caption}"><i class="fa-fw ${icon}"></i></button>`;
            h += '</td>';
        }
        h += '</tr></table>';
        h += `</div>`;
    }

    let pageLength = params.itemsPerPage ? params.itemsPerPage : Number.MAX_VALUE;
    let pagerItemsCount = params.pagerItemsCount ? params.pagerItemsCount : 10;
    let currentPage = params.startPage ? params.startPage : 1;

    h += `<div class="card-body table-responsive p-0">`;
    if (params.id) {
        h += `<table id= "${params.id}" class="table table-hover">`;
    } else {
        h += `<table class="table table-hover">`;
    }
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
        if (params.dropDownHeader) {
            if (params.dropDownHeader.menu) {
                h += `<th nowrap>${params.dropDownHeader.menu}</th>`;
            } else {
                params.dropDownHeader.id = "dropDownHeader" + md5(guid());
                h += `<th><i id="${params.dropDownHeader.id}" class="fa-fw ${params.dropDownHeader.icon} hoverable pointer" title="${params.dropDownHeader.title ? params.dropDownHeader.title : ''}"></i></th>`;
            }
        } else {
            h += `<th><i class="fa fa-fw"></i></th>`;
        }
    } else {
        if (params.dropDownHeader) {
            if (params.dropDownHeader.menu) {
                h += `<th nowrap>${params.dropDownHeader.menu}</th>`;
            } else {
                params.dropDownHeader.id = "dropDownHeader" + md5(guid());
                h += `<th><i id="${params.dropDownHeader.id}" class="fa-fw ${params.dropDownHeader.icon} hoverable pointer" title="${params.dropDownHeader.title ? params.dropDownHeader.title : ''}"></i></th>`;
            }
        }
    }

    h += `</tr>`;
    h += `</thead>`;

    let tableClass = "tableClass-" + md5(guid());
    let clickableClass = "clickableClass-" + md5(guid());
    let editClass = "editClass-" + md5(guid());

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
                if (j == params.columns.length - 1 && !hasDropDowns && params.dropDownHeader) {
                    h += ' colspan="2"';
                }
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
                if (rows[i].cols[j].ellipses) {
                    h += '<div class="ellipses-parent">';
                    h += '<div class="ellipses-children">';
                }
                if (typeof rows[i].cols[j].click === "string") {
                    h += `<a href="${rows[i].cols[j].click}" class="nodec hoverable">${rows[i].cols[j].data}</a>`;
                } else {
                    h += rows[i].cols[j].data;
                }
                if (rows[i].cols[j].ellipses) {
                    h += '</div>';
                    h += '</div>';
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
                    t += `" title="${rows[i].dropDown.items[0].title}" rowId="${i}" dropDownId="0" uid="${rows[i].uid}" action="${rows[i].dropDown.items[0].action?rows[i].dropDown.items[0].action:""}">`;
                    t += `<i class="${rows[i].dropDown.items[0].icon} fa-fw"></i>`;
                    t += `</span>`;
                } else {
                    let ddId = "ddId-" + md5(guid());
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
                            t += `" rowId="${i}" dropDownId="${j}" uid="${rows[i].uid}" action="${rows[i].dropDown.items[j].action?rows[i].dropDown.items[j].action:""}">`;
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

        let first, last;

        if (pages <= pagerItemsCount) {
            first = 1;
            last = pages;
        } else {
            if (page <= delta) {
                first = 1;
                last = pagerItemsCount;
            } else {
                first = page - delta + 1;
                last = first + pagerItemsCount - 1;
                if (last > pages) {
                    last = pages;
                    first = last - pagerItemsCount + 1;
                }
            }
        }

        if (first > 1) {
            h += `<li class="page-item pointer ${tableClass}-navButton" page="1"><span class="page-link" aria-label="Prev"><span aria-hidden="true">&laquo;</span><span class="sr-only">Prev</span></span></li>`;
        } else {
            h += `<li class="page-item disabled"><span class="page-link"><span aria-hidden="true">&laquo;</span></li>`;
        }

        for (let i = first; i <= last; i++) {
            if (currentPage == i) {
                h += `<li class="page-item pointer font-weight-bold ${tableClass}-navButton" page="${i}"><span class="page-link">${i}</span></li>`;
            } else {
                h += `<li class="page-item pointer ${tableClass}-navButton" page="${i}"><span class="page-link">${i}</span></li>`;
            }
        }

        if (last < pages) {
            h += `<li class="page-item pointer ${tableClass}-navButton" page="${pages}"><span class="page-link" aria-label="Next"><span aria-hidden="true">&raquo;</span><span class="sr-only">Next</span></></li>`;
        } else {
            h += `<li class="page-item disabled"><span class="page-link"><span aria-hidden="true">&raquo;</span></li>`;
        }

        return h;
    }

    let tfoot = "tfoot-" + md5(guid());
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
            if ($(this).attr("dropDownId") && rows[parseInt($(this).attr("rowId"))].dropDown.items[parseInt($(this).attr("dropDownId"))] && typeof rows[parseInt($(this).attr("rowId"))].dropDown.items[parseInt($(this).attr("dropDownId"))].click === "function") {
                rows[parseInt($(this).attr("rowId"))].dropDown.items[parseInt($(this).attr("dropDownId"))].click($(this).attr("uid"), $(this).attr("action"));
            }
        });

        $("." + clickableClass).off("click").on("click", function () {
            rows[parseInt($(this).attr("rowId"))].cols[parseInt($(this).attr("colId"))].click($(this).attr("uid"));
        });

        $("." + editClass).off("click").on("click", function () {
            params.edit($(this).attr("uid"))
        });

        if (params.dropDownHeader && typeof params.dropDownHeader.click == "function") {
            $("#" + params.dropDownHeader.id).off("click").on("click", params.dropDownHeader.click);
        }
    }

    if (params.target) {
        if (params.append) {
            h += `<span class="ml-2">${params.append}</span>`;
        }

        if (params.mode === "append") {
            $(params.target).append(h);
        } else {
            $(params.target).html(h);
        }

        $('.ellipses-children').each(function () {
            $(this).attr('title', $(this).text());
        });

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
        return params.id ? params.id : h;
    }
}
