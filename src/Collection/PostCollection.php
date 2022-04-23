<?php
/**
 * @link https://gewerk.dev/plugins/social-media-connect
 * @copyright 2022 gewerk, Dennis Morhardt
 * @license https://github.com/gewerk/social-media-connect/blob/main/LICENSE.md
 */

namespace Gewerk\SocialMediaConnect\Collection;

use Gewerk\SocialMediaConnect\Element\Post;
use Gewerk\SocialMediaConnect\Exception\InvalidCollectionItemException;
use Illuminate\Support\Collection;

class PostCollection extends Collection
{
    /**
     * @inheritdoc
     */
    public function __construct($items = [])
    {
        foreach ($items as $item) {
            if (!($item instanceof Post)) {
                throw new InvalidCollectionItemException($item, $this);
            }
        }

        parent::__construct($items);
    }
}
