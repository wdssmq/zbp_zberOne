<?php exit(); ?>
<!-- 「一个」标签页 - 单个来源（kind）的左右栏 -->
<div class="zber-one-slide" data-kind="{$zberOneTabSlot['kind']}">
    <div class="zber-one-head">
        <span class="zber-one-name">{zberOneTab_E($zberOneTabSlot['name'])}</span>
        {if $zberOneTabSlot['id'] != ''}<span class="zber-one-sid">id: {zberOneTab_E($zberOneTabSlot['id'])}</span>{/if}
    </div>
    <div class="zber-one-body">
        {template:plugin_zberOne_menu-side}
        {template:plugin_zberOne_main-one}
    </div>
</div>
