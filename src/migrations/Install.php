<?php

namespace kennethormandy\marketplace\migrations;

use Craft;
use verbb\auth\Auth;
use yii\db\Migration;

class Install extends Migration
{
    /**
     * The database driver to use.
     */
    public string $driver;

    public function safeUp(): bool
    {
        return true;

        // Ensure that the Auth module kicks off setting up tables
        Auth::$plugin->migrator->up();

        $this->createTables();
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
     *
     * If necessary, removes the `marketplace_fees` table created in Marketplace v1.
     */
    protected function removeTables(): void
    {
        $this->dropTableIfExists('{{%marketplace_fees}}');
    }
}
