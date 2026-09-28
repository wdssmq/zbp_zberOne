<?php exit(); ?>
<!-- 「一个」标签页 - 右栏：内容面板（$zberOneTabPanels 数组循环；只含组级面板，
     条目级面板点开时才由 zberOne_RenderPanel 取回，单块写法见 one-panel） -->
<div class="zber-one-main">
    {foreach $zberOneTabPanels as $panel}
    {php} include $zbp->template->GetTemplate('plugin_zberOne_one-panel'); {/php}
    {/foreach}
</div>
