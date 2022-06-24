<?php

/**
 * @link https://gewerk.dev/plugins/social-media-connect
 * @copyright 2022 gewerk, Dennis Morhardt
 * @license https://github.com/gewerk/social-media-connect/blob/main/LICENSE.md
 */

namespace Gewerk\SocialMediaConnect\Console\Controller;

use Craft;
use craft\console\Controller;
use Gewerk\SocialMediaConnect\Job\PublishShare;
use Gewerk\SocialMediaConnect\SocialMediaConnect;
use yii\console\ExitCode;

/**
 * Share related tasks
 *
 * @package Gewerk\SocialMediaConnect\Console\Controller
 */
class SharesController extends Controller
{
    /**
     * @var bool
     */
    public $dryRun = false;

    /**
     * @inheritdoc
     */
    public function options($actionID): array
    {
        return ['dryRun'];
    }

    /**
     * Publishes shares
     *
     * @return int
     */
    public function actionPublish(): int
    {
        // Get all unpublished shares
        $this->stdout('Publishing shares ... ' . PHP_EOL);
        $unpublishedShares = SocialMediaConnect::$plugin->getShare()->getUnpublishedShares();

        // Create publish jobs
        foreach ($unpublishedShares as $unpublishedShare) {
            $accountName = $unpublishedShare->getAccount()->name;
            $this->stdout("- Share to {$accountName} for entry #{$unpublishedShare->entryId}" . PHP_EOL);

            if (!$this->dryRun) {
                Craft::$app->getQueue()->push(new PublishShare([
                    'shareId' => $unpublishedShare->id,
                    'accountName' => $unpublishedShare->getAccount()->name,
                ]));
            }
        }

        return ExitCode::OK;
    }
}
