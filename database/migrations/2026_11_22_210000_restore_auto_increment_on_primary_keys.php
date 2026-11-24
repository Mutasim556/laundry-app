<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Restore missing PRIMARY KEY (if absent) and AUTO_INCREMENT on numeric `id` columns
     * (such as `bookings.id`, `migrations.id`, `customer_addresses.id`, `users.id`, etc.)
     * without deleting or altering any existing rows or foreign keys.
     */
    public function up(): void
    {
        if (! in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        $database = DB::getDatabaseName();

        $tablesMissingAutoIncrement = DB::select(
            "SELECT TABLE_NAME, COLUMN_TYPE, COLUMN_KEY
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = ?
               AND COLUMN_NAME = 'id'
               AND DATA_TYPE IN ('tinyint', 'smallint', 'mediumint', 'int', 'bigint')
               AND EXTRA NOT LIKE '%auto_increment%'
             ORDER BY TABLE_NAME",
            [$database]
        );

        if ($tablesMissingAutoIncrement === []) {
            return;
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        try {
            foreach ($tablesMissingAutoIncrement as $row) {
                $table = str_replace('`', '``', (string) $row->TABLE_NAME);
                $columnType = (string) $row->COLUMN_TYPE;
                $hasPrimaryKey = (string) $row->COLUMN_KEY === 'PRI';

                if (! $hasPrimaryKey) {
                    $existingPk = DB::select(
                        "SELECT 1 FROM information_schema.TABLE_CONSTRAINTS
                         WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND CONSTRAINT_TYPE = 'PRIMARY KEY'
                         LIMIT 1",
                        [$database, (string) $row->TABLE_NAME]
                    );

                    if ($existingPk === []) {
                        DB::statement("ALTER TABLE `{$table}` MODIFY COLUMN `id` {$columnType} NOT NULL AUTO_INCREMENT, ADD PRIMARY KEY (`id`)");
                        continue;
                    }
                }

                DB::statement("ALTER TABLE `{$table}` MODIFY COLUMN `id` {$columnType} NOT NULL AUTO_INCREMENT");
            }
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }

    /**
     * Intentionally non-destructive: do not strip AUTO_INCREMENT on rollback.
     */
    public function down(): void
    {
        //
    }
};
