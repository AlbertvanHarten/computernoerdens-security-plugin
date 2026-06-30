<?php

namespace Computernoerden\Security\Settings;

defined('ABSPATH') || exit;

/**
 * Determines whether the Pro edition is active.
 *
 * The Free plugin always reports 'free'. A future Pro add-on (a
 * separate small plugin/library that loads after this one) flips this
 * by hooking the 'cno_security/edition' filter — no changes to Free
 * code required. See docs/FREE-PRO.md for the feature split.
 */
class EditionManager
{
    const FREE = 'free';
    const PRO = 'pro';

    public function edition(): string
    {
        $edition = apply_filters('cno_security/edition', self::FREE);

        return $edition === self::PRO ? self::PRO : self::FREE;
    }

    public function isPro(): bool
    {
        return $this->edition() === self::PRO;
    }

    /**
     * Convenience for gating a feature: returns true when either the
     * site is on Pro, or the feature isn't Pro-restricted to begin
     * with.
     */
    public function allows(bool $isProFeature): bool
    {
        return !$isProFeature || $this->isPro();
    }
}
