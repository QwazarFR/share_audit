<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Ricardo Ferreira <rsfneg@gmail.com>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\ShareAuditDashboard\Db;

use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

class ReviewedShareMapper extends QBMapper {

    public function __construct(IDBConnection $db) {
        parent::__construct($db, 'shareaudit_reviewed', ReviewedShare::class);
    }

    /**
     * @return array<string, true> fingerprint set for quick lookups
     */
    public function fingerprintSet(string $scope): array {
        $qb = $this->db->getQueryBuilder();
        $qb->select('fingerprint')
            ->from($this->getTableName())
            ->where($qb->expr()->eq('scope', $qb->createNamedParameter($scope)));

        $result = $qb->executeQuery();
        $set = [];
        while (($fingerprint = $result->fetchOne()) !== false) {
            $set[(string)$fingerprint] = true;
        }
        $result->closeCursor();
        return $set;
    }

    public function mark(string $scope, int $shareId, string $fingerprint, ?string $reviewedBy, int $reviewedAt): void {
        if ($this->exists($scope, $shareId, $fingerprint)) {
            $qb = $this->db->getQueryBuilder();
            $qb->update($this->getTableName())
                ->set('reviewed_by', $qb->createNamedParameter($reviewedBy))
                ->set('reviewed_at', $qb->createNamedParameter($reviewedAt, IQueryBuilder::PARAM_INT))
                ->where($qb->expr()->eq('scope', $qb->createNamedParameter($scope)))
                ->andWhere($qb->expr()->eq('share_id', $qb->createNamedParameter($shareId, IQueryBuilder::PARAM_INT)))
                ->andWhere($qb->expr()->eq('fingerprint', $qb->createNamedParameter($fingerprint)));
            $qb->executeStatement();
            return;
        }

        $entity = new ReviewedShare();
        $entity->setScope($scope);
        $entity->setShareId($shareId);
        $entity->setFingerprint($fingerprint);
        $entity->setReviewedBy($reviewedBy);
        $entity->setReviewedAt($reviewedAt);
        $this->insert($entity);
    }

    /**
     * @param string[] $fingerprints
     */
    public function deleteByFingerprints(string $scope, array $fingerprints): int {
        if ($fingerprints === []) {
            return 0;
        }
        $qb = $this->db->getQueryBuilder();
        $qb->delete($this->getTableName())
            ->where($qb->expr()->eq('scope', $qb->createNamedParameter($scope)))
            ->andWhere($qb->expr()->in('fingerprint', $qb->createNamedParameter($fingerprints, IQueryBuilder::PARAM_STR_ARRAY)));
        return $qb->executeStatement();
    }

    public function deleteByScope(string $scope): int {
        $qb = $this->db->getQueryBuilder();
        $qb->delete($this->getTableName())
            ->where($qb->expr()->eq('scope', $qb->createNamedParameter($scope)));
        return $qb->executeStatement();
    }

    private function exists(string $scope, int $shareId, string $fingerprint): bool {
        $qb = $this->db->getQueryBuilder();
        $qb->select('id')
            ->from($this->getTableName())
            ->where($qb->expr()->eq('scope', $qb->createNamedParameter($scope)))
            ->andWhere($qb->expr()->eq('share_id', $qb->createNamedParameter($shareId, IQueryBuilder::PARAM_INT)))
            ->andWhere($qb->expr()->eq('fingerprint', $qb->createNamedParameter($fingerprint)))
            ->setMaxResults(1);
        try {
            $this->findEntity($qb);
            return true;
        } catch (DoesNotExistException) {
            return false;
        }
    }
}
