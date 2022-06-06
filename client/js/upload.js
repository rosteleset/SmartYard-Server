function modalUpload(url, post, callback) {
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
                        <tr style="display: none" id="uploadFileProgress">
                            <td colspan="2" class="pb-0">
                                <div class="progress">
                                    <div id="uploadFileProgressBar" class="progress-bar bg-primary progress-bar-striped" role="progressbar" aria-valuenow="90" aria-valuemin="0" aria-valuemax="100" style="width: 40%">&nbsp;</div>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td class="tdform">${i18n("chooseFile")}</td>
                            <td class="tdform-right">
                                <input type="file" id="fileInput" style="display: none"/>
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
        $("#uploadFileProgressBar").attr("aria-valuenow", p).css("width", p + "%");
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
                    ${files[0]}<br />
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

        $("#uploadFileProgress").show();

        let file = document.querySelector('#fileInput').files[0];
//            let allowed_size_mb = 20;
//            let allowed_mime_types = [ 'image/jpeg', 'image/png' ];
//
//            if(allowed_mime_types.indexOf(file.type) == -1) {
//                alert('Error : Incorrect file type');
//                return;
//            }
//
//            if(file.size > allowed_size_mb*1024*1024) {
//                alert('Error : Exceeded size');
//                return;
//            }

        let data = new FormData();

        data.append('file', file);

        for (let i in post) {
            data.append(i, post[i]);
        }

        data.append("_token", $.cookie("_token"));

        let request = new XMLHttpRequest();
        request.open('POST', url);

        // upload progress event
        request.upload.addEventListener('progress', function(e) {
            progress(Math.round((e.loaded / e.total)*100));
        });

        request.addEventListener("load", response => {
            $("#uploadFileProgress").hide();
            if (request.status !== 200) {
                error(request.statusText, request.status);
            } else {
                if (typeof callback === "function") {
                    callback(response);
                }
            }
        });

        request.addEventListener("error", error => {
            $("#uploadFileProgress").hide();
            console.log(error);
        });

        request.send(data);
    });

    autoZ($('#modalUpload').modal('show'));

    xblur();
}