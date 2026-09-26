<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

/**
 * Sends plain-text/HTML email via either PHP's built-in mail() (works
 * out of the box on most shared hosting, no configuration) or a small
 * dependency-free SMTP client (for hosts that block mail() or when a
 * transactional provider is preferred).
 *
 * No Composer dependency (e.g. PHPMailer/Symfony Mailer) — this project
 * ships with no build step.
 */
final class Mailer
{
    public static function send(string $toEmail, string $subject, string $textBody, string $htmlBody): bool
    {
        $config = Config::get('mail');

        if ($config['driver'] === 'smtp' && !empty($config['smtp_host'])) {
            return self::sendSmtp($config, $toEmail, $subject, $textBody, $htmlBody);
        }

        return self::sendPhpMail($config, $toEmail, $subject, $textBody, $htmlBody);
    }

    private static function sendPhpMail(array $config, string $to, string $subject, string $text, string $html): bool
    {
        $boundary = 'hj-' . bin2hex(random_bytes(8));
        $headers = [
            'From: ' . self::formatAddress($config['from_name'], $config['from_address']),
            'MIME-Version: 1.0',
            'Content-Type: multipart/alternative; boundary="' . $boundary . '"',
        ];

        $body = "--{$boundary}\r\nContent-Type: text/plain; charset=UTF-8\r\n\r\n{$text}\r\n"
            . "--{$boundary}\r\nContent-Type: text/html; charset=UTF-8\r\n\r\n{$html}\r\n--{$boundary}--";

        return mail($to, self::encodeSubject($subject), $body, implode("\r\n", $headers));
    }

    private static function sendSmtp(array $config, string $to, string $subject, string $text, string $html): bool
    {
        $host = $config['smtp_host'];
        $port = (int) $config['smtp_port'];
        $secure = $config['smtp_secure'];

        $transport = $secure === 'ssl' ? 'ssl://' . $host : $host;
        $socket = @stream_socket_client($transport . ':' . $port, $errno, $errstr, 15);
        if ($socket === false) {
            throw new RuntimeException("SMTP connection failed: {$errstr}");
        }

        $read = static function () use ($socket): string {
            $data = '';
            while (($line = fgets($socket, 515)) !== false) {
                $data .= $line;
                if (isset($line[3]) && $line[3] === ' ') {
                    break;
                }
            }
            return $data;
        };

        $write = static function (string $command) use ($socket): void {
            fwrite($socket, $command . "\r\n");
        };

        $expect = static function (string $response, string $code) {
            if (!str_starts_with($response, $code)) {
                throw new RuntimeException("SMTP error, expected {$code}: {$response}");
            }
        };

        $read(); // greeting
        $write('EHLO localhost');
        $expect($read(), '250');

        if ($secure === 'tls') {
            $write('STARTTLS');
            $expect($read(), '220');
            stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            $write('EHLO localhost');
            $expect($read(), '250');
        }

        if (!empty($config['smtp_user'])) {
            $write('AUTH LOGIN');
            $expect($read(), '334');
            $write(base64_encode($config['smtp_user']));
            $expect($read(), '334');
            $write(base64_encode($config['smtp_pass']));
            $expect($read(), '235');
        }

        $write('MAIL FROM:<' . $config['from_address'] . '>');
        $expect($read(), '250');
        $write('RCPT TO:<' . $to . '>');
        $expect($read(), '250');
        $write('DATA');
        $expect($read(), '354');

        $boundary = 'hj-' . bin2hex(random_bytes(8));
        $message = 'From: ' . self::formatAddress($config['from_name'], $config['from_address']) . "\r\n"
            . 'To: <' . $to . ">\r\n"
            . 'Subject: ' . self::encodeSubject($subject) . "\r\n"
            . "MIME-Version: 1.0\r\n"
            . "Content-Type: multipart/alternative; boundary=\"{$boundary}\"\r\n\r\n"
            . "--{$boundary}\r\nContent-Type: text/plain; charset=UTF-8\r\n\r\n{$text}\r\n"
            . "--{$boundary}\r\nContent-Type: text/html; charset=UTF-8\r\n\r\n{$html}\r\n--{$boundary}--\r\n.";

        $write($message);
        $expect($read(), '250');
        $write('QUIT');
        fclose($socket);

        return true;
    }

    private static function formatAddress(string $name, string $address): string
    {
        return '=?UTF-8?B?' . base64_encode($name) . '?= <' . $address . '>';
    }

    private static function encodeSubject(string $subject): string
    {
        return '=?UTF-8?B?' . base64_encode($subject) . '?=';
    }
}
