<?php

    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\Exception;

    function eMail($config, $to, $subj, $text) {

        if (@$config["email"]) {
            require 'lib/PHPMailer/src/Exception.php';
            require 'lib/PHPMailer/src/PHPMailer.php';
            require 'lib/PHPMailer/src/SMTP.php';

            $mail = new PHPMailer(true);

            try {
                $mail->SMTPDebug = 0;
                $mail->CharSet = 'UTF-8';
                $mail->isSMTP();
                $mail->Host = $config["email"]["server"];
                $mail->SMTPAuth = true;
                $mail->Username = $config["email"]["username"];
                $mail->Password = $config["email"]["password"];
                $mail->SMTPSecure = 'tls';
                $mail->Port = $config["email"]["port"];
                if (@$config["email"]["from_name"]) {
                    $mail->setFrom($config["email"]["from"], $config["email"]["from_name"]);
                } else {
                    $mail->setFrom($config["email"]["from"], $config["email"]["from"]);
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
