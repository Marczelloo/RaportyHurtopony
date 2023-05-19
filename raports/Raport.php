<?php
include("DBConnect.php");
include("DateConverter.php");

/**
 * Klasa Raport 
 * Służy do generowania raportów na podstawie parametrów oraz korzysta z klasy DBConnect.
 */
class Raport {
    protected $dbConnection;
    protected $range = null;
    protected $dateFrom = null;
    protected $dateTo = null;
    protected $indeks = null;

    /**
     * Konstruktor klasy Raport.
     * Tworzy instancję obiektu DBConnect, który służy do nawiązywania połączenia z bazą danych.
     */
    public function __construct() {
        $this->dbConnection = new DBConnect();
    }

    /**
     * Ustawia parametry raportu na podstawie przekazanej tablicy.
     * @param array $array Tablica zawierająca parametry raportu, takie jak 'indeks', 'range', 'dateFrom' i 'dateTo'.
     */
    public function setParameters($array) {
        if (isset($array['indeks'])) {
            $this->indeks = $array['indeks'];
        }

        if (isset($array['range'])) {
            $this->range = $array['range'];
        }

        if (isset($array['dateFrom']) && isset($array['dateTo'])) {
            $this->dateFrom = $array["dateFrom"];
            $this->dateTo = $array["dateTo"];
        } else if (isset($array["range"])) {
            $dc = new DateConverter($array['range']);
            $this->dateFrom = $dc->getData()['dateFrom'];
            $this->dateTo = $dc->getData()['dateTo'];
        } else {
            $this->dateFrom = null;
            $this->dateTo = null;
        }
    }

    /**
     * Zamyka połączenie z bazą danych.
     */
    public function dbClose() {
        $this->dbConnection->closeDB();
    }
}

?>