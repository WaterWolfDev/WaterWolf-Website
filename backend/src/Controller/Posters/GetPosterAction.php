<?php

namespace App\Controller\Posters;

use App\Environment;
use App\Http\Response;
use App\Http\ServerRequest;
use Doctrine\DBAL\Connection;
use Psr\Http\Message\ResponseInterface;
use Psr\SimpleCache\CacheInterface;

final readonly class GetPosterAction
{
    public function __construct(
        private Connection $db,
        private CacheInterface $cache,
        private Environment $environment
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $posterId = $params['id'] ?? $request->getParam('pid', $request->getParam('id'));

        $qb = $this->db->createQueryBuilder()
            ->select('p.id', 'p.file')
            ->from('web_posters', 'p')
            ->join('p', 'web_users', 'u', 'p.creator = u.id')
            ->where('u.banned != 1')
            ->andWhere('(p.expires_at IS NULL OR p.expires_at > CURRENT_TIMESTAMP())');

        $post = null;

        if (!empty($posterId)) {
            // Given an ID, just load the specified poster directly.
            $qb->andWhere('p.id = :id')
                ->setParameter('id', $posterId)
                ->setMaxResults(1);

            $post = $qb->fetchAssociative();
        } else {
            /*
             * Apply filters to the query or cache. Any filters can be chained.
             */

            $filters = [];

            $queryNickname = $request->getParam('collection');
            if (!empty($queryNickname)) {
                $qb->andWhere('LOWER(p.collection) = LOWER(:collection)')
                    ->setParameter('collection', $queryNickname);

                $filters[] = 'collection:' . $queryNickname;
            }

            $queryType = $request->getParam('type');
            if (!empty($queryType)) {
                $qb->andWhere('p.type_id = :type')
                    ->setParameter('type', $queryType);

                $filters[] = 'type:' . $queryType;
            }

            $queryGroup = $request->getParam('group');
            if (!empty($queryGroup)) {
                $qb->andWhere('p.group_id = :group')
                    ->setParameter('group', $queryGroup);

                $filters[] = 'group:' . $queryGroup;
            }

            /*
             * Cache a shuffled list of posters to avoid serving duplicate posters to people
             * loading multiple posters in the same world. VRC imposes a delay of 5 seconds
             * between image loads, so the cache lifetime has to exceed that.
             */

            $cacheKey = (count($filters) > 0)
                ? 'posters_' . $request->getIp() . '_' . md5(implode('_', $filters))
                : 'posters_' . $request->getIp() . '_all';

            $cacheKey = str_replace(':', '.', $cacheKey);

            if ($this->cache->has($cacheKey)) {
                $shuffleQueue = (array)$this->cache->get($cacheKey);

                if (count($shuffleQueue) > 0) {
                    $post = array_pop($shuffleQueue);
                    $post['cache'] = 'hit';

                    if (empty($shuffleQueue)) {
                        $this->cache->delete($cacheKey);
                    } else {
                        $this->cache->set($cacheKey, $shuffleQueue, 15);
                    }
                }
            }

            if ($post === null) {
                $shuffleQueue = $qb
                    ->orderBy('RAND()')
                    ->setMaxResults(30)
                    ->fetchAllAssociative();

                if (!empty($shuffleQueue)) {
                    $post = array_pop($shuffleQueue);
                    $post['cache'] = 'miss';

                    if (!empty($shuffleQueue)) {
                        $this->cache->set($cacheKey, $shuffleQueue, 15);
                    }
                }
            }
        }

        $imagePath = $this->environment->getBaseDirectory() . '/web/static/img/no_poster.jpg';

        if (!empty($post)) {
            $tryPaths = [
                '/img/posters/' . $post['file'] . '_full.jpg',
                '/img/posters/' . $post['file'] . '_590x1000.jpeg',
            ];

            foreach ($tryPaths as $tryPath) {
                $postPath = mediaPath($tryPath);
                if (file_exists($postPath)) {
                    $imagePath = $postPath;
                    break;
                }
            }

            // Update view count
            $this->db->executeQuery(
                <<<'SQL'
                UPDATE web_posters
                SET views=views+1, last_viewed = UNIX_TIMESTAMP()
                WHERE id = :id
            SQL,
                [
                    'id' => $post['id'],
                ]
            );
        }

        return $response->renderFile($imagePath)
            ->withHeader('Content-Disposition', 'inline');
    }
}
