<?php
declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Ricardo Ferreira <rsfneg@gmail.com>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\ShareAuditDashboard\Tests\Unit;

use OCA\ShareAuditDashboard\Service\PasswordGeneratorService;
use OCP\IAppConfig;
use OCP\Security\ISecureRandom;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Regression test for issue #9: a generated password shorter than the
 * instance's own "password_policy" minimum length is rejected right after
 * generation (IShare::setPassword() -> a generic "The action could not be
 * completed."), because the app hardcoded a 14-character length regardless
 * of that policy.
 */
class PasswordGeneratorServiceTest extends TestCase {

    private ISecureRandom&MockObject $random;
    private IAppConfig&MockObject $appConfig;
    private PasswordGeneratorService $service;

    protected function setUp(): void {
        $this->random = $this->createMock(ISecureRandom::class);
        // Real generator, so the length assertions reflect actual output.
        $this->random->method('generate')->willReturnCallback(
            fn (int $length, string $chars) => str_repeat(substr($chars, 0, 1), $length)
        );
        $this->appConfig = $this->createMock(IAppConfig::class);
        $this->service = new PasswordGeneratorService($this->random, $this->appConfig);
    }

    public function testDefaultsToFourteenCharactersWhenPasswordPolicyIsNotConfigured(): void {
        $this->appConfig->method('getValueInt')->willReturn(0);

        $this->assertSame(14, strlen($this->service->generate()));
    }

    public function testUsesTheAccountMinLengthWhenLongerThanTheDefault(): void {
        $this->appConfig->method('getValueInt')
            ->willReturnCallback(fn (string $app, string $key, int $default) => match ($key) {
                'minLength_sharing' => 0,
                'minLength' => 20,
                default => $default,
            });

        $this->assertSame(20, strlen($this->service->generate()));
    }

    public function testPrefersTheSharingSpecificMinLengthWhenSet(): void {
        $this->appConfig->method('getValueInt')
            ->willReturnCallback(fn (string $app, string $key, int $default) => match ($key) {
                'minLength_sharing' => 25,
                'minLength' => 20,
                default => $default,
            });

        $this->assertSame(25, strlen($this->service->generate()));
    }

    public function testNeverGoesBelowFourteenEvenWithALowPolicyMinimum(): void {
        $this->appConfig->method('getValueInt')
            ->willReturnCallback(fn (string $app, string $key, int $default) => match ($key) {
                'minLength_sharing' => 0,
                'minLength' => 8,
                default => $default,
            });

        $this->assertSame(14, strlen($this->service->generate()));
    }
}
