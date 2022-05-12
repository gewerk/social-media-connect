<?php

/**
 * @link https://gewerk.dev/plugins/social-media-connect
 * @copyright 2022 gewerk, Dennis Morhardt
 * @license https://github.com/gewerk/social-media-connect/blob/main/LICENSE.md
 */

namespace Gewerk\SocialMediaConnect\Controller;

use Craft;
use craft\helpers\Json;
use craft\web\Controller;
use craft\web\Response;
use Gewerk\SocialMediaConnect\SocialMediaConnect;
use Gewerk\SocialMediaConnect\Provider\MissingProvider;
use Gewerk\SocialMediaConnect\Provider\ProviderInterface;
use yii\web\BadRequestHttpException;
use yii\web\NotFoundHttpException;

/**
 * Management of providers
 *
 * @package Gewerk\SocialMediaConnect\Controller
 */
class ProvidersController extends Controller
{
    /**
     * Lists all providers
     *
     * @return Response
     */
    public function actionIndex(): Response
    {
        $plugin = SocialMediaConnect::$plugin;

        $providersService = SocialMediaConnect::$plugin->getProviders();
        $providers = $providersService->getAllProviders();

        return $this->renderTemplate('social-media-connect/settings/providers', [
            'plugin' => $plugin,
            'providers' => $providers,
        ]);
    }

    /**
     * Renders an edit screen for an existing or new provider
     *
     * @param int|null $providerId
     * @param ProviderInterface|null $provider
     * @return Response
     */
    public function actionEdit(int $providerId = null, ProviderInterface $provider = null): Response
    {
        $providers = SocialMediaConnect::$plugin->getProviders();
        $providerTypes = $providers->getAllProviderTypes();

        $isNew = true;
        $title = Craft::t('social-media-connect', 'Create a new provider');

        if ($provider === null && $providerId !== null) {
            $provider = $providers->getProviderById($providerId);

            if ($provider === null) {
                throw new NotFoundHttpException('Provider not found');
            }

            $isNew = false;
            $title = trim($provider->name) ?: Craft::t('social-media-connect', 'Edit provider');

            if (!in_array(get_class($provider), $providerTypes, true)) {
                $providerTypes[] = get_class($provider);
            }
        }

        $providerInstances = [];
        $providerTypeOptions = [];

        foreach ($providerTypes as $providerType) {
            $providerInstances[$providerType] = $providers->createProvider([
                'type' => $providerType,
            ]);

            $providerTypeOptions[] = [
                'value' => $providerType,
                'label' => $providerType::displayName(),
            ];
        }

        return $this->renderTemplate('social-media-connect/settings/providers/edit', [
            'plugin' => SocialMediaConnect::$plugin,
            'provider' => $provider,
            'isNew' => $isNew,
            'missingPlaceholder' => $provider instanceof MissingProvider ? $provider->getPlaceholderHtml() : null,
            'providerTypes' => $providerTypes,
            'providerTypeOptions' => $providerTypeOptions,
            'providerInstances' => $providerInstances,
            'title' => $title,
        ]);
    }

    /**
     * Saves a provider.
     *
     * @return Response|null
     */
    public function actionSave(): ?Response
    {
        $this->requirePostRequest();

        $providersService = SocialMediaConnect::$plugin->getProviders();
        $providerId = $this->request->getParam('id') ?: null;
        $type = $this->request->getParam('type');
        $settings = $this->request->getParam('types.' . $type, []);

        if ($providerId) {
            $savedProvider = $providersService->getProviderById($providerId);

            if (!$savedProvider) {
                throw new BadRequestHttpException("Invalid provider ID: $providerId");
            }

            $settings = array_merge($savedProvider->settings, $settings);
        }

        $provider = $providersService->createProvider([
            'id' => $providerId,
            'name' => $this->request->getParam('name'),
            'handle' => $this->request->getParam('handle'),
            'type' => $type,
            'settings' => $settings,
            'enabled' => (bool) $this->request->getParam('enabled'),
            'sortOrder' => $savedProvider->sortOrder ?? null,
            'uid' => $savedProvider->uid ?? null,
        ]);

        if (!$providersService->saveProvider($provider)) {
            $this->setFailFlash(Craft::t('social-media-connect', 'Couldn’t save provider.'));

            Craft::$app->getUrlManager()->setRouteParams([
                'provider' => $provider,
            ]);

            return null;
        }

        $this->setSuccessFlash(Craft::t('social-media-connect', 'Provider saved.'));

        return $this->redirectToPostedUrl($provider);
    }

    /**
     * Deletes a provider
     *
     * @return Response|null
     */
    public function actionDelete(): ?Response
    {
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();
        $providerId = $request->getRequiredParam('id');

        $providersService = SocialMediaConnect::$plugin->getProviders();
        $provider = $providersService->getProviderById($providerId);

        if (!$provider) {
            throw new NotFoundHttpException('Provider not found');
        }

        $deleted = $providersService->deleteProvider($provider);

        if ($request->getAcceptsJson()) {
            return $this->asJson([
                'success' => $deleted,
            ]);
        }

        if (!$deleted) {
            $this->setFailFlash(Craft::t('social-media-connect', 'Couldn’t delete provider.'));

            return null;
        }

        $this->setSuccessFlash(Craft::t('social-media-connect', 'Provider deleted.'));

        return $this->redirectToPostedUrl();
    }

    /**
     * Reorders all providers
     *
     * @return Response
     */
    public function actionReorder(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $providerIds = Json::decode($this->request->getRequiredParam('ids'));
        $providersService = SocialMediaConnect::$plugin->getProviders();
        $providersService->reorderProviders($providerIds);

        return $this->asJson(['success' => true]);
    }
}
