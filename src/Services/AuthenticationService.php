<?php
namespace WellCat\Services;

use Silex\Application;

class AuthenticationService
{
    protected $app;
    private $salt;

    public function __construct(Application $app, $salt)
    {
        $this->app = $app;
        $this->salt = $salt;
    }

    //checks to see if user session is setup and if so user is logged in.
    public function Authenticated()
    {
        $sessionUser = $this->app['session']->get('user');
        
        return $sessionUser != null
        && $sessionUser['email'] != null;
        //&& $sessionUser['userId'] != null;
    }


    public function PasswordRequirements($password)
    {
        //TODO:
        //need to input a regular expression formula for passwords we accept and confirm with the rest of
        //the teams the requirements. (need one of 4 or all? caps, numbers, lowercase, special character)
        if (!preg_match('', $password)) {
            return false;
        }
        return true;
    }

    public function EncryptPassword($password)
    {
        $password = $password.$this->salt;
        return password_hash($password, PASSWORD_DEFAULT);
    }

    public function Authenticate($email, $password)
    {
        if ($this->CheckPassword($email, $password)) {
            //  TODO: improve error handling so we can specifiy whether Authentication failed due to invalid password, or not being able to set session user
            return $this->SetSessionUser($email);
        } else {
            return false;
        }
    }

    private function CheckPassword($email, $password)
    {
        $prePassword = $password . $this->salt;
        $sql = 'SELECT password FROM account WHERE email = :email';
        $stmt = $this->app['db']->prepare($sql);
        $stmt->execute(array(':email' => $email));
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        if ($result == false) {
            return false;
        }
        if (password_verify($prePassword, $result['password'])) {
            return true;
        } else {
            return false;
        }
    }

    private function SetSessionUser($email)
    {
        $sql = 'SELECT userid FROM account WHERE email = :email';
        $stmt = $this->app['db']->prepare($sql);
        $stmt->execute(array(':email' => $email));
                
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);

        // TODO: probably better to throw an exception if false
        if ($result == false) {
            return false;
        }        

        $this->app['session']->set('user', array(
            'email' => $email,
            'userId' => (int)$result['userid'])
        );

        return true;
    }
}