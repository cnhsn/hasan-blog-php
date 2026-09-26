<?php

declare(strict_types=1);

namespace App\Controllers\Public;

use App\Core\Request;
use App\Core\View;
use App\Models\SiteSetting;

final class HomeController
{
    public function index(Request $request, array $params): void
    {
        $settings = (new SiteSetting())->get();

        View::render('public/home', [
            'settings' => $settings,
            'pageTitle' => $settings['site_title'],
            'metaDescription' => $settings['short_description'],
            'canonical' => base_url('/'),
        ]);
    }
}
