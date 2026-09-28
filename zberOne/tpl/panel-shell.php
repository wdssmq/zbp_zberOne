<?php exit(); ?>
<!-- 「一个」标签页 - 右栏单个面板：首屏静态渲染与按需取回共用（$panel 由调用处传入）
     data-zber-idx = 该面板当前的编辑对象序号（只有表单面板的编辑态有值），前端据此判断态对不对 -->
<div class="zber-one-panel{if $panel['active']} active{/if}" id="{$panel['id']}" data-panel="{$panel['id']}"{if null !== $panel['formIdx']} data-zber-idx="{$panel['formIdx']}"{/if}>
    {php} include $zbp->template->GetTemplate($panel['tpl']); {/php}
</div>
