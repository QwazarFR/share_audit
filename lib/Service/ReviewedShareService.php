<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Ricardo Ferreira <rsfneg@gmail.com>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\ShareAuditDashboard\Service;

use OCA\ShareAuditDashboard\Db\ReviewedShareMapper;
use OCP\AppFramework\Utility\ITimeFactory;

class ReviewedShareService {
    public const ADMIN_SCOPE = 'admin';

    public function __construct(
        private ReviewedShareMapper $mapper,
        private ITimeFactory $time,
    ) {
    }

    public function personalScope(string $uid): string {
        return 'personal:' . $uid;
    }

    /**
     * @param array<int, array<string, mixed>> $alerts
     * @return array<int, array<string, mixed>>
     */
    public function annotate(array $alerts, string $scope, bool $includeReviewed): array {
        $reviewed = $this->mapper->fingerprintSet($scope);
        $out = [];
        foreach ($alerts as $alert) {
            $fingerprint = $this->fingerprint($alert);
            $alert['fingerprint'] = $fingerprint;
            $alert['reviewed'] = isset($reviewed[$fingerprint]);
            if ($includeReviewed || !$alert['reviewed']) {
                $out[] = $alert;
            }
        }
        return $out;
    }

    /**
     * @param array<int, array<string, mixed>> $alerts
     */
    public function markAlerts(string $scope, array $alerts, ?string $actor): int {
        $now = $this->time->getTime();
        $count = 0;
        foreach ($alerts as $alert) {
            $this->mapper->mark($scope, (int)$alert['id'], $this->fingerprint($alert), $actor, $now);
            $count++;
        }
        return $count;
    }

    /**
     * @param array<int, array<string, mixed>> $alerts
     */
    public function unmarkAlerts(string $scope, array $alerts): int {
        return $this->mapper->deleteByFingerprints($scope, array_map([$this, 'fingerprint'], $alerts));
    }

    public function reset(string $scope): int {
        return $this->mapper->deleteByScope($scope);
    }

    /**
     * @param array<string, mixed> $alert
     */
    public function fingerprint(array $alert): string {
        $payload = [
            'id' => (int)($alert['id'] ?? 0),
            'shareType' => (int)($alert['shareType'] ?? 0),
            'owner' => (string)($alert['owner'] ?? ''),
            'initiator' => (string)($alert['initiator'] ?? ''),
            'recipient' => (string)($alert['recipient'] ?? ''),
            'fileId' => $alert['fileId'] ?? null,
            'path' => (string)($alert['path'] ?? ''),
            'permissions' => (int)($alert['permissions'] ?? 0),
            'hasPassword' => (bool)($alert['hasPassword'] ?? false),
            'expiration' => $alert['expiration'] ?? null,
            'issues' => array_values(array_map(
                static fn (array $issue): string => (string)$issue['code'],
                $alert['issues'] ?? [],
            )),
        ];
        sort($payload['issues']);
        return hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR));
    }
}
