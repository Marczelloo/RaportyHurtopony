<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title> System Raportow </title>
    <style>
    table, th, tr, td{
        border: 1px solid black;
        border-collapse: collapse;
        padding: 0.25rem;
        text-align: center;
    }
    </style>
</head>
<body>
</body>
</html>

<?php
include('Raport.php');
include("RaportBestSelling.php");
include("RaportNotSelling.php");
include("RaportMonthlySelling.php");

function wypiszRaport($raport){
    echo "<table>";
        echo "<tr>";
            echo "<th> DATA</th>";
            echo "<th> INDEKS </th>";
            echo "<th> NAZWA</th>";
            echo "<th> STAN </th>";
            echo "<th> CENA_HP0 </th>";
            echo "<th> SUMA ILOSC </th>";
            echo "<th> SUMA WARTOSC </th>";
        echo "</tr>";
    // echo "<tr>";
    // foreach ($raport as $r){
    //     foreach($r as $row => $value){
    //         echo "<th>".$row."</th>";
    //     }
    // }
    // echo "</tr>";
    // echo "<tr>";
    foreach($raport as $r){
    // echo "<td>";
        foreach ($r as $row => $value){
            // echo "<table>";
            foreach($value as $data){
                echo "<tr>";
                echo "<td>".$row."</td>";
                echo "<td>".$data['indeks']."</td>";
                echo "<td>".$data['nazwa']."</td>";
                echo "<td>".$data['stan']."</td>";
                echo "<td>".$data['cena_hp0']."</td>";
                echo "<td>".$data['suma_ilosc']."</td>";
                echo "<td>".$data['suma_wartosc']."</td>";
                echo "</tr>";
            }      
            // echo "</table>";
        }
    // echo "</td>";
    }
    // echo "</tr>";
    echo "</table>";
}

$range = ["range"=>"lastmonth"];

$raportBestSelling = new RaportBestSelling();
$raportBestSelling->setParameters($range);

$raport = $raportBestSelling->generate();

echo "<table>";
echo "<tr>";
    echo "<th> INDEKS</th>";
    echo "<th> NAZWA </th>";
    echo "<th> STAN </th>";
    echo "<th> CENA_HP0 </th>";
    echo "<th> ILOSC </th>";
    echo "<th> WARTOSC </th>";
    echo "<th> SREDNIA CENA SPRZEDAZY </th>";
echo "</tr>";
foreach($raport as $row){
    echo "<tr>";
    echo "<td>".$row["indeks"]."</td>";
    echo "<td>".$row["nazwa"]."</td>";
    echo "<td>".$row["stan"]."</td>";
    echo "<td>".$row["cena_hp0"]."</td>";
    echo "<td>".$row["suma_ilosc"]."</td>";
    echo "<td>".$row["suma_wartosc"]."</td>";
    echo "<td>".$row["srednia_cena_sprzedazy"]."</td>";
    echo "</tr>";
}
echo "</table>";

$raportBestSelling->dbClose();

// $raportNotSelling = new RaportNotSelling();
// $raportNotSelling->setParameters($range);

// $raport2 = $raportNotSelling->generate();

// echo "<table>";
// echo "<tr>";
//     echo "<th> INDEKS</th>";
//     echo "<th> NAZWA </th>";
//     echo "<th> STAN </th>";
//     echo "<th> CENA_HP0 </th>";
// echo "</tr>";
// foreach ($raport2 as $row){
//     echo "<tr>";
//     echo "<td>".$row["indeks"]."</td>";
//     echo "<td>".$row["nazwa"]."</td>";
//     echo "<td>".$row["stan"]."</td>";
//     echo "<td>".$row['cena_hp0']."</td>";
//     echo "</tr>";
// }
// echo "</table>";

//$raportNotSelling->dbClose();

// $params = ["range" => 'lastyear', "indeks"=>'98571'];
// $raportMonthlySelling = new RaportMonthlySelling();
// $raportMonthlySelling->setParameters($params);
// $raport =  $raportMonthlySelling->generate();
// wypiszRaport($raport);


// $params = ["range" => 'last180days', "indeks"=>'110879'];
// $raportMonthlySelling = new RaportMonthlySelling();
// $raportMonthlySelling->setParameters($params);
// $raport =  $raportMonthlySelling->generate();
// wypiszRaport($raport);


$dt = new DateConverter('thisyear');
$wynik1 = $dt->getData();
$dt->wypiszData($wynik1);

$wynik2 = $dt->getRangeBefore();
$dt->wypiszData($wynik2);

$wynik3 = $dt->getRangeYearAgo();
$dt->wypiszData($wynik3);

$customDate = ['dateFrom'=> '2023-05-10', 'dateTo'=> '2023-05-01'];
$dt = new DateConverter($customDate);
$wynik4 = $dt->getData();
$dt->wypiszData($wynik4);

$wynik5 = $dt->getRangeBefore();
$dt->wypiszData($wynik5);

$wynik6 = $dt->getRangeYearAgo();
$dt->wypiszData($wynik6);

$params = ["range" => 'lastmonth', 'indeks'=>'64537'];
$rbs = new RaportBestSelling();
$rbs->setParameters($params);
$wynik = $rbs->generate();

$porownanie = $rbs->compareRange();

foreach($porownanie as $key => $value){
    echo $key." : ".$value."<br>";
}
echo "<br><br>";

$porownanie = $rbs->compareYearAgo();


foreach($porownanie as $key => $value){
    echo $key." : ".$value."<br>";
}

?>