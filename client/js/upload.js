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
    mimeTypes = mimeTypes?escapeHTML(mimeTypes.join(",")):"";

    $("#uploadModalTitle").text(i18n("upload"));
    $("#uploadModalCancel").attr("title", i18n("cancel"));
    $("#chooseFileToUpload").text(i18n("chooseFile"));
    $("#uploadButton").text(i18n("doUpload"));
}    

function loadFile(mimeTypes, maxSize, callback) {
    uploadForm(mimeTypes);

    let files = [];
    let file = false;

    $("#chooseFileToUpload").off("click").on("click", () => {
        $("#fileIcon").html(`<h1><i class="far fa-folder-open"></i></h1>`);
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
            $('#uploadModal').modal('hide');

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

    autoZ($('#uploadModal')).modal('show');

    xblur();

    $("#chooseFileToUpload").click();
}
