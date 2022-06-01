({
    init: function () {
        // submodule - module<dot>submodule
        moduleLoaded("tt.createIssue", this);
    },

    route: function (params) {
        $("#altForm").hide();

        document.title = i18n("windowTitle") + " :: " + i18n("tt.createIssueTitle");

        let h = '';

        h += `<div class="card mt-2">`;
        h += `<div class="card-header">`;
        h += `<h3 class="card-title">`;
        h += i18n("tt.createIssueTitle");
        h += `</h3>`;
        h += `</div>`;
        h += `<div class="card-body table-responsive p-0">`;
        h += `<table class="table table-hover tform-borderless">`;

        h += `<tbody>`;

        h += `
            <tr>
                <td class="tdform">${i18n("tt.project")}</td>
                <td class="tdform-right">
                    <select id="newIssueProject" class="form-control select2">
                    </select>
                </td>
            </tr>
        `;

        h += `
            <tr>
                <td class="tdform">${i18n("tt.subject")}</td>
                <td class="tdform-right">
                    <input id="newIssueSubject" type="text" class="form-control" autocomplete="off" placeholder="${i18n("tt.subject")}">
                </td>
            </tr>
        `;

        h += `
            <tr>
                <td class="tdform-top">${i18n("tt.description")}</td>
                <td class="tdform-right">
                    <textarea id="newIssueDescription" class="form-control" autocomplete="off" placeholder="${i18n("tt.description")}"></textarea>
                </td>
            </tr>
        `;

        h += `
            <tr>
                <td class="tdform">${i18n("tt.tags")}</td>
                <td class="tdform-right">
                    <select id="newIssueTags" class="form-control" placeholder="${i18n("tt.tags")}" multiple="multiple"></select>
                </td>
            </tr>
        `;

        h += `</tbody>`;

        h += `<tfoot>`;
        h += `<tr>`;
        h += `<td colspan="2">`;
        h += `<button type="submit" class="btn btn-primary modalFormOk">${i18n("tt.createIssue")}</button>`;
        h += `<button type="cancel" class="btn btn-default float-right modalFormCancel">${i18n("cancel")}</button>`;
        h += `</td>`;
        h += `</tr>`;
        h += `</tfoot>`;

        h += `</table>`;
        h += `</div>`;
        h += `</div>`;

        $("#mainForm").html(h);

        $(".modalFormCancel").off("click").on("click", () => {
            history.back();
        });

        $("#newIssueDescription").summernote({
            tabDisable: false,
            tabsize: 4,
            height: 300,
            minHeight: null,
            maxHeight: null,
            disableResizeEditor: true,
            lang: (window.lang["_code"] === "ru")?"ru-RU":"en-US",
            toolbar: [
                ['style', ['style']],
                ['font', ['bold', 'italic', 'underline', 'strikethrough', 'superscript', 'subscript', 'clear']],
                ['fontname', ['fontname']],
                ['fontsize', ['fontsize']],
                ['color', ['color']],
                ['para', ['ul', 'ol', 'paragraph']],
                ['height', ['height']],
                ['table', ['table']],
                ['insert', ['link', 'picture', 'hr']],
            ],
        });
        $('.note-statusbar').hide();

        $("#newIssueTags").select2({
            tags: true,
            language: window.lang["_code"],
        });

        loadingDone();
    },
}).init();