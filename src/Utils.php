<?php

namespace EnergyMeters;

class utils
{

    function milliseconds(): int
    {
        $mt = explode(' ', microtime());
        return intval( $mt[1] * 1E3 ) + intval( round( $mt[0] * 1E3 ) );
    }
    // Format input string as nice hex
    function nice_hex($str): string
    {
        $str = bin2hex($str);
        return strtoupper(implode(' ',str_split($str,2)));
    }

    /**
     * @param $hex
     * @return string
     */
    function hex2str($hex ): string
    {
        return pack('C*', $hex);
    }
}