<?php
//ini_set("display_errors", 1);
//error_reporting(E_ALL);
defined( 'ABSPATH' ) || exit;

include_once FRAMEWORK_PATH . '/member/aliyun-php-sdk/aliyun-php-sdk-core/Config.php';
use Afs\Request\V20180112 as Afs;

function AfsCheckRequest( $csessionid, $token, $sig, $scene ){
    global $options;
    //YOUR ACCESS_KEY、YOUR ACCESS_SECRET请替换成您的阿里云accesskey id和secret
    $iClientProfile = DefaultProfile::getProfile( "cn-shanghai", trim($options['nc_access_id']), trim($options['nc_access_secret']) );
    $client = new DefaultAcsClient($iClientProfile);
    DefaultProfile::addEndpoint("cn-shanghai", "cn-shanghai", "afs", "afs.aliyuncs.com");

    $request = new Afs\AuthenticateSigRequest();
    $request->setSessionId($csessionid);// 必填参数，从前端获取，不可更改，android和ios只变更这个参数即可，下面参数不变保留xxx
    $request->setToken($token);// 必填参数，从前端获取，不可更改
    $request->setSig($sig);// 必填参数，从前端获取，不可更改
    $request->setScene($scene);// 必填参数，从前端获取，不可更改
    $request->setAppKey(trim($options['nc_appkey']));//必填参数，后端填写
    $request->setRemoteIp(wpcom_get_ip());//必填参数，后端填写

    return $client->getAcsResponse($request);
}

function wpcom_get_ip(){
    if(!empty($_SERVER["HTTP_CLIENT_IP"])){
        $cip = $_SERVER["HTTP_CLIENT_IP"];
    } elseif (!empty($_SERVER["HTTP_X_FORWARDED_FOR"])){
        $cip = $_SERVER["HTTP_X_FORWARDED_FOR"];
    } elseif (!empty($_SERVER["REMOTE_ADDR"])){
        $cip = $_SERVER["REMOTE_ADDR"];
    } else {
        $cip = "none";
    }
    return $cip;
}