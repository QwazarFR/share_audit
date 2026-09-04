<?php
declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Ricardo Ferreira <rsfneg@gmail.com>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\ShareAuditDashboard\Tests\Unit;

use OCA\ShareAuditDashboard\Db\DeletedShare;
use PHPUnit\Framework\TestCase;

/**
 * Regression test for issue #15 (soft-delete fails for user shares, whose
 * share_type is 0). Every NOT NULL column in the shareaudit_deleted table
 * (see the Version0004 migration) must reach QBMapper::insert()'s column
 * list even when the value being set equals the property's old PHP default
 * -- Entity::getUpdatedFields() only reports a field as "updated" when the
 * new value differs from the current one, so a domain-valid zero-ish
 * default (e.g. int 0, which is also the real share_type for a user share)
 * silently dropped that column from the INSERT, and the DB rejected it
 * ("Field 'share_type' doesn't have a default value"). Fixed by defaulting
 * these properties to null instead -- see DeletedShare's own comment.
 */
class DeletedShareTest extends TestCase {

    /**
     * @dataProvider insertRequiredFieldsProvider
     */
    public function testSettingTheRiskyValueOfAnInsertRequiredFieldStillMarksItUpdated(string $setter, string $getter, mixed $riskyValue): void {
        $entity = new DeletedShare();
        $entity->$setter($riskyValue);

        $this->assertSame($riskyValue, $entity->$getter());

        $field = lcfirst(substr($setter, 3));
        $this->assertArrayHasKey(
            $field,
            $entity->getUpdatedFields(),
            "$setter() with its old zero-value default must still mark '$field' as updated, "
            . 'or QBMapper::insert() silently omits this NOT NULL column'
        );
    }

    public static function insertRequiredFieldsProvider(): array {
        return [
            'shareType (0 = user share, issue #15)' => ['setShareType', 'getShareType', 0],
            'originalShareId' => ['setOriginalShareId', 'getOriginalShareId', 0],
            'permissions (0 = no permissions)' => ['setPermissions', 'getPermissions', 0],
            'uidOwner' => ['setUidOwner', 'getUidOwner', ''],
            'itemType' => ['setItemType', 'getItemType', ''],
            'deletedAt' => ['setDeletedAt', 'getDeletedAt', 0],
            'purgeAfter' => ['setPurgeAfter', 'getPurgeAfter', 0],
        ];
    }
}
