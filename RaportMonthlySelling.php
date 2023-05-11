<?php

class RaportMonthlySelling extends Raport{

    //raport dla pojedynczej opony
        //dla jednego indeksu przedzila w miesiacach (ten rok, poprzedni rok)+
        //$range albo dateFrom and dateTo oraz indeks w setParameters +


        //raportbestselling porownanie raportu z poprzednim miesiacem i porownanie procentowo sprzedazy
        //dataConverter dopisanie dwoch funkcji okres poprzedni i z poprzedniego roku +
        // dodanie mozliwosci podania zakresu date nie z range+

    public function __construct(){
        parent::__construct();
    }

    public function generate(){
        $raport = [];

        if (in_array($this->range, ['yesterday', 'last7days', 'last14days'])) {
            $bestSelling = new RaportBestSelling();
            $params = ['indeks' => $this->indeks, 'range' => $this->range];
            $bestSelling->setParameters($params);
            $raport["$this->dateTo-$this->dateFrom"] = $bestSelling->generate();
            return $raport;
        }

        $start_timestamp = $this->dateTo;
        $end_timestamp = $this->dateFrom;
        $current_timestamp = $start_timestamp;

        $bestSelling = new RaportBestSelling();
        $params = ['indeks' => $this->indeks, 'range' => $this->range];
        $bestSelling->setParameters($params);

        while ($current_timestamp <= $end_timestamp) {
            $date = new DateTime($current_timestamp);
            $month = $date->format('m');
            $year = $date->format('Y');

            $wynik = $bestSelling->generate($month, $year);
            $offset = "$year-$month";

            if (empty($wynik)) {
                $wynik = [['indeks' => $this->indeks, 'nazwa' => 'brak sprzedazy', 'stan' => 'brak sprzedazy', 'cena_hp0' => 'brak sprzedazy', 'suma_ilosc' => 'brak sprzedazy', 'suma_wartosc'=> 'brak sprzedazy']];
            }

            $raport[] = [$offset => $wynik];

            $current_timestamp = $date->format('Y-m') . '-01';
            $date->modify('+1 month');
            $current_timestamp = $date->format('Y-m-d');
        }

        $this->dbClose();
        
        return $raport;
    }

}

?>