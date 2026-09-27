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

$blogtitle = '一个 zblog 插件';

require $blogpath . 'zb_system/admin/admin_header.php';

require $blogpath . 'zb_system/admin/admin_top.php';
?>
<link rel="stylesheet" href="https://unpkg.com/lu2/theme/edge/css/common/ui.css">
<script type="module" src="https://unpkg.com/lu2/theme/edge/js/common/all.js"></script>
<div id="divMain">
    <div class="divHeader">
        <?php echo $blogtitle; ?>
    </div>
    <div class="SubMenu"></div>
    <div id="divMain2">
        <?php
        zberOne_echoTabMe();
        ?>
    </div>
</div>

<?php
require $blogpath . 'zb_system/admin/admin_footer.php';
RunTime();
?>
