<?php
include("DBConnect.php");
include("DateConverter.php");

class Raport{
    protected $dbConnection;
    protected $range = null;
    protected $dateFrom = null;
    protected $dateTo = null;
    protected $indeks = null;

    public function __construct(){
        $this->dbConnection = new DBConnect();
    }

    public function setParameters($array){
        if(isset($array['indeks'])){
            $this->indeks = $array['indeks'];
        }

        if(isset($array['range'])){
            $this->range = $array['range'];
        }

        if(isset($array['dateFrom']) && isset($array['dateTo'])){ //
            $this->dateFrom = $array["dateFrom"];
            $this->dateTo = $array["dateTo"];
        } else if(isset($array["range"])) { //
            $dc = new DateConverter($array['range']);
            $this->dateFrom = $dc->getData()['dateFrom'];
            $this->dateTo = $dc->getData()['dateTo'];
        }else{
            $this->dateFrom = null;
            $this->dateTo = null;
        }    
    }
    
    public function dbClose(){
        $this->dbConnection->closeDB();
    }
}

?>