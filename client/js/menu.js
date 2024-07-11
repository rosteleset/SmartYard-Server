var contextMenusGlobalList = {};

function contextMenuGlobalHandler(item, data) {
    item = item.split(":");

    contextMenusGlobalList[item[0]](item[1], data);
}

function menu(config) {
    let h = '';

    let xid = guid();

    h += `<span class="dropdown">`;
    h += `<span class="pointer hoverable dropdown-toggle dropdown-toggle-no-icon" data-toggle="dropdown" data-boundary="window" aria-haspopup="true" aria-expanded="false" data-flip="false" id="${xid}">${config.button}</span>`;
    h += `<ul class="dropdown-menu" aria-labelledby="${xid}" style="min-width: 250px!important;">`;

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
                h += `<li class="pointer dropdown-item ${c}" onclick="contextMenuGlobalHandler('${xid + ":" + (config.items[i].id ? config.items[i].id : config.items[i].text)}', $(this).attr('data-menu'))" data-menu="${config.items[i].data}">${config.items[i].text}</li>`;
            } else {
                h += `<li class="pointer dropdown-item ${c}" onclick="contextMenuGlobalHandler('${xid + ":" + (config.items[i].id ? config.items[i].id : config.items[i].text)}')">${config.items[i].text}</li>`;
            }
        }
    }

    h += '</ul>';
    h += '</span>';

    contextMenusGlobalList[xid] = config.click;

    return h;
}