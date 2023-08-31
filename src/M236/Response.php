<?php

namespace M236;

use EnergyMeters\utils;

class Response
{
    public static array $code = [
        "\x00" => "Ping",
    ];
    private utils $utils;

    function __construct(){

    }

    function getCode($name): int|string
    {
        return array_keys(static::$code, $name)[0];

    }

    static public function Ping($data , $time){
       echo "</br> Ping: ".(new Utils)->milliseconds() - $time.' ms';

    }


}