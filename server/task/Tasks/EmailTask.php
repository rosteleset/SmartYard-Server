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

    public function onTask(): bool
    {
        if (config('email')) {
            $mail = new PHPMailer(true);

            try {
                $mail->SMTPDebug = 0;
                $mail->CharSet = 'UTF-8';

                $mail->isSMTP();

                $mail->Host = config('email.server');
                $mail->SMTPAuth = true;
                $mail->Username = config('email.username');
                $mail->Password = config('email.password');
                $mail->SMTPSecure = 'tls';
                $mail->Port = config('email.port');

                $mail->setFrom(config('email.from'), config('email.from_name') ?? config('email.from'));

                $mail->addAddress($this->to);
                $mail->isHTML(true);

                $mail->Subject = $this->subject;
                $mail->Body = $this->text;

                $mail->send();
            } catch (Exception) {
            }
        }

        return true;
    }
}