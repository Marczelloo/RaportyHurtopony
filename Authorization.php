<?php

class Authorization{
    protected $dbConnection;

    private $login = null;
    private $haslo = null;

    private $token = null;
    private $refreshToken = null;

    private $tokenLifeTime = null;
    private $refreshTokenLifeTime = null;

    private $valid = null;

    private $errors = [];


    public function __construct($token = null, $login = null, $haslo = null){
        $this->dbConnection = new DBConnect();
        
        if($token != null){
            $parts = explode('.', $token);
            $this->login = $parts[0];
            $this->token = $parts[1];
        }else if ($login != null && $haslo != null){
            $this->login = $login;
            $this->haslo = md5($haslo);
        } else {
            $this->errors[] = "Nie zalogowano sie ani nie podano tokenu!";
        }
    }

    public function authorize(){
        $this->validateToken();
        if(empty($this->errors)){
            return true;
        } else { 
            return $this->errors;
        }

    }

    public function getToken(){
        if($this->token != null && $this->refreshToken != null)
        return ['token'=>base64_encode($this->login.".".$this->token), 'refreshToken'=> base64_encode($this->login.".".$this->refreshToken)];
    }

    public function login(){
        $query = "select haslo from authorization where login = ?";
        $prepare = $this->dbConnection->prepare($query);
        $prepare->bind_param('s', $this->login);
        $prepare->execute();
        $result = $prepare->get_result();
        $pass = $result->fetch_assoc()['haslo'];

        if($pass === $this->haslo){
            $this->generateTokens();
        } else { 
            $this->errors[] = "Bledne dane logowanie lub nie istnieje taki użytkownik";
        }

        if(!empty($this->errors)){
            return $this->errors;
        }
    }
    

    private function validateToken(){
        $query = "select token, refreshToken, tokenLifeTime, refreshLifeTime from authorization where login = ?";
        $prepare = $this->dbConnection->prepare($query);
        $prepare->bind_param('s', $this->login);
        $prepare->execute();
        $result = ($prepare->get_result())->fetch_assoc();
        
        if($this->token !== $result['token']){
            if($this->token !== $result['refreshToken']){
                $this->errors[] = 'Nie poprawny token';
                return;
            }
        }

        $date = strtotime((new DateTime())->format('Y-m-d H:i:s'));
        $lifeTime = strtotime($result['tokenLifeTime']);
        $refreshLifeTime = strtotime($result['refreshLifeTime']);

        if($lifeTime < $date){            
            $this->errors[] = "Token wygasl, podaj refresh token";

            if($refreshLifeTime > $date){
                $this->generateTokens();
            } else {
                $this->valid = false;
                $this->errors[] = "Refresh token wygasł, zaloguj sie ponownie!";
            }
        }
    }

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

    private function generateRandom(){
        $random = '';
        $lenght = 30;

        for($i  = 0; $i < $lenght; $i++){
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