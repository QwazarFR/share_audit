<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Ricardo Ferreira <rsfneg@gmail.com>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\ShareAuditDashboard\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/**
 * Reviewed-alert state for issue #5. The hasTable() guard keeps reinstalls
 * safe if the app was removed without dropping its tables.
 */
class Version0005Date20260902120000 extends SimpleMigrationStep {

    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
        /** @var ISchemaWrapper $schema */
        $schema = $schemaClosure();

        if (!$schema->hasTable('shareaudit_reviewed')) {
            $table = $schema->createTable('shareaudit_reviewed');
            $table->addColumn('id', Types::BIGINT, [
                'autoincrement' => true,
                'notnull' => true,
            ]);
            $table->addColumn('scope', Types::STRING, [
                'notnull' => true,
                'length' => 128,
            ]);
            $table->addColumn('share_id', Types::BIGINT, [
                'notnull' => true,
            ]);
            $table->addColumn('fingerprint', Types::STRING, [
                'notnull' => true,
                'length' => 64,
            ]);
            $table->addColumn('reviewed_by', Types::STRING, [
                'notnull' => false,
                'length' => 64,
            ]);
            $table->addColumn('reviewed_at', Types::BIGINT, [
                'notnull' => true,
            ]);

            $table->setPrimaryKey(['id']);
            $table->addUniqueIndex(['scope', 'share_id', 'fingerprint'], 'shareaudit_rev_unique');
            $table->addIndex(['scope', 'fingerprint'], 'shareaudit_rev_scope_fp');
        }

        return $schema;
    }
}
