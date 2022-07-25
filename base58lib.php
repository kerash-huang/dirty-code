<?php
/**
 *  translate the do58Encode js code to php from Tronscan source code
 *  base58lib::do58Encode(string)
 *     
 */
class base58lib {
    static function do58Encode($str) {
        $bytes = self::hexStr2byteArray($str);
        $encode_str = self::getBase58CheckAddress($bytes);
        return $encode_str;
    }


    static function hexStr2byteArray($str) {
        $byteArray = array();
        $d = 0;
        $i = 0;
        $j = 0;
        $k = 0;

        for ($i = 0; $i < strlen($str); $i++) {
            $c = substr($str, $i, 1);
            if (self::isHexChar($c)) {
                $d = $d << 4;
                $d += self::hexChar2byte($c);
                $j++;
                if (0 == ($j % 2)) {
                    $byteArray[$k++] = $d;
                    $d = 0;
                }
            }
        }
        return $byteArray;
    }


    static function isHexChar($c) {
        if (($c >= 'A' && $c <= 'F') ||
            ($c >= 'a' && $c <= 'f') ||
            ($c >= '0' && $c <= '9')
        ) {
            return 1;
        }
        return 0;
    }

    static function hexChar2byte($c) {
        $d = 0;
        if ($c >= 'A' && $c <= 'F') {
            $d = ord($c) - ord('A') + 10;
        } else if ($c >= 'a' && $c <= 'f') {
            $d = ord($c) - ord('a') + 10;
        } else if ($c >= '0' && $c <= '9') {
            $d = ord($c) - ord('0');
        }
        return $d;
    }

    static function getBase58CheckAddress($addressBytes) {

        $hash0 = openssl_digest(implode(array_map('chr', $addressBytes)), 'sha256', true);
        $hash1 = openssl_digest($hash0, 'sha256', true);
        $hash1 = str_split($hash1, 1);
        $hash1 = array_filter($hash1, 'strlen');
        $hash1 = array_map('ord', $hash1);
        $checkSum = array_slice($hash1, 0, 4);
        $checkSum = array_merge($addressBytes, $checkSum);
        $base58Check = self::encode58($checkSum);

        return $base58Check;
    }



    static function encode58($buffer) {
        $ALPHABET = '123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz';
        $ALPHABET_KEY = str_split($ALPHABET, 1);
        $BASE = 58;

        $i = 0;
        $j = 0;
        $digits = [0];
        for ($i = 0; $i < count($buffer); $i++) {
            for ($j = 0; $j < count($digits); $j++) {
                $digits[$j] = $digits[$j] << 8;
            }

            $digits[0] += $buffer[$i];

            $carry = 0;
            for ($j = 0; $j < count($digits); ++$j) {
                $digits[$j] += $carry;

                $carry = ($digits[$j] / $BASE) | 0;
                $digits[$j] %= $BASE;
            }

            while ($carry) {
                array_push($digits, $carry % $BASE);
                $carry = ($carry / $BASE) | 0;
            }
        }

        // deal with leading zeros
        for ($i = 0; $buffer[$i] === 0 && $i < count($buffer) - 1; $i++) {
            array_push($digits, 0);
        }

        $digits = array_reverse($digits, true);

        $new_digits = array();
        foreach ($digits as $d) {
            $new_digits[] = $ALPHABET_KEY[$d];
        }
        $str = implode('', $new_digits);
        return $str;
    }
}
