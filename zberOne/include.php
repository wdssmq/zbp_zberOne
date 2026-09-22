<?php

//注册插件
RegisterPlugin('zberOne', 'ActivePlugin_zberOne');

function ActivePlugin_zberOne()
{
}

function InstallPlugin_zberOne()
{
    global $zbp;
    $zbp->BuildTemplate();
}

function UninstallPlugin_zberOne()
{
    global $zbp;
    $zbp->BuildTemplate();
}

// function zberOne_Http()
// {
//   $url = "https://api.github.com/repos/zblogcn/zblogphp";
//   $http = Network::Create();
//   $http->open('GET', $url);
//   $http->send();
//   if ($http->status == 200) {
//     $s = $http->responseText;
//     if ($data = json_decode($s)) {
//       $int = 100 + intval($data->watchers / 37);
//       return $int;
//     }
//   }
//   return $http->status;
// }
