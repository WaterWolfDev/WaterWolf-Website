<?php

namespace App\Service\VrcApi;

use App\Environment;
use App\Service\VrcApi;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Cookie\FileCookieJar;
use GuzzleHttp\Cookie\SetCookie;
use OTPHP\TOTP;
use Psr\Http\Message\RequestInterface;

final readonly class AuthMiddleware
{
    public const string VRC_AUTH_COOKIE_NAME = 'auth';
    public const string VRC_TOTP_COOKIE_NAME = 'twoFactorAuth';

    private string|null $username;
    private string|null $password;
    private string|null $totp;

    private CookieJar $cookieJar;

    public function __construct(
        private Client $httpClient
    ) {
        $this->username = $_ENV['VRCHAT_USERNAME'] ?? null;
        $this->password = $_ENV['VRCHAT_PASSWORD'] ?? null;
        $this->totp = $_ENV['VRCHAT_TOTP'] ?? null;

        $this->cookieJar = new FileCookieJar(Environment::getTempDirectory() . '/vrcapi_cookies');
    }

    public function __invoke(callable $next): \Closure
    {
        return function (RequestInterface $request, array $options = []) use (&$next) {
            $request = $this->applyAuth($request);
            return $next($request, $options);
        };
    }

    protected function applyAuth(RequestInterface $request): RequestInterface
    {
        if (!$this->hasValidCookies()) {
            $this->reAuth();
        }

        return $this->cookieJar->withCookieHeader($request);
    }

    private function reAuth(): void
    {
        $options = [
            'base_uri' => VrcApi::VRCAPI_BASE_URL,
            'cookies' => $this->cookieJar,
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode(
                    urlencode($this->username) . ':' . urlencode($this->password)
                ),
            ],
        ];

        if (!empty($this->totp)) {
            $totp = TOTP::createFromSecret(str_replace(' ', '', strtoupper($this->totp)));
            $this->httpClient->post(
                'auth/twofactorauth/totp/verify',
                [
                    ...$options,
                    'json' => [
                        'code' => $totp->now(),
                    ],
                ]
            );
        } else {
            $this->httpClient->get(
                'auth/user',
                $options
            );
        }
    }

    private function hasValidCookies(): bool
    {
        if ($this->cookieJar->count() === 0) {
            return false;
        }

        $cookieNames = [];

        /** @var SetCookie $cookie */
        foreach ($this->cookieJar->getIterator() as $cookie) {
            if (!$cookie->isExpired()) {
                $cookieNames[$cookie->getName()] = $cookie;
            }
        }

        if (!isset($cookieNames[self::VRC_AUTH_COOKIE_NAME])) {
            return false;
        }

        if (!empty($this->totp) && !isset($cookieNames[self::VRC_TOTP_COOKIE_NAME])) {
            return false;
        }

        return true;
    }
}
