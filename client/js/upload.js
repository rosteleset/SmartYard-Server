// modalUpload([ "image/jpeg", "image/png", "application/pdf" ], 2 * 1024 * 1024, '/server/');

function modalUpload(mimeTypes, maxSize, url, postFields, callback) {
    let h = `
        <div class="card mt-0 mb-0">
            <div class="card-header">
                <h3 class="card-title">${i18n("upload")}</h3>
                <button type="button" class="btn btn-danger btn-xs btn-tool-rbt-right ml-2 float-right uploadModalFormCancel" data-dismiss="modal" title="${i18n("cancel")}"><i class="far fa-fw fa-times-circle"></i></button>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table tform-borderless">
                    <tbody>
                        <tr style="display: none">
                            <td colspan="2" id="uploadFileInfo">&nbsp;</td>
                        </tr>
                        <tr>
                            <td class="tdform">${i18n("chooseFile")}</td>
                            <td class="tdform-right">
                                <input type="file" id="fileInput" style="display: none" accept="${mimeTypes?mimeTypes.join(","):""}"/>
                                <div class="input-group">
                                    <input id="fakeFileInput" type="text" class="form-control modalFormField" autocomplete="off" placeholder="${i18n("chooseFile")}" readonly="readonly">
                                    <div class="input-group-append">
                                        <span id="fakeFileInputButton" class="input-group-text pointer"><i class="fas fa-fw fa-folder-open"></i></span>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="2"><button id="uploadButton" class="btn btn-default">${i18n("doUpload")}</button></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    `;

    $("#modalUploadBody").html(h);

    function progress(p) {
        loadingProgress.set(p);
    }

    $("#fakeFileInputButton").off("click").on("click", () => {
        $("#fakeFileInput").val("");
        $("#uploadFileInfo").parent().hide();
        $("#uploadFileProgress").hide();
        progress(0);
        $("#fileInput").off("change").val("").click().on("change", () => {
            let files = document.querySelector("#fileInput").files;
            if (files && files.length && files[0].name) {
                $("#fakeFileInput").val(files[0].name);
                $("#uploadFileInfo").html(`
                    ${i18n("fileName")}: ${files[0].name}<br />
                    ${i18n("fileSize")}: ${formatBytes(files[0].size)}<br />
                    ${i18n("fileDate")}: ${date("Y-m-d H:i", files[0].lastModified / 1000)}<br />
                    ${i18n("fileType")}: ${files[0].type}<br />
                `).parent().show();
                console.log(document.querySelector("#fileInput").files);
            }
        });
    });

    $("#uploadButton").off("click").on("click", () => {
        if (document.querySelector('#fileInput').files.length === 0) {
            error(i18n("noFileSelected"));
            return;
        }

        if (document.querySelector('#fileInput').files.length > 1) {
            error(i18n("multiuploadNotSupported"));
            return;
        }

        let file = document.querySelector('#fileInput').files[0];

        if (mimeTypes && mimeTypes.indexOf(file.type) === -1) {
            error("incorrectFileType");
            return;
        }

        if (maxSize && file.size > maxSize) {
            error("exceededSize");
            return;
        }

        $('#modalUpload').modal('hide');

        autoZ($('#progress').modal({
            backdrop: 'static',
            keyboard: false,
        }));

        let data = new FormData();

        data.append('file', file);

        for (let i in postFields) {
            data.append(i, postFields[i]);
        }

        data.append("_token", $.cookie("_token"));

        let request = new XMLHttpRequest();
        request.open('POST', url);

        request.upload.addEventListener('progress', function(e) {
            progress(Math.floor((e.loaded / e.total)*100));
        });

        request.addEventListener("loadend", response => {
            $('#progress').modal('hide');
            if (request.status !== 200) {
                error(request.statusText, request.status);
            } else {
                if (typeof callback === "function") {
                    callback(response);
                }
            }
        });

        request.send(data);
    });

    autoZ($('#modalUpload')).modal('show');

    xblur();
}