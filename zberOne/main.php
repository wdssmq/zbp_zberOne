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
<link rel="stylesheet" href="https://unpkg.com/lu2/theme/edge/css/common/ui/Tab.css">
<link rel="stylesheet" href="https://unpkg.com/lu2/theme/edge/css/common/form.css">
<link rel="stylesheet" href="https://unpkg.com/lu2/theme/edge/css/common/ui/Tips.css">
<link rel="stylesheet" href="https://unpkg.com/lu2/theme/edge/css/common/ui/Table.css">
<link rel="stylesheet" href="<?php echo zberOne_Path('style/style.css', 'host'); ?>">
<script type="module" src="https://unpkg.com/lu2/theme/edge/js/common/ui/Tab.js"></script>
<script type="module" src="https://unpkg.com/lu2/theme/edge/js/common/ui/Validate.js"></script>
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
    </div>
</div>

<script src="<?php echo zberOne_Path('style/main.js', 'host'); ?>"></script>

<?php
require $blogpath . 'zb_system/admin/admin_footer.php';
RunTime();
?>
