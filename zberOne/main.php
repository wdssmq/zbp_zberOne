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

  // 切换右栏面板：所有面板都已由模板真实渲染，这里只切 .active 显隐
  function showPanel(slide, panelId) {
    if (!panelId) {
      return;
    }
    var panels = slide.querySelectorAll('.zber-one-panel');
    for (var i = 0; i < panels.length; i++) {
      if (panels[i].id === panelId) {
        panels[i].classList.add('active');
      } else {
        panels[i].classList.remove('active');
      }
    }
  }

  // 从点击目标向上找祖先（跨自定义元素边界，closest() 在 <ui-tab> 上不可靠）
  function closestOf(el, selector, boundary) {
    var node = el;
    while (node && node !== boundary) {
      if (node.matches && node.matches(selector)) {
        return node;
      }
      node = node.parentNode;
    }

    return null;
  }

  // 从点击目标向上找到左栏里的「子项」或「主项目标题」
  function closestSide(el, slide) {
    var node = el;
    while (node && node !== slide) {
      if (node.classList && node.classList.contains('zber-one-item')) {
        return { item: node, trigger: null };
      }
      if (node.tagName === 'UI-TAB') {
        return { item: null, trigger: node };
      }
      node = node.parentNode;
    }

    return null;
  }

  // 点击左栏：主项目标题（dt）与子项都带 data-panel，指向要显示的右栏面板
  function onSideClick(el, slide) {
    var hit = closestSide(el, slide);
    if (!hit) {
      return;
    }
    var node = hit.item || hit.trigger;
    var target = closestOf(node, '[data-panel]', slide);
    if (target) {
      showPanel(slide, target.getAttribute('data-panel'));
    }
  }

  root.addEventListener('click', function (e) {
    var slide = closestOf(e.target, '.zber-one-slide', root);
    if (!slide) {
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
    onSideClick(e.target, slide);
  });

  // 默认显示第一个来源（我）
  activate(0);
})();
</script>

<?php
require $blogpath . 'zb_system/admin/admin_footer.php';
RunTime();
?>
