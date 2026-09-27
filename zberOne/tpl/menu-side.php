<?php exit(); ?>
<!-- 「一个」标签页 - 左栏：层级菜单（$zberOneTabMenu 数组循环生成） -->
<div class="zber-one-side">
    {foreach $zberOneTabMenu as $menuGroup}
    <details class="zber-one-group"{if $menuGroup['open']} open{/if}>
        <summary{if $menuGroup['panel'] != ''} data-panel="{$menuGroup['panel']}"{/if}>{zberOneTab_E($menuGroup['title'])}</summary>
        {if count($menuGroup['items']) > 0}
        {foreach $menuGroup['items'] as $menuItem}
        <div class="zber-one-item" data-panel="{$menuItem['panel']}">{zberOneTab_E($menuItem['title'])}</div>
        {/foreach}
        {elseif $menuGroup['empty'] != ''}
        <div class="zber-one-item zber-one-item-empty">{zberOneTab_E($menuGroup['empty'])}</div>
        {/if}
    </details>
    {/foreach}
</div>
