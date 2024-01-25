<?php

/**
 * TeslaHelper.php
 *
 * Part of the Trait-Libraray for IP-Symcon Modules.
 *
 * @package       traits
 * @author        Heiko Wilknitz <heiko@wilkware.de>
 * @copyright     2024 Heiko Wilknitz
 * @link          https://wilkware.de
 * @license       https://creativecommons.org/licenses/by-nc-sa/4.0/ CC BY-NC-SA 4.0
 */

declare(strict_types=1);

/**
 * Helper class for create variable profiles.
 */
trait TeslaHelper
{
    /**
     * Help function to determine the IP-Symcon variable type from json value
     *
     * @param mixed $value JSON value
     *
     * @return int Symcon variable type, default string(3)
     */
    private function GetVariableType($value)
    {
        if (is_bool($value)) {
            return 0; // Boolean
        } elseif (is_int($value)) {
            return 1; // Integer
        } elseif (is_float($value)) {
            return 2; // Float
        } else {
            return 3; // String
        }
    }

    /**
     * Generates an IPS-compliant IDENT from the transferred name
     *
     * @param string $name Name for the variable ident
     *
     * @return string Ident
     */
    private function GetVariableIdent($name)
    {
        $umlaute = ['/ä/', '/ö/', '/ü/', '/Ä/', '/Ö/', '/Ü/', '/ß/'];
        $replace = ['ae', 'oe', 'ue', 'Ae', 'Oe', 'Ue', 'ss'];
        $ident = preg_replace($umlaute, $replace, $name);
        // idents always lowercase?!?
        $ident = strtolower($ident);
        return preg_replace('/[^a-z0-9_]+/i', '', $ident);
    }

}
