({
    init: function () {
        // submodule - module<dot>submodule
        moduleLoaded("addresses.parking", this);
    },

    // state of the parking tab by form prefix (each opening of the camera form has its own prefix)
    states: {},

    colors: [ "#28a745", "#007bff", "#e83e8c", "#fd7e14", "#20c997", "#6f42c1", "#17a2b8", "#ffc107" ],

    available: function () {
        return AVAIL("cameras", "parking", "GET");
    },

    /**
     * field of the camera form, the tab content is rendered by renderTab()
     */

    field: function () {
        return {
            id: "parkingArea",
            type: "empty",
            title: false,
            tab: i18n("addresses.parking"),
            noHover: true,
            validate: (v, prefix) => {
                return !modules.addresses.parking.isDirty(prefix) || i18n("addresses.parkingUnsaved");
            },
        };
    },

    isDirty: function (prefix) {
        let state = modules.addresses.parking.states[prefix];

        return !!(state && state.dirty);
    },

    /**
     * prepare the tab container, nothing is loaded until the tab is activated
     */

    renderTab: function (prefix, cameraId) {
        let parking = modules.addresses.parking;

        for (let p in parking.states) {
            if (p != prefix) {
                parking.destroy(p);
            }
        }

        let state = {
            prefix,
            cameraId,
            editable: AVAIL("cameras", "parking", "PUT"),
            deletable: AVAIL("cameras", "parking", "DELETE"),
            loaded: false,
            loading: false,
            loadError: false,
            saved: null,
            draft: null,
            analysis: null,
            image: null,
            imageSize: null,
            map: null,
            overlay: null,
            group: null,
            active: null,
            mode: null,
            pending: [],
            fitted: false,
            dirty: false,
            saving: false,
            showSpaces: true,
            showGrid: false,
            calA: "",
            calB: "",
            checkLength: "2.5",
        };

        parking.states[prefix] = state;

        let h = "";
        // no gutters: the borderless form has no padding to absorb the negative margins of a row
        h += `<div class="row no-gutters">`;
        h += `<div class="col-lg-8 mb-2"><div id="${prefix}parkingMap" style="height: 560px; background: #6c757d;"></div></div>`;
        // the form cell sets white-space: nowrap, the panel text must wrap
        h += `<div class="col-lg-4 pl-lg-2 pr-3" id="${prefix}parkingPanel" style="max-height: 560px; overflow-y: auto; overflow-x: hidden; white-space: normal;"></div>`;
        h += `</div>`;

        let container = $(`#${prefix}parkingArea`).html(h);

        container.off(".parking").
        on("click.parking", "[data-parking-action]", function (e) {
            e.preventDefault();
            parking.action(state, $(this).attr("data-parking-action"), $(this).attr("data-index"));
            xblur();
        }).
        on("change.parking", "[data-parking-field]", function () {
            let el = $(this);
            parking.fieldChanged(state, el.attr("data-parking-field"), el.attr("type") == "checkbox" ? el.prop("checked") : el.val(), el.attr("data-index"));
        });

        parking.renderPanel(state);
    },

    /**
     * called on activation of any tab of the camera form: the dialog is wider on the parking tab only
     */

    tabActivated: function (prefix, tab) {
        let parking = modules.addresses.parking;
        let wide = tab == i18n("addresses.parking") && !!parking.states[prefix];
        let dialog = $("#modalBody").parent();

        dialog.toggleClass("modal-xxl", wide);

        if (wide) {
            // the class must not stay for other dialogs (cardForm also removes it on the next form)
            $("#modal").off("hide.bs.modal.parking").one("hide.bs.modal.parking", () => dialog.removeClass("modal-xxl"));
            parking.activate(prefix);
        }
    },

    activate: function (prefix) {
        let parking = modules.addresses.parking;
        let state = parking.states[prefix];

        if (!state) {
            return;
        }

        if (!state.loaded && !state.loading) {
            parking.load(state);
        } else if (state.map) {
            state.map.invalidateSize();
            if (!state.fitted) {
                parking.fit(state);
            }
        }
    },

    // the map can only be fitted while the tab is visible
    fit: function (state) {
        let f = modules.addresses.parking.frame(state);
        let el = document.getElementById(state.prefix + "parkingMap");

        state.fitted = !!(state.map && f && el && el.offsetWidth > 0);

        if (state.fitted) {
            state.map.invalidateSize();
            state.map.fitBounds([ [ -f.height, 0 ], [ 0, f.width ] ]);
        }
    },

    destroy: function (prefix) {
        let state = modules.addresses.parking.states[prefix];

        if (state && state.map) {
            try {
                state.map.remove();
            } catch (e) {
                //
            }
        }

        delete modules.addresses.parking.states[prefix];
    },

    // data

    load: function (state) {
        let parking = modules.addresses.parking;
        let pending = 2;
        let config = null;
        let configError = false;
        let image = null;

        state.loading = true;
        state.loadError = false;
        parking.renderPanel(state);

        function finish() {
            if (--pending) {
                return;
            }

            if (!parking.states[state.prefix]) {
                return;
            }

            if (configError) {
                state.loading = false;
                state.loadError = true;
                parking.renderPanel(state);
                return;
            }

            parking.setImage(state, image, () => {
                state.loading = false;
                state.loaded = true;
                parking.setSaved(state, config);
            });
        }

        GET("cameras", "parking", state.cameraId, true).
        done(r => {
            config = (r && r.parking) ? r.parking : null;
        }).
        fail(FAIL).
        fail(() => {
            configError = true;
        }).
        always(finish);

        GET("cameras", "camshot", state.cameraId, true).
        done(r => {
            image = (r && r.shot) ? ("data:image/jpg;base64," + r.shot) : null;
        }).
        always(finish);
    },

    setImage: function (state, image, callback) {
        if (!image) {
            state.image = null;
            state.imageSize = null;
            callback();
            return;
        }

        let img = new Image();

        img.onload = () => {
            state.image = image;
            state.imageSize = { width: img.naturalWidth, height: img.naturalHeight };
            callback();
        };

        img.onerror = () => {
            state.image = null;
            state.imageSize = null;
            callback();
        };

        img.src = image;
    },

    refreshImage: function (state) {
        let parking = modules.addresses.parking;

        GET("cameras", "camshot", state.cameraId, true).
        done(r => {
            parking.setImage(state, (r && r.shot) ? ("data:image/jpg;base64," + r.shot) : null, () => {
                parking.renderMap(state, true);
                parking.renderPanel(state);
            });
        }).
        fail(FAIL);
    },

    draftFrom: function (config) {
        return {
            revision: config.revision,
            enabled: config.enabled,
            frameWidth: config.frameWidth,
            frameHeight: config.frameHeight,
            calibration: config.calibration ? {
                points: JSON.parse(JSON.stringify(config.calibration.points)),
                checks: JSON.parse(JSON.stringify(config.calibration.checks || [])),
            } : null,
            areas: config.areas.map(a => ({
                parkingAreaId: a.parkingAreaId,
                name: a.name,
                enabled: a.enabled,
                imagePoints: JSON.parse(JSON.stringify(a.imagePoints)),
                baseEdge: a.baseEdge,
                spaceWidth: a.spaceWidth,
                spaceLength: a.spaceLength,
                spaceAngle: a.spaceAngle,
            })),
        };
    },

    setSaved: function (state, config) {
        let parking = modules.addresses.parking;

        state.saved = config;
        state.draft = config ? parking.draftFrom(config) : null;
        state.dirty = false;
        state.mode = null;
        state.pending = [];

        if (state.active !== null && (!state.draft || state.active >= state.draft.areas.length)) {
            state.active = null;
        }

        parking.syncCalibrationInputs(state);
        parking.analyze(state);
        parking.renderMap(state, true);
        parking.renderPanel(state);
    },

    payload: function (state) {
        let d = state.draft;

        return JSON.parse(JSON.stringify({
            revision: d.revision,
            enabled: d.enabled,
            frameWidth: d.frameWidth,
            frameHeight: d.frameHeight,
            calibration: d.calibration,
            areas: d.areas,
        }));
    },

    analyze: function (state) {
        state.analysis = state.draft ? parkingGeometry.analyze(modules.addresses.parking.payload(state)) : null;
    },

    changed: function (state, deferPanel) {
        let parking = modules.addresses.parking;

        state.dirty = true;
        parking.analyze(state);
        parking.renderMap(state);

        if (deferPanel) {
            // "change" fires on mousedown elsewhere: let a click on a panel button finish before the panel is replaced
            clearTimeout(state.panelTimeout);
            state.panelTimeout = setTimeout(() => {
                if (parking.states[state.prefix]) {
                    parking.renderPanel(state);
                }
            }, 200);
        } else {
            parking.renderPanel(state);
        }
    },

    save: function (state) {
        let parking = modules.addresses.parking;

        if (!state.draft || !state.analysis || !state.analysis.valid || state.saving) {
            return;
        }

        state.saving = true;
        parking.renderPanel(state);

        PUT("cameras", "parking", state.cameraId, parking.payload(state)).
        done(r => {
            state.saving = false;
            if (r && r.parking) {
                parking.setSaved(state, r.parking);
                message(i18n("addresses.parkingSaved"));
            }
        }).
        fail(response => {
            state.saving = false;
            parking.renderPanel(state);

            if (response && response.responseJSON && response.responseJSON.error == "parkingRevisionConflict") {
                mConfirm(i18n("addresses.parkingConflict"), i18n("confirm"), i18n("addresses.parkingReload"), () => {
                    state.loaded = false;
                    parking.load(state);
                });
            } else if (response && response.responseJSON && response.responseJSON.error == "parkingInvalidConfig" && response.getResponseHeader("x-last-error")) {
                error(i18n("addresses.parkingProblem_" + response.getResponseHeader("x-last-error")), i18n("error"), 30);
            } else {
                FAIL(response);
            }
        });
    },

    // geometry helpers

    frame: function (state) {
        if (state.draft) {
            return { width: state.draft.frameWidth, height: state.draft.frameHeight };
        }

        return state.imageSize;
    },

    toLatLng: function (state, p) {
        let f = modules.addresses.parking.frame(state);

        return [ -p.v * f.height, p.u * f.width ];
    },

    fromLatLng: function (state, ll) {
        let f = modules.addresses.parking.frame(state);
        let clamp = v => Math.min(1, Math.max(0, v));

        return { u: clamp(ll.lng / f.width), v: clamp(-ll.lat / f.height) };
    },

    // the same canonical order as on the server: clockwise on screen, base edge and angle keep their meaning
    canonical: function (state, area) {
        let f = modules.addresses.parking.frame(state);
        let p = area.imagePoints;

        if (parkingGeometry.signedArea(p.map(q => [ q.u * f.width, q.v * f.height ])) < 0) {
            area.imagePoints = [ p[0], p[3], p[2], p[1] ];
            area.baseEdge = (3 - area.baseEdge + 4) % 4;
            if (typeof area.spaceAngle == "number") {
                area.spaceAngle = -area.spaceAngle + 0;
            }
        }
    },

    longestEdge: function (state, area) {
        let f = modules.addresses.parking.frame(state);
        let h = state.analysis ? state.analysis.homography : null;
        let best = 0;
        let length = -1;

        for (let i = 0; i < 4; i++) {
            let a = area.imagePoints[i];
            let b = area.imagePoints[(i + 1) % 4];
            let l;

            if (h) {
                let pa = parkingGeometry.apply(h, a.u, a.v);
                let pb = parkingGeometry.apply(h, b.u, b.v);
                l = (pa[2] > 0 && pb[2] > 0) ? Math.hypot(pa[0] - pb[0], pa[1] - pb[1]) : -1;
            } else {
                l = Math.hypot((a.u - b.u) * f.width, (a.v - b.v) * f.height);
            }

            if (l > length) {
                length = l;
                best = i;
            }
        }

        return best;
    },

    areaAnalysis: function (state, index) {
        if (!state.analysis) {
            return null;
        }

        for (let a of state.analysis.areas) {
            if (a.index == index) {
                return a;
            }
        }

        return null;
    },

    calibrationSize: function (state) {
        let c = state.draft ? state.draft.calibration : null;

        if (!c || c.points.length != 4) {
            return null;
        }

        let p = c.points;
        let a = p[1].x, b = p[2].y;

        // calibration by a known rectangle: (0, 0), (A, 0), (A, B), (0, B)
        if (p[0].x === 0 && p[0].y === 0 && p[1].y === 0 && p[2].x === a && p[3].x === 0 && p[3].y === b) {
            return { a, b };
        }

        return null;
    },

    syncCalibrationInputs: function (state) {
        let s = modules.addresses.parking.calibrationSize(state);

        if (s) {
            state.calA = String(s.a);
            state.calB = String(s.b);
        }
    },

    number: function (v) {
        let n = parseFloat(String(v).replace(",", "."));

        return isFinite(n) ? n : NaN;
    },

    rectanglePoints: function (state, uv) {
        let parking = modules.addresses.parking;
        let a = parking.number(state.calA);
        let b = parking.number(state.calB);

        if (!(a > 0) || !(b > 0)) {
            return false;
        }

        let xy = [ [ 0, 0 ], [ a, 0 ], [ a, b ], [ 0, b ] ];

        return uv.map((p, i) => ({ u: p.u, v: p.v, x: xy[i][0], y: xy[i][1] }));
    },

    // user actions

    action: function (state, action, index) {
        let parking = modules.addresses.parking;
        let d = state.draft;

        index = (typeof index !== "undefined") ? parseInt(index) : null;

        switch (action) {
            case "retry":
                parking.load(state);
                break;

            case "refresh":
                parking.refreshImage(state);
                break;

            case "create":
                if (state.imageSize) {
                    state.draft = {
                        revision: 0,
                        enabled: 1,
                        frameWidth: state.imageSize.width,
                        frameHeight: state.imageSize.height,
                        calibration: null,
                        areas: [],
                    };
                    parking.changed(state);
                    parking.renderMap(state, true);
                }
                break;

            case "save":
                parking.save(state);
                break;

            case "revert":
                mConfirm(i18n("addresses.parkingConfirmRevert"), i18n("confirm"), `warning:${i18n("addresses.parkingRevert")}`, () => {
                    parking.setSaved(state, state.saved);
                });
                break;

            case "delete":
                mConfirm(i18n("addresses.parkingConfirmDelete"), i18n("confirm"), `danger:${i18n("addresses.parkingDelete")}`, () => {
                    DELETE("cameras", "parking", state.cameraId).
                    done(() => {
                        parking.setSaved(state, null);
                        message(i18n("addresses.parkingDeleted"));
                    }).
                    fail(FAIL);
                });
                break;

            case "acceptFrame":
                if (d && state.imageSize) {
                    d.frameWidth = state.imageSize.width;
                    d.frameHeight = state.imageSize.height;
                    parking.changed(state);
                    parking.renderMap(state, true);
                }
                break;

            case "addArea":
                parking.setMode(state, "area");
                break;

            case "calibrate":
                if (!(parking.number(state.calA) > 0) || !(parking.number(state.calB) > 0)) {
                    warning(i18n("addresses.parkingCalibrationSizeRequired"));
                } else {
                    parking.setMode(state, "calibration");
                }
                break;

            case "calibrationFromArea":
                if (state.active !== null && d.areas[state.active]) {
                    let points = parking.rectanglePoints(state, d.areas[state.active].imagePoints);

                    if (!points) {
                        warning(i18n("addresses.parkingCalibrationSizeRequired"));
                    } else {
                        d.calibration = { points, checks: d.calibration ? d.calibration.checks : [] };
                        parking.changed(state);
                    }
                }
                break;

            case "removeCalibration":
                d.calibration = null;
                parking.changed(state);
                break;

            case "addCheck":
                if (!(parking.number(state.checkLength) > 0)) {
                    warning(i18n("addresses.parkingCheckLengthRequired"));
                } else {
                    parking.setMode(state, "check");
                }
                break;

            case "deleteCheck":
                if (d.calibration) {
                    d.calibration.checks.splice(index, 1);
                    parking.changed(state);
                }
                break;

            case "cancelMode":
                parking.setMode(state, null);
                break;

            case "selectArea":
                state.active = (state.active === index) ? null : index;
                parking.renderMap(state);
                parking.renderPanel(state);
                break;

            case "deleteArea":
                mConfirm(i18n("addresses.parkingConfirmDeleteArea", escapeHTML(d.areas[index].name)), i18n("confirm"), `danger:${i18n("addresses.parkingDeleteArea")}`, () => {
                    d.areas.splice(index, 1);
                    state.active = null;
                    parking.changed(state);
                });
                break;

            case "rotateBase":
                if (state.active !== null) {
                    d.areas[state.active].baseEdge = (d.areas[state.active].baseEdge + 1) % 4;
                    parking.changed(state);
                }
                break;

            case "toggleSpaces":
                state.showSpaces = !state.showSpaces;
                parking.renderMap(state);
                parking.renderPanel(state);
                break;

            case "toggleGrid":
                state.showGrid = !state.showGrid;
                parking.renderMap(state);
                parking.renderPanel(state);
                break;
        }
    },

    fieldChanged: function (state, field, value, index) {
        let parking = modules.addresses.parking;
        let d = state.draft;
        let area = (state.active !== null && d) ? d.areas[state.active] : null;

        switch (field) {
            case "configEnabled":
                d.enabled = value ? 1 : 0;
                break;

            case "name":
                area.name = $.trim(value);
                break;

            case "areaEnabled":
                area.enabled = value ? 1 : 0;
                break;

            case "spaceWidth":
            case "spaceLength":
            case "spaceAngle":
                area[field] = parking.number(value);
                break;

            case "calA":
            case "calB":
                state[field] = value;
                if (d && d.calibration && d.calibration.points.length == 4) {
                    let points = parking.rectanglePoints(state, d.calibration.points);
                    if (points) {
                        d.calibration.points = points;
                    }
                }
                break;

            case "checkLength":
                state.checkLength = value;
                return;

            case "checkValue":
                if (d.calibration && d.calibration.checks[index]) {
                    d.calibration.checks[index].length = parking.number(value);
                }
                break;

            default:
                return;
        }

        parking.changed(state, true);
    },

    setMode: function (state, mode) {
        let parking = modules.addresses.parking;

        state.mode = mode;
        state.pending = [];

        if (mode) {
            state.active = null;
        }

        parking.renderMap(state);
        parking.renderPanel(state);
    },

    mapClick: function (state, e) {
        let parking = modules.addresses.parking;
        let d = state.draft;

        if (!state.mode || !d) {
            return;
        }

        let f = parking.frame(state);

        if (e.latlng.lng < 0 || e.latlng.lng > f.width || -e.latlng.lat < 0 || -e.latlng.lat > f.height) {
            return;
        }

        state.pending.push(parking.fromLatLng(state, e.latlng));

        if (state.mode == "area" && state.pending.length == 4) {
            let area = {
                parkingAreaId: null,
                name: i18n("addresses.parkingAreaDefaultName", d.areas.length + 1),
                enabled: 1,
                imagePoints: state.pending,
                baseEdge: 0,
                spaceWidth: 2.5,
                spaceLength: 5,
                spaceAngle: 0,
            };

            parking.canonical(state, area);
            area.baseEdge = parking.longestEdge(state, area);
            d.areas.push(area);
            state.mode = null;
            state.pending = [];
            state.active = d.areas.length - 1;
            parking.changed(state);
            return;
        }

        if (state.mode == "calibration" && state.pending.length == 4) {
            let points = parking.rectanglePoints(state, state.pending);

            d.calibration = { points, checks: d.calibration ? d.calibration.checks : [] };
            state.mode = null;
            state.pending = [];
            parking.changed(state);
            return;
        }

        if (state.mode == "check" && state.pending.length == 2) {
            if (d.calibration) {
                d.calibration.checks.push({
                    u1: state.pending[0].u,
                    v1: state.pending[0].v,
                    u2: state.pending[1].u,
                    v2: state.pending[1].v,
                    length: parking.number(state.checkLength),
                });
            }
            state.mode = null;
            state.pending = [];
            parking.changed(state);
            return;
        }

        parking.renderMap(state);
        parking.renderPanel(state);
    },

    polygonEdited: function (state, layer) {
        let parking = modules.addresses.parking;
        let area = state.draft ? state.draft.areas[layer._parkingIndex] : null;

        if (!area) {
            return;
        }

        area.imagePoints = layer.getLatLngs()[0].slice(0, 4).map(ll => parking.fromLatLng(state, ll));
        parking.canonical(state, area);

        // the edited layer is recreated, so leave the Leaflet.Editable handler first
        setTimeout(() => {
            if (parking.states[state.prefix]) {
                parking.changed(state);
            }
        }, 0);
    },

    // rendering

    renderMap: function (state, reset) {
        let parking = modules.addresses.parking;
        let el = document.getElementById(state.prefix + "parkingMap");
        let f = parking.frame(state);

        if (!el || !state.loaded || !f) {
            return;
        }

        if (!state.map) {
            state.map = L.map(el, {
                crs: L.CRS.Simple,
                editable: true,
                editOptions: {
                    skipMiddleMarkers: true,
                },
                minZoom: -6,
                maxZoom: 4,
                zoomSnap: 0.25,
                attributionControl: false,
            });

            state.map.addControl(new L.Control.Fullscreen({
                title: {
                    'false': i18n("fullscreen"),
                    'true': i18n("exitFullscreen"),
                }
            }));

            // vertices are never deleted or added: an area always has four of them
            state.map.on("editable:vertex:click", e => e.cancel());
            state.map.on("editable:vertex:dragend", e => parking.polygonEdited(state, e.layer));
            state.map.on("editable:dragend", e => parking.polygonEdited(state, e.layer));
            state.map.on("click", e => parking.mapClick(state, e));

            state.group = L.layerGroup().addTo(state.map);
            reset = true;
        }

        let bounds = [ [ -f.height, 0 ], [ 0, f.width ] ];

        if (reset) {
            if (state.overlay) {
                state.overlay.remove();
            }

            // the image is stretched to the frame of the markup: a frame of another shape is visibly distorted
            state.overlay = state.image ? L.imageOverlay(state.image, bounds) : L.rectangle(bounds, { color: "#343a40", weight: 1, fillOpacity: 0.6, interactive: false });
            state.overlay.addTo(state.map);
            state.map.setMaxBounds(L.latLngBounds(bounds).pad(0.5));
            parking.fit(state);
        }

        state.group.eachLayer(layer => {
            if (layer.editEnabled && layer.editEnabled()) {
                layer.disableEdit();
            }
        });
        state.group.clearLayers();

        let d = state.draft;

        if (!d) {
            return;
        }

        let a = state.analysis;
        let h = a ? a.homography : null;
        let ll = p => parking.toLatLng(state, p);
        let interactive = !state.mode;

        // grid on the parking plane, 1 m (5 m for large areas)
        if (state.showGrid && h) {
            let hi = parkingGeometry.invert(h);
            let xs = [], ys = [];

            for (let area of a.areas) {
                for (let p of (area.metricPoints || [])) {
                    xs.push(p.x);
                    ys.push(p.y);
                }
            }

            for (let p of a.calibration.points) {
                xs.push(p.x);
                ys.push(p.y);
            }

            if (hi && xs.length) {
                let x0 = Math.min(...xs) - 1, x1 = Math.max(...xs) + 1;
                let y0 = Math.min(...ys) - 1, y1 = Math.max(...ys) + 1;
                let step = Math.max(x1 - x0, y1 - y0) > 80 ? 5 : 1;
                let line = (p, q) => {
                    let pa = parkingGeometry.apply(hi, p[0], p[1]);
                    let pb = parkingGeometry.apply(hi, q[0], q[1]);
                    if (pa[2] > 0 && pb[2] > 0) {
                        L.polyline([ ll({ u: pa[0], v: pa[1] }), ll({ u: pb[0], v: pb[1] }) ], { color: "#ffc107", weight: 1, opacity: 0.6, interactive: false }).addTo(state.group);
                    }
                };

                for (let x = Math.ceil(x0 / step) * step; x <= x1; x += step) {
                    line([ x, y0 ], [ x, y1 ]);
                }

                for (let y = Math.ceil(y0 / step) * step; y <= y1; y += step) {
                    line([ x0, y ], [ x1, y ]);
                }
            }
        }

        d.areas.forEach((area, i) => {
            let color = parking.colors[i % parking.colors.length];
            let active = state.active === i;
            let analysis = parking.areaAnalysis(state, i);
            let points = area.imagePoints.map(ll);

            if (state.showSpaces && analysis && h) {
                for (let space of parkingGeometry.spaces(analysis, h)) {
                    L.polygon(space.points.map(ll), { color: "#ffffff", weight: 1, opacity: 0.8, fillOpacity: 0, interactive: false }).addTo(state.group);
                }
            }

            let polygon = L.polygon(points, {
                color,
                weight: active ? 3 : 2,
                fillOpacity: active ? 0.25 : 0.1,
                dashArray: area.enabled ? null : "6 6",
                interactive,
            }).addTo(state.group);

            polygon._parkingIndex = i;

            let label = escapeHTML(area.name || ("#" + (i + 1)));

            if (analysis && analysis.capacity !== null) {
                label += ": " + analysis.capacity;
            }

            polygon.bindTooltip(label, { permanent: true, direction: "center", interactive: false });

            if (interactive) {
                polygon.on("click", e => {
                    L.DomEvent.stop(e);
                    if (state.active !== i) {
                        parking.action(state, "selectArea", i);
                    }
                });
            }

            let b = area.baseEdge;

            if (b >= 0 && b <= 3) {
                L.polyline([ points[b], points[(b + 1) % 4] ], { color: "#fd7e14", weight: 6, opacity: 0.9, interactive: false }).addTo(state.group);
            }

            if (active && state.editable && interactive) {
                polygon.enableEdit(state.map);
            }
        });

        // calibration rectangle and control segments
        if (d.calibration) {
            let points = d.calibration.points;

            L.polygon(points.map(ll), { color: "#e83e8c", weight: 2, dashArray: "4 4", fillOpacity: 0, interactive: false }).addTo(state.group);

            points.forEach((p, i) => {
                let marker = L.marker(ll(p), {
                    interactive,
                    draggable: state.editable && interactive,
                    icon: L.divIcon({
                        className: "",
                        html: `<div style="width: 20px; height: 20px; margin: -10px 0 0 -10px; border-radius: 50%; background: #e83e8c; color: #fff; font-size: 12px; line-height: 20px; text-align: center;">${i + 1}</div>`,
                    }),
                }).addTo(state.group);

                marker.on("dragend", () => {
                    let uv = parking.fromLatLng(state, marker.getLatLng());
                    points[i].u = uv.u;
                    points[i].v = uv.v;
                    parking.changed(state);
                });
            });

            d.calibration.checks.forEach((c, i) => {
                let ends = [ { u: c.u1, v: c.v1 }, { u: c.u2, v: c.v2 } ];
                let measured = parking.checkLength(state, c);
                let line = L.polyline(ends.map(ll), { color: "#17a2b8", weight: 3, interactive: false }).addTo(state.group);

                line.bindTooltip(measured !== null ? (measured.toFixed(2) + " / " + c.length + " " + i18n("addresses.parkingMetres")) : String(c.length), { permanent: true, interactive: false });

                ends.forEach((p, k) => {
                    let marker = L.circleMarker(ll(p), { radius: 6, color: "#17a2b8", fillOpacity: 1, interactive: false }).addTo(state.group);

                    if (state.editable && interactive) {
                        // circle markers can't be dragged, a transparent marker takes it over
                        let handle = L.marker(ll(p), { draggable: true, opacity: 0 }).addTo(state.group);
                        handle.on("drag", () => marker.setLatLng(handle.getLatLng()));
                        handle.on("dragend", () => {
                            let uv = parking.fromLatLng(state, handle.getLatLng());
                            c["u" + (k + 1)] = uv.u;
                            c["v" + (k + 1)] = uv.v;
                            parking.changed(state);
                        });
                    }
                });
            });
        }

        // points placed in the current mode
        for (let p of state.pending) {
            L.circleMarker(ll(p), { radius: 5, color: state.mode == "calibration" ? "#e83e8c" : "#ffc107", fillOpacity: 1, interactive: false }).addTo(state.group);
        }

        if (state.pending.length > 1) {
            L.polyline(state.pending.map(ll), { color: "#ffc107", weight: 2, dashArray: "4 4", interactive: false }).addTo(state.group);
        }
    },

    checkLength: function (state, c) {
        let h = state.analysis ? state.analysis.homography : null;

        if (!h) {
            return null;
        }

        let a = parkingGeometry.apply(h, c.u1, c.v1);
        let b = parkingGeometry.apply(h, c.u2, c.v2);

        if (!(a[2] > 0) || !(b[2] > 0)) {
            return null;
        }

        return Math.hypot(a[0] - b[0], a[1] - b[1]);
    },

    problemText: function (state, p) {
        let text = i18n("addresses.parkingProblem_" + p.code);
        let d = state.draft;

        if (typeof p.area !== "undefined" && d && d.areas[p.area]) {
            text = escapeHTML(d.areas[p.area].name || ("#" + (p.area + 1))) + ": " + text;
        }

        return text;
    },

    renderPanel: function (state) {
        let parking = modules.addresses.parking;
        let panel = $(`#${state.prefix}parkingPanel`);
        let d = state.draft;
        let a = state.analysis;
        let h = "";

        let button = (action, icon, title, cls, disabled, index) => `<button type="button" class="btn btn-sm ${cls} mr-1 mb-1" data-parking-action="${action}"${typeof index !== "undefined" ? ` data-index="${index}"` : ""} title="${title}"${disabled ? " disabled" : ""}><i class="fa-fw ${icon}"></i></button>`;
        let textButton = (action, icon, title, cls, disabled, index) => `<button type="button" class="btn btn-sm ${cls} mr-1 mb-1" data-parking-action="${action}"${typeof index !== "undefined" ? ` data-index="${index}"` : ""}${disabled ? " disabled" : ""}><i class="fa-fw ${icon} mr-1"></i>${title}</button>`;
        let input = (field, value, attrs) => `<input type="text" class="form-control form-control-sm" data-parking-field="${field}" value="${escapeHTML(String(value))}" ${attrs || ""}${state.editable ? "" : " disabled"}>`;

        if (state.loading) {
            panel.html(`<div class="text-muted p-2"><i class="fas fa-fw fa-circle-notch fa-spin mr-1"></i>${i18n("addresses.parkingLoading")}</div>`);
            return;
        }

        if (state.loadError) {
            panel.html(`<div class="alert alert-danger">${i18n("addresses.parkingLoadError")}</div>` + textButton("retry", "fas fa-sync-alt", i18n("addresses.parkingRetry"), "btn-info"));
            return;
        }

        if (!state.loaded) {
            panel.html("");
            return;
        }

        if (!state.image) {
            h += `<div class="alert alert-warning p-2 mb-2">${i18n("addresses.parkingSnapshotError")}</div>`;
        }

        if (!d) {
            h += `<div class="alert alert-secondary p-2">${i18n("addresses.parkingNoConfig")}</div>`;
            h += textButton("refresh", "fas fa-sync-alt", i18n("addresses.areaRefresh"), "btn-info");
            if (state.editable) {
                h += textButton("create", "fas fa-plus-circle", i18n("addresses.parkingCreate"), "btn-success", !state.imageSize);
            }
            panel.html(h);
            return;
        }

        // status

        let codes = a.problems.map(p => p.code);
        let status;

        if (a.ready) {
            status = [ "success", "parkingStatusReady" ];
        } else if (!a.valid) {
            status = [ "danger", "parkingStatusInvalid" ];
        } else if (codes.includes("noCalibration")) {
            status = [ "warning", "parkingStatusNotCalibrated" ];
        } else {
            status = [ "warning", "parkingStatusCheck" ];
        }

        let total = a.areas.reduce((s, area) => s + ((area.enabled && area.capacity) ? area.capacity : 0), 0);

        h += `<div class="mb-2"><span class="badge badge-${status[0]} mr-2">${i18n("addresses." + status[1])}</span>${i18n("addresses.parkingTotalCapacity", total)}`;
        if (state.dirty) {
            h += `<span class="badge badge-warning ml-2">${i18n("addresses.parkingUnsavedChanges")}</span>`;
        }
        h += `</div>`;

        if (state.imageSize && (state.imageSize.width != d.frameWidth || state.imageSize.height != d.frameHeight)) {
            let ratio = (state.imageSize.width / state.imageSize.height) / (d.frameWidth / d.frameHeight);
            let aspect = Math.abs(ratio - 1) > 0.01;

            h += `<div class="alert alert-${aspect ? "danger" : "info"} p-2 mb-2">`;
            h += i18n(aspect ? "addresses.parkingFrameAspectChanged" : "addresses.parkingFrameResolutionChanged", `${d.frameWidth}×${d.frameHeight}`, `${state.imageSize.width}×${state.imageSize.height}`);
            if (state.editable) {
                h += `<div class="mt-1">${textButton("acceptFrame", "fas fa-check", i18n("addresses.parkingAcceptFrame"), "btn-light")}</div>`;
            }
            h += `</div>`;
        }

        if (a.problems.length) {
            h += `<ul class="list-unstyled small mb-2">`;
            for (let p of a.problems) {
                let icon = { error: "fas fa-times-circle text-danger", notReady: "fas fa-exclamation-triangle text-warning", warning: "fas fa-info-circle text-info" }[p.severity];
                h += `<li><i class="fa-fw ${icon} mr-1"></i>${parking.problemText(state, p)}</li>`;
            }
            h += `</ul>`;
        }

        // mode banner

        if (state.mode) {
            let left = { area: 4, calibration: 4, check: 2 }[state.mode] - state.pending.length;
            let key = { area: "parkingModeArea", calibration: "parkingModeCalibration", check: "parkingModeCheck" }[state.mode];
            h += `<div class="alert alert-info p-2 mb-2">${i18n("addresses." + key, left)}<div class="mt-1">${textButton("cancelMode", "fas fa-times", i18n("cancel"), "btn-light")}</div></div>`;
        }

        // toolbar

        h += `<div class="mb-2">`;
        if (state.editable) {
            h += textButton("save", "fas fa-save", i18n("addresses.parkingSave"), "btn-primary", !state.dirty || !a.valid || state.saving);
            h += button("revert", "fas fa-undo", i18n("addresses.parkingRevert"), "btn-warning", !state.dirty);
        }
        h += button("refresh", "fas fa-sync-alt", i18n("addresses.areaRefresh"), "btn-info");
        h += button("toggleSpaces", "fas fa-th", i18n("addresses.parkingShowSpaces"), state.showSpaces ? "btn-secondary" : "btn-outline-secondary");
        h += button("toggleGrid", "fas fa-border-all", i18n("addresses.parkingShowGrid"), state.showGrid ? "btn-secondary" : "btn-outline-secondary");
        if (state.deletable && state.saved) {
            h += button("delete", "fas fa-trash-alt", i18n("addresses.parkingDelete"), "btn-danger");
        }
        h += `</div>`;

        h += `<div class="custom-control custom-checkbox mb-2">`;
        h += `<input type="checkbox" class="custom-control-input" id="${state.prefix}parkingEnabled" data-parking-field="configEnabled"${d.enabled ? " checked" : ""}${state.editable ? "" : " disabled"}>`;
        h += `<label class="custom-control-label" for="${state.prefix}parkingEnabled">${i18n("addresses.parkingEnabled")}</label>`;
        h += `</div>`;

        // areas

        h += `<div class="font-weight-bold mt-2 mb-1">${i18n("addresses.parkingAreas")}</div>`;

        if (d.areas.length) {
            h += `<div class="list-group mb-2">`;
            d.areas.forEach((area, i) => {
                let analysis = parking.areaAnalysis(state, i);
                let capacity = (analysis && analysis.capacity !== null) ? analysis.capacity : "—";
                let color = parking.colors[i % parking.colors.length];
                h += `<div class="list-group-item list-group-item-action p-1 pl-2 pointer${state.active === i ? " active" : ""}" data-parking-action="selectArea" data-index="${i}">`;
                h += `<i class="fas fa-fw fa-square mr-1" style="color: ${color};"></i>${escapeHTML(area.name || ("#" + (i + 1)))}`;
                h += `<span class="float-right">${i18n("addresses.parkingCapacity", capacity)}</span>`;
                h += `</div>`;
            });
            h += `</div>`;
        } else {
            h += `<div class="text-muted small mb-2">${i18n("addresses.parkingNoAreas")}</div>`;
        }

        if (state.editable) {
            h += textButton("addArea", "fas fa-draw-polygon", i18n("addresses.parkingAddArea"), "btn-success", !!state.mode);
        }

        let area = state.active !== null ? d.areas[state.active] : null;

        if (area) {
            let i = state.active;
            h += `<div class="card card-body p-2 mt-2 mb-2">`;
            h += `<div class="form-group mb-1"><label class="mb-0 small">${i18n("addresses.parkingAreaName")}</label>${input("name", area.name, `maxlength="64"`)}</div>`;
            h += `<div class="form-row">`;
            h += `<div class="form-group col-4 mb-1"><label class="mb-0 small">${i18n("addresses.parkingSpaceWidth")}</label>${input("spaceWidth", isFinite(area.spaceWidth) ? area.spaceWidth : "")}</div>`;
            h += `<div class="form-group col-4 mb-1"><label class="mb-0 small">${i18n("addresses.parkingSpaceLength")}</label>${input("spaceLength", isFinite(area.spaceLength) ? area.spaceLength : "")}</div>`;
            h += `<div class="form-group col-4 mb-1"><label class="mb-0 small">${i18n("addresses.parkingSpaceAngle")}</label>${input("spaceAngle", isFinite(area.spaceAngle) ? area.spaceAngle : "")}</div>`;
            h += `</div>`;
            h += `<div class="small text-muted mb-1">${i18n("addresses.parkingSpaceHint")}</div>`;
            h += `<div class="custom-control custom-checkbox mb-1">`;
            h += `<input type="checkbox" class="custom-control-input" id="${state.prefix}parkingAreaEnabled" data-parking-field="areaEnabled"${area.enabled ? " checked" : ""}${state.editable ? "" : " disabled"}>`;
            h += `<label class="custom-control-label" for="${state.prefix}parkingAreaEnabled">${i18n("addresses.parkingAreaEnabled")}</label>`;
            h += `</div>`;
            if (state.editable) {
                h += `<div>`;
                h += textButton("rotateBase", "fas fa-redo", i18n("addresses.parkingRotateBase"), "btn-light");
                h += textButton("deleteArea", "fas fa-trash-alt", i18n("addresses.parkingDeleteArea"), "btn-danger", false, i);
                h += `</div>`;
            }
            h += `</div>`;
        }

        // calibration

        h += `<div class="font-weight-bold mt-3 mb-1">${i18n("addresses.parkingCalibration")}</div>`;
        h += `<div class="small text-muted mb-1">${i18n("addresses.parkingCalibrationHint")}</div>`;

        let custom = d.calibration && !parking.calibrationSize(state);

        if (custom) {
            h += `<div class="small mb-1">${i18n("addresses.parkingCalibrationCustom", d.calibration.points.length)}</div>`;
        } else {
            h += `<div class="form-row">`;
            h += `<div class="form-group col-6 mb-1"><label class="mb-0 small">${i18n("addresses.parkingCalibrationA")}</label>${input("calA", state.calA)}</div>`;
            h += `<div class="form-group col-6 mb-1"><label class="mb-0 small">${i18n("addresses.parkingCalibrationB")}</label>${input("calB", state.calB)}</div>`;
            h += `</div>`;
        }

        if (state.editable) {
            h += `<div>`;
            h += textButton("calibrate", "fas fa-crosshairs", i18n("addresses.parkingCalibrate"), "btn-info", !!state.mode);
            h += textButton("calibrationFromArea", "fas fa-clone", i18n("addresses.parkingCalibrationFromArea"), "btn-light", !area || !!state.mode);
            if (d.calibration) {
                h += button("removeCalibration", "fas fa-eraser", i18n("addresses.parkingRemoveCalibration"), "btn-danger");
            }
            h += `</div>`;
        }

        if (!d.calibration) {
            h += `<div class="text-muted small mb-1">${i18n("addresses.parkingNotCalibrated")}</div>`;
        } else {
            h += `<div class="font-weight-bold mt-2 mb-1 small">${i18n("addresses.parkingChecks")}</div>`;
            h += `<div class="small text-muted mb-1">${i18n("addresses.parkingChecksHint")}</div>`;

            d.calibration.checks.forEach((c, i) => {
                let measured = parking.checkLength(state, c);
                let err = (measured !== null && c.length > 0) ? Math.abs(measured - c.length) / c.length : null;
                let cls = err === null ? "" : (err > parkingGeometry.CHECK_FAILURE ? "text-danger" : (err > parkingGeometry.CHECK_WARNING ? "text-warning" : "text-success"));
                h += `<div class="form-row align-items-center mb-1">`;
                h += `<div class="col-4">${input("checkValue", isFinite(c.length) ? c.length : "", `data-index="${i}"`)}</div>`;
                h += `<div class="col-6 small ${cls}">${measured !== null ? i18n("addresses.parkingCheckMeasured", measured.toFixed(2), err !== null ? (err * 100).toFixed(1) : "—") : "—"}</div>`;
                h += `<div class="col-2">${state.editable ? button("deleteCheck", "fas fa-times", i18n("addresses.parkingDeleteCheck"), "btn-light", false, i) : ""}</div>`;
                h += `</div>`;
            });

            if (state.editable) {
                h += `<div class="form-row align-items-center">`;
                h += `<div class="col-4">${input("checkLength", state.checkLength)}</div>`;
                h += `<div class="col-8">${textButton("addCheck", "fas fa-ruler", i18n("addresses.parkingAddCheck"), "btn-light", !!state.mode)}</div>`;
                h += `</div>`;
            }
        }

        panel.html(h);
    },
}).init();
