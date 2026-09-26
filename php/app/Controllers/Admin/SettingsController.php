<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Models\SiteSetting;

final class SettingsController
{
    public function edit(Request $request, array $params): void
    {
        Auth::requireOwner();
        View::render('admin/settings', [
            'pageTitle' => 'Site Settings',
            'settings' => (new SiteSetting())->get(),
        ], 'layouts/admin');
    }

    public function update(Request $request, array $params): void
    {
        Auth::requireOwnerAndCsrf($request);

        $interests = array_values(array_filter(array_map('trim', explode(',', (string) $request->post('interests', '')))));
        $interests = array_slice($interests, 0, 5);

        $socialLabels = (array) $request->post('social_label', []);
        $socialUrls = (array) $request->post('social_url', []);
        $socialLinks = [];
        foreach ($socialLabels as $index => $label) {
            $label = trim((string) $label);
            $url = trim((string) ($socialUrls[$index] ?? ''));
            if ($label === '' || $url === '') {
                continue;
            }
            if (!filter_var($url, FILTER_VALIDATE_URL) || !str_starts_with($url, 'https://')) {
                Session::set('flash_error', "Social link \"{$label}\" must use https.");
                Response::redirect(base_url('admin/settings'));
            }
            $socialLinks[] = ['label' => mb_substr($label, 0, 40), 'url' => $url];
        }
        $socialLinks = array_slice($socialLinks, 0, 6);

        $fields = [
            'site_title' => trim((string) $request->post('site_title', '')),
            'short_description' => trim((string) $request->post('short_description', '')),
            'author_display_name' => trim((string) $request->post('author_display_name', '')) ?: 'Hasan',
            'hero_greeting' => trim((string) $request->post('hero_greeting', '')),
            'intro_heading' => trim((string) $request->post('intro_heading', '')),
            'intro_body' => trim((string) $request->post('intro_body', '')),
            'interests_json' => json_encode($interests, JSON_UNESCAPED_UNICODE),
            'copyright_name' => trim((string) $request->post('copyright_name', '')) ?: 'Hasan',
            'social_links_json' => json_encode($socialLinks, JSON_UNESCAPED_UNICODE),
            'default_seo_title' => trim((string) $request->post('default_seo_title', '')),
            'default_seo_description' => trim((string) $request->post('default_seo_description', '')),
            'nav_home_label' => trim((string) $request->post('nav_home_label', '')) ?: 'Home',
            'nav_blog_label' => trim((string) $request->post('nav_blog_label', '')) ?: 'Blog',
            'nav_projects_label' => trim((string) $request->post('nav_projects_label', '')) ?: 'Projects',
        ];

        $errors = [];
        if ($fields['site_title'] === '' || mb_strlen($fields['site_title']) > 100) {
            $errors[] = 'Site title is required (max 100 characters).';
        }
        if ($fields['short_description'] === '' || mb_strlen($fields['short_description']) > 300) {
            $errors[] = 'Short description is required (max 300 characters).';
        }
        if ($fields['hero_greeting'] === '' || mb_strlen($fields['hero_greeting']) > 80) {
            $errors[] = 'Hero greeting is required (max 80 characters).';
        }
        if ($fields['intro_heading'] === '' || mb_strlen($fields['intro_heading']) > 120) {
            $errors[] = 'Intro heading is required (max 120 characters).';
        }
        if ($fields['intro_body'] === '' || mb_strlen($fields['intro_body']) > 400) {
            $errors[] = 'Intro body is required (max 400 characters).';
        }
        if ($fields['default_seo_title'] === '' || mb_strlen($fields['default_seo_title']) > 70) {
            $errors[] = 'Default SEO title is required (max 70 characters).';
        }
        if ($fields['default_seo_description'] === '' || mb_strlen($fields['default_seo_description']) > 170) {
            $errors[] = 'Default SEO description is required (max 170 characters).';
        }

        if ($errors !== []) {
            Session::set('flash_error', implode(' ', $errors));
            Response::redirect(base_url('admin/settings'));
        }

        (new SiteSetting())->update($fields);
        Session::set('flash_success', 'Settings saved.');
        Response::redirect(base_url('admin/settings'));
    }
}
