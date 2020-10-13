<?php
/**
 * 个人微信授权登录
 * User: zhangzhongxian
 * Date: 2020/9/30 14:32
 */

namespace zhangzhongxian\oauth;


use GuzzleHttp\Client;

class Wechat implements OauthBase
{

    private $appid;
    private $secret;
    private $redirect_uri;
    private $code;
    private $access_token;
    private $openid;

    const CODE_URL = "https://open.weixin.qq.com/connect/oauth2/authorize";
    const ACCESS_TOKEN_URL = "https://api.weixin.qq.com/sns/oauth2/access_token";
    const USER_INFO_URL = "https://api.weixin.qq.com/sns/userinfo";

    public function __construct(array $config)
    {
        $this->appid = $config['appid'];
        $this->secret = $config['secret'];
        $this->redirect_uri = $config['redirect_uri'];
        $this->code = $config['return_data']['code'] ?? '';
    }

    public function code($param): string
    {
        $params = [
            'appid' => $this->appid,
            'redirect_uri' => $this->redirect_uri . '?' . http_build_query($param),
            'response_type'=> 'code',
            'scope' => 'snsapi_userinfo'
        ];
        $url = self::CODE_URL . '?' . http_build_query($params) . '#wechat_redirect';
        header("Location:".$url);
        exit;
    }

    /**
     * 获取用户信息
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function userInfo(): array
    {
        $this->accessToken();
        $params = [
            'appid' => $this->appid,
            'secret' => $this->secret,
            'access_token' => $this->access_token,
            'openid' => $this->openid,
            'lang' => 'zh_CN'
        ];
        $client = new Client();
        $response = $client->request('get', self::USER_INFO_URL . '?' . http_build_query($params))->getBody()->getContents();
        $response = json_decode($response, 1);

        $userinfo = [
            'openid'  => $response['openid'],
            'username' => $response['nickname'],
            'head'  => $response['headimgurl'],
            'gender'  => $response['sex'],
            'third_user_info' => json_encode($response)
        ];
        return $userinfo;
    }


    public function accessToken(): string
    {
        if(!$this->access_token) {
            $params = [
                'appid' => $this->appid,
                'secret' => $this->secret,
                'code' => $this->code,
                'grant_type' => 'authorization_code'
            ];
            $client = new Client();
            $response = $client->request('get', self::ACCESS_TOKEN_URL . '?' . http_build_query($params))->getBody()->getContents();
            $response = json_decode($response, 1);
            $this->access_token = $response['access_token'] ?? '';
            $this->openid = $response['openid'] ?? '';
        }
        return $this->access_token;
    }


}