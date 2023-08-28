<?php

namespace Selpol\Task\Tasks;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

use Selpol\Task\Task;

class EmailTask extends Task
{
    public string $to;
    public string $subject;
    public string $text;

    public function __construct(string $to, string $subject, string $text)
    {
        parent::__construct($to . ' - ' . $subject);

        $this->to = $to;
        $this->subject = $subject;
        $this->text = $text;
    }

    public function onTask()
    {
        if (@$this->config['email']) {
            $mail = new PHPMailer(true);

            try {
                $mail->SMTPDebug = 0;
                $mail->CharSet = 'UTF-8';

                $mail->isSMTP();

                $mail->Host = $this->config["email"]["server"];
                $mail->SMTPAuth = true;
                $mail->Username = $this->config["email"]["username"];
                $mail->Password = $this->config["email"]["password"];
                $mail->SMTPSecure = 'tls';
                $mail->Port = $this->config["email"]["port"];

                if (@$this->config["email"]["from_name"]) $mail->setFrom($this->config["email"]["from"], $this->config["email"]["from_name"]);
                else $mail->setFrom($this->config["email"]["from"], $this->config["email"]["from"]);

                $mail->addAddress($this->to);
                $mail->isHTML(true);

                $mail->Subject = $this->subject;
                $mail->Body = $this->text;

                $mail->send();
            } catch (Exception) {
            }
        }
    }
}