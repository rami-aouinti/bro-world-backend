<?php

declare(strict_types=1);

namespace App\User\Infrastructure\DataFixtures\ORM;

use App\User\Domain\Entity\Plugin;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Throwable;

/**
 * Class PluginFixtures
 *
 * @package App\User\Infrastructure\DataFixtures\ORM
 * @author  Rami Aouinti <rami.aouinti@tkdeutschland.de>
 */
class PluginFixtures extends Fixture
{
    /**
     * @throws Throwable
     */
    public function load(ObjectManager $manager): void
    {
        $plugins = [
            [
                'key' => 'ecommerce',
                'name' => 'E-Commerce',
                'description' => 'Sell and manage products online.',
                'icon' => 'mdi-shopping',
                'installed' => true,
                'link' => '/shopping',
                'pricing' => 'free',
                'action' => 'open',
            ],
            [
                'key' => 'calendar',
                'name' => 'Calendar',
                'description' => 'Organize your time and events.',
                'icon' => 'mdi-calendar-clock',
                'installed' => true,
                'link' => '/calendar',
                'pricing' => 'paid',
                'action' => 'open',
            ],
            [
                'key' => 'chat',
                'name' => 'Chat',
                'description' => 'Real-time messaging with friends and team.',
                'icon' => 'mdi-chat',
                'installed' => false,
                'link' => '/inbox',
                'pricing' => 'free',
                'action' => 'install',
            ],
            [
                'key' => 'game',
                'name' => 'Game',
                'description' => 'Play and challenge others.',
                'icon' => 'mdi-gamepad-variant',
                'installed' => true,
                'link' => '/game',
                'pricing' => 'paid',
                'action' => 'open',
            ],
            [
                'key' => 'blog',
                'name' => 'Blog',
                'description' => 'Write and share your thoughts.',
                'icon' => 'mdi-file-document',
                'installed' => true,
                'link' => '/home',
                'pricing' => 'free',
                'action' => 'open',
            ],
            [
                'key' => 'crm',
                'name' => 'CRM',
                'description' => 'Manage your contacts and business.',
                'icon' => 'mdi-table-large',
                'installed' => true,
                'link' => '/crm',
                'pricing' => 'free',
                'action' => 'open',
            ],
            [
                'key' => 'job',
                'name' => 'Job Board',
                'description' => 'Post and find job opportunities.',
                'icon' => 'mdi-table-large',
                'installed' => true,
                'link' => '/jobs',
                'pricing' => 'free',
                'action' => 'open',
            ],
        ];

        foreach ($plugins as $data) {
            $plugin = new Plugin(
                key: $data['key'],
                name: $data['name'],
                icon: $data['icon'],
                link: $data['link'],
                pricing: $data['pricing'],
                action: $data['action'],
                installed: $data['installed'],
                description: $data['description']
            );
            $manager->persist($plugin);
        }

        $manager->flush();
    }
}
