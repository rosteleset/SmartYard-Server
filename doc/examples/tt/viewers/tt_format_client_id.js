//function tt_client_id (value, issue, field) {
    let id = "custom_view_client_id_" + md5((new Date().getTime()));
    
    QUERY("custom", "custom", {
        action: "client_info",
        client_id: value,
    }).
    fail(FAIL).
    fail(() => {
        $("#" + id).html("Неудалось загрузить информацию о клиенте")
    }).
    done(success => {
        let h = '';
        h += `<a href="https://lfi.bz/?#client_id=${value}" target="_blank" class="hoverable">[${success.custom.common.contract_name}]</a><br />`;
        h += escapeHTML(success.custom.common.client_name) + '<br />';
        h += escapeHTML(success.custom.common.address) + '<br />';
        h += '<br />';
        
        for (let i in success.custom.phones) {
            let pid = success.custom.phones[i].phone_id;
            let pif = sprintf("%06d", pid);
            pif = pif.substring(0, 3) + '-' + pif.substring(3);

            if (success.custom.phones[i].for_communication == "t") {
                h += '<i class="text-success far fa-fw fa-comments" title="Для связи"></i>';
            } else {
                h += '<i class="text-muted far fa-fw fa-comments" style="opacity: 0.2!important;"></i>';
            }

            if (success.custom.phones[i].for_notification == "t") {
                h += '<i class="ml-2 text-success far fa-fw fa-comment" title="Для уведомлений"></i>';
            } else {
                h += '<i class="ml-2 text-muted far fa-fw fa-comment" style="opacity: 0.2!important;"></i>';
            }

            if (success.custom.phones[i].for_control == "t") {
                h += '<i class="ml-2 text-success fas fa-fw fa-home" title="Домофония"></i>';
            } else {
                h += '<i class="ml-2 text-muted fas fa-fw fa-home" style="opacity: 0.2!important;"></i>';
            }

            if (success.custom.phones[i].hidden == "t") {
                h += '<i class="ml-2 text-danger far fa-fw fa-eye-slash" title="Скрытый"></i>';
            } else {
                h += '<i class="ml-2 text-muted far fa-fw fa-eye-slash" style="opacity: 0.2!important;"></i>';
            }

            h += `<a href="tel:9${pid}" target="_blank" class="ml-2 hoverable">[${pif}]</a><span class="ml-2">${success.custom.phones[i].phone}</span>`;
            h += `<span class="ml-2">${success.custom.phones[i].name?(success.custom.phones[i].name + (success.custom.phones[i].mname?(" (" + success.custom.phones[i].mname + ")"):"")):""}</span>`;
            h += '<br />';
        }
        
        $("#" + id).html(h); 
    });
	return '<span id="' + id + '"><i class="fas fa-fw fa-circle-notch fa-spin"></i></span>';
//}