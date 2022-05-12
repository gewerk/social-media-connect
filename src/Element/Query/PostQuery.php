<?php
/**
 * @link https://gewerk.dev/plugins/social-media-connect
 * @copyright 2022 gewerk, Dennis Morhardt
 * @license https://github.com/gewerk/social-media-connect/blob/main/LICENSE.md
 */

namespace Gewerk\SocialMediaConnect\Element\Query;

use craft\db\Query;
use craft\elements\db\ElementQuery;
use craft\helpers\Db;
use Gewerk\SocialMediaConnect\Element\Account;
use Gewerk\SocialMediaConnect\Element\Post;
use Gewerk\SocialMediaConnect\Plugin;
use Gewerk\SocialMediaConnect\Provider\ProviderInterface;
use Gewerk\SocialMediaConnect\Record;

/**
 * Query for social media posts
 *
 * @property string|string[]|ProviderInterface $provider
 * @property string|string[]|Account $account
 * @method Post[] all($db = null)
 * @method Post|null one($db = null)
 * @method Post|null nth(int $n, Connection $db = null)
 * @package Gewerk\SocialMediaConnect\Element\Query
 */
class PostQuery extends ElementQuery
{
    /**
     * @var string|string[]|null Provider type
     */
    public $providerType;

    /**
     * @var int|int[]|null Provider
     */
    public $providerId;

    /**
     * @var string|null Identifier
     */
    public $identifier;

    /**
     * @var string|null Post payload type
     */
    public $type;

    /**
     * @var int|null Account ID
     */
    public $accountId;

    /**
     * @inheritdoc
     */
    public function __set($name, $value)
    {
        switch ($name) {
            case 'provider':
                $this->provider($value);
                break;
            case 'account':
                $this->account($value);
                break;
            default:
                parent::__set($name, $value);
        }
    }

    /**
     * Query by post payload type
     *
     * @param string|string[]|null $value
     * @return static
     */
    public function type($value = null)
    {
        $this->type = $value;

        return $this;
    }

    /**
     * Query by provider type
     *
     * @param string|string[]|null $value
     * @return static
     */
    public function providerType($value = null)
    {
        $this->providerType = $value;

        return $this;
    }

    /**
     * Query by provider
     *
     * @param ProviderInterface|string|string[]|null $value
     * @return static
     */
    public function provider($value = null)
    {
        // Swap handle to string to an object
        if (is_string($value)) {
            $value = Plugin::$plugin->getProviders()->getProviderByHandle($value);
        }

        if ($value instanceof ProviderInterface) {
            $this->providerId = [$value->id];
        } elseif ($value !== null) {
            $this->providerId = (new Query())
                ->select(['id'])
                ->from(Record\Provider::tableName())
                ->where(Db::parseParam('handle', $value))
                ->column();
        } else {
            $this->providerId = null;
        }

        return $this;
    }

    /**
     * Query by provider ID
     *
     * @param int|int[]|null $value
     * @return static
     */
    public function providerId($value = null)
    {
        $this->providerId = $value;

        return $this;
    }

    /**
     * Query by account
     *
     * @param Account|null $value
     * @return static
     */
    public function account($value = null)
    {
        if ($value instanceof Account) {
            $this->accountId = [$value->id];
        } else {
            $this->accountId = null;
        }

        return $this;
    }

    /**
     * Query by account ID
     *
     * @param int|int[]|null $value
     * @return static
     */
    public function accountId($value = null)
    {
        $this->accountId = $value;

        return $this;
    }

    /**
     * Query by identifier
     *
     * @param string|string[]|null $value
     * @return static
     */
    public function identifier($value = null)
    {
        $this->identifier = $value;

        return $this;
    }

    /**
     * @inheritdoc
     */
    protected function beforePrepare(): bool
    {
        // Join in the social media posts table
        $this->joinElementTable('social_media_connect_posts');

        // Select fields
        $this->query->select([
            'social_media_connect_posts.accountId',
            'social_media_connect_posts.identifier',
            'social_media_connect_posts.url',
            'social_media_connect_posts.postedAt',
            'social_media_connect_posts.type',
            'social_media_connect_posts.payload',
        ]);

        // Add token ID to query
        if ($this->accountId) {
            $this->subQuery->andWhere(Db::parseParam(
                'social_media_connect_posts.accountId',
                $this->accountId
            ));
        }

        // Add provider to query
        if ($this->providerId || $this->providerType) {
            $this->subQuery->innerJoin(
                Record\Account::tableName(),
                '[[social_media_connect_accounts.id]] = [[social_media_connect_posts.accountId]]'
            );

            $this->subQuery->innerJoin(
                Record\Token::tableName(),
                '[[social_media_connect_tokens.id]] = [[social_media_connect_accounts.tokenId]]'
            );

            if ($this->providerId) {
                $this->subQuery->andWhere(Db::parseParam(
                    'social_media_connect_tokens.providerId',
                    $this->providerId
                ));
            }

            if ($this->providerType) {
                $this->subQuery->innerJoin(
                    Record\Provider::tableName(),
                    '[[social_media_connect_providers.id]] = [[social_media_connect_tokens.providerId]]'
                );

                $this->subQuery->andWhere(Db::parseParam(
                    'social_media_connect_providers.type',
                    $this->providerType
                ));
            }
        }

        // Add identifier to query
        if ($this->identifier) {
            $this->subQuery->andWhere(Db::parseParam(
                'social_media_connect_posts.identifier',
                $this->identifier
            ));
        }

        // Add type to query
        if ($this->type) {
            $this->subQuery->andWhere(Db::parseParam(
                'social_media_connect_posts.type',
                $this->type
            ));
        }

        return parent::beforePrepare();
    }
}
