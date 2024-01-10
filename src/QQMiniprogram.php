<?php

namespace Superzc\QQMiniprogram;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Superzc\QQMiniprogram\Exceptions\DefaultException;
use Superzc\QQMiniprogram\Constants\ErrorCodes;

class QQMiniprogram
{
    protected $appid;
    protected $appsecret;
    protected $access_token;

    /**
     * 构造方法
     */
    public function __construct($config)
    {
        $this->appid = $config->get('miniprogram.qq.appid');
        $this->appsecret = $config->get('miniprogram.qq.appsecret');
    }

    /**
     * 获取接口调用凭据
     * https://q.qq.com/wiki/develop/miniprogram/server/open_port/port_use.html#getaccesstoken
     */
    public function getAccessToken()
    {
        // 校验参数
        if ($this->appid == '') {
            throw new DefaultException('缺少配置appid', ErrorCodes::INVALID_PARAMS);
        }
        if ($this->appsecret == '') {
            throw new DefaultException('缺少配置appsecret', ErrorCodes::INVALID_PARAMS);
        }

        $response = Http::get("https://api.q.qq.com/api/getToken?grant_type=client_credential&appid=" . $this->appid . "&secret=" . $this->appsecret);

        return $this->processResponse($response);
    }

    /**
     * 设置登录调用凭据
     */
    public function setAccessToken($access_token)
    {
        $this->access_token = $access_token;
    }

    /**
     * QQ小程序登录
     * https://q.qq.com/wiki/develop/miniprogram/server/open_port/port_login.html#code2session
     */
    public function code2Session($code)
    {
        // 校验参数
        if ($code == '') {
            throw new DefaultException('缺少参数code', ErrorCodes::INVALID_PARAMS);
        }

        $response = Http::get("https://api.q.qq.com/sns/jscode2session?appid=" . $this->appid . "&secret=" . $this->appsecret . "&js_code=" . $code . "&grant_type=authorization_code");

        return $this->processResponse($response);
    }

    /**
     * 获取用户encryptKey
     */
    public function getUserEncryptKey($openid, $session_key)
    {
        // 校验参数
        if ($openid == '') {
            throw new DefaultException('缺少参数openid', ErrorCodes::INVALID_PARAMS);
        }
        if ($session_key == '') {
            throw new DefaultException('缺少参数session_key', ErrorCodes::INVALID_PARAMS);
        }

        $signature = hash_hmac('sha256', '{}', $session_key);

        try {
            $access_token = $this->requestAccessToken();
        } catch (DefaultException $e) {
            throw new DefaultException($e->getMessage(), $e->getCode());
        }

        $postData = '';

        $response = Http::post("https://api.q.qq.com/api/trpc/userEncryptionSvr/GetUserEncryptKey?access_token=" . $access_token . "&appid=" . $this->appid . "&openid=" . $openid . "&openkey=" . $session_key . "&sig=" . $signature, $postData);

        return $this->processResponse($response);
    }

    /**
     * 加密数据
     *
     * @param openid string
     * @param session_key string
     * @param data array
     *
     * @return array
     */
    public function encryptData($openid, $session_key, $data)
    {
        require_once("xxtea.php");

        try {
            $result = $this->getUserEncryptKey($openid, $session_key);
        } catch (DefaultException $e) {
            throw new DefaultException($e->getMessage(), $e->getCode());
        }

        $version = '';
        $encrypt_str = '';

        if (isset($result['errcode']) && $result['errcode'] === 0) {
            $keyInfoList = $result['key_info_list'];
            $encrypt_key = $keyInfoList[0]['encrypt_key'];
            $version = $keyInfoList[0]['version'];

            $encrypt_str = xxtea_encrypt(json_encode($data, JSON_UNESCAPED_UNICODE), $encrypt_key);
            $encrypt_str = base64_encode($encrypt_str);
        } else {
            throw new DefaultException('获取用户encryptKey失败', ErrorCodes::ERROR);
        }

        return [
            'version' => $version,
            'encrypt_str' => $encrypt_str,
        ];
    }

    /**
     * 解密数据
     *
     * @param openid string
     * @param session_key string
     * @param version int
     * @param encrypt_str string
     *
     * @return array
     */
    public function decryptData($openid, $session_key, $version, $encrypt_str)
    {
        require_once("xxtea.php");

        try {
            $result = $this->getUserEncryptKey($openid, $session_key);
        } catch (DefaultException $e) {
            throw new DefaultException($e->getMessage(), $e->getCode());
        }

        $encrypt_key = '';
        $data = [];
        if (isset($result['errcode']) && $result['errcode'] === 0) {
            foreach ($result['key_info_list'] as $key => $value) {
                if ($value['version'] == $version && $value['expire_in'] > 0) {
                    $encrypt_key = $value['encrypt_key'];
                    break;
                }
            }

            if ($encrypt_key) {
                $encrypt_str = base64_decode($encrypt_str);
                $data = xxtea_decrypt($encrypt_str, $encrypt_key);
                $data = json_decode($data, true);
            } else {
                throw new DefaultException("用户encryptKey已失效", ErrorCodes::ERROR);
            }
        } else {
            throw new DefaultException("获取用户encryptKey失败", ErrorCodes::ERROR);
        }

        return $data;
    }

    /**
     * 文本内容安全识别
     * https://q.qq.com/wiki/develop/miniprogram/server/open_port/port_safe.html#security-msgseccheck
     * result.errCode: 错误码，0 内容正常；87014 内容含有违法违规内容
     * result.errMsg: ok 内容正常；risky 内容含有违法违规内容
     *
     * @param content string 需检测的文本内容，文本字数的上限为2500字，需使用UTF-8编码
     *
     * @return array
     */
    public function msgSecCheck($content)
    {
        // 校验参数
        if ($content == '') {
            throw new DefaultException('缺少参数content', ErrorCodes::INVALID_PARAMS);
        }

        $postData = [
            'appid' => $this->appid,
            'content' => $content,
        ];

        try {
            $access_token = $this->requestAccessToken();
        } catch (DefaultException $e) {
            throw new DefaultException($e->getMessage(), $e->getCode());
        }

        $response = Http::post("https://api.q.qq.com/api/json/security/MsgSecCheck?access_token=" . $access_token, $postData);

        return $this->processResponse($response);
    }

    /**
     * 获取登录调用凭据
     */
    private function requestAccessToken()
    {
        // 校验是否设置登录调用凭据
        if ($this->access_token == '') {
            throw new DefaultException('未设置登录调用凭据', ErrorCodes::INVALID_PARAMS);
        }

        return $this->access_token;
    }

    /**
     * 处理响应
     */
    private function processResponse($response)
    {
        $data = [];

        if ($response->successful()) {
            $data = $response->json();
        } elseif ($response->failed()) {
            // 请求失败的处理逻辑
            throw new DefaultException($response->failed(), ErrorCodes::SERVICE_UNAVAILABLE);
        } elseif ($response->clientError()) {
            // 客户端错误 4xx 的处理逻辑
            throw new DefaultException($response->clientError(), ErrorCodes::SERVICE_UNAVAILABLE);
        } elseif ($response->serverError()) {
            // 服务器错误 5xx 的处理逻辑
            throw new DefaultException($response->serverError(), ErrorCodes::SERVICE_UNAVAILABLE);
        }

        return $data;
    }

}
