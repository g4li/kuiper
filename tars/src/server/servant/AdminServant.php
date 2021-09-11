<?php

declare(strict_types=1);

/**
 * NOTE: This class is auto generated by Tars Generator (https://github.com/wenbinye/tars-generator).
 *
 * Do not edit the class manually.
 * Tars Generator version: 1.0
 */

namespace kuiper\tars\server\servant;

use kuiper\tars\annotation\TarsParameter;
use kuiper\tars\annotation\TarsReturnType;
use kuiper\tars\annotation\TarsServant;

/**
 * @TarsServant("AdminObj")
 */
interface AdminServant
{
    /**
     * For healthy check.
     *
     * @TarsReturnType("string")
     *
     * @return string
     */
    public function ping(): string;

    /**
     * Get server stat.
     *
     * @TarsReturnType("Stat")
     *
     * @return Stat
     */
    public function stats(): Stat;

    /**
     * receive notification.
     *
     * @TarsParameter(name="notification", type="Notification")
     * @TarsReturnType("void")
     *
     * @param Notification $notification
     *
     * @return void
     */
    public function notify(Notification $notification): void;
}
