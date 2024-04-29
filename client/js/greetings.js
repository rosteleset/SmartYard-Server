function showLoginForm() {
    $("#mainForm").html("");
    $("#altForm").hide();
    $("#page404").hide();
    $("#pageError").hide();
    $("#forgotForm").hide();
    $("#loginForm").show();

    $("#loginBoxLogin").val(lStore("_login"));
    $("#loginBoxServer").val(lStore("_server"));

    if (!$("#loginBoxServer").val()) {
        $("#loginBoxServer").val(config.defaultServer);
    }

    let server = $("#loginBoxServer").val();

    while (server[server.length - 1] === "/") {
        server = server.substring(0, server.length - 1);
    }

    $.get(server + "/accounts/forgot?available=ask").done(() => {
        $("#loginBoxForgot").show();
    });

    loadingDone(true);

    setTimeout(() => {
        if ($("#loginBoxLogin").val()) {
            $("#loginBoxPassword").focus();
        } else {
            $("#loginBoxLogin").focus();
        }
    }, 100);
}

function showForgotPasswordForm() {
    $("#mainForm").html("");
    $("#altForm").hide();
    $("#page404").hide();
    $("#pageError").hide();
    $("#loginForm").hide();
    $("#forgotForm").show();

    $("#forgotBoxServer").val(lStore("_server"));
    if (!$("#forgotBoxServer").val()) {
        $("#forgotBoxServer").val($("#loginBoxServer").val());
    }
    if (!$("#forgotBoxServer").val()) {
        $("#forgotBoxServer").val(config.defaultServer);
    }

    loadingDone(true);

    $("#forgotBoxEMail").focus();
}
