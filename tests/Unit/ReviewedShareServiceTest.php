<?php
declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Ricardo Ferreira <rsfneg@gmail.com>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\ShareAuditDashboard\Tests\Unit;

use OCA\ShareAuditDashboard\Db\ReviewedShareMapper;
use OCA\ShareAuditDashboard\Service\ReviewedShareService;
use OCP\AppFramework\Utility\ITimeFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ReviewedShareServiceTest extends TestCase {

    private ReviewedShareMapper&MockObject $mapper;
    private ITimeFactory&MockObject $time;

    protected function setUp(): void {
        $this->mapper = $this->createMock(ReviewedShareMapper::class);
        $this->time = $this->createMock(ITimeFactory::class);
    }

    public function testAnnotateHidesReviewedAlertsByDefault(): void {
        $service = $this->service();
        $alert = $this->alert();
        $this->mapper->method('fingerprintSet')->with('admin')->willReturn([
            $service->fingerprint($alert) => true,
        ]);

        $this->assertSame([], $service->annotate([$alert], 'admin', false));
    }

    public function testAnnotateCanIncludeReviewedAlerts(): void {
        $service = $this->service();
        $alert = $this->alert();
        $this->mapper->method('fingerprintSet')->willReturn([
            $service->fingerprint($alert) => true,
        ]);

        $items = $service->annotate([$alert], 'admin', true);

        $this->assertCount(1, $items);
        $this->assertTrue($items[0]['reviewed']);
        $this->assertSame($service->fingerprint($alert), $items[0]['fingerprint']);
    }

    public function testFingerprintChangesWhenRiskChanges(): void {
        $service = $this->service();
        $reviewed = $this->alert(['issues' => [['code' => 'no_password', 'severity' => 'critical']]]);
        $changed = $this->alert(['issues' => [['code' => 'no_expiration', 'severity' => 'warning']]]);

        $this->assertNotSame($service->fingerprint($reviewed), $service->fingerprint($changed));
    }

    /**
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    private function alert(array $overrides = []): array {
        return array_merge([
            'id' => 42,
            'shareType' => 3,
            'owner' => 'alice',
            'initiator' => 'alice',
            'recipient' => '',
            'fileId' => 100,
            'path' => '/report.txt',
            'permissions' => 1,
            'hasPassword' => false,
            'expiration' => null,
            'issues' => [['code' => 'no_password', 'severity' => 'critical']],
        ], $overrides);
    }

    private function service(): ReviewedShareService {
        return new ReviewedShareService($this->mapper, $this->time);
    }
}
