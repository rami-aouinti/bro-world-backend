<?php

declare(strict_types=1);

namespace App\Media\Application\Service;

use App\Media\Application\DTO\File\FileCreate;
use App\Media\Application\Resource\FileResource;
use App\Media\Application\Resource\FolderResource;
use App\Media\Domain\Entity\File;
use App\Media\Domain\Entity\Folder;
use App\Media\Domain\Enum\FileType;
use App\User\Application\Service\UserService;
use App\User\Domain\Entity\User;
use Symfony\Component\HttpFoundation\Request;

use function is_array;
use function pathinfo;

use const PATHINFO_EXTENSION;

/**
 * @package App\Media
 */
class FileCreationService
{
    public function __construct(
        private readonly UserService $userService,
        private readonly FileResource $fileResource,
        private readonly FolderResource $folderResource,
    ) {
    }

    /**
     * @return array<int, File>
     */
    public function create(User $user, Request $request, ?Folder $folder = null): array
    {
        $medias = $request->files->all() ? $this->userService->createMedia($request, 'media') : [];
        $data = $request->request->all();

        if ($folder === null && isset($data['folder'])) {
            $reference = $this->folderResource->getReference((string)$data['folder']);
            if ($reference instanceof Folder) {
                $folder = $reference;
            }
        }

        $isFavorite = (bool)($data['isFavorite'] ?? false);
        $isPrivate = (bool)($data['isPrivate'] ?? false);

        $created = [];

        foreach ($medias as $media) {
            if (!is_array($media) || !isset($media['fileName'], $media['path'])) {
                continue;
            }

            $extension = (string)pathinfo($media['fileName'], PATHINFO_EXTENSION);

            $dto = (new FileCreate())
                ->setName($media['fileName'])
                ->setUrl($media['path'])
                ->setSize((int)($media['fileSize'] ?? 0))
                ->setType(FileType::fromExtension($extension))
                ->setExtension($extension !== '' ? $extension : null)
                ->setIsFavorite($isFavorite)
                ->setIsPrivate($isPrivate)
                ->setUser($user);

            if ($folder instanceof Folder) {
                $dto->setFolder($folder);
            }

            $created[] = $this->fileResource->create($dto, true);
        }

        return $created;
    }
}

