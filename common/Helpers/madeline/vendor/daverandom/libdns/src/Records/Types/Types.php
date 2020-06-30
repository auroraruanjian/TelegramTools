<?php

declare (strict_types=1);
/**
 * Enumeration of simple data types
 *
 * PHP version 5.4
 *
 * @category LibDNS
 * @package Types
 * @author Chris Wright <https://github.com/DaveRandom>
 * @copyright Copyright (c) Chris Wright <https://github.com/DaveRandom>
 * @license http://www.opensource.org/licenses/mit-license.html MIT License
 * @version 2.0.0
 */
namespace LibDNS\Records\Types;

use LibDNS\Enumeration;
/**
 * Enumeration of simple data types
 *
 * @category LibDNS
 * @package Types
 * @author Chris Wright <https://github.com/DaveRandom>
 */
final class Types extends Enumeration
{
    const ANYTHING = 0b1;
    const BITMAP = 0b10;
    const CHAR = 0b100;
    const CHARACTER_STRING = 0b1000;
    const DOMAIN_NAME = 0b10000;
    const IPV4_ADDRESS = 0b100000;
    const IPV6_ADDRESS = 0b1000000;
    const LONG = 0b10000000;
    const SHORT = 0b100000000;
}