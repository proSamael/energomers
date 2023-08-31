<?php


namespace CE102R51;

use Exception;

require 'vendor/autoload.php';

try {
    $en = new Energomer('127.0.0.1', 5010, 98765);
    echo '<pre>';
    print_r($en->ReadDaysEnergy()->get());

} catch (Exception $e) {

    echo $e->getMessage();

}