<?php

/**
 * @link https://gewerk.dev/plugins/social-media-connect
 * @copyright 2022 gewerk, Dennis Morhardt
 * @license https://github.com/gewerk/social-media-connect/blob/main/LICENSE.md
 */

namespace Gewerk\SocialMediaConnect\Service;

use craft\base\Component;
use craft\db\Query;
use craft\helpers\DateTimeHelper;
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
        $tokenRecords = $this->tokenBaseQuery()
            ->groupBy('[[accounts.tokenId]]')
            ->all();

        $tokens = [];

        foreach ($tokenRecords as $tokenRecord) {
            $tokens[] = $this->populateToken($tokenRecord);
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

        $tokenRecord = $this->tokenBaseQuery()
            ->where(['[[tokens.id]]' => $account->tokenId])
            ->one();

        if (!$tokenRecord) {
            throw new InvalidTokenIdException($account->tokenId);
        }

        return $this->populateToken($tokenRecord);
    }

    /**
     * Base query for token
     *
     * @return Query
     */
    private function tokenBaseQuery(): Query
    {
        return (new Query())
            ->select([
                '[[tokens.id]]',
                '[[tokens.providerId]]',
                '[[tokens.identifier]]',
                '[[tokens.token]]',
                '[[tokens.refreshToken]]',
                '[[tokens.scopes]]',
                '[[tokens.expiryDate]]',
                '[[tokens.dateUpdated]]',
                '[[tokens.dateCreated]]',
                '[[tokens.uid]]',
            ])
            ->from(['tokens' => Record\Token::tableName()])
            ->leftJoin(
                ['accounts' => Record\Account::tableName()],
                '[[accounts.tokenId]] = [[tokens.id]]'
            );
    }

    /**
     * Populates token model
     *
     * @param array $tokenRecord
     * @return Token
     */
    private function populateToken(array $tokenRecord): Token
    {
        $token = new Token();
        $token->id = (int) $tokenRecord['id'];
        $token->providerId = (int) $tokenRecord['providerId'];
        $token->identifier = $tokenRecord['identifier'];
        $token->token = $tokenRecord['token'];
        $token->refreshToken = $tokenRecord['refreshToken'];
        $token->scopes = explode(',', $tokenRecord['scopes']);
        $token->expiryDate = DateTimeHelper::toDateTime($tokenRecord['expiryDate']);
        $token->dateUpdated = DateTimeHelper::toDateTime($tokenRecord['dateUpdated']);
        $token->dateCreated = DateTimeHelper::toDateTime($tokenRecord['dateCreated']);
        $token->refreshToken = $tokenRecord['uid'];

        return $token;
    }
}
