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
        fail(FAILPAGE).
        done(response => {
            let height = $(window).height() - mainFormTop;
            let h = '';
            h += `<div id='editorContainer' style='width: 100%; height: ${height}px;'>`;
            h += `<pre class="ace-editor mt-2" id="sheetEditor"></pre>`;
            h += "</div>";
            h += `<span style='position: absolute; right: 35px; top: 35px;'><span id="sheetSave" class="hoverable saveButton"><i class="fas fa-save pr-2"></i>${i18n("cs.sheetSave")}</span></span>`;
            $("#mainForm").html(h);
            let editor = ace.edit("sheetEditor");
            if (modules.darkmode && modules.darkmode.isDark())
                editor.setTheme("ace/theme/one_dark");
            else
                editor.setTheme("ace/theme/chrome");
            editor.setOptions({
                enableBasicAutocompletion: true,
                enableSnippets: true,
                enableLiveAutocompletion: true,
            });
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
            currentAceEditor = editor;
            currentAceEditorOriginalValue = currentAceEditor.getValue();
            editor.getSession().getUndoManager().reset();
            editor.clearSelection();
            editor.setFontSize(14);

            editor.commands.removeCommand("removeline");
            editor.commands.removeCommand("redo");

            editor.commands.addCommand({
                name: "removeline",
                description: "Remove line",
                bindKey: {
                    win: "Ctrl-Y",
                    mac: "Cmd-Y"
                },
                exec: function (editor) { editor.removeLines(); },
                scrollIntoView: "cursor",
                multiSelectAction: "forEachLine"
            });

            editor.commands.addCommand({
                name: "redo",
                description: "Redo",
                bindKey: {
                    win: "Ctrl-Shift-Z",
                    mac: "Command-Shift-Z"
                },
                exec: function (editor) { editor.redo(); }
            });

            editor.commands.addCommand({
                name: 'save',
                bindKey: {
                    win: "Ctrl-S",
                    mac: "Cmd-S"
                },
                exec: (() => {
                    $("#sheetSave").click();
                }),
            });

            $("#sheetSave").off("click").on("click", () => {
                let json;
                let err;
                try {
                    json = JSON.parse(editor.getValue());
                } catch (e) {
                    err = e.message;
                }
                if (!err) {
                    loadingStart();
                    let sheet = (json && json.sheet) ? json.sheet : params.sheet;
                    let date = (json && json.date) ? json.date : params.date;
                    PUT("cs", "sheet", false, {
                        "sheet": sheet,
                        "date": date,
                        "data": $.trim(editor.getValue()),
                    }).
                    fail(FAIL).
                    done(() => {
                        message(i18n("cs.sheetWasSaved"));
                        currentAceEditorOriginalValue = currentAceEditor.getValue();
                    }).
                    always(() => {
                        loadingDone();
                    });
                } else {
                    error(err, i18n("error"), 30);
                }
            });
            loadingDone();
        });
    },
}).init();