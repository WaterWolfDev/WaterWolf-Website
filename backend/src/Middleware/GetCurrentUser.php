<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Entity\User;
use App\Http\HttpFactory;
use App\Http\ServerRequest;
use Doctrine\DBAL\Connection;
use Mezzio\Session\SessionInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Inject information about the current user.
 */
final class GetCurrentUser implements MiddlewareInterface
{
    private readonly Connection $db;

    public function __construct(
        ContainerInterface $di
    ) {
        $this->db = $di->get(Connection::class);
    }

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $session = $request->getAttribute(ServerRequest::ATTR_SESSION);

        if (!($session instanceof SessionInterface)) {
            throw new \InvalidArgumentException('User check is before session init.');
        }

        if ($session->has('valid_id')) {
            $uid = $session->get('valid_id');

            $row = $this->db->fetchAssociative(
                <<<'SQL'
                    SELECT *
                    FROM web_users
                    WHERE id = :id
                SQL,
                [
                    'id' => $uid,
                ]
            );

            // Check for banned status on all page loads.
            if ($row === false || $row['banned'] === 1) {
                $session->clear();
                $session->regenerate();

                return (new HttpFactory())
                    ->createResponse(302)
                    ->withHeader('Location', '/');
            }

            $session->set('valid_time', time());

            $currentUser = new User($row);

            $this->db->update(
                'web_users',
                [
                    'lastactive' => time(),
                    'online' => '1',
                    'lastip' => $request->getAttribute(ServerRequest::ATTR_IP, 'UNKNOWN'),
                ],
                [
                    'id' => $uid,
                ]
            );
        } else {
            $currentUser = null;
        }

        $request = $request->withAttribute(ServerRequest::ATTR_CURRENT_USER, $currentUser);

        return $handler->handle($request);
    }
}
