<?php
#注册插件
RegisterPlugin("zberOne", "ActivePlugin_zberOne");

function ActivePlugin_zberOne()
{
  Add_Filter_Plugin('Filter_Plugin_Zbp_BuildTemplate', 'zberOne_Temp');
}
function zberOne_Temp(&$templates)
{
  // 给开发者：不要跨文件闭合 HTML 标签： https://bbs.zblogcn.com/thread-101310.html#484040
  // 以及 footer 中的 {$footer} 标记也不要省；
  $templates['footer'] = str_replace('{$footer}', '{$footer}' .  '<script src="' . zberOne_Path("script", "host") . '"></script>', $templates['footer']);
}
function zberOne_Path($file, $t = 'path')
{
  global $zbp;
  $result = $zbp->$t . 'zb_users/plugin/zberOne/';
  switch ($file) {
    case 'script':
      return $result . 'script/plugin.js';
      break;
    case 'usr':
      return $result . 'usr/';
      break;
    case 'var':
      return $result . 'var/';
      break;
    case 'main':
      return $result . 'main.php';
      break;
    default:
      return $result . $file;
  }
}
function zberOne_a($href, $title, $text = "")
{
  if (empty($text)) {
    $text = $href;
  }
  return "<a href=\"{$href}\" target=\"_blank\" title=\"{$title}\">$text</a>";
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

function zberOne_Check()
{
  global $zbp;
  $zberOne = GetVars("zberOne", "COOKIE");
  if ($zberOne !== "pass") {
    $href = "{$zbp->host}#zberOne";
    return zberOne_a($href, "点击获取验证码", "点击获取验证码");
  }
  $last = $zbp->config("zberOne")->last;
  $cur = date("YmdH");
  if ($last == $cur) {
    return $zbp->config("zberOne")->data;
  } else {
    $rlt = zberOne_Http();
    if ($rlt == "err") {
      return "获取失败";
    }
    $zbp->config("zberOne")->data = $rlt;
    $zbp->config("zberOne")->last = $cur;
    $zbp->SaveConfig('zberOne');
    return $zbp->config("zberOne")->data;
  }
}
function zberOne_Http()
{
  $url = "https://api.github.com/repos/zblogcn/zblogphp";
  $http = Network::Create();
  $http->open('GET', $url);
  $http->send();
  if ($http->status == 200) {
    $s = $http->responseText;
    if ($data = json_decode($s)) {
      $int = 100 + intval($data->watchers / 37);
      return $int;
    }
  }
  return $http->status;
}
