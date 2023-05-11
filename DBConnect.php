<?php 

class DBConnect{
    public $mysqli;
    private $DBHOST = 'localhost';
    private $DBUSER = 'root';
    private $DBPASSW = '';
    private $DBNAME = 'hurtopony';
    
    public function __construct(){ 
        $this->createMysqliInstance(); 
    }

    private function createMysqliInstance(){
        $this->mysqli =  new mysqli($this->DBHOST, $this->DBUSER, $this->DBPASSW, $this->DBNAME);
        if($this->mysqli->connect_error){
            die("Błąd połączenia: ". $this->mysqli->connect_error);
        } else {
            echo "Połączoną z bazą <br>";
        }
        $this->mysqli->query("SET NAMES 'utf8' COLLATE 'utf8_polish_ci'");
    }

    public function query($query){
        return $this->mysqli->query($query);
    }

    public function prepare($query){
        return $this->mysqli->prepare($query);
    }

    public function closeDB(){
        $this->mysqli->close();
    }
}

?>