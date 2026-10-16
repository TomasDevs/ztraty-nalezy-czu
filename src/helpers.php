<?php

declare(strict_types=1);

function env(string $key, mixed $default = null): mixed
{
    return $_ENV[$key] ?? $default;
}

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function base_path(): string
{
    return rtrim((string) env('BASE_PATH', ''), '/');
}

function url(string $path = '/', array $query = []): string
{
    $query = array_filter($query, fn ($value) => $value !== null && $value !== '');

    return base_path() . '/' . ltrim($path, '/') . ($query ? '?' . http_build_query($query) : '');
}

function absolute_url(string $path = '/'): string
{
    return rtrim((string) env('APP_URL', ''), '/') . url($path);
}

function current_path(): string
{
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $base = base_path();

    if ($base !== '' && str_starts_with($path, $base)) {
        $path = substr($path, strlen($base));
    }

    return '/' . trim($path, '/');
}

function csrf_field(): string
{
    return '<input type="hidden" name="_token" value="' . e(App\Core\App::csrf()->token()) . '">';
}

function auth(): App\Auth\Auth
{
    return App\Core\App::auth();
}

function current_uri(): string
{
    $query = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_QUERY);

    return current_path() . ($query ? "?{$query}" : '');
}

/**
 * Only internal paths are allowed as a redirect target, anything else falls back to the homepage.
 */
function safe_redirect_path(?string $path): string
{
    if ($path === null || !str_starts_with($path, '/') || str_starts_with($path, '//') || str_starts_with($path, '/\\')) {
        return '/';
    }

    return $path;
}

function render(string $template, array $data = []): string
{
    extract($data, EXTR_SKIP);
    ob_start();
    require ROOT_PATH . "/templates/{$template}.php";

    return (string) ob_get_clean();
}

function view(string $template, array $data = [], string $layout = 'layout'): void
{
    echo render($layout, [...$data, 'content' => render($template, $data)]);
}

function json(mixed $data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

function redirect(string $path, int $status = 302): never
{
    throw new App\Core\Redirect(url($path), $status);
}

function abort(int $status): never
{
    throw new App\Core\HttpError($status);
}

function render_error(int $status): string
{
    if (str_starts_with(current_path(), '/api/')) {
        header('Content-Type: application/json; charset=utf-8');

        return json_encode(['error' => $status]);
    }

    $titles = [403 => 'Přístup odepřen', 404 => 'Stránka nenalezena', 419 => 'Platnost formuláře vypršela'];
    $title = $titles[$status] ?? 'Něco se pokazilo';

    return render('layout', [
        'title' => $title,
        'noindex' => true,
        'content' => render("errors/{$status}"),
    ]);
}

/**
 * Lowercase text without diacritics, so "klice" matches "Klíče".
 * iconv transliteration differs between platforms, so letters are mapped explicitly.
 */
function fold(string $text): string
{
    return strtr(mb_strtolower($text), [
        'á' => 'a', 'ä' => 'a', 'č' => 'c', 'ď' => 'd', 'é' => 'e', 'ě' => 'e', 'í' => 'i', 'ň' => 'n',
        'ó' => 'o', 'ö' => 'o', 'ř' => 'r', 'š' => 's', 'ť' => 't', 'ú' => 'u', 'ů' => 'u', 'ü' => 'u',
        'ý' => 'y', 'ž' => 'z',
    ]);
}

function slugify(string $text): string
{
    return trim(preg_replace('/[^a-z0-9]+/', '-', fold($text)), '-');
}

/**
 * JSON-LD script tag; JSON_HEX_TAG keeps "</script>" in data from closing the tag.
 */
function json_ld(array $data): string
{
    $json = json_encode(
        ['@context' => 'https://schema.org', ...$data],
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG
    );

    return "<script type=\"application/ld+json\">{$json}</script>";
}

function breadcrumbs_ld(array $breadcrumbs): array
{
    return [
        '@type' => 'BreadcrumbList',
        'itemListElement' => array_map(
            fn (array $crumb, int $i) => [
                '@type' => 'ListItem',
                'position' => $i + 1,
                'name' => $crumb[0],
                ...($crumb[1] !== null ? ['item' => absolute_url($crumb[1])] : []),
            ],
            $breadcrumbs,
            array_keys($breadcrumbs)
        ),
    ];
}

function format_date(string $date): string
{
    return date('j. n. Y', strtotime($date));
}

function item_path(array $item): string
{
    return "/inzerat/{$item['id']}/{$item['slug']}";
}

function item_url(array $item): string
{
    return url(item_path($item));
}

/**
 * Documents (ISIC, ID cards) never show a photo, it would expose personal data
 * and let anyone claim the item.
 */
function is_private_item(array $item): bool
{
    return $item['category']['slug'] === 'doklady';
}

/**
 * Photos are stored as WebP in two sizes: {photo}.webp and {photo}-thumb.webp.
 */
function item_photo_path(array $item, bool $thumb = false): ?string
{
    $photo = $item['photo'] ?? null;

    if (!$photo || is_private_item($item)) {
        return null;
    }

    return "/{$photo}" . ($thumb ? '-thumb' : '') . '.webp';
}

function item_photo_url(array $item, bool $thumb = false): ?string
{
    $path = item_photo_path($item, $thumb);

    return $path ? url($path) : null;
}

function item_code(array $item): string
{
    return sprintf('#%03d', $item['id']);
}

/**
 * Czech plural forms: 1 inzerát, 2–4 inzeráty, 0 or 5+ inzerátů.
 */
function plural_form(int $count, string $one, string $few, string $many): string
{
    return match (true) {
        $count === 1 => $one,
        $count >= 2 && $count <= 4 => $few,
        default => $many,
    };
}

function plural(int $count, string $one, string $few, string $many): string
{
    return $count . ' ' . plural_form($count, $one, $few, $many);
}

function items_count(int $count): string
{
    return plural($count, 'inzerát', 'inzeráty', 'inzerátů');
}

/**
 * Outputs Vite asset tags: dev server when public/hot exists, built files otherwise.
 */
function vite(string $entry = 'assets/js/main.js'): string
{
    $hotFile = PUBLIC_PATH . '/hot';

    if (is_file($hotFile)) {
        $origin = trim((string) file_get_contents($hotFile));

        return sprintf(
            '<script type="module" src="%1$s/@vite/client"></script><script type="module" src="%1$s/%2$s"></script>',
            e($origin),
            e($entry)
        );
    }

    static $manifest = null;
    $manifest ??= json_decode((string) @file_get_contents(PUBLIC_PATH . '/build/.vite/manifest.json'), true) ?? [];

    $chunk = $manifest[$entry] ?? null;

    if ($chunk === null) {
        return '<!-- Vite manifest missing: run npm run build -->';
    }

    $tags = array_map(
        fn (string $css) => sprintf('<link rel="stylesheet" href="%s">', e(url("/build/{$css}"))),
        $chunk['css'] ?? []
    );
    $tags[] = sprintf('<script type="module" src="%s"></script>', e(url("/build/{$chunk['file']}")));

    return implode('', $tags);
}
