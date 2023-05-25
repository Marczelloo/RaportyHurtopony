<?php

/**
 * Klasa RaportMonthlySelling generuje raport miesięcznej sprzedaży produktów dla bazy HURTOPONY.
 * Dziedziczy po klasie Raport.
 */
class RaportMonthlySellingHO extends Raport{

    /**
     * Konstruktor klasy RaportMonthlySelling, wywołuje konstruktor klasy nadrzędnej
     */
    public function __construct(){
        parent::__construct();
    }

    /**
     * Metoda generate generuje raport miesięcznej sprzedaży produktów.
     * @return array Tablica zawierająca wygenerowany raport.
     */
    public function generate(){
        $raport = [];

        // Sprawdzenie, czy raport ma być wygenerowany dla pojedynczej opony w określonym przedziale czasu
        if (in_array($this->range, ['yesterday', 'last7days', 'last14days'])) {
            $bestSelling = new RaportBestSellingHO();
            $params = ['indeks' => $this->indeks, 'range' => $this->range];
            $bestSelling->setParameters($params);
            $raport["$this->dateTo-$this->dateFrom"] = $bestSelling->generate();
            return $raport;
        }

        $start_timestamp = $this->dateTo;
        $end_timestamp = $this->dateFrom;
        $current_timestamp = $start_timestamp;

        $bestSelling = new RaportBestSellingHO();
        $params = ['indeks' => $this->indeks, 'range' => $this->range];
        $bestSelling->setParameters($params);

        // Generowanie raportu dla każdego miesiąca w określonym przedziale czasu
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
        
        return $raport;
    }

}

?>
