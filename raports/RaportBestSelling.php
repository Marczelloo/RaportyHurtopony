<?php

/**
 * Klasa RaportBestSelling generuje raport dla najlepiej sprzedajacych sie produktow
 * Dziedziczy po klasie Raport.
 */
class RaportBestSelling extends Raport{

    /**
     * Konstruktor klasy RaportBestSelling, wywołuje konstruktor klasy nadrzędnej.
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
            $query = "SELECT INDEKS, NAZWA, STAN, CENA_HP0, sum(ILOSC) as SUMA_ILOSC, round(sum(WARTOSC), 2) as SUMA_WARTOSC, round((sum(WARTOSC) / sum(ILOSC)), 2) AS SREDNIA_CENA_SPRZEDAZY
            from ltx_stan_opon_186 inner join ltx_obroty_232 on ltx_stan_opon_186.INDEKS = ltx_obroty_232.INDEKS_TOW 
            where ";
            
            //dodanie do zapytania wlasicywch parametrow odpowiednio od argumentow
            //w przypadku podanych month i year nie dodaje do zapytanie przedzialu data
            //tylko sprawdza po miesiacu i roku w dacie
            //dodanie do paramatrow odpowiednich zmiennych
            if($month != null && $year != null){
                $query = $query . " MONTH(DATA_FAKT) = ? AND YEAR(DATA_FAKT) = ?";
                $params = [$month, $year];
            } else {
                $query = $query . " DATA_FAKT < ? AND DATA_FAKT > ?";
                $params = [$this->dateFrom, $this->dateTo];
            }

            //dodaje do zapytania wytyczna do indeksu oraz do parametru
            if($this->indeks != null){
                $query = $query . " AND INDEKS = ?";
                $params[] = $this->indeks;
            }

            $query .= " GROUP BY INDEKS ORDER BY ILOSC DESC";

            
            $prepare = $this->dbConnection->prepare($query);
            $prepare->bind_param(str_repeat("s", count($params)), ...$params);
            $prepare->execute();
            $wynik = $prepare->get_result();
            
            
            while($row = $wynik->fetch_assoc()){
                $raport[] = [
                    'indeks'=>$row['INDEKS'], 
                    'nazwa'=>$row['NAZWA'], 
                    'stan'=>$row['STAN'], 
                    'cena_hp0'=>$row['CENA_HP0'], 
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
    $calculatePercentageDifference = function ($current, $previous) {
        if ($current == 0 && $previous == 0) {
            return 0;
        } elseif ($previous == 0 && $current != 0) {
            return round($current * 100, 2);
        } else {
            return round(($current - $previous) / $previous * 100, 2);
        }
    };

    $currentRaport = [];
    $previousRaport = [];

    if ($this->range) {
        $previousRange = (new DateConverter($this->range))->getRangeBefore();
    } else {
        $previousRange = (new DateConverter(["dateFrom" => $this->dateFrom, "dateTo" => $this->dateTo]))->getRangeBefore();
    }

    $query = "SELECT INDEKS, NAZWA, STAN, CENA_HP0, sum(ILOSC) as SUMA_ILOSC, round(sum(WARTOSC), 2) as SUMA_WARTOSC, round((sum(WARTOSC) / sum(ILOSC)), 2) AS SREDNIA_CENA_SPRZEDAZY
        FROM ltx_stan_opon_186
        INNER JOIN ltx_obroty_232 ON ltx_stan_opon_186.INDEKS = ltx_obroty_232.INDEKS_TOW
        WHERE DATA_FAKT < ? AND DATA_FAKT > ?";

    if ($this->indeks != null) {
        $query .= " AND INDEKS = ?";
    }

    $query .= " GROUP BY INDEKS ORDER BY ILOSC DESC";

    $prepare = $this->dbConnection->prepare($query);
    $params = $this->indeks ? [$this->dateFrom, $this->dateTo, $this->indeks] : [$this->dateFrom, $this->dateTo];
    $prepare->bind_param(str_repeat('s', count($params)), ...$params);
    $prepare->execute();
    $wynik = $prepare->get_result();

    while ($row = $wynik->fetch_assoc()) {
        $currentRaport[] = [
            'indeks' => $row['INDEKS'],
            'nazwa' => $row['NAZWA'],
            'stan' => $row['STAN'],
            'cena_hp0' => $row['CENA_HP0'],
            'suma_ilosc' => $row['SUMA_ILOSC'],
            'suma_wartosc' => $row['SUMA_WARTOSC'],
            'srednia_cena_sprzedazy' => $row['SREDNIA_CENA_SPRZEDAZY']
        ];
    }

    $query2 = "SELECT INDEKS, NAZWA, STAN, CENA_HP0, sum(ILOSC) as SUMA_ILOSC, round(sum(WARTOSC), 2) as SUMA_WARTOSC, round((sum(WARTOSC) / sum(ILOSC)), 2) AS SREDNIA_CENA_SPRZEDAZY
        FROM ltx_stan_opon_186
        INNER JOIN ltx_obroty_232 ON ltx_stan_opon_186.INDEKS = ltx_obroty_232.INDEKS_TOW
        WHERE DATA_FAKT < ? AND DATA_FAKT > ?";

    if ($this->indeks != null) {
        $query2 .= " AND INDEKS = ?";
    }

    $query2 .= " AND INDEKS IN ($query) GROUP BY INDEKS ORDER BY ILOSC DESC";

    $prepare = $this->dbConnection->prepare($query2);
    $params = $this->indeks ? [$previousRange['dateFrom'], $previousRange['dateTo'], $this->indeks] : [$previousRange['dateFrom'], $previousRange['dateTo']];
    $prepare->bind_param(str_repeat('s', count($params)), ...$params);
    $prepare->execute();
    $wynik = $prepare->get_result();

    while ($row = $wynik->fetch_assoc()) {
        $previousRaport[] = [
            'indeks' => $row['INDEKS'],
            'nazwa' => $row['NAZWA'],
            'stan' => $row['STAN'],
            'cena_hp0' => $row['CENA_HP0'],
            'suma_ilosc' => $row['SUMA_ILOSC'],
            'suma_wartosc' => $row['SUMA_WARTOSC'],
            'srednia_cena_sprzedazy' => $row['SREDNIA_CENA_SPRZEDAZY']
        ];
    }
    $prepare->close();

    $newPreviousRaport = array_fill(0, count($currentRaport), 'brak_sprzedazy');

    for ($i = 0; $i < count($previousRaport); $i++) {
        for ($j = 0; $j < count($currentRaport); $j++) {
            if ($currentRaport[$j]['indeks'] == $previousRaport[$i]['indeks']) {
                $newPreviousRaport[$j] = $currentRaport[$j];
            }
        }
    }

    $raport = [];
    for ($i = 0; $i < count($currentRaport); $i++) {
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
            'srednia_cena_sprzedazy_poprzedni_okres' => $newPreviousRaport[$i] == 'brak_sprzedazy' ?  'brak_sprzedazy' : $newPreviousRaport[$i]['srednia_cena_sprzedazy'],
            'srednia_cena_sprzedazy_procent' => $calculatePercentageDifference($currentRaport[$i]['srednia_cena_sprzedazy'], $newPreviousRaport[$i] == 'brak_sprzedazy' ? 0 : $newPreviousRaport[$i]['srednia_cena_sprzedazy']),
        ];
    }

    return $raport;
    }

    /** 
    * Funckja porównująca raport z podanego przez nas zakresu z 
    * raportem z zakresu rok wcześniej
    * Czyli np podanie przez nas zakresu 2023.05.18 - 2023.05.01 porowna nam raport z raportem z zakresu 2023-05.18 - 2023-05.01 
    */
    public function newCopareRangeYearAgo(){
        $calculatePercentageDifference = function ($current, $previous) {
            if ($current == 0 && $previous == 0) {
                return 0;
            } elseif ($previous == 0 && $current != 0) {
                return round($current * 100, 2);
            } else {
                return round(($current - $previous) / $previous * 100, 2);
            }
        };
    
        $currentRaport = [];
        $previousRaport = [];
    
        if ($this->range) {
            $previousRange = (new DateConverter($this->range))->getRangeYearAgo();
        } else {
            $previousRange = (new DateConverter(["dateFrom" => $this->dateFrom, "dateTo" => $this->dateTo]))->getRangeBefore();
        }
    
        $query = "SELECT INDEKS, NAZWA, STAN, CENA_HP0, sum(ILOSC) as SUMA_ILOSC, round(sum(WARTOSC), 2) as SUMA_WARTOSC, round((sum(WARTOSC) / sum(ILOSC)), 2) AS SREDNIA_CENA_SPRZEDAZY
            FROM ltx_stan_opon_186
            INNER JOIN ltx_obroty_232 ON ltx_stan_opon_186.INDEKS = ltx_obroty_232.INDEKS_TOW
            WHERE DATA_FAKT < ? AND DATA_FAKT > ?";
    
        if ($this->indeks != null) {
            $query .= " AND INDEKS = ?";
        }
    
        $query .= " GROUP BY INDEKS ORDER BY ILOSC DESC";
    
        $prepare = $this->dbConnection->prepare($query);
        $params = $this->indeks ? [$this->dateFrom, $this->dateTo, $this->indeks] : [$this->dateFrom, $this->dateTo];
        $prepare->bind_param(str_repeat('s', count($params)), ...$params);
        $prepare->execute();
        $wynik = $prepare->get_result();
    
        while ($row = $wynik->fetch_assoc()) {
            $currentRaport[] = [
                'indeks' => $row['INDEKS'],
                'nazwa' => $row['NAZWA'],
                'stan' => $row['STAN'],
                'cena_hp0' => $row['CENA_HP0'],
                'suma_ilosc' => $row['SUMA_ILOSC'],
                'suma_wartosc' => $row['SUMA_WARTOSC'],
                'srednia_cena_sprzedazy' => $row['SREDNIA_CENA_SPRZEDAZY']
            ];
        }
    
        $query2 = "SELECT INDEKS, NAZWA, STAN, CENA_HP0, sum(ILOSC) as SUMA_ILOSC, round(sum(WARTOSC), 2) as SUMA_WARTOSC, round((sum(WARTOSC) / sum(ILOSC)), 2) AS SREDNIA_CENA_SPRZEDAZY
            FROM ltx_stan_opon_186
            INNER JOIN ltx_obroty_232 ON ltx_stan_opon_186.INDEKS = ltx_obroty_232.INDEKS_TOW
            WHERE DATA_FAKT < ? AND DATA_FAKT > ?";
    
        if ($this->indeks != null) {
            $query2 .= " AND INDEKS = ?";
        }
    
        $query2 .= " AND INDEKS IN ($query) GROUP BY INDEKS ORDER BY ILOSC DESC";
    
        $prepare = $this->dbConnection->prepare($query2);
        $params = $this->indeks ? [$previousRange['dateFrom'], $previousRange['dateTo'], $this->indeks] : [$previousRange['dateFrom'], $previousRange['dateTo']];
        $prepare->bind_param(str_repeat('s', count($params)), ...$params);
        $prepare->execute();
        $wynik = $prepare->get_result();
    
        while ($row = $wynik->fetch_assoc()) {
            $previousRaport[] = [
                'indeks' => $row['INDEKS'],
                'nazwa' => $row['NAZWA'],
                'stan' => $row['STAN'],
                'cena_hp0' => $row['CENA_HP0'],
                'suma_ilosc' => $row['SUMA_ILOSC'],
                'suma_wartosc' => $row['SUMA_WARTOSC'],
                'srednia_cena_sprzedazy' => $row['SREDNIA_CENA_SPRZEDAZY']
            ];
        }
        $prepare->close();
    
        $newPreviousRaport = array_fill(0, count($currentRaport), 'brak_sprzedazy');
    
        for ($i = 0; $i < count($previousRaport); $i++) {
            for ($j = 0; $j < count($currentRaport); $j++) {
                if ($currentRaport[$j]['indeks'] == $previousRaport[$i]['indeks']) {
                    $newPreviousRaport[$j] = $currentRaport[$j];
                }
            }
        }
    
        $raport = [];
        for ($i = 0; $i < count($currentRaport); $i++) {
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
                'srednia_cena_sprzedazy_poprzedni_okres' => $newPreviousRaport[$i] == 'brak_sprzedazy' ?  'brak_sprzedazy' : $newPreviousRaport[$i]['srednia_cena_sprzedazy'],
                'srednia_cena_sprzedazy_procent' => $calculatePercentageDifference($currentRaport[$i]['srednia_cena_sprzedazy'], $newPreviousRaport[$i] == 'brak_sprzedazy' ? 0 : $newPreviousRaport[$i]['srednia_cena_sprzedazy']),
            ];
        }
    
        return $raport;
    }

    /* public function newCopareRangeYearAgo(){
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
            $previousRange = (new DateConverter(["dateFrom" => $this->dateFrom, "dateTo" => $this->dateTo]))->getRangeYearAgo();
        }

        $query = "SELECT INDEKS, NAZWA, STAN, CENA_HP0, sum(ILOSC) as SUMA_ILOSC, round(sum(WARTOSC), 2) as SUMA_WARTOSC, round((sum(WARTOSC) / sum(ILOSC)), 2) AS SREDNIA_CENA_SPRZEDAZY
        from ltx_stan_opon_186 inner join ltx_obroty_232 on ltx_stan_opon_186.INDEKS = ltx_obroty_232.INDEKS_TOW 
        where  DATA_FAKT < ? AND DATA_FAKT > ? ";

        if($this->indeks != null){
            $query = $query . " AND INDEKS = ?";
        }

        $query .= " GROUP BY INDEKS ORDER BY ILOSC DESC";


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
                'stan'=>$row['STAN'], 
                'cena_hp0'=>$row['CENA_HP0'], 
                'suma_ilosc'=>$row['SUMA_ILOSC'], 
                'suma_wartosc'=>$row['SUMA_WARTOSC'], 
                'srednia_cena_sprzedazy'=>$row['SREDNIA_CENA_SPRZEDAZY']];
        }

        $query2 = "SELECT INDEKS, NAZWA, STAN, CENA_HP0, sum(ILOSC) as SUMA_ILOSC, round(sum(WARTOSC), 2) as SUMA_WARTOSC, round((sum(WARTOSC) / sum(ILOSC)), 2) AS SREDNIA_CENA_SPRZEDAZY
        from ltx_stan_opon_186 inner join ltx_obroty_232 on ltx_stan_opon_186.INDEKS = ltx_obroty_232.INDEKS_TOW
        where DATA_FAKT < ? AND DATA_FAKT > ?";

        if($this->indeks != null){
            $query2 = $query2 . " AND INDEKS = ?";
        }

        $query2 .= " and INDEKS IN ($query) GROUP BY INDEKS ORDER BY ILOSC DESC";

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
                'stan'=>$row['STAN'], 
                'cena_hp0'=>$row['CENA_HP0'], 
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
    } */
}

?>