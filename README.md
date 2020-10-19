<h1>配置</h1>

<h2>install</h2>

    composer require jsanf/oauth
    
<h2>config.php</h2>

    return [
        'Wechat' => [
            'appid' => 'wxedfaa675aa',        //个人微信appid
            'secret'=> '0a4ab6706702585c043c3d70245',      
            'redirect_uri' => 'https://member-sso.pxo.cn/user/receive'  //回调地址
        ],
        'Wxwork' => [
            'agentid' => '1000170',
            'redirect_uri' => 'https://member-sso.pxo.cn/user/receive',     //回调地址
    
            //第三方应用
            'appid' => 'wwe394e1149a61',                                //suiteId
            'secret' => 'lQUGcC-5yScSKPGnheKuwB7CMCUOPk4ZM',      //suite_secret
            'token' => 'XPOLhzU227GemS',
            'EncodingAESKey' => '1QBd6iL5qKsjY0HsBA4vPtwBm8NSRz',
    
            //suite_ticket 缓存名称
            'suiteTicketCacheKey' => 'member:suite_ticket'
        ]
    ]
    
    
<h2>simple</h2>
    
    
        /**
         * 第三方登录
         */
        public function third()
        {
            $oauth = (new Oauth)->getInstance("Wechat", $config['Wechat'] ?? '');
            if(!$oauth) {
                echo "该登录方式不存在.";
                exit;
            }
            $param['return_url'] = $this->get['return_url'];
            $param['oauth_type'] = 'Wechat';
            //$param['scope'] = $this->get['scope'] ?? 'snsapi_base';
            $oauth->code($param);
        }
    
        /**
         * 第三方回调
         * @throws \GuzzleHttp\Exception\GuzzleException
         */
        public function receive()
        {
            $config = $config['Wechat'] ?? [];
            if(!$config) {
                echo 'oauth_type有误.';exit;
            }
    
            $config['return_data'] = $this->get;
            $oauth = (new Oauth)->getInstance('Wechat', $config);
            $userInfo = $oauth->userInfo();
    
            $token = $userInfo['openid'];
    
            //保存用户信息
    
            //缓存用户信息到redis缓存
            $this->redis::set('member:'.$token, $userInfo);
    
            header("Location:" . $_GET['return_url'] . '?token=' . $token);
        }
