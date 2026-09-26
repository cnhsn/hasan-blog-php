<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Models\Media;
use App\Models\Project;
use App\Models\Redirect;
use App\Security\PreviewToken;
use App\Security\Sanitizer;
use App\Security\Slug;

final class ProjectController
{
    public function index(Request $request, array $params): void
    {
        Auth::requireOwner();
        View::render('admin/projects-index', [
            'pageTitle' => 'Projects',
            'projects' => (new Project())->all(),
        ], 'layouts/admin');
    }

    public function create(Request $request, array $params): void
    {
        Auth::requireOwner();
        $this->renderForm(null);
    }

    public function edit(Request $request, array $params): void
    {
        Auth::requireOwner();
        $project = (new Project())->find((int) $params['id']);
        if ($project === null) {
            Response::notFound();
            return;
        }
        $this->renderForm($project);
    }

    private function renderForm(?array $project): void
    {
        View::render('admin/project-form', [
            'pageTitle' => $project === null ? 'New project' : 'Edit project',
            'project' => $project,
            'coverMedia' => $project && $project['cover_media_id'] ? (new Media())->find((int) $project['cover_media_id']) : null,
            'seoMedia' => $project && $project['seo_share_media_id'] ? (new Media())->find((int) $project['seo_share_media_id']) : null,
            'versions' => $project ? (new Project())->versions((int) $project['id']) : [],
        ], 'layouts/admin');
    }

    public function store(Request $request, array $params): void
    {
        Auth::requireOwnerAndCsrf($request);
        $this->save($request, null);
    }

    public function update(Request $request, array $params): void
    {
        Auth::requireOwnerAndCsrf($request);
        $this->save($request, (int) $params['id']);
    }

    private function save(Request $request, ?int $id): void
    {
        $model = new Project();
        $existing = $id !== null ? $model->findRaw($id) : null;

        $title = trim((string) $request->post('title', ''));
        $slug = trim((string) $request->post('slug', '')) ?: Slug::slugify($title);
        $shortDescription = trim((string) $request->post('short_description', ''));
        $caseStudyHtml = Sanitizer::clean((string) $request->post('case_study_html', ''));
        $problemHtml = Sanitizer::clean((string) $request->post('problem_html', '')) ?: null;
        $approachHtml = Sanitizer::clean((string) $request->post('approach_html', '')) ?: null;
        $resultsHtml = Sanitizer::clean((string) $request->post('results_html', '')) ?: null;
        $coverMediaId = (int) $request->post('cover_media_id', 0) ?: null;
        $repositoryUrl = trim((string) $request->post('repository_url', '')) ?: null;
        $liveUrl = trim((string) $request->post('live_url', '')) ?: null;
        $lifecycleStatus = (string) $request->post('lifecycle_status', 'idea');
        $featured = $request->post('featured') === '1' ? 1 : 0;
        $startDate = trim((string) $request->post('start_date', ''));
        $endDate = trim((string) $request->post('end_date', '')) ?: null;
        $seoTitle = trim((string) $request->post('seo_title', '')) ?: null;
        $seoDescription = trim((string) $request->post('seo_description', '')) ?: null;
        $seoShareMediaId = (int) $request->post('seo_share_media_id', 0) ?: null;
        $intendedStatus = $request->post('intended_status') === 'published' ? 'published' : 'draft';

        $technologies = array_values(array_filter(array_map('trim', explode(',', (string) $request->post('technologies', '')))));
        $technologies = array_slice($technologies, 0, 12);

        $galleryMediaIds = array_map('intval', (array) $request->post('gallery_media_ids', []));
        $galleryCaptions = [];
        foreach ((array) $request->post('gallery_captions', []) as $mediaId => $caption) {
            $galleryCaptions[(int) $mediaId] = trim((string) $caption) ?: null;
        }
        $galleryMediaIds = array_slice(array_unique($galleryMediaIds), 0, 12);

        $errors = [];
        if ($title === '' || mb_strlen($title) > 160) {
            $errors[] = 'Title is required (max 160 characters).';
        }
        $slugCheck = Slug::validate($slug);
        if ($slugCheck !== true) {
            $errors[] = $slugCheck;
        } elseif ($this->slugTakenByOther($slug, $id)) {
            $errors[] = 'That slug is already used by another project.';
        }
        if ($shortDescription === '' || mb_strlen($shortDescription) > 320) {
            $errors[] = 'Short description is required (max 320 characters).';
        }
        if ($caseStudyHtml === '') {
            $errors[] = 'Case study content is required.';
        }
        if (!in_array($lifecycleStatus, ['idea', 'in-progress', 'completed', 'archived'], true)) {
            $errors[] = 'Invalid lifecycle status.';
        }
        if ($startDate === '' || !strtotime($startDate)) {
            $errors[] = 'A valid start date is required.';
        } elseif ($endDate !== null && strtotime($endDate) < strtotime($startDate)) {
            $errors[] = 'End date cannot precede start date.';
        }
        foreach ([$repositoryUrl, $liveUrl] as $url) {
            if ($url !== null && !filter_var($url, FILTER_VALIDATE_URL)) {
                $errors[] = 'Repository/live URL must be a valid http(s) URL.';
            }
        }

        if ($intendedStatus === 'published') {
            $mediaIds = array_filter(array_merge([$coverMediaId, $seoShareMediaId], $galleryMediaIds));
            $nonPublic = $this->nonPublicMedia($mediaIds);
            if ($nonPublic !== []) {
                $errors[] = 'Cannot publish: the following referenced media must be marked public first: ' . implode(', ', $nonPublic) . '.';
            }
        }

        if ($errors !== []) {
            Session::set('flash_error', implode(' ', $errors));
            Response::redirect(base_url($id === null ? 'admin/projects/new' : "admin/projects/{$id}/edit"));
        }

        $input = [
            'title' => $title,
            'slug' => $slug,
            'short_description' => $shortDescription,
            'case_study_html' => $caseStudyHtml,
            'problem_html' => $problemHtml,
            'approach_html' => $approachHtml,
            'results_html' => $resultsHtml,
            'cover_media_id' => $coverMediaId,
            'technologies_json' => json_encode($technologies, JSON_UNESCAPED_UNICODE),
            'repository_url' => $repositoryUrl,
            'live_url' => $liveUrl,
            'lifecycle_status' => $lifecycleStatus,
            'featured' => $featured,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'seo_title' => $seoTitle,
            'seo_description' => $seoDescription,
            'seo_share_media_id' => $seoShareMediaId,
        ];

        $wasPublished = $existing !== null && $existing['status'] === 'published';
        $oldSlug = $existing['slug'] ?? null;

        $newId = $model->save($id, $input, $galleryMediaIds, $galleryCaptions, $intendedStatus);

        if ($wasPublished && $intendedStatus !== 'draft' && $oldSlug !== null && $oldSlug !== $slug) {
            (new Redirect())->recordRename("/projects/{$oldSlug}", "/projects/{$slug}");
        }

        Session::set('flash_success', $intendedStatus === 'published' ? 'Project published.' : 'Draft saved.');
        Response::redirect(base_url("admin/projects/{$newId}/edit"));
    }

    private function slugTakenByOther(string $slug, ?int $excludeId): bool
    {
        $existing = (new Project())->findBySlug($slug, true);
        return $existing !== null && (int) $existing['id'] !== $excludeId;
    }

    private function nonPublicMedia(array $mediaIds): array
    {
        $mediaModel = new Media();
        $names = [];
        foreach (array_unique($mediaIds) as $id) {
            $media = $mediaModel->find((int) $id);
            if ($media !== null && $media['visibility'] !== 'public') {
                $names[] = $media['original_filename'];
            }
        }
        return $names;
    }

    public function unpublish(Request $request, array $params): void
    {
        Auth::requireOwnerAndCsrf($request);
        $id = (int) $params['id'];
        $project = (new Project())->find($id);
        if ($project === null) {
            Response::notFound();
            return;
        }

        (new Project())->unpublish($id);
        (new Redirect())->deactivateTargeting('/projects/' . $project['slug']);

        Session::set('flash_success', 'Project unpublished. It is no longer visible to visitors.');
        Response::redirect(base_url("admin/projects/{$id}/edit"));
    }

    public function destroy(Request $request, array $params): void
    {
        Auth::requireOwnerAndCsrf($request);
        $id = (int) $params['id'];
        $project = (new Project())->find($id);

        if ($project !== null && $project['status'] === 'published') {
            (new Redirect())->deactivateTargeting('/projects/' . $project['slug']);
        }

        (new Project())->delete($id);
        Session::set('flash_success', 'Project deleted.');
        Response::redirect(base_url('admin/projects'));
    }

    public function versions(Request $request, array $params): void
    {
        Auth::requireOwner();
        $id = (int) $params['id'];
        $project = (new Project())->find($id);
        if ($project === null) {
            Response::notFound();
            return;
        }

        View::render('admin/project-versions', [
            'pageTitle' => 'Version history: ' . $project['title'],
            'project' => $project,
            'versions' => (new Project())->versions($id),
        ], 'layouts/admin');
    }

    public function restoreVersion(Request $request, array $params): void
    {
        Auth::requireOwnerAndCsrf($request);
        $projectId = (int) $params['id'];
        $versionId = (int) $params['versionId'];

        (new Project())->restoreVersion($projectId, $versionId);
        Session::set('flash_success', 'Version restored into a draft. Publish it to make it live.');
        Response::redirect(base_url("admin/projects/{$projectId}/edit"));
    }

    public function previewLink(Request $request, array $params): void
    {
        Auth::requireOwner();
        $project = (new Project())->find((int) $params['id']);
        if ($project === null) {
            Response::notFound();
            return;
        }
        $token = PreviewToken::create('projects', $project['slug']);
        Response::redirect(base_url('preview?collection=projects&slug=' . urlencode($project['slug']) . '&token=' . urlencode($token)));
    }
}
