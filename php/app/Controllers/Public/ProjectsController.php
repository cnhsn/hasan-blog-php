<?php

declare(strict_types=1);

namespace App\Controllers\Public;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Models\Project;
use App\Models\Redirect;
use App\Models\SiteSetting;

final class ProjectsController
{
    public function index(Request $request, array $params): void
    {
        $page = max(1, (int) $request->query('page', 1));
        $result = (new Project())->listPublished($page);

        View::render('public/projects-index', [
            'projects' => $result['docs'],
            'totalPages' => $result['totalPages'],
            'page' => $result['page'],
            'settings' => (new SiteSetting())->get(),
            'pageTitle' => 'Projects',
            'metaDescription' => 'A place for experiments, side projects, and ideas turned into something useful.',
            'canonical' => base_url('projects'),
        ]);
    }

    public function show(Request $request, array $params): void
    {
        $slug = $params['slug'];
        $isPreview = Session::isOwner() && Session::get('preview_mode') === true;

        $project = (new Project())->findBySlug($slug, $isPreview);

        if ($project === null) {
            $redirect = (new Redirect())->findBySource('/projects/' . $slug);
            if ($redirect !== null) {
                Response::permanentRedirect(base_url(ltrim($redirect['target_path'], '/')));
            }
            Response::notFound();
            return;
        }

        if ($isPreview) {
            Response::noStore();
        }

        View::render('public/projects-show', [
            'project' => $project,
            'isPreview' => $isPreview,
            'settings' => (new SiteSetting())->get(),
            'pageTitle' => $project['seo_title'] ?: $project['title'],
            'metaDescription' => $project['seo_description'] ?: $project['short_description'],
            'canonical' => base_url('projects/' . $project['slug']),
        ]);
    }
}
