<?php

declare(strict_types=1);

namespace App\User\Infrastructure\DataFixtures\ORM;

use App\General\Domain\Enum\Language;
use App\General\Domain\Enum\Locale;
use App\General\Domain\Rest\UuidHelper;
use App\Media\Domain\Entity\File;
use App\Media\Domain\Entity\Folder;
use App\Media\Domain\Enum\FileType;
use App\Role\Application\Security\Interfaces\RolesServiceInterface;
use App\Tests\Utils\PhpUnitUtil;
use App\User\Domain\Entity\User;
use App\User\Domain\Entity\UserGroup;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Override;
use Throwable;

use function array_map;

/**
 * @package App\User
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class LoadUserData extends Fixture implements OrderedFixtureInterface
{
    /**
     * @var array<non-empty-string, non-empty-string>
     */
    public static array $uuids = [
        'john' => '20000000-0000-1000-8000-000000000001',
        'john-logged' => '20000000-0000-1000-8000-000000000002',
        'john-api' => '20000000-0000-1000-8000-000000000003',
        'john-user' => '20000000-0000-1000-8000-000000000004',
        'john-admin' => '20000000-0000-1000-8000-000000000005',
        'john-root' => '20000000-0000-1000-8000-000000000006',
    ];

    public function __construct(
        private readonly RolesServiceInterface $rolesService,
    ) {
    }

    /**
     * Load data fixtures with the passed EntityManager
     *
     * @throws Throwable
     */
    #[Override]
    public function load(ObjectManager $manager): void
    {
        // Create entities
        array_map(
            fn (?string $role): bool => $this->createUser($manager, $role),
            [
                null,
                ...$this->rolesService->getRoles(),
            ],
        );
        // Flush database changes
        $manager->flush();
    }

    /**
     * Get the order of this fixture
     */
    #[Override]
    public function getOrder(): int
    {
        return 3;
    }

    public static function getUuidByKey(string $key): string
    {
        return self::$uuids[$key];
    }

    /**
     * Method to create User entity with specified role.
     *
     * @throws Throwable
     */
    private function createUser(ObjectManager $manager, ?string $role = null): bool
    {
        $suffix = $role === null ? '' : '-' . $this->rolesService->getShort($role);
        // Create new entity
        $entity = (new User())
            ->setUsername('john' . $suffix)
            ->setFirstName('John')
            ->setLastName('Doe')
            ->setEmail('john.doe' . $suffix . '@test.com')
            ->setLanguage(Language::EN)
            ->setLocale(Locale::EN)
            ->setPlainPassword('password' . $suffix);

        if ($role !== null) {
            /** @var UserGroup $userGroup */
            $userGroup = $this->getReference('UserGroup-' . $this->rolesService->getShort($role), UserGroup::class);
            $entity->addUserGroup($userGroup);
        }

        PhpUnitUtil::setProperty(
            'id',
            UuidHelper::fromString(self::$uuids['john' . $suffix]),
            $entity
        );



        // Persist entity
        $manager->persist($entity);
        // Create reference for later usage
        $this->addReference('User-' . $entity->getUsername(), $entity);

        $this->createMediaSpace($entity, $manager);
        return true;
    }

    /**
     * @param User $user
     * @param      $manager
     *
     * @return void
     */
    private function createMediaSpace(User $user, $manager): void
    {
        $recipes = new Folder();
        $recipes->setName('Recipes');
        $recipes->setIsPrivate(false);
        $recipes->setIsFavorite(true);
        $recipes->setUser($user);

        $desserts = new Folder();
        $desserts->setName('Desserts');
        $desserts->setIsPrivate(false);
        $desserts->setIsFavorite(false);
        $desserts->setParent($recipes);
        $desserts->setUser($user);
        $recipes->getChildren()->add($desserts);

        $file1 = $this->createFile('chocolate_cake.pdf', 45678, 'mp4', $desserts, $user);
        $file2 = $this->createFile('chocolate_cake.pdf', 45678, 'mp3', $desserts, $user);
        $file3 = $this->createFile('chocolate_cake.pdf', 45678, 'zip', $desserts, $user);

        $xlsx = $this->createFile('recipe_index.xlsx', 32212, 'xlsx', $recipes, $user);

        $manager->persist($recipes);
        $manager->persist($desserts);
        $manager->persist($file1);
        $manager->persist($file2);
        $manager->persist($file3);
        $manager->persist($xlsx);

        $manager->flush();
    }
    private function createFile(string $name, int $size, string $extension, Folder $folder, User $user): File
    {
        $type = FileType::fromExtension($extension);
        $file = new File();
        $file->setName($name);
        $file->setType($type);
        $file->setExtension($extension);
        $file->setSize($size);
        $file->setExtension($extension);
        $file->setIsPrivate(false);
        $file->setIsFavorite(false);
        $file->setFolder($folder);
        $file->setUser($user);
        $file->setUrl('/uploads/' . $name);
        return $file;
    }

}
