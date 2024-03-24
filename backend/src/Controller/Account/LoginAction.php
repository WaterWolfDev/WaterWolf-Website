<?php

namespace App\Controller\Account;

use App\Http\Response;
use App\Http\ServerRequest;
use Doctrine\DBAL\Connection;
use Psr\Http\Message\ResponseInterface;

final readonly class LoginAction
{
    public function __construct(
        private Connection $db
    ) {
    }

    public function __invoke(ServerRequest $request, Response $response): ResponseInterface
    {
        $error = null;

        $session = $request->getSession();

        if (!$session->has('valid_id') && $request->isPost()) {
            try {
                $postParams = $request->getParsedBody();

                $username = $postParams['username'] ?? null;
                $userPassword = $postParams['pass'] ?? null;

                if (empty($username) || empty($userPassword)) {
                    throw new \InvalidArgumentException('Missing username or password!');
                }

                $userRow = $this->db->fetchAssociative(
                    <<<'SQL'
                        SELECT id, password, banned
                        FROM web_users
                        WHERE LOWER(username) = LOWER(:user)
                        OR LOWER(email) = LOWER(:user)
                    SQL,
                    [
                        'user' => $username,
                    ]
                );

                if ($userRow === false) {
                    throw new \InvalidArgumentException('This user does not exist!');
                }

                if ($userRow['banned'] == 1) {
                    throw new \InvalidArgumentException('You are banned and cannot log in.');
                }

                // Check legacy password.
                if (hash_equals($userRow['password'], md5($userPassword))) {
                    // Migrate to new password.
                    $newPassword = password_hash($userPassword, PASSWORD_ARGON2ID);
                    $this->db->update(
                        'web_users',
                        [
                            'password' => $newPassword,
                        ],
                        [
                            'id' => $userRow['id'],
                        ]
                    );
                } elseif (!password_verify($userPassword, $userRow['password'])) {
                    $this->db->executeQuery(
                        <<<'SQL'
                            UPDATE web_users
                            SET badpass = badpass + 1
                            WHERE id = :id
                        SQL,
                        [
                            'id' => $userRow['id'],
                        ]
                    );

                    throw new \InvalidArgumentException('Your credentials could not be validated.');
                }

                $this->db->executeQuery(
                    <<<'SQL'
                        UPDATE web_users
                        SET goodpass=goodpass + 1
                        WHERE id=:id
                    SQL,
                    [
                        'id' => $userRow['id'],
                    ]
                );

                $session->set('valid_id', $userRow['id']);
                $session->set('valid_time', time());
                $session->regenerate();

                $request->getFlash()->success('Successfully logged in! Welcome!');
            } catch (\Exception $e) {
                $error = $e->getMessage();
            }
        }

        $return = $request->getParam('return');

        // Redirect logged in users.
        if ($session->has('valid_id')) {
            if (empty($return)) {
                $return = '/dashboard';
            }

            return $response->withRedirect(
                $request->getUri()->withQuery('')->withPath($return)
            );
        }

        return $request->getView()->renderToResponse(
            $response,
            'account/login',
            [
                'error' => $error,
                'return' => $return,
            ]
        );
    }
}
