<?php

declare(strict_types=1);

/**
 * NOTE: This class is auto generated by Tars Generator (https://github.com/wenbinye/tars-generator).
 *
 * Do not edit the class manually.
 * Tars Generator version: 1.0
 */

namespace kuiper\tars\integration;

use kuiper\tars\annotation\TarsProperty;

final class StatPropMsgBody
{
    /**
     * @TarsProperty(order=0, required=true, type="vector<StatPropInfo>")
     *
     * @var StatPropInfo[]|null
     */
    public $vInfo;
}
