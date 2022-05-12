<?php

/**
 * @link https://gewerk.dev/plugins/social-media-connect
 * @copyright 2022 gewerk, Dennis Morhardt
 * @license https://github.com/gewerk/social-media-connect/blob/main/LICENSE.md
 */

namespace Gewerk\SocialMediaConnect\Exception;

use Throwable;

/**
 * Exception thrown if a OAuth callback fails
 *
 * @package Gewerk\SocialMediaConnect\Exception
 */
class CallbackException extends SocialMediaConnectException
{
    /**
     * @var bool Should redirect?
     */
    public $redirect = true;

    /**
     * Constructs an exception for failing to refresh an access token
     *
     * @param string $message
     * @param bool $redirect
     * @param int $code
     * @param Throwable|null $previous
     * @return void
     */
    public function __construct(string $message = '', bool $redirect = true, int $code = 0, Throwable $previous = null)
    {
        $this->redirect = $redirect;

        parent::__construct($message, $code, $previous);
    }

    /**
     * Returns if controller should redirect after catching
     *
     * @return bool
     */
    public function getRedirect()
    {
        return $this->redirect;
    }
}
