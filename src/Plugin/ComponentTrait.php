<?php

/**
 * @link https://gewerk.dev/plugins/social-media-connect
 * @copyright 2022 gewerk, Dennis Morhardt
 * @license https://github.com/gewerk/social-media-connect/blob/main/LICENSE.md
 */

namespace Gewerk\SocialMediaConnect\Plugin;

use Gewerk\SocialMediaConnect\Service\PostsService;
use Gewerk\SocialMediaConnect\Service\ShareService;
use Gewerk\SocialMediaConnect\Service\ProvidersService;
use Gewerk\SocialMediaConnect\Service\TokensService;

trait ComponentTrait
{
    /**
     * Returns the tokens service
     *
     * @return TokensService
     */
    public function getTokens(): TokensService
    {
        return $this->get('tokens');
    }

    /**
     * Returns the providers service
     *
     * @return ProvidersService
     */
    public function getProviders(): ProvidersService
    {
        return $this->get('providers');
    }

    /**
     * Returns the posting interface service
     *
     * @return ShareService
     */
    public function getShare(): ShareService
    {
        return $this->get('share');
    }

    /**
     * Returns the social media posts service
     *
     * @return PostsService
     */
    public function getPosts(): PostsService
    {
        return $this->get('posts');
    }

    /**
     * Registers all plugin components
     *
     * @return void
     */
    private function registerComponents()
    {
        $this->setComponents([
            'tokens' => TokensService::class,
            'providers' => ProvidersService::class,
            'share' => ShareService::class,
            'posts' => PostsService::class,
        ]);
    }
}
