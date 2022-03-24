<?php

use Phalcon\Mvc\Model;

class Users extends Model
{
    public $user_id;
    public $name;
    public $email;

    public function getUsers()
    {
        $user = Users::find();
        return $user;
    }
    public function checkUser($email, $password)
    {
        $user =  Users::findFirst(['conditions' => "email = '$email' AND password = '$password'"]);
        return $user;
    }
}
