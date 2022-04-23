<?php
/**
 * @link https://gewerk.dev/plugins/social-media-connect
 * @copyright 2022 gewerk, Dennis Morhardt
 * @license https://github.com/gewerk/social-media-connect/blob/main/LICENSE.md
 */

namespace Gewerk\SocialMediaConnect\Provider;

use craft\base\MissingComponentInterface;
use craft\base\MissingComponentTrait;
use craft\web\Request;
use craft\web\Response;
use Gewerk\SocialMediaConnect\Exception\CallbackException;
use Gewerk\SocialMediaConnect\Model\Token;
use yii\web\NotFoundHttpException;

class MissingProvider extends AbstractProvider implements MissingComponentInterface
{
    use MissingComponentTrait;

    /**
     * @inheritdoc
     */
    public function handleConnect(Request $request): Response
    {
        throw new NotFoundHttpException('Provider is missing: Connect not possible');
    }

    /**
     * @inheritdoc
     */
    public function handleCallback(Request $request): ?Token
    {
        throw new CallbackException('Provider is missing: Callback not possible');
    }
}
