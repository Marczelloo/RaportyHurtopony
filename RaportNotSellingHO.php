<?php
/**
 * Klasa RaportNotSellingHO generuje raport produktów, które nie zostały sprzedane w określonym przedziale czasu z bazy HURTOPONY.
 * Dziedziczy po klasie Raport.
 */
class RaportNotSellingHO extends Raport{
    /**
     * Konstruktor klasy RaportNotSellingHO,  wywołuje konstruktor klasy nadrzędnej
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
            $query = "SELECT rp_obroty.symbol as INDEKS, CONCAT(producent, '', bazaopon.nazwa) as NAZWA, MAGAZYN, CENA_KATALOGOWA 
            from bazaopon inner join rp_obroty on rp_obroty.symbol = bazaopon.symbol
            where MAGAZYN > 0 and DATA < ? and RodzajPozycji = 'P' GROUP BY rp_obroty.symbol";
            $prepare = $this->dbConnection->prepare($query);
            $prepare->bind_param("s", $this->dateTo);
            $prepare->execute();
            $prepare = $prepare->get_result();

            $query2 = "SELECT rp_obroty.symbol as INDEKS, CONCAT(producent, ' ', bazaopon.nazwa) as NAZWA, MAGAZYN, CENA_KATALOGOWA
                from bazaopon inner join rp_obroty on rp_obroty.symbol = bazaopon.symbol
                where DATA < ? AND DATA > ? AND rp_obroty.symbol = ? AND RodzajPozycji != 'R'";
        
            while($row = $prepare->fetch_assoc()){
                $prepare2 = $this->dbConnection->prepare($query2);
                $prepare2->bind_param("sss", $this->dateFrom, $this->dateTo, $row['INDEKS']);
                $prepare2->execute();
                $wynik = $prepare2->get_result();
                
                $rows = $wynik->num_rows;
                if($rows == 0){
                    $raport[] = [
                        "indeks"=> $row['INDEKS'], 
                        "nazwa"=>$row['NAZWA'],  
                        'cena_hp0'=>$row['CENA_KATALOGOWA']
                    ];
                }
            }
            $prepare2->close();
            $prepare->close();
        }
    
        return $raport;
    }
}

?>
