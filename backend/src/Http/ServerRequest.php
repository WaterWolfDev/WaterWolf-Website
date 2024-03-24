<?php

declare(strict_types=1);

namespace App\Http;

use App\Entity\User;
use App\Session\Csrf;
use App\Session\Flash;
use App\View;
use InvalidArgumentException;
use Mezzio\Session\SessionInterface;
use Slim\Interfaces\RouteInterface;
use Slim\Interfaces\RouteParserInterface;
use Slim\Routing\RouteContext;

final class ServerRequest extends \Slim\Http\ServerRequest
{
    public const ATTR_IP = 'app_ip';
    public const ATTR_VIEW = 'app_view';
    public const ATTR_SESSION = 'app_session';
    public const ATTR_SESSION_CSRF = 'app_session_csrf';
    public const ATTR_SESSION_FLASH = 'app_session_flash';
    public const ATTR_CURRENT_USER = 'app_current_user';

    public function getIp(): string
    {
        return $this->getAttribute(self::ATTR_IP, 'UNKNOWN');
    }

    public function getView(): View
    {
        return $this->getAttributeOfClass(self::ATTR_VIEW, View::class);
    }

    public function getSession(): SessionInterface
    {
        return $this->getAttributeOfClass(self::ATTR_SESSION, SessionInterface::class);
    }

    public function getCsrf(): Csrf
    {
        return $this->getAttributeOfClass(self::ATTR_SESSION_CSRF, Csrf::class);
    }

    public function getFlash(): Flash
    {
        return $this->getAttributeOfClass(self::ATTR_SESSION_FLASH, Flash::class);
    }

    public function getRouter(): RouteParserInterface
    {
        return $this->getAttributeOfClass(RouteContext::ROUTE_PARSER, RouteParserInterface::class);
    }

    public function getRoute(): ?RouteInterface
    {
        return $this->getAttribute(RouteContext::ROUTE);
    }

    public function getCurrentUser(): ?User
    {
        return $this->getAttribute(self::ATTR_CURRENT_USER);
    }

    public function isLoggedIn(): bool
    {
        $currentUser = $this->getCurrentUser();
        return null !== $currentUser;
    }

    /**
     * @param string $attr
     * @param string $class_name
     *
     * @throws InvalidArgumentException
     */
    private function getAttributeOfClass(string $attr, string $class_name): mixed
    {
        $object = $this->serverRequest->getAttribute($attr);

        if (empty($object)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Attribute "%s" is required and is empty in this request',
                    $attr
                )
            );
        }

        if (!($object instanceof $class_name)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Attribute "%s" must be of type "%s".',
                    $attr,
                    $class_name
                )
            );
        }

        return $object;
    }
}
