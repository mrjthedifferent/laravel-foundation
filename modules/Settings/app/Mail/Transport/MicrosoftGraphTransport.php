<?php

namespace Modules\Settings\Mail\Transport;

use Illuminate\Support\Facades\Http;
use Modules\Settings\Services\MicrosoftOAuthTokenService;
use Symfony\Component\Mailer\Exception\HttpTransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;

/**
 * Sends mail through the Microsoft Graph "sendMail" API using an app-only OAuth
 * token, so it works under tenants where Security Defaults block SMTP basic auth
 * and without the Exchange service-principal registration that XOAUTH2 SMTP
 * needs.
 *
 * The whole MIME message is base64-encoded and posted to Graph, which preserves
 * the HTML body, headers, and attachments Laravel produced instead of us
 * rebuilding them as a JSON message object.
 *
 * Requires an Entra app with the Microsoft Graph "Mail.Send" application
 * permission (admin consented). No PowerShell is required.
 *
 * @see https://learn.microsoft.com/en-us/graph/api/user-sendmail
 */
final class MicrosoftGraphTransport extends AbstractTransport
{
    private const SEND_MAIL_URL = 'https://graph.microsoft.com/v1.0/users/%s/sendMail';

    private readonly string $tenantId;

    private readonly string $clientId;

    private readonly string $clientSecret;

    private readonly string $mailbox;

    /**
     * @param  array<string, mixed>  $config  the resolved mail.mailers.microsoft_graph config
     */
    public function __construct(
        private readonly MicrosoftOAuthTokenService $tokens,
        array $config,
    ) {
        parent::__construct();

        $this->tenantId = (string) ($config['tenant_id'] ?? '');
        $this->clientId = (string) ($config['client_id'] ?? '');
        $this->clientSecret = (string) ($config['client_secret'] ?? '');
        $this->mailbox = (string) ($config['mailbox'] ?? $config['username'] ?? '');
    }

    protected function doSend(SentMessage $message): void
    {
        $token = $this->tokens->accessToken(
            $this->tenantId,
            $this->clientId,
            $this->clientSecret,
            MicrosoftOAuthTokenService::GRAPH_SCOPE,
        );

        $response = Http::withToken($token)
            ->withBody(base64_encode($message->toString()), 'text/plain')
            ->timeout(30)
            ->post(sprintf(self::SEND_MAIL_URL, rawurlencode($this->mailbox)));

        if ($response->failed()) {
            throw new HttpTransportException(
                'Microsoft Graph rejected the message: '.$this->errorMessage($response->json(), $response->status()),
                $response->toPsrResponse(),
            );
        }
    }

    /**
     * Surface Graph's own error text so a misconfiguration (ErrorAccessDenied =
     * missing Mail.Send consent, MailboxNotEnabledForRESTAPI = wrong mailbox) is
     * diagnosable from the failed job.
     *
     * @param  array<string, mixed>|null  $body
     */
    private function errorMessage(?array $body, int $status): string
    {
        $message = $body['error']['message'] ?? null;
        $code = $body['error']['code'] ?? null;

        if (is_string($message) && $message !== '') {
            return is_string($code) && $code !== '' ? "{$code}: {$message}" : $message;
        }

        return "HTTP {$status}.";
    }

    /**
     * Overridden so the bearer token never reaches logs or exception messages.
     */
    public function __toString(): string
    {
        return 'microsoft-graph://'.$this->mailbox;
    }
}
