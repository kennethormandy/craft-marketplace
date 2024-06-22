<?php

namespace kennethormandy\marketplace\migrations;

use Craft;
use craft\db\Migration;

/**
 * Install migration.
 *
 * @since 0.6.0
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
    public function safeUp(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        $this->driver = Craft::$app->getConfig()->getDb()->driver;
        $this->removeTables();

        return true;
    }

    /**
     * Removes the tables needed for the Records used by the plugin.
     */
    protected function removeTables(): void
    {
        // marketplace_fees table
        $this->dropTableIfExists('{{%marketplace_fees}}');
    }
}
