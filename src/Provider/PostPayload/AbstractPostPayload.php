<?php

/**
 * @link https://gewerk.dev/plugins/social-media-connect
 * @copyright 2022 gewerk, Dennis Morhardt
 * @license https://github.com/gewerk/social-media-connect/blob/main/LICENSE.md
 */

namespace Gewerk\SocialMediaConnect\Provider\PostPayload;

use craft\base\SavableComponent;
use Gewerk\SocialMediaConnect\Element\Post;

abstract class AbstractPostPayload extends SavableComponent
{
    /**
     * @var Post
     */
    private $post;

    /**
     * Sets the post
     *
     * @param Post $post
     * @return void
     */
    public function setPost(Post $post): void
    {
        $this->post = $post;
    }

    /**
     * Gets the post
     *
     * @return Post
     */
    public function getPost(): Post
    {
        return $this->post;
    }
}
