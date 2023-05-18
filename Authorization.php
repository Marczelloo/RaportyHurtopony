<?php

/**
 * Klasa Authorization
 * Zarządza autoryzacją użytkownika i generowaniem tokenów.
 */
class Authorization{
    protected $dbConnection;

    private $login = null;
    private $haslo = null;

    private $token = null;
    private $refreshToken = null;

    private $errors = [];

    private $refreshed = false;

    /**
     * Konstruktor Authorization.
     *
     * @param string|null $token  Token autoryzacji
     * @param string|null $login  Login użytkownika
     * @param string|null $haslo  Hasło użytkownika
     */
    public function __construct($token = null, $login = null, $haslo = null){
        $this->dbConnection = new DBConnect();
        
        if($token != null){
            // Jeśli podano token, zdekoduj go i wyodrębnij login i token
            $token = base64_decode($token);
            $parts = explode('.', $token);
            $this->login = $parts[0];
            $this->token = $parts[1];
        }else if ($login != null && $haslo != null){
            // Jeśli podano login i hasło, zapisz je
            $this->login = $login;
            $this->haslo = md5($haslo);
        } else {
            $this->errors[] = "Nie zalogowano się ani nie podano tokenu!";
        }
    }

    /**
     * Autoryzuje użytkownika przez sprawdzenie tokena, jeżeli walidacja przeszłą pomyślnie oraz tokeny nie zostały odświeżoneto zwraca wartość true jako zwerfyikowano token
     * W przypadku kiedy użytkownik musiał podać refresh token i zostały wygenerowane nowe tokeny to zwraca tablicę z tymi tokenami
     * W kazdym innym przypadku zwraca tablicę z błedami
     *
     * @return bool|array|string  Zwraca true, jeśli autoryzacja jest poprawna, tablicę tokenów, jeśli zostały odświeżone, lub komunikat o błędzie.
     */
    public function checkToken(){
        $this->validateToken();
        if(empty($this->errors) && $this->refreshed === false)
        {
            return true;
        }
        else if($this->refreshed === true)
        {
            return $this->getTokens();
        } 
        else 
        { 
            return $this->errors;
        }
    }
    
    /** 
     * Sprawdza poprawność logowania użytkownika i zwraca tokeny jeśli nie ma błędów, jak jakieś występuja to zwraca tablice z błedami.
     * 
     * @return array  Zwraca tablicę błędów lub tokenów.
    */
    public function checkLogin()
    { 
        $this->login();
        if(empty($this->errors)){
            return $this->getTokens();
        } else {
            return $this->errors;
        }
    }

    /**
     * Zwraca tokeny jako tablice
     *
     * @return array|null  Zwraca tablicę z kluczami 'token' i 'refreshToken', jeśli istnieją tokeny, w przeciwnym razie null.
     */
    public function getTokens(){
        if($this->token != null && $this->refreshToken != null)
        return ['token'=>base64_encode($this->login.".".$this->token), 'refreshToken'=> base64_encode($this->login.".".$this->refreshToken)];
    }

    /**
     * Wykonuje logowanie użytkownika poprzez sprawdzenie podanego loginu i hasła w bazie danych.
     * Po udanym logowaniu generuje tokeny
     */
    public function login(){
        $query = "select haslo from authorization where login = ?";
        $prepare = $this->dbConnection->prepare($query);
        $prepare->bind_param('s', $this->login);
        $prepare->execute();
        $result = $prepare->get_result();

        if($result == null || $result->num_rows == 0){
            $this->errors[] = "Nie ma takiego użytkownika w bazie";
        } else {
            $pass = $result->fetch_assoc()['haslo'];

            if($pass === $this->haslo){
                $this->generateTokens();
            } else { 
                $this->errors[] = "Błędne dane logowania lub nie istnieje taki użytkownik";
            }
        }
    }
    

    /**
     * Sprawdza poprawność podanego tokena przez porównanie z bazą danych.
     * Sprawdza ważność tokena oraz refresh tokena, w przypadku wygaśniecia zwykłego tokena generuje nowy token i refresh token.
     */
    private function validateToken(){
        $query = "select token, refreshToken, tokenLifeTime, refreshLifeTime from authorization where login = ?";
        $prepare = $this->dbConnection->prepare($query);
        $prepare->bind_param('s', $this->login);
        $prepare->execute();
        $result = ($prepare->get_result())->fetch_assoc();
        
        if($this->token !== $result['token']){
            if($this->token !== $result['refreshToken']){
                $this->errors[] = 'Nieprawidłowy token';
                return;
            }
        }
        $date = strtotime((new DateTime())->format('Y-m-d H:i:s'));
        $lifeTime = strtotime($result['tokenLifeTime']);
        $refreshLifeTime = strtotime($result['refreshLifeTime']);

        if($this->token === $result['token']){
            if($lifeTime < $date){
                $this->errors[] = 'Token wygasł, podaj token odświeżający';
            }
            return;
        } 

        if($this->token === $result['refreshToken']){
            if($refreshLifeTime > $date){
                $this->generateTokens();
                $this->refreshed = true;
            } else {
                $this->errors[] = 'Token odświeżający wygasł, zaloguj się ponownie!';
            }
        }
    }

    /**
     * Generuje nowe tokeny dla użytkownika i aktualizuje właściwości związane z tokenem.
     */
    private function generateTokens(){
        $date = (new DateTime())->add(new DateInterval('PT15M'));
        $token = md5($date->format('Y-m-d H:i:s').$this->generateRandom());
        $tokenLifeTime = $date->format('Y-m-d H:i:s');

        $date = (new DateTime())->add(new DateInterval('PT3H'));        
        $refreshToken = md5($date->format('Y-m-d H:i:s').$this->generateRandom());
        $refreshTokenLifeTime = $date->format('Y-m-d H:i:s');

        $query = "UPDATE authorization set token = ?, tokenLifeTime = ?, refreshToken = ?, refreshLifeTime = ? where login = ?";
        $prepare = $this->dbConnection->prepare($query);
        $prepare->bind_param('sssss', $token, $tokenLifeTime, $refreshToken, $refreshTokenLifeTime, $this->login);
        $prepare->execute();

        $this->token = $token;
        $this->refreshToken = $refreshToken;
    }

    /**
     * Generuje losowy ciąg znaków składający sie z dużych i małych liter o długości 30 znaków.
     *
     * @return string  Zwraca losowy ciąg znaków.
     */
    private function generateRandom(){
        $random = '';
        $lenght = 30;

        for($i = 0; $i < $lenght; $i++){
            $r = rand(0, 1);
            if($r == 0){
                $random .= chr(rand(65, 90));
            } else {
                $random .= chr(rand(97, 122));
            }
        }

        return $random;
    }
};
?>