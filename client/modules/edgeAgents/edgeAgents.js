({
    menuItem: false,
    summary: null,
    agent: null,
    invitation: null,
    poolDraft: null,

    init: function () {
        if (!document.getElementById("edge-agents-css")) {
            $("head").append(`<link id="edge-agents-css" rel="stylesheet" href="modules/edgeAgents/edgeAgents.css?ver=${version}">`);
        }
        if (AVAIL("edgeAgents", "controller")) {
            this.menuItem = leftSide("fas fa-fw fa-network-wired", i18n("edgeAgents.title"), "?#edgeAgents", "edgeAgents");
        }
        moduleLoaded("edgeAgents", this);
    },

    route: function () {
        document.title = i18n("windowTitle") + " :: " + i18n("edgeAgents.title");
        $("#altForm").hide();
        subTop();
        if (modules.edgeAgents.menuItem) {
            $("#" + modules.edgeAgents.menuItem).children().first().attr("href", refreshUrl());
        }
        $("#mainForm").html(modules.edgeAgents.pageSkeleton());
        modules.edgeAgents.refresh();
    },

    pageSkeleton: function () {
        return `
            <div class="edge-agents-page">
                <div class="ea-page-header">
                    <div>
                        <h1>${escapeHTML(i18n("edgeAgents.title"))}</h1>
                        <div class="text-muted">${escapeHTML(i18n("edgeAgents.subtitle"))}</div>
                    </div>
                    <div class="ea-actions">
                        <button type="button" class="btn btn-outline-secondary" onclick="modules.edgeAgents.toggleInvitation();">
                            <i class="fas fa-link mr-2"></i>${escapeHTML(i18n("edgeAgents.createInvitation"))}
                        </button>
                        <button type="button" class="btn btn-outline-secondary" onclick="modules.edgeAgents.togglePoolEditor();">
                            <i class="fas fa-plus mr-2"></i>${escapeHTML(i18n("edgeAgents.addPool"))}
                        </button>
                        <button type="button" class="btn btn-outline-secondary" onclick="modules.edgeAgents.refresh();">
                            <i class="fas fa-sync-alt mr-2"></i>${escapeHTML(i18n("edgeAgents.refresh"))}
                        </button>
                    </div>
                </div>
                <div id="eaInvitation" class="ea-editor" hidden></div>
                <div id="eaPoolEditor" class="ea-editor" hidden></div>
                <section class="ea-section">
                    <div class="ea-section-heading">
                        <h2>${escapeHTML(i18n("edgeAgents.agents"))}</h2>
                        <span id="eaControllerIdentity" class="text-muted"></span>
                    </div>
                    <div id="eaAgents"></div>
                </section>
                <section id="eaAgentSection" class="ea-section" hidden>
                    <div id="eaAgent"></div>
                </section>
                <section class="ea-section">
                    <div class="ea-section-heading">
                        <h2>${escapeHTML(i18n("edgeAgents.pools"))}</h2>
                    </div>
                    <div id="eaPools"></div>
                </section>
                <section class="ea-section">
                    <div class="ea-section-heading">
                        <h2>${escapeHTML(i18n("edgeAgents.events"))}</h2>
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="modules.edgeAgents.loadLogs();">
                            <i class="fas fa-sync-alt mr-1"></i>${escapeHTML(i18n("edgeAgents.refresh"))}
                        </button>
                    </div>
                    <div id="eaLogs" class="ea-log text-muted">${escapeHTML(i18n("edgeAgents.loading"))}</div>
                </section>
            </div>`;
    },

    refresh: function () {
        loadingStart();
        GET("edgeAgents", "controller", false, true).
        done(result => {
            modules.edgeAgents.summary = result;
            modules.edgeAgents.renderSummary();
            if (modules.edgeAgents.agent && modules.edgeAgents.agent.agent_id) {
                modules.edgeAgents.openAgent(modules.edgeAgents.agent.agent_id, false);
            }
            modules.edgeAgents.loadLogs();
        }).
        fail(FAIL).
        always(loadingDone);
    },

    renderSummary: function () {
        const summary = modules.edgeAgents.summary || {};
        const controller = summary.controller || {};
        $("#eaControllerIdentity").text(`${controller.controllerId || "-"} · ${modules.edgeAgents.shortKey(controller.keyId || controller.publicKey)}`);
        modules.edgeAgents.renderAgents(summary.agents || []);
        modules.edgeAgents.renderPools(summary.pools || []);
    },

    renderAgents: function (agents) {
        if (!agents.length) {
            $("#eaAgents").html(`<div class="ea-empty">${escapeHTML(i18n("edgeAgents.noAgents"))}</div>`);
            return;
        }
        let rows = "";
        agents.forEach(agent => {
            const status = modules.edgeAgents.statusBadge(agent.pairing_status);
            const managed = agent.managed_authorized
                ? modules.edgeAgents.badge(i18n("edgeAgents.managed"), "success")
                : modules.edgeAgents.badge(i18n("edgeAgents.baseMode"), "secondary");
            const generation = `${Number(agent.applied_generation || 0)} / ${Number(agent.desired_generation || 0)}`;
            const selected = modules.edgeAgents.agent && modules.edgeAgents.agent.agent_id === agent.agent_id ? " ea-selected" : "";
            rows += `
                <tr class="ea-clickable${selected}" onclick='modules.edgeAgents.openAgent(${JSON.stringify(String(agent.agent_id))});'>
                    <td><strong>${escapeHTML(agent.display_name || agent.agent_id)}</strong><div class="ea-mono text-muted">${escapeHTML(agent.agent_id)}</div></td>
                    <td>${status} ${managed}</td>
                    <td>${escapeHTML(modules.edgeAgents.formatTime(agent.last_seen_at))}</td>
                    <td>${escapeHTML(generation)}</td>
                    <td>${escapeHTML(agent.pool_title || "-")}<div class="text-muted">${escapeHTML(agent.overlay_prefix || "")}</div></td>
                    <td>${escapeHTML(String(agent.mapping_count || 0))}</td>
                </tr>`;
        });
        $("#eaAgents").html(`
            <div class="table-responsive">
                <table class="table table-hover ea-table">
                    <thead><tr>
                        <th>${escapeHTML(i18n("edgeAgents.agent"))}</th>
                        <th>${escapeHTML(i18n("edgeAgents.status"))}</th>
                        <th>${escapeHTML(i18n("edgeAgents.lastSeen"))}</th>
                        <th>${escapeHTML(i18n("edgeAgents.generation"))}</th>
                        <th>${escapeHTML(i18n("edgeAgents.pool"))}</th>
                        <th>${escapeHTML(i18n("edgeAgents.mappings"))}</th>
                    </tr></thead><tbody>${rows}</tbody>
                </table>
            </div>`);
    },

    renderPools: function (pools) {
        if (!pools.length) {
            $("#eaPools").html(`<div class="ea-empty">${escapeHTML(i18n("edgeAgents.noPools"))}</div>`);
            return;
        }
        let rows = "";
        pools.forEach(pool => {
            rows += `
                <tr>
                    <td><strong>${escapeHTML(pool.title || pool.pool_id)}</strong><div class="ea-mono text-muted">${escapeHTML(pool.pool_id)}</div></td>
                    <td>${modules.edgeAgents.badge(pool.overlay_type || "wireguard", pool.enabled ? "success" : "secondary")}</td>
                    <td>${escapeHTML(pool.gateway_interface || "-")}<div class="text-muted">${escapeHTML(pool.gateway_endpoint || "")}</div></td>
                    <td>${escapeHTML(pool.tunnel_pool || "-")}</td>
                    <td>${escapeHTML(pool.overlay_pool || "-")} /${escapeHTML(String(pool.agent_prefix_length || "-"))}</td>
                    <td>${escapeHTML(String(pool.lease_count || 0))}</td>
                    <td class="text-right"><button type="button" class="btn btn-sm btn-outline-secondary" onclick='modules.edgeAgents.editPool(${JSON.stringify(String(pool.pool_id))});'>${escapeHTML(i18n("edgeAgents.edit"))}</button></td>
                </tr>`;
        });
        $("#eaPools").html(`
            <div class="table-responsive"><table class="table ea-table">
                <thead><tr>
                    <th>${escapeHTML(i18n("edgeAgents.pool"))}</th><th>${escapeHTML(i18n("edgeAgents.type"))}</th>
                    <th>${escapeHTML(i18n("edgeAgents.gateway"))}</th><th>${escapeHTML(i18n("edgeAgents.tunnelPool"))}</th>
                    <th>${escapeHTML(i18n("edgeAgents.overlayPool"))}</th><th>${escapeHTML(i18n("edgeAgents.leases"))}</th><th></th>
                </tr></thead><tbody>${rows}</tbody>
            </table></div>`);
    },

    openAgent: function (agentId, scroll) {
        GET("edgeAgents", "controller", agentId, true).
        done(agent => {
            modules.edgeAgents.agent = agent;
            modules.edgeAgents.renderAgent(agent);
            modules.edgeAgents.renderAgents((modules.edgeAgents.summary || {}).agents || []);
            $("#eaAgentSection").prop("hidden", false);
            if (scroll !== false) {
                document.getElementById("eaAgentSection").scrollIntoView({ behavior: "smooth", block: "start" });
            }
        }).
        fail(FAIL);
    },

    renderAgent: function (agent) {
        const scopes = Array.isArray(agent.managed_scopes) ? agent.managed_scopes : [];
        const availableScopes = ["overlay.configure", "mapping.configure", "network.diagnose", "lan.scan"];
        const scopeBadges = scopes.length
            ? scopes.map(scope => modules.edgeAgents.badge(scope, "success")).join(" ")
            : `<span class="text-muted">${escapeHTML(i18n("edgeAgents.noManagedAccess"))}</span>`;
        const pools = ((modules.edgeAgents.summary || {}).pools || []).filter(pool => pool.enabled);
        let poolOptions = `<option value="">${escapeHTML(i18n("edgeAgents.selectPool"))}</option>`;
        pools.forEach(pool => {
            const selected = pool.pool_id === agent.pool_id ? " selected" : "";
            poolOptions += `<option value="${escapeHTML(pool.pool_id)}"${selected}>${escapeHTML(pool.title || pool.pool_id)}</option>`;
        });
        const mappings = modules.edgeAgents.mappingRows(agent.mappings || []);
        const actions = modules.edgeAgents.actionRows(agent.actions || []);
        const conditions = agent.condition_summary && Object.keys(agent.condition_summary).length
            ? `<pre class="ea-json">${escapeHTML(JSON.stringify(agent.condition_summary, null, 2))}</pre>`
            : `<span class="text-muted">${escapeHTML(i18n("edgeAgents.noConditions"))}</span>`;
        $("#eaAgent").html(`
            <div class="ea-section-heading">
                <div>
                    <h2>${escapeHTML(agent.display_name || agent.agent_id)}</h2>
                    <div class="ea-mono text-muted">${escapeHTML(agent.agent_id || "")}</div>
                </div>
                <div>${modules.edgeAgents.statusBadge(agent.pairing_status)}</div>
            </div>
            <div class="ea-facts">
                ${modules.edgeAgents.fact(i18n("edgeAgents.lastSeen"), modules.edgeAgents.formatTime(agent.last_seen_at))}
                ${modules.edgeAgents.fact(i18n("edgeAgents.agentVersion"), agent.agent_version || "-")}
                ${modules.edgeAgents.fact(i18n("edgeAgents.generation"), `${agent.applied_generation || 0} / ${agent.desired_generation || 0}`)}
                ${modules.edgeAgents.fact(i18n("edgeAgents.overlayKey"), modules.edgeAgents.shortKey(agent.overlay_public_key))}
                ${modules.edgeAgents.fact(i18n("edgeAgents.tunnelAddress"), agent.tunnel_ip || "-")}
                ${modules.edgeAgents.fact(i18n("edgeAgents.overlayPrefix"), agent.overlay_prefix || "-")}
            </div>
            <div class="ea-subsection">
                <div class="ea-subsection-title">${escapeHTML(i18n("edgeAgents.managedAuthorization"))}</div>
                <div class="mb-2">${scopeBadges}</div>
                <div class="form-row align-items-end">
                    <div class="form-group col-lg-7">
                        <label for="eaManagedSecret">${escapeHTML(i18n("edgeAgents.managedSecret"))}</label>
                        <input id="eaManagedSecret" type="password" class="form-control" autocomplete="new-password">
                    </div>
                    <div class="form-group col-lg-2">
                        <label for="eaManagedTTL">TTL, sec</label>
                        <input id="eaManagedTTL" type="number" min="60" max="1800" value="600" class="form-control">
                    </div>
                    <div class="form-group col-lg-3">
                        <button type="button" class="btn btn-primary btn-block" onclick="modules.edgeAgents.authorizeManaged();">${escapeHTML(i18n("edgeAgents.authorize"))}</button>
                    </div>
                </div>
                <div class="text-muted small">${escapeHTML(i18n("edgeAgents.managedHint"))}</div>
            </div>
            <div class="ea-subsection">
                <div class="ea-subsection-title">${escapeHTML(i18n("edgeAgents.overlay"))}</div>
                <div class="form-row align-items-end">
                    <div class="form-group col-lg-7">
                        <label for="eaAgentPool">${escapeHTML(i18n("edgeAgents.pool"))}</label>
                        <select id="eaAgentPool" class="form-control">${poolOptions}</select>
                    </div>
                    <div class="form-group col-lg-5 ea-actions">
                        <button type="button" class="btn btn-primary" onclick="modules.edgeAgents.assignPool();">${escapeHTML(i18n("edgeAgents.assignPool"))}</button>
                        <button type="button" class="btn btn-outline-danger" ${agent.pool_id ? "" : "disabled"} onclick="modules.edgeAgents.releasePool();">${escapeHTML(i18n("edgeAgents.releasePool"))}</button>
                    </div>
                </div>
                <div class="ea-facts ea-facts-compact">
                    ${modules.edgeAgents.fact(i18n("edgeAgents.gatewayState"), agent.gateway_state || "-")}
                    ${modules.edgeAgents.fact(i18n("edgeAgents.gatewayError"), agent.gateway_error || "-")}
                    ${modules.edgeAgents.fact(i18n("edgeAgents.agentState"), (agent.actual_state && agent.actual_state.status) || "-")}
                </div>
            </div>
            <div class="ea-subsection">
                <div class="ea-subsection-title">${escapeHTML(i18n("edgeAgents.mappings"))}</div>
                <div class="text-muted mb-3">${escapeHTML(i18n("edgeAgents.mappingHint"))}</div>
                <div class="form-row align-items-end">
                    <div class="form-group col-md-3"><label for="eaLocalIp">${escapeHTML(i18n("edgeAgents.localIp"))}</label><input id="eaLocalIp" class="form-control" placeholder="192.168.1.20"></div>
                    <div class="form-group col-md-3"><label for="eaOverlayIp">${escapeHTML(i18n("edgeAgents.overlayIp"))}</label><input id="eaOverlayIp" class="form-control" placeholder="${escapeHTML(i18n("edgeAgents.auto"))}"></div>
                    <div class="form-group col-md-4"><label for="eaMappingComment">${escapeHTML(i18n("edgeAgents.comment"))}</label><input id="eaMappingComment" class="form-control"></div>
                    <div class="form-group col-md-2"><button type="button" class="btn btn-primary btn-block" onclick="modules.edgeAgents.saveMapping();">${escapeHTML(i18n("edgeAgents.add"))}</button></div>
                </div>
                ${mappings}
            </div>
            <div class="ea-subsection">
                <div class="ea-subsection-title">${escapeHTML(i18n("edgeAgents.actions"))}</div>
                <div class="ea-actions mb-3">
                    <button type="button" class="btn btn-outline-secondary" onclick="modules.edgeAgents.queueAction('diagnostics');"><i class="fas fa-stethoscope mr-1"></i>${escapeHTML(i18n("edgeAgents.diagnostics"))}</button>
                    <button type="button" class="btn btn-outline-secondary" onclick="modules.edgeAgents.queueAction('inventory_refresh');"><i class="fas fa-list mr-1"></i>${escapeHTML(i18n("edgeAgents.inventory"))}</button>
                    <button type="button" class="btn btn-outline-secondary" onclick="modules.edgeAgents.queueAction('lan_scan');"><i class="fas fa-search mr-1"></i>${escapeHTML(i18n("edgeAgents.lanScan"))}</button>
                    <button type="button" class="btn btn-outline-secondary" onclick="modules.edgeAgents.queueAction('awg_key_rotation');"><i class="fas fa-key mr-1"></i>${escapeHTML(i18n("edgeAgents.rotateKey"))}</button>
                </div>
                ${actions}
            </div>
            <div class="ea-subsection">
                <div class="ea-subsection-title">${escapeHTML(i18n("edgeAgents.conditions"))}</div>
                ${conditions}
            </div>
            <div class="ea-danger-zone">
                <button type="button" class="btn btn-outline-danger" onclick="modules.edgeAgents.revokeAgent();"><i class="fas fa-unlink mr-1"></i>${escapeHTML(i18n("edgeAgents.revokePairing"))}</button>
            </div>`);
    },

    mappingRows: function (mappings) {
        if (!mappings.length) {
            return `<div class="ea-empty">${escapeHTML(i18n("edgeAgents.noMappings"))}</div>`;
        }
        let rows = "";
        mappings.forEach(mapping => {
            rows += `<tr>
                <td class="ea-mono">${escapeHTML(mapping.local_ip || "-")}</td>
                <td class="ea-mono">${escapeHTML(mapping.overlay_ip || "-")}</td>
                <td>${modules.edgeAgents.statusBadge(mapping.current_state || (mapping.enabled ? "pending" : "disabled"))}</td>
                <td>${escapeHTML(mapping.comment || "")}</td>
                <td class="text-right"><button type="button" class="btn btn-sm btn-outline-danger" onclick='modules.edgeAgents.deleteMapping(${JSON.stringify(String(mapping.mapping_id))});'><i class="fas fa-trash"></i></button></td>
            </tr>`;
        });
        return `<div class="table-responsive"><table class="table ea-table"><thead><tr>
            <th>${escapeHTML(i18n("edgeAgents.localIp"))}</th><th>${escapeHTML(i18n("edgeAgents.overlayIp"))}</th>
            <th>${escapeHTML(i18n("edgeAgents.status"))}</th><th>${escapeHTML(i18n("edgeAgents.comment"))}</th><th></th>
        </tr></thead><tbody>${rows}</tbody></table></div>`;
    },

    actionRows: function (actions) {
        if (!actions.length) {
            return `<div class="ea-empty">${escapeHTML(i18n("edgeAgents.noActions"))}</div>`;
        }
        let rows = "";
        actions.forEach(action => {
            const details = action.error || (action.result && Object.keys(action.result).length ? JSON.stringify(action.result) : "");
            rows += `<tr>
                <td>${escapeHTML(action.action_type || "-")}</td>
                <td>${modules.edgeAgents.statusBadge(action.state || "-")}</td>
                <td>${escapeHTML(modules.edgeAgents.formatTime(action.created_at))}</td>
                <td><span class="ea-wrap">${escapeHTML(details)}</span></td>
            </tr>`;
        });
        return `<div class="table-responsive"><table class="table ea-table"><thead><tr>
            <th>${escapeHTML(i18n("edgeAgents.action"))}</th><th>${escapeHTML(i18n("edgeAgents.status"))}</th>
            <th>${escapeHTML(i18n("edgeAgents.time"))}</th><th>${escapeHTML(i18n("edgeAgents.result"))}</th>
        </tr></thead><tbody>${rows}</tbody></table></div>`;
    },

    toggleInvitation: function () {
        const target = $("#eaInvitation");
        if (!target.prop("hidden")) {
            target.prop("hidden", true);
            return;
        }
        target.html(`
            <div class="ea-section-heading"><h2>${escapeHTML(i18n("edgeAgents.createInvitation"))}</h2></div>
            <div class="form-row align-items-end">
                <div class="form-group col-md-6"><label for="eaInvitationName">${escapeHTML(i18n("edgeAgents.displayName"))}</label><input id="eaInvitationName" class="form-control" value="RBT"></div>
                <div class="form-group col-md-3"><label for="eaInvitationTTL">TTL, sec</label><input id="eaInvitationTTL" type="number" min="60" max="3600" value="600" class="form-control"></div>
                <div class="form-group col-md-3"><button type="button" class="btn btn-primary btn-block" onclick="modules.edgeAgents.createInvitation();">${escapeHTML(i18n("edgeAgents.create"))}</button></div>
            </div>
            <div id="eaInvitationResult"></div>`).prop("hidden", false);
    },

    createInvitation: function () {
        modules.edgeAgents.post({
            operation: "createInvitation",
            displayName: $("#eaInvitationName").val(),
            ttl: Number($("#eaInvitationTTL").val()),
        }, invitation => {
            modules.edgeAgents.invitation = invitation;
            const value = JSON.stringify(invitation);
            $("#eaInvitationResult").html(`
                <label>${escapeHTML(i18n("edgeAgents.invitationJson"))}</label>
                <div class="input-group">
                    <textarea id="eaInvitationJson" class="form-control ea-code-input" readonly>${escapeHTML(value)}</textarea>
                    <div class="input-group-append"><button type="button" class="btn btn-outline-secondary" onclick="modules.edgeAgents.copyInvitation();"><i class="fas fa-copy mr-1"></i>${escapeHTML(i18n("edgeAgents.copy"))}</button></div>
                </div>
                <div class="text-muted small mt-2">${escapeHTML(i18n("edgeAgents.expires"))}: ${escapeHTML(modules.edgeAgents.formatTime(invitation.expiresAt))}</div>`);
        });
    },

    copyInvitation: function () {
        const value = $("#eaInvitationJson").val() || "";
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(value).then(() => toastr.success(i18n("edgeAgents.copied")));
        } else {
            const input = document.getElementById("eaInvitationJson");
            input.select();
            document.execCommand("copy");
            toastr.success(i18n("edgeAgents.copied"));
        }
    },

    togglePoolEditor: function () {
        const target = $("#eaPoolEditor");
        if (!target.prop("hidden")) {
            target.prop("hidden", true);
            return;
        }
        modules.edgeAgents.poolDraft = null;
        modules.edgeAgents.renderPoolEditor({});
    },

    editPool: function (poolId) {
        const pools = (modules.edgeAgents.summary || {}).pools || [];
        const pool = pools.find(item => item.pool_id === poolId);
        if (!pool) {
            return;
        }
        modules.edgeAgents.poolDraft = pool;
        modules.edgeAgents.renderPoolEditor(pool);
        document.getElementById("eaPoolEditor").scrollIntoView({ behavior: "smooth", block: "start" });
    },

    renderPoolEditor: function (pool) {
        const sources = Array.isArray(pool.allowed_source_prefixes) ? pool.allowed_source_prefixes.join("\n") : "";
        const parameters = pool.parameters && Object.keys(pool.parameters).length ? JSON.stringify(pool.parameters, null, 2) : "{}";
        $("#eaPoolEditor").html(`
            <div class="ea-section-heading"><h2>${escapeHTML(pool.pool_id ? i18n("edgeAgents.editPool") : i18n("edgeAgents.addPool"))}</h2></div>
            <div class="form-row">
                <div class="form-group col-md-4"><label for="eaPoolId">ID</label><input id="eaPoolId" class="form-control" value="${escapeHTML(pool.pool_id || "")}" ${pool.pool_id ? "readonly" : ""}></div>
                <div class="form-group col-md-4"><label for="eaPoolTitle">${escapeHTML(i18n("edgeAgents.name"))}</label><input id="eaPoolTitle" class="form-control" value="${escapeHTML(pool.title || "")}"></div>
                <div class="form-group col-md-4"><label for="eaPoolType">${escapeHTML(i18n("edgeAgents.type"))}</label><select id="eaPoolType" class="form-control"><option value="wireguard" ${pool.overlay_type === "wireguard" ? "selected" : ""}>WireGuard</option><option value="amneziawg" ${pool.overlay_type === "amneziawg" ? "selected" : ""}>AmneziaWG</option></select></div>
                <div class="form-group col-md-4"><label for="eaGatewayEndpoint">${escapeHTML(i18n("edgeAgents.gatewayEndpoint"))}</label><input id="eaGatewayEndpoint" class="form-control" placeholder="rbt.example.com:51820" value="${escapeHTML(pool.gateway_endpoint || "")}"></div>
                <div class="form-group col-md-4"><label for="eaGatewayInterface">${escapeHTML(i18n("edgeAgents.gatewayInterface"))}</label><input id="eaGatewayInterface" class="form-control" value="${escapeHTML(pool.gateway_interface || "wg-rbt0")}"></div>
                <div class="form-group col-md-4"><label for="eaGatewayTunnelAddress">${escapeHTML(i18n("edgeAgents.gatewayTunnelAddress"))}</label><input id="eaGatewayTunnelAddress" class="form-control" placeholder="10.254.0.1" value="${escapeHTML(pool.gateway_tunnel_address || "")}"></div>
                <div class="form-group col-md-6"><label for="eaGatewayPublicKey">${escapeHTML(i18n("edgeAgents.gatewayPublicKey"))}</label><div class="input-group"><input id="eaGatewayPublicKey" class="form-control ea-mono" value="${escapeHTML(pool.gateway_public_key || "")}"><div class="input-group-append"><button type="button" class="btn btn-outline-secondary" onclick="modules.edgeAgents.loadGatewayPublicKey();"><i class="fas fa-key"></i></button></div></div></div>
                <div class="form-group col-md-3"><label for="eaTunnelPool">${escapeHTML(i18n("edgeAgents.tunnelPool"))}</label><input id="eaTunnelPool" class="form-control" placeholder="10.254.0.0/24" value="${escapeHTML(pool.tunnel_pool || "")}"></div>
                <div class="form-group col-md-3"><label for="eaOverlayPool">${escapeHTML(i18n("edgeAgents.overlayPool"))}</label><input id="eaOverlayPool" class="form-control" placeholder="10.220.0.0/16" value="${escapeHTML(pool.overlay_pool || "")}"></div>
                <div class="form-group col-md-3"><label for="eaAgentPrefix">${escapeHTML(i18n("edgeAgents.agentPrefix"))}</label><input id="eaAgentPrefix" type="number" min="8" max="30" class="form-control" value="${escapeHTML(String(pool.agent_prefix_length || 24))}"></div>
                <div class="form-group col-md-3"><label for="eaKeepalive">Keepalive, sec</label><input id="eaKeepalive" type="number" min="0" max="3600" class="form-control" value="${escapeHTML(String(pool.persistent_keepalive_sec || 25))}"></div>
                <div class="form-group col-md-6"><label for="eaAllowedSources">${escapeHTML(i18n("edgeAgents.allowedSources"))}</label><textarea id="eaAllowedSources" class="form-control ea-code-input" placeholder="10.0.0.0/8">${escapeHTML(sources)}</textarea></div>
                <div class="form-group col-md-6"><label for="eaAwgParameters">${escapeHTML(i18n("edgeAgents.awgParameters"))}</label><textarea id="eaAwgParameters" class="form-control ea-code-input">${escapeHTML(parameters)}</textarea></div>
            </div>
            <div class="custom-control custom-checkbox mb-3"><input id="eaPoolEnabled" type="checkbox" class="custom-control-input" ${pool.enabled === false ? "" : "checked"}><label class="custom-control-label" for="eaPoolEnabled">${escapeHTML(i18n("edgeAgents.enabled"))}</label></div>
            <div class="ea-actions"><button type="button" class="btn btn-primary" onclick="modules.edgeAgents.savePool();">${escapeHTML(i18n("edgeAgents.save"))}</button><button type="button" class="btn btn-outline-secondary" onclick="$('#eaPoolEditor').prop('hidden', true);">${escapeHTML(i18n("edgeAgents.cancel"))}</button></div>`).prop("hidden", false);
    },

    loadGatewayPublicKey: function () {
        modules.edgeAgents.post({
            operation: "gatewayPublicKey",
            interface: $("#eaGatewayInterface").val(),
        }, result => $("#eaGatewayPublicKey").val(result.publicKey || ""));
    },

    savePool: function () {
        let parameters;
        try {
            parameters = JSON.parse($("#eaAwgParameters").val() || "{}");
        } catch (error) {
            toastr.error(i18n("edgeAgents.invalidJson"));
            return;
        }
        const pool = {
            poolId: $("#eaPoolId").val(),
            title: $("#eaPoolTitle").val(),
            overlayType: $("#eaPoolType").val(),
            gatewayEndpoint: $("#eaGatewayEndpoint").val(),
            gatewayPublicKey: $("#eaGatewayPublicKey").val(),
            gatewayTunnelAddress: $("#eaGatewayTunnelAddress").val(),
            gatewayInterface: $("#eaGatewayInterface").val(),
            tunnelPool: $("#eaTunnelPool").val(),
            overlayPool: $("#eaOverlayPool").val(),
            agentPrefixLength: Number($("#eaAgentPrefix").val()),
            persistentKeepaliveSec: Number($("#eaKeepalive").val()),
            allowedSourcePrefixes: String($("#eaAllowedSources").val() || "").split(/[\n,]+/).map(value => value.trim()).filter(Boolean),
            parameters: parameters,
            enabled: $("#eaPoolEnabled").prop("checked"),
        };
        modules.edgeAgents.post({ operation: "savePool", pool: pool }, () => {
            $("#eaPoolEditor").prop("hidden", true);
            modules.edgeAgents.refresh();
        });
    },

    authorizeManaged: function () {
        const secret = String($("#eaManagedSecret").val() || "");
        if (!secret) {
            toastr.error(i18n("edgeAgents.secretRequired"));
            return;
        }
        modules.edgeAgents.post({
            operation: "authorizeManaged",
            agentId: modules.edgeAgents.agent.agent_id,
            secret: secret,
            ttl: Number($("#eaManagedTTL").val()),
        }, () => {
            $("#eaManagedSecret").val("");
            modules.edgeAgents.openAgent(modules.edgeAgents.agent.agent_id, false);
        });
    },

    assignPool: function () {
        const poolId = $("#eaAgentPool").val();
        if (!poolId) {
            toastr.error(i18n("edgeAgents.selectPool"));
            return;
        }
        modules.edgeAgents.post({ operation: "assignPool", agentId: modules.edgeAgents.agent.agent_id, poolId: poolId, enabled: true }, () => modules.edgeAgents.refresh());
    },

    releasePool: function () {
        if (!window.confirm(i18n("edgeAgents.confirmReleasePool"))) {
            return;
        }
        modules.edgeAgents.remove({ operation: "releasePool", agentId: modules.edgeAgents.agent.agent_id }, () => modules.edgeAgents.refresh());
    },

    saveMapping: function () {
        modules.edgeAgents.post({
            operation: "saveMapping",
            agentId: modules.edgeAgents.agent.agent_id,
            mapping: {
                localIp: $("#eaLocalIp").val(),
                overlayIp: $("#eaOverlayIp").val(),
                comment: $("#eaMappingComment").val(),
                enabled: true,
            },
        }, agent => {
            modules.edgeAgents.agent = agent;
            modules.edgeAgents.renderAgent(agent);
        });
    },

    deleteMapping: function (mappingId) {
        if (!window.confirm(i18n("edgeAgents.confirmDeleteMapping"))) {
            return;
        }
        modules.edgeAgents.remove({ operation: "deleteMapping", agentId: modules.edgeAgents.agent.agent_id, mappingId: mappingId }, agent => {
            modules.edgeAgents.agent = agent;
            modules.edgeAgents.renderAgent(agent);
        });
    },

    queueAction: function (actionType) {
        modules.edgeAgents.post({ operation: "queueAction", agentId: modules.edgeAgents.agent.agent_id, actionType: actionType }, () => {
            toastr.success(i18n("edgeAgents.actionQueued"));
            modules.edgeAgents.openAgent(modules.edgeAgents.agent.agent_id, false);
        });
    },

    revokeAgent: function () {
        if (!window.confirm(i18n("edgeAgents.confirmRevoke"))) {
            return;
        }
        modules.edgeAgents.remove({ operation: "revokeAgent", agentId: modules.edgeAgents.agent.agent_id }, () => {
            modules.edgeAgents.agent = null;
            $("#eaAgentSection").prop("hidden", true);
            modules.edgeAgents.refresh();
        });
    },

    loadLogs: function () {
        GET("edgeAgents", "controller", "logs", true).
        done(result => {
            const events = result.events || [];
            if (!events.length) {
                $("#eaLogs").html(`<div class="ea-empty">${escapeHTML(i18n("edgeAgents.noEvents"))}</div>`);
                return;
            }
            let rows = "";
            events.slice().reverse().forEach(event => {
                const context = Object.assign({}, event);
                delete context.timestamp;
                delete context.event;
                rows += `<tr><td>${escapeHTML(modules.edgeAgents.formatTime(event.timestamp))}</td><td>${escapeHTML(event.event || "-")}</td><td><span class="ea-wrap">${escapeHTML(JSON.stringify(context))}</span></td></tr>`;
            });
            $("#eaLogs").html(`<div class="table-responsive"><table class="table ea-table"><thead><tr><th>${escapeHTML(i18n("edgeAgents.time"))}</th><th>${escapeHTML(i18n("edgeAgents.event"))}</th><th>${escapeHTML(i18n("edgeAgents.details"))}</th></tr></thead><tbody>${rows}</tbody></table></div>`);
        }).
        fail(FAIL);
    },

    post: function (data, done) {
        loadingStart();
        POST("edgeAgents", "controller", false, data).
        done(done).
        fail(FAIL).
        always(loadingDone);
    },

    remove: function (data, done) {
        loadingStart();
        DELETE("edgeAgents", "controller", false, data).
        done(done).
        fail(FAIL).
        always(loadingDone);
    },

    fact: function (title, value) {
        return `<div class="ea-fact"><span>${escapeHTML(title)}</span><strong>${escapeHTML(String(value || "-"))}</strong></div>`;
    },

    badge: function (text, kind) {
        return `<span class="badge badge-${kind || "secondary"}">${escapeHTML(String(text || "-"))}</span>`;
    },

    statusBadge: function (status) {
        const value = String(status || "unknown");
        const good = ["active", "applied", "completed", "ok"];
        const warning = ["pending", "queued", "running", "revoking", "disabled"];
        const kind = good.includes(value) ? "success" : (warning.includes(value) ? "warning" : (value === "revoked" || value === "failed" || value === "error" ? "danger" : "secondary"));
        return modules.edgeAgents.badge(value, kind);
    },

    shortKey: function (value) {
        value = String(value || "");
        return value.length > 20 ? value.slice(0, 10) + "…" + value.slice(-8) : (value || "-");
    },

    formatTime: function (value) {
        if (!value) {
            return "-";
        }
        let normalized = String(value);
        if (/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}/.test(normalized)) {
            normalized = normalized.replace(" ", "T");
        }
        const date = new Date(normalized);
        return Number.isNaN(date.getTime()) ? String(value) : date.toLocaleString();
    },
}).init();
