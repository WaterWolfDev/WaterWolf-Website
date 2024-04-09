<?php

declare(strict_types=1);

namespace App\Http;

use App\Environment;
use App\Exception\NotLoggedInException;
use App\Exception\PermissionDeniedException;
use App\Middleware\EnableSession;
use App\Session\Flash;
use App\View;
use Mezzio\Session\Session;
use Monolog\Level;
use Monolog\Logger;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\App;
use Slim\Exception\HttpException;
use Slim\Handlers\ErrorHandler as SlimErrorHandler;
use Throwable;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Run;

final class ErrorHandler extends SlimErrorHandler
{
    private bool $showDetailed = false;

    private Level $loggerLevel = Level::Error;

    private bool $returnJson = false;

    public function __construct(
        private readonly View $view,
        private readonly EnableSession $injectSession,
        App $app,
        Logger $logger,
    ) {
        parent::__construct($app->getCallableResolver(), $app->getResponseFactory(), $logger);
    }

    public function __invoke(
        ServerRequestInterface $request,
        Throwable $exception,
        bool $displayErrorDetails,
        bool $logErrors,
        bool $logErrorDetails
    ): ResponseInterface {
        $this->showDetailed = Environment::isDev() && !($exception instanceof HttpException);

        if ($exception instanceof HttpException) {
            $this->loggerLevel = Level::Info;
        }

        $this->returnJson = $this->shouldReturnJson($request);

        return parent::__invoke($request, $exception, $displayErrorDetails, $logErrors, $logErrorDetails);
    }

    private function shouldReturnJson(ServerRequestInterface $req): bool
    {
        $xhr = $req->getHeaderLine('X-Requested-With') === 'XMLHttpRequest';

        if ($xhr || Environment::isCli()) {
            return true;
        }

        if ($req->hasHeader('Accept')) {
            $accept = $req->getHeaderLine('Accept');
            if (false !== stripos($accept, 'application/json')) {
                return true;
            }
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    protected function writeToErrorLog(): void
    {
        $context = [
            'file' => $this->exception->getFile(),
            'line' => $this->exception->getLine(),
            'code' => $this->exception->getCode(),
        ];

        if ($this->showDetailed) {
            $context['trace'] = array_slice($this->exception->getTrace(), 0, 5);
        }

        $this->logger->log($this->loggerLevel, $this->exception->getMessage(), $context);
    }

    protected function respond(): ResponseInterface
    {
        if (!($this->request instanceof ServerRequest)) {
            return parent::respond();
        }

        /** @var Response $response */
        $response = $this->responseFactory->createResponse($this->statusCode);

        // Special handling for cURL requests.
        $ua = $this->request->getHeaderLine('User-Agent');

        if (false !== stripos($ua, 'curl')) {
            $response->getBody()->write(
                sprintf(
                    'Error: %s on %s L%s',
                    $this->exception->getMessage(),
                    $this->exception->getFile(),
                    $this->exception->getLine()
                )
            );

            return $response;
        }

        if ($this->returnJson) {
            return $response->withJson([
                'code' => $this->exception->getCode(),
                'type' => (new \ReflectionClass($this->exception))->getShortName(),
                'message' => $this->exception->getMessage(),
            ]);
        }

        // Redirect to login page for not-logged-in users.
        if ($this->exception instanceof NotLoggedInException) {
            // Inject the session for subsequent handlers.
            $sessionPersistence = $this->injectSession->getSessionPersistence();

            /** @var Session $session */
            $session = $sessionPersistence->initializeSessionFromRequest($this->request);

            $flash = new Flash($session);
            $flash->error($this->exception->getMessage());

            // Set referrer for login redirection.
            $session->set('login_referrer', $this->request->getUri()->getPath());

            return $sessionPersistence->persistSession(
                $session,
                $response->withRedirect('/login?return=' . $this->request->getUri()->getPath())
            );
        }

        // Bounce back to homepage for permission-denied users.
        if ($this->exception instanceof PermissionDeniedException) {
            // Inject the session for subsequent handlers.
            $sessionPersistence = $this->injectSession->getSessionPersistence();

            /** @var Session $session */
            $session = $sessionPersistence->initializeSessionFromRequest($this->request);

            $flash = new Flash($session);
            $flash->error($this->exception->getMessage());

            return $sessionPersistence->persistSession(
                $session,
                $response->withRedirect('/')
            );
        }

        if ($this->showDetailed && class_exists(Run::class)) {
            // Register error-handler.
            $handler = new PrettyPageHandler();
            $handler->setPageTitle('An error occurred!');

            $run = new Run();
            $run->prependHandler($handler);

            return $response->write($run->handleException($this->exception));
        }

        try {
            return $this->view->renderToResponse(
                $response,
                ($this->exception instanceof HttpException)
                    ? 'errors/http'
                    : 'errors/generic',
                [
                    'exception' => $this->exception,
                ]
            );
        } catch (Throwable $e) {
            return parent::respond();
        }
    }
}
