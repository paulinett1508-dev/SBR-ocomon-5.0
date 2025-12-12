<?php

namespace OcomonApi\Models;

use OcomonApi\Support\Message;
use CoffeeCode\DataLayer\DataLayer;
use Firebase\JWT\JWT;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\BeforeValidException;
use Firebase\JWT\SignatureInvalidException;

/**
 * OcoMon Api | Class AccessToken Active Record Pattern
 *
 * @author Flavio Ribeiro <flaviorib@gmail.com>
 * @package OcomonApi\Models
 */
class AccessToken extends DataLayer
{
    /**
     * AccessToken constructor.
     */
    public function __construct()
    {
        parent::__construct("access_tokens", ["token"], "id", true);
    }

    /**
     * @param int $user
     * @param string $app
     * @param string $columns
     * @return null|AccessToken
     */
    public function findByUserAndApp(int $user, string $app, string $columns = "*"): ?AccessToken
    {
        $find = $this->find("user_id = :user_id AND app = :app", "user_id={$user}&app={$app}", $columns);
        return $find->fetch();
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
            $tokenDb = $this->findByUserAndApp($user->data()->user_id, $app);
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


    /**
     * generate
     * Gera o token JWT 
     * Reserved clains padrão: iat, exp (padrão de 30 dias), iss (site configurado)
     * Public clains padrão - data (user, level)
     * Forneceder: app, exp
     * @param int $user_id
     * @param array $data
     * 
     * @return string|null
     */
    public function generate(int $user_id, array $data): ?string
    {
        // Implementar: dependendo do tipo de app (criar modelo) será permitido uma ou mais chaves no banco
        $user = (new User())->findById($user_id);

        if (!$user) {
            // echo "Usuário inválido";
            return null;
        }

        $config = (new Config())->findById(1);

        $defaultExp = time() + (60 * 60 * 24 * 30); /* Padrao 30 dias */
        $exp = (isset($data["exp"]) && filter_var($data["exp"], FILTER_VALIDATE_INT) ? $data["exp"] : $defaultExp);


        $tokenData = array(
            "iat" => time(),
            "exp" => $exp,
            "iss" => $config->data()->conf_ocomon_site,
            "data" => array(
                "user" => $user_id,
                "level" => $user->data()->nivel,
                "app" => $data["app"] ?? ""
            )
        );

        $jwt = JWT::encode($tokenData, $user->data()->hash ?? $user->data()->password);
        return $jwt;
    }


}
