<?php

declare(strict_types=1);

namespace App\Website\Services;

use App\Core\Database;
use App\Services\SettingService;
use App\Services\WhatsAppConfig;
use App\Website\Models\Enquiry;
use App\Website\Models\GalleryItem;
use App\Website\Models\Page;
use App\Website\Models\Testimonial;

final class WebsiteService
{
    /** @var array<string, string> */
    public const DEFAULTS = [
        'WEBSITE_LOGO' => '',
        'WEBSITE_HERO_IMAGE' => '',
        'WEBSITE_HERO_EYEBROW' => 'Home care, quietly done well',
        'WEBSITE_HERO_HEADLINE' => 'Hotel-level finish for the rooms you live in',
        'WEBSITE_HERO_SUBCOPY' => 'Sofas, kitchens, bathrooms, and pest care — booked in minutes, finished with care.',
        'WEBSITE_CTA_PRIMARY' => 'Book now',
        'WEBSITE_CTA_WHATSAPP' => 'WhatsApp',
        'WEBSITE_TRUST_1_VALUE' => '12+',
        'WEBSITE_TRUST_1_LABEL' => 'Years of care',
        'WEBSITE_TRUST_2_VALUE' => '8,000+',
        'WEBSITE_TRUST_2_LABEL' => 'Homes refreshed',
        'WEBSITE_TRUST_3_VALUE' => 'Pune & PCMC',
        'WEBSITE_TRUST_3_LABEL' => 'Areas served',
        'WEBSITE_HOW_1_TITLE' => 'Choose a service',
        'WEBSITE_HOW_1_BODY' => 'Pick the space that needs attention — sofa, kitchen, bathroom, or pest care.',
        'WEBSITE_HOW_2_TITLE' => 'Book a slot',
        'WEBSITE_HOW_2_BODY' => 'Register in the Shine Express app or message us on WhatsApp. We confirm the visit.',
        'WEBSITE_HOW_3_TITLE' => 'We take care of it',
        'WEBSITE_HOW_3_BODY' => 'A trained team arrives, works quietly, and leaves the room looking settled — not staged.',
        'WEBSITE_CONTACT_ADDRESS' => "Shine Express\nPune, Maharashtra",
        'WEBSITE_CONTACT_HOURS' => 'Open daily, 8:00 am – 8:00 pm',
        'WEBSITE_FOOTER_BLURB' => 'Hotel-level care for homes, kitchens, and living spaces.',
        'WEBSITE_SOCIAL_INSTAGRAM' => '',
        'WEBSITE_SOCIAL_FACEBOOK' => '',
        'WEBSITE_PLAY_STORE' => '',
        'WEBSITE_APP_STORE' => '',
        'WEBSITE_SEO_TITLE' => 'Shine Express — Professional home care',
        'WEBSITE_SEO_DESCRIPTION' => 'Boutique home-care for sofas, kitchens, bathrooms, and pest control in Pune.',
        'WEBSITE_SERVICES_INTRO' => 'Every service is priced clearly and finished to a hotel standard.',
        'WEBSITE_ABOUT_PHOTO' => '',
        'WEBSITE_VALUE_1_TITLE' => 'Quiet luxury',
        'WEBSITE_VALUE_1_BODY' => 'No harsh-chemical theatre. Just careful work and a calm home after we leave.',
        'WEBSITE_VALUE_2_TITLE' => 'Considered products',
        'WEBSITE_VALUE_2_BODY' => 'We choose finishes that respect fabrics, stone, and the people who live with them.',
        'WEBSITE_VALUE_3_TITLE' => 'People you can trust',
        'WEBSITE_VALUE_3_BODY' => 'Trained staff, timed visits, and a studio that answers on WhatsApp the same day.',
        'WEBSITE_WA_PREFILL' => 'Hello Shine Express, I would like to book a service.',
        'WEBSITE_BOOK_HEADLINE' => 'Ready for a quieter, cleaner home?',
        'WEBSITE_BOOK_LEDE' => 'Book in the Shine Express app, or message us on WhatsApp — we will take it from there.',
        'WEBSITE_GALLERY_INTRO' => 'Still rooms, after care. A few of the spaces we look after.',
        'WEBSITE_CONTACT_INTRO' => 'Visit, call, or send a note. We reply on WhatsApp the same day.',
    ];

    public function cmsReady(): bool
    {
        return (new Page())->tableReady();
    }

    /** @return array<string, mixed> */
    public function settings(): array
    {
        $raw = [];
        foreach (self::DEFAULTS as $key => $default) {
            $stored = trim((string) SettingService::get($key, $default));
            $raw[$key] = $stored !== '' ? $stored : $default;
        }

        $wa = WhatsAppConfig::supportWhatsApp();
        $prefill = $raw['WEBSITE_WA_PREFILL'];
        $waHref = whatsapp_link($wa, $prefill) ?? '#';

        return [
            'logo' => public_file_url($raw['WEBSITE_LOGO']),
            'logo_path' => $raw['WEBSITE_LOGO'],
            'hero_image' => public_file_url($raw['WEBSITE_HERO_IMAGE']),
            'hero_image_path' => $raw['WEBSITE_HERO_IMAGE'],
            'hero_eyebrow' => $raw['WEBSITE_HERO_EYEBROW'],
            'hero_headline' => $raw['WEBSITE_HERO_HEADLINE'],
            'hero_subcopy' => $raw['WEBSITE_HERO_SUBCOPY'],
            'cta_primary' => $raw['WEBSITE_CTA_PRIMARY'],
            'cta_whatsapp' => $raw['WEBSITE_CTA_WHATSAPP'],
            'trust' => [
                ['value' => $raw['WEBSITE_TRUST_1_VALUE'], 'label' => $raw['WEBSITE_TRUST_1_LABEL']],
                ['value' => $raw['WEBSITE_TRUST_2_VALUE'], 'label' => $raw['WEBSITE_TRUST_2_LABEL']],
                ['value' => $raw['WEBSITE_TRUST_3_VALUE'], 'label' => $raw['WEBSITE_TRUST_3_LABEL']],
            ],
            'how' => [
                ['title' => $raw['WEBSITE_HOW_1_TITLE'], 'body' => $raw['WEBSITE_HOW_1_BODY']],
                ['title' => $raw['WEBSITE_HOW_2_TITLE'], 'body' => $raw['WEBSITE_HOW_2_BODY']],
                ['title' => $raw['WEBSITE_HOW_3_TITLE'], 'body' => $raw['WEBSITE_HOW_3_BODY']],
            ],
            'values' => [
                ['title' => $raw['WEBSITE_VALUE_1_TITLE'], 'body' => $raw['WEBSITE_VALUE_1_BODY']],
                ['title' => $raw['WEBSITE_VALUE_2_TITLE'], 'body' => $raw['WEBSITE_VALUE_2_BODY']],
                ['title' => $raw['WEBSITE_VALUE_3_TITLE'], 'body' => $raw['WEBSITE_VALUE_3_BODY']],
            ],
            'contact_address' => $raw['WEBSITE_CONTACT_ADDRESS'],
            'contact_hours' => $raw['WEBSITE_CONTACT_HOURS'],
            'footer_blurb' => $raw['WEBSITE_FOOTER_BLURB'],
            'social_instagram' => $raw['WEBSITE_SOCIAL_INSTAGRAM'],
            'social_facebook' => $raw['WEBSITE_SOCIAL_FACEBOOK'],
            'play_store' => $raw['WEBSITE_PLAY_STORE'],
            'app_store' => $raw['WEBSITE_APP_STORE'],
            'seo_title' => $raw['WEBSITE_SEO_TITLE'],
            'seo_description' => $raw['WEBSITE_SEO_DESCRIPTION'],
            'services_intro' => $raw['WEBSITE_SERVICES_INTRO'],
            'about_photo' => public_file_url($raw['WEBSITE_ABOUT_PHOTO']),
            'about_photo_path' => $raw['WEBSITE_ABOUT_PHOTO'],
            'whatsapp_prefill' => $prefill,
            'whatsapp_href' => $waHref,
            'support_phone' => $wa,
            'book_headline' => $raw['WEBSITE_BOOK_HEADLINE'],
            'book_lede' => $raw['WEBSITE_BOOK_LEDE'],
            'gallery_intro' => $raw['WEBSITE_GALLERY_INTRO'],
            'contact_intro' => $raw['WEBSITE_CONTACT_INTRO'],
            'raw' => $raw,
        ];
    }

    /** @return list<array<string, mixed>> */
    public function featuredServices(int $limit = 6): array
    {
        $limit = max(1, $limit);
        try {
            $stmt = Database::connection()->query(
                "SELECT s.*, c.name AS category_name
                 FROM services s
                 JOIN service_categories c ON c.id = s.category_id
                 WHERE s.is_active = 1 AND COALESCE(s.show_on_website, 1) = 1 AND s.is_featured = 1
                 ORDER BY s.sort_order, s.name
                 LIMIT {$limit}"
            );
            $rows = $stmt->fetchAll() ?: [];
            if ($rows !== []) {
                return array_map([$this, 'hydrateService'], $rows);
            }
        } catch (\Throwable $e) {
            // Columns from 010 may be missing
        }

        return $this->publicServices($limit);
    }

    /** @return list<array<string, mixed>> */
    public function publicServices(?int $limit = null): array
    {
        $sql = 'SELECT s.*, c.name AS category_name, c.sort_order AS category_sort
                FROM services s
                JOIN service_categories c ON c.id = s.category_id
                WHERE s.is_active = 1';
        try {
            $sql .= ' AND COALESCE(s.show_on_website, 1) = 1';
        } catch (\Throwable $e) {
            // ignore
        }
        $sql .= ' ORDER BY c.sort_order, s.sort_order, s.name';
        if ($limit !== null) {
            $sql .= ' LIMIT ' . max(1, $limit);
        }

        try {
            $rows = Database::connection()->query($sql)->fetchAll() ?: [];
        } catch (\Throwable $e) {
            $rows = Database::connection()->query(
                'SELECT s.*, c.name AS category_name, c.sort_order AS category_sort
                 FROM services s
                 JOIN service_categories c ON c.id = s.category_id
                 WHERE s.is_active = 1
                 ORDER BY c.sort_order, s.sort_order, s.name'
                . ($limit !== null ? ' LIMIT ' . max(1, $limit) : '')
            )->fetchAll() ?: [];
        }

        return array_map([$this, 'hydrateService'], $rows);
    }

    /** @return array<string, list<array<string, mixed>>> */
    public function servicesByCategory(): array
    {
        $grouped = [];
        foreach ($this->publicServices() as $service) {
            $cat = (string) ($service['category_name'] ?? 'Services');
            $grouped[$cat][] = $service;
        }
        return $grouped;
    }

    /** @return array<string, mixed>|null */
    public function serviceBySlug(string $slug): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT s.*, c.name AS category_name
             FROM services s
             JOIN service_categories c ON c.id = s.category_id
             WHERE s.slug = ? AND s.is_active = 1 LIMIT 1'
        );
        $stmt->execute([$slug]);
        $row = $stmt->fetch();
        if ($row === false) {
            return null;
        }
        if (isset($row['show_on_website']) && (int) $row['show_on_website'] === 0) {
            return null;
        }

        $service = $this->hydrateService($row);
        $items = Database::connection()->prepare(
            'SELECT id, name, description, price, duration FROM service_items
             WHERE service_id = ? AND is_active = 1 ORDER BY sort_order, name'
        );
        $items->execute([(string) $service['id']]);
        $service['items'] = $items->fetchAll() ?: [];

        try {
            $faqs = Database::connection()->prepare(
                'SELECT question, answer FROM service_faqs
                 WHERE service_id = ? OR service_id IS NULL
                 ORDER BY sort_order'
            );
            $faqs->execute([(string) $service['id']]);
            $service['faqs'] = $faqs->fetchAll() ?: [];
        } catch (\Throwable $e) {
            $service['faqs'] = [];
        }

        return $service;
    }

    /** @return list<array<string, mixed>> */
    public function activeOffers(): array
    {
        try {
            return Database::connection()->query(
                'SELECT * FROM offers
                 WHERE is_active = 1
                   AND (starts_at IS NULL OR starts_at <= NOW())
                   AND (ends_at IS NULL OR ends_at >= NOW())
                 ORDER BY created_at DESC'
            )->fetchAll() ?: [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    /** @return list<array<string, mixed>> */
    public function gallery(bool $homeOnly = false): array
    {
        if (!$this->cmsReady()) {
            return [];
        }
        return (new GalleryItem())->listed($homeOnly);
    }

    /** @return list<array<string, mixed>> */
    public function testimonials(): array
    {
        $rows = $this->cmsReady() ? (new Testimonial())->listed(true) : [];
        if ($rows === []) {
            return [
                [
                    'quote' => 'They treated the apartment like a hotel suite. The sofa looks new, and nobody rushed.',
                    'name' => 'Ananya K.',
                    'area' => 'Koregaon Park',
                    'rating' => 5,
                    'photo_url' => '',
                ],
                [
                    'quote' => 'Quiet, precise kitchen work. I came home to a space that felt looked-after, not “cleaned at”.',
                    'name' => 'Rahul M.',
                    'area' => 'Baner',
                    'rating' => 5,
                    'photo_url' => '',
                ],
            ];
        }
        return $rows;
    }

    /** @return array<string, mixed>|null */
    public function pageBySlug(string $slug): ?array
    {
        $row = (new Page())->findBySlug($slug);
        if ($row !== null) {
            return $row;
        }
        return $slug === 'about' ? $this->defaultAbout() : null;
    }

    /** @return list<array<string, mixed>> */
    public function pages(): array
    {
        return (new Page())->ordered();
    }

    public function saveEnquiry(string $name, string $phone, string $email, string $message): void
    {
        (new Enquiry())->add($name, $phone, $email, $message);
    }

    /** @return list<array<string, mixed>> */
    public function enquiries(): array
    {
        return (new Enquiry())->recent();
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function hydrateService(array $row): array
    {
        $cover = trim((string) ($row['cover_image'] ?? ''));
        if ($cover === '') {
            $images = json_decode((string) ($row['images'] ?? '[]'), true);
            if (is_array($images) && isset($images[0]) && is_string($images[0])) {
                $cover = $images[0];
            }
        }
        $row['cover_url'] = public_file_url($cover);
        $row['from_price'] = money_format_inr($row['base_price'] ?? 0);
        return $row;
    }

    /** @return array<string, mixed> */
    private function defaultAbout(): array
    {
        return [
            'slug' => 'about',
            'title' => 'About Shine Express',
            'body' => self::DEFAULTS['WEBSITE_FOOTER_BLURB'] . "\n\n"
                . 'We look after sofas, kitchens, bathrooms, and pest care with a quiet, hotel-level finish.',
            'seo_title' => 'About Shine Express',
            'seo_description' => self::DEFAULTS['WEBSITE_SEO_DESCRIPTION'],
        ];
    }
}
