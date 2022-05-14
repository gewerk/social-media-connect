<?php

/**
 * @link https://gewerk.dev/plugins/social-media-connect
 * @copyright 2022 gewerk, Dennis Morhardt
 * @license https://github.com/gewerk/social-media-connect/blob/main/LICENSE.md
 */

namespace Gewerk\SocialMediaConnect\Provider\Capability;

use Gewerk\SocialMediaConnect\Element\Account;
use Gewerk\SocialMediaConnect\Element\Post;

/**
 * Adds pulling of posts feature to a provider
 *
 * @package Gewerk\SocialMediaConnect\Provider\Capability
 */
interface PullPostsCapabilityInterface
{
    /**
     * Returns the post model class
     *
     * @return string
     */
    public static function getPostPayloadClass(): string;

    /**
     * Returns if an account supports pulling posts
     *
     * @param Account $account
     * @return bool
     */
    public function supportsPulling(Account $account): bool;

    /**
     * Returns posts for this social media provider for an account
     *
     * @param Account $account
     * @param int $limit
     * @return void
     */
    public function handlePosts(Account $account, int $limit = 10): void;

    /**
     * Renders a post for element index of all posts
     *
     * @param Post $post
     * @return string
     */
    public function getPostAttributeHtml(Post $post): string;
}
