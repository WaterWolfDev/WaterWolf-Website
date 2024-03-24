<?php

use App\Http\Response;
use App\Http\ServerRequest;
use App\View;
use Psr\Http\Message\ResponseInterface;
use Slim\Routing\RouteCollectorProxy;

return function (Slim\App $app) {
    /*
     * View-enabled, user-enabled routes
     */
    $app->group('', function (RouteCollectorProxy $group) {
        $group->get('/', View::staticPage('index'))
            ->setName('home');

        $group->get('/about', View::staticPage('about'))
            ->setName('about');

        $group->get('/calendar', View::staticPage('calendar'))
            ->setName('calendar');

        $group->group('/dashboard', function (RouteCollectorProxy $group) {
            $group->get('', View::staticPage('dashboard/index'))
                ->setName('dashboard');

            $group->group('/admin', function (RouteCollectorProxy $group) {
                $group->map(
                    ['GET', 'POST'],
                    '/add_world',
                    App\Controller\Dashboard\Admin\AddWorldAction::class
                )->setName('dashboard:admin:add_world');

                $group->get(
                    '/users',
                    App\Controller\Dashboard\Admin\UsersAction::class
                )->setName('dashboard:admin:users');
            })->add(new App\Middleware\Auth\RequireAdmin());

            $group->map(
                ['GET', 'POST'],
                '/avatar[/{type}]',
                App\Controller\Dashboard\AvatarAction::class
            )->setName('dashboard:avatar');

            $group->map(
                ['GET', 'POST'],
                '/dmx',
                App\Controller\Dashboard\DmxController::class
            )->setName('dashboard:dmx');

            $group->map(
                ['GET', 'POST'],
                '/password',
                App\Controller\Dashboard\PasswordAction::class
            )->setName('dashboard:password');

            $group->group('/posters', function (RouteCollectorProxy $group) {
                $group->get(
                    '',
                    App\Controller\Dashboard\PostersController::class . ':listAction'
                )->setName('dashboard:posters');

                $group->map(
                    ['GET', 'POST'],
                    '/create',
                    App\Controller\Dashboard\PostersController::class . ':createAction'
                )->setName('dashboard:posters:create');

                $group->map(
                    ['GET', 'POST'],
                    '/edit[/{id}]',
                    App\Controller\Dashboard\PostersController::class . ':editAction'
                )->setName('dashboard:posters:edit');

                $group->get(
                    '/delete[/{id}]',
                    App\Controller\Dashboard\PostersController::class . ':deleteAction'
                )->setName('dashboard:posters:delete');
            });

            $group->map(
                ['GET', 'POST'],
                '/profile[/{id}]',
                App\Controller\Dashboard\EditProfileAction::class
            )->setName('dashboard:profile');

            $group->group('/short_urls', function (RouteCollectorProxy $group) {
                $group->get(
                    '',
                    App\Controller\Dashboard\ShortUrlsController::class . ':listAction'
                )->setName('dashboard:short_urls');

                $group->map(
                    ['GET', 'POST'],
                    '/create',
                    App\Controller\Dashboard\ShortUrlsController::class . ':createAction'
                )->setName('dashboard:short_urls:create');

                $group->map(
                    ['GET', 'POST'],
                    '/edit[/{id}]',
                    App\Controller\Dashboard\ShortUrlsController::class . ':editAction'
                )->setName('dashboard:short_urls:edit');

                $group->get(
                    '/delete[/{id}]',
                    App\Controller\Dashboard\ShortUrlsController::class . ':deleteAction'
                )->setName('dashboard:short_urls:delete');
            })->add(new App\Middleware\Auth\RequireMod());

            $group->map(
                ['GET', 'POST'],
                '/skills',
                App\Controller\Dashboard\SkillsController::class
            )->setName('dashboard:skills');
        })->add(new App\Middleware\Auth\RequireLoggedIn());

        $group->get('/defective', View::staticPage('defective/index'))
            ->setName('defective');

        $group->get('/donate', View::staticPage('donate'))
            ->setName('donate');

        $group->map(['GET', 'POST'], '/forgot', App\Controller\Account\ForgotAction::class)
            ->setName('forgot');

        $group->group('/foxxcon', function (RouteCollectorProxy $group) {
            $group->get('', View::staticPage('foxxcon/index'))
                ->setName('foxxcon');

            $group->get('/instances', View::staticPage('foxxcon/instances'))
                ->setName('foxxcon:instances');
        });

        $group->get('/live', View::staticPage('live'))
            ->setName('live');

        $group->map(['GET', 'POST'], '/login', App\Controller\Account\LoginAction::class)
            ->setName('login');

        $group->get('/logout', App\Controller\Account\LogoutAction::class)
            ->setName('logout');

        $group->get('/portals', View::staticPage('portals'))
            ->setName('portals');

        $group->get('/posters/faq', App\Controller\Posters\GetFaqAction::class)
            ->setName('posters:faq');

        $group->get('/profile[/{user}]', App\Controller\ProfileAction::class)
            ->setName('profile');

        $group->map(['GET', 'POST'], '/recover', App\Controller\Account\RecoverAction::class)
            ->setName('recover');

        $group->map(['GET', 'POST'], '/register', App\Controller\Account\RegisterAction::class)
            ->setName('register');

        $group->get('/talent', App\Controller\TalentAction::class)
            ->setName('talent');

        $group->get('/team', App\Controller\TeamAction::class)
            ->setName('team');

        $group->group('/wwradio', function (RouteCollectorProxy $group) {
            $group->get('', View::staticPage('wwradio/index'))
                ->setName('wwradio');

            $group->get('/info', View::staticPage('wwradio/info'))
                ->setName('wwradio:info');
        });

        $group->get('/worlds', App\Controller\WorldsController::class . ':listAction')
            ->setName('worlds');

        $group->get('/world[/{id}]', App\Controller\WorldsController::class . ':getAction')
            ->setName('world');
    })->add(App\Middleware\EnableView::class)
        ->add(App\Middleware\GetCurrentUser::class)
        ->add(App\Middleware\EnableSession::class);

    /*
     * No view, public-facing APIs
     */
    $app->group('/api', function (RouteCollectorProxy $group) {
        $group->get('/json', App\Controller\Api\JsonAction::class)
            ->setName('api:json');

        $group->post('/vrc_api', App\Controller\Api\VrcApiAction::class)
            ->setName('api:vrc_api');

        $group->group('/comments', function (RouteCollectorProxy $group) {
            $group->get('/{location}', App\Controller\Api\CommentsController::class . ':listAction')
                ->setName('api:comments');

            $group->post('/{location}', App\Controller\Api\CommentsController::class . ':postAction')
                ->setName('api:comments:post')
                ->add(new App\Middleware\Auth\RequireLoggedIn())
                ->add(App\Middleware\GetCurrentUser::class)
                ->add(App\Middleware\EnableSession::class);

            $group->delete('/{location}', App\Controller\Api\CommentsController::class . ':deleteAction')
                ->setName('api:comments:delete')
                ->add(new App\Middleware\Auth\RequireMod())
                ->add(App\Middleware\GetCurrentUser::class)
                ->add(App\Middleware\EnableSession::class);
        });
    });

    $app->get('/posters[/{id}]', App\Controller\Posters\GetPosterAction::class)
        ->setName('posters');

    $app->get('/short_url[/{url}]', App\Controller\GetShortUrlAction::class)
        ->setName('short_url');

    /*
     * URL Redirects
     */
    $redirects = [
        'discord' => 'https://discord.gg/waterwolf',
        'twitch' => 'https://www.twitch.tv/waterwolfvr',
        'twitter' => 'https://twitter.com/waterwolftown',
        'x' => 'https://twitter.com/waterwolftown',
        'vrchat' => 'https://vrc.group/WWOLF.1912',
    ];

    foreach ($redirects as $url => $dest) {
        $app->get(
            '/' . $url,
            function (ServerRequest $request, Response $response) use ($dest): ResponseInterface {
                return $response->withRedirect($dest);
            }
        )->setName($url);
    }
};
