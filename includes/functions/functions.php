<?php /*                        Copyright 2023 Flávio Ribeiro

This file is part of OCOMON.

OCOMON is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of the License, or
(at your option) any later version.

OCOMON is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with Foobar; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

ini_set('display_errors', 1);


use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;


function isPHPOlder(){
    if (version_compare(phpversion(), '7.4', '<' )){
        return true;
    }
    return false;
}



/**
 * alertRequiredModule
 * Checa se o módulo informado está carregado no PHP
 * Caso não esteja, exibe uma mensagem personalizada
 * Precisa ter uma entrada no arquivo de idiomas: PHP_MODULE_{$moduleName}_TO
 * @param string $moduleName
 * @return string|null
 */
function alertRequiredModule (string $moduleName): ?string
{
    $moduleName = strtoupper($moduleName);
    $moduleTo = '<hr>' .TRANS('PHP_MODULE_'.$moduleName.'_TO');
    if (!extension_loaded($moduleName)) {
        return message('danger', 'Ooops!', TRANS('REQUIRED_PHP_MODULE_NOT_FOUND') . ': ' . $moduleName . $moduleTo, '', '', true);
    }
    return null;
}

if (!function_exists('ereg')) {
    function ereg($pattern, $subject, &$matches = array())
    {
        return preg_match('/' . $pattern . '/', $subject, $matches);
    }
}

if (!function_exists('eregi')) {
    function eregi($pattern, $subject, &$matches = array())
    {
        return preg_match('/' . $pattern . '/i', $subject, $matches);
    }
}


/**
 * pass_hash
 * Retorna o hash do password informado
 * @param string $password
 * 
 * @return string
 */
function pass_hash(string $password) {
    if (!empty(password_get_info($password)['algo'])){
        return $password;
    }
    
    return password_hash($password, PASSWORD_DEFAULT, ["cost => 10"]);    
}

/**
 * olderThan
 * Retorna se uma data informada é mais antiga do que $years (quantidade de anos)
 *
 * @param string $date
 * @param int $years
 * 
 * @return bool
 */
function olderThan(string $date, int $years = 1): bool
{
    if (strtotime($date) < strtotime("-{$years} year")) {
        return true;
    }
    return false;
}


/**
 * Retorna o valor formatado no formato de moeda Brasileiro
 * @param string $price
 * @return string
 */
function priceScreen(?string $price): string
{
    return number_format((!empty($price) ? $price : 0), 2, ",", ".");
}

/**
 * Retorna o valor formatado no formato float para gravar no banco
 * @param string $price
 * @return string
 */
function priceDB(?string $price): string
{
    $price = (!empty($price) ? str_replace('.','', $price) : '');
    $price = (!empty($price) ? str_replace(',','.', $price) : '');
    
    // return number_format((!empty($price) ? $price : 0), 2, ".", ",");
    if (empty($price)) {
        return "0.00";
    }
    return $price;
}


/**
 * Adiciona o recurso multibyte na funçao uc_first
 */
if (!function_exists('mb_ucfirst')) {
    function mb_ucfirst(string $str, string $encoding = null): string
    {
        if ($encoding === null) {
            $encoding = mb_internal_encoding();
        }
        return mb_strtoupper(mb_substr($str, 0, 1, $encoding), $encoding) . mb_substr($str, 1, null, $encoding);
    }
}

/* caso a extensao mbstrings nao tenha sido instalada */
if (!function_exists('mb_strtolower')) {
    function mb_strtolower(string $str): string
    {
        return strtolower($str);
    }
}

/**
 * Retorna a string com apenas a primeira letra em caixa alta.
 */
function firstLetterUp(string $str): string
{
    return mb_ucfirst(mb_strtolower($str));
}

/**
 * Retorna apenas a primeira palavra da string.
 */
function firstWord(string $str): string
{
    return explode(" ", $str)[0];
}



function NVL($value)
{
    if ($value == '') {
        return '&nbsp';
    }
    return $value;
}


function valueSeparator($value, $sep)
{
    $notSep = "";
    if ($sep == ".") {
        $notSep = ",";
    }

    if ($sep == ",") {
        $notSep = ".";
    }

    if (strpos((string)$value, $notSep)) {
        $value = str_replace($notSep, $sep, $value);
    }
    if (!strpos((string)$value, $sep)) {
        $value .= $sep . "00";
    }

    return $value;
}

/**
 * @param array $data
 * @return array|null
 */
function filterArray(array $data): ?array
{
    $filter = [];
    foreach ($data as $key => $value) {
        $filter[$key] = (is_null($value) ? null : filter_var($value, FILTER_DEFAULT));
    }
    return $filter;
}


/**
 * getWorktimeSets
 * Retorna uma tela com as informações estruturadas do perfil de jornada
 * @param array $worktime
 * @return array
 */
function getWorktimeSets (array $worktime): array
{
    
    $empty = [];
    $empty['week'] = "";
    $empty['sat'] = "";
    $empty['sun'] = "";
    $empty['off'] = "";
    
    if (empty($worktime)) {
        return $empty;
    }

    $wt = $worktime;

    if ($wt['week_ini_time_hour'] == "00" && $wt['week_ini_time_minute'] == "00" && $wt['week_end_time_hour'] == "00" && $wt['week_end_time_minute'] == "00") {
        $empty['week'] = TRANS('OFF_TIME');
    } else {
        $empty['week'] = TRANS('TIME_FROM') . " " . $wt['week_ini_time_hour'] . ":" . $wt['week_ini_time_minute'] . " " . TRANS('TIME_TO') . " " . $wt['week_end_time_hour'] . ":" . $wt['week_end_time_minute'];
    }

    if ($wt['sat_ini_time_hour'] == "00" && $wt['sat_ini_time_minute'] == "00" && $wt['sat_end_time_hour'] == "00" && $wt['sat_end_time_minute'] == "00") {
        $empty['sat'] = TRANS('OFF_TIME');
    } else {
        $empty['sat'] = TRANS('TIME_FROM') . " " . $wt['sat_ini_time_hour'] . ":" . $wt['sat_ini_time_minute'] . " " . TRANS('TIME_TO') . " " . $wt['sat_end_time_hour'] . ":" . $wt['sat_end_time_minute'];
    }

    if ($wt['sun_ini_time_hour'] == "00" && $wt['sun_ini_time_minute'] == "00" && $wt['sun_end_time_hour'] == "00" && $wt['sun_end_time_minute'] == "00") {
        $empty['sun'] = TRANS('OFF_TIME');
    } else {
        $empty['sun'] = TRANS('TIME_FROM') . " " . $wt['sun_ini_time_hour'] . ":" . $wt['sun_ini_time_minute'] . " " . TRANS('TIME_TO') . " " . $wt['sun_end_time_hour'] . ":" . $wt['sun_end_time_minute'];
    }

    if ($wt['off_ini_time_hour'] == "00" && $wt['off_ini_time_minute'] == "00" && $wt['off_end_time_hour'] == "00" && $wt['off_end_time_minute'] == "00") {
        $empty['off'] = TRANS('OFF_TIME');
    } else {
        $empty['off'] = TRANS('TIME_FROM') . " " . $wt['off_ini_time_hour'] . ":" . $wt['off_ini_time_minute'] . " " . TRANS('TIME_TO') . " " . $wt['off_end_time_hour'] . ":" . $wt['off_end_time_minute'];
    }

    return $empty;

}


/**
 * Returns the last part of a given path string.
 *
 * @param string $path The path string to extract the last part from.
 *
 * @return string The last part of the path string.
 */
function getLastPartOfPath(string $path): string
{
    $parts1 = explode('/', $path);
    $parts2 = explode('\\', end($parts1));

    return end($parts2);
}


/**
 * Realiza substituição dos valores do $index de acordo com o definido no arquivo de idioma utilizado
 * @param string $index: índice do array no arquivo de idioma
 * @param string $suggest: valor que deverá ser criado, caso nao exista, no arquivo de idioma
 * @param int $javascript: faz o escape de quando nao encontra o índice informado 
 *              (necessário para quando esse retorno é passado em um alert do javascript)
 */
function TRANS_OLD($index, $suggest = '', $javascript = 0)
{
    /* Para utilizar quando debugando a interface */
    $spanOpening = "<span class='bg-warning text-danger'>";
    if ($javascript)
        $spanOpening = "<span class=\"bg-warning text-danger\">";
    $spanClosing = "</span>";
    $spanOpening = "";
    $spanClosing = "";
    
    if (!isset($_SESSION['s_language'])) {
        $_SESSION['s_language'] = "pt_BR.php";
    }

    if (is_file(__DIR__ . "/" . "../languages/" . $_SESSION['s_language'])) {
        include __DIR__ . "/" . "../languages/" . $_SESSION['s_language'];
    
        if (!isset($TRANS[$index])) {
            if ($javascript) {
                return '<font color=red>$TRANS[\'' . $index . '\']="</font>' . $suggest . '<font color=red>";</font>';
            }
            return '<font color=red>$TRANS[' . $index . ']="</font>' . $suggest . '<font color=red>";</font>';
        } 
        return $spanOpening . $TRANS[$index] . $spanClosing;
    
    }
    return "No translation file found";
}


/**
 * Realiza substituição dos valores do $index de acordo com o definido no arquivo de idioma utilizado
 *
 * @param string $index índice do array no arquivo de idioma
 * @param string $suggest valor que deverá ser criado, caso nao exista, no arquivo de idioma
 * @param int $javascript faz o escape de quando nao encontra o índice informado (necessário para quando esse retorno é passado em um alert do javascript)
 *
 * @return string
 */
function TRANS(string $index, string $suggest = '', int $javascript = 0): string
{
    static $TRANS = null;
    if ($TRANS === null) {
        if (!isset($_SESSION['s_language'])) {
            $_SESSION['s_language'] = "pt_BR.php";
        }

        $_SESSION['s_language'] = noHtml(getLastPartOfPath($_SESSION['s_language']));

        if (is_file(__DIR__ . "/" . "../languages/" . $_SESSION['s_language'])) {
            include __DIR__ . "/" . "../languages/" . $_SESSION['s_language'];
        } else {
            return "No translation file found";
        }
    }

    if (!isset($TRANS[$index])) {
        if ($javascript) {
            return '<font color=red>$TRANS[\'' . $index . '\']="</font>' . $suggest . '<font color=red>";</font>';
        }
        return '<font color=red>$TRANS[' . $index . ']="</font>' . $suggest . '<font color=red>";</font>';
    }
    return $TRANS[$index];
}







/**
 * @param string $text
 * @return string
 */
function textarea_nl(string $text): string
{
    $text = filter_var($text, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    /* $arrayReplace = ["&#10;", "&#10;&#10;", "&#10;&#10;&#10;", "&#10;&#10;&#10;&#10;", "&#10;&#10;&#10;&#10;&#10;"];
    return "<p>" . str_replace($arrayReplace, "</p><p>", $text) . "</p>"; */
    return "<p>" . preg_replace('#\R+#', '</p><p>', $text) . "</p>";
}

function removeEmptyLines($string)
{
    // return preg_replace('/^\n+|^[\t\s]*\n+/m', '', $string);
    return rtrim(preg_replace("/(\R){2,}/", "$1", $string));
}


function dump($variavel, $info = "", $cor = 'magenta')
{
    if (trim($info) != "") {
        echo "<br><font color='" . $cor . "'>" . $info . "</font>";
    }

    if (is_array($variavel) || is_object($variavel)) {
        echo "<pre>";
        print_r($variavel);
        echo "</pre>";
        return;
    }
    
    echo "<pre>";
    echo $variavel;
    echo "</pre>";
    return; 
}

function normaliza($str)
{
    return toHtml($str);
}

function reIndexArray(&$array)
{
    $tmpArray = array();

    if (is_array($array)) {
        $array = array_unique($array);
        foreach ($array as $value) {
            if (!empty($value))
                $tmpArray[] = $value;
        }
        for ($i = 0; $i <= count($array); $i++) {
            array_pop($array);
        }
    }
    $array = $tmpArray;
}


/** Boostrap and fontAwesome must be included
 * @param string $type - primary|secondary|success|danger|info|warning|light|dark
 * @param string $strong - The short message to be strong bold
 * @param string $message - The message itself
 * @param string $id - the id to be treated in jquery
 * @param string $returnLink - a href link to another page
 * @param bool $fixed - if the message cant be closed
 * @param string $iconFa - specific fontAwesome Class Names
 */
function message($type, $strong, $message, $elementID, $returnLink = '', $fixed = '', $iconFa = ''){

    $fixed = (empty($fixed) ? false : $fixed);

    $icon = [];
    $icon['success'] = "fas fa-check-circle"; 
    $icon['info'] = "fas fa-info-circle"; 
    $icon['warning'] = "fas fa-exclamation-circle";
    $icon['danger'] = "fas fa-exclamation-triangle"; 

    if (!empty($iconFa)) {
        $icon[$type] = $iconFa;
    }

    $goTo = "";
    if (!empty($returnLink)){
        $goTo = "<hr><a href='{$returnLink}' class='alert-link'>Voltar</a>";
    }
    if (!$fixed)
    /* style=' z-index:1030 !important;' */
        return "
        <div class='d-flex justify-content-center '>
            <div class='d-flex justify-content-center  my-3' style=' max-width: 100%; position: fixed; top: 1%; z-index:1030 !important;'>
                <div class='alert alert-{$type} alert-dismissible fade show w-100' role='alert' id='{$elementID}'  onClick=\"this.style.display='none'\" >
                    <i class='" . $icon[$type] . "'></i>
                    <strong>{$strong}</strong> {$message} {$goTo}
                    <button type='button' class='close' data-dismiss='alert' aria-label='Close'>
                        <span aria-hidden='true'>&times;</span>
                    </button>
                </div>
            </div>
            </div>
        ";
        /* style=' z-index:1030 !important;' */
    return "
        <div class='d-flex justify-content-center' style=' z-index:2 !important;'>
            <div class='alert alert-{$type} fade show w-100'  role='alert' id='{$elementID}' '>
                <i class='" . $icon[$type] . "'></i>
                <strong>{$strong}</strong> {$message} {$goTo}
                
            </div>
        </div>
    ";
}


function putComma($vetor)
{
    $chamados = "";
    if (is_array($vetor)) {

        if (count($vetor) >= 1) {
            for ($i = 0; $i < count($vetor); $i++) {
                $chamados .= "$vetor[$i],";
            }
            if (strlen((string)$chamados) > 0) {
                $chamados = substr($chamados, 0, -1);
            }
        } 
        return $chamados;
    } 
    return $vetor;
}


/* utilizado no módulo de inventário - retorna a diferença em dias cheios */
function date_diff_dias($data1, $data2)
{
    if (empty($data1) || empty($data2)) {
        return "";
    } 

    $seconds = strtotime($data2) - strtotime($data1);
    $days = intval($seconds / 86400);
    $seconds -= $days * 86400;
    $hours = intval($seconds / 3600);
    $seconds -= $hours * 3600;
    $minutes = intval($seconds / 60);
    $seconds -= $minutes * 60;

    $value = $days;
    return $value;
}



/**
 * Utilizar sempre para gravar no banco
 * @param string|null $date
 * @param string $format
 * @param int|null $nullable (se for 1 então o retorno será vazio caso a data esteja vazia - 
 *                  Se for 0 então o retorno será a data atual caso a data esteja vazia)
 * @return string
 */
function dateDB(?string $date, ?int $nullable = 0): string
{
    $date = (empty($date) ? '' : $date);

    if ($nullable == 0) {
        $date = (empty($date) ? "now" : $date);
    }
    
    if (empty($date)) {
        return '';
    }

    if (strpos((string)$date, '/')) {
        $date = str_replace('/', '-', $date);
    }
    return (new DateTime($date))->format("Y-m-d H:i:s");
}

/**
 * Formata de acordo com o definido no menu de administração
 * @param string|null $date
 * @param int|null $hideTime
 * @return string
 */
function dateScreen(?string $date, ?int $hideTime = 0, ?string $format = null ): string
{
 
    if (empty($date))
        return '';

    if ($format) {
        return (new DateTime($date))->format($format);
    }

    if (isset($_SESSION['s_date_format']) && !empty($_SESSION['s_date_format'])) {
        $format = $_SESSION['s_date_format'];
    } else {
        $format = 'd/m/Y H:i:s';
    }
    

    if ($hideTime != 0) {
        $dateParts = explode(' ', (new DateTime($date))->format($format));
        return $dateParts[0];
    }
    
    return (new DateTime($date))->format($format);
}

/**
 * Apenas repliquei a função dateScreen para não precisar substituir em todos os arquivos que utilizam a formatDate
 */
function formatDate(?string $date, $hideTime = 0): string
{
    $format = 'd/m/Y H:i:s';
    if (isset($_SESSION['s_date_format']) && !empty($_SESSION['s_date_format'])) {
        $format = $_SESSION['s_date_format'];
    }
    if (empty($date))
    return '';

    if ($hideTime != 0 && $hideTime != " ") {
        $dateParts = explode(' ', (new DateTime($date))->format($format));
        return $dateParts[0];
    }
    
    return (new DateTime($date))->format($format);
}


function new_utf8_encode($string): string
{
    return mb_convert_encoding($string, 'UTF-8', 'ISO-8859-1');
}

function new_utf8_decode($string): string
{
    return mb_convert_encoding($string, 'ISO-8859-1', 'UTF-8');
}


/**
 * @param string $string
 * @param string $prefix
 * @return string
 */
function str_slug(string $string, ?string $prefix = null, ?bool $addRandomSufix = false): string
{
    $randomSufix = "";
    if ($addRandomSufix) {
        $randomSufix = "_" .randomChars(5);
    }
   
    
    $string = filter_var(mb_strtolower($string), FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $formats = 'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜüÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûýýþÿRr"!@#$%&*()_-+={[}]/?;:.,\\\'<>°ºª';
    $replace = 'aaaaaaaceeeeiiiidnoooooouuuuuybsaaaaaaaceeeeiiiidnoooooouuuyybyRr                                 ';

    $slug = str_replace(["-----", "----", "---", "--"], "-",
        str_replace(" ", "_",
            // trim(strtr(utf8_decode($string), utf8_decode($formats), $replace))
            trim(strtr(new_utf8_decode($string), new_utf8_decode($formats), $replace))
        )
    );

    $slug = $slug . $randomSufix;

    if ($prefix) {
        return $prefix.$slug;
    }
    return $slug;
}

/**
 * Generate a slug from a given string by removing special characters and spaces, 
 * converting accented characters to their ASCII equivalents, and converting to 
 * lowercase. An optional prefix can be added to the slug, and an optional random 
 * suffix of length 5 can be added.
 *
 * @param string $string The string to generate the slug from.
 * @param string|null $prefix An optional prefix to add to the slug.
 * @param bool|null $addRandomSufix Whether to add a random suffix to the slug.
 * @return string The generated slug.
 */
function generate_slug(string $string, ?string $prefix = null, ?bool $addRandomSufix = false): string
{

    $randomSufix = "";
    if ($addRandomSufix) {
        $randomSufix = "_" .randomChars(5);
    }


    // Remove espaços antes e depois
    $string = trim($string);
    // Converte caracteres acentuados para suas versões sem acento
    $slug = iconv('UTF-8', 'ASCII//TRANSLIT', $string);

    // Substitui espaços por hifens
    $slug = str_replace(' ', '-', $slug);

    // Remove caracteres especiais e de pontuação
    $slug = preg_replace('/[^A-Za-z0-9\-]/', '', $slug);

    // Converte para minúsculas
    $slug = strtolower($slug);

    // Remove hifens duplicados
    $slug = preg_replace('/-+/', '-', $slug);

    $slug = $slug . $randomSufix;

    if ($prefix) {
        return $prefix.$slug;
    }
    return $slug;
}





function noHtml($string)
{
    $newline = ["\n", "\r\n", "\r"];
    $flagsNewlines = ["linuxbreak", "windowsbreak", "macbreak"];
    $string = str_replace($newline, $flagsNewlines, (string)$string);
    
    $string = preg_replace('/[^\PCc^\PCn^\PCs]/u', '', (string)$string); /* Remoção de caracteres invisíveis */

    $string = str_replace($flagsNewlines, $newline, (string)$string);

    return trim(filter_var($string, FILTER_DEFAULT));
}


function noHtml2($string)
{
    $newline = ["\n", "\r\n", "\r"];
    $flagsNewlines = ["linuxbreak", "windowsbreak", "macbreak"];
    $string = strtr($string, array_combine($newline, $flagsNewlines));

    $string = preg_replace('/[^\PCc^\PCn^\PCs]/u', '', $string); /* Remoção de caracteres invisíveis */

    $string = strtr($string, array_combine($flagsNewlines, $newline));

    return trim(filter_var($string, FILTER_DEFAULT));
}



function toHtml($string)
{
    $tags = array(
        htmlentities("<script>"), 
        htmlentities("</script>"),
        "<script>", 
        "</script>"
    );

    $string = str_replace($tags, "[script]", $string);
    
    $transTbl = get_html_translation_table(HTML_ENTITIES);
    $transTbl = array_flip($transTbl);
    return strtr($string, $transTbl);
}



function renderHtml($string)
{
    // Remove all HTML tags and attributes
    $string = strip_tags($string);

    // Convert special characters to HTML entities
    $string = htmlspecialchars($string, ENT_QUOTES | ENT_HTML5, 'UTF-8');

    return $string;
}








/**
 * hasFormatBar
 * Retorna se a barra de formatação de textos está habilitada para o $target informado
 * Targets permitidos: %oco% | %mural%
 * @param array $config
 * @param string $target
 * 
 * @return bool
 */
function hasFormatBar(array $config, string $target): bool
{
    $targets = ["%oco%", "%mural%"];
    if (!in_array($target, $targets)) {
        return false;
    }
    
    if (strpos($config['conf_formatBar'], $target)) {
        return true;
    }
    return false;
}


function isIn($pattern, $values)
{
    if (strpos((string)$values, ",")) {
        $valuesArray = explode(",", $values);

        for ($i = 0; $i < count($valuesArray); $i++) {
            if ($valuesArray[$i] == (int) $pattern) {
                return true;
            }
        }
    } 
    
    if ($values == (int) $pattern) {
        return true;
    }
    return false;
}

function sepComma($value, $array)
{
    $array = $value;
    
    if (strpos((string)$value, ",")) {
        $array = explode(",", $value);
    }

    return (array)$array;
}


/**
 * randomChars
 * Retorna uma string aleatória de acordo com o tamanho informado
 * @param int|null $iterations
 * 
 * @return string
 */
function randomChars (?int $iterations = 10): string
{
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen((string)$characters);
    $randomString = '';
    for ($i = 0; $i < $iterations; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}


function random(): string
{
    $rand = "";
    for ($i = 0; $i < 10; $i++) {
        $rand .= mt_rand(1, 300);
    }
    return ($rand);
}

function random64() {
    return base64_encode(random_bytes(20));
}

function transbool($bool)
{
    if ($bool == 0) {
        return TRANS('NOT');
    }
    if ($bool == 1) {
        return TRANS('YES');
    }
    return $bool;
}

function transvars($msg, $arrayEnv)
{
    /* original code */
    // foreach ($arrayEnv as $id => $var) {
    //     $msg = str_replace($id, $var, $msg);
    // }

    foreach ($arrayEnv as $id => $var) {

        if (!is_array($var)) {
            $msg = str_replace($id, (string)$var, $msg);
        } else {
            
            foreach ($var as $k => &$v) {
                // echo $id . " - " . $k." - ".$v."<br>";
                $msg = str_replace($id, (string)$v, $msg);
            }
        }
    }

    return $msg;
}


/**
 * Faz o envio dos e-mails nas opções das ocorrências - Essa função será removida em breve
 */
function mail_send($mailConf, $to, $cc, $subject, $body, $replyto, $envVars)
{
    if (!$mailConf['mail_send']) {
        return true;
    }    

    if (is_file("./.root_dir")) {
        if (!class_exists(PHPMailer::class)) {
            require __DIR__ . "/../../api/ocomon_api/vendor/phpmailer/phpmailer/src/Exception.php";
            require __DIR__ . "/../../api/ocomon_api/vendor/phpmailer/phpmailer/src/PHPMailer.php";
            require __DIR__ . "/../../api/ocomon_api/vendor/phpmailer/phpmailer/src/SMTP.php";
        }
    } else {
        if (!class_exists(PHPMailer::class)) {
            require __DIR__ . "/../../api/ocomon_api/vendor/phpmailer/phpmailer/src/Exception.php";
            require __DIR__ . "/../../api/ocomon_api/vendor/phpmailer/phpmailer/src/PHPMailer.php";
            require __DIR__ . "/../../api/ocomon_api/vendor/phpmailer/phpmailer/src/SMTP.php";
        }
    }
    
    $mail = new PHPMailer;
    //Tell PHPMailer to use SMTP
    //$mail->isSMTP();
    if ($mailConf['mail_issmtp']) {
        $mail->IsSMTP();
    }

    //Enable SMTP debugging
    // 0 = off (for production use)
    // 1 = client messages
    // 2 = client and server messages
    $mail->SMTPDebug = 0;
    //Set the hostname of the mail server
    $mail->Host = $mailConf['mail_host']; // specify main and backup server
    // use
    // $mail->Host = gethostbyname('smtp.gmail.com');
    // if your network does not support SMTP over IPv6
    $mail->Port = $mailConf['mail_port'];
    //Set the encryption system to use - ssl (deprecated) or tls
    $mail->SMTPSecure = $mailConf['mail_secure'];
    //Whether to use SMTP authentication
    //$mail->SMTPAuth = true;
    $mail->SMTPAuth = $mailConf['mail_isauth']; // turn on SMTP authentication

    $mail->CharSet = 'UTF-8';
    // $mail->setLanguage = 'br';
    $mail->Encoding = 'base64';

    //Username to use for SMTP authentication - use full email address for gmail
    $mail->Username = $mailConf['mail_user']; // SMTP username
    //Password to use for SMTP authentication
    $mail->Password = $mailConf['mail_pass']; // SMTP password
    //Set who the message is to be sent from
    $mail->setFrom($mailConf['mail_from'], $mailConf['mail_from_name']);
    //Set an alternative reply-to address
    //Set who the message is to be sent to

    $mail->AddReplyTo($replyto, $mailConf['mail_from_name']);

    $mail->msgHTML(nl2br(transvars($body, $envVars)));

    //Replace the plain text body with one created manually
    $mail->AltBody = nl2br(transvars($body, $envVars));

    $recipients = 1;
    $sepTo = explode(",", $to);
    if (is_array($sepTo)) {
        $recipients = count($sepTo);
    }

    for ($i = 0; $i < $recipients; $i++) {
        $mail->AddAddress(trim($sepTo[$i]));
    }

    if (isset($cc) && $cc != "") {

        $sepCC = explode(",", $cc);

        $copies = 1;
        if (is_array($sepCC)) {
            $copies = count($sepCC);
        }

        for ($i = 0; $i < $copies; $i++) {
            $mail->AddCC(trim($sepCC[$i]));
        }
    }

    $mail->Subject = transvars($subject, $envVars);

    if (!$mail->Send()) {
        echo "A mensagem não pôde ser enviada. <p>";
        echo "Mailer Error: " . $mail->ErrorInfo;
        // exit;
        return false;
    }
    return true;
}



/**
 * arraySortByColumn
 * Ordena um array a partir da coluna informada
 *
 * @param array $array
 * @param string $column
 * @param int $dir
 * 
 * @return array
 */
function arraySortByColumn (array $array, string $column, int $dir = SORT_ASC): array
{
    $sortArray = array();
    foreach ($array as $key => $row) {
        $sortArray[$key] = strtolower($row[$column]);
    }
    array_multisort($sortArray, $dir, SORT_STRING, $array);
    return $array;
}


/**
 * array_key_exists_recursive
 *
 * @param mixed $key
 * @param array $array
 * 
 * @return bool
 */
function array_key_exists_recursive ($key, array $array): bool
{
    if (array_key_exists($key, $array)) {
        return true;
    }
    foreach($array as $k => $value) {
        if (is_array($value) && array_key_exists_recursive($key, $value)) {
            return true;
        }
    }
    return false;      
}



/**
 * strToTags
 * Recebe uma string separada por vírgula
 * Retorna cada item como um badge
 * @param string|null $tags
 * @param int $breakLine : quebra de linha a partir da quantidade $breakLine de tags
 * @param string|null $class : classe de cor para ser aplicada nos badges: padrão 'info'
 * @param string|null $eventClass : classe referencia para eventos javascript
 * @param string|null $faIcon : classe font awesome para o final do label
 * @return void
 */
function strToTags(?string $tags = null, int $breakLine = 0, ?string $class = 'info', ?string $eventClass = 'input-tag-link', ?string $faIcon = ""): string
{
    if (!$tags) {
        return '';
    }

    $icon = "";
    if (isset($faIcon) && !empty($faIcon)) {
        $icon = '&nbsp;<i class="' . $faIcon . '"></i>';
    }
    
    $badge = "";
    $tags = filter_var($tags, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    
    $arrayTags = explode(',',(string)$tags);
    sort($arrayTags, SORT_LOCALE_STRING);

    $i = 0;
    foreach ($arrayTags as $tag) {
        $badge .= '<span class="badge badge-' . $class . ' p-2 mr-1 my-1 ' . $eventClass . '" data-tag-name="' . urlencode($tag) . '" data-tag-name-raw="' . $tag . '">' . $tag . $icon . '</span>' 
            . ($breakLine > 0 && ($i % $breakLine) == 0 ? '<br />' : '');
        $i++;
    }

    return $badge;
}


/**
 * tagsRemoved
 * Compara duas strings separadas por vírgula e retorna os itens removidos da primeira string, se for o caso
 * @param string|null $tagsBase
 * @param string|null $tagsCurrent
 * 
 * @return string
 */
function tagsRemoved (?string $tagsBase, ?string $tagsCurrent): string
{
    $arrayBase = explode(',', (string)$tagsBase);
    $arrayCurrent = explode(',', (string)$tagsCurrent);
    $removed = array_diff($arrayBase, $arrayCurrent);

    $joinRemoved = implode(',', $removed);
    return $joinRemoved;
}

/**
 * tagsAdded
 * Compara duas strings separadas por vírgula e retorna os itens adicionados com relacao a primeira string, se for o caso
 * @param string|null $tagsBase
 * @param string|null $tagsCurrent
 * 
 * @return string
 */
function tagsAdded (?string $tagsBase, ?string $tagsCurrent): string
{
    $arrayBase = explode(',', (string)$tagsBase);
    $arrayCurrent = explode(',', (string)$tagsCurrent);
    $added = array_diff($arrayCurrent, $arrayBase);

    $joinAdded = implode(',', $added);
    return $joinAdded;
}




/**
 * subtract_array
 * Faz a mesma operação do array_diff mas consegue distinguir os valores duplicados
 * Considera cada valor duplicado como independente
 *
 * @param array $array1
 * @param array $array2
 * 
 * @return array
 */
function subtract_array(array $array1, array $array2): array
{
    foreach ($array2 as $item) {
        $key = array_search($item, $array1);
        if ( $key !== false ) {
            unset($array1[$key]);
        }
    }
    return array_values($array1);
}

/**
 * valuesRemoved
 * Compara dois arrays e retorna os valores removidos do primeiro array, se for o caso
 * @param array $valuesBase
 * @param array $valuesCurrent
 * 
 * @return array
 */
function valuesRemoved (array $valuesBase, array $valuesCurrent): array
{
    // $removed = array_diff($valuesBase, $valuesCurrent);
    /* Considera os valores duplicados de forma independente */
    $removed = subtract_array($valuesBase, $valuesCurrent);
    
    return $removed;
}

/**
 * valuesAdded
 * Compara dois array e retorna os valores adicionados, se for o caso
 * @param array $valuesBase
 * @param array $valuesCurrent
 * 
 * @return array
 */
function valuesAdded (array $valuesBase, array $valuesCurrent): array
{
    // $added = array_diff($valuesCurrent, $valuesBase);
    /* Considera os valores duplicados de forma independente */
    $added = subtract_array($valuesCurrent, $valuesBase);
    
    return $added;
}





/**
 * keyPairsToHtmlAttrs
 * Recebe uma string com pareamento do tipo chave=valor
 *
 * @param string|null $keyPairs
 * 
 * @return string
 */
function keyPairsToHtmlAttrs (?string $keyPairs = null): string
{
    if (!$keyPairs) {
        return '';
    }
    $parseAttributes = "";
    $inlineAttributes = "";
    $arrayAttributes = [];

    $keyPairs = trim(noHtml($keyPairs));
    $keyPairs = str_replace(" ", "", $keyPairs);

    $parseAttributes = str_replace(",", "&", $keyPairs);

    parse_str($parseAttributes, $arrayAttributes);
							
    foreach ($arrayAttributes as $attr => $value) {
        if (strlen((string)$inlineAttributes)) $inlineAttributes .= " ";
        $inlineAttributes .= $attr . "=" . '"' . $value . '"';
    }

    return $inlineAttributes;
}


function isImage($type)
{
    if (eregi("^image\/(pjpeg|jpeg|png|gif|x-ms-bmp)$", $type)) {
        return true;
    }
    return false;
}

function noSpace($word)
{
    $newWord = trim(str_replace(" ", "_", $word));
    return $newWord;
}

/**
 * Realiza a validação dos tipos de arquivos permitidos de acordo com o mimetype e tamanho
 */
function upload($imgFile, $config, $fileTypes = "%%IMG%", $fileAttributes = "")
{

    include __DIR__ . "/" . "../languages/" . $_SESSION['s_language'];

    if (empty($fileAttributes))
        $fileAttributes = $_FILES[$imgFile];
    $arquivo = ($_FILES && isset($_FILES[$imgFile]) ? $fileAttributes : false);

    $maxFileSize = ($config["conf_upld_size"] / 1024) . "kbytes";
    $saida = "OK";

    if ($arquivo) {
        
        if ($arquivo['error'] == 2) {
            return TRANS('FILE_TOO_HEAVY') . ". " . TRANS('LIMIT') . ": " . $maxFileSize;
        }
        
        $erro = array();
        $mime = array();
        $type = explode("%", $fileTypes);
        reIndexArray($type);

        /* A serem testados de acordo com o permitido na configuração geral */
        $mime['PDF'] = "application\/pdf";
        $mime['TXT'] = "text\/plain";
        $mime['RTF'] = "application\/rtf";
        $mime['HTML'] = "text\/html";
        $mime['IMG'] = "image\/(pjpeg|jpeg|png|gif|x-ms-bmp)";
        $mime['ODF'] = "application\/vnd.oasis.opendocument.(text|spreadsheet|presentation|graphics)";
        $mime['OOO'] = "application\/vnd.sun.xml.(writer|calc|draw|impress)";
        $mime['MSO'] = "application\/(msword|vnd.ms-excel|vnd.ms-powerpoint)";
        $mime['NMSO'] = "application\/vnd.openxmlformats-officedocument.(wordprocessingml.document|spreadsheetml.sheet|presentationml.presentation|presentationml.slideshow)";

        $typeOK = false;
        $types = "";
        for ($i = 0; $i < count($type); $i++) {
            if (strlen((string)$types) > 0) {
                $types .= ", ";
            }
            if ($type[$i] == "IMG") {
                $types .= "jpeg, png, gif, bmp";
            } else
            if ($type[$i] == "PDF") {
                $types .= "pdf";
            } else
            if ($type[$i] == "TXT") {
                $types .= "txt";
            } else
            if ($type[$i] == "RTF") {
                $types .= "rtf";
            } else
            if ($type[$i] == "HTML") {
                $types .= "html";
            } else
            if ($type[$i] == "ODF") {
                $types .= "odt, ods, odp, odg";
            } else
            if ($type[$i] == "OOO") {
                $types .= "sxw, sxc, sxi, sxd";
            } else
            if ($type[$i] == "MSO") {
                $types .= "doc, xls, ppt";
            } else
            if ($type[$i] == "NMSO") {
                $types .= "docx, xlsx, pptx, ppsx";
            }

            if (preg_match("/^" . $mime[$type[$i]] . "$/i", $arquivo["type"])) {
                $typeOK = true;
            }
        }

        if (!$typeOK) {
            $erro[] = TRANS('UPLOAD_TYPE_NOT_ALLOWED') . $types;
        } else {
            // Verifica tamanho do arquivo
            if ($arquivo["size"] >= $config["conf_upld_size"]) {
                $erro[] = TRANS('FILE_TOO_HEAVY') . ". " . TRANS('LIMIT') . ": " . $maxFileSize;
            } else
            
            if (preg_match("/^image\/(pjpeg|jpeg|png|gif|bmp)$/i", $arquivo["type"])) {
                // Se for imagem
                $tamanhos = getimagesize($arquivo["tmp_name"]);
                // Verifica largura
                if ($tamanhos[0] > $config["conf_upld_width"]) {
                    $erro[] = TRANS('WIDTH_TOO_LARGE') . " " . $config["conf_upld_width"] . " pixels";
                }
                // Verifica altura
                if ($tamanhos[1] > $config["conf_upld_height"]) {
                    $erro[] = TRANS('HEIGHT_TOO_LARGE') . " " . $config["conf_upld_height"] . " pixels";
                }
            }
        }
        if (sizeof($erro)) {
            $saida = "";
            foreach ($erro as $err) {
                $saida .= "<hr>" . $err;
            }
        }
        if ($arquivo && !sizeof($erro)) {
            $saida = "OK";
        }
    } else {
        $saida = "File error!";
    }
    return $saida;
}


//Destaca as entradas '$string' em um texto '$texto' passado
function destaca($string, $texto)
{
    $string .= "|" . noHtml($string) . "|" . toHtml($string);

    $pattern = explode("|", $string);
    $pattern = array_unique($pattern);
    $destaque = array();

    reIndexArray($pattern);

    $texto2 = toHtml(strtolower($texto));

    for ($i = 0; $i < count($pattern); $i++) {
        $destaque = "<mark><span class='text-dark bg-warning p-1'>" . $pattern[$i] . "</span></mark>";
        $texto2 = str_replace(strtolower($pattern[$i]), strtolower($destaque), $texto2);
    }
    return $texto2;
}




/**
 * isValidDate
 * Verifica se a data é válida de acordo com o formato informado
 *
 * @param string $date
 * @param string $format
 * 
 * @return bool
 */
function isValidDate(string $date, string $format = 'd/m/Y'): bool
{
    DateTime::createFromFormat($format, $date);
    $errors = DateTime::getLastErrors();

    if (is_array($errors)) {
        return $errors['warning_count'] === 0 && $errors['error_count'] === 0;
    }

    return true;
}


/**
 * daysFromDate
 * Retorna a quantidade de dias passados a partir de uma data informada (data no passado). 
 * @param string $baseDate
 * 
 * @return int
 */
function daysFromDate (string $baseDate, ?string $format = "Y-m-d H:i:s"): int
{
    if (!isValidDate($baseDate, $format)) {
        echo "wrong date format! Expected: " . $format;
        return 0;
    }

    $dateA = new DateTime($baseDate);
    $now = new DateTime();

    if ($dateA > $now) {
        return 0;
    }

    return $dateA->diff($now)->days;
}




/**
 * isWeekendDay
 * Retorna se a data informada é um sábado ou um Domingo
 *
 * @param string $date
 * 
 * @return bool
 */
function isWeekendDay (string $date) : bool
{
    $day = date("l", strtotime($date));
    if ($day == "Saturday" || $day == "Sunday") {
        return true;
    }
    return false;
}


/**
 * weekendsInPeriod
 * Retorna o número de Sábados e Domingos de um dado período
 *
 * @param string $pastDate
 * @param string|null $referenceDate
 * 
 * @return int
 */
function weekendDaysInPeriod (string $pastDate, ?string $referenceDate = null): int
{
    if (!$referenceDate) {
        $referenceDate = date('Y-m-d H:i:s');
    }
    
    $countWeekendDay = 0;

    $period = new DatePeriod(
        new DateTime($pastDate),
        new DateInterval('P1D'),
        new DateTime($referenceDate)
    );
    foreach ($period as $key => $value) {
        if ($value->format('N') >= 6) {
            $countWeekendDay++;
        } 
    }

    /* Se a data final do período for fim de semana o loop acima não contabiliza. Então checo a seguir: */
    $countWeekendDay = (isWeekendDay($referenceDate) ? $countWeekendDay + 1 : $countWeekendDay);
    
    return $countWeekendDay;
}





/**
 * addDaysToDate
 * Retorna uma nova data com a soma do total de dias informados
 *
 * @param string $baseDate
 * @param int $daysToAdd
 * @param bool|null $onlyBusinesDay: nesse caso considera apenas os dias entre segunda e sexta
 * 
 * @return string
 */
function addDaysToDate(string $baseDate, int $daysToAdd, ?bool $onlyBusinesDay = null): string
{
    if (!isValidDate($baseDate, 'Y-m-d H:i:s')) {
        return 'Invalid date format';
    }

    if ($daysToAdd == 0) {
        return $baseDate;
    }

    $objBaseDate = new DateTime($baseDate);
    
    if ($onlyBusinesDay) {
        $i = 0;
        while ($i < $daysToAdd) {
            $newDate = date_add($objBaseDate, new DateInterval('P1D'));
            $testDate = date_format($newDate, "Y-m-d H:i:s");
            if (!isWeekendDay($testDate)) {
                $i++;
            }
        }
        
        $newDate = date_format($newDate, "Y-m-d H:i:s");
        return $newDate;

    } else {
        $newDate = date_add($objBaseDate, new DateInterval('P'.$daysToAdd.'D'));
        $newDate = date_format($newDate, "Y-m-d H:i:s");
    }
    
    return $newDate;
}


/**
 * subDaysFromDate
 * Retorna uma nova data reduzidos o total de dias informados
 *
 * @param string $baseDate
 * @param int $daysToSub
 * @param bool|null $onlyBusinesDay: nesse caso considera apenas os dias entre segunda e sexta
 * 
 * @return string
 */
function subDaysFromDate(string $baseDate, int $daysToSub, ?bool $onlyBusinesDay = null): string
{
    if (!isValidDate($baseDate, 'Y-m-d H:i:s')) {
        return 'Invalid date format';
    }

    if ($daysToSub == 0) {
        return $baseDate;
    }
    $objBaseDate = new DateTime($baseDate);
    $testDate = "";
    
    if ($onlyBusinesDay) {
        $i = 0;
        while ($i < $daysToSub) {
            
            $lastDay = $i == ($daysToSub - 1);
            $dayBeforeLastDay = $i == ($daysToSub - 2);
            
            $newDate = date_sub($objBaseDate, new DateInterval('P1D'));
            $testDate = date_format($newDate, "Y-m-d H:i:s");
            /** Se for final de semana a contagem é desconsiderada - a menos que seja nos dois ultimos dias do loop pois o 
             * chamado pode ter sido encerrado em fim de semana.
             * O objetivo da função é obter a data de corte para saber que chamados podem ser avaliados de forma automática
             * ou pelo solicitante
             */
            if (!isWeekendDay($testDate) || $lastDay || $dayBeforeLastDay) {
                $i++;
            }
            // if (!isWeekendDay($testDate)) {
            //     $i++;
            // }
        }
        
        $newDate = date_format($newDate, "Y-m-d H:i:s");
        return $newDate;

    } else {
        $newDate = date_sub($objBaseDate, new DateInterval('P'.$daysToSub.'D'));
        $newDate = date_format($newDate, "Y-m-d H:i:s");
    }
    
    return $newDate;
}

/**
 * Realiza a validação por expressão regular
 * @param string $CAMPO: label/rótulo do campo - será utilizado para indicar ao usuário qual é o campo
 * @param mix $VALOR: O valor a ser validado
 * @param string $TIPO: O tipo para o qual o valor será verificado - ver a listagem possível
 * @param string $ERR: Variável que recebe a Mensagem de retorno por referência 
 * @param string $MSG: Mensagem de retorno personalizada
 */
function valida($campo, $valor, $tipo, $obrigatorio, &$err, $msg = '')
{

    include __DIR__ . "/" . "../languages/" . $_SESSION['s_language'];

    $LISTA = array();
    $LISTA['INTFULL'] = "/^\d*$/"; //INTEIRO QUALQUER
    $LISTA['INTEIRO'] = "/^[1-9]\d*$/"; //NAO INICIADOS POR ZERO
    $LISTA['MAIL'] = "/^[\w!#$%&'*+\/=?^`{|}~-]+(\.[\w!#$%&'*+\/=?^`{|}~-]+)*@(([\w-]+\.)+[A-Za-z]{2,6}|\[\d{1,3}(\.\d{1,3}){3}\])$/";
    $LISTA['MAILMULTI'] = "/^([\w!#$%&'*+\/=?^`{|}~-]+(\.[\w!#$%&'*+\/=?^`{|}~-]+)*@(([\w-]+\.)+[A-Za-z]{2,6}|\[\d{1,3}(\.\d{1,3}){3}\]))(\,\s?([\w!#$%&'*+\/=?^`{|}~-]+(\.[\w!#$%&'*+\/=?^`{|}~-]+)*@(([\w-]+\.)+[A-Za-z]{2,6}|\[\d{1,3}(\.\d{1,3}){3}\]))+)*$/";
    $LISTA['DATA'] = "/^((0?[1-9]|[12]\d)\/(0?[1-9]|1[0-2])|30\/(0?[13-9]|1[0-2])|31\/(0?[13578]|1[02]))\/(19|20)?\d{2}$/";
    $LISTA['DATA_'] = "/^((0?[1-9]|[12]\d)\-(0?[1-9]|1[0-2])|30\-(0?[13-9]|1[0-2])|31\-(0?[13578]|1[02]))\-(19|20)?\d{2}$/";
    $LISTA['DATAHORA'] = "/^(((0?[1-9]|[12]\d)\/(0?[1-9]|1[0-2])|30\/(0?[13-9]|1[0-2])|31\/(0?[13578]|1[02]))\/(19|20)?\d{2})[ ]([0-1]\d|2[0-3])+:[0-5]\d:[0-5]\d$/";
    $LISTA['MOEDA'] = "/^\d{1,3}(\.\d{3})*\,\d{2}$/";
    $LISTA['MOEDASIMP'] = "/^\d*\,\d{2}$/";
    $LISTA['ETIQUETA'] = "/^[1-9]\d*(\,\d+)*$/"; //expressão para validar consultas separadas por vírgula;
    $LISTA['ALFA'] = "/^[A-Z]|[a-z]([A-Z]|[a-z])*$/";
    $LISTA['ALFANUM'] = "/^([A-Z]|[a-z]|[0-9])([A-Z]|[a-z]|[0-9])*\.?([A-Z]|[a-z]|[0-9])([A-Z]|[a-z]|[0-9])*$/"; //Valores alfanuméricos aceitando separação com no máximo um ponto.
    $LISTA['ALFAFULL'] = "/^[\w!#$%&'*+\/=?^`{|}~-]+(\.[\w!#$%&'*+\/=?^`{|}~-]+)*$/";
    $LISTA['FONE'] = "/^(([+][\d]{2,2})?([-]|[\s])?[\d]*([-]|[\s])?[\d]+)+([,][\s]([+][\d]{2,2})?([-]|[\s])?[\d]*([-]|[\s])?[\d]+)*$/";
    $LISTA['COR'] = "/^([#]([A-F]|[a-f]|[\d]){6,6})|([I][M][G][_][D][E][F][A][U][L][T])$/";
    $LISTA['USUARIO'] = "/^([0-9a-zA-Z]+([_.-]?[0-9a-zA-Z]+))$/";

    $LISTA['ANO'] = "/^\d{4}$/"; //var regANO = /^\d{4}$/;

    $ERRO = array();
    $ERRO['OBRIGATORIO'] = "O campo " . $campo . " é obrigatório!";
    $ERRO['INTFULL'] = "O campo " . $campo . " deve conter apenas numeros inteiros!";
    $ERRO['INTEIRO'] = "O campo " . $campo . " deve conter apenas numeros inteiros não iniciados por ZERO!";
    $ERRO['MAIL'] = "Formato de e-mail inválido para o campo {$campo}";
    $ERRO['MAILMULTI'] = TRANS('INVALID_EMAIL_FORMAT');
    $ERRO['DATA'] = "Formato de data invalido! dd/mm/aaaa";
    $ERRO['DATA_'] = "Formato de data invalido! dd-mm-aaaa";
    $ERRO['DATAHORA'] = "Formato de data invalido! dd/mm/aaaa H:m:s";
    $ERRO['MOEDA'] = "Formato de moeda inválido!";
    $ERRO['MOEDASIMP'] = "Formato de moeda inválido! XXXXXX,XX";
    $ERRO['ETIQUETA'] = "o Formato do campo " . $campo . " deve ser de valores inteiros não iniciados por Zero e separados por vírgula!";
    $ERRO['ALFA'] = "Esse o campo " . $campo . " só aceita carateres do alfabeto sem espaços!";
    $ERRO['ALFANUM'] = "O campo " . $campo . " só aceita valores alfanuméricos sem espaços ou separados por um ponto(no máximo um)!";
    $ERRO['ALFAFULL'] = "O campo " . $campo . " só aceita valores alfanuméricos sem espaços!";
    $ERRO['FONE'] = "O campo " . $campo . " só aceita valores formatados para telefones (algarismos, traços e espaços) separados por vírgula.";
    $ERRO['COR'] = "O campo " . $campo . " só aceita valores formatados para cores HTML! Ex: #FFCC99";
    $ERRO['USUARIO'] = "O campo " . $campo . " não está no formato aceito.";
    $ERRO['ANO'] = "O campo " . $campo . " não está no formato aceito.";

    if ($LISTA[$tipo] == '') {
        print "ÍNDICE INVÁLIDO!";
        return false;
    }
    
    if ($obrigatorio) {
        if ($valor == '') {
            $err = ($msg == "") ? $ERRO['OBRIGATORIO'] : $msg;
            return false;
        }
        if (preg_match($LISTA[$tipo], $valor)) {
            return true;
        } 
        $err = ($msg == "") ? $ERRO[$tipo] : $msg;
        return false;
    }
    
    if ($valor != '') {
        if (preg_match($LISTA[$tipo], $valor)) {
            return true;
        }

        $err = ($msg == "") ? $ERRO[$tipo] : $msg;
        return false;
    }
    return true;
}

function getDirFileNames($dir, $ext = 'php|PHP')
{
    // Abre um diretorio conhecido, e faz a leitura de seu conteudo de acordo com a extensão solicitada
    $array = array();
    if (is_dir($dir)) {
        if ($readFiles = opendir($dir)) {
            while (($file = readdir($readFiles)) !== false) {
                if ($file != '..' && $file != '.' && $file != '' && $file != 'index.php') {
                    if (eregi("\.(" . $ext . "){1}$", $file)) {
                        $array[] = $file;
                    }
                }
            }
            closedir($readFiles);
        }
    }
    return $array;
}

function isPar($number){ 
    if($number % 2 == 0){ 
        return true;
    } 
    return false;
} 

function isImpar($number){ 
    if($number % 2 == 0){ 
        return false;
    } 
    return true;
} 


/**
 * dbField
 *
 * @param mixed $field
 * @param mixed $type="int"|"text"|"date"|"float"
 * 
 * @return [type]
 */
function dbField($field, $type="int") {

    $field = noHtml($field);

    if ($type == "int")
        return $field = ($field == '-1' || $field == 'null' || $field == '' ? 'null' : $field);

    if ($type == "float") {
        $field = str_replace(',','.', $field);
        return $field = ($field == '-1' || $field == 'null' || $field == '' ? 'null' : $field);
    }
        
    if ($type == "text")
        return $field = ($field == '-1' || $field == 'null' || $field == '' ? 'null' : "'$field'");

    if ($type == "date") {
        // $field = FDate($field);
        $field = dateDB($field, 1);
        return $field = ($field == '' || $field == '0000-00-00' || $field == '0000-00-00 00:00:00' ? 'null' : "'$field'");
    }
}



/**
 * csrf
 * @param string $sessionKey Indica uma chave específica para sessão
 *
 * @return void
 */
function csrf(string $sessionKey = 'csrf_token'): void
{
    $_SESSION[$sessionKey] = base64_encode(random_bytes(20));
}

/**
 * csrf_input
 * @param string $sessionKey Indica uma chave específica para sessão
 *
 * @return string
 */
function csrf_input(string $sessionKey = 'csrf_token'): string
{
    csrf($sessionKey);
    
    $inputs = "<input type='hidden' name='csrf' id='csrf' value='".($_SESSION[$sessionKey] ?? "")."'/>";
    $inputs .= "<input type='hidden' name='csrf_session_key' id='csrf_session_key' value='" . ($sessionKey ?? "") . "'/>";
    return $inputs;
}

/**
 * csrf_verify
 *
 * @param $request
 * @param string $sessionKey Indica uma chave específica para sessão
 * 
 * @return bool
 */
function csrf_verify($request, string $sessionKey = 'csrf_token'): bool
{
    if (empty($_SESSION[$sessionKey]) || empty($request['csrf']) || $request['csrf'] != $_SESSION[$sessionKey]){
        return false;
    }
    csrf($sessionKey);
    return true;
}

/**
 * @param string $url
 */
function redirect(string $url): void
{
    header("HTTP/1.1 302 Redirect");
    // if (filter_var($url, FILTER_VALIDATE_URL)) {
        header("Location: {$url}");
        exit();
    // }
}


/**
 * Retorna o nome do mês correspondente ao índice numérico recebido - valores de 1 a 12
 */
function getMonthLabel($monthIndex){

    include __DIR__ . "/" . "../languages/" . $_SESSION['s_language'];

    $months = array();

    $months[1] = TRANS('JANUARY');
    $months[2] = TRANS('FEBRUARY');
    $months[3] = TRANS('MARCH');
    $months[4] = TRANS('APRIL');
    $months[5] = TRANS('MAY');
    $months[6] = TRANS('JUNE');
    $months[7] = TRANS('JULY');
    $months[8] = TRANS('AUGUST');
    $months[9] = TRANS('SEPTEMBER');
    $months[10] = TRANS('OCTOBER');
    $months[11] = TRANS('NOVEMBER');
    $months[12] = TRANS('DECEMBER');

    return $months[$monthIndex];
}


/**
 * Retorna o nome do tipo de assentamento de acordo com o indice informado 
 */
function getEntryType($entryIndex){

    include __DIR__ . "/" . "../languages/" . $_SESSION['s_language'];

    $types = array();

    $types[0] = TRANS('ENTRY_TYPE_OPENING');
    $types[1] = TRANS('ENTRY_TYPE_EDITING');
    $types[2] = TRANS('ENTRY_TYPE_GET_TO_TREAT');
    $types[3] = TRANS('ENTRY_TYPE_JUSTIFYING');
    $types[4] = TRANS('ENTRY_TYPE_TECH_DESCRIPTION');
    $types[5] = TRANS('ENTRY_TYPE_SOLUTION_DESCRIPTION');
    $types[6] = TRANS('ENTRY_TYPE_OUT_OF_SCHEDULE');
    $types[7] = TRANS('ENTRY_TYPE_SCHEDULING');
    $types[8] = TRANS('ENTRY_TYPE_ADDITIONAL_INFO');
    $types[9] = TRANS('ENTRY_TYPE_TICKET_REOPENED');
    $types[10] = TRANS('ENTRY_TYPE_SUBTICKET_OPENED');
    $types[11] = TRANS('ENTRY_TYPE_TICKET_RELATION_REMOVED');
    $types[12] = TRANS('ENTRY_TYPE_TAG_EDITED');
    $types[13] = TRANS('ENTRY_TICKET_APPROVED');
    $types[14] = TRANS('ENTRY_TICKET_REJECTED');
    $types[15] = TRANS('ENTRY_TICKET_CLOSE');
    $types[16] = TRANS('ENTRY_SERVICE_DONE');
    $types[17] = TRANS('ROUTED_TO_OPERATOR_QUEUE');

    if (!array_key_exists($entryIndex, $types)) {
        return TRANS('ENTRY_TYPE_NOT_LABELED');
    }
    return $types[$entryIndex];
}

/**
 * Retorna o nome do tipo de operação para log, de acordo com o indice informado 
 */
function getOperationType($index){

    include __DIR__ . "/" . "../languages/" . $_SESSION['s_language'];

    $types = array();

    $types[0] = TRANS('OPT_OPERATION_TYPE_OPEN');
    $types[1] = TRANS('OPT_OPERATION_TYPE_EDIT');
    $types[2] = TRANS('OPT_OPERATION_TYPE_ATTEND');
    $types[3] = TRANS('OPT_OPERATION_TYPE_REOPEN');
    $types[4] = TRANS('OPT_OPERATION_TYPE_CLOSE');
    $types[5] = TRANS('OPT_OPERATION_TYPE_ATTRIB');
    $types[6] = TRANS('OPT_OPERATION_SCHEDULE');
    $types[7] = TRANS('OPT_OPERATION_APPROVAL');
    $types[8] = TRANS('OPT_OPERATION_CLOSURE_REJECTED');
    $types[9] = TRANS('OPT_OPERATION_SERVICE_CLOSURE');

    if (!array_key_exists($index, $types)) {
        return TRANS('OPT_OPERATION_NOT_LABELED');
    }
    return $types[$index];
}


/**
 * Retorna um array com as datas de início e fim de cada mês 
 * no perído retroativo compatível com o parâmetro de intervalo informado: ex: P6M
 */
function getMonthRangesUpToNOw($maxInterval) {
    // $maxInterval = 'P6M';
    $regularInterval = 'P1M';

    $begin = new DateTime(date('Y-m-01 00:00:00'));
    $begin = date_sub($begin, new DateInterval($maxInterval));
    $end = new DateTime(date('Y-m-01 00:00:00'));
    $end = date_add($end, new DateInterval($regularInterval));

    $interval = new DateInterval($regularInterval);
    $daterange = new DatePeriod($begin, $interval ,$end);
    $dates = [];
    foreach($daterange as $date){
        $dates['ini'][] = date_format($date, "Y-m-d 00:00:00");
        $dates['end'][] = date_format($date, "Y-m-t 23:59:59");
        $dates['mLabel'][] = getMonthLabel((int)date_format($date, "m"));
    }

    return $dates;
}


/**
 * Generates an array of month ranges up to the current month.
 *
 * @param string $maxIntervalRange The maximum interval range. Default: 'P6M'.
 * @param string $interval The interval between each month. Default: 'P1M'.
 * @throws None
 * @return array An array of month ranges with start and end dates, labels, and period labels.
 */
function getMonthRangesUpToNow2($maxIntervalRange = 'P6M', $interval = 'P1M'): array 
{
    $intervalLabels = [
        'P1M' => 'Mensal',
        'P2M' => 'Bimestral',
        'P3M' => 'Trimestral',
        'P6M' => 'Semestral',
        'P12M' => 'Anual'
    ];
    
    $dates = [];
    $dates['intervalLabel'] = ($intervalLabels[$interval] ?? '');
    
    $begin = new DateTime(date('Y-m-01 00:00:00'));
    $begin = date_sub($begin, new DateInterval($maxIntervalRange));

    $end = new DateTime(date('Y-m-01 00:00:00'));
    $end = date_add($end, new DateInterval('P1D'));
    // $end = date_add($end, new DateInterval('P1M'));

    $interval = new DateInterval($interval);
    $daterange = new DatePeriod($begin, $interval ,$end);

    $i = 0;
    foreach($daterange as $date){
        $dates['begin'][] = date_format($date, "Y-m-d 00:00:00");
        $dates['labelBegin'][] = getMonthLabel((int)date_format($date, "m"));
        $tmp = date_add($date, $interval);
        $tmp = date_sub($tmp, new DateInterval('P1D'));
        $dates['end'][] = date_format($tmp, "Y-m-d 23:59:59");
        $dates['labelEnd'][] = getMonthLabel((int)date_format($date, "m"));
        $dates['periodLabel'][] = ($dates['labelBegin'][$i] == $dates['labelEnd'][$i]) ? $dates['labelBegin'][$i] : $dates['labelBegin'][$i] . ' :: ' . $dates['labelEnd'][$i];
        $i++;
    }
    return $dates;
}



function secToTime(int $secs): array
{
    $time = array("seconds" => 0, "minutes" => 0, "hours" => 0, "verbose" => "");
    $time['seconds'] = $secs % 60;
    $secs = ($secs - $time['seconds']) / 60;
    $time['minutes'] = $secs % 60;
    $time['hours'] = ($secs - $time['minutes']) / 60;
    
    $time['verbose'] = $time['hours'] . "h " . $time['minutes'] . "m " . $time['seconds'] . "s";

    return $time;
}

/**
 * Trunca (formata) a exibição do tempo de acordo com o número de elementos definidos em $nSets
 * @param string $time
 * @param integer $nSets
 * 
 * @return string
 */
function truncateTime ($time, $nSets): string
{
    $newTime = trim($time);
    $nSets = ($nSets == 0 ? 1 : $nSets);
    $sets = explode(" ", $time);
    if ($nSets < count($sets)) {
        $newTime = "";
        for ($i = 0; $i < $nSets; $i++) {
            $newTime .= $sets[$i] ." ";
        }
        $newTime = trim($newTime) . "..";
    }
    return $newTime;
}

function detectUTF8($string)
{
        return preg_match('%(?:
        [\xC2-\xDF][\x80-\xBF]        # non-overlong 2-byte
        |\xE0[\xA0-\xBF][\x80-\xBF]               # excluding overlongs
        |[\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}      # straight 3-byte
        |\xED[\x80-\x9F][\x80-\xBF]               # excluding surrogates
        |\xF0[\x90-\xBF][\x80-\xBF]{2}    # planes 1-3
        |[\xF1-\xF3][\x80-\xBF]{3}                  # planes 4-15
        |\xF4[\x80-\x8F][\x80-\xBF]{2}    # plane 16
        )+%xs', $string);
}

/**
 * DETECTS IF THE GIVEN STRING IS IN UTF8 AND CONVERTS TO ISO-88591
 * @author Flavio Ribeiro
 * char
 *
 * @param string $string
 * 
 * @return null|string
 */
function char(?string $string): ?string
{
    if (isset($string)){
        if (detectUTF8($string))
            // return utf8_decode($string);
            return new_utf8_decode($string);
        return $string;
    } 
    return null;
}


/**
 * Converts a UTF-8 string to ISO-88591
 *
 * @param string|null $string
 * 
 * @return string|null
 */
function convertToISO(?string $string): ?string
{
    if (!$string) {
        return null;
    }

    return mb_convert_encoding($string, 'ISO-8859-1', 'UTF-8');
}
