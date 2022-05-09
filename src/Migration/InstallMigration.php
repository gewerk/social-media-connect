<?php
/**
 * @link https://gewerk.dev/plugins/social-media-connect
 * @copyright 2022 gewerk, Dennis Morhardt
 * @license https://github.com/gewerk/social-media-connect/blob/main/LICENSE.md
 */

namespace Gewerk\SocialMediaConnect\Migration;

use craft\db\Migration;
use craft\db\Table;
use Gewerk\SocialMediaConnect\Record;

/**
 * Creates tables for this plugin
 *
 * @package Gewerk\SocialMediaConnect\Migration
 */
class InstallMigration extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->createTable(Record\Provider::tableName(), [
            'id' => $this->primaryKey(),
            'name' => $this->string()->notNull(),
            'handle' => $this->string(64)->notNull(),
            'type' => $this->string()->notNull(),
            'sortOrder' => $this->smallInteger()->unsigned(),
            'enabled' => $this->boolean()->notNull()->defaultValue(true),
            'settings' => $this->text(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->createTable(Record\Token::tableName(), [
            'id' => $this->primaryKey(),
            'providerId' => $this->integer()->notNull(),
            'identifier' => $this->string(),
            'token' => $this->tinyText()->notNull(),
            'refreshToken' => $this->tinyText(),
            'scopes' => $this->tinyText(),
            'expiryDate' => $this->dateTime(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->createIndex(null, Record\Token::tableName(), ['identifier']);

        $this->addForeignKey(
            null,
            Record\Token::tableName(),
            ['providerId'],
            Record\Provider::tableName(),
            ['id'],
            'CASCADE'
        );

        $this->createTable(Record\Account::tableName(), [
            'id' => $this->primaryKey(),
            'tokenId' => $this->integer()->notNull(),
            'connectorId' => $this->integer(),
            'identifier' => $this->string(),
            'name' => $this->tinyText()->notNull(),
            'handle' => $this->tinyText()->notNull(),
            'settings' => $this->json()->notNull(),
            'lastRefreshedAt' => $this->dateTime()->notNull(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->createIndex(null, Record\Account::tableName(), ['identifier']);

        $this->addForeignKey(
            null,
            Record\Account::tableName(),
            ['id'],
            Table::ELEMENTS,
            ['id'],
            'CASCADE'
        );

        $this->addForeignKey(
            null,
            Record\Account::tableName(),
            ['tokenId'],
            Record\Token::tableName(),
            ['id'],
            'CASCADE'
        );

        $this->addForeignKey(
            null,
            Record\Account::tableName(),
            ['connectorId'],
            Table::USERS,
            ['id'],
            'SET NULL'
        );

        $this->createTable(Record\Share::tableName(), [
            'id' => $this->primaryKey(),
            'entryId' => $this->integer()->notNull(),
            'siteId' => $this->integer()->notNull(),
            'accountId' => $this->integer()->notNull(),
            'publishWithEntry' => $this->boolean()->defaultValue(true),
            'postAt' => $this->dateTime(),
            'postedAt' => $this->dateTime(),
            'success' => $this->boolean(),
            'settings' => $this->json(),
            'response' => $this->json(),
            'postUrl' => $this->tinyText(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->createIndex(null, Record\Share::tableName(), ['entryId', 'siteId', 'accountId']);

        $this->addForeignKey(
            null,
            Record\Share::tableName(),
            ['entryId'],
            Table::ENTRIES,
            ['id'],
            'CASCADE'
        );

        $this->addForeignKey(
            null,
            Record\Share::tableName(),
            ['siteId'],
            Table::SITES,
            ['id'],
            'CASCADE'
        );

        $this->addForeignKey(
            null,
            Record\Share::tableName(),
            ['accountId'],
            Record\Account::tableName(),
            ['id'],
            'CASCADE'
        );
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->dropTableIfExists(Record\Share::tableName());
        $this->dropTableIfExists(Record\Account::tableName());
        $this->dropTableIfExists(Record\Token::tableName());
        $this->dropTableIfExists(Record\Provider::tableName());
    }
}
