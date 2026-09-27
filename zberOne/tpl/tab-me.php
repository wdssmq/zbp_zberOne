<?php exit(); ?>
<!-- 「我」标签页 - 外层容器（左右双栏） -->
<link rel="stylesheet" href="{$zberMeTabStyle}">
<div id="zber-me-root" class="zber-me-wrap">
    {template:plugin_zberOne_menu-side}
    {template:plugin_zberOne_main-me}
</div>

{pre}<script>
(function() {
  var root = document.getElementById('zber-me-root');
  if (!root) {
    return;
  }
  root.addEventListener('click', function(e) {
    var el = e.target.closest('[data-panel]');
    if (!el || !root.contains(el)) {
      return;
    }
    var panels = root.querySelectorAll('.zber-me-panel');
    for (var i = 0; i < panels.length; i++) {
      panels[i].classList.remove('active');
    }
    var target = document.getElementById(el.getAttribute('data-panel'));
    if (target) {
      target.classList.add('active');
    }
    var items = root.querySelectorAll('.zber-me-item');
    for (var j = 0; j < items.length; j++) {
      items[j].classList.remove('active');
    }
    if (el.classList.contains('zber-me-item')) {
      el.classList.add('active');
    }
  });
})();
</script>{/pre}
