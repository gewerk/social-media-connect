<?php

/**
 * @link https://gewerk.dev/plugins/social-media-connect
 * @copyright 2022 gewerk, Dennis Morhardt
 * @license https://github.com/gewerk/social-media-connect/blob/main/LICENSE.md
 */

namespace Gewerk\SocialMediaConnect\Validator;

use Twitter\Text\Parser;
use yii\validators\StringValidator;

/**
 * Validates the length of a tweet
 *
 * @package Gewerk\SocialMediaConnect\Validator
 */
class TweetLengthValidator extends StringValidator
{
    /**
     * @inheritdoc
     */
    public $min = 1;

    /**
     * @inheritdoc
     */
    public $max = 240;

    /**
     * @inheritdoc
     */
    public function validateAttribute($model, $attribute)
    {
        $value = $model->$attribute;

        if (!$this->strict && is_scalar($value) && !is_string($value)) {
            $value = (string)$value;
        }

        if (!is_string($value)) {
            $this->addError($model, $attribute, $this->message);
            return;
        }

        $validator = Parser::create()->parseTweet($value);
        $length = $validator->weightedLength;

        if ($this->min !== null && $length < $this->min) {
            $this->addError($model, $attribute, $this->tooShort, ['min' => $this->min]);
        }

        if ($this->max !== null && $length > $this->max) {
            $this->addError($model, $attribute, $this->tooLong, ['max' => $this->max]);
        }

        if ($this->length !== null && $length !== $this->length) {
            $this->addError($model, $attribute, $this->notEqual, ['length' => $this->length]);
        }
    }
}
