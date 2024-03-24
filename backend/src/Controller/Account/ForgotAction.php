<?php

namespace App\Controller\Account;

use App\Http\Response;
use App\Http\ServerRequest;
use Doctrine\DBAL\Connection;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

final readonly class ForgotAction
{
    public function __construct(
        private Connection $db,
        private Mailer $mailer
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

        if ($request->isPost()) {
            try {
                $email = $request->getParam('email');
                if (empty($email)) {
                    throw new \InvalidArgumentException('Must provide e-mail address.');
                }

                $userId = $this->db->fetchOne(
                    <<<'SQL'
                        SELECT id
                        FROM web_users
                        WHERE LOWER(email) = LOWER(:email)
                    SQL,
                    [
                        'email' => $email,
                    ]
                );

                if ($userId === false) {
                    throw new \InvalidArgumentException('E-mail address not found!');
                }

                /*
                 * Create a new "split-token" key for password reset
                 * per ParagonIE's PHP security recommendations:
                 * https://paragonie.com/blog/2017/02/split-tokens-token-based-authentication-protocols-without-side-channels
                 */

                $randomStr = hash('sha256', random_bytes(32));
                $tokenIdentifier = substr($randomStr, 0, 16);
                $tokenVerifier = substr($randomStr, 16, 32);

                $this->db->insert(
                    'web_user_login_tokens',
                    [
                        'id' => $tokenIdentifier,
                        'verifier' => hash('sha512', $tokenVerifier),
                        'creator' => $userId,
                    ]
                );

                $token = $tokenIdentifier . ':' . $tokenVerifier;

                $recoverUrl = $request->getRouter()->fullUrlFor(
                    $request->getUri(),
                    'recover',
                    queryParams: [
                        'token' => $token,
                    ]
                );

                // Send e-mail
                $mailBody = $request->getView()->render(
                    'emails/forgot',
                    [
                        'url' => $recoverUrl,
                    ]
                );

                $email = (new Email())
                    ->from(new Address('noreply@mail.waterwolf.club', 'WaterWolf Community'))
                    ->subject('Recover your WaterWolf Account')
                    ->to($email)
                    ->text($mailBody);

                $this->mailer->send($email);

                $request->getFlash()->success(
                    'Recovery code successfully sent! Check your inbox for instructions. If you don\'t see a message, check your "Spam" folder.'
                );
                return $response->withRedirect('/');
            } catch (\Throwable $e) {
                $error = $e->getMessage();
            }
        }

        return $request->getView()->renderToResponse(
            $response,
            'account/forgot',
            [
                'error' => $error,
            ]
        );
    }
}
