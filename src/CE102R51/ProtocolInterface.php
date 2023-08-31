<?php

namespace CE102R51;

interface ProtocolInterface
{
    public function ping();
    public function TimeSync();
    public function Version($index);
    public function ReadConfig();
    public function WriteConfig();
    public function ReadStatus();
    public function ReadRTCCorrection();
    public function WriteRTCCorrection();
    public function WritePsw();
    public function ReadSerialNumber();
    public function ReadDateTime();
    public function WriteDateTime();
    public function ReadMeterCode();
    public function RTCCorrectMode();
    public function ReadDaysEnergy(...$args);
    public function ReadMonthEnergy(...$args);
    public function ReadJournalEvent();
    public function ActivateTarProg();
    public function ReadSeason();
    public function WriteSeson();
    public function ReadSpecDays();
    public function WriteSpecDays();
    public function ReadDaySched();
    public function WriteDaySched();
    public function ReadHourZimaLeto();
    public function WriteHourZimaLeto();
    public function SCOP();
    public function ClearTarProg();
}