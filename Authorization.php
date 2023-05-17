<?php

/**
 * Class Authorization
 * Handles user authorization and token generation.
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
     * Authorization constructor.
     *
     * @param string|null $token  Token for authorization
     * @param string|null $login  User login
     * @param string|null $haslo  User password
     */
    public function __construct($token = null, $login = null, $haslo = null){
        $this->dbConnection = new DBConnect();
        
        if($token != null){
            // If token is provided, decode it and extract login and token parts
            $token = base64_decode($token);
            $parts = explode('.', $token);
            $this->login = $parts[0];
            $this->token = $parts[1];
        }else if ($login != null && $haslo != null){
            // If login and password are provided, store them
            $this->login = $login;
            $this->haslo = md5($haslo);
        } else {
            $this->errors[] = "Nie zalogowano sie ani nie podano tokenu!";
        }
    }

    /**
     * Authorize the user by validating the token and generating new tokens if necessary.
     *
     * @return bool|array|string  Returns true if authorized, an array of tokens if refreshed, or an error message.
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
     *  Checks if user validation gone well and return the tokens if theres no errors and return errors if there are anu
     * 
     * @return array Returns array of errors or tokens
    */
    public function checkLogin()
    { 
        if(empty($this->errors)){
            return $this->getTokens();
        } else {
            return $this->errors;
        }
    }

    /**
     * Get the tokens as an array.
     *
     * @return array|null  Returns an array with 'token' and 'refreshToken' keys if tokens exist, otherwise null.
     */
    public function getTokens(){
        if($this->token != null && $this->refreshToken != null)
        return ['token'=>base64_encode($this->login.".".$this->token), 'refreshToken'=> base64_encode($this->login.".".$this->refreshToken)];
    }

    /**
     * Perform user login by checking the provided login and password against the database.
     */
    public function login(){
        $query = "select haslo from authorization where login = ?";
        $prepare = $this->dbConnection->prepare($query);
        $prepare->bind_param('s', $this->login);
        $prepare->execute();
        $result = $prepare->get_result();

        if($result == null || $result->num_rows == 0){
            $this->errors[] = "Nie ma takiego uzytkownika w bazie";
        } else {
            $pass = $result->fetch_assoc()['haslo'];

            if($pass === $this->haslo){
                $this->generateTokens();
            } else { 
                $this->errors[] = "Bledne dane logowanie lub nie istnieje taki użytkownik";
            }
        }
    }
    

    /**
     * Validate the provided token by checking against the database.
     * Generate new tokens if necessary and update the token-related properties.
     */
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

        if($this->token === $result['token']){
            if($lifeTime < $date){
                $this->errors[] = 'Token wygasl, podaj refresh token';
            }
            return;
        } 

        if($this->token === $result['refreshToken']){
            if($refreshLifeTime > $date){
                $this->generateTokens();
                $this->refreshed = true;
            } else {
                $this->errors[] = 'Refresh token wygasl, zaloguj sie ponownie!';
            }
        }
    }

    /**
     * Generate new tokens for the user and update the token-related properties.
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
     * Generate a random string.
     *
     * @return string  Returns a random string.
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
