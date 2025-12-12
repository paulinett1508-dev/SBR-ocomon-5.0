<?php
session_start();

require_once __DIR__ . "/" . "../../includes/components/Captcha-master/src/Gregwar/Captcha/CaptchaBuilderInterface.php";
require_once __DIR__ . "/" . "../../includes/components/Captcha-master/src/Gregwar/Captcha/PhraseBuilderInterface.php";
require_once __DIR__ . "/" . "../../includes/components/Captcha-master/src/Gregwar/Captcha/PhraseBuilder.php";
require_once __DIR__ . "/" . "../../includes/components/Captcha-master/src/Gregwar/Captcha/CaptchaBuilder.php";

use Gregwar\Captcha\CaptchaBuilder;

$data = [];
$builder = new CaptchaBuilder;
$builder->build();


$data['captcha'] = $builder->inline();
$_SESSION['captcha'] = $builder->getPhrase();
echo json_encode($data);

?>
