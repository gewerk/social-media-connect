<?php
/**
 * @link https://gewerk.dev/plugins/social-media-connect
 * @copyright 2022 gewerk, Dennis Morhardt
 * @license https://github.com/gewerk/social-media-connect/blob/main/LICENSE.md
 */

namespace Gewerk\SocialMediaConnect\Provider\Capability;

use Gewerk\SocialMediaConnect\Collection\PostCollection;
use Gewerk\SocialMediaConnect\Element\Account;

interface PullPostsCapabilityInterface
{
    /**
     * Returns posts for this social media provider for an account
     *
     * @param Account $account
     * @param int $limit
     * @return PostCollection
     */
    public function getPosts(Account $account, int $limit = 10): PostCollection;
}
