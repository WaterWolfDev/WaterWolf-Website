<?php

namespace App\Controller\Account;

use App\Http\Response;
use App\Http\ServerRequest;
use App\Service\Discord;
use App\Service\VrcApi;
use Doctrine\DBAL\Connection;
use Psr\Http\Message\ResponseInterface;

final readonly class RegisterAction
{
    public function __construct(
        private Connection $db,
        private Discord $discord,
        private VrcApi $vrcApi
    ) {
    }

    public function __invoke(ServerRequest $request, Response $response): ResponseInterface
    {
        if ($request->isLoggedIn()) {
            return $response->withRedirect(
                $request->getRouter()->urlFor('dashboard')
            );
        }

        $error = null;
        $postData = $request->getParams();

        if ($request->isPost()) {
            try {
                $row = [
                    'username' => str_replace(['#', '&', '�'], '', $postData['reg_username'] ?? ''),
                    'email' => filter_var($postData['reg_email'] ?? '', FILTER_SANITIZE_EMAIL),
                    'country' => $postData['reg_country'] ?? null,
                    'reg_date' => time(),
                    'lastip' => $request->getIp(),
                    'discord' => $postData['discord_username'] ?? null,
                    'ref' => $postData['ref'] ?? null,
                ];

                if (empty($row['username']) || empty($row['email'])) {
                    throw new \Exception('Nickname and e-mail cannot be left blank.');
                }

                $regPass = str_replace(' ', '', $postData['reg_pass'] ?? '');
                $regPassConfirm = str_replace(' ', '', $postData['reg_pass_confirm'] ?? '');

                if ($regPass !== $regPassConfirm) {
                    throw new \Exception('The password and confirm password boxes do not match. Please try again.');
                }

                $row['password'] = password_hash($regPass, PASSWORD_ARGON2ID);

                $checkUserOrEmail = $this->db->fetchOne(
                    <<<'SQL'
                        SELECT username
                        FROM web_users
                        WHERE LOWER(username) = LOWER(:username) OR LOWER(email) = LOWER(:email)
                    SQL,
                    [
                        'username' => $row['username'],
                        'email' => $row['email'],
                    ]
                );

                if ($checkUserOrEmail !== false) {
                    throw new \Exception('Username or e-mail address already registered.');
                }

                $row['vrchat_uid'] = VrcApi::parseUserId($postData['vrchat_uid'] ?? '');
                $row['vrchat'] = $this->vrcApi->getDisplayNameFromUid($row['vrchat_uid']);
                $row['vrchat_synced_at'] = time();

                $this->db->insert('web_users', $row);

                $newUserId = $this->db->lastInsertId();

                $session = $request->getSession();
                $session->set('valid_id', $newUserId);
                $session->set('valid_time', time());
                $session->regenerate();

                $this->discord->sendMessage(
                    $_ENV['DISCORD_WEBHOOK_URL'],
                    '',
                    hexdec('00FF00'),
                    'New User Created on WaterWolf Website',
                    sprintf('User %s has created a new account.', $row['username']),
                    (string)$request->getUri()->withPath('/static/img/waterwolf_community.png')
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
            'account/register',
            [
                'error' => $error,
                'data' => $postData,
            ]
        );
    }
}
