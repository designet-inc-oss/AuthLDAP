<?php
require_once dirname(__FILE__) . '/../../videos/configuration.php';
require_once $global['systemRootPath'] . 'objects/user.php';
require_once $global['systemRootPath'] . 'plugin/AuthLDAP/AuthLDAP.php';

$obj = new stdClass();
$obj->Authenticated = false;
$obj->error = '';

$res = AuthLDAP::login($_POST['user'], $_POST['pass']);
if ($res === User::USER_LOGGED) {
    $obj->Authenticated = User::isLogged();
}

$obj->needCaptcha = User::isCaptchaNeed();
if($res === User::CAPTCHA_ERROR) {
    $obj->error = __("Invalid Captcha");
}

header('Content-Type: application/json');
echo json_encode($obj);
