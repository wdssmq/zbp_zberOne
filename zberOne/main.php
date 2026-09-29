<?php

/** @var string $blogpath */
require '../../../zb_system/function/c_system_base.php';

require '../../../zb_system/function/c_system_admin.php';
$zbp->Load();
$action = 'root';
if (!$zbp->CheckRights($action)) {
    $zbp->ShowError(6);

    exit();
}
if (!$zbp->CheckPlugin('zberOne')) {
    $zbp->ShowError(48);

    exit();
}

InstallPlugin_zberOne();

$blogtitle = '一个 zblog 插件';

require $blogpath . 'zb_system/admin/admin_header.php';

require $blogpath . 'zb_system/admin/admin_top.php';
?>
<!-- lu2 为本地副本，来源与升级方式见 assets/lu2/SOURCE.md -->
<link rel="stylesheet" href="<?php echo zberOne_Path('assets/lu2/Tab.css', 'host'); ?>">
<link rel="stylesheet" href="<?php echo zberOne_Path('assets/lu2/form.css', 'host'); ?>">
<link rel="stylesheet" href="<?php echo zberOne_Path('assets/lu2/Tips.css', 'host'); ?>">
<link rel="stylesheet" href="<?php echo zberOne_Path('assets/lu2/Table.css', 'host'); ?>">
<link rel="stylesheet" href="<?php echo zberOne_Path('style/style.css', 'host'); ?>">
<script type="module" src="<?php echo zberOne_Path('assets/lu2/Tab.js', 'host'); ?>"></script>
<script type="module" src="<?php echo zberOne_Path('assets/lu2/Validate.js', 'host'); ?>"></script>
<div id="divMain">
    <div class="divHeader">
        <?php echo $blogtitle; ?>
    </div>
    <div class="SubMenu"></div>
    <div id="divMain2">
        <div id="zber-one-root" class="zber-one-wrap">
            <div class="zber-one-pager">
                <div class="zber-one-track">
                    <?php
                    // 每个来源（me / other）渲染成一套可左右滑动的左右栏
                    foreach (zberOne_GetTabOneSlots() as $slot) {
                        zberOne_echoTabOne($slot['kind']);
                    }
                    ?>
                </div>
            </div>
        </div>
        <div id="zber-one-footer">
            <p>提交站点请点下边页面：</p>
            <p><a href="https://github.com/wdssmq/zbp_zberOne/issues" target="_blank" title="Issues · wdssmq/zbp_zberOne">https://github.com/wdssmq/zbp_zberOne/issues</a></p>
        </div>
    </div>
</div>

<script src="<?php echo zberOne_Path('style/main.js', 'host'); ?>"></script>

<?php
require $blogpath . 'zb_system/admin/admin_footer.php';
RunTime();
?>
