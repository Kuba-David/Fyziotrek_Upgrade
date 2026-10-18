<?php
declare(strict_types=1);

/**
 * Send a plain-text e-mail through PHP mail(), which works out of the box on WEDOS.
 * The sender must be an address on the site's own domain (config mail.from).
 */
function send_mail(string|array $to, string $subject, string $body, ?string $replyTo = null): bool
{
    $recipients = array_filter(array_map('trim', is_array($to) ? $to : explode(',', $to)), 'valid_email');
    if (!$recipients) {
        return false;
    }

    $subject = (string) preg_replace('~[\r\n]+~', ' ', $subject);
    $from = (string) config('mail.from', 'info@fyziotrek.cz');
    $fromName = (string) config('mail.from_name', 'Fyziotrek');
    $headers = [
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8',
        'Content-Transfer-Encoding: 8bit',
        'From: ' . mb_encode_mimeheader($fromName, 'UTF-8', 'Q') . ' <' . $from . '>',
    ];
    if ($replyTo && valid_email($replyTo)) {
        $headers[] = 'Reply-To: ' . $replyTo;
    }

    $log = ROOT_DIR . '/data/mail.log';
    if (config('mail.log_only')) {
        // Development: write e-mails to a file instead of sending them.
        file_put_contents($log, "To: " . implode(', ', $recipients) . "\nSubject: $subject\n" . implode("\n", $headers) . "\n\n$body\n-----\n", FILE_APPEND);
        return true;
    }

    $ok = true;
    foreach ($recipients as $recipient) {
        $sent = mail(
            $recipient,
            mb_encode_mimeheader($subject, 'UTF-8', 'Q'),
            $body,
            implode("\r\n", $headers),
            '-f' . $from
        );
        if (!$sent) {
            $ok = false;
            error_log("Fyziotrek: odeslání e-mailu na $recipient selhalo ($subject)");
        }
    }
    return $ok;
}
