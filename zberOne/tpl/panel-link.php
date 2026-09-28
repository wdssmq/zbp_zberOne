<?php exit(); ?>
<!-- 面板 - 我的文章 / 我的视频：一条一个面板（展示 + 编辑/删除入口） -->
<div class="zber-one-box">
    {if $panel['head'] != ''}
    <h3 class="zber-one-panel-title">
        {zberOneTab_E($panel['head'])}
        {if $panel['formPanel'] != ''}<a class="zber-one-op" href="#" data-zber-edit data-panel="{$panel['formPanel']}" data-zber-idx="{$panel['editIdx']}">编辑</a>{/if}
        {if $panel['delUrl'] != ''}<a class="zber-one-op" href="{zberOneTab_E($panel['delUrl'])}" data-zber-del data-type="{$panel['type']}" data-idx="{$panel['idx']}">删除</a>{/if}
    </h3>
    {/if}
    {if $panel['url'] != ''}
    <p>链接：<a href="{zberOneTab_E($panel['url'])}" target="_blank" rel="noopener">{zberOneTab_E($panel['url'])}</a></p>
    {/if}
</div>
