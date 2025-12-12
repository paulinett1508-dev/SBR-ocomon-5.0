<?php

namespace OcomonApi\WebControllers;

use Firebase\JWT\JWT;
use OcomonApi\Models\User;
use OcomonApi\Core\OcomonWeb;
use OcomonApi\Support\Message;
use OcomonApi\Models\AccessToken;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\BeforeValidException;
use Firebase\JWT\SignatureInvalidException;

class AccessTokens extends OcomonWeb
{

    public function __construct()
    {
        parent::__construct();
        
    }


    /**
     * is_valid
     *
     * @param string $login
     * @param string $app
     * @param string $token
     * 
     * @return bool
     */
    public function is_valid(string $login, string $app, string $token): bool
    {
        $user = (new User())->findByLogin($login);
        if ($user) {
            try {
                $decoded = JWT::decode($token, $user->data()->hash ?? $user->data()->password, array('HS256'));
                if ($decoded->data->app === $app)
                    return true;
                return false;
            } catch (ExpiredException $e) {
                echo $e->getMessage();
                return false;
            } catch (SignatureInvalidException $e) {
                echo $e->getMessage();
                return false;
            } catch (BeforeValidException $e) {
                echo $e->getMessage();
                return false;
            } catch (\Exception $e) {
                echo $e->getMessage();
                return false;
            }
        }
        return false;
    }


    /**
     * is_equal
     *
     * @param string $login
     * @param string $app
     * @param string $tokenWeb
     * 
     * @return bool
     */
    public function is_equal(string $login, string $app, string $tokenWeb): bool
    {
        $user = (new User())->findByLogin($login);
        if ($user) {
            $tokenDb = (new AccessToken())->findByUserAndApp($user->data()->user_id, $app);
            if ($tokenDb) {
                if (strcmp($tokenDb->data->token, $tokenWeb) == 0)
                    return true;
                return false;
            }
            return false;
        }
        return false;
    }

    /**
     * is_allowed
     * Principal metodo para validacao do token
     * @param string $login
     * @param string $app
     * @param string $tokenWeb
     * 
     * @return bool
     */
    public function is_allowed(string $login, string $app, string $tokenWeb): bool
    {
        $message = new Message();
        
        if (!$this->is_equal($login, $app, $tokenWeb)) {
            $this->message = $message->error('Tokens diferentes');
            // var_dump($this->message);
            echo $this->message->getText();
            return false;
        }
        
        if (!$this->is_valid($login, $app, $tokenWeb)) {
            $this->message = $message->error('Token inválido');
            // var_dump($this->message);
            echo $this->message->getText();
            return false;
        }

        return true;
    }


    public function expire_at(string $login, string $app, string $tokenWeb): string
    {
        $user = (new User())->findByLogin($login);
        if ($user) {
            try {
                $decoded = JWT::decode($tokenWeb, $user->data()->hash ?? $user->data()->password, array('HS256'));
                return date('d/m/Y H:i:s',$decoded->exp);
                
            } catch (ExpiredException $e) {
                return $e->getMessage();
            } catch (SignatureInvalidException $e) {
                return $e->getMessage();
            } catch (BeforeValidException $e) {
                return $e->getMessage();
            } catch (\Exception $e) {
                return $e->getMessage();
            }
        }
    }



}