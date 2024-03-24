<?php

namespace App\Controller\Dashboard;

use App\Http\Response;
use App\Http\ServerRequest;
use Doctrine\DBAL\Connection;
use Psr\Http\Message\ResponseInterface;

final readonly class PasswordAction
{
    public function __construct(
        private Connection $db
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $currentUser = $request->getCurrentUser();
        assert($currentUser !== null);

        $error = null;

        if ($request->isPost()) {
            try {
                $postData = $request->getParsedBody();

                $currentPassword = $postData['current_password'] ?? null;
                $newPassword = $postData['new_password'] ?? null;
                $newPasswordConfirm = $postData['new_password_confirm'] ?? null;

                if (empty($currentPassword) || empty($newPassword) || empty($newPasswordConfirm)) {
                    throw new \InvalidArgumentException('Please provide all required fields.');
                }

                if ($newPassword !== $newPasswordConfirm) {
                    throw new \InvalidArgumentException('New password and confirmation do not match.');
                }

                if (!password_verify($currentPassword, $currentUser['password'])) {
                    throw new \InvalidArgumentException('Current password is not valid.');
                }

                $newPasswordHash = password_hash($newPassword, \PASSWORD_ARGON2ID);

                $this->db->update(
                    'web_users',
                    [
                        'password' => $newPasswordHash,
                    ],
                    [
                        'id' => $currentUser['id'],
                    ]
                );

                $session = $request->getSession();
                $session->clear();
                $session->regenerate();

                $request->getFlash()->success('Password reset! Please log in again.');

                return $response->withRedirect(
                    $request->getRouter()->urlFor('login')
                );
            } catch (\Throwable $e) {
                $error = $e->getMessage();
            }
        }

        return $request->getView()->renderToResponse(
            $response,
            'dashboard/password',
            [
                'error' => $error,
            ]
        );
    }
}
