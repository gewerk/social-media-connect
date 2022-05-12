<?php

/**
 * @link https://gewerk.dev/plugins/social-media-connect
 * @copyright 2022 gewerk, Dennis Morhardt
 * @license https://github.com/gewerk/social-media-connect/blob/main/LICENSE.md
 */

namespace Gewerk\SocialMediaConnect\Job;

use Craft;
use craft\queue\BaseJob as Job;
use Gewerk\SocialMediaConnect\SocialMediaConnect;
use Gewerk\SocialMediaConnect\Provider\Capability\ComposingCapabilityInterface;

/**
 * Publishes a share
 *
 * @package Gewerk\SocialMediaConnect\Job
 */
class PublishShare extends Job
{
    /**
     * @var string Share ID
     */
    public $shareId;

    /**
     * @var string Account name
     */
    public $accountName;

    /**
     * @inheritdoc
     */
    protected function defaultDescription(): string
    {
        return Craft::t('social-media-connect', 'Posting to {account}', [
            'account' => $this->accountName,
        ]);
    }

    /**
     * @inheritdoc
     */
    public function execute($queue)
    {
        // Get share
        $share = SocialMediaConnect::$plugin->getShare()->getShareById($this->shareId);
        if (!$share || $share->success !== null) {
            return null;
        }

        /** @var ComposingCapabilityInterface */
        $provider = $share->getAccount()->getProvider();
        $share = $provider->publishShare($share);
        SocialMediaConnect::$plugin->getShare()->saveShare($share, false);

        // Set progress
        $this->setProgress($queue, 1);
    }
}
