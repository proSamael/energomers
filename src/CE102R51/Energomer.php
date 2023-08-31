<?php

namespace CE102R51;

use EnergyMeters\SocketClient;
use Exception;

class Energomer implements ProtocolInterface
{

    /**
     * @var SocketClient
     */
    private SocketClient $client;


    public static string $END = "\xC0";                        # 1 байт
    public static string $OPT = "\x48";                        # 1 байт
    public static int $SERIAL = 39847;                      # широковещательный b'\xFFFF'
    public static string $ESC = "\xDB";
    public static int $ADDRESS_SOURCE = 253;                # 2 байта
    public static string $USER_PASSWORD = "\x00\x00\x00\x00";  # 4 байта
    public static array $crc8tab = [
0x00, 0xb5, 0xdf, 0x6a, 0x0b, 0xbe, 0xd4, 0x61, 0x16, 0xa3, 0xc9, 0x7c, 0x1d, 0xa8, 0xc2, 0x77,
0x2c, 0x99, 0xf3, 0x46, 0x27, 0x92, 0xf8, 0x4d, 0x3a, 0x8f, 0xe5, 0x50, 0x31, 0x84, 0xee, 0x5b,
0x58, 0xed, 0x87, 0x32, 0x53, 0xe6, 0x8c, 0x39, 0x4e, 0xfb, 0x91, 0x24, 0x45, 0xf0, 0x9a, 0x2f,
0x74, 0xc1, 0xab, 0x1e, 0x7f, 0xca, 0xa0, 0x15, 0x62, 0xd7, 0xbd, 0x08, 0x69, 0xdc, 0xb6, 0x03,
0xb0, 0x05, 0x6f, 0xda, 0xbb, 0x0e, 0x64, 0xd1, 0xa6, 0x13, 0x79, 0xcc, 0xad, 0x18, 0x72, 0xc7,
0x9c, 0x29, 0x43, 0xf6, 0x97, 0x22, 0x48, 0xfd, 0x8a, 0x3f, 0x55, 0xe0, 0x81, 0x34, 0x5e, 0xeb,
0xe8, 0x5d, 0x37, 0x82, 0xe3, 0x56, 0x3c, 0x89, 0xfe, 0x4b, 0x21, 0x94, 0xf5, 0x40, 0x2a, 0x9f,
0xc4, 0x71, 0x1b, 0xae, 0xcf, 0x7a, 0x10, 0xa5, 0xd2, 0x67, 0x0d, 0xb8, 0xd9, 0x6c, 0x06, 0xb3,
0xd5, 0x60, 0x0a, 0xbf, 0xde, 0x6b, 0x01, 0xb4, 0xc3, 0x76, 0x1c, 0xa9, 0xc8, 0x7d, 0x17, 0xa2,
0xf9, 0x4c, 0x26, 0x93, 0xf2, 0x47, 0x2d, 0x98, 0xef, 0x5a, 0x30, 0x85, 0xe4, 0x51, 0x3b, 0x8e,
0x8d, 0x38, 0x52, 0xe7, 0x86, 0x33, 0x59, 0xec, 0x9b, 0x2e, 0x44, 0xf1, 0x90, 0x25, 0x4f, 0xfa,
0xa1, 0x14, 0x7e, 0xcb, 0xaa, 0x1f, 0x75, 0xc0, 0xb7, 0x02, 0x68, 0xdd, 0xbc, 0x09, 0x63, 0xd6,
0x65, 0xd0, 0xba, 0x0f, 0x6e, 0xdb, 0xb1, 0x04, 0x73, 0xc6, 0xac, 0x19, 0x78, 0xcd, 0xa7, 0x12,
0x49, 0xfc, 0x96, 0x23, 0x42, 0xf7, 0x9d, 0x28, 0x5f, 0xea, 0x80, 0x35, 0x54, 0xe1, 0x8b, 0x3e,
0x3d, 0x88, 0xe2, 0x57, 0x36, 0x83, 0xe9, 0x5c, 0x2b, 0x9e, 0xf4, 0x41, 0x20, 0x95, 0xff, 0x4a,
0x11, 0xa4, 0xce, 0x7b, 0x1a, 0xaf, 0xc5, 0x70, 0x07, 0xb2, 0xd8, 0x6d, 0x0c, 0xb9, 0xd3, 0x66
];
    private Response $response;
    private int $serial;
    private int|string $result;
    public static array $ErrorCodes = [
        0 => "Команда отсутствует",
        1 => "Неверный формат принятого пакета",
        2 => "Недостаточный уровень доступа для выполнения команды",
        3 => "Неверное количество параметров для выполнения команды",
        4 => "Текущая конфигурация не позволяет выполнить эту команду",
        5 => "Не нажата кнопка «Доступ», для выполнения команды через оптопорт",
        16 => "Неверные параметры для выполнения команды",
        32 => "Несуществующая или неверная запись в памяти",
        64 => "Недопустимая тарифная программа",
        128 => "Ошибка чтения внешней памяти",
        ];
    /**
     *
     * @throws Exception
     */
    public function __construct($host, $port, $serial)
    {
        $this->client = new SocketClient($host, $port);
        $this->serial = $serial;
        $this->response = new Response();

    }
    /**
     * @throws Exception
     */
    private function sendPacked($command, $data = ""): void
    {
        $request = self::getPacked(code: $this->response->getCode($command), data: $data , address: $this->serial, address_source: static::$ADDRESS_SOURCE);
        $this->client->sendRequest($request);
        #echo 'request >> '.bin2hex($request)."</br>";
        $this->result = $this->client->getResponse();
        #$hex = bin2hex($this->result);
        #echo 'response << '.$hex."</br>";
        $this->client->close();
    }

    /**
     * @throws Exception
     */
    private function getServ($message_len=0): string
    {
        if ($message_len > 15) {
            throw new Exception('Длина сообщения не может быть больше 15 байт');
        }
        $message_len = str_pad(decbin($message_len), 4, '0', STR_PAD_LEFT);
        $class_access = str_pad(decbin(5), 3, '0', STR_PAD_LEFT);
        $direct = decbin(1);
        $binary_str = $direct . $class_access . $message_len;
        $binary_int = bindec($binary_str);
        return pack('H*', str_pad(dechex($binary_int), 2, '0', STR_PAD_LEFT));
    }

    /**
     * @throws Exception
     */
    function get(): mixed
    {

        #echo 'response << '.bin2hex($this->result)."</br>";
        $packed_data = $this->result;
        if($len = floor(strlen(bin2hex($packed_data))/2) <= 7){

            throw new Exception("Message too short for packed unpacking: ". $len ." bytes");
        }
        //Получем строку для проверки CRC8
        $packed_data_crc_checksum = substr($packed_data, 1, -2);
        // Убирем STR OPT END
        $packed_data = substr($packed_data, 2, -1);
        // Получаем DATA
        $data = substr($packed_data, 7, -1);
        if(strlen(bin2hex($data))/2 == 1){
            throw new Exception("ErrorCode 0x".bin2hex($data).': '.static::$ErrorCodes[hexdec(bin2hex($data))]);
        }
        //Читаем длину DATA
        $len_data = floor(strlen(bin2hex($data))/2);
        // Получаем CRC8
        $crc8 = substr($packed_data, 7+$len_data);
        //CRC8 – контрольная сумма пакета, рассчитывается до применения ESC- символов для байтов от 2 до N - 2. Вычисление
        //производится с использованием полинома 0xB5
        if(self::getCRC($packed_data_crc_checksum) != $crc8){
            throw new Exception("Invalid CRC8 checksum");
        }
        //Распаковываем
        $unpacked_data = unpack('SAddS/SAddD/CServ/nAddHL/C*Data', $packed_data);
        /* Пример
        Array
        (
          [AddS] => fd
          [AddD] => 9ba7
          [Serv] => 57
          [AddHL] => 12f
          [Data1] => 12
          [Data2] => 7
          [Data3] => 23
          [Data4] => 88
          [Data5] => 0
          [Data6] => 0
          [Data7] => 0
          [Data8] => f5
        )
        */
        $function = $this->response::$code;
        $result = call_user_func_array(array($this->response, $function[self::hex2str($unpacked_data['AddHL'])]), array($data));
        #echo '<pre>';
        return $result;
        //Массив $unpacked_data в HEX
        #$hex_data = array_map('dechex', $result);
        #echo '<pre>'; print_r($hex_data);

    }
    /**
     * @throws Exception
     */
    private function getPacked($code, $data, $address, $address_source): string
    {
        $AddrH = pack('v', $address);
        $AddrL = pack('v', $address_source);
        $pal = self::getPal($code, $data, static::$USER_PASSWORD);

        $message = static::$OPT . $AddrH . $AddrL . $pal ;
        $crc8 = self::getCRC($message);
        return static::$END . $message . $crc8 . static::$END;
    }

    /**
     * @throws Exception
     */
    private function getPal($cmd, $data, $password): string
    {
        $message_len = strlen($data);
        $serv = self::getServ($message_len);
        return $password . $serv . $cmd .  $data ;
    }

    /**
     * @param $byte_string
     * @return bool|string
     */
    private function getCRC($byte_string): bool|string
    {
        $byte_string = str_split($byte_string, 1);
        $checksum = 0;
        foreach ($byte_string as $item) {
            $item_int = ord($item);
            $item_hex = bin2hex(pack('C', $item_int));
            $item_int_hex = hexdec($item_hex);
            $checksum = static::$crc8tab[$item_int_hex ^ $checksum];
        }
        return hex2bin(sprintf('%02x', $checksum));
    }

    /**
     * @throws Exception
     */
    public function Ping(): static
    {
       $this->sendPacked(__FUNCTION__,"");
       return $this;
    }

    /**
     * @throws Exception
     */
    public function TimeSync(): bool
    {
        $this->sendPacked(__FUNCTION__);
        return true;
    }

    /**
     * @throws Exception
     */
    public function Version($index = ""): static
    {

        if ($index and is_integer($index) and (-1 > $index  or 1 > $index)){
            $index = dechex($index);
        }else{
            $index = "";
        }

        $this->sendPacked(__FUNCTION__, $index);
        return $this;
    }

    /**
     * @throws Exception
     */
    public function ReadConfig(): static
    {
        $this->sendPacked(__FUNCTION__);
        return $this;
    }

    /**
     * @throws Exception
     */
    public function ReadMonthEnergy(...$args): static
    {
        if(count($args) > 0){
            if(count($args) == 2){
                $args = dechex($args[0].$args[1]);
            }else{
                throw new Exception('Incorrect args');
            }
        }else{
            $args = "\x00\x00";
        }
        $this->sendPacked(__FUNCTION__ , $args);
        return $this;
    }

    public function WriteConfig()
    {
        // TODO: Implement WriteConfig() method.
    }

    public function ReadStatus()
    {
        // TODO: Implement ReadStatus() method.
    }

    public function ReadRTCCorrection()
    {
        // TODO: Implement ReadRTCCorrection() method.
    }

    public function WriteRTCCorrection()
    {
        // TODO: Implement WriteRTCCorrection() method.
    }

    public function WritePsw()
    {
        // TODO: Implement WritePsw() method.
    }

    public function ReadSerialNumber()
    {
        // TODO: Implement ReadSerialNumber() method.
    }

    public function ReadDateTime()
    {
        // TODO: Implement ReadDateTime() method.
    }

    public function WriteDateTime()
    {
        // TODO: Implement WriteDateTime() method.
    }

    public function ReadMeterCode()
    {
        // TODO: Implement ReadMeterCode() method.
    }

    public function RTCCorrectMode()
    {
        // TODO: Implement RTCCorrectMode() method.
    }

    public function ReadDaysEnergy(...$args)
    {
        if(count($args) > 0){
            if(count($args) == 2){
                $args = dechex($args[0].$args[1]);
            }else{
                throw new Exception('Incorrect args');
            }
        }else{
            $args = "\x00\x00";
        }
        $this->sendPacked(__FUNCTION__ , $args);
        return $this;
    }

    public function ReadJournalEvent()
    {
        // TODO: Implement ReadJournalEvent() method.
    }

    public function ActivateTarProg()
    {
        // TODO: Implement ActivateTarProg() method.
    }

    public function ReadSeason()
    {
        // TODO: Implement ReadSeason() method.
    }

    public function WriteSeson()
    {
        // TODO: Implement WriteSeson() method.
    }

    public function ReadSpecDays()
    {
        // TODO: Implement ReadSpecDays() method.
    }

    public function WriteSpecDays()
    {
        // TODO: Implement WriteSpecDays() method.
    }

    public function ReadDaySched()
    {
        // TODO: Implement ReadDaySched() method.
    }

    public function WriteDaySched()
    {
        // TODO: Implement WriteDaySched() method.
    }

    public function ReadHourZimaLeto()
    {
        // TODO: Implement ReadHourZimaLeto() method.
    }

    public function WriteHourZimaLeto()
    {
        // TODO: Implement WriteHourZimaLeto() method.
    }

    public function SCOP()
    {
        // TODO: Implement SCOP() method.
    }

    public function ClearTarProg()
    {
        // TODO: Implement ClearTarProg() method.
    }

    function hex2str( $hex ): string
    {
        return pack('n*', $hex);
    }
}