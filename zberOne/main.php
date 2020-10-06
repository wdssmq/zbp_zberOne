<?php
require '../../../zb_system/function/c_system_base.php';
require '../../../zb_system/function/c_system_admin.php';
$zbp->Load();
$action = 'root';
if (!$zbp->CheckRights($action)) {
  $zbp->ShowError(6);
  die();
}
if (!$zbp->CheckPlugin('zberOne')) {
  $zbp->ShowError(48);
  die();
}

$blogtitle = '一个zblog插件';
require $blogpath . 'zb_system/admin/admin_header.php';
require $blogpath . 'zb_system/admin/admin_top.php';
?>
<div id="divMain">
  <div class="divHeader"><?php echo $blogtitle; ?><small><a title="刷新" href="main.php" style="font-size: 16px;display: inline-block;margin-left: 5px;">刷新</a></div>
  <div class="SubMenu"></small>
  </div>
  <div id="divMain2">
    <p>群名称：一个zblog用户群</p>
    <p>群 号：859347813</p>
    <p>链接：<?php echo zberOne_a("https://jq.qq.com/?_wv=1027&k=Ot3DJ5Xv", "一个zblog用户群"); ?></p>
    <p>验证：<?php echo zberOne_Check(); ?></p>
    <h3>代码的重要本质是`证明`，然而人生更主要而又无奈的组成是`认为`！！！</h3>
    <p>
      推荐部动画电影：颠倒的帕特玛_番剧_bilibili_哔哩哔哩
      <a href="https://www.bilibili.com/bangumi/play/ss3668" target="_blank" title="颠倒的帕特玛：第1话_番剧_bilibili_哔哩哔哩">https://www.bilibili.com/bangumi/play/ss3668</a></p>
    <p>-------</p>
    <p>
      插件地址：<?php echo zberOne_a("https://app.zblogcn.com/?id=15575", "一个zblog插件"); ?>
    </p>
    <p>
      Z-Blog相关资讯RSS订阅
      <a href="https://bbs.zblogcn.com/thread-100631.html" target="_blank" title="Z-Blog相关资讯RSS订阅">https://bbs.zblogcn.com/thread-100631.html</a> ←使用其他阅读器的可以在这里查看源地址
    </p>
    <p>
      zblog贴吧订阅：<?php echo zberOne_a("https://feeds.pub/feed/https%3A%2F%2Frsshub.app%2Ftieba%2Fforum%2Fzblog", "zblog贴吧"); ?> ← 非产出型的贴子建议发在贴吧里
    </p>
    <p>
      应用中心订阅：<?php echo zberOne_a("https://feeds.pub/feed/https%3A%2F%2Fapp.zblogcn.com%2Ffeed.php", "应用中心"); ?>
    </p>
    <p>
      zblog论坛订阅：<?php echo zberOne_a("https://feeds.pub/feed/https%3A%2F%2Fbbs.zblogcn.com%2Findex-0.html%3Frss%3D1", "zblog论坛"); ?>
    </p>
    <p>----</p>
    <p>1、[注册feeds.pub并订阅上边源]。（并不限于这个RSS阅读器，Feedly，Inoreader等都可以，手机可以用Rolly）</p>
    <p>2、按如下格式在<a href="https://tieba.baidu.com/f?kw=zblog&ie=utf-8" target="_blank" title="zblog吧-百度贴吧">[zblog吧-百度贴吧]</a>发贴</p>
    <p>标题：</p>
    <p>[申请解封][zblog论坛]：论坛名</p>
    <p>或者</p>
    <p>[申请解封][QQ群]：群昵移</p>
    <p><s>群昵称和feeds.pub账号统一更容易通过</s>，人工确认太麻烦所以划掉，写代码也很麻烦</p>
    <p>需附图：（任意web或客户端RSS阅读器均可）</p>
    <p><img src="doc/001.png" alt="截图"></p>

  </div>
</div>

<?php
require $blogpath . 'zb_system/admin/admin_footer.php';
RunTime();
?>