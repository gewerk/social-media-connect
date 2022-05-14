<?php

/**
 * @link https://gewerk.dev/plugins/social-media-connect
 * @copyright 2022 gewerk, Dennis Morhardt
 * @license https://github.com/gewerk/social-media-connect/blob/main/LICENSE.md
 */

namespace Gewerk\SocialMediaConnect\Provider;

use craft\base\SavableComponent;
use craft\helpers\UrlHelper;

/**
 * Abstract provider implementation
 *
 * @package Gewerk\SocialMediaConnect\Provider
 */
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
     * @inheritdoc
     */
    public function getName(): string
    {
        return $this->name ?? '';
    }

    /**
     * @inheritdoc
     */
    public function getHandle(): string
    {
        return $this->handle ?? '';
    }

    /**
     * @inheritdoc
     */
    public function getEnabled(): bool
    {
        return $this->enabled ?? true;
    }

    /**
     * @inheritdoc
     */
    public function getSortOrder(): int
    {
        return $this->sortOrder ?? 0;
    }

    /**
     * @inheritdoc
     */
    public function getUid(): string
    {
        return $this->uid ?? '';
    }

    /**
     * @inheritdoc
     */
    public function getCpEditUrl(): string
    {
        return UrlHelper::cpUrl("settings/plugins/social-media-connect/providers/edit/{$this->id}");
    }
}
