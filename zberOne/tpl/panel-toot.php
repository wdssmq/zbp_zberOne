<?php exit(); ?>
<!-- 面板 - 说说：一条一个面板 -->
{if $panel['head'] != ''}
<h3 class="zber-one-panel-title">{zberOneTab_E($panel['head'])}</h3>
{/if}
{if $panel['text'] != ''}
<p>{nl2br(zberOneTab_E($panel['text']))}</p>
{/if}
{if $panel['meta'] != ''}<p class="zber-one-meta">{zberOneTab_E($panel['meta'])}</p>{/if}

