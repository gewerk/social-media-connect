<?php

/**
 * @link https://gewerk.dev/plugins/social-media-connect
 * @copyright 2022 gewerk, Dennis Morhardt
 * @license https://github.com/gewerk/social-media-connect/blob/main/LICENSE.md
 */

namespace Gewerk\SocialMediaConnect\Service;

use craft\base\Component;
use Gewerk\SocialMediaConnect\Collection\PostCollection;
use Gewerk\SocialMediaConnect\Element\Account;
use Gewerk\SocialMediaConnect\Exception\MissingCapabilityException;
use Gewerk\SocialMediaConnect\Provider\Capability\PullPostsCapabilityInterface;

/**
 * Service component for social media posts
 *
 * @package Gewerk\SocialMediaConnect\Service
 */
class PostsService extends Component
{
    /**
     * Pulls posts from account
     *
     * @param Account $account
     * @param int $limit
     * @return PostCollection
     * @throws MissingCapabilityException
     */
    public function pullPostsForAccount(Account $account, int $limit = 10): PostCollection
    {
        $provider = $account->getProvider();

        if ($provider instanceof PullPostsCapabilityInterface) {
            return $provider->getPosts($account, $limit);
        }

        throw new MissingCapabilityException(
            $provider,
            PullPostsCapabilityInterface::class
        );
    }
}
