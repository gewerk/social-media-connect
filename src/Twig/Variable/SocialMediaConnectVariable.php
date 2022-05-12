<?php

/**
 * @link https://gewerk.dev/plugins/social-media-connect
 * @copyright 2022 gewerk, Dennis Morhardt
 * @license https://github.com/gewerk/social-media-connect/blob/main/LICENSE.md
 */

namespace Gewerk\SocialMediaConnect\Twig\Variable;

use craft\base\Component;
use craft\elements\Entry;
use Gewerk\SocialMediaConnect\SocialMediaConnect;

class SocialMediaConnectVariable extends Component
{
    public function getPlugin(): SocialMediaConnect
    {
        return SocialMediaConnect::$plugin;
    }

    public function getSharesByEntry(Entry $entry): array
    {
        return $this->getPlugin()->getShare()->getSharesByEntry($entry);
    }

    public function getProviders()
    {
        return $this->getPlugin()->getProviders()->getAllProviders();
    }
}
