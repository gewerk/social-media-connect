<?php

/**
 * @link https://gewerk.dev/plugins/social-media-connect
 * @copyright 2022 gewerk, Dennis Morhardt
 * @license https://github.com/gewerk/social-media-connect/blob/main/LICENSE.md
 */

namespace Gewerk\SocialMediaConnect\Helper;

use Craft;
use Fusonic\OpenGraph\Consumer;
use Fusonic\OpenGraph\Objects\ObjectBase;
use Gewerk\SocialMediaConnect\Http\RequestFactory;
use Psr\Http\Client\ClientExceptionInterface;

/**
 * Open Graph related helpers
 *
 * @package Gewerk\SocialMediaConnect\Helper
 */
class OpenGraphHelper
{
    /**
     * Gets metadata from an URL
     *
     * @param string $url
     * @return ObjectBase|null
     */
    public static function getMetadata(string $url): ?ObjectBase
    {
        $httpClient = Craft::createGuzzleClient([
            'headers' => [
                'User-Agent' => 'facebookexternalhit/1.1',
            ],
        ]);

        $httpRequestFactory = new RequestFactory();
        $consumer = new Consumer($httpClient, $httpRequestFactory);
        $consumer->useFallbackMode = true;

        try {
            return $consumer->loadUrl($url);
        } catch (ClientExceptionInterface $e) {
            Craft::warning($e->getTraceAsString());
            return null;
        }
    }
}
