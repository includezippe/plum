<?php
/**
 * Created by PhpStorm.
 * User: dmitrijdorozkin
 * Date: 22.10.14
 * Time: 6:36
 */


define('VKTOKEN', '5010a140809a77dde5723e31238bc2f8a09d7e51fc3551b9c1e4018e16e4ee11d38fdf51ca878dff26571');

class VkApi{
    private $app_id;
    private $secret_key;
    private $redirect_uri;
    private $version = '5.2';

    function __construct($app_id,$secret_key,$redirect_uri='https://oauth.vk.com/blank.html'){
        $this->app_id = $app_id;
        $this->secret_key = $secret_key;
        $this->redirect_uri = $redirect_uri;
    }

    private function sendPost($post_url, $post_data){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $post_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_REFERER, $this->redirect_uri);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
        curl_setopt($ch, CURLOPT_USERAGENT,'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.17 (KHTML, like Gecko) Chrome/24.0.1312.57 Safari/537.17');

        $data = curl_exec($ch);
        curl_close($ch);

        return $data;
    }

    function goAuth($scope = 'audio,photos,status,offline'){
        $url = 'http://oauth.vk.com/authorize';
        $params = array(
            'client_id'     => $this->app_id,
            'redirect_uri'  => $this->redirect_uri,
            'scope'			=> $scope,
            'response_type' => 'code',
            'v'				=> $this->version
        );

        return $url.'?'.urldecode(http_build_query($params));
    }

    function getToken($code){
        $url = 'https://oauth.vk.com/access_token';
        $params = array(
            'client_id'     => $this->app_id,
            'client_secret' => $this->secret_key,
            'code'			=> $code,
            'redirect_uri'  => $this->redirect_uri
        );

        $resp = json_decode($this->sendPost($url,http_build_query($params)),true);

        return $resp;
    }

    function goMethod($method,$params,$token){
        $url = 'https://api.vk.com/method/'.$method;

        array_push($params, 'access_token');
        $params['access_token'] = $token;

        $resp = json_decode($this->sendPost($url,http_build_query($params)),true);

        return $resp;
    }
}
?>