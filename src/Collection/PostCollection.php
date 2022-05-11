<?php
/**
 * @link https://gewerk.dev/plugins/social-media-connect
 * @copyright 2022 gewerk, Dennis Morhardt
 * @license https://github.com/gewerk/social-media-connect/blob/main/LICENSE.md
 */

namespace Gewerk\SocialMediaConnect\Collection;

use Gewerk\SocialMediaConnect\Exception\InvalidCollectionItemException;
use Gewerk\SocialMediaConnect\Provider\Post\AbstractPost;
use Illuminate\Support\Collection;

class PostCollection extends Collection
{
    /**
     * @inheritdoc
     */
    public function __construct($items = [])
    {
        foreach ($items as $item) {
            if (!($item instanceof AbstractPost)) {
                throw new InvalidCollectionItemException($item, $this);
            }
        }

        parent::__construct($items);
    }
}
