<?php

if (! function_exists('versioned_asset')) {
    /**
     * Generate a cache-busted URL for a static public asset.
     */
    function versioned_asset(string $path, ?bool $secure = null): string
    {
        $url = asset($path, $secure);
        $version = config('app.asset_version');

        if (! is_string($version) || $version === '') {
            $publicPath = public_path(ltrim(parse_url($path, PHP_URL_PATH) ?: $path, '/\\'));
            $modifiedAt = is_file($publicPath) ? filemtime($publicPath) : false;
            $version = $modifiedAt === false ? null : (string) $modifiedAt;
        }

        if ($version === null || $version === '') {
            return $url;
        }

        $fragmentPosition = strpos($url, '#');
        $fragment = $fragmentPosition === false ? '' : substr($url, $fragmentPosition);
        $urlWithoutFragment = $fragmentPosition === false ? $url : substr($url, 0, $fragmentPosition);
        $separator = str_contains($urlWithoutFragment, '?') ? '&' : '?';

        return $urlWithoutFragment.$separator.'v='.rawurlencode($version).$fragment;
    }
}
