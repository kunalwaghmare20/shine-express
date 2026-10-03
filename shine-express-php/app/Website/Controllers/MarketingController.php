<?php

declare(strict_types=1);

namespace App\Website\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Session;
use App\Website\Controller;
use App\Website\Services\WebsiteService;

final class MarketingController extends Controller
{
    public function home(): void
    {
        $cms = new WebsiteService();
        $site = $cms->settings();
        $this->render('public/home', [
            'title' => 'Home',
            'seoTitle' => $site['seo_title'],
            'seoDescription' => $site['seo_description'],
            'ogImage' => $site['hero_image'],
            'featured' => $cms->featuredServices(6),
            'offers' => $cms->activeOffers(),
            'gallery' => $cms->gallery(true),
            'testimonials' => $cms->testimonials(),
        ], $site);
    }

    public function services(): void
    {
        $cms = new WebsiteService();
        $site = $cms->settings();
        $this->render('public/services', [
            'title' => 'Services',
            'seoTitle' => 'Services · Shine Express',
            'seoDescription' => $site['services_intro'],
            'grouped' => $cms->servicesByCategory(),
        ], $site);
    }

    public function service(string $slug): void
    {
        $cms = new WebsiteService();
        $site = $cms->settings();
        $service = $cms->serviceBySlug($slug);
        if ($service === null) {
            flash_error('That service is not available.');
            $this->redirect('/services');
        }
        $this->render('public/service', [
            'title' => (string) $service['name'],
            'seoTitle' => $service['name'] . ' · Shine Express',
            'seoDescription' => substr(trim((string) ($service['description'] ?? '')), 0, 160),
            'ogImage' => $service['cover_url'] ?: $site['hero_image'],
            'service' => $service,
        ], $site);
    }

    public function about(): void
    {
        $cms = new WebsiteService();
        $site = $cms->settings();
        $page = $cms->pageBySlug('about');
        $this->render('public/about', [
            'title' => (string) ($page['title'] ?? 'About'),
            'seoTitle' => (string) ($page['seo_title'] ?: ($page['title'] ?? 'About') . ' · Shine Express'),
            'seoDescription' => (string) ($page['seo_description'] ?? $site['seo_description']),
            'ogImage' => $site['about_photo'] ?: $site['hero_image'],
            'page' => $page,
        ], $site);
    }

    public function gallery(): void
    {
        $cms = new WebsiteService();
        $site = $cms->settings();
        $this->render('public/gallery', [
            'title' => 'Gallery',
            'seoTitle' => 'Gallery · Shine Express',
            'seoDescription' => $site['gallery_intro'],
            'gallery' => $cms->gallery(false),
        ], $site);
    }

    public function contact(): void
    {
        $cms = new WebsiteService();
        $site = $cms->settings();
        $this->render('public/contact', [
            'title' => 'Contact',
            'seoTitle' => 'Contact · Shine Express',
            'seoDescription' => $site['contact_intro'],
        ], $site);
    }

    public function submitEnquiry(): void
    {
        if (!verify_csrf(Request::input('_csrf'))) {
            flash_error('Please try again.');
            $this->redirect('/contact');
        }

        $name = trim((string) Request::input('name'));
        $phone = trim((string) Request::input('phone'));
        $email = trim((string) Request::input('email'));
        $message = trim((string) Request::input('message'));

        if ($name === '' || $message === '') {
            flash_error('Please add your name and a short message.');
            Session::flash('_old', Request::all());
            $this->redirect('/contact');
        }
        if ($phone === '' && $email === '') {
            flash_error('Add a phone number or email so we can reply.');
            Session::flash('_old', Request::all());
            $this->redirect('/contact');
        }

        $cms = new WebsiteService();
        if (!$cms->cmsReady()) {
            flash_error('Enquiries are not available yet. Please message us on WhatsApp.');
            $this->redirect('/contact');
        }

        try {
            $cms->saveEnquiry($name, $phone, $email, $message);
            flash_success('Thank you. We will get back to you shortly.');
        } catch (\Throwable $e) {
            flash_error('Could not send the enquiry. Please use WhatsApp instead.');
        }
        $this->redirect('/contact');
    }

    public function cmsPage(string $slug): void
    {
        $slug = slugify($slug);
        if ($slug === 'about') {
            $this->redirect('/about');
        }
        $cms = new WebsiteService();
        $site = $cms->settings();
        $page = $cms->pageBySlug($slug);
        if ($page === null) {
            \App\Core\Response::abort(404, 'Page not found');
        }
        $this->render('public/page', [
            'title' => (string) $page['title'],
            'seoTitle' => (string) ($page['seo_title'] ?: $page['title'] . ' · Shine Express'),
            'seoDescription' => (string) ($page['seo_description'] ?? $site['seo_description']),
            'page' => $page,
        ], $site);
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed> $site
     */
    private function render(string $view, array $data, array $site): void
    {
        $this->view($view, array_merge([
            'site' => $site,
            'waHref' => $site['whatsapp_href'],
            'bookHref' => Auth::role() === 'CUSTOMER' ? url('/book') : url('/register'),
        ], $data), 'layouts/marketing');
    }
}
