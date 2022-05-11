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
use Gewerk\SocialMediaConnect\Plugin;
use Gewerk\SocialMediaConnect\Provider\ProviderInterface;
use Gewerk\SocialMediaConnect\Record;

/**
 * Query for social media accounts
 *
 * @property string|string[]|ProviderInterface $provider
 * @method Account[] all($db = null)
 * @method Account|null one($db = null)
 * @method Account|null nth(int $n, Connection $db = null)
 * @package Gewerk\SocialMediaConnect\Element\Query
 */
class AccountQuery extends ElementQuery
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
     * @var int|null Connector ID
     */
    public $connectorId;

    /**
     * @var int|null Token ID
     */
    public $tokenId;

    /**
     * @inheritdoc
     */
    public function __set($name, $value)
    {
        switch ($name) {
            case 'provider':
                $this->provider($value);
                break;
            default:
                parent::__set($name, $value);
        }
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
     * Query by connector ID
     *
     * @param int|int[]|null $value
     * @return static
     */
    public function connectorId($value)
    {
        $this->connectorId = $value;

        return $this;
    }

    /**
     * Query by token ID
     *
     * @param int|int[]|null $value
     * @return static
     */
    public function tokenId($value)
    {
        $this->tokenId = $value;

        return $this;
    }

    /**
     * Query by identifier
     *
     * @param string|string[]|null $value
     * @return static
     */
    public function identifier($value)
    {
        $this->identifier = $value;

        return $this;
    }

    /**
     * @inheritdoc
     */
    protected function beforePrepare(): bool
    {
        // Join in the social media accounts table
        $this->joinElementTable('social_media_connect_accounts');

        // Select fields
        $this->query->select([
            'social_media_connect_accounts.tokenId',
            'social_media_connect_accounts.connectorId',
            'social_media_connect_accounts.identifier',
            'social_media_connect_accounts.name',
            'social_media_connect_accounts.handle',
            'social_media_connect_accounts.lastRefreshedAt',
            'social_media_connect_accounts.settings',
        ]);

        // Add token ID to query
        if ($this->tokenId) {
            $this->subQuery->andWhere(Db::parseParam(
                'social_media_connect_accounts.tokenId',
                $this->tokenId
            ));
        }

        // Add provider to query
        if ($this->providerId || $this->providerType) {
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

        // Add connector to query
        if ($this->connectorId) {
            $this->subQuery->andWhere(Db::parseParam(
                'social_media_connect_accounts.connectorId',
                $this->connectorId
            ));
        }

        // Add identifier to query
        if ($this->identifier) {
            $this->subQuery->andWhere(Db::parseParam(
                'social_media_connect_accounts.identifier',
                $this->identifier
            ));
        }

        return parent::beforePrepare();
    }
}
