({
    init: function () {
        // submodule - module<dot>submodule
        moduleLoaded("cs.sheet", this);
    },

    route: function (params) {
        $("#rightTopDynamic").html("");

        QUERY("cs", "sheet", {
            "sheet": params.sheet,
            "date": params.date,
        }).
        fail(FAIL).
        fail(() => {
            history.back();
        }).
        done(response => {
            // TODO f..ck!
            let top = 75;
            let height = $(window).height() - top;
            let h = '';
            h += `<div id='editorContainer' style='width: 100%; height: ${height}px;'>`;
            h += `<pre class="ace-editor mt-2" id="sheetEditor" style="position: relative; border: 1px solid #ced4da; border-radius: 0.25rem; width: 100%; height: 100%;"></pre>`;
            h += "</div>";
            h += `<span style='position: absolute; right: 35px; top: 35px;'><span id="sheetSave" class="hoverable"><i class="fas fa-save pr-2"></i>${i18n("cs.sheetSave")}</span></span>`;
            $("#mainForm").html(h);
            let editor = ace.edit("sheetEditor");
            editor.setTheme("ace/theme/chrome");
            editor.session.setMode("ace/mode/json");
            let pretty = false;
            try {
                pretty = JSON.parse(response.sheet);
            } catch (_) {
                //
            }
            if (pretty) {
                pretty.sheet = params.sheet;
                pretty.date = params.date;
                pretty = JSON.stringify(pretty, null, 4);  
                editor.setValue(pretty, -1);
            } else {
                editor.setValue(response.sheet, -1);
            }
            editor.clearSelection();
            editor.setFontSize(14);
            $("#sheetSave").off("click").on("click", () => {
                loadingStart();
                PUT("cs", "sheet", false, {
                    "sheet": params.sheet,
                    "date": params.date,
                    "data": $.trim(editor.getValue()),
                }).
                fail(FAIL).
                done(() => {
                    message(i18n("cs.sheetWasSaved"));
                }).
                always(() => {
                    loadingDone();
                });
            });
        }).
        done(loadingDone);
    },
}).init();