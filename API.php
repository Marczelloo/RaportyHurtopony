<?php

include('Raport.php');
include("RaportBestSelling.php");
include("RaportNotSelling.php");
include("RaportMonthlySelling.php");

class API{
    private $request; // 'bestselling' || 'bestsellingRangeAgo' || 'bestsellingYearAgo' || 'notselling' || 'monthlyselling'
    private $range; // 'yesterday' || 'last7days' || 'last14days' || 'last30days' || 'last90days' || 'last180days' || 'lastmonth' || 'thismonth' || 'lastyear' || 'thisyear'
    private $valid_ranges = array('yesterday', 'last7days', 'last14days', 'last30days', 'last90days', 'last180days', 'lastmonth', 'thismonth', 'lastyear', 'thisyear');
    private $dateFrom; //zakres dat od i do jest liczony w przeszlosc czyli od = 12.05.2023, do = 01.01.2023
    private $dateTo; 
    private $indeks;
    private $errors = [];
    private $result = [];

    public function __construct(){
        $this->request = $_POST['request'] ?? null;
        $this->range = $_POST['range'] ?? null;
        $this->dateFrom = $_POST['dateFrom'] ?? null;
        $this->dateTo = $_POST['dateTo'] ?? null;
        $this->indeks = $_POST['indeks'] ?? null;
    }

    public function handle(){
        $this->request = $this->sanitizeInput($this->request);
        $this->range = $this->sanitizeInput($this->range);
        $this->dateFrom = $this->sanitizeInput($this->dateFrom);
        $this->dateTo = $this->sanitizeInput($this->dateTo);
        $this->indeks = $this->sanitizeInput($this->indeks);

        if(!isset($this->range)){
            if($this->dateFrom == null){
                $this->errors[] = "Nie podano zakresu daty od(dateFrom)!";
            } else {
                if (false === strtotime($this->dateFrom)) {
                    $this->errors[] = "Niepoprawny format daty (dateFrom)!";
                } else {
                    $this->range = ['dateFrom' => $this->dateFrom];
                }
                
            }
    
            if($this->dateTo == null){
                $this->errors[] = "Nie podano zakresu daty do(dateTo)!";
            } else {
                if (false === strtotime($this->dateTo)) {
                    $this->errors[] = "Niepoprawny format daty (dateFrom)!";
                } else {
                    $this->range += ['dateTo' => $this->dateTo];
                }
            }

            if(empty($this->range)){
                $this->errors[] = "Nie podano zakresu(range)!";
            }


        } else {
            if(is_string($this->range)){
                if (!in_array($this->range, $this->valid_ranges)) {
                    $this->errors[] = "Niepoprawny zakres(range)!";
                }
            } else {
                $this->errors = "Błędny rodzaj zakresu(raneg) Zakres musi byc rodzaju string";
            }
        }

        
        
        if($this->indeks !=  null){
            if(!is_string($this->indeks))
                $this->errors[] = "Nie poprawny format indeksu!";  
            else if(!preg_match('/^[0-9]+$/', $this->indeks)) {
                $this->errors[] = "Niepoprawny format indeksu! Indeks moze zawierac tylko cyfry.";
            }  
        }

        if(!isset($this->request))
            $this->errors[] = "Nie podano żadania(request)!";
        else{
            if(is_string($this->request)){
                switch($this->request){
                    case 'bestselling':
                        $this->bestselling();
                    break;
                    case 'bestsellingRangeAgo':
                        $this->bestsellingRangeAgo();
                    break;
                    case 'bestsellingYearAgo':
                        $this->bestSellingRangeYearAgo();
                    break;
                    case 'notselling':
                        $this->notselling();
                    break;
                    case 'monthlyselling':
                        $this->monthlyselling();
                    break;
                    default:
                    $this->errors[] = "Niepoprawne żądanie(request)!";
                }
            } else {
                $this->errors[] = "Nie poprawny format żądania(request)";
            }
        }  
        
        
        if(empty($this->errors)){
            echo json_encode(['success'=> 1 , "result" => $this->result]);
        } else {
            echo json_encode(['success' => 0, "result" => $this->errors]);
        }
    }

    private function bestselling(){
        $raport = new RaportBestSelling();
        if($this->indeks != null){ 
            if(isset($this->range)){
                echo "1<br>";
                $raport->setParameters(['range'=> $this->range, 'indeks'=> $this->indeks]);
            } else {
                echo "2<br>";
                $raport->setParameters(['dateFrom'=> $this->dateFrom, 'dateTo'=> $this->dateTo, 'indeks'=>$this->indeks]);
            }
        } else{ 
            if(isset($this->range)){
                echo "3<br>";
                $raport->setParameters(['range'=> $this->range]);
            } else {
                echo "4<br>";
                $raport->setParameters(['dateFrom'=> $this->dateFrom, 'dateTo'=> $this->dateTo]);
            }
        }
        $this->result = $raport->generate();
        $raport->dbClose();
    }

    private function bestsellingRangeAgo(){
        $raport = new RaportBestSelling();
        if(isset($this->indeks)){ 
            if(isset($this->range)){
                $raport->setParameters(['range'=> $this->range, 'indeks'=> $this->indeks]);
                $this->result = $raport->compareRange();
            } else {
                $raport->setParameters(['dateFrom'=> $this->dateFrom, 'dateTo'=> $this->dateTo, 'indeks'=>$this->indeks]);
                $this->result = $raport->compareRange();
            }
        } else{ 
            if(isset($this->range)){
                $raport->setParameters(['range'=> $this->range]);
                $this->result = $raport->allCompareRange();
            } else {
                $raport->setParameters(['dateFrom'=> $this->dateFrom, 'dateTo'=> $this->dateTo]);
                $this->result = $raport->allCompareRange();
            }
        }
        
        $raport->dbClose();
    }

    private function bestsellingRangeYearAgo(){
        $raport = new RaportBestSelling();
        if(isset($this->indeks)){ 
            if(isset($this->range)){
                $raport->setParameters(['range'=> $this->range, 'indeks'=> $this->indeks]);
                $this->result = $raport->compareYearAgo();
            } else {
                $raport->setParameters(['dateFrom'=> $this->dateFrom, 'dateTo'=> $this->dateTo, 'indeks'=>$this->indeks]);
                $this->result = $raport->compareYearAgo(); //
            }
        } else{ 
            if(isset($this->range)){
                $raport->setParameters(['range'=> $this->range]);
                $this->result = $raport->allCompareYearAgo();
            } else {
                $raport->setParameters(['dateFrom'=> $this->dateFrom, 'dateTo'=> $this->dateTo]);
                $this->result = $raport->allCompareYearAgo();
            }
        }
        $raport->dbClose();
    }

    private function notselling(){
        $raport = new RaportNotSelling();
        if(isset($this->indeks)){ 
            if(isset($this->range)){
                $raport->setParameters(['range'=> $this->range, 'indeks'=> $this->indeks]);
            } else {
                $raport->setParameters(['dateFrom'=> $this->dateFrom, 'dateTo'=> $this->dateTo, 'indeks'=>$this->indeks]);
            }
        } else{ 
            if(isset($this->range)){
                $raport->setParameters(['range'=> $this->range]);
            } else {
                $raport->setParameters(['dateFrom'=> $this->dateFrom, 'dateTo'=> $this->dateTo]);
            }
        }
        $this->result = $raport->generate();
        $raport->dbClose();
    }

    private function monthlyselling(){ 
        $raport = new RaportMonthlySelling();
        if(isset($this->indeks)){ 
            if(isset($this->range)){
                $raport->setParameters(['range'=> $this->range, 'indeks'=> $this->indeks]);
            } else {
                $raport->setParameters(['dateFrom'=> $this->dateFrom, 'dateTo'=> $this->dateTo, 'indeks'=>$this->indeks]);
            }
        } else{ 
            if(isset($this->range)){
                $raport->setParameters(['range'=> $this->range]);
            } else {
                $raport->setParameters(['dateFrom'=> $this->dateFrom, 'dateTo'=> $this->dateTo]);
            }
        }
        $this->result = $raport->generate();
        $raport->dbClose();
    }

    private function sanitizeInput($input) {
        $input = trim($input);
        $input = stripslashes($input);
        $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
        if (preg_match("/[\'=]/", $input)) {
            $this->errors[] = "Input zawiera attak wstrzykujący SQL";
        }
    
        return $input;
    }
}

?>