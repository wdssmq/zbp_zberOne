<?php
function zberOne_Path($file = '', $t = 'path')
{
  global $zbp;
  static $paths = array(
    'main' => 'main.php',
    'tpl' => 'tpl/',
    'u-data'  => 'usr-data/',
  );
  $base = $zbp->$t . 'zb_users/plugin/zberOne/';
  return $base . (isset($paths[$file]) ? $paths[$file] : $file);
}
