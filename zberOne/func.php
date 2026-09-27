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
 *   panel  点该组标题时显示的组面板 id：zber-panel-<kind>-<type>
 *   open   是否默认展开（互斥手风琴下只应有一组为 true）
 *   empty  子项为空时的占位文字
 *   items  子项列表，每项 title（菜单里的短标题）+ panel（该项对应的面板 id）
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
    // panel 一律指向该组的组面板（点组标题永远显示组面板）
    $groupName = 'zber-group-' . $kind;
    $menu = [
        ['title' => $prefix . '信息', 'type' => 'one', 'id' => $groupName . '-one', 'name' => $groupName, 'open' => true, 'empty' => '', 'items' => []],
        ['title' => $prefix . '说说', 'type' => 'toot', 'id' => $groupName . '-toot', 'name' => $groupName, 'open' => false, 'empty' => '（暂无说说）', 'items' => []],
        ['title' => $prefix . '文章', 'type' => 'post', 'id' => $groupName . '-post', 'name' => $groupName, 'open' => false, 'empty' => '（暂无文章）', 'items' => []],
        ['title' => $prefix . '视频', 'type' => 'video', 'id' => $groupName . '-video', 'name' => $groupName, 'open' => false, 'empty' => '（暂无视频）', 'items' => []],
    ];

    $listMap = [
        'toot' => $data->Toots(),
        'post' => $data->Posts(),
        'video' => $data->Videos(),
    ];

    foreach ($menu as $i => $group) {
        $type = $group['type'];
        $menu[$i]['panel'] = zberOne_PanelId($kind, $type);

        if (!isset($listMap[$type])) {
            continue;
        }

        foreach ($listMap[$type] as $j => $item) {
            $menu[$i]['items'][] = [
                'title' => ('toot' === $type) ? zberOneTab_TootTitle($item) : zberOneTab_EntryTitle($item),
                'panel' => zberOne_PanelId($kind, $type, $j),
            ];
        }
    }

    return $menu;
}

/**
 * 面板 id：zber-panel-<kind>-<type>[-<序号>].
 *
 * 同一 kind 下唯一，两个 slide 之间靠 kind 区分，避免 id 撞车。
 * 省略序号 = 该组的组面板（点组标题显示）；带序号 = 该组某条子项的面板。
 *
 * @param string   $kind 数据来源
 * @param string   $type 子级项目（one / toot / post / video）
 * @param null|int $idx  子项序号；省略表示该组的组面板
 *
 * @return string
 */
function zberOne_PanelId($kind, $type, $idx = null)
{
    $id = 'zber-panel-' . $kind . '-' . $type;
    if (null !== $idx) {
        $id .= '-' . $idx;
    }

    return $id;
}

/**
 * 「一个」标签页 - 指定来源的右栏内容面板数据.
 *
 * 结构与左栏菜单对应，每个可点目标都有对应的已渲染面板：
 *   组面板：每组一条（id = zber-panel-<kind>-<type>），点组标题显示；无数据时自己显示空提示
 *   子项面板：说说 / 文章 / 视频的每条子项一条（id 带序号），点子项显示
 *   信息组没有子项，只有组面板（渲染 source 字段行）
 *
 * 每项结构：
 *   id / type / tpl / active  公共字段
 *   head   面板顶部标题
 *   rows   信息组组面板的 dl 行（无数据时为空数组，模板回落到 empty）
 *   items  组面板的子项列表，每项 title + value（value 为链接或正文）
 *   text   说说子项面板的正文
 *   meta   说说子项面板的附加信息
 *   url    文章 / 视频子项面板的链接
 *   empty  空数据提示
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

    // 信息：组面板即数据源本身的字段行（无子项）
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
        'id' => zberOne_PanelId($kind, 'one'),
        'type' => 'one',
        'tpl' => 'plugin_zberOne_panel-one',
        'active' => true,
        'head' => $prefix . '信息',
        'rows' => $rows,
        'empty' => (zberOne_base::KIND_ME === $kind)
            ? '暂无数据（' . basename($data->Dir()) . '/one.json）'
            : '暂无数据（' . $slot['name'] . ' id：' . $slot['id'] . '，将来由 id 从外部获取）',
    ];

    // 说说：组面板（列出全部子项）+ 每条的子项面板
    $toots = $data->Toots();
    $group = [
        'id' => zberOne_PanelId($kind, 'toot'),
        'type' => 'toot',
        'tpl' => 'plugin_zberOne_panel-group',
        'active' => false,
        'head' => $prefix . '说说',
        'items' => [],
        'empty' => '（暂无说说）',
    ];
    foreach ($toots as $i => $toot) {
        $title = zberOneTab_TootTitle($toot);
        $text = isset($toot['text']) ? (string) $toot['text'] : '';

        $group['items'][] = ['title' => $title, 'value' => $text];

        $createdAt = isset($toot['created_at']) ? trim((string) $toot['created_at']) : '';
        $panels[] = [
            'id' => zberOne_PanelId($kind, 'toot', $i),
            'type' => 'toot',
            'tpl' => 'plugin_zberOne_panel-toot',
            'active' => false,
            'head' => $prefix . '说说 · ' . $title,
            'text' => $text,
            'meta' => '发布时间：' . ('' !== $createdAt ? $createdAt : '—'),
            'empty' => '（暂无说说）',
        ];
    }
    $panels[] = $group;

    // 文章 / 视频：组面板（列出全部子项）+ 每条的子项面板
    $entries = [
        ['type' => 'post', 'label' => $prefix . '文章', 'empty' => '（暂无文章）', 'list' => $data->Posts()],
        ['type' => 'video', 'label' => $prefix . '视频', 'empty' => '（暂无视频）', 'list' => $data->Videos()],
    ];
    foreach ($entries as $entry) {
        $group = [
            'id' => zberOne_PanelId($kind, $entry['type']),
            'type' => $entry['type'],
            'tpl' => 'plugin_zberOne_panel-group',
            'active' => false,
            'head' => $entry['label'],
            'items' => [],
            'empty' => $entry['empty'],
        ];
        foreach ($entry['list'] as $i => $item) {
            $title = zberOneTab_EntryTitle($item);
            $url = isset($item['url']) ? trim((string) $item['url']) : '';

            $group['items'][] = ['title' => $title, 'value' => $url];

            $panels[] = [
                'id' => zberOne_PanelId($kind, $entry['type'], $i),
                'type' => $entry['type'],
                'tpl' => 'plugin_zberOne_panel-link',
                'active' => false,
                'head' => $entry['label'] . ' · ' . $title,
                'url' => $url,
                'empty' => $entry['empty'],
            ];
        }
        $panels[] = $group;
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
