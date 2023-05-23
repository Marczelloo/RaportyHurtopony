<?php
include("API.php");


// $raport = new RaportBestSellingHO();
// $raport->setParameters(['range'=>'last90days']);
// $wynik = $raport->generate();
// print_r($wynik);

$api = new API();
$api->handle();

?>