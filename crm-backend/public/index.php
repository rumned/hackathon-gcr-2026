<?php

declare(strict_types=1);

use App\Alerts;
use App\Auth;
use App\Bulk;
use App\Config;
use App\Dashlets;
use App\Favorites;
use App\Http\HttpError;
use App\Http\Request;
use App\Http\Response;
use App\Merge;
use App\Modules;
use App\Preferences;
use App\Records;
use App\Router;
use App\Search;
use App\Subpanels;
use App\TargetLists;
use App\Tracker;
use App\Users;

require dirname(__DIR__) . '/src/bootstrap.php';

$router = new Router();

// ---------------------------------------------------------------- Login screen, user menu
$router->post('/api/auth/login', static function (Request $request): array {
    $body = $request->json();

    return Auth::login((string) ($body['user_name'] ?? ''), (string) ($body['password'] ?? ''));
}, public: true);

$router->post('/api/auth/logout', static function (): Response {
    Auth::logout();

    return Response::empty();
}, public: true);

$router->get('/api/auth/me', static fn (): array => Auth::user());

// Users for the "Assigned to" Select button and the Employees page
$router->get('/api/users', static fn (Request $request): array => Users::list($request->query));

// ---------------------------------------------------------------- Top bar
$router->get('/api/search', static fn (Request $request): array => Search::run($request->query));

$router->get('/api/alerts', static fn (Request $request): array => Alerts::list($request->query));

$router->post('/api/alerts/{id}/read', static function (Request $request, array $p): Response {
    Alerts::markRead($p['id']);

    return Response::empty();
});

// ---------------------------------------------------------------- Sidebar
$router->get('/api/recently-viewed', static fn (Request $request): array => Tracker::recent($request->query));

$router->get('/api/favorites', static fn (Request $request): array => Favorites::list($request->query));

$router->post('/api/favorites', static function (Request $request): Response {
    Favorites::add($request->json());

    return Response::empty();
});

$router->delete('/api/favorites/{module}/{id}', static function (Request $request, array $p): Response {
    Favorites::remove($p['module'], $p['id']);

    return Response::empty();
});

// ---------------------------------------------------------------- Home dashboard
$router->get('/api/dashlets/{name}', static fn (Request $request, array $p): array => Dashlets::get($p['name'], $request->query));

// Dashboard tabs / dashlet layout, list view columns
$router->get('/api/preferences/{category}', static fn (Request $request, array $p): array => Preferences::get($p['category']));

$router->put('/api/preferences/{category}', static fn (Request $request, array $p): array => Preferences::put($p['category'], $request->json()));

// ---------------------------------------------------------------- Edit view dropdowns and field rules
$router->get('/api/meta/{module}', static fn (Request $request, array $p): array => Modules::describe($p['module']));

// ---------------------------------------------------------------- Target lists ("Add to target list")
$router->get('/api/prospect_lists/{id}/records', static fn (Request $request, array $p): array => TargetLists::members($p['id']));

$router->post('/api/prospect_lists/{id}/records', static fn (Request $request, array $p): array => TargetLists::add($p['id'], $request->json()));

// ---------------------------------------------------------------- List view (any module)
$router->get('/api/{module}', static fn (Request $request, array $p): array => Records::list($p['module'], $request->query));

$router->post('/api/{module}', static fn (Request $request, array $p): Response => Response::json(Records::create($p['module'], $request->json()), 201));

$router->get('/api/{module}/export', static fn (Request $request, array $p): Response => Bulk::export($p['module'], $request->query));

$router->post('/api/{module}/import', static fn (Request $request, array $p): array => Bulk::import($p['module'], $request->json()));

$router->post('/api/{module}/mass-update', static fn (Request $request, array $p): array => Bulk::update($p['module'], $request->json()));

$router->post('/api/{module}/mass-delete', static fn (Request $request, array $p): array => Bulk::delete($p['module'], $request->json()));

$router->post('/api/{module}/merge', static fn (Request $request, array $p): array => Merge::run($p['module'], $request->json()));

// ---------------------------------------------------------------- Detail and edit views
$router->get('/api/{module}/{id}', static function (Request $request, array $p): array {
    $record = Records::get($p['module'], $p['id']);
    Tracker::record($p['module'], $p['id'], (string) $record['name']);
    $record['is_favorite'] = Favorites::isFavorite(Modules::get($p['module'])['label'], $p['id']);

    return $record;
});

$router->put('/api/{module}/{id}', static fn (Request $request, array $p): array => Records::update($p['module'], $p['id'], $request->json()));

$router->delete('/api/{module}/{id}', static function (Request $request, array $p): Response {
    Records::delete($p['module'], $p['id']);

    return Response::empty();
});

$router->get('/api/{module}/{id}/duplicates', static fn (Request $request, array $p): array => Records::duplicates($p['module'], $p['id']));

$router->get('/api/{module}/{id}/changelog', static fn (Request $request, array $p): array => Records::changeLog($p['module'], $p['id']));

// Subpanels
$router->get('/api/{module}/{id}/subpanels/{subpanel}', static fn (Request $request, array $p): array => Subpanels::list($p['module'], $p['id'], $p['subpanel'], $request->query));

$router->post('/api/{module}/{id}/subpanels/{subpanel}', static function (Request $request, array $p): Response {
    Subpanels::link($p['module'], $p['id'], $p['subpanel'], $request->json());

    return Response::empty();
});

$router->delete('/api/{module}/{id}/subpanels/{subpanel}/{recordId}', static function (Request $request, array $p): Response {
    Subpanels::unlink($p['module'], $p['id'], $p['subpanel'], $p['recordId']);

    return Response::empty();
});

// ---------------------------------------------------------------- Run

$request = Request::fromGlobals();

$corsHeaders = [];
$allowedOrigin = (string) Config::get('cors_origin', '');
if ($allowedOrigin !== '' && ($_SERVER['HTTP_ORIGIN'] ?? '') === $allowedOrigin) {
    $corsHeaders = [
        'Access-Control-Allow-Origin' => $allowedOrigin,
        'Access-Control-Allow-Credentials' => 'true',
        'Access-Control-Allow-Headers' => 'Content-Type',
        'Access-Control-Allow-Methods' => 'GET, POST, PUT, DELETE, OPTIONS',
        'Vary' => 'Origin',
    ];
}

try {
    if ($request->method === 'OPTIONS') {
        $response = Response::empty();
    } elseif (in_array($request->method, ['POST', 'PUT'], true) && !$request->isJson()) {
        // Requiring JSON also stops plain HTML forms on other sites from posting here.
        throw new HttpError(415, 'Send the body as JSON with the header Content-Type: application/json');
    } else {
        Auth::startSession();
        $response = $router->dispatch($request);
    }
} catch (HttpError $e) {
    $response = Response::json(['error' => $e->getMessage()] + $e->details, $e->status);
} catch (Throwable $e) {
    error_log((string) $e);
    $response = Response::json(['error' => Config::get('debug') ? $e->getMessage() : 'Server error'], 500);
}

$response->send($corsHeaders);
