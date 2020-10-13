<?php
/**
 * User: zhangzhongxian
 * Date: 2020/10/9 9:03
 */

namespace zhangzhongxian\oauth;


interface OauthBase
{

    public function __construct(array $config);

    public function code($param): string;

    //获取第三方用户信息
    public function userInfo(): array;

    //获取accessToken
    public function accessToken(): string;

}
