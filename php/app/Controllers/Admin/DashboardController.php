<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Request;
use App\Core\View;
use App\Models\Media;
use App\Models\Post;
use App\Models\Project;

final class DashboardController
{
    public function index(Request $request, array $params): void
    {
        Auth::requireOwner();

        $posts = (new Post())->all();
        $projects = (new Project())->all();

        View::render('admin/dashboard', [
            'pageTitle' => 'Dashboard',
            'postCount' => count($posts),
            'publishedPostCount' => count(array_filter($posts, static fn ($p) => $p['status'] === 'published')),
            'projectCount' => count($projects),
            'publishedProjectCount' => count(array_filter($projects, static fn ($p) => $p['status'] === 'published')),
            'mediaCount' => count((new Media())->all()),
            'recentPosts' => array_slice($posts, 0, 5),
        ], 'layouts/admin');
    }
}
