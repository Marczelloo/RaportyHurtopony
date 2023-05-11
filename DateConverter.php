<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title> Konwerter dat</title>
</head>
<body>
</body>
</html>

<?php 

class DateConverter{
    private $dataZero = '1800-12-28';
    
    private $dateFromSQL;
    private $dateToSQL;

    private $dateFromNumber;
    private $dateToNumber;


    private $dateBeforeFromSQL;
    private $dateBeforeToSQL;

    private $dateBeforeFromNumber;
    private $dateBeforeToNumber;


    private $dateYearAgoFromSQL;
    private $dateYearAgoToSQL;

    private $dateYearAgoFromNumber;
    private $dateYearAgoToNumber;


    private $customDateFromNumber;
    private $customDateToNumber;

    private $customDateFromSQL;
    private $customDateToSQL;

    private $range = null;
    public function __construct($range = null){ 
        //konstruktor zbierajacy zakres i podajacy go do funkcji timerPeriod
        //albo jezeli podamy customowy zakres dat to przypisuje go do zmiennych
        if(isset($range['dateFrom']) && isset($range['dateTo'])){
            $this->customDateFromSQL = $range['dateFrom'];
            $this->customDateToSQL = $range['dateTo'];            
            $this->customDatePeriod();
        } else { 
            $this->range = $range;
            $this->timePeriod($range);
        }
    }

    private function timePeriod($range, $before = null){ 
        //w zaleznosci od podanego przez nas w konstruktorze range uzywa danej
        //funkcji do obliczenia wybranego zakresu dat
        switch($range){ 
            case "yesterday":
                $this->yesterday($before);
            break;
            case "last7days":
                $this->last7days($before);
            break;
            case "last14days";
                $this->last14days($before);
            break;
            case "last30days":
                $this->last30days($before);
            break;
            case "last90days":
                $this->last90days($before);
            break;
            case "last180days":
                $this->last180days($before);
            break;
            case "thismonth":
                $this->thismonth($before);
            break;
            case "lastmonth":
                $this->lastmonth($before);
            break;
            case "thisyear":
                $this->thisyear($before);
            break;
            case "lastyear":
                $this->lastyear($before);
            break;
            defualt: 
                echo "Nie podano zakresu";
            break;
        }
    }

    public function getData($numberFormat = false){ 
        //zwraca tablice z indeksami dateFrom, dateTo w typie UNIX albo SQL
        if($numberFormat){
            return ["dateFrom"=>$this->dateFromNumber, "dateTo"=>$this->dateToNumber];
        } else { 
            return ["dateFrom"=>$this->dateFromSQL, "dateTo"=>$this->dateToSQL];
        }
            
    }

    private function convertNumberToDateSQL($number){ 
        //konwertuje i zwraca date z typu UNIX na rodzaj SQL
        //np. 0 na 1800-12-28, 1 na 1800-12-29, 2 na 1800-12-30
        $date = date_create($this->dataZero);
        date_add($date, date_interval_create_from_date_string($number . "days"));
        $date = date_format($date, "Y-m-d");

        return $date;
    }

    private function convertDateSQLToNumber($date){ 
        //konwertuje i zwraca date z rodzaju SQL na typ UNIX 
        //np 1800-12-28 na 0, 1800-12-29 na 1, 1800-12-30 na 2
        $dateZero = new DateTime($this->dataZero);
        $dateArg = new DateTime($date);
        $roznica = $dateArg->diff($dateZero);

        $number = $roznica->days;

        return $number;
    }
    private function customDatePeriod(){
        //przypisuje zmiennym wartosci nie z range'a tylko z customowego zakresu dat
        $this->dateFromNumber = $this->convertDateSQLToNumber($this->customDateFromSQL);
        $this->dateToNumber = $this->convertDateSQLToNumber($this->customDateToSQL);

        $this->dateFromSQL = $this->customDateFromSQL;
        $this->dateToSQL = $this->customDateToSQL;
    }

    private function getNow(){
        //funkcja zwracajaca dzisiejsza date
        $now = new DateTime();
        return $now->format('Y')."-".$now->format('m')."-".$now->format('d');
    }

    private function yesterday($before = false){ 
        if($before){
            //oblicza zakres 1 dzien wczesniej podanego wczensiej zakresu dat
            $this->dateBeforeFromNumber = $this->dateToNumber - 1;
            $this->dateBeforeToNumber = $this->dateBeforeFromNumber;

            $this->dateBeforeFromSQL = $this->convertNumberToDateSQL($this->dateBeforeFromNumber);
            $this->dateBeforeToSQL = $this->convertNumberToDateSQL($this->dateBeforeToNumber);
            return;
        } 
        //oblicza zakres date 180 dni do tylu od teraz
        $this->dateFromNumber = $this->convertDateSQLToNumber($this->getNow()) - 1;
        $this->dateToNumber = $this->dateFromNumber;

        $this->dateFromSQL = $this->convertNumberToDateSQL($this->dateFromNumber);
        $this->dateToSQL = $this->dateFromSQL;

        if($this->dateYearAgoFromNumber && $this->dateYearAgoFromSQL ){
            //oblicz zakres rok wczesniej z wczoraj
            $this->dateYearAgoToNumber = $this->dateYearAgoFromNumber;
            $this->dateYearAgoToSQL = $this->dateYearAgoFromSQL;
        }
    }

    private function last7days($before = false){
        if($before){
            //oblicza zakres dat 7 dni do tylu od poprzedniego zakresu dat
            $this->dateBeforeFromNumber = $this->dateToNumber;
            $this->dateBeforeToNumber = $this->dateBeforeFromNumber - 7;

            $this->dateBeforeFromSQL = $this->convertNumberToDateSQL($this->dateBeforeFromNumber);
            $this->dateBeforeToSQL = $this->convertNumberToDateSQL($this->dateBeforeToNumber);
            return;
        } else {
            //oblicza zakres date 7 dni do tylu od teraz
            $this->dateFromNumber = $this->convertDateSQLToNumber($this->getNow());
            $this->dateToNumber = $this->dateFromNumber - 7;

            $this->dateFromSQL = $this->convertNumberToDateSQL($this->dateFromNumber);
            $this->dateToSQL = $this->convertNumberToDateSQL($this->dateToNumber);
        }  

        if($this->dateYearAgoFromNumber){
            //oblicz zakres rok wczesniej dla zakresu dat 7 dni do tylu od teraz 
            $this->dateYearAgoToNumber = $this->dateYearAgoFromNumber - 7;
            $this->dateYearAgoToSQL = $this->convertNumberToDateSQL($this->dateYearAgoToNumber);
        }
    }

    private function last14days($before = false){ 
        if($before){
            //oblicza zakres dat 14 dni do tylu od poprzedniego zakresu dat
            $this->dateBeforeFromNumber = $this->dateToNumber;
            $this->dateBeforeToNumber = $this->dateBeforeFromNumber - 14;

            $this->dateBeforeFromSQL = $this->convertNumberToDateSQL($this->dateBeforeFromNumber);
            $this->dateBeforeToSQL = $this->convertNumberToDateSQL($this->dateBeforeToNumber);  
            return;
        } 
        //oblicza zakres date 14 dni do tylu od teraz
        $this->dateFromNumber = $this->convertDateSQLToNumber($this->getNow());
        $this->dateToNumber = $this->dateFromNumber - 14;
    
        $this->dateFromSQL = $this->convertNumberToDateSQL($this->dateFromNumber);
        $this->dateToSQL = $this->convertNumberToDateSQL($this->dateToNumber);

        if($this->dateYearAgoFromNumber){
            //oblicz zakres rok wczesniej dla zakresu dat 14 dni do tylu od teraz 
            $this->dateYearAgoToNumber = $this->dateYearAgoFromNumber - 14;
            $this->dateYearAgoToSQL = $this->convertNumberToDateSQL($this->dateYearAgoToNumber);
        }
    }

    private function last30days($before = false){ 
        if($before){
            //oblicza zakres dat 30 dni do tylu od poprzedniego zakresu dat
            $this->dateBeforeFromNumber = $this->dateToNumber;
            $this->dateBeforeToNumber = $this->dateBeforeFromNumber - 30;

            $this->dateBeforeFromSQL = $this->convertNumberToDateSQL($this->dateBeforeFromNumber);
            $this->dateBeforeToSQL = $this->convertNumberToDateSQL($this->dateBeforeToNumber);
            return;
        }
        //oblicza zakres date 30 dni do tylu od teraz
        $this->dateFromNumber = $this->convertDateSQLToNumber($this->getNow());
        $this->dateToNumber = $this->dateFromNumber - 30;

        $this->dateFromSQL = $this->convertNumberToDateSQL($this->dateFromNumber);
        $this->dateToSQL = $this->convertNumberToDateSQL($this->dateToNumber);

        if($this->dateYearAgoFromNumber){
            //oblicz zakres rok wczesniej dla zakresu dat 30 dni do tylu od teraz 
            $this->dateYearAgoToNumber = $this->dateYearAgoFromNumber - 30;
            $this->dateYearAgoToSQL = $this->convertNumberToDateSQL($this->dateYearAgoToNumber);
        }
    }

    private function last90days($before = false){ 
        if($before){
            //oblicza zakres dat 90 dni do tylu od poprzedniego zakresu dat
            $this->dateBeforeFromNumber = $this->dateToNumber;
            $this->dateBeforeToNumber = $this->dateBeforeFromNumber - 90;

            $this->dateBeforeFromSQL = $this->convertNumberToDateSQL($this->dateBeforeFromNumber);
            $this->dateBeforeToSQL = $this->convertNumberToDateSQL($this->dateBeforeToNumber);
            return;
        } 
        //oblicza zakres date 90 dni do tylu od teraz
        $this->dateFromNumber = $this->convertDateSQLToNumber($this->getNow());
        $this->dateToNumber = $this->dateFromNumber - 90;

        $this->dateFromSQL = $this->convertNumberToDateSQL($this->dateFromNumber);
        $this->dateToSQL = $this->convertNumberToDateSQL($this->dateToNumber);
        
        if($this->dateYearAgoFromNumber){
            //oblicz zakres rok wczesniej dla zakresu dat 90 dni do tylu od teraz 
            $this->dateYearAgoToNumber = $this->dateYearAgoFromNumber - 90;
            $this->dateYearAgoToSQL = $this->convertNumberToDateSQL($this->dateYearAgoToNumber);
        }
    }

    private function last180days($before = false){ 
        if($before){
            //oblicza zakres dat 180 dni do tylu od poprzedniego zakresu dat
            $this->dateBeforeFromNumber = $this->dateToNumber;
            $this->dateBeforeToNumber = $this->dateBeforeFromNumber - 180;

            $this->dateBeforeFromSQL = $this->convertNumberToDateSQL($this->dateBeforeFromNumber);
            $this->dateBeforeToSQL = $this->convertNumberToDateSQL($this->dateBeforeToNumber);
            return;
        } 
        //oblicza zakres date 180 dni do tylu od teraz
        $this->dateFromNumber = $this->convertDateSQLToNumber($this->getNow());
        $this->dateToNumber = $this->dateFromNumber - 180;
            
    
        $this->dateFromSQL = $this->convertNumberToDateSQL($this->dateFromNumber);
        $this->dateToSQL = $this->convertNumberToDateSQL($this->dateToNumber);
    

        if($this->dateYearAgoFromNumber){

            $this->dateYearAgoToNumber = $this->dateYearAgoFromNumber - 180;
            $this->dateYearAgoToSQL = $this->convertNumberToDateSQL($this->dateYearAgoToNumber);
        }
    }

    private function thismonth($before = false){
        if($before){
            //oblicza zakres date poprzedni od poczatku tego misiaca
            //rowny dlugosci zakresowi od teraz do poczatku miesiaca
            $daysDiff = $this->dateFromNumber - $this->dateToNumber;
            
            $this->dateBeforeFromNumber = $this->dateToNumber;
            $this->dateBeforeToNumber = $this->dateBeforeFromNumber - $daysDiff;

            $this->dateBeforeFromSQL = $this->convertNumberToDateSQL($this->dateBeforeFromNumber);
            $this->dateBeforeToSQL = $this->convertNumberToDateSQL($this->dateBeforeToNumber);
            return;
        }
        //oblicza zakres date od teraz do poczatku miesiaca
        $date = new DateTime($this->getNow());

        $month = $date->format('m');
        $year = $date->format('Y');

        $firstDay = new DateTime("$year-$month-01");
        $firstDay = $firstDay->format("Y")."-".$firstDay->format("m")."-".$firstDay->format("d");
    
        $this->dateFromNumber = $this->convertDateSQLToNumber($this->getNow());
        $this->dateToNumber = $this->convertDateSQLToNumber($firstDay);

        $this->dateFromSQL = $this->getNow();
        $this->dateToSQL = $firstDay;

        if($this->dateYearAgoFromNumber){
            //oblicza zakres dat dla poprzedniego roku
            $daysDiff = $this->dateFromNumber - $this->dateToNumber;

            $this->dateYearAgoToNumber = $this->dateYearAgoFromNumber - $daysDiff;
            $this->dateYearAgoToSQL = $this->convertNumberToDateSQL($this->dateYearAgoToNumber);
        }
        
        
    }

    private function lastmonth($before = false){ 
        if($before){
            //oblicz zakres dat dla kolejndego misiaca poprzedniego od poprzedniego miesiac 
            $date = new DateTime($this->getNow());
        
            $month = $date->format('m') - 2;
            $year = $date->format('Y');
            if( $month <= 0){
                $month = 12;
                $year--;
            }
    
            $firstDay = new DateTime("$year-$month-01");
            $firstDay = $firstDay->format("Y")."-".$firstDay->format("m")."-".$firstDay->format("d");
    
            $day = $this->dayValidator($month, $year);
            $lastDay = new DateTime("$year-$month-$day");
            $lastDay = $lastDay->format("Y")."-".$lastDay->format("m")."-".$lastDay->format("d");
    
            $this->dateBeforeFromNumber = $this->convertDateSQLToNumber($lastDay);
            $this->dateBeforeToNumber = $this->convertDateSQLToNumber($firstDay);
    
            $this->dateBeforeFromSQL = $lastDay;
            $this->dateBeforeToSQL = $firstDay;
            return;
        } 
        //oblicz zakres dat dla poprzedniego miesica
        $date = new DateTime($this->getNow());
        
        $month = $date->format('m') - 1;
        $year = $date->format('Y');
        if( $month <= 0){
            $month = 12;
            $year--;
        }

        $firstDay = new DateTime("$year-$month-01");
        $firstDay = $firstDay->format("Y")."-".$firstDay->format("m")."-".$firstDay->format("d");

        $day = $this->dayValidator($month, $year);
        $lastDay = new DateTime("$year-$month-$day");
        $lastDay = $lastDay->format("Y")."-".$lastDay->format("m")."-".$lastDay->format("d");

        $this->dateFromNumber = $this->convertDateSQLToNumber($lastDay);
        $this->dateToNumber = $this->convertDateSQLToNumber($firstDay);

        $this->dateFromSQL = $lastDay;
        $this->dateToSQL = $firstDay;

        if($this->dateYearAgoFromNumber){
            //oblicza zakres dat dla poprzedniego roku
            $date = new DateTime($this->dateFromSQL);
        
            $month = $date->format('m') - 1;
            $year = $date->format('Y') - 1;
            if( $month <= 0){
                $month = 12;
                $year--;
            }
            $firstDay = new DateTime("$year-$month-01");
            $firstDay = $firstDay->format("Y")."-".$firstDay->format("m")."-".$firstDay->format("d");
    
            $day = $this->dayValidator($month, $year);
            $lastDay = new DateTime("$year-$month-$day");
            $lastDay = $lastDay->format("Y")."-".$lastDay->format("m")."-".$lastDay->format("d");

            $this->dateYearAgoFromNumber = $this->convertDateSQLToNumber($lastDay);
            $this->dateYearAgoFromSQL = $lastDay;
            $this->dateYearAgoToNumber = $this->convertDateSQLToNumber($firstDay);
            $this->dateYearAgoToSQL = $firstDay;
        }
        
    }

    private function thisyear($before = false){
        if($before){
            //oblicza zakres od teraz do poczatku roku i zwraca tak samo dlugi okres
            //przed danym zakresem
            $daysDiff = $this->dateFromNumber - $this->dateToNumber;
            
            $this->dateBeforeFromNumber = $this->dateToNumber;
            $this->dateBeforeToNumber = $this->dateBeforeFromNumber - $daysDiff;

            $this->dateBeforeFromSQL = $this->convertNumberToDateSQL($this->dateBeforeFromNumber);
            $this->dateBeforeToSQL = $this->convertNumberToDateSQL($this->dateBeforeToNumber); 
            
        } 
        //oblicz zakres dat od teraz do poczatku roku
        $date = new DateTime($this->getNow());        
        
        $year = $date->format('Y');

        $firstDay = new DateTime("$year-01-01");
        $firstDay = $firstDay->format("Y")."-".$firstDay->format("m")."-".$firstDay->format("d");
    
        $this->dateFromNumber = $this->convertDateSQLToNumber($this->getNow());
        $this->dateToNumber = $this->convertDateSQLToNumber($firstDay);

        $this->dateFromSQL = $this->getNow();
        $this->dateToSQL = $firstDay;

        if($this->dateYearAgoFromNumber){
            //oblicza zakres dat dla poprzedniego roku
            $daysDiff = $this->dateFromNumber - $this->dateToNumber;

            $this->dateYearAgoToNumber = $this->dateYearAgoFromNumber - $daysDiff;
            $this->dateYearAgoToSQL = $this->convertNumberToDateSQL($this->dateYearAgoToNumber);
        }
    }
    
    private function lastyear($before = false){
        if($before){
            //oblicza zaokres dat z poprzedniego roku wzgledem zakresu z poprzedniego roku
            //czyli jezeli podalo nam rok 2022 to ta funckja poda nam zakers dat z 2021 roku
            $date = new DateTime($this->getNow());
            $year = $date->format('Y')-2;
    
            $firstDay = new DateTime("$year-01-01");
            $firstDay = $firstDay->format("Y")."-".$firstDay->format("m")."-".$firstDay->format("d");

            $lastDay = new DateTime("$year-12-31");
            $lastDay = $lastDay->format("Y")."-".$lastDay->format("m")."-".$lastDay->format("d");


            $this->dateBeforeFromNumber = $this->convertDateSQLToNumber($lastDay);
            $this->dateBeforeToNumber = $this->convertDateSQLToNumber($firstDay);

            $this->dateBeforeFromSQL = $lastDay;
            $this->dateBeforeToSQL = $firstDay;
        } 
        //oblicza zakres dat z poprzedniego roku wzgledem obecnego czasu
        //oraz przypisuje odpowiednim zmiennym odpowiednie wartosc
        $date = new DateTime($this->getNow());
        $year = $date->format('Y')-1;
    
        $firstDay = new DateTime("$year-01-01");
        $firstDay = $firstDay->format("Y")."-".$firstDay->format("m")."-".$firstDay->format("d");

        $lastDay = new DateTime("$year-12-31");
        $lastDay = $lastDay->format("Y")."-".$lastDay->format("m")."-".$lastDay->format("d");


        $this->dateFromNumber = $this->convertDateSQLToNumber($lastDay);
        $this->dateToNumber = $this->convertDateSQLToNumber($firstDay);

        $this->dateFromSQL = $lastDay;
        $this->dateToSQL = $firstDay;

        if($this->dateYearAgoFromNumber){
            //oblicz zakres dat z poprzedniego roku dla kolejndego poprzedniego roku
            $date = new DateTime($this->dateFromSQL);
            $year = $date->format('Y')-2;
    
            $firstDay = new DateTime("$year-01-01");
            $firstDay = $firstDay->format("Y")."-".$firstDay->format("m")."-".$firstDay->format("d");

            $lastDay = new DateTime("$year-12-31");
            $lastDay = $lastDay->format("Y")."-".$lastDay->format("m")."-".$lastDay->format("d");

            $this->dateYearAgoFromNumber = $this->convertDateSQLToNumber($lastDay);
            $this->dateYearAgoFromSQL = $lastDay;
            $this->dateYearAgoToNumber = $this->convertDateSQLToNumber($firstDay);
            $this->dateYearAgoToSQL = $firstDay;
        }
    }

    private function dayValidator($month, $year){
        //sprawdza ile dni ma podany miesiac w podanym roku i zwraca ich ilosc
        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        return $daysInMonth;
    }

    public function getRangeBefore($numberFormat = null){
        if($this->customDateFromSQL == null && $this->customDateToSQL == null){
            //liczy okres z akresu dat przed podna data
            $this->timePeriod($this->range, true);
        } else {
            //liczenie okresu przed dla customowych zakresow
            $this->customDateFromNumber = $this->convertDateSQLToNumber($this->customDateFromSQL);
            $this->customDateToNumber = $this->convertDateSQLToNumber($this->customDateToSQL);
            
            $daysDiff = $this->customDateFromNumber - $this->customDateToNumber;
            echo "daysDiff: $daysDiff";

            $this->dateBeforeFromNumber = $this->customDateToNumber;
            $this->dateBeforeToNumber = $this->dateBeforeFromNumber - $daysDiff;

            $this->dateBeforeFromSQL = $this->convertNumberToDateSQL($this->dateBeforeFromNumber);
            $this->dateBeforeToSQL = $this->convertNumberToDateSQL($this->dateBeforeToNumber);
        }

        if($numberFormat){
            return ["dateFrom"=> $this->dateBeforeFromNumber, "dateTo"=> $this->dateBeforeToNumber];
        } else {
            return ["dateFrom"=> $this->dateBeforeFromSQL, "dateTo"=> $this->dateBeforeToSQL];
        }
    }

    private function rangeYearAgo(){
        if($this->customDateFromSQL == null && $this->customDateToSQL == null){
            //olbicza zakres dat z range o rok do tylu(czesc jest obliczana w funkcjach z timePeriod)
            $dateAgoFrom = new DateTime($this->dateFromSQL);

            $day = $dateAgoFrom->format('d');
            $month = $dateAgoFrom->format('m');
            $year = $dateAgoFrom->format('Y')-1;

            if($this->dayValidator($month, $year) < $day){
                $day = '01';
                $month++;
                if($month > 12){
                    $year++;
                    $month = '01';
                }
            }

            $dateAgoFrom = $year.'-'.$month.'-'.$day;

            $this->dateYearAgoFromNumber = $this->convertDateSQLToNumber($dateAgoFrom);
            $this->dateYearAgoFromSQL = $dateAgoFrom;

            $this->timePeriod($this->range);
        } else {
            //zwraca range dla customoweg zakresu dat
            $daysDiff = $this->customDateFromNumber - $this->customDateToNumber;
            $dateAgoFrom = new DateTime($this->customDateFromSQL);

            $day = $dateAgoFrom->format('d');
            $month = $dateAgoFrom->format('m');
            $year = $dateAgoFrom->format('Y')-1;

            if($this->dayValidator($month, $year) < $day){
                $day = '01';
                $month++;
                if($month > 12){
                    $year++;
                    $month = '01';
                }
            }

            $dateAgoFrom = $year.'-'.$month.'-'.$day;

            $this->dateYearAgoFromNumber = $this->convertDateSQLToNumber($dateAgoFrom);
            $this->dateYearAgoFromSQL = $dateAgoFrom;

            $this->dateYearAgoToNumber = $this->dateYearAgoFromNumber - $daysDiff;
            $this->dateYearAgoToSQL = $this->convertNumberToDateSQL($this->dateYearAgoToNumber);
        }  
    }

    public function getRangeYearAgo($numberFormat = null){
        $this->rangeYearAgo();
        
        if($numberFormat)
            return ["dateFrom"=>$this->dateYearAgoFromNumber, "dateTo"=>$this->dateYearAgoToNumber];
        else
            return ["dateFrom"=>$this->dateYearAgoFromSQL, "dateTo"=>$this->dateYearAgoToSQL];
    }

    public function wypiszData($data){
        echo "<br>";
        foreach($data as $d => $value){
            echo $d." ".$value."<br>";
        }
        echo "<br>";
    }
}

?>