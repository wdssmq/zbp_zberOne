<?php exit(); ?>
<!-- 面板 - 组：点组标题显示，把该组全部子项列成表格；编辑/删除/添加入口只给「我」
     表格自带边框，放在标题框外面（否则视觉上「框里还有框」） -->
<div class="zber-one-box">
    <h3 class="zber-one-panel-title">
        {zberOneTab_E($panel['head'])}
        {if $panel['addUrl'] != ''}<a class="zber-one-op" href="#" data-zber-add data-panel="{$panel['addUrl']}">添加</a>{/if}
    </h3>

    {if count($panel['items']) === 0}
    <p class="zber-one-empty">{zberOneTab_E($panel['empty'])}</p>
    {/if}
</div>

{if count($panel['items']) > 0}
<table class="ui-table" width="100%">
    <thead>
    <tr>
        <th>{zberOneTab_E($panel['colHead'])}</th>
        <th width="100">操作</th>
    </tr>
    </thead>
    <tbody>
    {foreach $panel['items'] as $row}
    <tr>
        <td>{zberOneTab_E($row['title'])}</td>
        <td>
            {if $row['formPanel'] != ''}<a class="zber-one-op" href="#" data-zber-edit data-panel="{$row['formPanel']}" data-zber-idx="{$row['editIdx']}">编辑</a>{/if}
            {if $row['delUrl'] != ''}<a class="zber-one-op" href="{zberOneTab_E($row['delUrl'])}" data-zber-del data-type="{$panel['type']}" data-idx="{$row['idx']}">删除</a>{/if}
        </td>
    </tr>
    {/foreach}
    </tbody>
</table>
{/if}
