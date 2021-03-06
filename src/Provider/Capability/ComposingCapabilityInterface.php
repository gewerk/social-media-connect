<?php

/**
 * @link https://gewerk.dev/plugins/social-media-connect
 * @copyright 2022 gewerk, Dennis Morhardt
 * @license https://github.com/gewerk/social-media-connect/blob/main/LICENSE.md
 */

namespace Gewerk\SocialMediaConnect\Provider\Capability;

use craft\base\Element;
use Gewerk\SocialMediaConnect\Element\Account;
use Gewerk\SocialMediaConnect\Provider\Share\AbstractShare;

interface ComposingCapabilityInterface
{
    /**
     * Returns the share model class
     *
     * @return string
     */
    public static function getShareModelClass(): string;

    /**
     * Returns if an account supports composing
     *
     * @param Account $account
     * @return bool
     */
    public function supportsComposing(Account $account): bool;

    /**
     * Returns the fields for composing a social media posting
     *
     * @param AbstractShare $post
     * @return string
     */
    public function getComposingHtml(AbstractShare $share): string;

    /**
     * Publishes a composed post
     *
     * @param AbstractShare $post
     * @return AbstractShare
     */
    public function publishShare(AbstractShare $share): AbstractShare;

    /**
     * Returns the error message if an share has failed to be published.
     *
     * @param AbstractShare $share
     * @return string
     */
    public function getShareErrorMessage(AbstractShare $share): string;

    /**
     * Returns the list of attributes to be rendered of a share
     *
     * @param AbstractShare $share
     * @return string[]
     */
    public function defineShareAttributes(AbstractShare $share): array;

    /**
     * Returns html for an attribute of a share
     *
     * @param AbstractShare $share
     * @param string $attribute
     * @return string
     */
    public function getShareAttributeHtml(AbstractShare $share, string $attribute): string;
}
