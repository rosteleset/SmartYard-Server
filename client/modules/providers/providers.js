({
    init: function () {
        leftSide("fas fa-fw fa-network-wired", i18n("providers.providers"), "#providers", true);
        moduleLoaded("providers", this);
    },

    route: function () {
        $("#subTop").html("");
        $("#altForm").hide();

        document.title = i18n("windowTitle") + " :: " + i18n("providers.providers");

        loadingStart();
        GET("providers", "json", false, true).
        done(json => {
            // TODO f..ck!
            let top = 75;
            let height = $(window).height() - top;
            let h = '';
            h += `<div id='editorContainer' style='width: 100%; height: ${height}px;'>`;
            h += `<pre class="ace-editor mt-2" id="workflowEditor" style="position: relative; border: 1px solid #ced4da; border-radius: 0.25rem; width: 100%; height: 100%;"></pre>`;
            h += "</div>";
            h += `<span style='position: absolute; right: 35px; top: 35px;'><span id="workflowSave" class="hoverable"><i class="fas fa-save pr-2"></i>${i18n("providers.save")}</span></span>`;
            $("#mainForm").html(h);
            let editor = ace.edit("workflowEditor");
            editor.setTheme("ace/theme/chrome");
            editor.session.setMode("ace/mode/json");
            if (json.json && json.json !== "null") {
                editor.setValue(json.json.toString(), -1);
                editor.clearSelection();
            }
            editor.setFontSize(14);
            $("#workflowSave").off("click").on("click", () => {
                loadingStart();
                PUT("providers", "json", false, { "body": $.trim(editor.getValue()) }).
                fail(FAIL).
                always(() => {
                    loadingDone();
                });
            });
        }).
        fail(FAIL).
        always(() => {
            loadingDone();
        });
    },
}).init();