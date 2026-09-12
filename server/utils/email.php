<?php

    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\Exception;

    function eMail($config, $to, $subj, $text) {

        if (@$config) {
            $mail = new PHPMailer(true);

            try {
                $mail->SMTPDebug = 0;
                $mail->CharSet = 'UTF-8';
                $mail->isSMTP();
                $mail->Host = $config["server"];
                $mail->SMTPAuth = true;
                $mail->Username = $config["username"];
                $mail->Password = $config["password"];

                $port = (int)@$config["port"];
                if ($port <= 0) {
                    $port = 587;
                }
                $mail->Port = $port;

                $timeout = (int)@$config["timeout"];
                if ($timeout <= 0) {
                    $timeout = 10;
                }
                $mail->Timeout = $timeout;

                $secure = strtolower(trim((string)@$config["secure"]));

                // Port 465 is SMTPS by convention; don't hang on STARTTLS there.
                if ($secure === "" && $port === 465) {
                    $secure = "smtps";
                }

                if ($secure === "ssl" || $secure === "smtps") {
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                } else
                if ($secure === "none" || $secure === "false" || $secure === "0") {
                    $mail->SMTPSecure = false;
                    $mail->SMTPAutoTLS = false;
                } else {
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                }

                if (@$config["from_name"]) {
                    $mail->setFrom($config["from"], $config["from_name"]);
                } else {
                    $mail->setFrom($config["from"], $config["from"]);
                }
                $mail->addAddress($to);
                $mail->isHTML(true);
                $mail->Subject = $subj;
                $mail->Body = $text;
                $mail->send();

                return true;
            } catch (Exception $ex) {
                $info = trim((string)@$mail->ErrorInfo);
                $message = $ex->getMessage();

                if ($info !== "" && stripos($message, $info) === false) {
                    return $message . " | " . $info;
                }

                return $message ?: ($info ?: "emailSendFailed");
            }
        } else {
            return false;
        }
    }
