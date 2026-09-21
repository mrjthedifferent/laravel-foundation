<?php

namespace Modules\Settings\Mail\Transport;

use Modules\Settings\Services\MicrosoftOAuthTokenService;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\Smtp\Auth\XOAuth2Authenticator;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use Symfony\Component\Mime\RawMessage;

/**
 * SMTP transport that authenticates against Exchange Online with SASL XOAUTH2
 * instead of a mailbox password.
 *
 * The access token is minted by {@see MicrosoftOAuthTokenService} and passed as
 * the SMTP password, which is what Symfony's XOAuth2Authenticator encodes into
 * "user=<mailbox>\1auth=Bearer <token>\1\1".
 */
final class MicrosoftOAuthTransport extends EsmtpTransport
{
    private readonly string $tenantId;

    private readonly string $clientId;

    private readonly string $clientSecret;

    /**
     * @param  array<string, mixed>  $config  the resolved mail.mailers.microsoft_oauth config
     */
    public function __construct(
        private readonly MicrosoftOAuthTokenService $tokens,
        array $config,
    ) {
        $host = (string) ($config['host'] ?? 'smtp.office365.com');
        $port = (int) ($config['port'] ?? 587);

        // null lets Symfony negotiate STARTTLS on 587; 465 is implicit TLS.
        parent::__construct($host, $port, $port === 465 ? true : null, null, null, null, [new XOAuth2Authenticator]);

        $this->tenantId = (string) ($config['tenant_id'] ?? '');
        $this->clientId = (string) ($config['client_id'] ?? '');
        $this->clientSecret = (string) ($config['client_secret'] ?? '');

        $this->setUsername((string) ($config['mailbox'] ?? $config['username'] ?? ''));
    }

    public function send(RawMessage $message, ?Envelope $envelope = null): ?SentMessage
    {
        $token = $this->tokens->accessToken($this->tenantId, $this->clientId, $this->clientSecret);

        // A long-lived queue worker keeps one resolved mailer for the life of the
        // process, so an open connection would otherwise stay authenticated with
        // an expired token. Drop it and let the parent reconnect and re-auth.
        if ($token !== $this->getPassword()) {
            $this->stop();
            $this->setPassword($token);
        }

        return parent::send($message, $envelope);
    }

    /**
     * Overridden so the bearer token never reaches logs or exception messages —
     * the parent renders the password into its DSN string.
     */
    public function __toString(): string
    {
        return 'microsoft-oauth://'.$this->getUsername();
    }
}
