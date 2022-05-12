<?php

/**
 * @link https://gewerk.dev/plugins/social-media-connect
 * @copyright 2022 gewerk, Dennis Morhardt
 * @license https://github.com/gewerk/social-media-connect/blob/main/LICENSE.md
 */

namespace Gewerk\SocialMediaConnect\Service;

use craft\base\Component;
use craft\db\Query;
use craft\helpers\Db;
use Gewerk\SocialMediaConnect\Element\Account;
use Gewerk\SocialMediaConnect\Exception\MissingCapabilityException;
use Gewerk\SocialMediaConnect\Exception\InvalidTokenIdException;
use Gewerk\SocialMediaConnect\Model\Token;
use Gewerk\SocialMediaConnect\Provider\OAuth2\SupportsTokenRefreshingInterface;
use Gewerk\SocialMediaConnect\Record;

/**
 * Service component for managing tokens
 *
 * @package Gewerk\SocialMediaConnect\Service
 */
class TokensService extends Component
{
    /**
     * Refreshes a token
     *
     * @param Token $token
     * @return bool
     */
    public function refreshToken(Token $token): bool
    {
        $provider = $token->getProvider();

        if ($provider instanceof SupportsTokenRefreshingInterface) {
            $token = $provider->refreshToken($token);

            $tokenRecord = Record\Token::findOne([
                'providerId' => $token->providerId,
                'identifier' => $token->identifier,
            ]);

            $tokenRecord->token = $token->token;
            $tokenRecord->refreshToken = $token->refreshToken;
            $tokenRecord->expiryDate = Db::prepareDateForDb($token->expiryDate);
            $tokenRecord->scopes = implode(',', $token->scopes);

            return $tokenRecord->save();
        }

        throw new MissingCapabilityException(
            $provider,
            SupportsTokenRefreshingInterface::class
        );
    }

    /**
     * Returns all used tokens
     *
     * @return Token[]
     */
    public function getUsedTokens(): array
    {
        $tokenRecords = (new Query())
            ->select([
                '[[social_media_connect_tokens.id]]',
                '[[social_media_connect_tokens.providerId]]',
                '[[social_media_connect_tokens.identifier]]',
                '[[social_media_connect_tokens.token]]',
                '[[social_media_connect_tokens.refreshToken]]',
                '[[social_media_connect_tokens.scopes]]',
                '[[social_media_connect_tokens.expiryDate]]',
                '[[social_media_connect_tokens.dateUpdated]]',
                '[[social_media_connect_tokens.dateCreated]]',
                '[[social_media_connect_tokens.uid]]',
            ])
            ->from(Record\Token::tableName())
            ->leftJoin(
                Record\Account::tableName(),
                '[[social_media_connect_accounts.tokenId]] = [[social_media_connect_tokens.id]]'
            )
            ->groupBy('[[social_media_connect_accounts.tokenId]]')
            ->all();

        $tokens = [];

        foreach ($tokenRecords as $tokenRecord) {
            $token = new Token($tokenRecord);
            $token->afterFind();

            $tokens[] = $token;
        }

        return $tokens;
    }

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
