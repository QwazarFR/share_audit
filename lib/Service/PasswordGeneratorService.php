<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Ricardo Ferreira <rsfneg@gmail.com>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\ShareAuditDashboard\Service;

use OCP\IAppConfig;
use OCP\Security\ISecureRandom;

/**
 * Generates strong random passwords for public links, mixed enough to satisfy
 * typical Nextcloud password policies (upper, lower, digit, symbol).
 */
class PasswordGeneratorService {

    private const SYMBOLS = '!@#$%&*?';
    private const LENGTH = 14;

    public function __construct(
        private ISecureRandom $random,
        private IAppConfig $appConfig,
    ) {
    }

    public function generate(): string {
        $length = $this->targetLength();

        // Guarantee at least one character from each class.
        $chars = [
            $this->random->generate(1, ISecureRandom::CHAR_UPPER),
            $this->random->generate(1, ISecureRandom::CHAR_LOWER),
            $this->random->generate(1, ISecureRandom::CHAR_DIGITS),
            $this->random->generate(1, self::SYMBOLS),
        ];

        $all = ISecureRandom::CHAR_UPPER . ISecureRandom::CHAR_LOWER
            . ISecureRandom::CHAR_DIGITS . self::SYMBOLS;
        for ($i = count($chars); $i < $length; $i++) {
            $chars[] = $this->random->generate(1, $all);
        }

        // Shuffle so the guaranteed characters are not always in front.
        shuffle($chars);
        return implode('', $chars);
    }

    /**
     * At least self::LENGTH, but never shorter than the instance's own
     * "password_policy" app minimum -- otherwise a generated password fails
     * that app's own validation right after we hand it to IShare::setPassword()
     * (surfaced to the admin as a generic "The action could not be
     * completed.", see issue #9). password_policy is not a declared
     * dependency (few instances disable it, and generating a slightly longer
     * password than strictly required is harmless when it's absent), so we
     * read its config directly with a safe fallback rather than adding one.
     * It only applies a share-specific minimum ("minLength_sharing") when an
     * admin has explicitly opted into per-context policies; otherwise every
     * password -- account or share -- is governed by the plain "minLength"
     * key, so that is the fallback here too.
     */
    private function targetLength(): int {
        $sharing = $this->appConfig->getValueInt('password_policy', 'minLength_sharing', 0);
        $account = $this->appConfig->getValueInt('password_policy', 'minLength', self::LENGTH);
        return max(self::LENGTH, $sharing > 0 ? $sharing : $account);
    }
}
