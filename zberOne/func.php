<?php

/**
 * zberOne 通用函数
 */

/**
 * 插件内文件路径
 *
 * @param string $file 路径表中的键名，或直接给出相对路径
 * @param string $t    $zbp 上的根路径属性名（path / host）
 *
 * @return string
 */
function zberOne_Path($file = '', $t = 'path')
{
    global $zbp;
    static $paths = array(
        'main' => 'main.php',
        'tpl' => 'tpl/',
        'u-data' => 'usr-data/',
    );
    $base = $zbp->$t . 'zb_users/plugin/zberOne/';

    return $base . (isset($paths[$file]) ? $paths[$file] : $file);
}

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
    if ('' === $text) {
        return '（空）';
    }
    if (function_exists('mb_substr')) {
        return mb_strlen($text) > 24 ? mb_substr($text, 0, 24) . '…' : $text;
    }

    return strlen($text) > 48 ? substr($text, 0, 48) . '…' : $text;
}

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

    return '' !== $title ? $title : '（未命名）';
}

/**
 * 「我」标签页 - 左栏层级菜单数据
 *
 * 数组结构：
 *   title  菜单标题
 *   type   对应的数据类型（me / toot / post / video）
 *   panel  顶层项自身对应的面板 id（子项为空的组留空）
 *   open   是否默认展开
 *   empty  子项为空时的占位文字
 *   items  子项列表，每项 title + panel
 *
 * @return array
 */
function zberOne_GetTabMeMenu()
{
    $data = new zberOne_base();

    // 菜单结构先定义成数组，再由各主项目数据循环填充子项
    $menu = array(
        array('title' => '我', 'type' => 'me', 'panel' => 'zber-panel-me', 'open' => true, 'empty' => '', 'items' => array()),
        array('title' => '我的说说', 'type' => 'toot', 'panel' => '', 'open' => true, 'empty' => '（暂无说说）', 'items' => array()),
        array('title' => '我的文章', 'type' => 'post', 'panel' => '', 'open' => false, 'empty' => '（暂无文章）', 'items' => array()),
        array('title' => '我的视频', 'type' => 'video', 'panel' => '', 'open' => false, 'empty' => '（暂无视频）', 'items' => array()),
    );

    $listMap = array(
        'toot' => $data->Toots(),
        'post' => $data->Posts(),
        'video' => $data->Videos(),
    );

    foreach ($menu as $i => $group) {
        if (!isset($listMap[$group['type']])) {
            continue;
        }
        foreach ($listMap[$group['type']] as $j => $item) {
            $menu[$i]['items'][] = array(
                'title' => ('toot' === $group['type']) ? zberMeTab_TootTitle($item) : zberMeTab_EntryTitle($item),
                'panel' => 'zber-panel-' . $group['type'] . '-' . $j,
            );
        }
    }

    return $menu;
}

/**
 * 「我」标签页 - 右栏内容面板数据
 *
 * 每项结构：
 *   id / type / tpl / active / title  公共字段
 *   rows   类型为 me 时的 dl 行
 *   text   类型为 toot 时的正文
 *   meta   类型为 toot 时的附加信息
 *   url    类型为 post / video 时的链接
 *
 * @return array
 */
function zberOne_GetTabMePanels()
{
    $data = new zberOne_base();

    $panels = array();

    // 「我」
    $me = $data->Me();
    $panels[] = array(
        'id' => 'zber-panel-me',
        'type' => 'me',
        'tpl' => 'plugin_zberOne_panel-me',
        'active' => true,
        'title' => '我',
        'empty' => '暂无数据（' . basename($data->Dir()) . '/me.json）',
        'rows' => array(
            array('label' => 'ID', 'value' => isset($me['id']) ? $me['id'] : ''),
            array('label' => '名称', 'value' => isset($me['name']) ? $me['name'] : ''),
            array('label' => '简介', 'value' => isset($me['description']) ? $me['description'] : ''),
        ),
    );

    // 我的说说
    foreach ($data->Toots() as $i => $toot) {
        $createdAt = isset($toot['created_at']) ? trim((string) $toot['created_at']) : '';
        $panels[] = array(
            'id' => 'zber-panel-toot-' . $i,
            'type' => 'toot',
            'tpl' => 'plugin_zberOne_panel-toot',
            'active' => false,
            'title' => '我的说说 · ' . zberMeTab_TootTitle($toot),
            'text' => isset($toot['text']) ? (string) $toot['text'] : '',
            'meta' => '发布时间：' . ('' !== $createdAt ? $createdAt : '—'),
        );
    }

    // 我的文章 / 我的视频：条目仅 title + url
    $entries = array(
        array('type' => 'post', 'label' => '我的文章', 'list' => $data->Posts()),
        array('type' => 'video', 'label' => '我的视频', 'list' => $data->Videos()),
    );
    foreach ($entries as $entry) {
        foreach ($entry['list'] as $i => $item) {
            $panels[] = array(
                'id' => 'zber-panel-' . $entry['type'] . '-' . $i,
                'type' => $entry['type'],
                'tpl' => 'plugin_zberOne_panel-link',
                'active' => false,
                'title' => $entry['label'] . ' · ' . zberMeTab_EntryTitle($item),
                'url' => isset($item['url']) ? trim((string) $item['url']) : '',
            );
        }
    }

    return $panels;
}

/**
 * 输出「我」标签页（左栏菜单 + 右栏面板）
 */
function zberOne_echoTabMe()
{
    global $zbp;

    $tplName = 'plugin_zberOne_tab-me';

    // 模板未编译时先重建，开发期改动 tpl 文件后无需手动刷新
    if (!$zbp->template->HasTemplate($tplName)) {
        $zbp->BuildTemplate();
    }

    $zbp->template->SetTags('zberMeTabMenu', zberOne_GetTabMeMenu());
    $zbp->template->SetTags('zberMeTabPanels', zberOne_GetTabMePanels());
    $zbp->template->SetTags('zberMeTabStyle', zberOne_Path('style/style.css', 'host'));

    $zbp->template->Display($tplName);
}
