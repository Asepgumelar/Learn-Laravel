<?php

namespace App\Libraries;

use Webpatser\Uuid\Uuid;

class NumberLibrary
{
    /**
     * Konvert nilai decimal ke nilai romawi.
     *
     * @param $value
     * @return bool|mixed
     */
    public static function decimalToRomawi($value)
    {
        if (!is_numeric($value) || $value > 3999 || $value <= 0) return false;
        $roman = array('M' => 1000, 'D' => 500, 'C' => 100, 'L' => 50, 'X' => 10, 'V' => 5, 'I' => 1);
        foreach ($roman as $k => $v) if (($amount[$k] = floor($value / $v)) > 0) $value -= $amount[$k] * $v;
        $return = '';
        foreach ($amount as $k => $v) {
            $return .= $v <= 3 ? str_repeat($k, $v) : $k . $old_k;
            $old_k = $k;
        }
        return str_replace(array('VIV', 'LXL', 'DCD'), array('IX', 'XC', 'CM'), $return);
    }

    /**
     * Parse nilai 1.000.000,00 jadi 1000000.
     *
     * @param $string_money
     * @param string $separator
     * @return float|int
     */
    public static function moneyToNumber($string_money, $separator = '.')
    {
        $_val = str_replace($separator, "", $string_money);
        if (is_nan($_val)) {
            $r = 0;
        } else {
            $r = (float)$_val;
        }
        return $r;
    }


    private static function generateSign($hmac = false)
    {
        $string_to_sign = Uuid::generate(4)->string;
        $auth_secret = Uuid::generate(4)->string;
        $auth_signature = base64_encode(hash_hmac('sha256', $string_to_sign, $auth_secret, false));
        if ($hmac == true) {
            $otpString = hash_hmac('sha256', $string_to_sign, $auth_secret, false);
        } else {
            $otpString = self::getRfc($auth_signature, strtotime("now"), 8);
        }
        return $otpString;
    }

    /**
     * This function implements the algorithm outlined
     * in RFC 6238 for Time-Based One-Time Passwords
     *
     * @link http://tools.ietf.org/html/rfc6238
     * @param string $key the string to use for the HMAC key
     * @param mixed $time a value that reflects a time (unix
     *                       time in the example)
     * @param int $digits the desired length of the OTP
     * @param string $crypto the desired HMAC crypto algorithm
     * @return string the generated OTP
     */
    private static function getRfc($key, $time, $digits = 8, $crypto = 'sha256')
    {
        $digits = intval($digits);
        $result = null;

        // Convert counter to binary (64-bit)
        $data = pack('NNC*', $time >> 32, $time & 0xFFFFFFFF);

        // Pad to 8 chars (if necessary)
        if (strlen($data) < 8) {
            $data = str_pad($data, 8, chr(0), STR_PAD_LEFT);
        }

        // Get the hash
        $hash = hash_hmac($crypto, $data, $key);

        // Grab the offset
        $offset = 2 * hexdec(substr($hash, strlen($hash) - 1, 1));

        // Grab the portion we're interested in
        $binary = hexdec(substr($hash, $offset, 8)) & 0x7fffffff;

        // Modulus
        $result = $binary % pow(10, $digits);

        // Pad (if necessary)
        $result = str_pad($result, $digits, "0", STR_PAD_LEFT);

        return $result;
    }

    public static function createInvoice()
    {
        $date = date('Y') . date('m') . date('d');
        $romawiDate = \App\Libraries\NumberLibrary::decimalToRomawi(date('d'));
        $romawiMonth = \App\Libraries\NumberLibrary::decimalToRomawi(date('m'));
        $uniqueNumber = \App\Libraries\NumberLibrary::generateSign();
        return 'INV' . '/' . $date . '/' . $romawiDate . '/' . $romawiMonth . '/' . $uniqueNumber;
    }
}