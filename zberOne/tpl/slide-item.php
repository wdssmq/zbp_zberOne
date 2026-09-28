<?php exit(); ?>
<!-- 「一个」标签页 - 单个来源（kind）的左右栏
     data-zber-ajax 是取该来源「条目级面板」的基址（act=ajax&src=zberOne&op=panel&kind=…&csrfToken=…），
     脚本补上 panel=<面板 id> 即可，整栏替换不会动到它 -->
<div class="zber-one-slide" data-kind="{$zberOneTabSlot['kind']}" data-zber-ajax="{zberOneTab_E($zberOneTabAjaxUrl)}">
    <div class="zber-one-head">
        <span class="zber-one-name">{zberOneTab_E($zberOneTabSlot['name'])}</span>
        {if $zberOneTabSlot['id'] != ''}<span class="zber-one-sid">id: {zberOneTab_E($zberOneTabSlot['id'])}</span>{/if}
    </div>
    <div class="zber-one-body">
        {template:plugin_zberOne_one-left}
        {template:plugin_zberOne_one-right}
    </div>
</div>
