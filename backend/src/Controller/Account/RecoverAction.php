<?php

namespace App\Controller\Account;

use App\Http\Response;
use App\Http\ServerRequest;
use Doctrine\DBAL\Connection;
use Psr\Http\Message\ResponseInterface;

final readonly class RecoverAction
{
    public function __construct(
        private Connection $db
    ) {
    }

    public function __invoke(ServerRequest $request, Response $response): ResponseInterface
    {
        if ($request->isLoggedIn()) {
            return $response->withRedirect(
                $request->getRouter()->urlFor('dashboard')
            );
        }

        $token = $request->getParam('token');
        if (empty($token)) {
            throw new \InvalidArgumentException('No token provided!');
        }

        // Look up token
        [$tokenId, $tokenVerifier] = explode(':', $token);

        $threshold = new \DateTimeImmutable('-1 day', new \DateTimeZone('UTC'));
        $tokenRow = $this->db->fetchAssociative(
            <<<'SQL'
                SELECT verifier, creator, created_at
                FROM web_user_login_tokens
                WHERE id=:id
                AND created_at > :threshold
            SQL,
            [
                'id' => $tokenId,
                'threshold' => $threshold->format('Y-m-d h:i:s'),
            ]
        );

        if ($tokenRow === false) {
            throw new \InvalidArgumentException('Invalid token!');
        }

        if (
            !hash_equals(
                $tokenRow['verifier'],
                hash('sha512', $tokenVerifier)
            )
        ) {
            throw new \InvalidArgumentException('Invalid token!');
        }

        $error = null;

        if ($request->isPost()) {
            try {
                $postParams = $request->getParsedBody();
                $newPassword = $postParams['new_password'] ?? null;
                $newPasswordConfirm = $postParams['new_password_confirm'] ?? null;

                if (empty($newPassword) || empty($newPasswordConfirm)) {
                    throw new \InvalidArgumentException('Please provide all required fields.');
                }

                if ($newPassword !== $newPasswordConfirm) {
                    throw new \InvalidArgumentException('New password and confirmation do not match.');
                }

                $newPasswordHash = password_hash($newPassword, \PASSWORD_ARGON2ID);

                // Update the user's password.
                $this->db->update(
                    'web_users',
                    [
                        'password' => $newPasswordHash,
                    ],
                    [
                        'id' => $tokenRow['creator'],
                    ]
                );

                // Remove the now-consumed token.
                $this->db->delete(
                    'web_user_login_tokens',
                    [
                        'id' => $tokenId,
                    ]
                );

                $session = $request->getSession();
                $session->set('valid_id', $tokenRow['creator']);
                $session->set('valid_time', time());
                $session->regenerate();

                $request->getFlash()->success(
                    'Your password has been reset and you have been logged in.'
                );

                return $response->withRedirect(
                    $request->getRouter()->urlFor('dashboard')
                );
            } catch (\Throwable $e) {
                $error = $e->getMessage();
            }
        }

        return $request->getView()->renderToResponse(
            $response,
            'account/recover',
            [
                'error' => $error,
                'token' => $token,
            ]
        );
    }
}
