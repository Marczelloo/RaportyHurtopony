<?php

class RaportBestSelling extends Raport{

    public function __construct(){
        parent::__construct();
    }

    public function generate($month = null, $year = null){
        $raport = [];

        if($this->dateFrom != null && $this->dateTo != null){

            //stworzenie bazy zapytania
            $query =  $query = "SELECT INDEKS, NAZWA, STAN, CENA_HP0, sum(ILOSC) as SUMA_ILOSC, round(sum(WARTOSC), 2) as SUMA_WARTOSC, round((sum(WARTOSC) / sum(ILOSC)), 2) AS SREDNIA_CENA_SPRZEDAZY
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

    public function compareRange($showRaports = false){
        //arg: raports = fale : wypisuje tylko tablice z procentami z porownanych okresow
        //raports = true : wypisuje raport z pierwszego okresu oraz drugiego i procenty
        
        $currentRange = $this->range;
        $previosRange = (new DateConverter($this->range))->getRangeBefore();

        $currentRaport = new RaportBestSelling();
        $currentRaport->setParameters(['range' => $currentRange, 'indeks'=> $this->indeks]);
        $currentRaport = $currentRaport->generate()[0];

        $previosRaport = new RaportBestSelling();
        $previosRaport->setParameters(['range' => $previosRange, 'indeks'=> $this->indeks]);
        $previosRaport = $previosRaport->generate()[0];

        $calculatePercentageDifference = function($current, $previous) {
            return round((($current - $previous) / $previous) * 100, 2);
        };
        
        $percentages = [
            'indeks' => $currentRaport['indeks'],
            'nazwa' => $currentRaport['nazwa'],
            'stan_procent' => $calculatePercentageDifference($currentRaport['stan'], $previosRaport['stan']),
            'cena_hp0' => $calculatePercentageDifference($currentRaport['cena_hp0'], $previosRaport['cena_hp0']),
            'suma_ilosc_procent' => $calculatePercentageDifference($currentRaport['suma_ilosc'], $previosRaport['suma_ilosc']),
            'suma_wartosc_procent' => $calculatePercentageDifference($currentRaport['suma_wartosc'], $previosRaport['suma_wartosc']),
            'srednia_cena_sprzedazy_procent' => $calculatePercentageDifference($currentRaport['srednia_cena_sprzedazy'], $previosRaport['srednia_cena_sprzedazy']),
        ];

        $raport = [
            ['okres_domyslny' => $currentRaport],
            ['okres_poprzedni' => $previosRaport],
            ['raport' => $percentages],
        ];
       
        if (!$showRaports) {
            unset($raport['okres_domyslny']);
            unset($raport['okres_poprzedni']);
            
            $raport = $percentages;
        } else {
            $raport = [
                ['okres_domyslny' => $currentRaport],
                ['okres_poprzedni' => $previosRaport],
                ['raport' => $percentages],
            ];
        }

        return $raport;
    }

    public function compareYearAgo($showRaports = false){
        //arg: raports = fale : wypisuje tylko tablice z procentami z porownanych okresow
        //raports = true : wypisuje raport z pierwszego okresu oraz drugiego i procenty
        $currentRange = $this->range;
        $previosRange = (new DateConverter($this->range))->getRangeYearAgo();

        $currentRaport = new RaportBestSelling();
        $currentRaport->setParameters(['range' => $currentRange, 'indeks'=> $this->indeks]);
        $currentRaport = $currentRaport->generate()[0];

        $previosRaport = new RaportBestSelling();
        $previosRaport->setParameters(['range' => $previosRange, 'indeks'=> $this->indeks]);
        $previosRaport = $previosRaport->generate()[0];

        $calculatePercentageDifference = function($current, $previous) {
            return round((($current - $previous) / $previous) * 100, 2);
        };
        
        $percentages = [
            'indeks' => $currentRaport['indeks'],
            'nazwa' => $currentRaport['nazwa'],
            'stan_procent' => $calculatePercentageDifference($currentRaport['stan'], $previosRaport['stan']),
            'cena_hp0' => $calculatePercentageDifference($currentRaport['cena_hp0'], $previosRaport['cena_hp0']),
            'suma_ilosc_procent' => $calculatePercentageDifference($currentRaport['suma_ilosc'], $previosRaport['suma_ilosc']),
            'suma_wartosc_procent' => $calculatePercentageDifference($currentRaport['suma_wartosc'], $previosRaport['suma_wartosc']),
            'srednia_cena_sprzedazy_procent' => $calculatePercentageDifference($currentRaport['srednia_cena_sprzedazy'], $previosRaport['srednia_cena_sprzedazy']),
        ];

        $raport = [
            ['okres_domyslny' => $currentRaport],
            ['okres_poprzedni' => $previosRaport],
            ['raport' => $percentages],
        ];
       
        if (!$showRaports) {
            unset($raport['okres_domyslny']);
            unset($raport['okres_poprzedni']);
            
            $raport = $percentages;
        } else {
            $raport = [
                ['okres_domyslny' => $currentRaport],
                ['okres_poprzedni' => $previosRaport],
                ['raport' => $percentages],
            ];
        }

        return $raport;
    }

}

?>