<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Ricardo Ferreira <rsfneg@gmail.com>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\ShareAuditDashboard\Db;

use OCP\AppFramework\Db\Entity;

class ReviewedShare extends Entity {
    protected string $scope = '';
    protected int $shareId = 0;
    protected string $fingerprint = '';
    protected ?string $reviewedBy = null;
    protected int $reviewedAt = 0;

    public function __construct() {
        $this->addType('scope', 'string');
        $this->addType('shareId', 'integer');
        $this->addType('fingerprint', 'string');
        $this->addType('reviewedBy', 'string');
        $this->addType('reviewedAt', 'integer');
    }
}
