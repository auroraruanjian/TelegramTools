<?php

/**
 * Raw DSA Key Handler
 *
 * PHP version 5
 *
 * Reads and creates arrays as DSA keys
 *
 * @category  Crypt
 * @package   DSA
 * @author    Jim Wigginton <terrafrost@php.net>
 * @copyright 2015 Jim Wigginton
 * @license   http://www.opensource.org/licenses/mit-license.html  MIT License
 * @link      http://phpseclib.sourceforge.net
 */
namespace tgseclib\Crypt\DSA\Formats\Keys;

use tgseclib\Math\BigInteger;
/**
 * Raw DSA Key Handler
 *
 * @package DSA
 * @author  Jim Wigginton <terrafrost@php.net>
 * @access  public
 */
abstract class Raw
{
    /**
     * Break a public or private key down into its constituent components
     *
     * @access public
     * @param array $key
     * @param string $password optional
     * @return array
     */
    public static function load($key, $password = '')
    {
        if (!is_array($key)) {
            throw new \UnexpectedValueException('Key should be a array - not a ' . gettype($key));
        }
        switch (true) {
            case !isset($key['p']) || !isset($key['q']) || !isset($key['g']):
            case !$key['p'] instanceof BigInteger:
            case !$key['q'] instanceof BigInteger:
            case !$key['g'] instanceof BigInteger:
            case !isset($key['x']) && !isset($key['y']):
            case isset($key['x']) && !$key['x'] instanceof BigInteger:
            case isset($key['y']) && !$key['y'] instanceof BigInteger:
                throw new \UnexpectedValueException('Key appears to be malformed');
        }
        $options = ['p' => 1, 'q' => 1, 'g' => 1, 'x' => 1, 'y' => 1];
        return array_intersect_key($key, $options);
    }
    /**
     * Convert a private key to the appropriate format.
     *
     * @access public
     * @param \tgseclib\Math\BigInteger $p
     * @param \tgseclib\Math\BigInteger $q
     * @param \tgseclib\Math\BigInteger $g
     * @param \tgseclib\Math\BigInteger $y
     * @param \tgseclib\Math\BigInteger $x
     * @param string $password optional
     * @return string
     */
    public static function savePrivateKey(BigInteger $p, BigInteger $q, BigInteger $g, BigInteger $y, BigInteger $x, $password = '')
    {
        return compact('p', 'q', 'g', 'y', 'x');
    }
    /**
     * Convert a public key to the appropriate format
     *
     * @access public
     * @param \tgseclib\Math\BigInteger $p
     * @param \tgseclib\Math\BigInteger $q
     * @param \tgseclib\Math\BigInteger $g
     * @param \tgseclib\Math\BigInteger $y
     * @return string
     */
    public static function savePublicKey(BigInteger $p, BigInteger $q, BigInteger $g, BigInteger $y)
    {
        return compact('p', 'q', 'g', 'y');
    }
}