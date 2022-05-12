<?php

/**
 * @link https://gewerk.dev/plugins/social-media-connect
 * @copyright 2022 gewerk, Dennis Morhardt
 * @license https://github.com/gewerk/social-media-connect/blob/main/LICENSE.md
 */

namespace Gewerk\SocialMediaConnect\Console\Controller;

use craft\console\Controller;
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
     * Publishes shares
     *
     * @return int
     */
    public function actionPublish()
    {
        SocialMediaConnect::$plugin->getShare()->publishSharesFromPendingEntries();

        return ExitCode::OK;
    }
}
