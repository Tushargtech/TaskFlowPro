<?php

declare(strict_types=1);

class MailHelper
{
    public static function sendPassword(string $toEmail, string $fullName, string $password): bool
    {
        if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
            error_log('MailHelper validation error: invalid recipient email address.');
            return false;
        }

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
            error_log('MailHelper configuration error: PHPMailer library files not found.');
            return false;
        }

        $requiredFiles = ['Exception.php', 'PHPMailer.php', 'SMTP.php'];

        foreach ($requiredFiles as $requiredFile) {
            $fullPath = $phpMailerBase . $requiredFile;
            if (!is_file($fullPath)) {
                error_log('MailHelper configuration error: Missing PHPMailer file ' . $fullPath);
                return false;
            }
            require_once $fullPath;
        }

        if (!class_exists('\PHPMailer\\PHPMailer\\PHPMailer')) {
            error_log('MailHelper configuration error: PHPMailer class not loaded.');
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

        $mailUsernameRaw = trim((string) ($mailConfig['username'] ?? ''));
        if ($mailUsernameRaw === '') {
            $mailUsernameRaw = trim((string) (getenv('MAIL_USERNAME') ?: ''));
        }

        $mailPasswordRaw = (string) ($mailConfig['password'] ?? '');
        if (trim($mailPasswordRaw) === '') {
            $mailPasswordRaw = (string) (getenv('MAIL_PASSWORD') ?: '');
        }

        $mailHostRaw = trim((string) ($mailConfig['host'] ?? ''));
        if ($mailHostRaw === '') {
            $mailHostRaw = trim((string) (getenv('MAIL_HOST') ?: 'smtp.gmail.com'));
        }

        $mailPortRaw = (int) ($mailConfig['port'] ?? 0);
        if ($mailPortRaw <= 0) {
            $mailPortRaw = (int) (getenv('MAIL_PORT') ?: 587);
        }

        $mailFromRaw = trim((string) ($mailConfig['from_email'] ?? ''));
        if ($mailFromRaw === '') {
            $mailFromRaw = trim((string) (getenv('MAIL_FROM_EMAIL') ?: $mailUsernameRaw));
        }

        $mailFromNameRaw = trim((string) ($mailConfig['from_name'] ?? ''));
        if ($mailFromNameRaw === '') {
            $mailFromNameRaw = trim((string) (getenv('MAIL_FROM_NAME') ?: 'TaskFlow Pro Admin'));
        }

        $mailUsername = $mailUsernameRaw;
        $mailPassword = preg_replace('/\s+/', '', $mailPasswordRaw);
        $mailHost = $mailHostRaw;
        $mailPort = $mailPortRaw;
        $mailFrom = $mailFromRaw;
        $mailFromName = $mailFromNameRaw;

        if ($mailUsername === '' || $mailPassword === '' || $mailFrom === '') {
            error_log('MailHelper configuration error: username/password/from_email is missing.');
            return false;
        }

        if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
            error_log('MailHelper validation error: invalid recipient email address.');
            return false;
        }

        $safeName = htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8');
        $safeEmail = htmlspecialchars($toEmail, ENT_QUOTES, 'UTF-8');
        $safePassword = htmlspecialchars($password, ENT_QUOTES, 'UTF-8');

        $mail = null;
        try {
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = $mailHost;
            $mail->SMTPAuth = true;
            $mail->Username = $mailUsername;
            $mail->Password = $mailPassword;
            $mail->SMTPSecure = $mailPort === 465
                ? \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS
                : \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = $mailPort;
            $mail->SMTPDebug = 0;
            $mail->Timeout = 20;

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
            $mailerError = $mail instanceof \PHPMailer\PHPMailer\PHPMailer ? $mail->ErrorInfo : '';
            error_log('PHPMailer Error: ' . $exception->getMessage() . ($mailerError !== '' ? ' | MailerError: ' . $mailerError : ''));
            return false;
        }
    }

    public static function sendPasswordResetLink(string $toEmail, string $fullName, string $resetUrl): bool
    {
        if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
            error_log('MailHelper validation error: invalid recipient email address for password reset.');
            return false;
        }

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
            error_log('MailHelper configuration error: PHPMailer library files not found for password reset.');
            return false;
        }

        $requiredFiles = ['Exception.php', 'PHPMailer.php', 'SMTP.php'];

        foreach ($requiredFiles as $requiredFile) {
            $fullPath = $phpMailerBase . $requiredFile;
            if (!is_file($fullPath)) {
                error_log('MailHelper configuration error: Missing PHPMailer file ' . $fullPath);
                return false;
            }
            require_once $fullPath;
        }

        if (!class_exists('\PHPMailer\\PHPMailer\\PHPMailer')) {
            error_log('MailHelper configuration error: PHPMailer class not loaded for password reset.');
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

        $mailUsernameRaw = trim((string) ($mailConfig['username'] ?? ''));
        if ($mailUsernameRaw === '') {
            $mailUsernameRaw = trim((string) (getenv('MAIL_USERNAME') ?: ''));
        }

        $mailPasswordRaw = (string) ($mailConfig['password'] ?? '');
        if (trim($mailPasswordRaw) === '') {
            $mailPasswordRaw = (string) (getenv('MAIL_PASSWORD') ?: '');
        }

        $mailHostRaw = trim((string) ($mailConfig['host'] ?? ''));
        if ($mailHostRaw === '') {
            $mailHostRaw = trim((string) (getenv('MAIL_HOST') ?: 'smtp.gmail.com'));
        }

        $mailPortRaw = (int) ($mailConfig['port'] ?? 0);
        if ($mailPortRaw <= 0) {
            $mailPortRaw = (int) (getenv('MAIL_PORT') ?: 587);
        }

        $mailFromRaw = trim((string) ($mailConfig['from_email'] ?? ''));
        if ($mailFromRaw === '') {
            $mailFromRaw = trim((string) (getenv('MAIL_FROM_EMAIL') ?: $mailUsernameRaw));
        }

        $mailFromNameRaw = trim((string) ($mailConfig['from_name'] ?? ''));
        if ($mailFromNameRaw === '') {
            $mailFromNameRaw = trim((string) (getenv('MAIL_FROM_NAME') ?: 'TaskFlow Pro Admin'));
        }

        $mailUsername = $mailUsernameRaw;
        $mailPassword = preg_replace('/\s+/', '', $mailPasswordRaw);
        $mailHost = $mailHostRaw;
        $mailPort = $mailPortRaw;
        $mailFrom = $mailFromRaw;
        $mailFromName = $mailFromNameRaw;

        if ($mailUsername === '' || $mailPassword === '' || $mailFrom === '') {
            error_log('MailHelper configuration error: username/password/from_email is missing for password reset.');
            return false;
        }

        $safeName = htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8');
        $safeResetUrl = htmlspecialchars($resetUrl, ENT_QUOTES, 'UTF-8');

        $mail = null;
        try {
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = $mailHost;
            $mail->SMTPAuth = true;
            $mail->Username = $mailUsername;
            $mail->Password = $mailPassword;
            $mail->SMTPSecure = $mailPort === 465
                ? \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS
                : \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = $mailPort;
            $mail->SMTPDebug = 0;
            $mail->Timeout = 20;

            $mail->setFrom($mailFrom, $mailFromName);
            $mail->addAddress($toEmail, $fullName);

            $mail->isHTML(true);
            $mail->Subject = 'TaskFlow Pro Password Reset Request';
            $mail->Body = "<h3>Hello {$safeName},</h3>"
                . '<p>We received a request to reset your TaskFlow Pro password.</p>'
                . '<p>Click the button below to continue:</p>'
                . '<p><a href="' . $safeResetUrl . '" style="display:inline-block;padding:10px 16px;background:#0d6efd;color:#ffffff;text-decoration:none;border-radius:6px;">Reset Password</a></p>'
                . '<p>If the button does not work, use this link:</p>'
                . '<p><a href="' . $safeResetUrl . '">' . $safeResetUrl . '</a></p>'
                . '<p>This link expires in 15 minutes and can be used only once.</p>'
                . '<p>If you did not request this, you can ignore this email.</p>';

            $mail->send();
            return true;
        } catch (\Exception $exception) {
            $mailerError = $mail instanceof \PHPMailer\PHPMailer\PHPMailer ? $mail->ErrorInfo : '';
            error_log('PHPMailer reset mail error: ' . $exception->getMessage() . ($mailerError !== '' ? ' | MailerError: ' . $mailerError : ''));
            return false;
        }
    }
}
