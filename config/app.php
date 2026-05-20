<?php
/**
 * App URL helpers — production uses HTTP (InfinityFree free SSL can break sessions/forms).
 */

function isLocalAppHost(): bool {
    $host = strtolower($_SERVER['HTTP_HOST'] ?? '');
    $remoteAddr = $_SERVER['REMOTE_ADDR'] ?? '';

    if (in_array($remoteAddr, ['127.0.0.1', '::1'], true)) {
        return true;
    }

    return $host === 'localhost'
        || $host === '127.0.0.1'
        || strpos($host, 'localhost:') === 0
        || strpos($host, '127.0.0.1:') === 0;
}

function requestUsesHttps(): bool {
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        return true;
    }

    return strtolower($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
}

/** Redirect HTTPS requests to HTTP on production (skip localhost). */
function enforceHttpOnProduction(): void {
    if (PHP_SAPI === 'cli' || isLocalAppHost() || !requestUsesHttps()) {
        return;
    }

    $host = $_SERVER['HTTP_HOST'] ?? '';
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    header('Location: http://' . $host . $uri, true, 301);
    exit;
}

function getAppBaseUrl(): string {
    return 'http://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
}

function getAppUrl(string $path = ''): string {
    $path = ltrim($path, '/');
    $base = rtrim(getAppBaseUrl(), '/');

    return $path === '' ? $base . '/' : $base . '/' . $path;
}
