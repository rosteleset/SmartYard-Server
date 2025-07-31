var contextMenusGlobalList = {};

function contextMenuGlobalHandler(item, data) {
    item = item.split(":");

    contextMenusGlobalList[item[0]](item[1], data);
}

function cleanupContextMenusGlobalList() {
    let present = {};

    $(".contextMenusGlobalList").each(function () {
        present[$(this).attr("id")] = true;
    });

    for (let i in contextMenusGlobalList) {
        if (!present[i]) {
            delete contextMenusGlobalList[i];
        }
    }
}

function menu(config) {
    let h = '';

    let xid = guid();

    h += `<span class="dropdown">`;
    h += `<span id="${xid}" class="contextMenusGlobalList pointer dropdown-toggle dropdown-toggle-no-icon" data-toggle="dropdown" data-boundary="window" aria-haspopup="true" aria-expanded="false" data-flip="false">`;
    if (config.icon) {
        h += `<i class="mr-1 fa-fw ${config.icon}"></i>`
    }
    if (config.text) {
        h += `<span class="hoverable ml-1">${config.text}</span>`;
    }
    h += '</span>';

    if (config.right) {
        h += `<ul class="dropdown-menu dropdown-menu-right" aria-labelledby="${xid}" style="min-width: 250px!important;">`;
    } else {
        h += `<ul class="dropdown-menu" aria-labelledby="${xid}" style="min-width: 250px!important;">`;
    }

    let icons = false;

    for (let i in config.items) {
        if (config.items[i].icon) {
            icons = true;
        }
    }

    for (let i in config.items) {
        if (config.items[i].text == "-") {
            if (config.items[i].hint) {
                h += `<li class="dropdown-divider hr-text-white" data-content="${config.items[i].hint}"></li>`;
            } else {
                h += `<li class="dropdown-divider"></li>`;
            }
        } else {
            let c = '';
            if (config.items[i].class) {
                c += " " + config.items[i].class;
            }
            if (config.items[i].disabled) {
                c += " disabled opacity-disabled";
            }
            if (config.items[i].selected) {
                c += " text-bold";
            }
            c = c.trim();
            if (config.items[i].data) {
                h += `<li class="pointer dropdown-item ${c}" onclick="contextMenuGlobalHandler('${xid + ":" + (config.items[i].id ? config.items[i].id : config.items[i].text)}', $(this).attr('data-menu'))" data-menu="${config.items[i].data}">`;
            } else {
                h += `<li class="pointer dropdown-item ${c}" onclick="contextMenuGlobalHandler('${xid + ":" + (config.items[i].id ? config.items[i].id : config.items[i].text)}')">`;
            }
            if (icons) {
                if (config.items[i].icon) {
                    h += `<i class="fa-fw mr-2 ${config.items[i].icon}"></i>`;
                } else {
                    h += `<i class="fas fa-fw mr-2"></i>`;
                }
            }
            h += config.items[i].text ? config.items[i].text : '&nbsp;';
            h += '</li>';
        }
    }

    h += '</ul>';
    h += '</span>';

    contextMenusGlobalList[xid] = config.click;

    return h;
}