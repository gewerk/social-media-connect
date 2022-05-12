<?php
/**
 * @link https://gewerk.dev/plugins/social-media-connect
 * @copyright 2022 gewerk, Dennis Morhardt
 * @license https://github.com/gewerk/social-media-connect/blob/main/LICENSE.md
 */

namespace Gewerk\SocialMediaConnect\Console\Controller;

use craft\console\Controller;
use craft\helpers\Console;
use DateTime;
use Gewerk\SocialMediaConnect\Exception\MissingCapabilityException;
use Gewerk\SocialMediaConnect\SocialMediaConnect;
use Throwable;
use yii\console\ExitCode;

/**
 * Tokens related tasks
 *
 * @package Gewerk\SocialMediaConnect\Console\Controller
 */
class TokensController extends Controller
{
    /**
     * Publishes shares
     *
     * @return int
     */
    public function actionRefresh(): int
    {
        // Get all used tokens
        $tokensService = SocialMediaConnect::$plugin->getTokens();
        $tokens = $tokensService->getUsedTokens();

        // Process all tokens
        foreach ($tokens as $token) {
            try {
                if ($token->expiryDate && $token->expiryDate->diff(new DateTime())->days > 30) {
                    Console::output(sprintf('Token ID %d skipped, still 30 days valid', $token->id));
                    continue;
                }

                $tokensService->refreshToken($token);
                Console::output(sprintf('Token ID %d refreshed', $token->id));
            } catch (MissingCapabilityException $e) {
                continue;
            } catch (Throwable $e) {
                Console::outputWarning(sprintf(
                    'Refreshing of token ID %d failed: %s',
                    $token->id,
                    $e->getMessage()
                ));
            }
        }

        return ExitCode::OK;
    }
}
