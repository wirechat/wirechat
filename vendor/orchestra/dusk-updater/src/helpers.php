<?php

namespace Orchestra\DuskUpdater;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Utils;

/**
 * Rename exported ChromeDriver binary filename.
 */
function rename_chromedriver_binary(string $binary, string $operatingSystem): string
{
    $binary = str_replace(DIRECTORY_SEPARATOR, '/', $binary);

    if (mb_strpos($binary, '/') > 0) {
        return array_reverse(explode('/', str_replace('chromedriver', 'chromedriver-'.$operatingSystem, $binary), 2))[0];
    }

    return str_replace('chromedriver', 'chromedriver-'.$operatingSystem, $binary);
}

/**
 * Download from URL.
 *
 *
 * @throws \Exception
 */
function download(string $url, string $destination, ?string $proxy = null, bool $verifySsl = true): void
{
    $client = new Client();

    $resource = Utils::tryFopen($destination, 'w');

    $response = $client->get($url, array_merge([
        'sink' => $resource,
        'verify' => $verifySsl,
    ], array_filter([
        'proxy' => $proxy,
    ])));

    if ($response->getStatusCode() < 200 || $response->getStatusCode() > 299) {
        throw new Exception("Unable to download from [{$url}]");
    }
}

/**
 * Get contents from URL.
 *
 *
 * @throws \Exception
 */
function fetch(string $url, ?string $proxy = null, bool $verifySsl = true): string
{
    $client = new Client();

    $response = $client->get($url, array_merge([
        'verify' => $verifySsl,
    ], array_filter([
        'proxy' => $proxy,
    ])));

    if ($response->getStatusCode() < 200 || $response->getStatusCode() > 299) {
        throw new Exception("Unable to fetch contents from [{$url}]");
    }

    return (string) $response->getBody();
}
