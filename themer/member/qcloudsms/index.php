<?php
defined( 'ABSPATH' ) || exit;

require_once __DIR__ . "/SmsSenderUtil.php";
require_once __DIR__ . "/SmsSingleSender.php";
require_once __DIR__ . "/SmsMultiSender.php";
require_once __DIR__ . "/SmsStatusPuller.php";
require_once __DIR__ . "/SmsMobileStatusPuller.php";
require_once __DIR__ . "/SmsVoicePromptSender.php";
require_once __DIR__ . "/SmsVoiceVerifyCodeSender.php";

require_once __DIR__ . "/VoiceFileUploader.php";
require_once __DIR__ . "/FileVoiceSender.php";
require_once __DIR__ . "/TtsVoiceSender.php";


use Qcloud\Sms\SmsSingleSender;

function wpcom_qcloud_sms_sender($phone, $params){
    global $options;
    $appid = isset($options['qcloud_sms_appid']) ? $options['qcloud_sms_appid'] : '';
    $appkey = isset($options['qcloud_sms_appkey']) ? $options['qcloud_sms_appkey'] : '';
    $templateId = isset($options['qcloud_sms_tid']) ? $options['qcloud_sms_tid'] : '';
    $sign = isset($options['qcloud_sms_sign']) ? $options['qcloud_sms_sign'] : '';

    if(isset($appid) && $appid && $appkey && $templateId && $sign){
        // 短信模板ID，需要在短信应用中申请
        $ssender = new SmsSingleSender($appid, $appkey);
        $result = $ssender->sendWithParam("86", $phone, $templateId, $params, $sign, "", "");
        return json_decode($result);
    }else{
        $result = new stdClass();
        $result->result = -1;
        $result->errmsg = '网站未配置短信接口';
        return $result;
    }
}