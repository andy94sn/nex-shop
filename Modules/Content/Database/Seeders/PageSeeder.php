<?php

declare(strict_types=1);

namespace Modules\Content\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Content\Models\Page;
use Modules\Content\Models\PageImage;
use Modules\Content\Models\PageAttachment;

class PageSeeder extends Seeder
{
    public function run(): void
    {
        // Create page
        $page = Page::create([
            'slug' => 'about-us',
            'title' => [
                'en' => 'About NexDistribution',
                'ro' => 'Despre NexDistribution',
            ],
            'content' => [
                'en' => '<p>We are a leading distributor of electronics across Europe. Our mission is to connect customers with the best products at great prices.</p>',
                'ro' => '<p>Suntem un distribuitor de top de electronice în Europa. Misiunea noastră este să conectăm clienții cu cele mai bune produse la prețuri avantajoase.</p>',
            ],
            'is_active' => true,
            'type' => 'about',
            'meta_title' => [
                'en' => 'About NexDistribution — Who we are',
                'ro' => 'Despre NexDistribution — Cine suntem',
            ],
            'meta_description' => [
                'en' => 'Learn about NexDistribution, our mission and partners.',
                'ro' => 'Aflați despre NexDistribution, misiunea și partenerii noștri.',
            ],
            'intro_text' => [
                'en' => 'Leading distributor of electronics',
                'ro' => 'Distribuitor de top de electronice',
            ],
        ]);

        // Add an image (paths are example strings; ensure files exist in storage)
        PageImage::create([
            'page_id' => $page->id,
            'path' => 'pages/about/hero.jpg',
            'alt' => [
                'en' => 'About us hero image',
                'ro' => 'Imagine despre noi',
            ],
            'caption' => [
                'en' => 'Our team at the warehouse',
                'ro' => 'Echipa noastră în depozit',
            ],
            'is_cover' => true,
            'sort' => 0,
        ]);

        // Add an attachment (e.g., company brochure)
        PageAttachment::create([
            'page_id' => $page->id,
            'path' => 'pages/about/brochure.pdf',
            'label' => [
                'en' => 'Company brochure',
                'ro' => 'Broșură companie',
            ],
            'mime_type' => 'application/pdf',
            'size' => 123456,
            'sort' => 0,
        ]);
    }
}
