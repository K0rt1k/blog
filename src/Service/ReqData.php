<?php

namespace MyApp;

class ReqDataService
{
    private $reqUser;

    function __construct($request)
    {
        if($request) $this->reqUser = $request->toArray();
    }

    public function getReqUserArr(): array
    {
        return $this->reqUser;
    }

    public function getReqUserLogin(): string
    {
        return $this->reqUser["login"];
    }

    public function getReqUserPassword(): string
    {
        return $this->reqUser["password"];
    }

    public function getReqUserEmail(): string
    {
        return $this->reqUser["email"];
    }

    public function getReqUserRole(): string
    {
        return $this->reqUser["role"];
    }

    public function autorization($userLogin, $userPassword): bool
    {
        if ($this->reqUser["login"] == $userLogin){
            if (password_verify($this->reqUser["password"], $userPassword)){
                return true;
            }
        }
        return false;
    }

    function passwordHesh()
    {
        $this->reqUser["password"] = password_hash($this->reqUser["password"], PASSWORD_DEFAULT);
    }

    function checkPassword($password): bool
    {
        if (!password_verify($this->reqUser["password"], $password)){
            return true;
        }
        return false;
    }

    function checkEmail($email): bool
    {
        if ($this->reqUser["email"] != $email){
            return true;
        }
        return false;
    }

}
