<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class MailHelper
{
    private $mail;

    public function __construct()
    {
        $this->mail = new PHPMailer(true);
        $this->configurarSMTP();
    }

    private function configurarSMTP()
    {
        $this->mail->isSMTP();
        $this->mail->Host = 'smtp.gmail.com';
        $this->mail->SMTPAuth = true;
        $this->mail->Username = 'preguntadospwii@gmail.com';
        $this->mail->Password = 'nabi hfvn wcdu sken';
        $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $this->mail->Port = 587;
        $this->mail->CharSet = 'UTF-8';
    }

    public function enviarCodigoVerificacion($destinatario, $codigo)
    {
        try {
            $this->mail->setFrom('preguntadospwii@gmail.com', 'Preguntados');
            $this->mail->addAddress($destinatario);
            $this->mail->Subject = 'Tu código de verificación - Preguntados';
            $this->mail->Body = "Tu código de verificación es: $codigo\n\nIngresa este código en la aplicación para completar tu registro.";
            $this->mail->AltBody = "Tu código de verificación es: $codigo\n\nIngresa este código en la aplicación para completar tu registro.";
            $this->mail->send();
            return true;
        } catch (Exception $e) {
            $error = "Error al enviar email: " . $this->mail->ErrorInfo;
            error_log($error);
            file_put_contents(__DIR__ . '/../log/email_debug.log', "[" . date('Y-m-d H:i:s') . "] $error\n", FILE_APPEND);
            return false;
        }
    }
}
