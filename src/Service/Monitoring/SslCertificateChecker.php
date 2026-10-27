<?php

namespace App\Service\Monitoring;

/**
 * Reads the expiry date of the TLS certificate a server presents.
 *
 * The certificate is read, not validated: an expired or self-signed certificate must still be
 * reported, which is why peer verification is off.
 */
class SslCertificateChecker
{
    /**
     * "https://shop.example.com/path" => "shop.example.com". Only https URLs have a certificate to watch,
     * a URL without scheme is assumed to be https. Returns null for anything else.
     */
    public function hostFromUrl(string $url): ?string
    {
        $url = trim($url);
        if ($url === '') {
            return null;
        }

        if (!preg_match('#^[a-z][a-z0-9+.-]*://#i', $url)) {
            $url = 'https://' . $url;
        }

        $parts = parse_url($url);
        if (!is_array($parts) || empty($parts['host']) || strtolower($parts['scheme'] ?? '') !== 'https') {
            return null;
        }

        return strtolower($parts['host']);
    }

    /**
     * @return \DateTimeImmutable|null the "not after" date, or null when the server cannot be reached
     */
    public function getExpiry(string $host, int $port = 443, int $timeout = 5): ?\DateTimeImmutable
    {
        $context = stream_context_create(['ssl' => [
            'capture_peer_cert' => true,
            'verify_peer' => false,
            'verify_peer_name' => false,
            'SNI_enabled' => true,
            'peer_name' => $host,
        ]]);

        $client = @stream_socket_client(sprintf('ssl://%s:%d', $host, $port), $errorCode, $errorMessage, $timeout, STREAM_CLIENT_CONNECT, $context);
        if ($client === false) {
            return null;
        }

        $params = stream_context_get_params($client);
        fclose($client);

        $certificate = $params['options']['ssl']['peer_certificate'] ?? null;
        $info = $certificate ? openssl_x509_parse($certificate) : false;

        if (!is_array($info) || !isset($info['validTo_time_t'])) {
            return null;
        }

        return (new \DateTimeImmutable())->setTimestamp((int) $info['validTo_time_t']);
    }
}
