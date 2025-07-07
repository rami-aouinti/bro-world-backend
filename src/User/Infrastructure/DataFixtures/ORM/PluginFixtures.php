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
                'subTitle' => 'Manage your online store',
                'logo' => 'https://bro-world.org/uploads/logo/ecommerce.png',
                'description' => 'Easily create, manage, and sell your products online. Track orders, control inventory, and deliver a seamless shopping experience to your customers with an intuitive dashboard.',
                'icon' => 'mdi-shopping',
                'installed' => true,
                'link' => '/shopping',
                'pricing' => 'free',
                'action' => 'open',
            ],
            [
                'key' => 'calendar',
                'name' => 'Calendar',
                'subTitle' => 'Organize your time and events',
                'logo' => 'https://bro-world.org/uploads/logo/general.png',
                'description' => 'Plan and schedule your meetings, appointments, and tasks efficiently. Sync with your other tools and stay on top of your daily activities.',
                'icon' => 'mdi-calendar-clock',
                'installed' => true,
                'link' => '/calendar',
                'pricing' => 'paid',
                'action' => 'open',
            ],
            [
                'key' => 'chat',
                'name' => 'Chat',
                'subTitle' => 'Instant messaging for teams',
                'logo' => 'https://bro-world.org/uploads/logo/chat.png',
                'description' => 'Communicate in real time with teammates, friends, or clients. Supports private messages, group chats, file attachments, and live notifications.',
                'icon' => 'mdi-chat',
                'installed' => false,
                'link' => '/inbox',
                'pricing' => 'free',
                'action' => 'install',
            ],
            [
                'key' => 'game',
                'name' => 'Game',
                'subTitle' => 'Play and compete',
                'logo' => 'https://bro-world.org/uploads/logo/game.png',
                'description' => 'Enjoy a selection of interactive games. Relax or challenge others in a fun and engaging environment integrated within your workspace.',
                'icon' => 'mdi-gamepad-variant',
                'installed' => true,
                'link' => '/game',
                'pricing' => 'paid',
                'action' => 'open',
            ],
            [
                'key' => 'blog',
                'name' => 'Blog',
                'subTitle' => 'Write and share your ideas',
                'logo' => 'https://bro-world.org/uploads/logo/blog.png',
                'description' => 'Create and publish articles, stories, or tutorials. Manage your content, interact with readers, and grow your audience with ease.',
                'icon' => 'mdi-file-document',
                'installed' => true,
                'link' => '/home',
                'pricing' => 'free',
                'action' => 'open',
            ],
            [
                'key' => 'crm',
                'name' => 'CRM',
                'subTitle' => 'Customer relationship management',
                'logo' => 'https://bro-world.org/uploads/logo/general.png',
                'description' => 'Track leads, manage contacts, and improve your customer engagement. A powerful CRM tool to streamline your sales and communication processes.',
                'icon' => 'mdi-table-large',
                'installed' => true,
                'link' => '/crm',
                'pricing' => 'free',
                'action' => 'open',
            ],
            [
                'key' => 'job',
                'name' => 'Job Board',
                'subTitle' => 'Find or post job offers',
                'logo' => 'https://bro-world.org/uploads/logo/job.png',
                'description' => 'Create job postings and browse new opportunities. Whether you are a recruiter or a job seeker, this tool helps you connect and grow your career.',
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
                subTitle: $data['subTitle'],
                logo: $data['logo'],
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
