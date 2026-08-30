({
    flatLabelsCache: {},
    activeScope: null,

    init: function () {
        moduleLoaded("addresses.flatPlog", this);

        $("#tableModalHeader").
        on("change.flatPlog", ".flat-plog-day-select", function () {
            let scope = modules.addresses.flatPlog.activeScope;
            if (!scope || !scope._plogDays) {
                return;
            }
            modules.addresses.flatPlog.loadDayEvents(scope, scope._plogDays, $(this).val());
        }).
        on("click.flatPlog", ".flat-plog-event-type-filter-menu", e => {
            e.stopPropagation();
        }).
        on("click.flatPlog", ".flat-plog-event-type-select-all", function (e) {
            e.preventDefault();
            e.stopPropagation();
            modules.addresses.flatPlog.setEventTypeFilter(modules.addresses.flatPlog.defaultEventTypeFilter());
        }).
        on("click.flatPlog", ".flat-plog-event-type-clear-all", function (e) {
            e.preventDefault();
            e.stopPropagation();
            modules.addresses.flatPlog.setEventTypeFilter([]);
        }).
        on("change.flatPlog", ".flat-plog-event-type-option", function () {
            let selected = [];
            $("#tableModalHeader .flat-plog-event-type-option:checked").each(function () {
                selected.push(parseInt($(this).val()));
            });
            modules.addresses.flatPlog.setEventTypeFilter(selected);
        }).
        on("click.flatPlog", ".flat-plog-export-csv", function (e) {
            e.preventDefault();
            modules.addresses.flatPlog.exportCsv();
        });

        $("#tableModal").on("hidden.bs.modal.flatPlog", () => {
            $("#tableModal").removeClass("flat-plog-modal");
            $("#tableModalHeader").removeClass("flat-plog-modal-header");
            $("#tableModalLabel").removeClass("flat-plog-modal-title");
            $("#tableModalHeader .flat-plog-header-controls").remove();
            if (modules.addresses.flatPlog.activeScope) {
                modules.addresses.flatPlog.activeScope._plogHeaderReady = false;
                modules.addresses.flatPlog.activeScope._plogTableReady = false;
            }
            modules.addresses.flatPlog.activeScope = null;
        });
    },

    ensurePlogStyles: function () {
        if ($("#flatPlogStyles").length) {
            return;
        }

        $("head").append(`
            <style id="flatPlogStyles">
                #tableModal.flat-plog-modal #tableModalBody {
                    overflow-y: auto;
                }

                #tableModal.flat-plog-modal .card-body.table-responsive {
                    overflow-x: auto;
                }

                #tableModalHeader.flat-plog-modal-header {
                    display: flex;
                    align-items: center;
                    flex-wrap: nowrap;
                    width: 100%;
                }

                #tableModalHeader.flat-plog-modal-header #tableModalLabel.flat-plog-modal-title {
                    flex: 0 1 auto;
                    max-width: 32%;
                    min-width: 120px;
                    margin-bottom: 0;
                    overflow: hidden;
                    text-overflow: ellipsis;
                    white-space: nowrap;
                }

                #tableModalHeader.flat-plog-modal-header .flat-plog-header-controls {
                    display: flex;
                    align-items: center;
                    flex: 1 1 auto;
                    min-width: 460px;
                    margin-left: 1rem;
                    margin-right: 0.5rem;
                }

                #tableModalHeader.flat-plog-modal-header .flat-plog-day-select-wrap {
                    width: 220px;
                    flex-shrink: 0;
                }

                #tableModalHeader.flat-plog-modal-header .flat-plog-event-type-filter-wrap {
                    flex-shrink: 0;
                    margin-left: 0.5rem;
                }

                #tableModalHeader.flat-plog-modal-header .flat-plog-export-csv {
                    flex-shrink: 0;
                    margin-left: 0.5rem;
                }

                #tableModalHeader.flat-plog-modal-header [data-dismiss="modal"] {
                    flex: 0 0 auto;
                    margin-left: auto;
                }

                #flatPlogTable {
                    table-layout: auto;
                    width: 100%;
                }

                #flatPlogTable th,
                #flatPlogTable td {
                    vertical-align: middle;
                    padding-left: 0.65rem;
                    padding-right: 0.65rem;
                }

                #flatPlogTable thead th {
                    white-space: normal !important;
                    line-height: 1.2;
                    overflow: visible;
                }

                #flatPlogTable .flat-plog-col-date {
                    width: 1%;
                    white-space: nowrap;
                }

                #flatPlogTable td.flat-plog-col-date {
                    white-space: nowrap;
                }

                #flatPlogTable .flat-plog-col-type {
                    min-width: 108px;
                }

                #flatPlogTable .flat-plog-col-entrance {
                    min-width: 72px;
                }

                #flatPlogTable .flat-plog-col-flat {
                    min-width: 92px;
                    width: 1%;
                }

                #flatPlogTable .flat-plog-flat-link {
                    font-weight: 600;
                }

                #flatPlogTable .flat-plog-col-detail {
                    min-width: 88px;
                }

                #flatPlogTable .flat-plog-col-preview {
                    width: 130px;
                    min-width: 130px;
                }

                #flatPlogTable .flat-plog-col-preview td {
                    padding-top: 0.35rem;
                    padding-bottom: 0.35rem;
                }

                .flat-plog-preview-wrap {
                    position: relative;
                    width: 120px;
                    height: 72px;
                    overflow: hidden;
                    border-radius: 4px;
                    background: #f4f6f9;
                    display: block;
                }

                .flat-plog-preview-wrap .flat-plog-preview-loading,
                .flat-plog-preview-wrap .flat-plog-preview-missing {
                    position: absolute;
                    inset: 0;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                }

                .flat-plog-preview-wrap .flat-plog-preview-thumb {
                    position: absolute;
                    inset: 0;
                    width: 100%;
                    height: 100%;
                    object-fit: cover;
                    object-position: center center;
                    border-radius: 4px;
                    cursor: zoom-in;
                    display: none;
                }
                #flatPlogTable .flat-plog-col-detail {
                    white-space: normal;
                    word-break: break-word;
                }

                #flatPlogTable tbody.flat-plog-loading {
                    opacity: 0.45;
                    pointer-events: none;
                }
            </style>
        `);
    },

    setEventTypeFilter: function (selected) {
        let scope = modules.addresses.flatPlog.activeScope;

        if (!scope || !scope._plogDays || !scope.selectedDay) {
            return;
        }

        scope.eventTypeFilter = selected;
        modules.addresses.flatPlog.syncHeaderState(scope, scope._plogDays, scope.selectedDay);
        modules.addresses.flatPlog.paintEvents(scope, scope._plogDays, scope.selectedDay);
    },

    buildTableBodyHtml: function (scope, events) {
        let rows = modules.addresses.flatPlog.eventRows(scope, events);
        let columns = modules.addresses.flatPlog.scopeColumns(scope);
        let html = "";

        for (let i in rows) {
            html += "<tr>";

            for (let j in rows[i].cols) {
                let col = rows[i].cols[j];
                let colDef = columns[j] || {};
                let classes = [];

                if (col.class) {
                    classes.push(col.class);
                } else if (colDef.class) {
                    classes.push(colDef.class);
                }

                if ((col.nowrap || colDef.nowrap) && classes.indexOf("flat-plog-col-date") < 0) {
                    classes.push("cut-text");
                }

                html += "<td";

                if (classes.length) {
                    html += ` class="${classes.join(" ")}"`;
                }

                html += ">" + col.data + "</td>";
            }

            html += "</tr>";
        }

        return html;
    },

    updateTableBody: function (scope, events) {
        let $tbody = $("#flatPlogTable tbody");

        if (!$tbody.length) {
            return false;
        }

        $tbody.html(modules.addresses.flatPlog.buildTableBodyHtml(scope, events));
        $("#flatPlogTable tfoot").hide();
        return true;
    },

    syncHeaderState: function (scope, days, selectedDay) {
        let $header = $("#tableModalHeader");
        let $select = $header.find(".flat-plog-day-select");

        if ($select.length && days) {
            if (days.length) {
                let options = "";

                for (let i in days) {
                    options += `<option value="${escapeHTML(days[i].day)}"${days[i].day === selectedDay ? " selected" : ""}>${escapeHTML(days[i].day)} (${days[i].events})</option>`;
                }

                $select.html(options).prop("disabled", false);
            } else {
                $select.html(`<option>${escapeHTML(i18n("addresses.plogNoEvents"))}</option>`).prop("disabled", true);
            }
        } else if (selectedDay && $select.length) {
            $select.val(selectedDay);
        }

        if (scope.mode === "key") {
            return;
        }

        $header.find(".flat-plog-event-type-filter-toggle").prop("disabled", !(days && days.length)).text(modules.addresses.flatPlog.eventTypeFilterLabel(scope.eventTypeFilter));
        $header.find(".flat-plog-event-type-option").prop("disabled", !(days && days.length)).each(function () {
            let type = parseInt($(this).val());
            $(this).prop("checked", scope.eventTypeFilter.indexOf(type) >= 0);
        });
        $header.find(".flat-plog-event-type-select-all, .flat-plog-event-type-clear-all").prop("disabled", !(days && days.length));
        $header.find(".flat-plog-export-csv").prop("disabled", !(days && days.length && selectedDay));
    },

    csvEscape: function (value) {
        value = value === null || value === undefined ? "" : String(value);

        if (/[",\n\r]/.test(value)) {
            return `"${value.replace(/"/g, '""')}"`;
        }

        return value;
    },

    csvHeaders: function (scope) {
        if (scope.mode === "key") {
            return [
                i18n("addresses.plogDate"),
                i18n("addresses.plogEntrance"),
                i18n("addresses.plogDetail"),
            ];
        }

        if (scope.mode === "house") {
            return [
                i18n("addresses.plogDate"),
                i18n("addresses.eventType"),
                i18n("addresses.plogEntrance"),
                i18n("addresses.plogFlat"),
                i18n("addresses.plogDetail"),
            ];
        }

        if (scope.mode === "entrance") {
            return [
                i18n("addresses.plogDate"),
                i18n("addresses.eventType"),
                i18n("addresses.plogFlat"),
                i18n("addresses.plogDetail"),
            ];
        }

        return [
            i18n("addresses.plogDate"),
            i18n("addresses.eventType"),
            i18n("addresses.plogEntrance"),
            i18n("addresses.plogDetail"),
        ];
    },

    csvEventRow: function (scope, event) {
        if (scope.mode === "key") {
            return [
                event.date,
                event.mechanizmaDescription || event.objectMechanizma || "",
                modules.addresses.flatPlog.formatDetail(event),
            ];
        }

        let row = [
            event.date,
            modules.addresses.flatPlog.formatEventType(event.event),
        ];

        if (scope.mode === "house") {
            row.push(
                event.mechanizmaDescription || event.objectMechanizma || "",
                modules.addresses.flatPlog.formatFlatLabel(event.flatId, scope),
            );
        } else if (scope.mode === "entrance") {
            row.push(modules.addresses.flatPlog.formatFlatLabel(event.flatId, scope));
        } else {
            row.push(event.mechanizmaDescription || event.objectMechanizma || "");
        }

        row.push(modules.addresses.flatPlog.formatDetail(event));

        return row;
    },

    exportFilename: function (scope) {
        let label = (scope.label || scope.id || "").toString().trim().replace(/[^\w\-]+/g, "_").replace(/_+/g, "_").replace(/^_|_$/g, "");

        return [
            "plog",
            scope.mode || "flat",
            scope.selectedDay || "",
            label,
        ].filter(Boolean).join("-") + ".csv";
    },

    exportCsv: function () {
        let scope = modules.addresses.flatPlog.activeScope;

        if (!scope || !scope.selectedDay) {
            return;
        }

        let events = modules.addresses.flatPlog.filterEventsByType(scope.allEvents || [], scope.eventTypeFilter);

        if (!events.length) {
            error(i18n("addresses.plogNothingToExport"));
            return;
        }

        let lines = [
            modules.addresses.flatPlog.csvHeaders(scope).map(modules.addresses.flatPlog.csvEscape).join(","),
        ];

        for (let i in events) {
            lines.push(modules.addresses.flatPlog.csvEventRow(scope, events[i]).map(modules.addresses.flatPlog.csvEscape).join(","));
        }

        let blob = new Blob(["\uFEFF" + lines.join("\r\n")], { type: "text/csv;charset=utf-8;" });
        let url = URL.createObjectURL(blob);
        let link = document.createElement("a");

        link.href = url;
        link.download = modules.addresses.flatPlog.exportFilename(scope);
        link.style.display = "none";
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(url);
    },

    refreshPlogTable: function (scope, events) {
        let params = modules.addresses.flatPlog.modalTableParams(scope, events);

        params.modal = true;
        params.target = "#tableModalBody";
        cardTable(params);
    },

    isPlogModalOpen: function () {
        return $("#tableModal").hasClass("flat-plog-modal") && $("#tableModal").hasClass("show");
    },

    modalTableParams: function (scope, events) {
        return {
            id: "flatPlogTable",
            caption: modules.addresses.flatPlog.scopeTitle(scope),
            columns: modules.addresses.flatPlog.scopeColumns(scope),
            rows: () => modules.addresses.flatPlog.eventRows(scope, events),
        };
    },

    buildFlatLabels: function (flats) {
        let map = {};

        for (let i in flats) {
            map[flats[i].flatId] = flats[i].flat;
        }

        return map;
    },

    ensureFlatLabels: function (houseId, callback) {
        houseId = parseInt(houseId);

        if (!houseId) {
            callback({});
            return;
        }

        if (modules.addresses.houses.meta && modules.addresses.houses.meta.flats && parseInt(modules.addresses.houses.houseId) === houseId) {
            let map = modules.addresses.flatPlog.buildFlatLabels(modules.addresses.houses.meta.flats);
            modules.addresses.flatPlog.flatLabelsCache[houseId] = map;
            callback(map);
            return;
        }

        if (modules.addresses.flatPlog.flatLabelsCache[houseId]) {
            callback(modules.addresses.flatPlog.flatLabelsCache[houseId]);
            return;
        }

        loadingStart();
        GET("houses", "house", houseId, true).
        done(response => {
            let map = modules.addresses.flatPlog.buildFlatLabels(response.house.flats || []);
            modules.addresses.flatPlog.flatLabelsCache[houseId] = map;
            callback(map);
        }).
        fail(FAIL).
        always(loadingDone);
    },

    formatEventType: function (eventType) {
        let key = "addresses.eventType" + parseInt(eventType);
        let label = i18n(key);
        return label === key ? eventType : label;
    },

    allEventTypes: function () {
        return [1, 2, 3, 4, 5, 6, 7, 9];
    },

    defaultEventTypeFilter: function () {
        return modules.addresses.flatPlog.allEventTypes().slice();
    },

    ensureEventTypeFilter: function (scope) {
        if (scope.eventTypeFilter === undefined || scope.eventTypeFilter === null) {
            scope.eventTypeFilter = modules.addresses.flatPlog.defaultEventTypeFilter();
        }
    },

    eventTypeFilterLabel: function (selected) {
        let all = modules.addresses.flatPlog.allEventTypes();

        if (!selected) {
            return i18n("addresses.plogEventTypes");
        }

        if (!selected.length) {
            return i18n("addresses.plogEventTypes") + " (0)";
        }

        if (selected.length >= all.length) {
            return i18n("addresses.plogEventTypes");
        }

        return i18n("addresses.plogEventTypes") + " (" + selected.length + ")";
    },

    filterEventsByType: function (events, types) {
        if (!events || !events.length) {
            return [];
        }

        if (!types || !types.length) {
            return [];
        }

        let allowed = {};

        for (let i in types) {
            allowed[parseInt(types[i])] = true;
        }

        return events.filter(event => allowed[parseInt(event.event)]);
    },

    formatDetail: function (event) {
        let detail = event.detailX || {};
        switch (parseInt(event.event)) {
            case 1:
            case 2:
                return detail.opened === "t" ? i18n("addresses.plogOpened") : i18n("addresses.plogNotOpened");
            case 3:
                return detail.key || "";
            case 4:
                return detail.phone || "";
            case 5:
                return detail.faceId || "";
            case 6:
                return detail.code || "";
            case 7:
                if (detail.phoneFrom && detail.phoneTo) {
                    return detail.phoneFrom + " → " + detail.phoneTo;
                }
                return detail.phoneFrom || detail.phoneTo || "";
            case 9:
                return detail.vehicle && detail.vehicle.plateNumber ? detail.vehicle.plateNumber : "";
            default:
                return "";
        }
    },

    formatFlatLabel: function (flatId, scope) {
        if (!flatId) {
            return "";
        }

        if (scope && scope.flatLabels && scope.flatLabels[flatId]) {
            return scope.flatLabels[flatId];
        }

        if (modules.addresses.houses.meta && modules.addresses.houses.meta.flats) {
            for (let i in modules.addresses.houses.meta.flats) {
                if (modules.addresses.houses.meta.flats[i].flatId == flatId) {
                    return modules.addresses.houses.meta.flats[i].flat;
                }
            }
        }

        return flatId;
    },

    formatFlatCell: function (flatId, scope) {
        if (!flatId) {
            return "";
        }

        let label = modules.addresses.flatPlog.formatFlatLabel(flatId, scope);

        return `<a href="#" class="flat-plog-flat-link nodec hoverable" data-flat-id="${escapeHTML(flatId)}" data-flat-label="${escapeHTML(label)}" title="${escapeHTML(i18n("addresses.flatPlog"))}">${escapeHTML(label)}</a>`;
    },

    bindFlatLinks: function () {
        $("#tableModalBody").off("click.flatPlogFlat").on("click.flatPlogFlat", ".flat-plog-flat-link", function (e) {
            e.preventDefault();
            e.stopPropagation();

            let flatId = $(this).attr("data-flat-id");
            let flatLabel = $(this).attr("data-flat-label");

            if (flatId) {
                modules.addresses.flatPlog.modalFlatPlog(flatId, flatLabel || flatId);
            }
        });
    },

    previewUrl: function (imageUuid) {
        return lStore("_server") + "/plog/camshot?uuid=" + encodeURIComponent(imageUuid) + "&_=" + Math.random();
    },

    ensurePreviewPopup: function () {
        if ($("#flatPlogPreviewPopup").length) {
            return;
        }

        $("body").append(`
            <div id="flatPlogPreviewPopup" class="flat-plog-preview-popup" style="display: none; position: fixed; inset: 0; z-index: 2000; align-items: center; justify-content: center;">
                <div class="flat-plog-preview-backdrop" style="position: absolute; inset: 0; background: rgba(0, 0, 0, 0.78);"></div>
                <div class="flat-plog-preview-content" style="position: relative; z-index: 1; max-width: 92vw; max-height: 92vh;">
                    <button type="button" class="close flat-plog-preview-close text-white" aria-label="${escapeHTML(i18n("close"))}" style="position: absolute; top: -36px; right: 0; font-size: 28px; opacity: 0.95; text-shadow: 0 0 6px rgba(0, 0, 0, 0.8);">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <img class="flat-plog-preview-image" src="" alt="" style="display: block; max-width: 92vw; max-height: 92vh; border-radius: 6px; box-shadow: 0 8px 32px rgba(0, 0, 0, 0.45);">
                </div>
            </div>
        `);

        let hidePreview = () => {
            $("#flatPlogPreviewPopup").hide();
            $("#flatPlogPreviewPopup .flat-plog-preview-image").attr("src", "");
        };

        $("#flatPlogPreviewPopup").on("click", ".flat-plog-preview-backdrop, .flat-plog-preview-close", hidePreview);
        $(document).on("keydown.flatPlogPreview", e => {
            if (e.key === "Escape") {
                hidePreview();
            }
        });
    },

    showPreviewPopup: function (url) {
        modules.addresses.flatPlog.ensurePreviewPopup();
        $("#flatPlogPreviewPopup .flat-plog-preview-image").attr("src", url);
        $("#flatPlogPreviewPopup").css("display", "flex");
    },

    bindPreviewClicks: function () {
        $("#tableModalBody").off("click.flatPlogPreview").on("click.flatPlogPreview", ".flat-plog-preview-thumb", function (e) {
            e.preventDefault();
            e.stopPropagation();
            let url = $(this).attr("data-url");
            if (url) {
                modules.addresses.flatPlog.showPreviewPopup(url);
            }
        });
    },

    formatPreviewPlaceholder: function () {
        return `<span class="flat-plog-no-preview text-muted small">${escapeHTML(i18n("addresses.plogNoPreview"))}</span>`;
    },

    formatPreview: function (event) {
        if (!event.imageUuid && !event.preview) {
            return modules.addresses.flatPlog.formatPreviewPlaceholder();
        }

        let url = event.preview || modules.addresses.flatPlog.previewUrl(event.imageUuid);
        let missing = escapeHTML(i18n("addresses.plogNoPreview"));
        return `
            <div class="flat-plog-preview-wrap">
                <i class="fas fa-spinner fa-spin flat-plog-preview-loading text-secondary"></i>
                <span class="flat-plog-preview-missing text-muted small" style="display: none;">${missing}</span>
                <img class="flat-plog-preview-thumb" src="${escapeHTML(url)}" data-url="${escapeHTML(url)}" alt="" title="${escapeHTML(i18n("addresses.plogPreview"))}">
            </div>`;
    },

    bindPreviewImages: function () {
        $("#tableModalBody .flat-plog-preview-wrap").each(function () {
            let $wrap = $(this);
            let $img = $wrap.find(".flat-plog-preview-thumb");
            let $loading = $wrap.find(".flat-plog-preview-loading");
            let $missing = $wrap.find(".flat-plog-preview-missing");

            $img.off("load.flatPlog error.flatPlog").on("load.flatPlog", () => {
                $loading.hide();
                $missing.hide();
                $wrap.css("background", "transparent");
                $img.show();
            }).on("error.flatPlog", () => {
                $loading.hide();
                $img.hide();
                $missing.show();
            });

            if ($img[0].complete) {
                if ($img[0].naturalWidth) {
                    $loading.hide();
                    $wrap.css("background", "transparent");
                    $img.show();
                } else {
                    $loading.hide();
                    $missing.show();
                }
            }
        });
    },

    scopeTitle: function (scope) {
        if (scope.mode === "key") {
            return i18n("addresses.keyPlogTitle", scope.label);
        }

        if (scope.mode === "entrance") {
            return i18n("addresses.entrancePlogTitle", scope.label);
        }

        if (scope.mode === "house") {
            return i18n("addresses.housePlogTitle", scope.label);
        }

        return i18n("addresses.flatPlogTitle", scope.label);
    },

    scopeColumns: function (scope) {
        if (scope.mode === "key") {
            return [
                { title: i18n("addresses.plogDate"), class: "flat-plog-col-date" },
                { title: i18n("addresses.plogEntrance"), nowrap: true, class: "flat-plog-col-entrance" },
                { title: i18n("addresses.plogDetail"), class: "flat-plog-col-detail" },
                { title: i18n("addresses.plogPreview"), nowrap: true, class: "flat-plog-col-preview" },
            ];
        }

        if (scope.mode === "house") {
            return [
                { title: i18n("addresses.plogDate"), class: "flat-plog-col-date" },
                { title: i18n("addresses.eventType"), nowrap: true, class: "flat-plog-col-type" },
                { title: i18n("addresses.plogEntrance"), nowrap: true, class: "flat-plog-col-entrance" },
                { title: i18n("addresses.plogFlat"), nowrap: true, class: "flat-plog-col-flat" },
                { title: i18n("addresses.plogDetail"), class: "flat-plog-col-detail" },
                { title: i18n("addresses.plogPreview"), nowrap: true, class: "flat-plog-col-preview" },
            ];
        }

        if (scope.mode === "entrance") {
            return [
                { title: i18n("addresses.plogDate"), class: "flat-plog-col-date" },
                { title: i18n("addresses.eventType"), nowrap: true, class: "flat-plog-col-type" },
                { title: i18n("addresses.plogFlat"), nowrap: true, class: "flat-plog-col-flat" },
                { title: i18n("addresses.plogDetail"), class: "flat-plog-col-detail" },
                { title: i18n("addresses.plogPreview"), nowrap: true, class: "flat-plog-col-preview" },
            ];
        }

        return [
            { title: i18n("addresses.plogDate"), class: "flat-plog-col-date" },
            { title: i18n("addresses.eventType"), nowrap: true, class: "flat-plog-col-type" },
            { title: i18n("addresses.plogEntrance"), nowrap: true, class: "flat-plog-col-entrance" },
            { title: i18n("addresses.plogDetail"), class: "flat-plog-col-detail" },
            { title: i18n("addresses.plogPreview"), nowrap: true, class: "flat-plog-col-preview" },
        ];
    },

    eventRows: function (scope, events) {
        let rows = [];

        for (let i in events) {
            let event = events[i];
            let cols = [
                { data: event.date, class: "flat-plog-col-date" },
            ];

            if (scope.mode === "key") {
                cols.push(
                    { data: event.mechanizmaDescription || event.objectMechanizma, nowrap: true },
                    { data: modules.addresses.flatPlog.formatDetail(event) },
                    { data: modules.addresses.flatPlog.formatPreview(event), nowrap: true },
                );
            } else {
                cols.push({ data: modules.addresses.flatPlog.formatEventType(event.event), nowrap: true });

                if (scope.mode === "house") {
                    cols.push(
                        { data: event.mechanizmaDescription || event.objectMechanizma, nowrap: true },
                        { data: modules.addresses.flatPlog.formatFlatCell(event.flatId, scope), nowrap: true, class: "flat-plog-col-flat" },
                    );
                } else if (scope.mode === "entrance") {
                    cols.push({ data: modules.addresses.flatPlog.formatFlatCell(event.flatId, scope), nowrap: true, class: "flat-plog-col-flat" });
                } else {
                    cols.push({ data: event.mechanizmaDescription || event.objectMechanizma, nowrap: true });
                }

                cols.push(
                    { data: modules.addresses.flatPlog.formatDetail(event) },
                    { data: modules.addresses.flatPlog.formatPreview(event), nowrap: true },
                );
            }

            rows.push({
                uid: event.uuid,
                cols: cols,
            });
        }

        return rows;
    },

    bindHeaderControls: function (scope, days, selectedDay) {
        if (scope._plogHeaderReady) {
            modules.addresses.flatPlog.syncHeaderState(scope, days, selectedDay);
            return;
        }

        modules.addresses.flatPlog.ensurePlogStyles();

        let $header = $("#tableModalHeader");
        let $modal = $("#tableModal");

        $modal.addClass("flat-plog-modal");
        $header.addClass("flat-plog-modal-header");

        let $close = $header.find("[data-dismiss='modal']").first();

        $header.find(".flat-plog-header-controls").remove();
        $close.detach().removeClass("ml-auto");

        $("#tableModalLabel").addClass("flat-plog-modal-title");

        let daySelectHtml;

        if (days.length) {
            let options = "";

            for (let i in days) {
                options += `<option value="${escapeHTML(days[i].day)}"${days[i].day === selectedDay ? " selected" : ""}>${escapeHTML(days[i].day)} (${days[i].events})</option>`;
            }

            daySelectHtml = `<select class="form-control form-control-sm flat-plog-day-select">${options}</select>`;
        } else {
            daySelectHtml = `<select class="form-control form-control-sm" disabled><option>${escapeHTML(i18n("addresses.plogNoEvents"))}</option></select>`;
        }

        let eventFilterHtml = "";

        if (scope.mode !== "key") {
            modules.addresses.flatPlog.ensureEventTypeFilter(scope);

            let items = "";

            for (let i in modules.addresses.flatPlog.allEventTypes()) {
                let type = modules.addresses.flatPlog.allEventTypes()[i];
                let checked = scope.eventTypeFilter.indexOf(type) >= 0 ? " checked" : "";
                items += `
                    <div class="custom-control custom-checkbox mb-1">
                        <input type="checkbox" class="custom-control-input flat-plog-event-type-option" id="flat-plog-event-type-${type}" value="${type}"${checked}${days.length ? "" : " disabled"}>
                        <label class="custom-control-label" for="flat-plog-event-type-${type}">${escapeHTML(modules.addresses.flatPlog.formatEventType(type))}</label>
                    </div>
                `;
            }

            eventFilterHtml = `
                <div class="flat-plog-event-type-filter-wrap">
                    <div class="dropdown">
                        <button type="button" class="btn btn-default btn-sm dropdown-toggle flat-plog-event-type-filter-toggle"${days.length ? "" : " disabled"} data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            ${escapeHTML(modules.addresses.flatPlog.eventTypeFilterLabel(scope.eventTypeFilter))}
                        </button>
                        <div class="dropdown-menu dropdown-menu-right p-2 flat-plog-event-type-filter-menu" style="min-width: 240px; max-height: 320px; overflow-y: auto;">
                            <div class="d-flex justify-content-end mb-2 pb-1 border-bottom">
                                <button type="button" class="btn btn-link btn-sm p-0 mr-2 flat-plog-event-type-select-all"${days.length ? "" : " disabled"}>${escapeHTML(i18n("checkAll"))}</button>
                                <button type="button" class="btn btn-link btn-sm p-0 flat-plog-event-type-clear-all"${days.length ? "" : " disabled"}>${escapeHTML(i18n("unCheckAll"))}</button>
                            </div>
                            ${items}
                        </div>
                    </div>
                </div>
            `;
        }

        $header.append(`
            <div class="flat-plog-header-controls">
                <div class="flat-plog-day-select-wrap">${daySelectHtml}</div>
                ${eventFilterHtml}
                <button type="button" class="btn btn-default btn-sm flat-plog-export-csv ml-2" title="${escapeHTML(i18n("addresses.plogExportCsv"))}"${days.length && selectedDay ? "" : " disabled"}>
                    <i class="fas fa-file-csv"></i>
                </button>
            </div>
        `);
        $header.append($close);
        scope._plogHeaderReady = true;
    },

    paintEvents: function (scope, days, selectedDay) {
        let events = modules.addresses.flatPlog.filterEventsByType(scope.allEvents || [], scope.eventTypeFilter);
        let $cardBody = $("#tableModalBody .card-body");

        $("#tableModalLabel").html(modules.addresses.flatPlog.scopeTitle(scope));

        if (!scope._plogTableReady) {
            if (scope._plogInPlace) {
                modules.addresses.flatPlog.refreshPlogTable(scope, events);
                scope._plogInPlace = false;
            } else {
                modalTable(modules.addresses.flatPlog.modalTableParams(scope, events));
            }

            scope._plogTableReady = true;

            if (!scope._plogHeaderReady) {
                modules.addresses.flatPlog.bindHeaderControls(scope, days, selectedDay);
            } else {
                modules.addresses.flatPlog.syncHeaderState(scope, days, selectedDay);
            }
        } else if (!modules.addresses.flatPlog.updateTableBody(scope, events)) {
            if (scope._plogInPlace) {
                modules.addresses.flatPlog.refreshPlogTable(scope, events);
                scope._plogInPlace = false;
            } else {
                modalTable(modules.addresses.flatPlog.modalTableParams(scope, events));
            }

            if (!scope._plogHeaderReady) {
                modules.addresses.flatPlog.bindHeaderControls(scope, days, selectedDay);
            } else {
                modules.addresses.flatPlog.syncHeaderState(scope, days, selectedDay);
            }
        } else {
            modules.addresses.flatPlog.syncHeaderState(scope, days, selectedDay);
        }

        $cardBody.find(".flat-plog-empty").remove();

        if (!events.length) {
            $cardBody.append(`<div class="flat-plog-empty text-center text-muted p-4">${escapeHTML(i18n("addresses.plogNoEvents"))}</div>`);
        }

        modules.addresses.flatPlog.bindPreviewClicks();
        modules.addresses.flatPlog.bindFlatLinks();
        modules.addresses.flatPlog.bindPreviewImages();
    },

    loadDayEvents: function (scope, days, selectedDay) {
        scope.selectedDay = selectedDay;
        scope._plogDays = days;

        let $tbody = $("#flatPlogTable tbody");
        if ($tbody.length) {
            $tbody.addClass("flat-plog-loading");
        }

        QUERY("plog", "events", modules.addresses.flatPlog.eventsQuery(scope, selectedDay), true).
        done(result => {
            scope.allEvents = result.events || [];
            modules.addresses.flatPlog.paintEvents(scope, days, selectedDay);
        }).
        fail(FAIL).
        always(() => {
            $("#flatPlogTable tbody").removeClass("flat-plog-loading");
        });
    },

    daysQuery: function (scope) {
        if (scope.mode === "key") {
            return {
                flatId: scope.id,
                events: "3",
                rfId: scope.rfId,
            };
        }

        if (scope.mode === "entrance") {
            return { entranceId: scope.id };
        }

        if (scope.mode === "house") {
            return { houseId: scope.id };
        }

        return { flatId: scope.id };
    },

    eventsQuery: function (scope, selectedDay) {
        let query = modules.addresses.flatPlog.daysQuery(scope);
        query.day = selectedDay;
        return query;
    },

    renderEvents: function (scope, days, selectedDay) {
        scope._plogDays = days;

        if (!selectedDay) {
            selectedDay = days.length ? days[0].day : false;
        }

        if (!selectedDay) {
            scope.allEvents = [];
            scope.selectedDay = false;
            modules.addresses.flatPlog.paintEvents(scope, days, false);
            return;
        }

        modules.addresses.flatPlog.ensureEventTypeFilter(scope);
        modules.addresses.flatPlog.loadDayEvents(scope, days, selectedDay);
    },

    openPlog: function (scope, inPlace) {
        inPlace = inPlace && modules.addresses.flatPlog.isPlogModalOpen();

        modules.addresses.flatPlog.activeScope = scope;
        scope._plogDays = null;
        scope.selectedDay = null;
        scope.eventTypeFilter = modules.addresses.flatPlog.defaultEventTypeFilter();
        scope.allEvents = [];
        scope._plogInPlace = inPlace;

        if (inPlace) {
            scope._plogTableReady = false;
            $("#tableModalLabel").html(modules.addresses.flatPlog.scopeTitle(scope));

            let $tbody = $("#flatPlogTable tbody");
            if ($tbody.length) {
                $tbody.addClass("flat-plog-loading");
            }
        } else {
            scope._plogHeaderReady = false;
            scope._plogTableReady = false;
            loadingStart();
        }

        QUERY("plog", "days", modules.addresses.flatPlog.daysQuery(scope), true).
        done(result => {
            let days = result.days || [];
            modules.addresses.flatPlog.renderEvents(scope, days);
        }).
        fail(FAIL).
        always(() => {
            if (inPlace) {
                $("#flatPlogTable tbody").removeClass("flat-plog-loading");
            } else {
                loadingDone();
            }
        });
    },

    modalFlatPlog: function (flatId, flatLabel) {
        modules.addresses.flatPlog.openPlog({
            mode: "flat",
            id: flatId,
            label: flatLabel,
        }, true);
    },

    modalKeyPlog: function (flatId, rfId, keyLabel, flatLabel) {
        modules.addresses.flatPlog.openPlog({
            mode: "key",
            id: flatId,
            rfId: rfId,
            label: keyLabel || rfId,
            flatLabel: flatLabel,
        });
    },

    modalEntrancePlog: function (entranceId, entranceLabel, houseId) {
        houseId = houseId || modules.addresses.houses.houseId;
        modules.addresses.flatPlog.ensureFlatLabels(houseId, flatLabels => {
            modules.addresses.flatPlog.openPlog({
                mode: "entrance",
                id: entranceId,
                label: entranceLabel,
                houseId: houseId,
                flatLabels: flatLabels,
            });
        });
    },

    modalHousePlog: function (houseId, houseLabel) {
        modules.addresses.flatPlog.ensureFlatLabels(houseId, flatLabels => {
            modules.addresses.flatPlog.openPlog({
                mode: "house",
                id: houseId,
                label: houseLabel,
                houseId: houseId,
                flatLabels: flatLabels,
            });
        });
    },
}).init();
