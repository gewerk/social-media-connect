<?php
/**
 * @link https://gewerk.dev/plugins/social-media-connect
 * @copyright 2022 gewerk, Dennis Morhardt
 * @license https://github.com/gewerk/social-media-connect/blob/main/LICENSE.md
 */

namespace Gewerk\SocialMediaConnect\Exception;

use Gewerk\SocialMediaConnect\Element\Account;
use Throwable;

class AccountMissingCapabilityException extends SocialMediaConnectException
{
    /**
     * @var string
     */
    public $capability;

    /**
     * @var Account
     */
    public $account;

    /**
     * Constructs an exception for failing to refresh an access token
     *
     * @param Account $account
     * @param string $capability
     * @param int $code
     * @param Throwable|null $previous
     * @return void
     */
    public function __construct(Account $account, string $capability, int $code = 0, ?Throwable $previous = null)
    {
        $this->account = $account;
        $this->capability = $capability;

        parent::__construct(
            sprintf(
                'Account %s (%s) misses the %s capability',
                $this->account->identifier,
                $this->account->getProvider()->displayName(),
                $this->capability
            ),
            $code,
            $previous
        );
    }

    /**
     * Returns the missing capability
     *
     * @return string
     */
    public function getCapability()
    {
        return $this->capability;
    }

    /**
     * Returns the offending account
     *
     * @return Account
     */
    public function getAccount()
    {
        return $this->account;
    }
}
