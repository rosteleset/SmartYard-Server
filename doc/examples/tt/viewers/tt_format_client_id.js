// function tt_client_id(value, issue, field, target) {

    if (target.startsWith('pwa')) {
        const { api, escapeHTML } = utils;
        return {
            data() {
                return {
                    value,
                    common: null,
                    phones: null,
                    error: null,
                    loading: true,
                }
            },
            computed: {
                url() {
                    return `https://lfi.bz/?#client_id=${this.value}`
                }
            },
            methods: {
                formattedPhone(phone_id) {
                    const pid = String(phone_id).padStart(6, '0');
                    return `${pid.substring(0, 3)}-${pid.substring(3)}`;
                },
                telLink(phone_id) {
                    return `tel:9${phone_id}`;
                },
            },
            async created() {
                api.GET('custom/custom', {
                    action: "client_info",
                    client_id: this.value,
                })
                    .then(response => {
                        this.common = response.custom.common;
                        this.phones = response.custom.phones;

                    })
                    .catch(e => {
                        this.error = error.message;
                    })
                    .finally(() => {
                        this.loading = false;
                    })
            },
            template: `
            <ion-progress-bar v-if="loading" type="indeterminate" />
             <ion-card v-else-if="common">
                <ion-card-content>
                <div>
                    <a :href="url" target="_blank" class="hoverable">
                    [{{ common.contract_name }}]
                    </a>
                </div>
                <div>{{ escapeHTML(common.client_name) }}</div>
                <div>{{ escapeHTML(common.address) }}</div>

                <div v-for="(phone, index) in phones" :key="index">
                    <ion-icon
                    name="chatbubble-ellipses-outline"
                    :color="phone.for_communication === 't' ? 'success' : ''"
                    :style="phone.for_communication === 't' ? {} : { opacity: '0.2' }" title="Для связи"></ion-icon>

                    <ion-icon name="notifications-outline"
                    :color="phone.for_notification === 't' ? 'success' : ''"
                    :style="phone.for_notification === 't' ? {} : { opacity: '0.2' }" title="Для уведомлений"></ion-icon>

                    <ion-icon name="home-outline"
                    :color="phone.for_control === 't' ? 'success' : ''"
                    :style="phone.for_control === 't' ? {} : { opacity: '0.2' }" title="Домофония"></ion-icon>

                    <ion-icon name="eye-off-outline"
                    :color="phone.hidden === 't' ? 'danger' : ''"
                    :style="phone.hidden === 't' ? {} : { opacity: '0.2' }" title="Скрытый"></ion-icon>

                    <a :href="telLink(phone.phone_id)" class="ml-2">[{{ formattedPhone(phone.phone_id) }}]</a>
                    <span class="ml-2">{{ phone.phone }}</span>
                    <span class="ml-2">{{ phone.name ? phone.name + (phone.mname ? ' ('+phone.mname+') ' : '') : '' }}</span>
                </div>
                </ion-card-content>
            </ion-card>
            <ion-label v-else color="danger">{{error}}</ion-label>
            `
        }

    } else {
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
                    h += `<span class="ml-2">${success.custom.phones[i].name ? (success.custom.phones[i].name + (success.custom.phones[i].mname ? (" (" + success.custom.phones[i].mname + ")") : "")) : ""}</span>`;
                    h += '<br />';
                }

                $("#" + id).html(h);
            });
        return '<span id="' + id + '"><i class="fas fa-fw fa-circle-notch fa-spin"></i></span>';
    }
// }