// modalUpload([ "image/jpeg", "image/png", "application/pdf" ], 2 * 1024 * 1024, '/server/');

const mime2fa = {
    false: "far fa-fw fa-file",
    ".jpg": "far fa-fw fa-file-image",
    ".png": "far fa-fw fa-file-image",
    ".tiff": "far fa-fw fa-file-image",
    ".pdf": "far fa-fw fa-file-pdf",
    ".mp4": "far fa-fw fa-file-video",
    ".odt": "far fa-fw fa-file-word",
    ".doc": "far fa-fw fa-file-word",
    ".docx": "far fa-fw fa-file-word",
    ".ods": "far fa-fw fa-file-excel",
    ".xlsx": "far fa-fw fa-file-excel",
    ".xls": "far fa-fw fa-file-excel",
};

function uploadForm(mimeTypes) {
    mimeTypes = escapeHTML(mimeTypes?mimeTypes.join(","):"");
    let h = `
        <div class="card mt-0 mb-0">
            <div class="card-header">
                <h3 class="card-title">${i18n("upload")}</h3>
                <button type="button" class="btn btn-danger btn-xs btn-tool-rbt-right ml-2 float-right uploadModalFormCancel" data-dismiss="modal" title="${i18n("cancel")}"><i class="far fa-fw fa-times-circle"></i></button>
            </div>
            <div class="card-body table-responsive p-0">
                <input type="file" id="fileInput" style="display: none" accept="${mimeTypes}"/>
                <table class="table tform-borderless" style="width: 100%;">
                    <tbody>
                        <tr style="display: none">
                            <td id="fileIcon">&nbsp;</td>
                            <td id="uploadFileInfo" style="text-align: left; width: 100%;">&nbsp;</td>
                        </tr>
                    </tbody>
                </table>
                <table class="table tform-borderless">
                    <tfoot>
                        <tr>
                            <td><button id="uploadButton" class="btn btn-default">${i18n("doUpload")}</button></td>
                            <td style="text-align: right"><button id="chooseFileToUpload" class="btn btn-default">${i18n("chooseFile")}...</button></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    `;

    $("#modalUploadBody").html(h);
}

function modalUpload(mimeTypes, maxSize, url, postFields, callback) {

    uploadForm(mimeTypes);

    function progress(p) {
        loadingProgress.set(p);
    }

    $("#chooseFileToUpload").off("click").on("click", () => {
        $("#uploadFileInfo").parent().hide();
        $("#uploadFileProgress").hide();
        progress(0);
        $("#fileInput").off("change").val("").click().on("change", () => {
            let files = document.querySelector("#fileInput").files;
            if (files && files.length && files[0].name) {
                $("#uploadFileInfo").html(`
                    ${files[0].name}<br />
                    ${i18n("fileSize")}: ${formatBytes(files[0].size)}<br />
                    ${i18n("fileDate")}: ${date("Y-m-d H:i", files[0].lastModified / 1000)}<br />
                    ${i18n("fileType")}: ${files[0].type}<br />
                `).parent().show();
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

function loadFile(mimeTypes, maxSize, callback) {
    uploadForm(mimeTypes);

    let files = [];
    let file = false;

    $("#chooseFileToUpload").off("click").on("click", () => {
        $("#fileIcon").html(`<h1><i class="fas fa-file-upload"></i></h1>`);
        $("#fileIcon").attr("title", i18n("fileNotUploaded"));
        $("#uploadFileInfo").html(`
            ${i18n("fileNotUploaded")}<br />
            ${i18n("fileSize")}:<br />
            ${i18n("fileDate")}:<br />
            ${i18n("fileType")}:<br />
        `).parent().show();
        $("#uploadButton").hide();
        $("#fileInput").off("change").val("").click().on("change", () => {
            files = document.querySelector("#fileInput").files;

            if (files.length === 0) {
                error(i18n("noFileSelected"));
                return;
            }

            if (files.length > 1) {
                error(i18n("multiuploadNotSupported"));
                return;
            }

            file = files[0];

            if (mimeTypes && mimeTypes.indexOf(file.type) === -1) {
                error("incorrectFileType");
                return;
            }

            if (maxSize && file.size > maxSize) {
                error("exceededSize");
                return;
            }

            if (file) {
                let icon;
                if (mime2fa[file.type]) {
                    icon = mime2fa[file.type];
                } else {
                    icon = mime2fa[false];
                }
                $("#fileIcon").html(`<h1><i class="${icon}"></i></h1>`);
                $("#fileIcon").attr("title", file.name);
                $("#uploadFileInfo").html(`
                    ${file.name}<br />
                    ${i18n("fileSize")}: ${formatBytes(file.size)}<br />
                    ${i18n("fileDate")}: ${date("Y-m-d H:i", file.lastModified / 1000)}<br />
                    ${i18n("fileType")}: ${file.type}<br />
                `).parent().show();
                $("#uploadButton").show();
            }
        });
    });

    $("#uploadButton").off("click").on("click", () => {
        if (file) {
            $('#modalUpload').modal('hide');

            fetch(URL.createObjectURL(file)).then(response => {
                return response.blob();
            }).then(blob => {
                setTimeout(() => {
                    let reader = new FileReader();
                    reader.onloadend = () => {
                        let body = reader.result.split(';base64,')[1];
                        if (typeof callback === "function") {
                            callback({
                                name: file.name,
                                size: file.size,
                                date: file.lastModified,
                                type: file.type,
                                body: body,
                            });
                        }
                    };
                    reader.readAsDataURL(blob);
                }, 100);
            });
        }
    });

    autoZ($('#modalUpload')).modal('show');

    xblur();

    $("#chooseFileToUpload").click();
}
