<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Environment;
use App\Http\ServerRequest;
use App\Session\Csrf;
use App\Session\Flash;
use Mezzio\Session\Cache\CacheSessionPersistence;
use Mezzio\Session\LazySession;
use Mezzio\Session\SessionInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\ProxyAdapter;

/**
 * Inject the session object into the request.
 */
final class EnableSession implements MiddlewareInterface
{
    private readonly CacheItemPoolInterface $cachePool;

    public function __construct(
        private readonly Environment $environment,
        CacheItemPoolInterface $psr6Cache
    ) {
        if ($this->environment->isCli()) {
            $psr6Cache = new ArrayAdapter();
        }

        $this->cachePool = new ProxyAdapter($psr6Cache, 'session.');
    }

    public function getSessionPersistence(): CacheSessionPersistence
    {
        return new CacheSessionPersistence(
            cache: $this->cachePool,
            cookieName: 'app_session',
            cookiePath: '/',
            cacheLimiter: 'nocache',
            cacheExpire: 43200,
            lastModified: time(),
            persistent: true,
            cookieSecure: $this->environment->isProduction(),
            cookieHttpOnly: true
        );
    }

    public function injectSession(
        SessionInterface $session,
        ServerRequestInterface $request
    ): ServerRequestInterface {
        $csrf = new Csrf($session);
        $flash = new Flash($session);

        return $request->withAttribute(ServerRequest::ATTR_SESSION, $session)
            ->withAttribute(ServerRequest::ATTR_SESSION_CSRF, $csrf)
            ->withAttribute(ServerRequest::ATTR_SESSION_FLASH, $flash);
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $sessionPersistence = $this->getSessionPersistence();
        $session = new LazySession($sessionPersistence, $request);

        $request = $this->injectSession($session, $request);
        $response = $handler->handle($request);

        return $sessionPersistence->persistSession($session, $response);
    }
}
