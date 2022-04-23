<?php
/**
 * @link https://gewerk.dev/plugins/social-media-connect
 * @copyright 2022 gewerk, Dennis Morhardt
 * @license https://github.com/gewerk/social-media-connect/blob/main/LICENSE.md
 */

namespace Gewerk\SocialMediaConnect\Service;

use Craft;
use craft\base\Component;
use craft\elements\Entry;
use craft\helpers\ArrayHelper;
use craft\helpers\Component as ComponentHelper;
use craft\helpers\Db;
use craft\helpers\Json;
use craft\web\View;
use Gewerk\SocialMediaConnect\AssetBundle\ComposeShareAssetBundle;
use Gewerk\SocialMediaConnect\Element\Account;
use Gewerk\SocialMediaConnect\Provider\Capability\ComposingCapabilityInterface;
use Gewerk\SocialMediaConnect\Provider\Share\AbstractShare;
use Gewerk\SocialMediaConnect\Record\Share;

/**
 * Share service
 *
 * @package Gewerk\SocialMediaConnect\Service
 */
class ShareService extends Component
{
    /**
     * Inits a share by entry and account
     *
     * @param Entry $entry
     * @param Account $account
     * @return null|AbstractShare
     */
    public function createShare(Entry $entry, Account $account): ?AbstractShare
    {
        if (!$account->getSupportsComposing()) {
            return null;
        }

        /** @var ComposingCapabilityInterface */
        $provider = $account->getProvider();

        /** @var AbstractShare $share */
        $share = ComponentHelper::createComponent([
            'type' => $provider->getShareModelClass(),
        ], AbstractShare::class);

        $share->setEntry($entry);
        $share->setAccount($account);

        return $share;
    }

    /**
     * Saves a share
     * @param AbstractShare $share
     * @param bool $validate
     * @return bool
     */
    public function saveShare(AbstractShare $share, bool $validate = true): bool
    {
        if ($validate && !$share->validate()) {
            return false;
        }

        if ($share->id) {
            $shareRecord = Share::findOne([
                'id' => $share->id,
            ]);
        } else {
            $shareRecord = new Share();
        }

        $shareRecord->entryId = $share->entryId;
        $shareRecord->siteId = $share->siteId;
        $shareRecord->accountId = $share->accountId;
        $shareRecord->success = $share->success;
        $shareRecord->response = $share->response;
        $shareRecord->postedAt = Db::prepareDateForDb($share->postedAt);
        $shareRecord->postUrl = $share->postUrl;
        $shareRecord->settings = $share->getSettings();

        return $shareRecord->save();
    }

    /**
     * Renders list of shares in entry context
     *
     * @param array $context
     * @return void
     */
    public function renderDetails(array &$context)
    {
        // Get entry from context
        /** @var Entry */
        $entry = $context['entry'];

        // Get accounts which support composing
        $accounts = ArrayHelper::where(
            Account::findAll(),
            'supportsComposing',
            true
        );

        // Build settings
        $settings = Json::encode([
            'entryId' => $entry->id,
            'canonicalId' => $entry->canonicalId,
            'siteId' => $entry->siteId,
            'accounts' => array_map(function (Account $account) {
                return [
                    'id' => $account->id,
                    'name' => $account->name,
                    'provider' => $account->getProvider()->getName(),
                ];
            }, $accounts),
        ], JSON_UNESCAPED_UNICODE);

        // Load assets
        $view = Craft::$app->getView();
        $view->registerAssetBundle(ComposeShareAssetBundle::class);
        $view->registerJs(
            "new Craft.SocialMediaConnect.ComposeShare('smc-compose-share', {$settings});",
            View::POS_END
        );
    }
}
