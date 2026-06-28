<?php

namespace App\Services\EmailService;

use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Throwable;

class EmailService
{
    private const MAILERS = [
        'smtp_primary',
        'smtp_secondary',
        'smtp_tertiary',
    ];

    public function send(
        string|array $to,
        string $subject,
        string $body,
        array $options = [],
    ): void {
        $lastException = null;

        $recipients = $this->normalizeRecipients($to);
        $options = $this->normalizeOptions($options);

        foreach (self::MAILERS as $mailer) {
            try {
                $this->sendViaMailer(
                    mailer: $mailer,
                    recipients: $recipients,
                    subject: $subject,
                    body: $body,
                    options: $options,
                );

                Log::info('[EmailService] Mail sent successfully', [
                    'mailer' => $mailer,
                    'to' => array_keys($recipients),
                    'subject' => $subject,
                ]);

                return;
            } catch (Throwable $e) {
                $lastException = $e;

                Log::warning('[EmailService] Mailer failed, trying next one', [
                    'mailer' => $mailer,
                    'to' => array_keys($recipients),
                    'subject' => $subject,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::error('[EmailService] All configured mailers failed', [
            'mailers' => self::MAILERS,
            'to' => array_keys($recipients),
            'subject' => $subject,
            'error' => $lastException?->getMessage(),
        ]);

        throw new RuntimeException(
            'Mail could not be sent. All SMTP hosts failed. Last error: ' . ($lastException?->getMessage() ?? 'Unknown error'),
            previous: $lastException,
        );
    }

    public function sendSilently(
        string|array $to,
        string $subject,
        string $body,
        array $options = [],
    ): bool {
        try {
            $this->send($to, $subject, $body, $options);
            return true;
        } catch (Throwable $e) {
            Log::notice('[EmailService] Silent send failed', [
                'to' => is_array($to) ? $to : [$to],
                'subject' => $subject,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    private function sendViaMailer(
        string $mailer,
        array $recipients,
        string $subject,
        string $body,
        array $options,
    ): void {
        Mail::mailer($mailer)->send([], [], function (Message $message) use ($recipients, $subject, $body, $options): void {
            $this->applyRecipients($message, $recipients);

            $message->subject($subject);
            $message->html($body);

            foreach ($options['cc'] as $address => $name) {
                $message->cc($address, $name);
            }

            foreach ($options['bcc'] as $address => $name) {
                $message->bcc($address, $name);
            }

            foreach ($options['replyTo'] as $address => $name) {
                $message->replyTo($address, $name);
            }

            foreach ($options['attachments'] as $attachment) {
                if (!isset($attachment['path'])) {
                    continue;
                }

                $message->attach(
                    $attachment['path'],
                    array_filter([
                        'as' => $attachment['name'] ?? null,
                        'mime' => $attachment['mime'] ?? null,
                    ])
                );
            }
        });
    }

    private function normalizeRecipients(string|array $recipients): array
    {
        if (is_string($recipients)) {
            return [$recipients => null];
        }

        $normalized = [];

        foreach ($recipients as $key => $value) {
            if (is_int($key)) {
                $normalized[$value] = null;
            } else {
                $normalized[$key] = $value;
            }
        }

        return $normalized;
    }

    private function normalizeOptions(array $options): array
    {
        return [
            'cc' => $this->normalizeAddressList($options['cc'] ?? []),
            'bcc' => $this->normalizeAddressList($options['bcc'] ?? []),
            'replyTo' => $this->normalizeAddressList(
                is_string($options['replyTo'] ?? null)
                    ? [$options['replyTo']]
                    : ($options['replyTo'] ?? [])
            ),
            'attachments' => is_array($options['attachments'] ?? null) ? $options['attachments'] : [],
        ];
    }

    private function normalizeAddressList(array $addresses): array
    {
        $normalized = [];

        foreach ($addresses as $key => $value) {
            if (is_int($key)) {
                $normalized[$value] = null;
            } else {
                $normalized[$key] = $value;
            }
        }

        return $normalized;
    }

    private function applyRecipients(Message $message, array $recipients): void
    {
        foreach ($recipients as $email => $name) {
            $message->to($email, $name);
        }
    }
}
