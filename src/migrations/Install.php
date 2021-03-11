<?php

namespace kennethormandy\marketplace\migrations;

use Craft;
use craft\db\Migration;

/**
 * Install migration.
 *
 * @since   0.6.0
 */
class Install extends Migration
{
    // Public Properties
    // =========================================================================

    /**
     * @var string The database driver to use
     */
    public $driver;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->driver = Craft::$app->getConfig()->getDb()->driver;

        $this->createTables();
        $this->createIndexes();
        $this->addForeignKeys();

        // Refresh the db schema caches
        Craft::$app->db->schema->refresh();
        $this->insertDefaultData();

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->driver = Craft::$app->getConfig()->getDb()->driver;
        $this->removeTables();

        return true;
    }

    // Protected Methods
    // =========================================================================

    /**
     * Creates the tables needed for the Records used by the plugin.
     */
    protected function createTables()
    {
        // marketplace_fees table
        $tableSchema = Craft::$app->db->schema->getTableSchema('{{%marketplace_fees}}');
        if ($tableSchema === null) {
            $this->createTable(
                '{{%marketplace_fees}}',
                [
                    'id' => $this->primaryKey(),
                    'dateCreated' => $this->dateTime()->notNull(),
                    'dateUpdated' => $this->dateTime()->notNull(),
                    'dateDeleted' => $this->dateTime()->null(),
                    'uid' => $this->uid(),

                    // Custom columns in the table
                    'siteId' => $this->integer()->notNull(),
                    'handle' => $this->string(255)->notNull()->defaultValue(''),
                    'name' => $this->string(255)->notNull()->defaultValue(''),
                    'value' => $this->integer()->notNull(),
                    'type' => $this->string(255)->notNull()->defaultValue(''),

                    // Not adding an order column here, because the fees should
                    // all be applied against the order total, rather than
                    // having any kind of order of operations. Ex. if you had
                    // a $1.00 fee and a 10% fee on a $50 order, the fee is $6:
                    // ($1) + ($50 * 0.1) = $6.00
                    // It would NOT be $5.10, which would rquire an order:
                    // ($50 + $1) * 0.1 = $5.10
                ]
            );
        }
    }

    /**
     * Creates the indexes needed for the Records used by the plugin.
     */
    protected function createIndexes()
    {
        // marketplace_fees table
        $this->createIndex(
            $this->db->getIndexName('{{%marketplace_fees}}', 'handle', true),
            '{{%marketplace_fees}}',
            'handle',
            true
        );
    }

    /**
     * Creates the foreign keys needed for the Records used by the plugin.
     */
    protected function addForeignKeys()
    {
        // marketplace_fees table
        $this->addForeignKey(
            $this->db->getForeignKeyName('{{%marketplace_fees}}', 'siteId'),
            '{{%marketplace_fees}}',
            'siteId',
            '{{%sites}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
    }

    /**
     * Populates the DB with the default data.
     */
    protected function insertDefaultData()
    {
    }

    /**
     * Removes the tables needed for the Records used by the plugin.
     */
    protected function removeTables()
    {
        // marketplace_fees table
        $this->dropTableIfExists('{{%marketplace_fees}}');
    }
}
