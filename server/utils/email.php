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
                $mail->SMTPSecure = 'tls';
                $mail->Port = $config["port"];
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
                return $ex;
            }
        } else {
            return false;
        }
    }
