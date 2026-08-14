<?php

namespace Classes;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Email
{
    public string $nombre;
    public string $email;
    public string $token;

    public function __construct(string $nombre, string $email, string $token = '')
    {
        $this->nombre = $nombre;
        $this->email  = $email;
        $this->token  = $token;
    }

    public function enviarConfirmacion(): void
    {
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = $_ENV['SMTP_HOST']     ?? 'localhost';
            $mail->SMTPAuth   = true;
            $mail->Username   = $_ENV['SMTP_USER']     ?? '';
            $mail->Password   = $_ENV['SMTP_PASS']     ?? '';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = (int)($_ENV['SMTP_PORT'] ?? 587);
            $mail->CharSet    = 'UTF-8';

            $mail->setFrom($_ENV['SMTP_FROM'] ?? 'noreply@akim.local', 'AKIM');
            $mail->addAddress($this->email, $this->nombre);

            $mail->isHTML(true);
            $mail->Subject = 'Confirmá tu cuenta AKIM';
            $mail->Body    = "<p>Hola <strong>{$this->nombre}</strong>,</p>
                              <p>Tu token de confirmación: <strong>{$this->token}</strong></p>";
            $mail->send();
        } catch (Exception $e) {
            error_log('[AKIM Email] ' . $mail->ErrorInfo);
        }
    }
}
