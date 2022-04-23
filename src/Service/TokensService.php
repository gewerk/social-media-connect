<?php
/**
 * @link https://gewerk.dev/plugins/social-media-connect
 * @copyright 2022 gewerk, Dennis Morhardt
 * @license https://github.com/gewerk/social-media-connect/blob/main/LICENSE.md
 */

namespace Gewerk\SocialMediaConnect\Service;

use craft\base\Component;
use Gewerk\SocialMediaConnect\Element\Account;
use Gewerk\SocialMediaConnect\Exception\InvalidTokenIdException;
use Gewerk\SocialMediaConnect\Model\Token;
use Gewerk\SocialMediaConnect\Record;

class TokensService extends Component
{
    /**
     * Returns token by account
     *
     * @param Account $account
     * @return Token
     * @throws InvalidTokenIdException
     */
    public function getTokenByAccount(Account $account): Token
    {
        if ($account->tokenId === null) {
            throw new InvalidTokenIdException(null);
        }

        $record = Record\Token::findOne($account->tokenId);

        if (!$record) {
            throw new InvalidTokenIdException($account->tokenId);
        }

        $token = new Token($record);
        $token->afterFind();

        return $token;
    }
}
