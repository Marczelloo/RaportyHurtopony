<?php

/**
 * Klasa RaportNotSelling generuje raport produktów, które nie zostały sprzedane w określonym przedziale czasu.
 * Dziedziczy po klasie Raport.
 */
class RaportNotSelling extends Raport{
    /**
     * Konstruktor klasy RaportNotSelling,  wywołuje konstruktor klasy nadrzędnej
     */
    public function __construct(){
        parent::__construct();
    }

    /**
     * Metoda generate generuje raport produktów, które nie zostały sprzedane w określonym przedziale czasu.
     * @return array Tablica zawierająca wygenerowany raport.
     */
    public function generate(){
        $raport = [];

        if($this->dateFrom != NULL && $this->dateTo != NULL){
            $query = "select * from ltx_stan_opon_186 where stan>0";
            $prepare = $this->dbConnection->query($query);
            
            $query2 = "SELECT INDEKS, NAZWA, STAN, CENA_HP0
                from ltx_stan_opon_186 inner join ltx_obroty_232 on ltx_stan_opon_186.INDEKS = ltx_obroty_232.INDEKS_TOW 
                where DATA_FAKT < ? AND DATA_FAKT > ? and indeks = ?";
        
            while($row = $prepare->fetch_assoc()){
                $prepare2 = $this->dbConnection->prepare($query2);
                $prepare2->bind_param("sss", $this->dateFrom, $this->dateTo, $row['INDEKS']);
                $prepare2->execute();
                $wynik = $prepare2->get_result();
                
                $rows = $wynik->num_rows;
                if($rows == 0){
                    $raport[] = ["indeks"=> $row['INDEKS'], "nazwa"=>$row['NAZWA'], 'stan'=>$row['STAN'], 'cena_hp0'=>$row['CENA_HP0']];
                }
            }
            $prepare2->close();
            $prepare->close();
        }
    
        return $raport;
    }
}

?>
