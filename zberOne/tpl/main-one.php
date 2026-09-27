<?php exit(); ?>
<!-- 「一个」标签页 - 右栏：内容面板（$zberOneTabPanels 数组循环） -->
<div class="zber-one-main">
    {foreach $zberOneTabPanels as $panel}
    <div class="zber-one-panel{if $panel['active']} active{/if}" id="{$panel['id']}" data-panel="{$panel['id']}">
        {php} include $zbp->template->GetTemplate($panel['tpl']); {/php}
    </div>
    {/foreach}
</div>
