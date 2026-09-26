<?php

declare(strict_types=1);

use App\Controllers\Admin;
use App\Controllers\Public;
use App\Core\Request;
use App\Core\Router;

/**
 * Route handlers are closures so a controller class is only
 * instantiated (and therefore only needs to exist / autoload) at the
 * moment its specific route is actually dispatched — a request to a
 * public route never touches the admin controller classes at all.
 */

$router = new Router();

// --- Public ---------------------------------------------------------
$router->get('/', fn (Request $r, array $p) => (new Public\HomeController())->index($r, $p));
$router->get('/blog', fn (Request $r, array $p) => (new Public\BlogController())->index($r, $p));
$router->get('/blog/{slug}', fn (Request $r, array $p) => (new Public\BlogController())->show($r, $p));
$router->get('/projects', fn (Request $r, array $p) => (new Public\ProjectsController())->index($r, $p));
$router->get('/projects/{slug}', fn (Request $r, array $p) => (new Public\ProjectsController())->show($r, $p));
$router->get('/rss.xml', fn (Request $r, array $p) => (new Public\FeedController())->rss($r, $p));
$router->get('/sitemap.xml', fn (Request $r, array $p) => (new Public\FeedController())->sitemap($r, $p));
$router->get('/robots.txt', fn (Request $r, array $p) => (new Public\FeedController())->robots($r, $p));
$router->get('/api/health', fn (Request $r, array $p) => (new Public\HealthController())->check($r, $p));
$router->get('/preview', fn (Request $r, array $p) => (new Public\PreviewController())->enter($r, $p));
$router->get('/preview/exit', fn (Request $r, array $p) => (new Public\PreviewController())->exit($r, $p));
$router->get('/media/{filename}', fn (Request $r, array $p) => (new Public\MediaController())->deliver($r, $p));

// --- Admin -----------------------------------------------------------
$router->get('/admin/login', fn (Request $r, array $p) => (new Admin\AuthController())->loginForm($r, $p));
$router->post('/admin/login', fn (Request $r, array $p) => (new Admin\AuthController())->login($r, $p));
$router->post('/admin/logout', fn (Request $r, array $p) => (new Admin\AuthController())->logout($r, $p));
$router->get('/admin/forgot-password', fn (Request $r, array $p) => (new Admin\AuthController())->forgotForm($r, $p));
$router->post('/admin/forgot-password', fn (Request $r, array $p) => (new Admin\AuthController())->forgotSubmit($r, $p));
$router->get('/admin/reset-password', fn (Request $r, array $p) => (new Admin\AuthController())->resetForm($r, $p));
$router->post('/admin/reset-password', fn (Request $r, array $p) => (new Admin\AuthController())->resetSubmit($r, $p));

$router->get('/admin', fn (Request $r, array $p) => (new Admin\DashboardController())->index($r, $p));
$router->get('/admin/account', fn (Request $r, array $p) => (new Admin\AccountController())->edit($r, $p));
$router->post('/admin/account', fn (Request $r, array $p) => (new Admin\AccountController())->update($r, $p));

$router->get('/admin/posts', fn (Request $r, array $p) => (new Admin\PostController())->index($r, $p));
$router->get('/admin/posts/new', fn (Request $r, array $p) => (new Admin\PostController())->create($r, $p));
$router->post('/admin/posts/new', fn (Request $r, array $p) => (new Admin\PostController())->store($r, $p));
$router->get('/admin/posts/{id}/edit', fn (Request $r, array $p) => (new Admin\PostController())->edit($r, $p));
$router->post('/admin/posts/{id}/edit', fn (Request $r, array $p) => (new Admin\PostController())->update($r, $p));
$router->post('/admin/posts/{id}/delete', fn (Request $r, array $p) => (new Admin\PostController())->destroy($r, $p));
$router->post('/admin/posts/{id}/unpublish', fn (Request $r, array $p) => (new Admin\PostController())->unpublish($r, $p));
$router->get('/admin/posts/{id}/versions', fn (Request $r, array $p) => (new Admin\PostController())->versions($r, $p));
$router->post('/admin/posts/{id}/versions/{versionId}/restore', fn (Request $r, array $p) => (new Admin\PostController())->restoreVersion($r, $p));
$router->get('/admin/posts/{id}/preview-link', fn (Request $r, array $p) => (new Admin\PostController())->previewLink($r, $p));

$router->get('/admin/projects', fn (Request $r, array $p) => (new Admin\ProjectController())->index($r, $p));
$router->get('/admin/projects/new', fn (Request $r, array $p) => (new Admin\ProjectController())->create($r, $p));
$router->post('/admin/projects/new', fn (Request $r, array $p) => (new Admin\ProjectController())->store($r, $p));
$router->get('/admin/projects/{id}/edit', fn (Request $r, array $p) => (new Admin\ProjectController())->edit($r, $p));
$router->post('/admin/projects/{id}/edit', fn (Request $r, array $p) => (new Admin\ProjectController())->update($r, $p));
$router->post('/admin/projects/{id}/delete', fn (Request $r, array $p) => (new Admin\ProjectController())->destroy($r, $p));
$router->get('/admin/projects/{id}/versions', fn (Request $r, array $p) => (new Admin\ProjectController())->versions($r, $p));
$router->post('/admin/projects/{id}/versions/{versionId}/restore', fn (Request $r, array $p) => (new Admin\ProjectController())->restoreVersion($r, $p));
$router->get('/admin/projects/{id}/preview-link', fn (Request $r, array $p) => (new Admin\ProjectController())->previewLink($r, $p));

$router->get('/admin/media', fn (Request $r, array $p) => (new Admin\MediaController())->index($r, $p));
$router->post('/admin/media/upload', fn (Request $r, array $p) => (new Admin\MediaController())->upload($r, $p));
$router->post('/admin/media/{id}/visibility', fn (Request $r, array $p) => (new Admin\MediaController())->setVisibility($r, $p));
$router->post('/admin/media/{id}/update', fn (Request $r, array $p) => (new Admin\MediaController())->update($r, $p));
$router->post('/admin/media/{id}/delete', fn (Request $r, array $p) => (new Admin\MediaController())->destroy($r, $p));

$router->get('/admin/categories', fn (Request $r, array $p) => (new Admin\CategoryController())->index($r, $p));
$router->post('/admin/categories', fn (Request $r, array $p) => (new Admin\CategoryController())->store($r, $p));
$router->post('/admin/categories/{id}/update', fn (Request $r, array $p) => (new Admin\CategoryController())->update($r, $p));
$router->post('/admin/categories/{id}/delete', fn (Request $r, array $p) => (new Admin\CategoryController())->destroy($r, $p));

$router->get('/admin/tags', fn (Request $r, array $p) => (new Admin\TagController())->index($r, $p));
$router->post('/admin/tags', fn (Request $r, array $p) => (new Admin\TagController())->store($r, $p));
$router->post('/admin/tags/{id}/update', fn (Request $r, array $p) => (new Admin\TagController())->update($r, $p));
$router->post('/admin/tags/{id}/delete', fn (Request $r, array $p) => (new Admin\TagController())->destroy($r, $p));

$router->get('/admin/redirects', fn (Request $r, array $p) => (new Admin\RedirectController())->index($r, $p));
$router->post('/admin/redirects', fn (Request $r, array $p) => (new Admin\RedirectController())->store($r, $p));
$router->post('/admin/redirects/{id}/update', fn (Request $r, array $p) => (new Admin\RedirectController())->update($r, $p));
$router->post('/admin/redirects/{id}/delete', fn (Request $r, array $p) => (new Admin\RedirectController())->destroy($r, $p));

$router->get('/admin/settings', fn (Request $r, array $p) => (new Admin\SettingsController())->edit($r, $p));
$router->post('/admin/settings', fn (Request $r, array $p) => (new Admin\SettingsController())->update($r, $p));

$router->dispatch(new Request());
