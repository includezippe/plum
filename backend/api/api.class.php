<?php
/**
 * Created by PhpStorm.
 * User: dmitrijdorozkin
 * Date: 19.10.14
 * Time: 20:36
 */

include_once 'model.class.php';
include_once 'view.class.php';

class api {

    private $model;
    private $view;
    private $userID;

    function __construct() {
        $this->model = new model();
        $this->view = new view();
        $url = parse_url($_SERVER['REQUEST_URI']);
        $action = explode('/', $url['path']);
        if ((count($_REQUEST)>0)&&($action[1]=="api")) {
            $action = end($action);
            if(!empty($action)) {
                if(empty($_POST)&&substr($action,0,4)!='get_') {
                    view::error("No params post.");
                } else {
                    if(method_exists($this, $action)) {
                        $func = new ReflectionMethod($this, $action);

                        $args = array();
                        foreach ($func->getParameters() as $param)
                            $args[$param->getName()] = isset($_POST[$param->getName()])?$_POST[$param->getName()]:NULL;

                        //sanitize POST data
                        //$this->sanitize($args);

                        //check auth token
                        if($action!='signup_vk') if(!$this->userID = $this->model->checkUserToken($_SESSION['Token'])) {
                            view::state(false);
                            exit;
                        }

                        //request api method
                        try {
                            call_user_func_array(array($this, $action), $args);
                        } catch (ErrorException $e) {
                            view::error($_POST);
                        }
                    } else {
                        view::error("The method ".$action." is undefined.");
                    }
                }
            }
        }
    }

    private function randString($length){
        $char = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $char = str_shuffle($char);
        for($i = 0, $rand = '', $l = strlen($char) - 1; $i < $length; $i ++) {
            $rand .= $char{mt_rand(0, $l)};
        }
        return $rand;
    }

    function uploadImage($file) {
        $uploaddir = getcwd().'/upload/img/';
        if(!$file['type']) return NULL;
        $imagetypes = array(
            'image/png' => '.png',
            'image/gif' => '.gif',
            'image/jpeg' => '.jpg',
            'image/bmp' => '.bmp');
        $ext = $imagetypes[$file['type']];
        $randval =  $this->randString(7);
        $uploadname = $randval.$ext;
        $uploadfile = $uploaddir.$uploadname;
        if (@move_uploaded_file($file['tmp_name'], $uploadfile)) {
            include_once 'libs/ImageResize.class.php';

            $resizeObj = new resize($uploadfile);
            $resizeObj -> resizeImage(250, 250, 'auto');
            $resizeObj -> saveImage($uploaddir.$randval.'_150'.$ext, 1000);
            $resizeObj = new resize($uploadfile);
            $resizeObj -> resizeImage(500, 500, 'auto');
            $resizeObj -> saveImage($uploaddir.$randval.'_320'.$ext, 1000);
            $resizeObj = new resize($uploadfile);
            $resizeObj -> resizeImage(2000, 2000, 'auto');
            $resizeObj -> saveImage($uploaddir.$randval.$ext, 1000);

            return "http://".$_SERVER['HTTP_HOST']."/upload/img/".$randval.$ext;
        }
        else return NULL;
    }

    public function signup_vk($token) {

        include('libs/vkapi.class.php');
        $vk = new VkApi(VKAPPID, VKSECRET);

        $params = array(
            'fields' => 'photo_200,photo_max_orig,screen_name'
        );

        $data = $vk->goMethod('users.get', $params, $token);

        if(!empty($data['error'])) {
            view::state(false);
            return false;
        }

        $data = $data['response'][0];
        $res = $this->model->getToken_vk($data['uid']);

        if($res != false) {
            view::encode($res);
            return true;
        } else {
            $result = $this->model->register_vk($data['uid']);
            $this->model->update_stage(1,$result['UserID']);
            view::encode($result);
            return true;
        }
    }

    private function update_profile() {
        $data = array();
        foreach($_POST as $key => $arg)
            $data[$key] = $arg;
        //$this->sanitize($data);
        $this->model->update_stage(2,$this->userID);
        view::state($this->model->update_profile($data,$this->userID));
    }

    private function get_profile($userID) {
        $userID = empty($userID)?intval($_GET['id']):$userID;

        $profile = $this->model->getUserProfile($userID);

        view::encode($profile);
    }

    private function send_profile_image($role) {
        $url = $this->uploadImage($_FILES['Image']);

        switch ($role) {
            case 2:
                if($imgID = $this->model->checkImageRole($role,$this->userID))
                    $this->model->changeImageRole(1,$imgID);
                break;
            case 3:
                if($imgID = $this->model->checkImageRole($role,$this->userID))
                    $this->model->changeImageRole(1,$imgID);
                break;
        }

        view::encode($this->model->addUserImage($url,$role,$this->userID));
    }

    private function sanitize_array($array) {
        return $array;
    }

    private function delete_profile_image($imgID) {
        view::state($this->model->changeImageRole(0,$imgID));
    }

    public function get_user_stage($token) {
        return $this->model->get_user_stage($this->model->checkUserToken($token));
    }

    public function get_touch_slides() {
        view::encode($this->model->get_touch_slides());
    }

    public function send_characters() {
        $points = $this->sanitize_array(explode(',',$_POST[0]));
        $characters = $this->sanitize_array(explode(',',$_POST[1]));
        $group = array();
        $points = array_keys($points,max($points));
        if(count($points)>2) {
            $group[] = 5;
        } else {
            foreach($points as $value) {
                $group[] = intval($value) + 1;
            }
        }

        $this->model->update_stage(3,$this->userID);
        view::state($this->model->set_user_characters($group,$characters,$this->userID));
    }

    public function get_users() {
        view::encode($this->model->get_users_random($this->userID));
    }

    public function get_favorites() {
        view::encode($this->model->get_favorites($this->userID));
    }
}



