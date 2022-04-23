<?php
/**
 * @link https://gewerk.dev/plugins/social-media-connect
 * @copyright 2022 gewerk, Dennis Morhardt
 * @license https://github.com/gewerk/social-media-connect/blob/main/LICENSE.md
 */

namespace Gewerk\SocialMediaConnect\Element;

use craft\base\Element;
use craft\elements\db\ElementQueryInterface;
use Gewerk\SocialMediaConnect\Element\Query\PostQuery;

class Post extends Element
{
    /**
     * @inheritdoc
     * @return PostQuery
     */
    public static function find(): ElementQueryInterface
    {
        return new PostQuery(static::class);
    }

    /**
     * Finds one or create a new instance with provided criteria
     *
     * @param array $criteria
     * @return self
     */
    public static function findOneOrCreate($criteria)
    {
        return static::findByCondition($criteria, true) ?? new static($criteria);
    }
}
