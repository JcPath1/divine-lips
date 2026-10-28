<?php
// Sends email through the shop's own mailbox (SMTP), using PHPMailer.
// In test mode ('mail.mode' => 'log') emails are written to a file instead.

declare(strict_types=1);

use PHPMailer\PHPMailer\PHPMailer;

require_once __DIR__ . '/vendor/PHPMailer/Exception.php';
require_once __DIR__ . '/vendor/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/vendor/PHPMailer/SMTP.php';

function send_mail(string $to, string $subject, string $html, string $text, string $replyTo = '', array $attachments = []): bool
{
    if (cfg('mail.mode') === 'log') {
        $files = $attachments ? "\nAttached: " . implode(', ', array_map('basename', $attachments)) : '';
        $entry = "==== " . now() . "\nTo: $to\nSubject: $subject$files\n\n$text\n\n";
        file_put_contents((string)cfg('mail.log_file'), $entry, FILE_APPEND);
        return true;
    }

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = (string)cfg('mail.smtp_host');
        $mail->Port = (int)cfg('mail.smtp_port', 465);
        $mail->SMTPAuth = true;
        $mail->Username = (string)cfg('mail.from');
        $mail->Password = (string)cfg('mail.smtp_pass');
        $mail->SMTPSecure = $mail->Port === 465 ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
        $mail->CharSet = 'UTF-8';
        $mail->setFrom((string)cfg('mail.from'), 'Glimmer Bliss');
        $mail->addAddress($to);
        if ($replyTo !== '') {
            $mail->addReplyTo($replyTo);
        }
        $mail->Subject = $subject;
        $mail->isHTML(true);
        $mail->Body = $html;
        $mail->AltBody = $text;
        foreach ($attachments as $file) {
            $mail->addAttachment($file);
        }
        $mail->send();
        return true;
    } catch (Throwable $e) {
        // Never stop an order because an email failed - just note it in the server log.
        error_log('Glimmer Bliss mail to ' . $to . ' failed: ' . $mail->ErrorInfo);
        return false;
    }
}
