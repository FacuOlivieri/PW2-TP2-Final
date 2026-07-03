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

    public function enviarCodigoVerificacion($destinatario, $codigo, $username = null)
    {
        try {
            $this->mail->setFrom('preguntadospwii@gmail.com', 'Preguntados');
            $this->mail->addAddress($destinatario);
            $this->mail->isHTML(true);
            $this->mail->Subject = 'Tu código de verificación - Preguntados';
            $this->mail->Body = $this->generarPlantillaVerificacion($destinatario, $codigo, $username);
            $this->mail->AltBody = "Hola" . ($username ? " $username" : "") . ",\n\nTu código de verificación es: $codigo\n\nIngresá este código en la aplicación para completar tu registro.\n\nPreguntados";
            $this->mail->send();
            return true;
        } catch (Exception $e) {
            $error = "Error al enviar email: " . $this->mail->ErrorInfo;
            error_log($error);
            file_put_contents(__DIR__ . '/../log/email_debug.log', "[" . date('Y-m-d H:i:s') . "] $error\n", FILE_APPEND);
            return false;
        }
    }

    private function generarPlantillaVerificacion($destinatario, $codigo, $username = null)
    {
        $saludo = $username ? "¡Hola, <strong>" . htmlspecialchars($username) . "</strong>!" : "¡Hola!";
        $mailEscapado = htmlspecialchars($destinatario);

        return '
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin: 0; padding: 0; background-color: #f8f9ff; font-family: \'Open Sans\', Arial, Helvetica, sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #f8f9ff; padding: 32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width: 560px; background-color: #ffffff; border-radius: 20px; overflow: hidden; box-shadow: 0 14px 38px rgba(31, 41, 55, 0.10);">

                    <!-- Header con gradiente -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #7c3aed, #6366f1); background-color: #7c3aed; padding: 36px 32px; text-align: center;">
                            <h1 style="margin: 0; color: #ffffff; font-size: 30px; font-weight: 800; letter-spacing: 1px;">
                                Preguntados
                            </h1>
                            <p style="margin: 8px 0 0; color: rgba(255, 255, 255, 0.85); font-size: 14px; letter-spacing: 2px; text-transform: uppercase; font-weight: 600;">
                                Verificación de cuenta
                            </p>
                        </td>
                    </tr>

                    <!-- Cuerpo -->
                    <tr>
                        <td style="padding: 36px 32px 24px;">
                            <p style="margin: 0 0 8px; color: #1f2937; font-size: 20px; font-weight: 700;">
                                ' . $saludo . '
                            </p>
                            <p style="margin: 0 0 24px; color: #6b7280; font-size: 15px; line-height: 1.6;">
                                ¡Gracias por registrarte! Estás a un paso de empezar a jugar.
                                Usá el siguiente código para verificar tu cuenta:
                            </p>

                            <!-- Código -->
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td align="center" style="padding: 8px 0 24px;">
                                        <div style="display: inline-block; background-color: #f5f3ff; border: 2px dashed #7c3aed; border-radius: 16px; padding: 20px 44px;">
                                            <span style="color: #7c3aed; font-size: 38px; font-weight: 800; letter-spacing: 10px; font-family: \'Courier New\', monospace;">' . htmlspecialchars($codigo) . '</span>
                                        </div>
                                    </td>
                                </tr>
                            </table>

                            <!-- Datos de la cuenta -->
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #eef2ff; border-radius: 14px; margin-bottom: 24px;">
                                <tr>
                                    <td style="padding: 18px 22px;">
                                        <p style="margin: 0 0 6px; color: #6366f1; font-size: 12px; font-weight: 800; text-transform: uppercase; letter-spacing: 1px;">
                                            Datos de tu cuenta
                                        </p>
                                        ' . ($username ? '<p style="margin: 0 0 4px; color: #1f2937; font-size: 14px;"><strong style="color: #6d28d9;">Usuario:</strong> ' . htmlspecialchars($username) . '</p>' : '') . '
                                        <p style="margin: 0; color: #1f2937; font-size: 14px;"><strong style="color: #6d28d9;">Email:</strong> ' . $mailEscapado . '</p>
                                    </td>
                                </tr>
                            </table>

                            <!-- Aviso -->
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #fffbeb; border-left: 4px solid #f59e0b; border-radius: 10px;">
                                <tr>
                                    <td style="padding: 14px 18px;">
                                        <p style="margin: 0; color: #92400e; font-size: 13px; line-height: 1.5;">
                                            <strong>Importante:</strong> si no creaste esta cuenta, podés ignorar este correo. Nunca compartas este código con nadie.
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #f8f9ff; padding: 22px 32px; text-align: center; border-top: 1px solid #e5e7eb;">
                            <p style="margin: 0 0 4px; color: #6b7280; font-size: 13px; font-weight: 600;">
                                Preguntados · Programación Web II
                            </p>
                            <p style="margin: 0; color: #9ca3af; font-size: 12px;">
                                Este es un correo automático, por favor no respondas a este mensaje.
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>';
    }
}
