<?php exit(); ?>
<!-- 面板 - one（我 / 他人，按 kind 取数） -->
{if count($panel['rows']) > 0}
<dl>
    {foreach $panel['rows'] as $row}
    <dt>{zberOneTab_E($row['label'])}</dt>
    <dd>{zberOneTab_E($row['value'])}</dd>
    {/foreach}
</dl>
{else}
<p class="zber-one-empty">{zberOneTab_E($panel['empty'])}</p>
{/if}
