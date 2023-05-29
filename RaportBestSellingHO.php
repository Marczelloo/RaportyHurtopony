<?php

/**
 * Klasa RaportBestSellingHO generuje raport dla najlepiej sprzedajacych sie produktow dla bazy HURTOPONY
 * Dziedziczy po klasie Raport.
 */
class RaportBestSellingHO extends Raport{

    /**
     * Konstruktor klasy RaportBestSellingHO, wywołuje konstruktor klasy nadrzędnej.
     */
    public function __construct(){
        parent::__construct();
    }

    /**
     * Metoda generate generuje raport najlepiej sprzedających się produktów.
     * @param int|null $month (opcjonalny) - miesiąc
     * @param int|null $year (opcjonalny) - rok
     * @return array - wygenerowany raport
     */
    public function generate($month = null, $year = null){
        $raport = [];

        if($this->dateFrom != null && $this->dateTo != null){

            //stworzenie bazy zapytania
            $query = "SELECT rp_obroty.symbol as INDEKS, CONCAT(producent, ' ', bazaopon.nazwa) AS NAZWA, ILOSC_MAGAZYN, CENA_KATALOGOWA, SUM(rp_obroty.ILOSC) AS SUMA_ILOSC, ROUND(SUM(rp_obroty.cena_brutto), 2) AS SUMA_WARTOSC, ROUND((SUM(cena_brutto) / SUM(rp_obroty.ILOSC)), 2) AS SREDNIA_CENA_SPRZEDAZY
            FROM bazaopon
            INNER JOIN rp_obroty ON bazaopon.symbol = rp_obroty.symbol
            WHERE";
            
            //dodanie do zapytania wlasicywch parametrow odpowiednio od argumentow
            //w przypadku podanych month i year nie dodaje do zapytanie przedzialu data
            //tylko sprawdza po miesiacu i roku w dacie
            //dodanie do paramatrow odpowiednich zmiennych
            if($month != null && $year != null){
                $query = $query . " MONTH(DATA) = ? AND YEAR(DATA) = ?";
                $params = [$month, $year];
            } else {
                $query = $query . " DATA < ? AND DATA > ?";
                $params = [$this->dateFrom, $this->dateTo];
            }

            //dodaje do zapytania wytyczna do indeksu oraz do parametru
            if($this->indeks != null){
                $query = $query . " AND rp_obroty.symbol = ?";
                $params[] = $this->indeks;
            }

            $query .= " GROUP BY rp_obroty.symbol ORDER BY rp_obroty.ilosc DESC";

            
            $prepare = $this->dbConnection->prepare($query);
            $prepare->bind_param(str_repeat("s", count($params)), ...$params);
            $prepare->execute();
            $wynik = $prepare->get_result();
            
            
            while($row = $wynik->fetch_assoc()){
                $raport[] = [
                    'indeks'=>$row['INDEKS'], 
                    'nazwa'=>$row['NAZWA'], 
                    'stan'=>$row['ILOSC_MAGAZYN'], 
                    'cena_hp0'=>$row['CENA_KATALOGOWA'], 
                    'suma_ilosc'=>$row['SUMA_ILOSC'], 
                    'suma_wartosc'=>$row['SUMA_WARTOSC'], 
                    'srednia_cena_sprzedazy'=>$row['SREDNIA_CENA_SPRZEDAZY']];
            }
            $prepare->close();
        }
            
        return $raport;
    }

    /** 
    * Funckja porównująca raport z podanego przez nas zakresu z 
    * raportem z o długość zakresu wcześniej
    * Czyli np podanie przez nas zakresu 2023.05.18 - 2023.05.01 porowna nam raport z raportem z zakresu 2023-04.31 - 2023-04.13 
    */
    public function newCompareRange(){
        $calculatePercentageDifference = function($current, $previous) {
            if($current == 0 && $previous == 0){
                return 0;
            } else if($previous == 0 && $current != 0){
                return round($current * 100, 2);
            } else {
                return round((($current - $previous) / $previous) * 100, 2);
            }
        };

        $currentRaport = [];
        $previousRaport = [];



        if($this->range){
            $previousRange = (new DateConverter($this->range))->getRangeBefore();
        } else {
            $previousRange = (new DateConverter(["dateFrom" => $this->dateFrom, "dateTo" => $this->dateTo]))->getRangeBefore();
        }

        $query = "SELECT rp_obroty.symbol as INDEKS, CONCAT(producent, ' ', bazaopon.nazwa) AS NAZWA, MAGAZYN, CENA_KATALOGOWA, SUM(rp_obroty.ILOSC) AS SUMA_ILOSC, ROUND(SUM(rp_obroty.cena_brutto), 2) AS SUMA_WARTOSC, ROUND((SUM(cena_brutto) / SUM(rp_obroty.ILOSC)), 2) AS SREDNIA_CENA_SPRZEDAZY
        FROM bazaopon
        INNER JOIN rp_obroty ON bazaopon.symbol = rp_obroty.symbol
        WHERE DATA < ? AND DATA > ?";

        if($this->indeks != null){
            $query = $query . " AND rp_obroty.symbol = ?";
        }

        $query .= " GROUP BY rp_obroty.symbol ORDER BY rp_obroty.ILOSC DESC";


        $prepare = $this->dbConnection->prepare($query);
        if($this->indeks){
            $prepare->bind_param('sss', $this->dateFrom, $this->dateTo, $this->indeks);
        } else {
            $prepare->bind_param('ss', $this->dateFrom, $this->dateTo);
        }
        $prepare->execute();

        $wynik = $prepare->get_result();

        while($row = $wynik->fetch_assoc()){
            $currentRaport[] = [
                'indeks'=>$row['INDEKS'], 
                'nazwa'=>$row['NAZWA'], 
                'stan'=>$row['MAGAZYN'], 
                'cena_hp0'=>$row['CENA_KATALOGOWA'], 
                'suma_ilosc'=>$row['SUMA_ILOSC'], 
                'suma_wartosc'=>$row['SUMA_WARTOSC'], 
                'srednia_cena_sprzedazy'=>$row['SREDNIA_CENA_SPRZEDAZY']];
        }

        $query2 = "SELECT rp_obroty.symbol as INDEKS, CONCAT(producent, ' ', bazaopon.nazwa) AS NAZWA, MAGAZYN, CENA_KATALOGOWA, SUM(rp_obroty.ILOSC) AS SUMA_ILOSC, ROUND(SUM(rp_obroty.cena_brutto), 2) AS SUMA_WARTOSC, ROUND((SUM(cena_brutto) / SUM(rp_obroty.ILOSC)), 2) AS SREDNIA_CENA_SPRZEDAZY
        FROM bazaopon
        INNER JOIN rp_obroty ON bazaopon.symbol = rp_obroty.symbol
        WHERE DATA < ? AND DATA > ?";

        if($this->indeks != null){
            $query2 = $query2 . " AND rp_obroty.symbol = ?";
        }

        $query2 .= "AND rp_obroty.symbol IN ($query) GROUP BY rp_obroty.symbol ORDER BY rp_obroty.symbol DESC";

        $prepare = $this->dbConnection->prepare($query);
        if($this->indeks){
            $prepare->bind_param('sss', $previousRange['dateFrom'], $previousRange['dateTo'], $this->indeks);
        } else {
            $prepare->bind_param('ss', $previousRange['dateFrom'], $previousRange['dateTo']);
        }
        $prepare->execute();

        $wynik = $prepare->get_result();

        while($row = $wynik->fetch_assoc()){
            $previousRaport[] = [
                'indeks'=>$row['INDEKS'], 
                'nazwa'=>$row['NAZWA'], 
                'stan'=>$row['MAGAZYN'], 
                'cena_hp0'=>$row['CENA_KATALOGOWA'], 
                'suma_ilosc'=>$row['SUMA_ILOSC'], 
                'suma_wartosc'=>$row['SUMA_WARTOSC'], 
                'srednia_cena_sprzedazy'=>$row['SREDNIA_CENA_SPRZEDAZY']];
        }
        $prepare->close();

        $newPreviousRaport = [];
        $newPreviousRaport = array_fill(0, count($currentRaport), 'brak_sprzedazy');

        for($i = 0; $i < count($previousRaport); $i++){
            for($j = 0; $j < count($currentRaport); $j++){
                if($currentRaport[$j]['indeks'] == $previousRaport[$i]['indeks']){
                    $newPreviousRaport[$j] = $currentRaport[$j];
                }
            }
        }

        for($i = 0; $i < count($currentRaport); $i++){
            $raport[] = [
                'indeks' => $currentRaport[$i]['indeks'],
                'nazwa' => $currentRaport[$i]['nazwa'],
                'stan' => $currentRaport[$i]['stan'],
                'cena_hp0' => $currentRaport[$i]['cena_hp0'],
                'suma_ilosc_ten_okres' => $currentRaport[$i]['suma_ilosc'],
                'suma_ilosc_poprzedni_okres' => $newPreviousRaport[$i] == 'brak_sprzedazy' ? 'brak_sprzedazy' : $newPreviousRaport[$i]['suma_ilosc'],
                'suma_ilosc_procent' => $calculatePercentageDifference($currentRaport[$i]['suma_ilosc'], $newPreviousRaport[$i] == 'brak_sprzedazy' ? 0 : $newPreviousRaport[$i]['suma_ilosc']),
                'suma_wartosc_ten_okres' => $currentRaport[$i]['suma_wartosc'],
                'suma_wartosc_poprzedni_okres' => $newPreviousRaport[$i] == 'brak_sprzedazy' ? 'brak_sprzedazy' : $newPreviousRaport[$i]['suma_wartosc'],
                'suma_wartosc_procent' => $calculatePercentageDifference($currentRaport[$i]['suma_wartosc'], $newPreviousRaport[$i] == 'brak_sprzedazy' ? 0 : $newPreviousRaport[$i]['suma_wartosc']),
                'srednia_cena_sprzedazy_ten_okres' => $currentRaport[$i]['srednia_cena_sprzedazy'],
                'srednia_cena_sprzedazy_poprzedni_okres' => $newPreviousRaport[$i] == 'brak_sprzedazy' ?  'brak_sprzedazy' : $newPreviousRaport[$i]['srednia_cena_sprzedazy'] ,
                'srednia_cena_sprzedazy_procent' => $calculatePercentageDifference($currentRaport[$i]['srednia_cena_sprzedazy'], $newPreviousRaport[$i] == 'brak_sprzedazy' ? 0 : $newPreviousRaport[$i]['srednia_cena_sprzedazy'] ),
            ];
        }

        return $raport;
    }

    public function newCompareRangeYearAgo(){
        $calculatePercentageDifference = function($current, $previous) {
            if($current == 0 && $previous == 0){
                return 0;
            } else if($previous == 0 && $current != 0){
                return round($current * 100, 2);
            } else {
                return round((($current - $previous) / $previous) * 100, 2);
            }
        };

        $currentRaport = [];
        $previousRaport = [];



        if($this->range){
            $previousRange = (new DateConverter($this->range))->getRangeYearAgo();
        } else {
            $previousRange = (new DateConverter(["dateFrom" => $this->dateFrom, "dateTo" => $this->dateTo]))->getRangeBefore();
        }

        $query = "SELECT rp_obroty.symbol as INDEKS, CONCAT(producent, ' ', bazaopon.nazwa) AS NAZWA, MAGAZYN, CENA_KATALOGOWA, SUM(rp_obroty.ILOSC) AS SUMA_ILOSC, ROUND(SUM(rp_obroty.cena_brutto), 2) AS SUMA_WARTOSC, ROUND((SUM(cena_brutto) / SUM(rp_obroty.ILOSC)), 2) AS SREDNIA_CENA_SPRZEDAZY
        FROM bazaopon
        INNER JOIN rp_obroty ON bazaopon.symbol = rp_obroty.symbol
        WHERE DATA < ? AND DATA > ?";

        if($this->indeks != null){
            $query = $query . " AND rp_obroty.symbol = ?";
        }

        $query .= " GROUP BY rp_obroty.symbol ORDER BY rp_obroty.ILOSC DESC";


        $prepare = $this->dbConnection->prepare($query);
        if($this->indeks){
            $prepare->bind_param('sss', $this->dateFrom, $this->dateTo, $this->indeks);
        } else {
            $prepare->bind_param('ss', $this->dateFrom, $this->dateTo);
        }
        $prepare->execute();

        $wynik = $prepare->get_result();

        while($row = $wynik->fetch_assoc()){
            $currentRaport[] = [
                'indeks'=>$row['INDEKS'], 
                'nazwa'=>$row['NAZWA'], 
                'stan'=>$row['MAGAZYN'], 
                'cena_hp0'=>$row['CENA_KATALOGOWA'], 
                'suma_ilosc'=>$row['SUMA_ILOSC'], 
                'suma_wartosc'=>$row['SUMA_WARTOSC'], 
                'srednia_cena_sprzedazy'=>$row['SREDNIA_CENA_SPRZEDAZY']];
        }

        $query2 = "SELECT rp_obroty.symbol as INDEKS, CONCAT(producent, ' ', bazaopon.nazwa) AS NAZWA, MAGAZYN, CENA_KATALOGOWA, SUM(rp_obroty.ILOSC) AS SUMA_ILOSC, ROUND(SUM(rp_obroty.cena_brutto), 2) AS SUMA_WARTOSC, ROUND((SUM(cena_brutto) / SUM(rp_obroty.ILOSC)), 2) AS SREDNIA_CENA_SPRZEDAZY
        FROM bazaopon
        INNER JOIN rp_obroty ON bazaopon.symbol = rp_obroty.symbol
        WHERE DATA < ? AND DATA > ?";

        if($this->indeks != null){
            $query2 = $query2 . " AND rp_obroty.symbol = ?";
        }

        $query2 .= "AND rp_obroty.symbol IN ($query) GROUP BY rp_obroty.symbol ORDER BY rp_obroty.symbol DESC";

        $prepare = $this->dbConnection->prepare($query);
        if($this->indeks){
            $prepare->bind_param('sss', $previousRange['dateFrom'], $previousRange['dateTo'], $this->indeks);
        } else {
            $prepare->bind_param('ss', $previousRange['dateFrom'], $previousRange['dateTo']);
        }
        $prepare->execute();

        $wynik = $prepare->get_result();

        while($row = $wynik->fetch_assoc()){
            $previousRaport[] = [
                'indeks'=>$row['INDEKS'], 
                'nazwa'=>$row['NAZWA'], 
                'stan'=>$row['MAGAZYN'], 
                'cena_hp0'=>$row['CENA_KATALOGOWA'], 
                'suma_ilosc'=>$row['SUMA_ILOSC'], 
                'suma_wartosc'=>$row['SUMA_WARTOSC'], 
                'srednia_cena_sprzedazy'=>$row['SREDNIA_CENA_SPRZEDAZY']];
        }
        $prepare->close();

        $newPreviousRaport = [];
        $newPreviousRaport = array_fill(0, count($currentRaport), 'brak_sprzedazy');

        for($i = 0; $i < count($previousRaport); $i++){
            for($j = 0; $j < count($currentRaport); $j++){
                if($currentRaport[$j]['indeks'] == $previousRaport[$i]['indeks']){
                    $newPreviousRaport[$j] = $currentRaport[$j];
                }
            }
        }

        for($i = 0; $i < count($currentRaport); $i++){
            $raport[] = [
                'indeks' => $currentRaport[$i]['indeks'],
                'nazwa' => $currentRaport[$i]['nazwa'],
                'stan' => $currentRaport[$i]['stan'],
                'cena_hp0' => $currentRaport[$i]['cena_hp0'],
                'suma_ilosc_ten_okres' => $currentRaport[$i]['suma_ilosc'],
                'suma_ilosc_poprzedni_okres' => $newPreviousRaport[$i] == 'brak_sprzedazy' ? 'brak_sprzedazy' : $newPreviousRaport[$i]['suma_ilosc'],
                'suma_ilosc_procent' => $calculatePercentageDifference($currentRaport[$i]['suma_ilosc'], $newPreviousRaport[$i] == 'brak_sprzedazy' ? 0 : $newPreviousRaport[$i]['suma_ilosc']),
                'suma_wartosc_ten_okres' => $currentRaport[$i]['suma_wartosc'],
                'suma_wartosc_poprzedni_okres' => $newPreviousRaport[$i] == 'brak_sprzedazy' ? 'brak_sprzedazy' : $newPreviousRaport[$i]['suma_wartosc'],
                'suma_wartosc_procent' => $calculatePercentageDifference($currentRaport[$i]['suma_wartosc'], $newPreviousRaport[$i] == 'brak_sprzedazy' ? 0 : $newPreviousRaport[$i]['suma_wartosc']),
                'srednia_cena_sprzedazy_ten_okres' => $currentRaport[$i]['srednia_cena_sprzedazy'],
                'srednia_cena_sprzedazy_poprzedni_okres' => $newPreviousRaport[$i] == 'brak_sprzedazy' ?  'brak_sprzedazy' : $newPreviousRaport[$i]['srednia_cena_sprzedazy'] ,
                'srednia_cena_sprzedazy_procent' => $calculatePercentageDifference($currentRaport[$i]['srednia_cena_sprzedazy'], $newPreviousRaport[$i] == 'brak_sprzedazy' ? 0 : $newPreviousRaport[$i]['srednia_cena_sprzedazy'] ),
            ];
        }

        return $raport;
    }

    
}

?>