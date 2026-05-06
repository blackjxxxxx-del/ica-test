<?php
/**
 * Simple SMTP mailer — sends via Gmail SMTP with STARTTLS
 * Supports plain text and HTML emails.
 */
function sendMail(string $to, string $subject, string $body, string $replyTo = '', bool $isHtml = false): bool {
    require_once __DIR__ . '/../config.php';

    $host     = SMTP_HOST;
    $port     = SMTP_PORT;
    $user     = SMTP_USER;
    $pass     = SMTP_PASS;
    $from     = MAIL_FROM;
    $fromName = MAIL_FROM_NAME;

    try {
        $socket = fsockopen('tcp://' . $host, $port, $errno, $errstr, 15);
        if (!$socket) return false;

        fgets($socket, 512);

        $ehlo = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
        fwrite($socket, "EHLO $ehlo\r\n");
        while ($line = fgets($socket, 512)) { if (isset($line[3]) && $line[3] === ' ') break; }

        fwrite($socket, "STARTTLS\r\n");
        fgets($socket, 512);
        stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);

        fwrite($socket, "EHLO $ehlo\r\n");
        while ($line = fgets($socket, 512)) { if (isset($line[3]) && $line[3] === ' ') break; }

        fwrite($socket, "AUTH LOGIN\r\n");
        fgets($socket, 512);
        fwrite($socket, base64_encode($user) . "\r\n");
        fgets($socket, 512);
        fwrite($socket, base64_encode($pass) . "\r\n");
        $auth = fgets($socket, 512);
        if (substr($auth, 0, 3) !== '235') { fclose($socket); return false; }

        fwrite($socket, "MAIL FROM:<$from>\r\n"); fgets($socket, 512);
        fwrite($socket, "RCPT TO:<$to>\r\n");     fgets($socket, 512);
        fwrite($socket, "DATA\r\n");               fgets($socket, 512);

        $contentType = $isHtml
            ? "Content-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: base64"
            : "Content-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64";

        $headers  = "From: =?UTF-8?B?" . base64_encode($fromName) . "?= <$from>\r\n";
        $headers .= "To: $to\r\n";
        $headers .= "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n";
        if ($replyTo) $headers .= "Reply-To: $replyTo\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= $contentType . "\r\n";

        fwrite($socket, $headers . "\r\n" . chunk_split(base64_encode($body)) . "\r\n.\r\n");
        fgets($socket, 512);
        fwrite($socket, "QUIT\r\n");
        fclose($socket);
        return true;

    } catch (Exception $e) {
        error_log('Mailer error: ' . $e->getMessage());
        return false;
    }
}

/**
 * Build a styled HTML email template
 */
function buildEmailHtml(string $title, string $bodyHtml, string $footerNote = ''): string {
    return <<<HTML
<!DOCTYPE html>
<html lang="th">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="margin:0;padding:0;background:#f4f6f9;font-family:'Inter',Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6f9;padding:40px 20px;">
  <tr><td align="center">
    <table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,0.08);">
      <!-- Header -->
      <tr>
        <td style="background:linear-gradient(135deg,#1a3a5f,#274c77);padding:32px 40px;text-align:center;">
          <div style="color:#ffb400;font-size:12px;font-weight:700;letter-spacing:2px;text-transform:uppercase;margin-bottom:8px;">76TH ANNUAL ICA</div>
          <div style="color:#ffffff;font-size:22px;font-weight:800;letter-spacing:0.5px;">REGIONAL HUB THAILAND 2026</div>
          <div style="color:rgba(255,255,255,0.65);font-size:13px;margin-top:4px;">6–7 June 2026 · Chulalongkorn University · Bangkok</div>
        </td>
      </tr>
      <!-- Title bar -->
      <tr>
        <td style="background:#ffb400;padding:14px 40px;">
          <div style="color:#1a3a5f;font-size:16px;font-weight:700;">{$title}</div>
        </td>
      </tr>
      <!-- Body -->
      <tr>
        <td style="padding:32px 40px;color:#1e293b;font-size:15px;line-height:1.7;">
          {$bodyHtml}
        </td>
      </tr>
      <!-- Footer -->
      <tr>
        <td style="background:#f8fafc;padding:20px 40px;border-top:1px solid #e2e8f0;text-align:center;color:#94a3b8;font-size:12px;line-height:1.6;">
          <strong style="color:#1a3a5f;">ICA Thailand Hub 2026</strong><br>
          Faculty of Communication Arts, Chulalongkorn University, Bangkok<br>
          <a href="https://icahubthailand.org" style="color:#274c77;">icahubthailand.org</a>
          {$footerNote}
        </td>
      </tr>
    </table>
  </td></tr>
</table>
</body></html>
HTML;
}
