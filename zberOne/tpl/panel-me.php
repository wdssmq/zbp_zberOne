<?php exit(); ?>
<!-- 面板 - 我 -->
{if count($panel['rows']) > 0}
<dl>
    {foreach $panel['rows'] as $row}
    <dt>{zberMeTab_E($row['label'])}</dt>
    <dd>{zberMeTab_E($row['value'])}</dd>
    {/foreach}
</dl>
{else}
<p class="zber-me-empty">{zberMeTab_E($panel['empty'])}</p>
{/if}
