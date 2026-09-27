<?php exit(); ?>
<!-- 「我」标签页 - 左栏：层级菜单（$zberMeTabMenu 数组循环生成） -->
<div class="zber-me-side">
    {foreach $zberMeTabMenu as $menuGroup}
    <details class="zber-me-group"{if $menuGroup['open']} open{/if}>
        <summary{if $menuGroup['panel'] != ''} data-panel="{$menuGroup['panel']}"{/if}>{zberMeTab_E($menuGroup['title'])}</summary>
        {if count($menuGroup['items']) > 0}
        {foreach $menuGroup['items'] as $menuItem}
        <div class="zber-me-item" data-panel="{$menuItem['panel']}">{zberMeTab_E($menuItem['title'])}</div>
        {/foreach}
        {elseif $menuGroup['empty'] != ''}
        <div class="zber-me-item zber-me-item-empty">{zberMeTab_E($menuGroup['empty'])}</div>
        {/if}
    </details>
    {/foreach}
</div>
