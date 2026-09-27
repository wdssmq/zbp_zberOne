<?php exit(); ?>
<!-- 面板 - 我的文章 / 我的视频：一条一个面板 -->
{if $panel['head'] != ''}
<h3 class="zber-one-panel-title">{zberOneTab_E($panel['head'])}</h3>
{/if}
{if $panel['url'] != ''}
<p>链接：<a href="{zberOneTab_E($panel['url'])}" target="_blank" rel="noopener">{zberOneTab_E($panel['url'])}</a></p>
{else}
<p class="zber-one-empty">{zberOneTab_E($panel['empty'])}</p>
{/if}
