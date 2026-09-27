<?php exit(); ?>
<!-- 面板 - 组：点组标题显示，列出该组全部子项 -->
<h3 class="zber-one-panel-title">{zberOneTab_E($panel['head'])}</h3>
{if count($panel['items']) > 0}
<ul class="zber-one-list">
    {foreach $panel['items'] as $row}
    <li>{zberOneTab_E($row['title'])}</li>
    {/foreach}
</ul>
{else}
<p class="zber-one-empty">{zberOneTab_E($panel['empty'])}</p>
{/if}
