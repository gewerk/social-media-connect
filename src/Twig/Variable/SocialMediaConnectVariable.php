<?php

/**
 * @link https://gewerk.dev/plugins/social-media-connect
 * @copyright 2022 gewerk, Dennis Morhardt
 * @license https://github.com/gewerk/social-media-connect/blob/main/LICENSE.md
 */

namespace Gewerk\SocialMediaConnect\Twig\Variable;

use craft\base\Component;
use craft\elements\Entry;
use Gewerk\SocialMediaConnect\Provider\ProviderInterface;
use Gewerk\SocialMediaConnect\SocialMediaConnect;

/**
 * Variable for accessing this plugin via Twig templates
 *
 * @package Gewerk\SocialMediaConnect\Twig\Variable
 */
class SocialMediaConnectVariable extends Component
{
    /**
     * Returns the plugin instance
     *
     * @return SocialMediaConnect
     */
    public function getPlugin(): SocialMediaConnect
    {
        return SocialMediaConnect::$plugin;
    }

    /**
     * Returns all shares for an entry
     *
     * @param Entry $entry
     * @return array
     */
    public function getSharesByEntry(Entry $entry): array
    {
        return $this->getPlugin()->getShare()->getSharesByEntry($entry);
    }

    /**
     * Returns all providers
     *
     * @return ProviderInterface[]
     */
    public function getProviders()
    {
        return $this->getPlugin()->getProviders()->getAllProviders();
    }
}
