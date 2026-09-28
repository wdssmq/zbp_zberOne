<?php exit(); ?>
<!-- 面板 - 组：点组标题显示，列出该组全部子项；编辑/删除/添加入口只给「我」 -->
<h3 class="zber-one-panel-title">
    {zberOneTab_E($panel['head'])}
    {if $panel['addUrl'] != ''}<a class="zber-one-op" href="#" data-zber-add data-panel="{$panel['addUrl']}">添加</a>{/if}
</h3>
{if count($panel['items']) > 0}
<ul class="zber-one-list">
    {foreach $panel['items'] as $row}
    <li>
        {zberOneTab_E($row['title'])}
        {if $row['formPanel'] != ''}<a class="zber-one-op" href="#" data-zber-edit data-panel="{$row['formPanel']}" data-zber-idx="{$row['editIdx']}">编辑</a>{/if}
        {if $row['delUrl'] != ''}<a class="zber-one-op" href="{zberOneTab_E($row['delUrl'])}" data-zber-del data-type="{$panel['type']}" data-idx="{$row['idx']}">删除</a>{/if}
    </li>
    {/foreach}
</ul>
{else}
<p class="zber-one-empty">{zberOneTab_E($panel['empty'])}</p>
{/if}
