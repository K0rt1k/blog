<?php

namespace MyApp;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Throwable;


class ServiceJWT
{
    private $secretKey  = "Secret_Key";
    private $httpAuthor;
    private $messege;
    private $decoded;

    function __construct($request)
    {
        if($request) $this->httpAuthor = $request->server->get('HTTP_AUTHORIZATION');
    }

    public function checkJWT()
    {
        if (!empty($this->httpAuthor)) {
            if (preg_match('/Bearer\s(\S+)/', $this->httpAuthor, $matches)) {
                $jwt = $matches[1];
            }
        }
        
        try
        {
            $this->decoded = JWT::decode($jwt, new Key($this->secretKey, 'HS256'));
        }
        catch(Throwable $ex)
        {
            $this->messege = "Сообщение об ошибке: " . $ex->getMessage() . "<br>";
        }
    }

    public function getLogin(): string
    {
        if($this->decoded->userLogin){
            return $this->decoded->userLogin;
        }
    }

    public function getErrors(): string
    {
        if($this->messege) return $this->messege;
    }

}
