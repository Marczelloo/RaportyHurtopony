<?php
ini_set('display_errors', 0);

include('Raport.php');
include("RaportBestSelling.php");
include("RaportNotSelling.php");
include("RaportMonthlySelling.php");
include("Authorization.php");


/**
 * Klasa API
 * Interfejsc zwracający raporty na podstawie przekazanych danych
 */
class API{
    private $request; // 'bestselling' || 'bestsellingRangeAgo' || 'bestsellingYearAgo' || 'notselling' || 'monthlyselling' || 'raportbestsellingHO || 
    private $range; // 'yesterday' || 'last7days' || 'last14days' || 'last30days' || 'last90days' || 'last180days' || 'lastmonth' || 'thismonth' || 'lastyear' || 'thisyear'
    private $valid_ranges = ['yesterday', 'last7days', 'last14days', 'last30days', 'last90days', 'last180days', 'lastmonth', 'thismonth', 'lastyear', 'thisyear'];
    private $dateFrom; //zakres dat od i do jest liczony w przeszlosc czyli od = 12.05.2023, do = 01.01.2023
    private $dateTo; 
    private $indeks;

    private $login;
    private $haslo;
    
    private $token;

    private $errors = [];
    private $result = [];

    /**
     * Konstruktor API
     */
    public function __construct(){    
        $this->request = $_POST['request'] ?? null;
        $this->range = $_POST['range'] ?? null;
        $this->dateFrom = $_POST['dateFrom'] ?? null;
        $this->dateTo = $_POST['dateTo'] ?? null;
        $this->indeks = $_POST['indeks'] ?? null;
        $this->token = $_POST['token'] ?? null;
        $this->login = $_POST['login'] ?? null;
        $this->haslo = $_POST['haslo'] ?? null;
    }

    /** 
     * Zajmuje się walidacją podanych danych
     * W zależności od requesta loguje się i zwraca tokeny, który trzeba podać podczas żądania
     * albo pokazuje raport dla danego żądanie
     * W razie nieudanej walidacji danych lub tokenu zwraca tablice w JSON 
     * W razie pomyślnej walidacji zwraca wynik żądania tablice w JSON
    */
    public function handle(){
        //oczyszcza dane z niepotrzebnych znaków
        $this->clearInputs();
        
        /**
         * Sprawdzenie czy podano login i haslo
         * Jeżeli podano login i hasło to waliduje tylko login i hasło
         * Jeżeli nie podano loginu i hasła to waliduje potrzebne dane do request
         */
        if(!isset($this->login) && !isset($this->haslo))
        {
            if(!isset($this->range) || $this->range == null || $this->range == "")
            {
                $this->validateDateRange();
            } 
            else 
            {
                $this->validateRangeInput();
            }
            $this->validateIndeks();
        } 
        else 
        {
           $this->validateLoginCredentuals();
        }

        //Sprawdzenie czy podano request 
        if(!isset($this->request))
        {
            $this->errors[] = "Nie podano zadania(request)!";
        }
        else 
        {
           $this->validateRequest();
        }
                
        //Utworzenie wyniku w zależności czy są jakieś błędy czy nie
        $response = [];
        if(empty($this->errors))
        {
            echo json_encode(['success'=> 1 , "result" => $this->result]);
        } 
        else 
        {
            echo json_encode(['success'=> 0 , "result" => $this->errors]);
        }
    }

    //Funkcja oczyszczająca wszystkie dane
    private function clearInputs(){
        $this->request = $this->sanitizeInput($this->request);
        $this->range = $this->sanitizeInput($this->range);
        $this->dateFrom = $this->sanitizeInput($this->dateFrom);
        $this->dateTo = $this->sanitizeInput($this->dateTo);
        $this->indeks = $this->sanitizeInput($this->indeks);
        $this->login = $this->sanitizeInput($this->login);
        $this->haslo = $this->sanitizeInput($this->haslo);
    }

    //Funckja walidująca date oraz range
    //W zależności czy użytkownik podał zakres dat czy range takie dane zostaną przekazane do requesta
    private function validateDateRange(){
        if($this->dateFrom == null){
            $this->errors[] = "Nie podano zakresu daty od(dateFrom)!";
        } else {
            if (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $this->dateFrom)) {
                $this->errors[] = "Niepoprawny format daty (dateFrom)!";
            } else {
                $dateCheck = DateTime::createFromFormat('Y-m-d', $this->dateFrom);
                if(($dateCheck && $dateCheck->format('Y-m-d') === $this->dateFrom)) {
                    $this->range = ['dateFrom' => $this->dateFrom];
                } else {
                    $this->errors[] = "Niepoprawna data (dateFrom)!";
                }
            }
            
        }

        if($this->dateTo == null)
        {
            $this->errors[] = "Nie podano zakresu daty do(dateTo)!";
        } 
        else 
        {
            if (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $this->dateTo)) 
            {
                $this->errors[] = "Niepoprawny format daty (dateTo)!";
            } 
            else 
            {
                $dateCheck = DateTime::createFromFormat('Y-m-d', $this->dateTo);
                if($dateCheck && $dateCheck->format('Y-m-d') === $this->dateTo) 
                {
                    if($this->range != null) 
                    {
                        $this->range += ['dateTo' => $this->dateTo];
                    }
                } 
                else 
                {
                    $this->errors[] = "Niepoprawna data (dateTo)!";
                }
            }
        }

        if(empty($this->range))
        {
            $this->errors[] = "Nie podano zakresu(range)!";
        }
    }

    //Funckja sprawdzająca czy podany przez użytkownika range znajduje się w możliwch do podnia rangeów
    private function validateRangeInput(){
        if(is_string($this->range))
        {
            if (!in_array($this->range, $this->valid_ranges)) 
            {
                $this->errors[] = "Niepoprawny zakres(range)!";
            }
        } 
        else 
        {
            $this->errors = "Błędny rodzaj zakresu(raneg) Zakres musi byc rodzaju string";
        }
    }


    //Funckja sprawdzająca czy indeks jest poprawny
    private function validateIndeks(){
        if($this->indeks !=  null)
        {
            if(!is_string($this->indeks))
            {
                $this->errors[] = "Nie poprawny format indeksu!";
            } 
            else if(!preg_match('/^[0-9]+$/', $this->indeks)) 
            {
                $this->errors[] = "Niepoprawny format indeksu! Indeks moze zawierac tylko cyfry.";
            }  
        }
    }

    //Funckja sprawdzająca czy login i hasło są poprawne
    private function validateLoginCredentuals(){
        if($this->login == null || $this->login == "")
        {
            $this->errors[] = "Nie poprawny format login lub nie podano";
        }

        if($this->haslo == null || $this->haslo == "")
        {
            $this->errors[] = "Nie poprawny format haslo lub nie podano";
        }
    }

    //Sprawdzenie ważności oraz poprawności tokenu oraz przetwarzanie żądań
    //Jeżeli request = "getTokens" zalogowanie użytkownika oraz przekazanie tokenów
    private function validateRequest(){
        if(is_string($this->request))
        {
            /**
            *Jeżeli użytkownik podał token jest on sprawdzany
            *Jeżeli nie to jest pomijane sprawdzanie, 
            *gdyż albo sie loguje i podał login i hasło i nie ma jeszcze tokenu
            *albo nie ma go i nie zostanie przepuszczony dalej
            */
            if($this->token != null)
            {
                $auth_token = new Authorization($this->token);
                $valid = $auth_token->checkToken();
                if(is_array($valid))
                {
                    if(array_key_exists('token', $valid) && array_key_exists('refreshToken', $valid))
                    {
                        $this->result = $valid;
                    } 
                    else
                    {
                        $this->errors = $valid;
                    }
                } 
            }

            //sprawdzenie czy użytkownik przeszedł walidacje tokenu 
            //lub czy podał login i hasło żęby sie załogować i wygenerować tokeny
            if($valid === true || ($this->login != null && $this->haslo != null))
            {
                switch($this->request)
                {
                    case 'getToken' : 
                        $auth = new Authorization(null, $this->login, $this->haslo);
                        $validLogin = $auth->checkLogin();
                        if(array_key_exists('token', $validLogin) && array_key_exists('refreshToken', $validLogin))
                        {
                            $this->result = $auth->getTokens();
                        } 
                        else 
                        {
                            $this->errors = $validLogin;
                        }
                    break;
                    case 'bestselling':
                        $this->bestselling();
                    break;
                    case 'bestsellingRangeAgo':
                        $this->bestsellingRangeAgo();
                    break;
                    case 'bestsellingYearAgo':
                        $this->bestSellingRangeYearAgo();
                    break;
                    case 'notselling':
                        $this->notselling(); 
                    break;
                    case 'monthlyselling':
                        $this->monthlyselling();
                    break;
                    case 'raportbestsellingHO':
                        $this->raportBestsellingHO();
                    break;
                    default:
                    $this->errors[] = "Niepoprawne żądanie(request)!";
                }
            }
        } 
        else 
        {
            $this->errors[] = "Nie poprawny format żądania(request)";
        }
    }

    //Funckja wykonująca żądanie z raportem bestselling
    //W zależności czy podano indeks oraz zakres dat czy range przypisuje odpowiednie parametry
    private function bestselling(){
        $raport = new RaportBestSelling();
        if($this->indeks != null)
        { 
            if(isset($this->range))
            {
                $raport->setParameters(['range'=> $this->range, 'indeks'=> $this->indeks]);
            } 
            else 
            {
                $raport->setParameters(['dateFrom'=> $this->dateFrom, 'dateTo'=> $this->dateTo, 'indeks'=>$this->indeks]);
            }
        } 
        else
        { 
            if(isset($this->range))
            {
                $raport->setParameters(['range'=> $this->range]);
            } 
            else 
            {
                $raport->setParameters(['dateFrom'=> $this->dateFrom, 'dateTo'=> $this->dateTo]);
            }
        }
        $this->result = $raport->generate();
        $raport->dbClose();
    }

    //Funckja wykonująca żądanie z raportem bestsellingRangeAgo
    //W zależności czy podano indeks oraz zakres dat czy range przypisuje odpowiednie parametry
    private function bestsellingRangeAgo(){
        $raport = new RaportBestSelling();
        if(isset($this->indeks))
        { 
            if(isset($this->range))
            {
                $raport->setParameters(['range'=> $this->range, 'indeks'=> $this->indeks]);
                $this->result = $raport->newCompareRange();
            } 
            else
            {
                $raport->setParameters(['dateFrom'=> $this->dateFrom, 'dateTo'=> $this->dateTo, 'indeks'=>$this->indeks]);
                $this->result = $raport->newCompareRange();
            }
        } 
        else
        { 
            if(isset($this->range))
            {
                $raport->setParameters(['range'=> $this->range]);
                $this->result = $raport->newCompareRange();
            } 
            else 
            {
                $raport->setParameters(['dateFrom'=> $this->dateFrom, 'dateTo'=> $this->dateTo]);
                $this->result = $raport->newCompareRange();
            }
        }
        
        $raport->dbClose();
    }

    //Funckja wykonująca żądanie z raportem bestsellingYearAgo
    //W zależności czy podano indeks oraz zakres dat czy range przypisuje odpowiednie parametry
    private function bestsellingRangeYearAgo(){
        $raport = new RaportBestSelling();
        if(isset($this->indeks))
        { 
            if(isset($this->range))
            {
                $raport->setParameters(['range'=> $this->range, 'indeks'=> $this->indeks]);
                $this->result = $raport->newCopareRangeYearAgo();
            } 
            else 
            {
                $raport->setParameters(['dateFrom'=> $this->dateFrom, 'dateTo'=> $this->dateTo, 'indeks'=>$this->indeks]);
                $this->result = $raport->newCopareRangeYearAgo();
            }
        } 
        else
        { 
            if(isset($this->range))
            {
                $raport->setParameters(['range'=> $this->range]);
                $this->result = $raport->newCopareRangeYearAgo();
            } 
            else 
            {
                $raport->setParameters(['dateFrom'=> $this->dateFrom, 'dateTo'=> $this->dateTo]);
                $this->result = $raport->newCopareRangeYearAgo();
            }
        }
        $raport->dbClose();
    }

    //Funckja wykonująca żądanie z raportem notselling
    //W zależności czy podano indeks oraz zakres dat czy range przypisuje odpowiednie parametry
    private function notselling(){
        $raport = new RaportNotSelling();
        if(isset($this->indeks))
        { 
            if(isset($this->range))
            {
                $raport->setParameters(['range'=> $this->range, 'indeks'=> $this->indeks]);
            } 
            else 
            {
                $raport->setParameters(['dateFrom'=> $this->dateFrom, 'dateTo'=> $this->dateTo, 'indeks'=>$this->indeks]);
            }
        } 
        else
        { 
            if(isset($this->range))
            {
                $raport->setParameters(['range'=> $this->range]);
            } 
            else 
            {
                $raport->setParameters(['dateFrom'=> $this->dateFrom, 'dateTo'=> $this->dateTo]);
            }
        }
        $this->result = $raport->generate();
        $raport->dbClose();
    }

    //Funckja wykonująca żądanie z raportem monthlyselling
    //W zależności czy podano indeks oraz zakres dat czy range przypisuje odpowiednie parametry
    private function monthlyselling(){ 
        $raport = new RaportMonthlySelling();
        if(isset($this->indeks))
        { 
            if(isset($this->range))
            {
                $raport->setParameters(['range'=> $this->range, 'indeks'=> $this->indeks]);
            } 
            else 
            {
                $raport->setParameters(['dateFrom'=> $this->dateFrom, 'dateTo'=> $this->dateTo, 'indeks'=>$this->indeks]);
            }
        } 
        else
        { 
            if(isset($this->range))
            {
                $raport->setParameters(['range'=> $this->range]);
            } 
            else 
            {
                $raport->setParameters(['dateFrom'=> $this->dateFrom, 'dateTo'=> $this->dateTo]);
            }
        }
        $this->result = $raport->generate();
        $raport->dbClose();
    }

    private function raportbestsellingHO(){
        $raport = new RaportBestSellingHO();
        if(isset($this->indeks))
        {
            if(isset($this->range))
            {
                $raport->setParameters(['range'=> $this->range, 'indeks'=>$this->indeks]);
            }
            else
            {
                $raport->setParameters(['dateFrom'=> $this->dateFrom, 'dateTo'=> $this->dateTo, 'indeks'=>$this->indeks]);
            }
        }
        else
        {
            if(isset($this->range))
            {
                $raport->setParameters(['range'=> $this->range]);
            } 
            else 
            {
                $raport->setParameters(['dateFrom'=> $this->dateFrom, 'dateTo'=> $this->dateTo]);
            }
        }
    }

    /**
    *Funckja czyszcząca dane z pustych miejsc przed i po zmienną, 
    *cudzysłowów, zamienjąca specjalne nzaki HTML 
    *oraz sprawdzająca czy w zmiennej nie jest przekazane zapytanie SQL z atakiem
    * @return string Zwraca oczyszczoną zmienną
    */
    private function sanitizeInput($input) {
        if($input === null)
        return;
        $input = trim($input);
        $input = stripslashes($input);
        $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
        if (preg_match("/[\'=]/", $input)) 
        {
            $this->errors[] = "Input zawiera atak wstrzykujący SQL";
        }
    
        return $input;
    }
}


?>