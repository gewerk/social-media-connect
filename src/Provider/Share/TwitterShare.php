<?php
/**
 * @link https://gewerk.dev/plugins/social-media-connect
 * @copyright 2022 gewerk, Dennis Morhardt
 * @license https://github.com/gewerk/social-media-connect/blob/main/LICENSE.md
 */

namespace Gewerk\SocialMediaConnect\Provider\Share;

use Gewerk\SocialMediaConnect\Validator\TweetLengthValidator;

class TwitterShare extends AbstractShare
{
    /**
     * @var string|null Message
     */
    public $message = null;

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['message'], 'required'];
        $rules[] = [['message'], TweetLengthValidator::class];

        return $rules;
    }
}
