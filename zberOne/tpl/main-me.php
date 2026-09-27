<?php exit(); ?>
<!-- 「我」标签页 - 右栏：内容面板（$zberMeTabPanels 数组循环生成） -->
<div class="zber-me-main">
    {foreach $zberMeTabPanels as $panel}
    <div class="zber-me-panel{if $panel['active']} active{/if}" id="{$panel['id']}">
        <h3>{zberMeTab_E($panel['title'])}</h3>
        {php} include $zbp->template->GetTemplate($panel['tpl']); {/php}
    </div>
    {/foreach}
</div>
