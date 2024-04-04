<?php

declare(strict_types=1);

use App\Media;
use League\Flysystem\StorageAttributes;
use Phinx\Db\Table\Column;
use Phinx\Migration\AbstractMigration;

final class AddPosterPaths extends AbstractMigration
{
    public function change(): void
    {
        $this->table('web_posters')
            ->addColumn(
                (new Column())
                    ->setName('full_path')
                    ->setType(Column::STRING)
                    ->setLimit(150)
                    ->setNull(true)
                    ->setAfter('file')
            )->addColumn(
                (new Column())
                    ->setName('thumb_path')
                    ->setType(Column::STRING)
                    ->setLimit(150)
                    ->setNull(true)
                    ->setAfter('full_path')
            )->update();

        if ($this->isMigratingUp()) {
            $this->setPathsOnExistingPosters();
        }
    }

    protected function setPathsOnExistingPosters(): void
    {
        $fs = Media::getFilesystem();

        $posterFiles = [];

        /** @var StorageAttributes $posterFile */
        foreach ($fs->listContents('img/posters/') as $posterFile) {
            if (!$posterFile->isFile()) {
                continue;
            }

            $posterFiles[$posterFile->path()] = $posterFile->path();
        }

        $posters = $this->query(
            <<<'SQL'
                SELECT p.* FROM web_posters AS p
            SQL
        );

        foreach($posters as $poster) {
            $tryFullPaths = [
                'img/posters/%s_full.jpg',
                'img/posters/%s_590x1000.jpeg',
            ];

            $fullPath = null;
            foreach($tryFullPaths as $tryPath) {
                $tryPath = sprintf($tryPath, $poster['file']);
                if (isset($posterFiles[$tryPath])) {
                    $fullPath = basename($tryPath);
                    break;
                }
            }

            $tryThumbPaths = [
                'img/posters/%s_thumb.jpg',
                'img/posters/%s_150x200.jpeg',
            ];

            $thumbPath = null;
            foreach($tryThumbPaths as $tryPath) {
                $tryPath = sprintf($tryPath, $poster['file']);
                if (isset($posterFiles[$tryPath])) {
                    $thumbPath = basename($tryPath);
                    break;
                }
            }

            $this->getUpdateBuilder()
                ->update('web_posters')
                ->set('full_path', $fullPath)
                ->set('thumb_path', $thumbPath)
                ->where(['id' => $poster['id']])
                ->execute();
        }
    }
}
