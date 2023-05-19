<?php 

/** 
 * Klasa DBConnect
 * Zajmuje się łączeniem z bazą oraz zapytaniami
*/
class DBConnect{
    public $mysqli;
    private $DBHOST = 'localhost';
    private $DBUSER = 'root';
    private $DBPASSW = '';
    private $DBNAME = 'hurtopony';
    
    public function __construct(){ 
        $this->createMysqliInstance(); 
    }

    /**
     * Tworzy instancję obiektu mysqli i nawiązuje połączenie z bazą danych.
     */
    private function createMysqliInstance(){
        $this->mysqli =  new mysqli($this->DBHOST, $this->DBUSER, $this->DBPASSW, $this->DBNAME);
        if($this->mysqli->connect_error){
            die("Błąd połączenia: ". $this->mysqli->connect_error);
        } else {
            
        }
        $this->mysqli->query("SET NAMES 'utf8' COLLATE 'utf8_polish_ci'");
    }

    /**
     * Wykonuje zapytanie do bazy danych i zwraca wynik.
     *
     * @param string $query Zapytanie SQL.
     * @return mixed Wynik zapytania.
     */
    public function query($query){
        return $this->mysqli->query($query);
    }

    /**
     * Tworzy przygotowane zapytanie do bazy danych.
     *
     * @param string $query Zapytanie SQL.
     * @return mixed Przygotowane zapytanie.
     */
    public function prepare($query){
        return $this->mysqli->prepare($query);
    }

    /**
     * Zamyka połączenie z bazą danych.
     */
    public function closeDB(){
        $this->mysqli->close();
    }
}

?>
