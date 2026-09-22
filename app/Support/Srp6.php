<?php

namespace App\Support;

class Srp6
{
    // SRP6 constants used by WoW (Trinity/AzerothCore). g = 7, N is 256-bit prime.
    // N value sourced from TrinityCore implementation.
    private const G = 7;
    private const N_HEX = '894B645E89E1535BBDAD5B8B290650530801B18EBFBF5E8FAB3C82872A3E9BB7';

    /**
     * Compute SRP6 verifier for WoW using SHA1, username/password uppercased.
     * Returns 32-byte little-endian binary string as stored in account.verifier.
     */
    public static function computeVerifier(string $username, string $password, string $saltBytes): string
    {
        $userUpper = strtoupper($username);
        $passUpper = strtoupper($password);

        // h1 = SHA1( UPPER(username) + ':' + UPPER(password) ) -> binary
        $h1 = sha1($userUpper . ':' . $passUpper, true);

        // h2 = SHA1( salt || h1 ) -> binary
        $h2 = sha1($saltBytes . $h1, true);

        // Convert h2 (20 bytes) to big integer treating as little-endian per Trinity
        $x = self::binLeToBigInt($h2);

        $N = self::hexToBigInt(self::N_HEX);
        $g = gmp_init(self::G, 10);

        // v = g^x mod N
        $v = gmp_powm($g, $x, $N);

        // Export v as 32-byte little-endian
        $vLe = self::bigIntToBinLe($v, 32);

        return $vLe;
    }

    private static function hexToBigInt(string $hex)
    {
        return gmp_init($hex, 16);
    }

    private static function binLeToBigInt(string $littleEndianBytes)
    {
        $be = strrev($littleEndianBytes);
        $hex = bin2hex($be);
        if ($hex === '') {
            return gmp_init(0, 10);
        }
        return gmp_init($hex, 16);
    }

    private static function bigIntToBinLe($num, int $length): string
    {
        if (gmp_cmp($num, 0) === 0) {
            return str_repeat("\x00", $length);
        }

        $hex = gmp_strval($num, 16);
        if (strlen($hex) % 2 === 1) {
            $hex = '0' . $hex;
        }
        $be = hex2bin($hex);
        $le = strrev($be);
        if (strlen($le) < $length) {
            $le = $le . str_repeat("\x00", $length - strlen($le));
        } else if (strlen($le) > $length) {
            $le = substr($le, 0, $length);
        }
        return $le;
    }
}


