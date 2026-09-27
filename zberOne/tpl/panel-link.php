<?php exit(); ?>
<!-- 面板 - 我的文章 / 我的视频 -->
{if $panel['url'] != ''}
<p>链接：<a href="{zberOneTab_E($panel['url'])}" target="_blank" rel="noopener">{zberOneTab_E($panel['url'])}</a></p>
{else}
<p class="zber-one-empty">暂无链接</p>
{/if}
