<?php

namespace App\Mail\Transports;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Email;

class MicrosoftGraphTransport extends AbstractTransport
{
    protected string $tenantId;
    protected string $clientId;
    protected string $clientSecret;
    protected string $fromAddress;

    public function __construct(string $tenantId, string $clientId, string $clientSecret, string $fromAddress)
    {
        parent::__construct();
        $this->tenantId = $tenantId;
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->fromAddress = $fromAddress;
    }

    protected function doSend(SentMessage $message): void
    {
        /** @var Email $email */
        $email = $message->getOriginalMessage();

        // 1. ADIM: Token Al (Lokaldeki ve sunucudaki SSL hatasına takılmamak için withoutVerifying kullanıldı)
        $tokenResponse = Http::withoutVerifying()->asForm()->post("https://login.microsoftonline.com/{$this->tenantId}/oauth2/v2.0/token", [
            'client_id'     => $this->clientId,
            'client_secret' => $this->clientSecret,
            'scope'         => 'https://graph.microsoft.com/.default',
            'grant_type'    => 'client_credentials',
        ]);

        if (!$tokenResponse->successful()) {
            Log::error('Microsoft Graph Token Alınamadı!', [
                'status' => $tokenResponse->status(),
                'response' => $tokenResponse->body(),
            ]);
            return;
        }

        $token = $tokenResponse->json('access_token');

        // 2. ADIM: Alıcıları Ayarla (Mail kime/kimlere gidiyor?)
        $toRecipients = [];
        foreach ($email->getTo() as $address) {
            $toRecipients[] = [
                'emailAddress' => [
                    'address' => $address->getAddress(),
                    'name' => $address->getName() ?: null,
                ]
            ];
        }

        // CC Alıcıları (Varsa)
        $ccRecipients = [];
        foreach ($email->getCc() as $address) {
            $ccRecipients[] = [
                'emailAddress' => [
                    'address' => $address->getAddress(),
                    'name' => $address->getName() ?: null,
                ]
            ];
        }

        // BCC Alıcıları (Varsa)
        $bccRecipients = [];
        foreach ($email->getBcc() as $address) {
            $bccRecipients[] = [
                'emailAddress' => [
                    'address' => $address->getAddress(),
                    'name' => $address->getName() ?: null,
                ]
            ];
        }

        $fromName = config('mail.from.name', 'KÖKSAN HEDEFLER SİSTEMİ');
        $fromAddresses = $email->getFrom();
        if (!empty($fromAddresses) && $fromAddresses[0]->getName()) {
            $fromName = $fromAddresses[0]->getName(); 
        }

        // 3. ADIM: İçeriği Ayarla (HTML mi yoksa düz metin mi?)
        $htmlBody = $email->getHtmlBody();
        $textBody = $email->getTextBody();
        
        $content = $htmlBody ? (is_resource($htmlBody) ? stream_get_contents($htmlBody) : $htmlBody) : (is_resource($textBody) ? stream_get_contents($textBody) : $textBody);
        $contentType = $htmlBody ? 'HTML' : 'Text';

        // 4. ADIM: Payload hazırla
        $payload = [
            'message' => [
                'subject' => $email->getSubject(),
                'body' => [
                    'contentType' => $contentType,
                    'content' => (string) $content
                ],
                'toRecipients' => $toRecipients,
                'from' => [
                    'emailAddress' => [
                        'address' => $this->fromAddress,
                        'name' => $fromName,
                    ]
                ]
            ],
            'saveToSentItems' => 'false'
        ];

        if (!empty($ccRecipients)) {
            $payload['message']['ccRecipients'] = $ccRecipients;
        }

        if (!empty($bccRecipients)) {
            $payload['message']['bccRecipients'] = $bccRecipients;
        }

        // 5. ADIM: Microsoft Graph API'ye Teslim Et!
        $response = Http::withoutVerifying()->withToken($token)
            ->post("https://graph.microsoft.com/v1.0/users/{$this->fromAddress}/sendMail", $payload);

        if (!$response->successful()) {
            Log::error('Microsoft Graph sendMail Başarısız!', [
                'status' => $response->status(),
                'response' => $response->body(),
                'to' => $toRecipients,
                'subject' => $email->getSubject(),
            ]);
        }
    }

    public function __toString(): string
    {
        return 'microsoft-graph';
    }
}

