<?php exit(); ?>
<!-- 面板 - 添加 / 编辑表单（添加与编辑复用同一块，编辑预填现值） -->
<div class="zber-one-box">
    <h3 class="zber-one-panel-title">{zberOneTab_E($panel['head'])}</h3>
    <form class="zber-one-form" method="post" action="{zberOneTab_E($panel['action'])}" data-zber-form is="ui-form">
        {foreach $panel['fields'] as $field}
        {php}$type = $field['name'] === 'url' ? 'url' : 'text';{/php}
        <p class="zber-one-field">
            <label class="zber-one-field-label">{zberOneTab_E($field['label'])}</label>
            <input type="{$type}" class="ui-input" name="{zberOneTab_E($field['name'])}" value="{zberOneTab_E($field['value'])}" />
        </p>
        {/foreach}
        <p class="zber-one-form-act">
            <button type="submit" data-type="primary" class="ui-button" is="ui-button">保存</button>
            <button type="button" class="ui-button" is="ui-button" data-zber-cancel data-panel="{$panel['backPanel']}">取消</button>
        </p>
    </form>
</div>
