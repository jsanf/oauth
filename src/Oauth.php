<?php
/**
 * User: zhangzhongxian
 * Date: 2020/9/30 14:27
 */

namespace zhangzhongxian;

class Oauth
{

    public function getInstance($classname, $config)
    {
        $path = "\\oauth\\oauth\\".$classname;

        if(!class_exists($path)){
            return false;
        }

        return new $path($config);
    }


}