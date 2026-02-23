<?php

declare(strict_types=1);

class MailHelper
{
    public static function sendPassword(string $toEmail, string $fullName, string $password): bool
    {
        $phpMailerBase = null;
        $candidatePaths = [
            __DIR__ . '/PHPMailer/',
            dirname(__DIR__, 2) . '/PHPMailer-master/src/',
            __DIR__ . '/../../PHPMailer-master/src/',
        ];

        foreach ($candidatePaths as $candidatePath) {
            if (is_file($candidatePath . 'PHPMailer.php') && is_file($candidatePath . 'SMTP.php') && is_file($candidatePath . 'Exception.php')) {
                $phpMailerBase = $candidatePath;
                break;
            }
        }

        if ($phpMailerBase === null) {
            return false;
        }

        $requiredFiles = ['Exception.php', 'PHPMailer.php', 'SMTP.php'];

        foreach ($requiredFiles as $requiredFile) {
            $fullPath = $phpMailerBase . $requiredFile;
            if (!is_file($fullPath)) {
                return false;
            }
            require_once $fullPath;
        }

        if (!class_exists('\PHPMailer\\PHPMailer\\PHPMailer')) {
            return false;
        }

        $mailConfigPath = dirname(__DIR__) . '/config/mail_config.php';
        $mailConfig = [];

        if (is_file($mailConfigPath)) {
            $loadedConfig = require $mailConfigPath;
            if (is_array($loadedConfig)) {
                $mailConfig = $loadedConfig;
            }
        }

        $mailUsername = (string) ($mailConfig['username'] ?? getenv('MAIL_USERNAME') ?: '');
        $mailPassword = (string) ($mailConfig['password'] ?? getenv('MAIL_PASSWORD') ?: '');
        $mailHost = (string) ($mailConfig['host'] ?? getenv('MAIL_HOST') ?: 'smtp.gmail.com');
        $mailPort = (int) ($mailConfig['port'] ?? getenv('MAIL_PORT') ?: 587);
        $mailFrom = (string) ($mailConfig['from_email'] ?? getenv('MAIL_FROM_EMAIL') ?: $mailUsername);
        $mailFromName = (string) ($mailConfig['from_name'] ?? getenv('MAIL_FROM_NAME') ?: 'TaskFlow Pro Admin');

        if ($mailUsername === '' || $mailPassword === '' || $mailFrom === '') {
            return false;
        }

        $safeName = htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8');
        $safeEmail = htmlspecialchars($toEmail, ENT_QUOTES, 'UTF-8');
        $safePassword = htmlspecialchars($password, ENT_QUOTES, 'UTF-8');

        try {
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = $mailHost;
            $mail->SMTPAuth = true;
            $mail->Username = $mailUsername;
            $mail->Password = $mailPassword;
            $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = $mailPort;
            $mail->SMTPDebug = 0;

            $mail->setFrom($mailFrom, $mailFromName);
            $mail->addAddress($toEmail, $fullName);

            $mail->isHTML(true);
            $mail->Subject = 'Your TaskFlow Pro Account Credentials';
            $mail->Body = "<h3>Welcome to TaskFlow Pro, {$safeName}!</h3>"
                . "<p>Your account has been created.</p>"
                . "<p><strong>Login:</strong> {$safeEmail}</p>"
                . "<p><strong>Temporary Password:</strong> {$safePassword}</p>"
                . '<p>please log in and change your password immediately.</p>';

            $mail->send();
            return true;
        } catch (\Exception $exception) {
            error_log('PHPMailer Error: ' . $exception->getMessage());
            return false;
        }
    }
}
