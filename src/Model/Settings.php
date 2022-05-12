<?php

/**
 * @link https://gewerk.dev/plugins/social-media-connect
 * @copyright 2022 gewerk, Dennis Morhardt
 * @license https://github.com/gewerk/social-media-connect/blob/main/LICENSE.md
 */

namespace Gewerk\SocialMediaConnect\Model;

use craft\base\Model;

/**
 * Settings model for this plugin
 *
 * @package Gewerk\SocialMediaConnect\Model
 */
class Settings extends Model
{
    /**
     * The plugin display name.
     *
     * @var string
     */
    public $pluginName = 'Social Media Connect';

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['pluginName'], 'required'];
        $rules[] = [['pluginName'], 'string', 'max' => 52];

        return $rules;
    }
}
