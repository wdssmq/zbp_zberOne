<?php exit(); ?>
<!-- 面板 - one（我 / 他人，按 kind 取数；编辑入口只给「我」，无删除） -->
<div class="zber-one-box">
    <h3 class="zber-one-panel-title">
        {zberOneTab_E($panel['head'])}
        {if $panel['formPanel'] != ''}<a class="zber-one-op" href="javascript:;" data-zber-edit data-panel="{$panel['formPanel']}">编辑</a><a class="zber-one-op" href="javascript:;" data-zber-json>JSON 查看</a>{/if}
    </h3>
    {if count($panel['rows']) > 0}
    <dl>
        {foreach $panel['rows'] as $row}
        <dt>{zberOneTab_E($row['label'])}</dt>
        <dd class="zber-one-dd-{$row['key']}">{zberOneTab_E($row['value'])}</dd>
        {/foreach}
    </dl>
    {else}
    <p class="zber-one-empty">{zberOneTab_E($panel['empty'])}</p>
    {/if}
</div>
