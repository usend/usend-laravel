<?php

declare(strict_types=1);

namespace Usend\Laravel;

use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\MessageConverter;
use Usend\Client;
use Usend\UsendException;

/** Sends a Symfony Mime message through the usend API (POST /emails). */
final class UsendTransport extends AbstractTransport
{
    /** Headers usend sets itself. */
    private const SKIP_HEADERS = ['from', 'to', 'cc', 'bcc', 'reply-to', 'subject', 'content-type', 'mime-version',
        'content-transfer-encoding', 'date', 'message-id'];

    public function __construct(private readonly Client $client)
    {
        parent::__construct();
    }

    protected function doSend(SentMessage $message): void
    {
        $email = MessageConverter::toEmail($message->getOriginalMessage());
        $envelope = $message->getEnvelope();

        try {
            $result = $this->client->emails->send($this->payload($email, $envelope));
        } catch (UsendException $e) {
            throw new TransportException('usend refused the message: ' . $e->getMessage(), (int) $e->getCode(), $e);
        }

        if (!empty($result['id'])) {
            $message->setMessageId((string) $result['id']);
            $email->getHeaders()->addHeader('X-Usend-Email-Id', (string) $result['id']);
        }
    }

    /** @return array<string,mixed> */
    private function payload(Email $email, Envelope $envelope): array
    {
        $payload = [
            'from' => $envelope->getSender()->toString(),
            'to' => array_map(static fn (Address $a) => $a->toString(), $email->getTo()),
            'subject' => (string) $email->getSubject(),
        ];
        // Recipients only in the envelope (Bcc) are carried as `bcc`.
        $shown = array_map(static fn (Address $a) => strtolower($a->getAddress()), [...$email->getTo(), ...$email->getCc()]);
        $bcc = array_values(array_filter(
            array_map(static fn (Address $a) => $a->getAddress(), $envelope->getRecipients()),
            static fn (string $a) => !in_array(strtolower($a), $shown, true),
        ));
        if ($email->getCc()) {
            $payload['cc'] = array_map(static fn (Address $a) => $a->toString(), $email->getCc());
        }
        if ($bcc) {
            $payload['bcc'] = $bcc;
        }
        if ($email->getReplyTo()) {
            $payload['reply_to'] = array_map(static fn (Address $a) => $a->toString(), $email->getReplyTo());
        }
        if ($email->getHtmlBody() !== null) {
            $payload['html'] = (string) $email->getHtmlBody();
        }
        if ($email->getTextBody() !== null) {
            $payload['text'] = (string) $email->getTextBody();
        }

        $headers = [];
        foreach ($email->getHeaders()->all() as $header) {
            if (!in_array(strtolower($header->getName()), self::SKIP_HEADERS, true)) {
                $headers[$header->getName()] = $header->getBodyAsString();
            }
        }
        if ($headers) {
            $payload['headers'] = $headers;
        }

        $attachments = [];
        foreach ($email->getAttachments() as $part) {
            $h = $part->getPreparedHeaders();
            $attachments[] = array_filter([
                'filename' => $h->getHeaderParameter('Content-Disposition', 'filename'),
                'content' => base64_encode($part->getBody()),
                'content_type' => $part->getMediaType() . '/' . $part->getMediaSubtype(),
                'content_id' => $h->getHeaderBody('Content-ID') ?: null,
            ], static fn ($v) => $v !== null && $v !== '');
        }
        if ($attachments) {
            $payload['attachments'] = $attachments;
        }

        return $payload;
    }

    public function __toString(): string
    {
        return 'usend';
    }
}
