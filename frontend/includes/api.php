<?php
// Calls the CRM backend API (crm-backend). The backend's login cookie is kept in this
// frontend's PHP session, so the browser only talks to the frontend.

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$crmConfig = require dirname(__DIR__) . '/config.php';
define('API_URL', rtrim((string) $crmConfig['api_url'], '/'));

final class ApiError extends RuntimeException
{
    public function __construct(public readonly int $status, string $message)
    {
        parent::__construct($message);
    }
}

/**
 * One request to the API. Returns ['status' => HTTP status, 'data' => decoded JSON or null].
 * Status 0 means the backend could not be reached.
 */
function api_request(string $method, string $path, ?array $body = null, array $query = []): array
{
    $url = API_URL . $path . ($query ? '?' . http_build_query($query) : '');
    $headers = ['Accept: application/json'];
    $cookies = $_SESSION['api_cookies'] ?? [];
    if ($cookies) {
        $pairs = array_map(static fn ($name, $value) => "$name=$value", array_keys($cookies), $cookies);
        $headers[] = 'Cookie: ' . implode('; ', $pairs);
    }
    $http = ['method' => $method, 'ignore_errors' => true, 'timeout' => 15];
    if ($body !== null) {
        $headers[] = 'Content-Type: application/json';
        $http['content'] = json_encode($body === [] ? new stdClass() : $body);
    }
    $http['header'] = implode("\r\n", $headers);

    $raw = @file_get_contents($url, false, stream_context_create(['http' => $http]));
    $responseHeaders = $http_response_header ?? [];
    if ($raw === false && !$responseHeaders) {
        return ['status' => 0, 'data' => null];
    }

    $status = 0;
    foreach ($responseHeaders as $line) {
        if (preg_match('#^HTTP/\S+\s+(\d{3})#', $line, $m)) {
            $status = (int) $m[1];
        } elseif (stripos($line, 'Set-Cookie:') === 0) {
            api_store_cookie(substr($line, strlen('Set-Cookie:')));
        }
    }

    return ['status' => $status, 'data' => ($raw === false || $raw === '') ? null : json_decode($raw, true)];
}

/** Keeps (or forgets) a cookie the backend set. */
function api_store_cookie(string $header): void
{
    $pair = trim(explode(';', $header, 2)[0]);
    [$name, $value] = array_pad(explode('=', $pair, 2), 2, '');
    $expired = preg_match('/expires=([^;]+)/i', $header, $m) && strtotime($m[1]) < time();
    if ($value === '' || $value === 'deleted' || $expired) {
        unset($_SESSION['api_cookies'][$name]);
    } else {
        $_SESSION['api_cookies'][$name] = $value;
    }
}

function api_logged_in(): bool
{
    return !empty($_SESSION['api_cookies']) && isset($_SESSION['user']);
}

function api_forget_login(): void
{
    unset($_SESSION['api_cookies'], $_SESSION['user']);
}

/** The backend no longer accepts the login: back to the login page. */
function api_login_expired(): never
{
    api_forget_login();
    header('Location: login.php?expired=1');
    exit;
}

function api_unreachable(): ApiError
{
    return new ApiError(0, "Can't reach the CRM backend at " . API_URL . '. Check that it is running and that api_url in frontend/config.php points to it.');
}

/**
 * GET for data a page needs. Not logged in: redirects to login. 404: returns null.
 * Other errors throw ApiError (shown by the error page in bootstrap.php).
 */
function api_get(string $path, array $query = []): ?array
{
    $response = api_request('GET', $path, null, $query);
    if ($response['status'] === 0) {
        throw api_unreachable();
    }
    if ($response['status'] === 401) {
        api_login_expired();
    }
    if ($response['status'] === 404) {
        return null;
    }
    if ($response['status'] >= 400) {
        throw new ApiError($response['status'], (string) ($response['data']['error'] ?? 'The backend returned an error.'));
    }

    return $response['data'];
}

/**
 * POST / PUT / DELETE from a form.
 * Returns ['ok' => bool, 'data' => response, 'errors' => field => message, 'message' => error text].
 */
function api_send(string $method, string $path, array $body = []): array
{
    $response = api_request($method, $path, $method === 'DELETE' ? null : $body);
    if ($response['status'] === 0) {
        throw api_unreachable();
    }
    if ($response['status'] === 401) {
        api_login_expired();
    }
    $data = $response['data'];

    return [
        'ok' => $response['status'] >= 200 && $response['status'] < 300,
        'data' => $data,
        'errors' => is_array($data['errors'] ?? null) ? $data['errors'] : [],
        'message' => (string) ($data['error'] ?? ''),
    ];
}
