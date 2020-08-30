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
     * Creates the tables needed for the Records used by the plugin
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
                ]
            );
        }
    }

    /**
     * Creates the indexes needed for the Records used by the plugin
     *
     * @return void
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
     * Creates the foreign keys needed for the Records used by the plugin
     *
     * @return void
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
     *
     * @return void
     */
    protected function insertDefaultData()
    {
    }

    /**
     * Removes the tables needed for the Records used by the plugin
     *
     * @return void
     */
    protected function removeTables()
    {
        // marketplace_fees table
        $this->dropTableIfExists('{{%marketplace_fees}}');
    }
}
