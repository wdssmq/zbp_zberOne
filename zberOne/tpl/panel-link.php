<?php exit(); ?>
<!-- 面板 - 我的文章 / 我的视频 -->
{if $panel['url'] != ''}
<p>链接：<a href="{zberMeTab_E($panel['url'])}" target="_blank" rel="noopener">{zberMeTab_E($panel['url'])}</a></p>
{else}
<p class="zber-me-empty">暂无链接</p>
{/if}
