({
    init: function () {
        // submodule - module<dot>submodule
        moduleLoaded("tt.json", this);
    },

    route: function (params) {
        loadingStart();

        subTop();

        if ($("#altForm:visible").length > 0) {
            $("#mainForm").html("");
            $("#altForm").hide();
        }

        GET("tt", "json", params["issue"], true).
        done(r => {
            if (r && r.issue) {
                let height = $(window).height() - mainFormTop;
                let h = '';
                h += `<div id='editorContainer' style='width: 100%; height: ${height}px;'>`;
                h += `<pre class="ace-editor mt-2" id="issueEditor"></pre>`;
                h += "</div>";
                if (AVAIL("tt", "json", "PUT")) {
                    h += `<span style='position: absolute; right: 35px; top: 35px;'><span id="issueSave" class="hoverable saveButton"><i class="fas fa-save pr-2"></i>${i18n("tt.issueSave")}</span></span>`;
                }
                $("#mainForm").html(h);
                let editor = ace.edit("issueEditor");
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
                delete r.issue._id;
                editor.setValue(JSON.stringify(r.issue, null, 4), -1);
                currentAceEditor = editor;
                currentAceEditorOriginalValue = currentAceEditor.getValue();
                editor.getSession().getUndoManager().reset();
                editor.clearSelection();
                editor.focus();
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
                if (AVAIL("tt", "json", "PUT")) {
                    editor.commands.addCommand({
                        name: 'save',
                        bindKey: {
                            win: "Ctrl-S",
                            mac: "Cmd-S"
                        },
                        exec: (() => {
                            $("#issueSave").click();
                        }),
                    });
                }
                if (AVAIL("tt", "json", "PUT")) {
                    $("#issueSave").off("click").on("click", () => {
                        loadingStart();
                        let i;
                        try {
                            i = JSON.parse(editor.getValue());
                            i.issueId = r.issue.issueId;
                        } catch (e) {
                            loadingDone();
                            error(e.message, i18n("error"), 30);
                            return;
                        }
                        delete i._id;
                        PUT("tt", "json", false, { issue: i  }).
                        fail(FAIL).
                        done(() => {
                            message(i18n("tt.issueWasSaved"));
                            currentAceEditorOriginalValue = JSON.stringify(i, null, 4);
                            editor.setValue(currentAceEditorOriginalValue, -1);
                        }).
                        always(() => {
                            loadingDone();
                        });
                    });
                }
            } else {
                FAILPAGE();
            }
            loadingDone();
        }).
        fail(FAILPAGE);
    },

}).init();
