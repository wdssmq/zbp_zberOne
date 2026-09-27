<?php exit(); ?>
<!-- 「一个」标签页 - 左栏：层级菜单（$zberOneTabMenu 数组循环生成）
     展开收起用 LuLu UI 的 <ui-tab>：target 指向 <dt>，name 相同的组互斥展开（手风琴），
     .active 由 <ui-tab> 加在 <dt> 上，配合 dt.active + dd 控制 dd 的高 -->
<div class="zber-one-side">
    <dl>
        {foreach $zberOneTabMenu as $menuGroup}
        <dt class="zber-one-group{if $menuGroup['open']} active{/if}" id="{$menuGroup['id']}">
            <ui-tab target="{$menuGroup['id']}" name="{$menuGroup['name']}"{if $menuGroup['open']} open{/if}{if $menuGroup['panel'] != ''} data-panel="{$menuGroup['panel']}"{/if}>{zberOneTab_E($menuGroup['title'])}</ui-tab>
        </dt>
        <dd>
            <div class="zber-one-items">
                {if count($menuGroup['items']) > 0}
                {foreach $menuGroup['items'] as $menuItem}
                <div class="zber-one-item" data-panel="{$menuItem['panel']}">{zberOneTab_E($menuItem['title'])}</div>
                {/foreach}
                {elseif $menuGroup['empty'] != ''}
                <div class="zber-one-item zber-one-item-empty">{zberOneTab_E($menuGroup['empty'])}</div>
                {/if}
            </div>
        </dd>
        {/foreach}
    </dl>
</div>
