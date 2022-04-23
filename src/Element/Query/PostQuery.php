<?php
/**
 * @link https://gewerk.dev/plugins/social-media-connect
 * @copyright 2022 gewerk, Dennis Morhardt
 * @license https://github.com/gewerk/social-media-connect/blob/main/LICENSE.md
 */

namespace Gewerk\SocialMediaConnect\Element\Query;

use craft\elements\db\ElementQuery;
use Gewerk\SocialMediaConnect\Element\Post;

/**
 * Query for social media posts
 *
 * @method Post[]|array all($db = null)
 * @method Post|array|null one($db = null)
 * @method Post|array|null nth(int $n, Connection $db = null)
 * @package Gewerk\SocialMediaConnect\Element\Query
 */
class PostQuery extends ElementQuery
{
}
