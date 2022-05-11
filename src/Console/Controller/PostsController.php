<?php
/**
 * @link https://gewerk.dev/plugins/social-media-connect
 * @copyright 2022 gewerk, Dennis Morhardt
 * @license https://github.com/gewerk/social-media-connect/blob/main/LICENSE.md
 */

namespace Gewerk\SocialMediaConnect\Console\Controller;

use craft\console\Controller;
use craft\helpers\ArrayHelper;
use craft\helpers\Console;
use Gewerk\SocialMediaConnect\Element\Account;
use Gewerk\SocialMediaConnect\Plugin;
use Throwable;
use yii\console\ExitCode;

/**
 * Social media posts related tasks
 *
 * @package Gewerk\SocialMediaConnect\Console\Controller
 */
class PostsController extends Controller
{
    /**
     * Publishes shares
     *
     * @return int
     */
    public function actionPull(int $limit = 10): int
    {
        // Get all accounts
        /** @var Account[] */
        $accounts = ArrayHelper::where(
            Account::findAll(),
            'supportsPulling',
            true
        );

        // Process all accounts
        $errors = [];
        $total = count($accounts);
        Console::startProgress(0, $total);

        foreach ($accounts as $index => $account) {
            try {
                Plugin::$plugin->getPosts()->pullPostsForAccount($account, $limit);
            } catch (Throwable $e) {
                // Errors happen
                $errors[] = [
                    'account' => $account->name,
                    'message' => $e->getMessage(),
                ];
            } finally {
                Console::updateProgress($index, $total);
            }
        }

        // Report
        Console::endProgress(true);

        // Render errors
        if (count($errors) > 0) {
            foreach ($errors as $error) {
                Console::outputWarning(
                    sprintf(
                        'Pulling posts from %s failed: %s',
                        $error['account'],
                        $error['message']
                    )
                );
            }
        } else {
            Console::output('Successful pulled posts');
        }

        return ExitCode::OK;
    }
}
