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

$blogtitle = '一个 zblog 插件';
require $blogpath . 'zb_system/admin/admin_header.php';
require $blogpath . 'zb_system/admin/admin_top.php';
?>
<style>
  p {
    font-size: 16px;
  }

  p:not(:last-child) {
    margin-bottom: .3em;
  }

  blockquote {
    padding-left: .7rem;
    border-left: 3px solid #999;
    margin-left: .2rem;
  }

  hr {
    background-color: #f5f5f5;
    border: none;
    display: block;
    height: 2px;
    margin: 1.5rem 0;
    visibility: visible;
  }
</style>
<div id="divMain">
  <div class="divHeader">
    <?php echo $blogtitle; ?>
  </div>
  <div class="SubMenu"></small>
  </div>
  <div id="divMain2">
    <h3> 一个 QQ 群</h3>
    <p>群名称：一个 zblog 用户群</p>
    <p>群 号：859347813</p>
    <p>用 途：用于账号解封申请</p>
    <p>链 接：<?php echo zberOne_a("https://jq.qq.com/?_wv=1027&k=Ot3DJ5Xv", "一个 zblog 用户群"); ?></p>
    <p>验 证：<?php echo zberOne_Check(); ?></p>
    <p><b>↑↑请自己 Debug 出这个验证码为什么不会显示。</b></p>
    <hr>
    <p>
      插件地址：<?php echo zberOne_a("https://app.zblogcn.com/?id=15575", "一个 zblog 插件"); ?>
    </p>
    <blockquote>
      <h3>代码的重要本质是`证明`，然而人生更主要而又无奈的组成是`认为`！！！</h3>
      <p>
        推荐部动画电影：颠倒的帕特玛_番剧_bilibili_哔哩哔哩
        <a href="https://www.bilibili.com/bangumi/play/ss3668" target="_blank" title="颠倒的帕特玛：第1话_番剧_bilibili_哔哩哔哩">https://www.bilibili.com/bangumi/play/ss3668</a>
      </p>
    </blockquote>
    <hr>
    <!-- <h3>开发者申请</h3> -->
    <!-- <p>论坛发贴规范（申请开发者也先看这里）-论坛事务-ZBlogger技术交流中心</p> -->
    <!-- <p><a href="https://bbs.zblogcn.com/thread-102989.html" target="_blank" title="论坛发贴规范（申请开发者也先看这里）-论坛事务-ZBlogger技术交流中心">https://bbs.zblogcn.com/thread-102989.html</a></p> -->
    <h3>解封申请</h3>
    <p>
      Z-Blog 相关资讯 RSS 订阅
      <a href="https://bbs.zblogcn.com/thread-100631.html" target="_blank" title="Z-Blog 相关资讯 RSS 订阅">https://bbs.zblogcn.com/thread-100631.html</a> ←使用其他阅读器的可以在这里查看源地址
    </p>
    <p><b>↓↓注册并订阅，不需要手机验证；</b></p>
    <p>
      zblog 论坛订阅：<?php echo zberOne_a("https://feeds.pub/feed/https%3A%2F%2Fbbs.zblogcn.com%2Findex-0.html%3Frss%3D1", "zblog 论坛"); ?>
    </p>
    <p>
      应用中心订阅：<?php echo zberOne_a("https://feeds.pub/feed/https%3A%2F%2Fapp.zblogcn.com%2Ffeed.php", "应用中心"); ?>
    </p>
    <!-- <p>
      zblog 玩家：<a href="https://www.innoreader.com/bundle/0014cd640d60" target="_blank" title="zblog 玩家">https://www.innoreader.com/bundle/0014cd640d60</a>
    </p> -->
    <p><b>↑↑注册并订阅，不需要手机验证；</b></p>
    <p>----</p>
    <p>0、大部分只是禁言而非直接踢掉的基本是因为没正确标注 z-blog 版权。<b>你想让人看到你的站，而我确实看到了，并用相应的行动证明我看到了，仅此而已。</b></p>
    <p>1、注册「feeds.pub」并订阅上边源。（并不限于这个 RSS 阅读器，Feedly，Inoreader 等都可以，手机可以用 Rolly）</p>
    <p>2、加 QQ 群附图；</p>
    <p><b>3、因擅自分享应用等原因造成的封号不予解封；</b></p>
    <p>----</p>
    <p><b>需附图：（任意 web 或客户端 RSS 阅读器均可）</b></p>
    <p><img src="doc/001.png" alt="截图"></p>
  </div>
</div>

<?php
require $blogpath . 'zb_system/admin/admin_footer.php';
RunTime();
?>
