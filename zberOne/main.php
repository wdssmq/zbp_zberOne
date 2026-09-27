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
<link rel="stylesheet" href="<?php echo zberOne_Path('style/style.css', 'host'); ?>">
<script type="module" src="https://unpkg.com/lu2/theme/edge/js/common/all.js"></script>
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

<script>
(function () {
  var root = document.getElementById('zber-one-root');
  if (!root) {
    return;
  }
  var track = root.querySelector('.zber-one-track');
  var slides = root.querySelectorAll('.zber-one-slide');

  function activate(index) {
    for (var i = 0; i < slides.length; i++) {
      var slide = slides[i];
      if (i === index) {
        slide.classList.add('active');
      } else {
        slide.classList.remove('active');
      }
      // 激活项左侧的来源向左渐隐、右侧的向右渐隐，遮罩方向相反不能共用
      if (i < index) {
        slide.classList.add('is-before');
        slide.classList.remove('is-after');
      } else if (i > index) {
        slide.classList.add('is-after');
        slide.classList.remove('is-before');
      } else {
        slide.classList.remove('is-before', 'is-after');
      }
    }
    if (track) {
      // 左移 index 个 slide 宽，再右移 index 个漏出宽度：
      // 激活项占据主要空间，前一个来源在左侧漏出一条、后一个在右侧漏出一条
      // （漏出宽度见 style.css 的 --zber-one-peek）
      track.style.transform = 'translateX(calc(' + index + ' * var(--zber-one-peek) - ' + index + ' * 100%))';
    }
  }

  root.addEventListener('click', function (e) {
    var slide = e.target.closest('.zber-one-slide');
    if (!slide || !root.contains(slide)) {
      return;
    }
    var index = Array.prototype.indexOf.call(slides, slide);
    if (index < 0) {
      return;
    }
    // 点到右侧漏出的那一部分（未激活的来源）→ 滑动切换
    if (!slide.classList.contains('active')) {
      activate(index);
      return;
    }
    // 激活的左右栏内 → 切换面板
    var el = e.target.closest('[data-panel]');
    if (!el) {
      return;
    }
    var panels = slide.querySelectorAll('.zber-one-panel');
    for (var i = 0; i < panels.length; i++) {
      panels[i].classList.remove('active');
    }
    var target = document.getElementById(el.getAttribute('data-panel'));
    if (target) {
      target.classList.add('active');
    }
    var items = slide.querySelectorAll('.zber-one-item');
    for (var j = 0; j < items.length; j++) {
      items[j].classList.remove('active');
    }
    if (el.classList.contains('zber-one-item')) {
      el.classList.add('active');
    }
  });

  // 默认显示第一个来源（我）
  activate(0);
})();
</script>

<?php
require $blogpath . 'zb_system/admin/admin_footer.php';
RunTime();
?>
