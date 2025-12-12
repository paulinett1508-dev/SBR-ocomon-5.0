<?php

/**
 * ####################
 * ###   VALIDATE   ###
 * ####################
 */

/**
 * @param string $email
 * @return bool
 */
function is_email(string $email): bool
{
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}


/**
 * ##################
 * ###   STRING   ###
 * ##################
 */

/**
 * @param string $text
 * @return string
 */
function str_textarea(string $text): string
{
    // $text = filter_var($text, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $text = filter_var($text, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    return "<p>" . preg_replace('#\R+#', '</p><p>', $text) . "</p>";
}

/**
 * @param string $msg
 * @param array $arrayEnv
 * @return string
 */
function transReplace($msg, $arrayEnv): string
{
    foreach ($arrayEnv as $id => $var) {
        // $msg = str_replace($id, $var, $msg);
        // $msg = str_replace($id, $var ?? '', $msg);
        $msg = str_replace($id, (string)$var, $msg);
    }
    return $msg;
}

/**
 * ###############
 * ###   URL   ###
 * ###############
 */

/**
 * @param string $path
 * @return string
 */
function url(string $path = null): string
{
    if (strpos($_SERVER['HTTP_HOST'], "localhost")) {
        if ($path) {
            return CONF_URL_TEST . "/" . ($path[0] == "/" ? mb_substr($path, 1) : $path);
        }
        return CONF_URL_TEST;
    }

    if ($path) {
        return CONF_URL_BASE . "/" . ($path[0] == "/" ? mb_substr($path, 1) : $path);
    }

    return CONF_URL_BASE;
}

/**
 * @return string
 */
function url_back(): string
{
    return ($_SERVER['HTTP_REFERER'] ?? url());
}

/**
 * @param string $url
 */
function redirect_(string $url): void
{
    header("HTTP/1.1 302 Redirect");
    if (filter_var($url, FILTER_VALIDATE_URL)) {
        header("Location: {$url}");
        exit;
    }

    if (filter_input(INPUT_GET, "route", FILTER_DEFAULT) != $url){
        $location = url($url);
        header("Location: {$location}");
        exit;
    }
}

/**
 * ################
 * ###   DATE   ###
 * ################
 */

/**
 * @param string|null $date
 * @param string $format
 * @return string
 */
function date_fmt(?string $date, string $format = "d/m/Y H\hi"): string
{
    $date = (empty($date) ? "now" : $date);
    return (new \DateTime($date))->format($format);
}

/**
 * ####################
 * ###   PASSWORD   ###
 * ####################
 */

/**
 * @param string $password
 * @param string $hash
 * @return bool
 */
function passwd_verify(string $password, string $hash): bool
{
    return password_verify($password, $hash);
}


/**
 * @param string $password
 * @return string
 */
function passwd(string $password): string
{
    if (!empty(password_get_info($password)['algo'])){
        return $password;
    }
    
    return password_hash($password, CONF_PASSWD_ALGO, CONF_PASSWD_OPTION);
}

// /**
//  * @param string $password
//  * @param string $hash
//  * @return bool
//  */
// function passwd_verify(string $password, string $hash): bool
// {
//     return password_verify($password, $hash);
// }

/**
 * @param string $hash
 * @return bool
 */
function passwd_rehash(string $hash): bool
{
    return password_needs_rehash($hash, CONF_PASSWD_ALGO, CONF_PASSWD_OPTION);
}


/*
 * Cross-platform function that gets the IP
 * address of the server.
 */
function getServerIp(){
    if (isset($_SERVER['SERVER_ADDR'])){
        return $_SERVER['SERVER_ADDR'];
    }
    
    if (isset($_SERVER['LOCAL_ADDR'])){
        return $_SERVER['LOCAL_ADDR'];
    }
    
    return false;
}



