<?php

namespace CE102R51;

use Exception;

class Response
{
    public static array $code = [
        "\x00\x01" => "Ping",
        "\x00\x03" => "TimeSync",
        "\x01\x00" => "Version",
        "\x01\x01" => "ReadConfig",
        "\x01\x02" => "WriteConfig",
        "\x01\x03" => "ReadStatus",
        "\x01\x0D" => "ReadRTCCorrection",
        "\x01\x0E" => "WriteRTCCorrection",
        "\x01\x17" => "WritePsw",
        "\x01\x1A" => "ReadSerialNumber",
        "\x01\x20" => "ReadDateTime",
        "\x01\x21" => "WriteDateTime",
        "\x01\x28" => "ReadMeterCode",
        "\x01\x2A" => "RTCCorrectMode",
        "\x01\x2F" => "ReadDaysEnergy",
        "\x01\x30" => "ReadMonthEnergy",
        "\x01\x38" => "ReadJournalEvent",
        "\x01\x3F" => "ActivateTarProg",
        "\x01\x40" => "ReadSeason",
        "\x01\x41" => "WriteSeson",
        "\x01\x42" => "ReadSpecDays",
        "\x01\x43" => "WriteSpecDays",
        "\x01\x44" => "ReadDaySched",
        "\x01\x45" => "WriteDaySched",
        "\x01\x5A" => "ReadHourZimaLeto",
        "\x01\x5B" => "WriteHourZimaLeto",
        "\x01\x60" => "SKOP",
        "\x01\x77" => "ClearTarProg",
    ];
    function __construct(){

    }

    function getCode($name): int|string
    {
        return array_keys(static::$code, $name)[0];

    }

    static public function Ping($data){
        return unpack("v*", $data)[1];
    }

    static public function TimeSync($data){
        return $data;
    }

    static public function Version($data){
        $len = self::lenData($data);
        if ($len > 2){
            $hex_data = self::unpakedToHex("CVer/CType/CFWare/CDay/CMonth/CYear", $data);
            return array(
                "Version" => self::HexUint8ToDec($hex_data['Ver']),
                "Type" => self::HexUint8ToDec($hex_data['Type']),
                "Firmware" => self::HexUint8ToDec($hex_data['FWare']),
                "Date" => self::HexBSDToDec($hex_data['Day']) . 'Response.php/' . self::HexBSDToDec($hex_data['Month']).'/' . self::HexBSDToDec($hex_data['Year'])
            );
        }elseif ($len == 2){
            $hex_data = self::unpakedToHex("CVer/CSubver", $data);
            return array(
                "Version" => self::HexUint8ToDec($hex_data['Ver']),
                "SubVesion" => self::HexUint8ToDec($hex_data['Subver']),
            );
        }else{
            return false;
        }


    }

    /**
     * @throws Exception
     */
    static public function ReadConfig($data): array
    {
        $result = array();
        $len = self::lenData($data);
        if ($len == 6){
            $hex_data = self::unpakedToHex("C*", $data);
            echo '<pre>';print_r($hex_data);
            for($i = 1; $i <= self::lenData($data); ++$i) {
                switch ($i) {
                    case 1:
                        $result['EmTariffN'] = hexdec($hex_data[$i]);
                        break;
                    case 2:
                        $getbits = strrev(self::hex2bit(hexdec($hex_data[$i])));
                        $result['MaskOfTariffs'] = array();
                        $result['MaskOfTariffs']['ViewTarif1'] = mb_substr($getbits, 0, 1);
                        $result['MaskOfTariffs']['ViewTarif2'] = mb_substr($getbits, 1, 1);
                        $result['MaskOfTariffs']['ViewTarif3'] = mb_substr($getbits, 2, 1);
                        $result['MaskOfTariffs']['ViewTarif4'] = mb_substr($getbits, 3, 1);
                        $result['MaskOfTariffs']['ViewTarif5'] = mb_substr($getbits, 4, 1);
                        $result['MaskOfTariffs']['Reserverd1'] = mb_substr($getbits, 5, 1);
                        $result['MaskOfTariffs']['Reserverd2'] = mb_substr($getbits, 6, 1);
                        $result['MaskOfTariffs']['Reserverd3'] = mb_substr($getbits, 7, 1);
                        break;
                    case 3:
                        $getbits = strrev(self::hex2bit(hexdec($hex_data[$i])));
                        $result['DisplayIndications'] = array();
                        $result['DisplayIndications']['TimeIndications'] = bindec(mb_substr($getbits, 0, 5));
                        $result['DisplayIndications']['ViewFormat'] = mb_substr($getbits, 6, 1);
                        $result['DisplayIndications']['AutoBackGroup'] = mb_substr($getbits, 7, 1);
                        break;
                    case 4:
                        $result['Reserved4'] = $hex_data[$i];
                        break;
                    case 5:
                        $result['Reserved5'] = $hex_data[$i];
                        break;
                    case 6:
                        $result['Reserved6'] = $hex_data[$i];
                        break;
                        #echo "i равно 2";
                }

            }
        }else{
            throw new Exception("Data too short");
        }
        return $result;
    }

    static public function ReadMonthEnergy($data): mixed
    {
        #echo self::lenData($data);
        #echo bin2hex($data);
        return self::unpakedToHex("CDay/CMonth/CYear/VData", $data);
            #unpack("CDay/CMonth/CYear/VData", $data);
    }

    static public function ReadDaysEnergy($data){
        #echo self::lenData($data);
        #echo bin2hex($data);
        $result = self::unpakedToHex("CDay/CMonth/CYear/VData", $data);
        $result['Data'] = hexdec($result['Data'])/100;
        return $result;
    }

    /**
     * @param $uint8_t
     * @return float|int
     */
    static private function HexUint8ToDec($uint8_t): float|int
    {
        return hexdec($uint8_t);
    }

    /**
     * @param $bsd
     * @return float|int
     */
    static private function HexBSDToDec($bsd): float|int
    {
        return (int) $bsd;
    }

    static private function lenData($data): float|int
    {
        return  strlen(bin2hex($data))/2;
    }

    static private function unpakedToHex($format, $data): array
    {
        $unpacked = unpack($format, $data);
        return array_map('dechex', $unpacked);
    }

    static private function hex2bit($bytes){
        return sprintf("%08b", hexdec($bytes));
    }

}