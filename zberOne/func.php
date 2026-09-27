<?php

/**
 * zberOne 通用函数.
 *
 * @param mixed $file
 * @param mixed $t
 */

/**
 * 插件内文件路径.
 *
 * @param string $file 路径表中的键名，或直接给出相对路径
 * @param string $t    $zbp 上的根路径属性名（path / host）
 *
 * @return string
 */
function zberOne_Path($file = '', $t = 'path')
{
    global $zbp;
    static $paths = [
        'main' => 'main.php',
        'tpl' => 'tpl/',
        'u-data' => 'usr-data/',
    ];
    $base = $zbp->{$t} . 'zb_users/plugin/zberOne/';

    return $base . (isset($paths[$file]) ? $paths[$file] : $file);
}

/**
 * HTML 转义输出.
 *
 * @param string $str
 *
 * @return string
 */
function zberOneTab_E($str)
{
    return htmlspecialchars((string) $str, ENT_QUOTES, 'UTF-8');
}

/**
 * 说说条目的菜单标题：取 text 前 24 字.
 *
 * @param array $toot
 *
 * @return string
 */
function zberOneTab_TootTitle($toot)
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
 * 文章/视频条目的菜单标题：取 title.
 *
 * @param array $item
 *
 * @return string
 */
function zberOneTab_EntryTitle($item)
{
    $title = isset($item['title']) ? trim((string) $item['title']) : '';

    return '' !== $title ? $title : '（未命名）';
}

/**
 * 「一个」标签页 - 数据来源 slot 定义.
 *
 * 数组结构：
 *   kind   父级来源（me / other），对应 zberOne_base::KIND_*
 *   id     来源标识：me 固定为空（数据在 usr-data/one.json）；other 暂固定为 any，
 *          将来由它从外部读取具体数据
 *   name   该来源的显示名（滑动切换区域用）
 *   label  该来源下四个主项目的标题前缀
 *
 * @return array
 */
function zberOne_GetTabOneSlots()
{
    return [
        ['kind' => zberOne_base::KIND_ME, 'id' => '', 'name' => '我', 'label' => '我的'],
        ['kind' => zberOne_base::KIND_OTHER, 'id' => 'any', 'name' => '他人', 'label' => '他人的'],
    ];
}

/**
 * 按 kind 取单个来源 slot；kind 无效时返回 null.
 *
 * @param string $kind
 *
 * @return null|array
 */
function zberOne_GetTabOneSlot($kind)
{
    foreach (zberOne_GetTabOneSlots() as $slot) {
        if ($slot['kind'] === $kind) {
            return $slot;
        }
    }

    return null;
}

/**
 * 「一个」标签页 - 指定来源的左栏层级菜单数据.
 *
 * 数组结构：
 *   title  菜单标题
 *   type   对应的子级项目（one / toot / post / video）
 *   id     该组在 DOM 中的唯一 id，即 LuLu <ui-tab> 的 target：zber-group-<kind>-<type>
 *   name   手风琴分组名，同 kind 的各组互斥展开（同时只开一个）：zber-group-<kind>
 *   panel  顶层项自身对应的面板 id（子项为空的组留空）
 *   open   是否默认展开（互斥手风琴下只应有一组为 true）
 *   empty  子项为空时的占位文字
 *   items  子项列表，每项 title + panel
 *
 * @param string $kind 数据来源
 *
 * @return array
 */
function zberOne_GetTabOneMenu($kind = zberOne_base::KIND_ME)
{
    $slot = zberOne_GetTabOneSlot($kind);
    if (null === $slot) {
        return [];
    }

    $data = new zberOne_base($kind);
    $prefix = $slot['label'];

    // 菜单结构先定义成数组，再由各主项目数据循环填充子项
    // id / name 供 LuLu <ui-tab> 手风琴使用：id 是 target，name 相同者互斥展开
    $groupName = 'zber-group-' . $kind;
    $menu = [
        ['title' => $prefix . '信息', 'type' => 'one', 'id' => $groupName . '-one', 'name' => $groupName, 'panel' => 'zber-panel-' . $kind . '-one', 'open' => true, 'empty' => '', 'items' => []],
        ['title' => $prefix . '说说', 'type' => 'toot', 'id' => $groupName . '-toot', 'name' => $groupName, 'panel' => '', 'open' => false, 'empty' => '（暂无说说）', 'items' => []],
        ['title' => $prefix . '文章', 'type' => 'post', 'id' => $groupName . '-post', 'name' => $groupName, 'panel' => '', 'open' => false, 'empty' => '（暂无文章）', 'items' => []],
        ['title' => $prefix . '视频', 'type' => 'video', 'id' => $groupName . '-video', 'name' => $groupName, 'panel' => '', 'open' => false, 'empty' => '（暂无视频）', 'items' => []],
    ];

    $listMap = [
        'toot' => $data->Toots(),
        'post' => $data->Posts(),
        'video' => $data->Videos(),
    ];

    foreach ($menu as $i => $group) {
        if (!isset($listMap[$group['type']])) {
            continue;
        }
        foreach ($listMap[$group['type']] as $j => $item) {
            $menu[$i]['items'][] = [
                'title' => ('toot' === $group['type']) ? zberOneTab_TootTitle($item) : zberOneTab_EntryTitle($item),
                'panel' => 'zber-panel-' . $kind . '-' . $group['type'] . '-' . $j,
            ];
        }
    }

    return $menu;
}

/**
 * 「一个」标签页 - 指定来源的右栏内容面板数据.
 *
 * 每项结构：
 *   id / type / tpl / active / title  公共字段
 *   rows   子级为 one 时的 dl 行（无数据时为空数组，模板回落到 empty）
 *   empty  子级为 one 时无数据源的提示
 *   text   子级为 toot 时的正文
 *   meta   子级为 toot 时的附加信息
 *   url    子级为 post / video 时的链接
 *
 * @param string $kind 数据来源
 *
 * @return array
 */
function zberOne_GetTabOnePanels($kind = zberOne_base::KIND_ME)
{
    $slot = zberOne_GetTabOneSlot($kind);
    if (null === $slot) {
        return [];
    }

    $data = new zberOne_base($kind);
    $prefix = $slot['label'];

    $panels = [];

    // 信息
    $one = $data->One();

    // 数据不存在时 rows 留空，由模板显示「空数据」
    $rows = [];
    if (count($one) > 0) {
        $rows = [
            ['label' => 'ID', 'value' => isset($one['id']) ? $one['id'] : ''],
            ['label' => '名称', 'value' => isset($one['name']) ? $one['name'] : ''],
            ['label' => '简介', 'value' => isset($one['description']) ? $one['description'] : ''],
        ];
    }

    $panels[] = [
        'id' => 'zber-panel-' . $kind . '-one',
        'type' => 'one',
        'tpl' => 'plugin_zberOne_panel-one',
        'active' => true,
        'title' => $prefix . '信息',
        'empty' => (zberOne_base::KIND_ME === $kind)
            ? '暂无数据（' . basename($data->Dir()) . '/one.json）'
            : '暂无数据（' . $slot['name'] . ' id：' . $slot['id'] . '，将来由 id 从外部获取）',
        'rows' => $rows,
    ];

    // 说说
    foreach ($data->Toots() as $i => $toot) {
        $createdAt = isset($toot['created_at']) ? trim((string) $toot['created_at']) : '';
        $panels[] = [
            'id' => 'zber-panel-' . $kind . '-toot-' . $i,
            'type' => 'toot',
            'tpl' => 'plugin_zberOne_panel-toot',
            'active' => false,
            'title' => $prefix . '说说 · ' . zberOneTab_TootTitle($toot),
            'text' => isset($toot['text']) ? (string) $toot['text'] : '',
            'meta' => '发布时间：' . ('' !== $createdAt ? $createdAt : '—'),
        ];
    }

    // 文章 / 视频：条目仅 title + url
    $entries = [
        ['type' => 'post', 'label' => $prefix . '文章', 'list' => $data->Posts()],
        ['type' => 'video', 'label' => $prefix . '视频', 'list' => $data->Videos()],
    ];
    foreach ($entries as $entry) {
        foreach ($entry['list'] as $i => $item) {
            $panels[] = [
                'id' => 'zber-panel-' . $kind . '-' . $entry['type'] . '-' . $i,
                'type' => $entry['type'],
                'tpl' => 'plugin_zberOne_panel-link',
                'active' => false,
                'title' => $entry['label'] . ' · ' . zberOneTab_EntryTitle($item),
                'url' => isset($item['url']) ? trim((string) $item['url']) : '',
            ];
        }
    }

    return $panels;
}

/**
 * 输出「一个」标签页 - 单个来源（kind）的左右栏.
 *
 * 页面级的滑动容器见 main.php；每个来源渲染成一个 slide。
 *
 * @param string $kind 数据来源
 */
function zberOne_echoTabOne($kind = zberOne_base::KIND_ME)
{
    global $zbp;

    $slot = zberOne_GetTabOneSlot($kind);
    if (null === $slot) {
        return;
    }

    $tplName = 'plugin_zberOne_tab-one';

    // 模板未编译时先重建，开发期改动 tpl 文件后无需手动刷新
    if (!$zbp->template->HasTemplate($tplName)) {
        $zbp->BuildTemplate();
    }

    $zbp->template->SetTags('zberOneTabSlot', $slot);
    $zbp->template->SetTags('zberOneTabMenu', zberOne_GetTabOneMenu($kind));
    $zbp->template->SetTags('zberOneTabPanels', zberOne_GetTabOnePanels($kind));

    $zbp->template->Display($tplName);
}
