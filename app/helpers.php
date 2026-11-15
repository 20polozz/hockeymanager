<?php
/**
 * Funcions d'ajuda globals per a vistes i controladors.
 */
declare(strict_types=1);

use App\Core\App;
use App\Core\Csrf;
use App\Core\View;

/** Escapa text per a HTML. */
function e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** URL interna respectant la ruta base (/hockeymanager o domini propi). */
function url(string $path = '/', array $query = []): string
{
    $url = App::basePath() . '/' . ltrim($path, '/');
    if ($url === '') {
        $url = '/';
    }
    if ($query) {
        $url .= '?' . http_build_query($query);
    }
    return $url;
}

/** URL d'un recurs estàtic amb versió per evitar la memòria cau antiga. */
function asset(string $path): string
{
    $file = HM_ROOT . '/assets/' . ltrim($path, '/');
    $v = is_file($file) ? (string)filemtime($file) : '1';
    return url('assets/' . ltrim($path, '/')) . '?v=' . $v;
}

function redirect(string $path): void
{
    header('Location: ' . (preg_match('#^https?://#', $path) ? $path : url($path)));
    exit;
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(Csrf::token()) . '">';
}

/** Missatge flash per a la següent petició. */
function flash(string $type, string $message): void
{
    $_SESSION['hm_flash'][] = ['type' => $type, 'message' => $message];
}

/** @return array<int,array{type:string,message:string}> */
function take_flashes(): array
{
    $f = $_SESSION['hm_flash'] ?? [];
    unset($_SESSION['hm_flash']);
    return is_array($f) ? $f : [];
}

/** Valor antic d'un formulari després d'un error de validació. */
function old(string $key, string $default = ''): string
{
    return (string)($_SESSION['hm_old'][$key] ?? $default);
}

function remember_input(array $data): void
{
    $_SESSION['hm_old'] = $data;
}

function forget_input(): void
{
    unset($_SESSION['hm_old']);
}

/** Renderitza un component reutilitzable de app/Views/components. */
function component(string $name, array $props = []): string
{
    return View::partial('components/' . $name, $props);
}

function is_current_path(string $path): bool
{
    $current = App::path();
    $path = '/' . trim($path, '/');
    return $path === '/' ? $current === '/' : ($current === $path || strpos($current, $path . '/') === 0);
}

/**
 * Icona SVG de línia (estil Lucide, llicència ISC).
 */
function icon(string $name, string $class = 'icon'): string
{
    static $icons = null;
    if ($icons === null) {
        $icons = require HM_ROOT . '/app/icons.php';
    }
    $body = $icons[$name] ?? $icons['circle'];
    return '<svg class="' . e($class) . '" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $body . '</svg>';
}

/** Inicials per a l'avatar ("Marc Puig" → "MP"). */
function initials(string $name): string
{
    $parts = preg_split('/\s+/u', trim($name)) ?: [];
    $out = '';
    foreach (array_slice($parts, 0, 2) as $p) {
        $out .= mb_strtoupper(mb_substr($p, 0, 1));
    }
    return $out !== '' ? $out : '?';
}

/**
 * Dates en català.
 *   'day'   → "dissabte 21 de novembre"
 *   'short' → "ds. 21 nov."
 *   'time'  → "18:30"
 *   'full'  → "dissabte 21 de novembre · 18:30"
 */
function fdate(?string $datetime, string $format = 'short'): string
{
    if (!$datetime) {
        return '—';
    }
    $ts = strtotime($datetime);
    if ($ts === false) {
        return '—';
    }
    $days = ['diumenge', 'dilluns', 'dimarts', 'dimecres', 'dijous', 'divendres', 'dissabte'];
    $daysShort = ['dg.', 'dl.', 'dt.', 'dc.', 'dj.', 'dv.', 'ds.'];
    $months = ['gener', 'febrer', 'març', 'abril', 'maig', 'juny', 'juliol', 'agost', 'setembre', 'octubre', 'novembre', 'desembre'];
    $monthsShort = ['gen.', 'febr.', 'març', 'abr.', 'maig', 'juny', 'jul.', 'ag.', 'set.', 'oct.', 'nov.', 'des.'];

    $w = (int)date('w', $ts);
    $d = (int)date('j', $ts);
    $m = (int)date('n', $ts) - 1;
    $month = $months[$m];
    $of = preg_match('/^[aeiouàèéíòóú]/u', $month) ? "d'" : 'de ';

    switch ($format) {
        case 'time':
            return date('H:i', $ts);
        case 'day':
            return $days[$w] . ' ' . $d . ' ' . $of . $month;
        case 'full':
            return $days[$w] . ' ' . $d . ' ' . $of . $month . ' · ' . date('H:i', $ts);
        case 'daynum':
            return (string)$d;
        case 'monthshort':
            return $monthsShort[$m];
        default:
            return $daysShort[$w] . ' ' . $d . ' ' . $monthsShort[$m];
    }
}

/** Temps relatiu: "fa 5 min", "fa 2 dies". */
function ago(?string $datetime): string
{
    $ts = $datetime ? strtotime($datetime) : false;
    if ($ts === false) {
        return '';
    }
    $diff = time() - $ts;
    if ($diff < 60) {
        return 'ara mateix';
    }
    if ($diff < 3600) {
        return 'fa ' . (int)floor($diff / 60) . ' min';
    }
    if ($diff < 86400) {
        $h = (int)floor($diff / 3600);
        return 'fa ' . $h . ($h === 1 ? ' hora' : ' hores');
    }
    $dd = (int)floor($diff / 86400);
    if ($dd < 30) {
        return 'fa ' . $dd . ($dd === 1 ? ' dia' : ' dies');
    }
    return fdate($datetime, 'short');
}

/** "Bon dia" / "Bona tarda" / "Bona nit" segons l'hora. */
function greeting(): string
{
    $h = (int)date('G');
    if ($h >= 6 && $h < 14) {
        return 'Bon dia';
    }
    if ($h >= 14 && $h < 21) {
        return 'Bona tarda';
    }
    return 'Bona nit';
}

function first_name(string $name): string
{
    $parts = preg_split('/\s+/u', trim($name)) ?: [''];
    return (string)$parts[0];
}
