<?php

/**
 * tab_me.php — 「我」标签页
 * 由 main.php 在 #divMain2 内引入
 * 数据来源：usr-data/ 目录下的 json 文件（每个主项目一个文件）
 * 读取逻辑封装于 class/zberOne_base.php
 */

if (!defined('ZBP_PATH')) {
    exit;
}

require_once __DIR__ . '/class/zberOne_base.php';

if (!function_exists('zberMeTab_TootTitle')) {
    /**
     * 说说条目的菜单标题：取 text 前 24 字
     *
     * @param array $toot
     *
     * @return string
     */
    function zberMeTab_TootTitle($toot)
    {
        $text = isset($toot['text']) ? trim((string) $toot['text']) : '';
        if ($text === '') {
            return '（空）';
        }
        if (function_exists('mb_substr')) {
            return mb_strlen($text) > 24 ? mb_substr($text, 0, 24) . '…' : $text;
        }

        return strlen($text) > 48 ? substr($text, 0, 48) . '…' : $text;
    }
}

if (!function_exists('zberMeTab_EntryTitle')) {
    /**
     * 文章/视频条目的菜单标题：取 title
     *
     * @param array $item
     *
     * @return string
     */
    function zberMeTab_EntryTitle($item)
    {
        $title = isset($item['title']) ? trim((string) $item['title']) : '';

        return $title !== '' ? $title : '（未命名）';
    }
}

if (!function_exists('zberMeTab_E')) {
    /**
     * HTML 转义输出
     *
     * @param string $str
     *
     * @return string
     */
    function zberMeTab_E($str)
    {
        return htmlspecialchars((string) $str, ENT_QUOTES, 'UTF-8');
    }
}

$zberMeTab_data = new zberOne_base();
$zberMeTab_me = $zberMeTab_data->Me();
$zberMeTab_toots = $zberMeTab_data->Toots();
$zberMeTab_posts = $zberMeTab_data->Posts();
$zberMeTab_videos = $zberMeTab_data->Videos();
?>
<style>
    #zber-me-root {
        display: flex;
        align-items: flex-start;
        gap: 16px;
        margin-top: 10px;
    }

    #zber-me-root .zber-me-side {
        flex: 0 0 220px;
        width: 220px;
        background: #fff;
        border: 1px solid #d8d8d8;
        border-radius: 4px;
        padding: 6px 0;
    }

    #zber-me-root .zber-me-main {
        flex: 1 1 auto;
        min-width: 0;
    }

    #zber-me-root details.zber-me-group details.zber-me-group {
        margin-left: 14px;
    }

    #zber-me-root summary {
        padding: 6px 12px;
        cursor: pointer;
        font-weight: 600;
        color: #333;
        user-select: none;
        list-style: revert;
    }

    #zber-me-root summary:hover {
        background: #f2f6fc;
    }

    #zber-me-root .zber-me-item {
        padding: 5px 12px 5px 30px;
        cursor: pointer;
        color: #444;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    #zber-me-root .zber-me-item:hover {
        background: #f2f6fc;
    }

    #zber-me-root .zber-me-item.active {
        background: #e3edfd;
        color: #1a66b3;
    }

    #zber-me-root .zber-me-panel {
        display: none;
        background: #fff;
        border: 1px solid #d8d8d8;
        border-radius: 4px;
        padding: 14px 18px;
    }

    #zber-me-root .zber-me-panel.active {
        display: block;
    }

    #zber-me-root .zber-me-panel h3 {
        margin: 0 0 10px;
        font-size: 15px;
    }

    #zber-me-root dl {
        margin: 0;
    }

    #zber-me-root dt {
        font-weight: 600;
        margin-top: 8px;
    }

    #zber-me-root dd {
        margin: 2px 0 0;
        word-break: break-all;
    }

    #zber-me-root .zber-me-empty {
        color: #999;
    }

    #zber-me-root .zber-me-meta {
        color: #888;
        font-size: 12px;
        margin-top: 10px;
    }
</style>

<div id="zber-me-root" class="zber-me-wrap">
    <!-- 左栏：层级菜单 -->
    <div class="zber-me-side">
        <details class="zber-me-group" open>
            <summary data-panel="zber-panel-me">我</summary>
        </details>

        <details class="zber-me-group" open>
            <summary>我的说说</summary>
            <?php if (empty($zberMeTab_toots)) { ?>
                <div class="zber-me-item">（暂无说说）</div>
            <?php } else { ?>
                <?php foreach ($zberMeTab_toots as $zberMeTab_i => $zberMeTab_toot) { ?>
                    <div class="zber-me-item" data-panel="zber-panel-toot-<?php echo $zberMeTab_i; ?>"><?php echo zberMeTab_E(zberMeTab_TootTitle($zberMeTab_toot)); ?></div>
                <?php } ?>
            <?php } ?>
        </details>

        <details class="zber-me-group">
            <summary>我的文章</summary>
            <?php if (empty($zberMeTab_posts)) { ?>
                <div class="zber-me-item">（暂无文章）</div>
            <?php } else { ?>
                <?php foreach ($zberMeTab_posts as $zberMeTab_i => $zberMeTab_post) { ?>
                    <div class="zber-me-item" data-panel="zber-panel-post-<?php echo $zberMeTab_i; ?>"><?php echo zberMeTab_E(zberMeTab_EntryTitle($zberMeTab_post)); ?></div>
                <?php } ?>
            <?php } ?>
        </details>

        <details class="zber-me-group">
            <summary>我的视频</summary>
            <?php if (empty($zberMeTab_videos)) { ?>
                <div class="zber-me-item">（暂无视频）</div>
            <?php } else { ?>
                <?php foreach ($zberMeTab_videos as $zberMeTab_i => $zberMeTab_video) { ?>
                    <div class="zber-me-item" data-panel="zber-panel-video-<?php echo $zberMeTab_i; ?>"><?php echo zberMeTab_E(zberMeTab_EntryTitle($zberMeTab_video)); ?></div>
                <?php } ?>
            <?php } ?>
        </details>
    </div>

    <!-- 右栏：内容面板 -->
    <div class="zber-me-main">
        <div class="zber-me-panel active" id="zber-panel-me">
            <h3>我</h3>
            <?php if (empty($zberMeTab_me)) { ?>
                <p class="zber-me-empty">暂无数据（usr-data/me.json）</p>
            <?php } else { ?>
                <dl>
                    <dt>ID</dt>
                    <dd><?php echo zberMeTab_E(isset($zberMeTab_me['id']) ? $zberMeTab_me['id'] : ''); ?></dd>
                    <dt>名称</dt>
                    <dd><?php echo zberMeTab_E(isset($zberMeTab_me['name']) ? $zberMeTab_me['name'] : ''); ?></dd>
                    <dt>简介</dt>
                    <dd><?php echo zberMeTab_E(isset($zberMeTab_me['description']) ? $zberMeTab_me['description'] : ''); ?></dd>
                </dl>
            <?php } ?>
        </div>

        <?php foreach ($zberMeTab_toots as $zberMeTab_i => $zberMeTab_toot) { ?>
            <div class="zber-me-panel" id="zber-panel-toot-<?php echo $zberMeTab_i; ?>">
                <h3>我的说说 · <?php echo zberMeTab_E(zberMeTab_TootTitle($zberMeTab_toot)); ?></h3>
                <p><?php echo nl2br(zberMeTab_E(isset($zberMeTab_toot['text']) ? $zberMeTab_toot['text'] : '')); ?></p>
                <p class="zber-me-meta">发布时间：<?php echo zberMeTab_E(isset($zberMeTab_toot['created_at']) && trim((string) $zberMeTab_toot['created_at']) !== '' ? $zberMeTab_toot['created_at'] : '—'); ?></p>
            </div>
        <?php } ?>

        <?php foreach ($zberMeTab_posts as $zberMeTab_i => $zberMeTab_post) { ?>
            <div class="zber-me-panel" id="zber-panel-post-<?php echo $zberMeTab_i; ?>">
                <h3>我的文章 · <?php echo zberMeTab_E(zberMeTab_EntryTitle($zberMeTab_post)); ?></h3>
                <?php $zberMeTab_url = isset($zberMeTab_post['url']) ? trim((string) $zberMeTab_post['url']) : ''; ?>
                <?php if ($zberMeTab_url !== '') { ?>
                    <p>链接：<a href="<?php echo zberMeTab_E($zberMeTab_url); ?>" target="_blank" rel="noopener"><?php echo zberMeTab_E($zberMeTab_url); ?></a></p>
                <?php } else { ?>
                    <p class="zber-me-empty">暂无链接</p>
                <?php } ?>
            </div>
        <?php } ?>

        <?php foreach ($zberMeTab_videos as $zberMeTab_i => $zberMeTab_video) { ?>
            <div class="zber-me-panel" id="zber-panel-video-<?php echo $zberMeTab_i; ?>">
                <h3>我的视频 · <?php echo zberMeTab_E(zberMeTab_EntryTitle($zberMeTab_video)); ?></h3>
                <?php $zberMeTab_url = isset($zberMeTab_video['url']) ? trim((string) $zberMeTab_video['url']) : ''; ?>
                <?php if ($zberMeTab_url !== '') { ?>
                    <p>链接：<a href="<?php echo zberMeTab_E($zberMeTab_url); ?>" target="_blank" rel="noopener"><?php echo zberMeTab_E($zberMeTab_url); ?></a></p>
                <?php } else { ?>
                    <p class="zber-me-empty">暂无链接</p>
                <?php } ?>
            </div>
        <?php } ?>
    </div>
</div>

<script>
    (function() {
        var root = document.getElementById('zber-me-root');
        if (!root) {
            return;
        }
        root.addEventListener('click', function(e) {
            var el = e.target.closest('[data-panel]');
            if (!el || !root.contains(el)) {
                return;
            }
            var panels = root.querySelectorAll('.zber-me-panel');
            for (var i = 0; i < panels.length; i++) {
                panels[i].classList.remove('active');
            }
            var target = document.getElementById(el.getAttribute('data-panel'));
            if (target) {
                target.classList.add('active');
            }
            var items = root.querySelectorAll('.zber-me-item');
            for (var j = 0; j < items.length; j++) {
                items[j].classList.remove('active');
            }
            if (el.classList.contains('zber-me-item')) {
                el.classList.add('active');
            }
        });
    })();
</script>
