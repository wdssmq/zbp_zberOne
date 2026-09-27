<?php

require_once __DIR__ . '/func.php';
// usr-data 数据读取类
require_once __DIR__ . '/class/zberOne_base.php';
// 注册插件
RegisterPlugin('zberOne', 'ActivePlugin_zberOne');

function ActivePlugin_zberOne()
{
    Add_Filter_Plugin('Filter_Plugin_Zbp_BuildTemplate', 'zberOne_GenTpl');
    // 后台「我」的数据写入走 cmd.php 自带的 ajax 接口（act=ajax&src=zberOne）
    Add_Filter_Plugin('Filter_Plugin_Cmd_Ajax', 'zberOne_CmdAjax');
}

function zberOne_GenTpl(&$templates)
{
    $tplDir = zberOne_Path('tpl');
    $tplFiles = GetFilesInDir($tplDir, 'php');
    foreach ($tplFiles as $tplFile) {
        $tplCont = file_get_contents($tplFile);
        $tplName = 'plugin_zberOne_' . basename($tplFile, '.php');
        $templates[$tplName] = $tplCont;
    }
}

function InstallPlugin_zberOne()
{
    global $zbp;

    // 安装时生成数据文件（已有文件不覆盖）
    $data = new zberOne_base();
    $data->InitFiles();

    if (!$zbp->HasConfig('zberOne')) {
        $zbp->Config('zberOne')->version = '2026.09.26';
        $zbp->SaveConfig('zberOne');
    }
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
