<?php
/**
 * @link https://gewerk.dev/plugins/social-media-connect
 * @copyright 2022 gewerk, Dennis Morhardt
 * @license https://github.com/gewerk/social-media-connect/blob/main/LICENSE.md
 */

namespace Gewerk\SocialMediaConnect\Provider\PostPayload;

/**
 * Twitter post
 *
 * @package Gewerk\SocialMediaConnect\Provider\Post
 */
class TwitterPostPayload extends AbstractPostPayload
{
    /**
     * @var string
     */
    public $type = 'text';

    /**
     * @var string
     */
    public $text;

    /**
     * @var string|null
     */
    public $videoUrl = null;

    /**
     * @var string|null
     */
    public $imageUrl = null;

    /**
     * @var string|null
     */
    public $imageAlt = null;
}
