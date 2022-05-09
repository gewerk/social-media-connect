<?php
/**
 * @link https://gewerk.dev/plugins/social-media-connect
 * @copyright 2022 gewerk, Dennis Morhardt
 * @license https://github.com/gewerk/social-media-connect/blob/main/LICENSE.md
 */

namespace Gewerk\SocialMediaConnect\Provider;

use craft\base\SavableComponent;
use craft\helpers\Component;
use craft\helpers\UrlHelper;
use Gewerk\SocialMediaConnect\Collection\AccountCollection;
use Gewerk\SocialMediaConnect\Element\Account;
use Gewerk\SocialMediaConnect\Model\Token;

abstract class AbstractProvider extends SavableComponent implements ProviderInterface
{
    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $handle;

    /**
     * @var bool
     */
    public $enabled;

    /**
     * @var int
     */
    public $sortOrder;

    /**
     * @var string
     */
    public $uid;

    /**
     * @inheritdoc
     */
    public static function icon(): ?string
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return $this->name ?? '';
    }

    /**
     * @inheritDoc
     */
    public function getHandle(): string
    {
        return $this->handle ?? '';
    }

    /**
     * @inheritDoc
     */
    public function getEnabled(): bool
    {
        return $this->enabled ?? true;
    }

    /**
     * @inheritDoc
     */
    public function getSortOrder(): int
    {
        return $this->sortOrder ?? 0;
    }

    /**
     * @inheritDoc
     */
    public function getUid(): string
    {
        return $this->uid ?? '';
    }

    /**
     * @inheritDoc
     */
    public function getCpEditUrl(): string
    {
        return UrlHelper::cpUrl("settings/plugins/social-media-connect/providers/edit/{$this->id}");
    }

    /**
     * @inheritdoc
     */
    public function getAccounts(Token $token): AccountCollection
    {
        return new AccountCollection([]);
    }
}
