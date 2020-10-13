<?php
/**
 * 企业微信第三方应用开发-授权登录
 * User: zhangzhongxian
 * Date: 2020/10/9 15:27
 */

namespace zhangzhongxian\oauth;


use GuzzleHttp\Client;
use redis\RedisCache;
use oauth\FileCache;

class Wxwork implements OauthBase
{
    private $appid;
    private $secret;
    private $redirect_uri;
    private $agentid;
    private $code;
    private $suite_ticket;
    private $user_ticket;
    private $access_token;
    private $openid;
    const CODE_URL = "https://open.weixin.qq.com/connect/oauth2/authorize";
    const ACCESS_TOKEN_URL = "https://qyapi.weixin.qq.com/cgi-bin/service/get_suite_token";
    const USER_INFO_URL = "https://qyapi.weixin.qq.com/cgi-bin/service/getuserinfo3rd";
    const USER_INFO_DETAIL_URL = "https://qyapi.weixin.qq.com/cgi-bin/service/getuserdetail3rd";

    public function __construct(array $config)
    {
        $this->appid = $config['appid'];
        $this->secret = $config['secret'];
        $this->redirect_uri = $config['redirect_uri'] ?? '';
        $this->suite_ticket = $config['suite_ticket'] ?? '';
        $this->agentid = $config['agentid'] ?? '';
        $this->code = $config['return_data']['code'] ?? '';
    }

    public function code($param): string
    {
        $params = [
            'appid' => 'ww3e4fc9b495ff0fd8',
            'redirect_uri' => $this->redirect_uri . '?' . http_build_query($param),
            'response_type'=> 'code',
            'scope' => 'snsapi_userinfo',
            //'agentid' => $this->agentid
        ];
        $url = self::CODE_URL . '?' . http_build_query($params) . '#wechat_redirect';
        header("Location:".$url);
        exit;
    }

    /**
     * @return string
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function accessToken(): string
    {
        $fileCache = new FileCache('/tmp', 100, 100);
        $this->access_token = $fileCache->get('suite_access_token');
        if(!$this->access_token) {
            $params = [
                'suite_id' => $this->appid,
                'suite_secret' => $this->secret,
                'suite_ticket' => $this->suite_ticket
            ];
            $option = [
                'body' => json_encode($params)
            ];
            $client = new Client();
            $response = $client->request('post', self::ACCESS_TOKEN_URL, $option)->getBody()->getContents();
            $response = json_decode($response, 1);
            if(!isset($response['suite_access_token']) || !$response['suite_access_token']) {
                throw new \Exception('suite access token not exist.');
            }
            $this->access_token = $response['suite_access_token'];
            $fileCache->set('suite_access_token', $this->access_token, $response['expires_in']);
        }
        return $this->access_token;
    }

    /**
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function userInfo(): array
    {
        $params = [
            'suite_access_token' => $this->accessToken(),
            'code' => $this->code
        ];
        $url = self::USER_INFO_URL . '?' . http_build_query($params);
        $client = new Client();
        $response = $client->request('get', $url)->getBody()->getContents();
        $response = json_decode($response, 1);
        if ($response['errcode'] != 0) {
            throw new \Exception($response['errmsg']);
        }

        //非企业用户
        if(isset($response['OpenId'])) {
            return ['openid' => $response['OpenId']];
        }

        //企业用户通过user_ticker获取用户信息
        $params = [
            'user_ticket' => $response['user_ticket'] ?? ''
        ];
        $response = $client->request('post', self::USER_INFO_DETAIL_URL, ['body' => json_encode($params)])->getBody()->getContents();
        $response = json_decode($response, 1);
        if ($response['errcode'] != 0) {
            throw new \Exception($response['errmsg']);
        }
        $userinfo = [
            'openid' => $response['open_userid'] ?? $response['OpenId'],
            'username' => $response['name'],
            'head'  => $response['avatar'],
            'gender'  => $response['gender'],
            'third_user_info' => json_encode($response)
        ];

        return $userinfo;
    }

}