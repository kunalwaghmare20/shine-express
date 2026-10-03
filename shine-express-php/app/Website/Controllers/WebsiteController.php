<?php

declare(strict_types=1);

namespace App\Website\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Services\SettingService;
use App\Website\Controller;
use App\Website\Models\GalleryItem;
use App\Website\Models\Page;
use App\Website\Models\Testimonial;
use App\Website\Services\WebsiteService;

final class WebsiteController extends Controller
{
    public function settings(): void
    {
        $cms = new WebsiteService();
        $this->admin('admin/settings', [
            'title' => 'Website settings',
            'site' => $cms->settings(),
            'cmsReady' => $cms->cmsReady(),
            'tableReady' => SettingService::instance()->tableReady(),
        ]);
    }

    public function saveSettings(): void
    {
        if (!Request::isPost() || !verify_csrf(Request::input('_csrf'))) {
            flash_error('Invalid request');
            $this->redirect('/admin/website/settings');
        }

        $settings = SettingService::instance();
        if (!$settings->tableReady()) {
            flash_error('Run database migration 009_app_settings.sql first, then save again.');
            $this->redirect('/admin/website/settings');
        }

        $pairs = [];
        foreach (array_keys(WebsiteService::DEFAULTS) as $key) {
            if (in_array($key, ['WEBSITE_LOGO', 'WEBSITE_HERO_IMAGE', 'WEBSITE_ABOUT_PHOTO'], true)) {
                continue;
            }
            $pairs[$key] = trim((string) Request::input($key, WebsiteService::DEFAULTS[$key]));
        }

        $existing = (new WebsiteService())->settings();
        $pairs['WEBSITE_LOGO'] = $existing['logo_path'];
        $pairs['WEBSITE_HERO_IMAGE'] = $existing['hero_image_path'];
        $pairs['WEBSITE_ABOUT_PHOTO'] = $existing['about_photo_path'];

        try {
            foreach (['WEBSITE_LOGO' => 'logo', 'WEBSITE_HERO_IMAGE' => 'hero_image', 'WEBSITE_ABOUT_PHOTO' => 'about_photo'] as $key => $field) {
                $uploaded = save_public_image($field, 'website');
                if ($uploaded !== null) {
                    $pairs[$key] = $uploaded;
                }
            }
            $settings->setMany($pairs, Auth::id());
            flash_success('Website settings saved.');
        } catch (\Throwable $e) {
            flash_error($e->getMessage());
        }
        $this->redirect('/admin/website/settings');
    }

    public function pages(): void
    {
        $cms = new WebsiteService();
        $this->admin('admin/pages', [
            'title' => 'Website pages',
            'pages' => $cms->pages(),
            'cmsReady' => $cms->cmsReady(),
        ]);
    }

    public function createPageForm(): void
    {
        $this->requireCms();
        $this->admin('admin/page_form', [
            'title' => 'Add page',
            'page' => null,
        ]);
    }

    public function storePage(): void
    {
        $this->requireCms();
        if (!verify_csrf(Request::input('_csrf'))) {
            flash_error('Invalid token');
            $this->redirect('/admin/website/pages');
        }
        $title = trim((string) Request::input('title'));
        $slug = slugify((string) Request::input('slug', $title));
        if ($title === '') {
            flash_error('Title is required.');
            $this->redirect('/admin/website/pages/create');
        }
        try {
            (new Page())->create([
                'id' => generate_id(),
                'slug' => $slug,
                'title' => $title,
                'body' => (string) Request::input('body', ''),
                'seo_title' => trim((string) Request::input('seo_title')),
                'seo_description' => trim((string) Request::input('seo_description')),
                'updated_by' => Auth::id(),
            ]);
            flash_success('Page created.');
        } catch (\Throwable $e) {
            flash_error('Could not save the page. The slug may already be in use.');
        }
        $this->redirect('/admin/website/pages');
    }

    public function editPageForm(string $id): void
    {
        $this->requireCms();
        $page = $this->findPage($id);
        if ($page === null) {
            flash_error('Page not found');
            $this->redirect('/admin/website/pages');
        }
        $this->admin('admin/page_form', [
            'title' => 'Edit page',
            'page' => $page,
        ]);
    }

    public function updatePage(string $id): void
    {
        $this->requireCms();
        if (!verify_csrf(Request::input('_csrf'))) {
            flash_error('Invalid token');
            $this->redirect('/admin/website/pages');
        }
        $page = $this->findPage($id);
        if ($page === null) {
            flash_error('Page not found');
            $this->redirect('/admin/website/pages');
        }
        $title = trim((string) Request::input('title'));
        $slug = (string) ($page['slug'] ?? '') === 'about'
            ? 'about'
            : slugify((string) Request::input('slug', $title));
        (new Page())->update($id, [
            'slug' => $slug,
            'title' => $title,
            'body' => (string) Request::input('body', ''),
            'seo_title' => trim((string) Request::input('seo_title')),
            'seo_description' => trim((string) Request::input('seo_description')),
            'updated_by' => Auth::id(),
        ]);
        flash_success('Page updated.');
        $this->redirect('/admin/website/pages');
    }

    public function deletePage(string $id): void
    {
        $this->requireCms();
        if (!verify_csrf(Request::input('_csrf'))) {
            flash_error('Invalid token');
            $this->redirect('/admin/website/pages');
        }
        $page = $this->findPage($id);
        if ($page !== null && (string) $page['slug'] === 'about') {
            flash_error('The About page cannot be deleted.');
            $this->redirect('/admin/website/pages');
        }
        (new Page())->delete($id);
        flash_success('Page deleted.');
        $this->redirect('/admin/website/pages');
    }

    public function gallery(): void
    {
        $cms = new WebsiteService();
        $this->admin('admin/gallery', [
            'title' => 'Website gallery',
            'items' => $cms->gallery(false),
            'cmsReady' => $cms->cmsReady(),
        ]);
    }

    public function storeGallery(): void
    {
        $this->requireCms();
        if (!verify_csrf(Request::input('_csrf'))) {
            flash_error('Invalid token');
            $this->redirect('/admin/website/gallery');
        }
        try {
            $path = save_public_image('image', 'website');
            if ($path === null) {
                flash_error('Please choose a JPEG, PNG, or WebP image.');
                $this->redirect('/admin/website/gallery');
            }
            (new GalleryItem())->create([
                'id' => generate_id(),
                'image_path' => $path,
                'caption' => trim((string) Request::input('caption')),
                'sort_order' => (int) Request::input('sort_order', 0),
                'show_on_home' => Request::input('show_on_home') ? 1 : 0,
            ]);
            flash_success('Photo added.');
        } catch (\Throwable $e) {
            flash_error($e->getMessage());
        }
        $this->redirect('/admin/website/gallery');
    }

    public function updateGallery(string $id): void
    {
        $this->requireCms();
        if (!verify_csrf(Request::input('_csrf'))) {
            flash_error('Invalid token');
            $this->redirect('/admin/website/gallery');
        }
        (new GalleryItem())->update($id, [
            'caption' => trim((string) Request::input('caption')),
            'sort_order' => (int) Request::input('sort_order', 0),
            'show_on_home' => Request::input('show_on_home') ? 1 : 0,
        ]);
        flash_success('Photo updated.');
        $this->redirect('/admin/website/gallery');
    }

    public function deleteGallery(string $id): void
    {
        $this->requireCms();
        if (!verify_csrf(Request::input('_csrf'))) {
            flash_error('Invalid token');
            $this->redirect('/admin/website/gallery');
        }
        (new GalleryItem())->delete($id);
        flash_success('Photo removed.');
        $this->redirect('/admin/website/gallery');
    }

    public function testimonials(): void
    {
        $this->admin('admin/testimonials', [
            'title' => 'Testimonials',
            'items' => (new Testimonial())->listed(false),
            'cmsReady' => (new WebsiteService())->cmsReady(),
        ]);
    }

    public function storeTestimonial(): void
    {
        $this->requireCms();
        if (!verify_csrf(Request::input('_csrf'))) {
            flash_error('Invalid token');
            $this->redirect('/admin/website/testimonials');
        }
        $quote = trim((string) Request::input('quote'));
        $name = trim((string) Request::input('name'));
        if ($quote === '' || $name === '') {
            flash_error('Quote and name are required.');
            $this->redirect('/admin/website/testimonials');
        }
        try {
            $photo = save_public_image('photo', 'website');
            (new Testimonial())->create([
                'id' => generate_id(),
                'quote' => $quote,
                'name' => $name,
                'area' => trim((string) Request::input('area')),
                'rating' => min(5, max(1, (int) Request::input('rating', 5))),
                'photo_path' => $photo,
                'sort_order' => (int) Request::input('sort_order', 0),
                'is_active' => Request::input('is_active') ? 1 : 0,
            ]);
            flash_success('Testimonial added.');
        } catch (\Throwable $e) {
            flash_error($e->getMessage());
        }
        $this->redirect('/admin/website/testimonials');
    }

    public function updateTestimonial(string $id): void
    {
        $this->requireCms();
        if (!verify_csrf(Request::input('_csrf'))) {
            flash_error('Invalid token');
            $this->redirect('/admin/website/testimonials');
        }
        $row = (new Testimonial())->find($id);
        if ($row === null) {
            flash_error('Testimonial not found');
            $this->redirect('/admin/website/testimonials');
        }
        $photo = (string) ($row['photo_path'] ?? '');
        try {
            $uploaded = save_public_image('photo', 'website');
            if ($uploaded !== null) {
                $photo = $uploaded;
            }
        } catch (\Throwable $e) {
            flash_error($e->getMessage());
            $this->redirect('/admin/website/testimonials');
        }
        (new Testimonial())->update($id, [
            'quote' => trim((string) Request::input('quote')),
            'name' => trim((string) Request::input('name')),
            'area' => trim((string) Request::input('area')),
            'rating' => min(5, max(1, (int) Request::input('rating', 5))),
            'photo_path' => $photo !== '' ? $photo : null,
            'sort_order' => (int) Request::input('sort_order', 0),
            'is_active' => Request::input('is_active') ? 1 : 0,
        ]);
        flash_success('Testimonial updated.');
        $this->redirect('/admin/website/testimonials');
    }

    public function deleteTestimonial(string $id): void
    {
        $this->requireCms();
        if (!verify_csrf(Request::input('_csrf'))) {
            flash_error('Invalid token');
            $this->redirect('/admin/website/testimonials');
        }
        (new Testimonial())->delete($id);
        flash_success('Testimonial removed.');
        $this->redirect('/admin/website/testimonials');
    }

    public function enquiries(): void
    {
        $cms = new WebsiteService();
        $this->admin('admin/enquiries', [
            'title' => 'Website enquiries',
            'items' => $cms->enquiries(),
            'cmsReady' => $cms->cmsReady(),
        ]);
    }

    private function requireCms(): void
    {
        if ((new WebsiteService())->cmsReady()) {
            return;
        }
        flash_error('Run database migration 010_website_cms.sql, then try again.');
        $this->redirect('/admin/website/settings');
    }

    /** @return array<string, mixed>|null */
    private function findPage(string $id): ?array
    {
        return (new Page())->find($id);
    }

    /** @param array<string, mixed> $data */
    private function admin(string $view, array $data): void
    {
        $this->view($view, array_merge(['user' => Auth::user()], $data), 'layouts/dashboard');
    }
}
