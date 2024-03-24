<?php

namespace App\Controller\Account;

use App\Environment;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Service\Discord;
use Doctrine\DBAL\Connection;
use Psr\Http\Message\ResponseInterface;

final readonly class RegisterAction
{
    public function __construct(
        private Environment $environment,
        private Connection $db,
        private Discord $discord
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
                $regUsername = str_replace(['#', '&', '�'], '', $postData['reg_username'] ?? '');
                $regEmail = filter_var($postData['reg_email'] ?? '', FILTER_SANITIZE_EMAIL);

                if (empty($regUsername) || empty($regEmail)) {
                    throw new \Exception('Nickname and e-mail cannot be left blank.');
                }

                $regPass = str_replace(' ', '', $postData['reg_pass'] ?? '');
                $regPassConfirm = str_replace(' ', '', $postData['reg_pass_confirm'] ?? '');

                if ($regPass !== $regPassConfirm) {
                    throw new \Exception('The password and confirm password boxes do not match. Please try again.');
                }

                $checkUserOrEmail = $this->db->fetchOne(
                    <<<'SQL'
                        SELECT username
                        FROM web_users
                        WHERE LOWER(username) = LOWER(:username) OR LOWER(email) = LOWER(:email)
                    SQL,
                    [
                        'username' => $regUsername,
                        'email' => $regEmail,
                    ]
                );

                if ($checkUserOrEmail !== false) {
                    throw new \Exception('Username or e-mail address already registered.');
                }

                $this->db->insert(
                    'web_users',
                    [
                        'username' => $regUsername,
                        'email' => $regEmail,
                        'country' => $postData['reg_country'] ?? null,
                        'reg_date' => time(),
                        'lastip' => $request->getIp(),
                        'password' => password_hash($regPass, PASSWORD_ARGON2ID),
                        'vrchat' => $postData['vrchat_username'] ?? null,
                        'discord' => $postData['discord_username'] ?? null,
                        'ref' => $postData['ref'] ?? null,
                    ]
                );

                $newUserId = $this->db->lastInsertId();

                $session = $request->getSession();
                $session->set('valid_id', $newUserId);
                $session->set('valid_time', time());
                $session->regenerate();

                $this->discord->sendMessage(
                    $this->environment->getDiscordWebookUrl(),
                    '',
                    hexdec('00FF00'),
                    'New User Created on WaterWolf Website',
                    'User ' . $regUsername . ' Has created a new account.',
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
