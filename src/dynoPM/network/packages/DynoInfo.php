<?php
/**
 * ________
 * ___  __ \____  ______________
 * __  / / /_  / / /_  __ \  __ \
 * _  /_/ /_  /_/ /_  / / / /_/ /
 * /_____/ _\__, / /_/ /_/\____/
 *         /____/
 *
 * This program is free: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is based on PocketMine Software and Synapse.
 *
 * @copyright (c) 2018
 * @author Y$SS-YassLV
 */

declare(strict_types=1);

namespace dynoPM\network\packages;

class DynoInfo
{

    public const CURRENT_PROTOCOL = 1;
    public const CONFIG_VERSION = 1.0;

    public const PROTOCOL_MAGIC = 0xbabe;

    public const HEARTBEAT_PACKET = 0x70;
    public const CONNECT_PACKET = 0x71;
    public const DISCONNECT_PACKET = 0x72;
    public const INFORMATION_PACKET = 0x73;
    public const INPUT_PACKET = 0x74;
    public const OUTPUT_PACKET = 0x75;
}
