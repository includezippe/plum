<?php
/**
 * Created by PhpStorm.
 * User: dmitrijdorozkin
 * Date: 22.10.14
 * Time: 6:04
 */

require_once 'backend/api/api.class.php';

@session_start();
$api = new api();

define('VKAPPID', '4600276');
define('VKSECRET','R5aj8VAUt2QFqJhDhK6R');
define('TOKEN', isset($_SESSION['Token'])?$_SESSION['Token']:false);

error_reporting(E_ALL);
ini_set('display_errors', '1');

//switch ($_SERVER['SERVER_ADDR']) {
//    case '*.*.*.*':
//        $serverDomain = ' ';
//        break;
//    case '*.*.*.*':
//        $serverDomain = ' ';
//        break;
//    default:
//        $serverDomain = ' ';
//        break;
//}

function handleError($errno, $errstr, $errfile, $errline, array $errcontext)
{
    if (0 === error_reporting()) return false;
    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
}

set_error_handler('handleError');

$action = explode('/', $_SERVER['REQUEST_URI']);

if(!isset($action[2]))
    $page = NULL;
else
    $page = $action[2];

if(!isset($action[1])) $action[1] = NULL;

if(stripos($action[1],'?')) {
    $action = explode('?',$action[1]);
    $action = $action[0];
} else
    $action = $action[1];

switch($action) {
    case 'quit':
        session_destroy();
        print 123;
        header('Location: /');
        break;
    case 'api':
        header('Content-type: application/json; charset=UTF-8');
        break;
    case 'auth':
        header('Content-type: text/html; charset=UTF-8');
        if(isset($_GET['code'])) {
            $url = 'https://oauth.vk.com/access_token?client_id='.VKAPPID.'&client_secret='.VKSECRET.'&code='.$_GET['code'].'&redirect_uri=http://plumlove.ru/auth/';
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_URL,$url);
            $result=curl_exec($ch);
            curl_close($ch);
            $json = json_decode($result, true);
            ob_start();
            $api->signup_vk($json['access_token']);
            $json = json_decode(ob_get_contents(),true);
            ob_end_clean();
            $_SESSION['Token'] = $json['Token'];
            header('Location: /join');
        }
        break;
    case 'view':
        @include_once 'frontend/templates/'.$page;
        break;
    case 'css':
        @include_once 'frontend/css/'.$page;
        break;
    case 'js':
        @include_once 'frontend/js/'.$page;
        break;
    case 'lib':
        @include_once 'frontend/lib/'.$page;
        break;
    case 'logout':
        @session_destroy();
        header('Location: /');
        break;
    default:
        header('Content-type: text/html; charset=UTF-8');
        if(TOKEN) {
            $status = $api->get_user_stage(TOKEN);
            switch ($status['Stage']) {
                case 1:
                    if($action!='join') header('Location: /join');
                    break;
                case 2:
                    if($action!='touch') header('Location: /touch');
                    break;
                case 3:
                    if(($action=='') or ($action=='touch') or ($action=='join')) header('Location: /scan');
                    break;
            }
        } else {
            if($action!='') header('Location: /');
        }
        @include_once 'frontend/templates/index.html';
        break;

}