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
 * 非数字序号（如 'form'）用于表单等特殊面板，不与数据序号冲突。
 *
 * @param string          $kind 数据来源
 * @param string          $type 子级项目（one / toot / post / video）
 * @param null|int|string $idx  子项序号；省略表示该组的组面板
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
 * 类型名映射到中文显示名.
 *
 * @param string $type
 *
 * @return string
 */
function zberOne_TypeLabel($type)
{
    static $labels = [
        zberOne_base::TYPE_ONE => '信息',
        zberOne_base::TYPE_TOOT => '说说',
        zberOne_base::TYPE_POST => '文章',
        zberOne_base::TYPE_VIDEO => '视频',
    ];

    return isset($labels[$type]) ? $labels[$type] : $type;
}

/**
 * 类型对应的表单字段定义.
 *
 * 只列用户需要填写的字段；由服务端管理的字段（如说说的发布时间）不在此列。
 *
 * @param string $type
 *
 * @return array 每项 name + label
 */
function zberOne_FormFields($type)
{
    switch ($type) {
        case zberOne_base::TYPE_ONE:
            return [
                ['name' => 'id', 'label' => 'ID'],
                ['name' => 'name', 'label' => '名称'],
                ['name' => 'description', 'label' => '简介'],
            ];

        case zberOne_base::TYPE_TOOT:
            return [
                ['name' => 'text', 'label' => '内容'],
            ];

        case zberOne_base::TYPE_POST:
        case zberOne_base::TYPE_VIDEO:
            return [
                ['name' => 'title', 'label' => '标题'],
                ['name' => 'url', 'label' => '链接'],
            ];
    }

    return [];
}

/**
 * 构造带 CSRF token 的 cmd.php ajax 链接（act=ajax&src=zberOne，token 走 URL 由 CheckCSRFTokenValid 接住）.
 *
 * @param array $params 业务参数（type / idx 等；act 为 cmd.php 的动作名，本插件子动作用 op），不含 csrfToken
 *
 * @return string
 */
function zberOne_AjaxUrl($params = [])
{
    global $zbp;

    $url = $zbp->cmdurl . '?act=ajax&src=zberOne';
    if (!empty($params)) {
        $url .= '&' . http_build_query($params);
    }

    if (function_exists('BuildSafeURL')) {
        $url = BuildSafeURL($url);
    }

    return $url;
}

/**
 * 「一个」标签页 - 指定来源的右栏内容面板数据.
 *
 * 结构与左栏菜单对应，每个可点目标都有对应的已渲染面板：
 *   组面板：每组一条（id = zber-panel-<kind>-<type>），点组标题显示；无数据时自己显示空提示
 *   子项面板：说说 / 文章 / 视频的每条子项一条（id 带序号），点子项显示
 *   表单面板：每组一条（id 带 'form' 后缀），点「添加 / 编辑」显示，添加与编辑复用同一块
 *   信息组没有子项，只有组面板（渲染 source 字段行）
 *
 * 每项结构：
 *   id / type / tpl / active  公共字段
 *   head   面板顶部标题
 *   rows   信息组组面板的 dl 行（无数据时为空数组，模板回落到 empty）
 *   items  组面板的子项列表，每项 title + value + idx + delUrl + formPanel
 *   text   说说子项面板的正文
 *   meta   说说子项面板的附加信息
 *   url    文章 / 视频子项面板的链接
 *   fields 表单面板的字段列表，每项 name + label + value
 *   action 表单面板的提交地址（add 或 update，已带 token）
 *   addUrl 组面板顶部「添加」链接（切到表单面板）
 *   delUrl / formPanel / idx  操作与切换用
 *   empty  空数据提示
 *
 * 写入口径：表单面板、addUrl / delUrl / formPanel 只对「我」生成；「他人」恒为空串。
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
    $canWrite = (zberOne_base::KIND_ME === $kind);

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

    $oneType = zberOne_base::TYPE_ONE;
    $panels[] = [
        'id' => zberOne_PanelId($kind, $oneType),
        'type' => $oneType,
        'tpl' => 'plugin_zberOne_panel-one',
        'active' => true,
        'head' => $prefix . '信息',
        'rows' => $rows,
        'formPanel' => $canWrite ? zberOne_PanelId($kind, $oneType, 'form') : '',
        'empty' => (zberOne_base::KIND_ME === $kind)
            ? '暂无数据（' . basename($data->Dir()) . '/one.json）'
            : '暂无数据（' . $slot['name'] . ' id：' . $slot['id'] . '，将来由 id 从外部获取）',
    ];

    // 信息表单面板（添加 = 编辑，复用；无 idx；只给「我」）
    if ($canWrite) {
        $oneFields = [];
        foreach (zberOne_FormFields($oneType) as $f) {
            $oneFields[] = [
                'name' => $f['name'],
                'label' => $f['label'],
                'value' => isset($one[$f['name']]) ? (string) $one[$f['name']] : '',
            ];
        }
        $panels[] = [
            'id' => zberOne_PanelId($kind, $oneType, 'form'),
            'type' => $oneType,
            'tpl' => 'plugin_zberOne_panel-form',
            'active' => false,
            'head' => $prefix . '信息 · 编辑',
            'fields' => $oneFields,
            'action' => zberOne_AjaxUrl(['op' => 'update', 'kind' => $kind, 'type' => $oneType]),
            'idx' => null,
            'formPanel' => zberOne_PanelId($kind, $oneType, 'form'),
            'backPanel' => zberOne_PanelId($kind, $oneType),
            'empty' => '',
        ];
    }

    // 说说：组面板（列出全部子项）+ 每条的子项面板 + 每条的表单面板 + 该组一个添加表单面板
    $toots = $data->Toots();
    $tootType = zberOne_base::TYPE_TOOT;
    $group = [
        'id' => zberOne_PanelId($kind, $tootType),
        'type' => $tootType,
        'tpl' => 'plugin_zberOne_panel-group',
        'active' => false,
        'head' => $prefix . '说说',
        'items' => [],
        'addUrl' => $canWrite ? zberOne_PanelId($kind, $tootType, 'form') : '',
        'empty' => '（暂无说说）',
    ];

    foreach ($toots as $i => $toot) {
        $title = zberOneTab_TootTitle($toot);
        $text = isset($toot['text']) ? (string) $toot['text'] : '';
        $createdAt = isset($toot['created_at']) ? trim((string) $toot['created_at']) : '';

        // 该条的编辑表单面板（与展示面板同前缀，id 用「<序号>-form」后缀避免撞车）
        $tootFormId = zberOne_PanelId($kind, $tootType, $i . '-form');
        $tootDelUrl = $canWrite ? zberOne_AjaxUrl(['op' => 'delete', 'kind' => $kind, 'type' => $tootType, 'idx' => $i]) : '';
        $group['items'][] = [
            'title' => $title,
            'value' => $text,
            'idx' => $i,
            'delUrl' => $tootDelUrl,
            'formPanel' => $canWrite ? $tootFormId : '',
        ];

        // 子项面板（展示 + 编辑/删除入口）
        $panels[] = [
            'id' => zberOne_PanelId($kind, $tootType, $i),
            'type' => $tootType,
            'tpl' => 'plugin_zberOne_panel-toot',
            'active' => false,
            'head' => $prefix . '说说 · ' . $title,
            'text' => $text,
            'meta' => '发布时间：' . ('' !== $createdAt ? $createdAt : '—'),
            'idx' => $i,
            'delUrl' => $tootDelUrl,
            'formPanel' => $canWrite ? $tootFormId : '',
        ];

        if (!$canWrite) {
            continue;
        }

        // 该条的编辑表单面板（预填现值）
        $tootFields = [];
        foreach (zberOne_FormFields($tootType) as $f) {
            $tootFields[] = [
                'name' => $f['name'],
                'label' => $f['label'],
                'value' => isset($toot[$f['name']]) ? (string) $toot[$f['name']] : '',
            ];
        }
        $panels[] = [
            'id' => $tootFormId,
            'type' => $tootType,
            'tpl' => 'plugin_zberOne_panel-form',
            'active' => false,
            'head' => $prefix . '说说 · 编辑',
            'fields' => $tootFields,
            'action' => zberOne_AjaxUrl(['op' => 'update', 'kind' => $kind, 'type' => $tootType, 'idx' => $i]),
            'idx' => $i,
            'formPanel' => $tootFormId,
            'backPanel' => zberOne_PanelId($kind, $tootType),
            'empty' => '',
        ];
    }

    // 该组的添加表单面板（form 后缀；只给「我」）
    if ($canWrite) {
        $addFields = [];
        foreach (zberOne_FormFields($tootType) as $f) {
            $addFields[] = ['name' => $f['name'], 'label' => $f['label'], 'value' => ''];
        }
        $panels[] = [
            'id' => zberOne_PanelId($kind, $tootType, 'form'),
            'type' => $tootType,
            'tpl' => 'plugin_zberOne_panel-form',
            'active' => false,
            'head' => $prefix . '说说 · 添加',
            'fields' => $addFields,
            'action' => zberOne_AjaxUrl(['op' => 'add', 'kind' => $kind, 'type' => $tootType]),
            'idx' => null,
            'formPanel' => zberOne_PanelId($kind, $tootType, 'form'),
            'backPanel' => zberOne_PanelId($kind, $tootType),
            'empty' => '',
        ];
    }

    $panels[] = $group;

    // 文章 / 视频：组面板 + 每条的子项面板 + 每条的表单面板 + 该组一个添加表单面板
    $entries = [
        ['type' => zberOne_base::TYPE_POST, 'label' => $prefix . '文章', 'empty' => '（暂无文章）', 'list' => $data->Posts()],
        ['type' => zberOne_base::TYPE_VIDEO, 'label' => $prefix . '视频', 'empty' => '（暂无视频）', 'list' => $data->Videos()],
    ];
    foreach ($entries as $entry) {
        $etype = $entry['type'];
        $group = [
            'id' => zberOne_PanelId($kind, $etype),
            'type' => $etype,
            'tpl' => 'plugin_zberOne_panel-group',
            'active' => false,
            'head' => $entry['label'],
            'items' => [],
            'addUrl' => $canWrite ? zberOne_PanelId($kind, $etype, 'form') : '',
            'empty' => $entry['empty'],
        ];
        foreach ($entry['list'] as $i => $item) {
            $title = zberOneTab_EntryTitle($item);
            $url = isset($item['url']) ? trim((string) $item['url']) : '';

            $entryFormId = zberOne_PanelId($kind, $etype, $i . '-form');
            $entryDelUrl = $canWrite ? zberOne_AjaxUrl(['op' => 'delete', 'kind' => $kind, 'type' => $etype, 'idx' => $i]) : '';
            $group['items'][] = [
                'title' => $title,
                'value' => $url,
                'idx' => $i,
                'delUrl' => $entryDelUrl,
                'formPanel' => $canWrite ? $entryFormId : '',
            ];

            $panels[] = [
                'id' => zberOne_PanelId($kind, $etype, $i),
                'type' => $etype,
                'tpl' => 'plugin_zberOne_panel-link',
                'active' => false,
                'head' => $entry['label'] . ' · ' . $title,
                'url' => $url,
                'idx' => $i,
                'delUrl' => $entryDelUrl,
                'formPanel' => $canWrite ? $entryFormId : '',
            ];

            if (!$canWrite) {
                continue;
            }

            $entryFields = [];
            foreach (zberOne_FormFields($etype) as $f) {
                $entryFields[] = [
                    'name' => $f['name'],
                    'label' => $f['label'],
                    'value' => isset($item[$f['name']]) ? (string) $item[$f['name']] : '',
                ];
            }
            $panels[] = [
                'id' => $entryFormId,
                'type' => $etype,
                'tpl' => 'plugin_zberOne_panel-form',
                'active' => false,
                'head' => $entry['label'] . ' · 编辑',
                'fields' => $entryFields,
                'action' => zberOne_AjaxUrl(['op' => 'update', 'kind' => $kind, 'type' => $etype, 'idx' => $i]),
                'idx' => $i,
                'formPanel' => $entryFormId,
                'backPanel' => zberOne_PanelId($kind, $etype),
                'empty' => '',
            ];
        }

        // 该组的添加表单面板（只给「我」）
        if ($canWrite) {
            $addFields = [];
            foreach (zberOne_FormFields($etype) as $f) {
                $addFields[] = ['name' => $f['name'], 'label' => $f['label'], 'value' => ''];
            }
            $panels[] = [
                'id' => zberOne_PanelId($kind, $etype, 'form'),
                'type' => $etype,
                'tpl' => 'plugin_zberOne_panel-form',
                'active' => false,
                'head' => $entry['label'] . ' · 添加',
                'fields' => $addFields,
                'action' => zberOne_AjaxUrl(['op' => 'add', 'kind' => $kind, 'type' => $etype]),
                'idx' => null,
                'formPanel' => zberOne_PanelId($kind, $etype, 'form'),
                'backPanel' => zberOne_PanelId($kind, $etype),
                'empty' => '',
            ];
        }

        $panels[] = $group;
    }

    return $panels;
}

/**
 * 渲染指定来源的左栏 / 右栏 HTML 片段（供首屏与 ajax 端点共用）.
 *
 * 只返回两栏的外层容器本身（.zber-one-side / .zber-one-main），
 * 由前端整体替换对应容器，避免把容器套进容器里。
 * 两段都从同一次 GetTabOneMenu / GetTabOnePanels 取数，
 * 保证菜单项 data-panel 与面板 id 成对一致。
 *
 * @param string $kind 数据来源
 *
 * @return array ['sidebar' => html, 'main' => html]
 */
function zberOne_RenderTabOneParts($kind = zberOne_base::KIND_ME)
{
    global $zbp;

    $slot = zberOne_GetTabOneSlot($kind);
    if (null === $slot) {
        return ['sidebar' => '', 'main' => ''];
    }

    $menuTpl = 'plugin_zberOne_one-left';
    $mainTpl = 'plugin_zberOne_one-right';

    if (!$zbp->template->HasTemplate($menuTpl) || !$zbp->template->HasTemplate($mainTpl)) {
        $zbp->BuildTemplate();
    }

    $zbp->template->SetTags('zberOneTabSlot', $slot);
    $zbp->template->SetTags('zberOneTabMenu', zberOne_GetTabOneMenu($kind));
    $zbp->template->SetTags('zberOneTabPanels', zberOne_GetTabOnePanels($kind));

    ob_start();
    $zbp->template->Display($menuTpl);
    $sidebar = ob_get_clean();

    ob_start();
    $zbp->template->Display($mainTpl);
    $main = ob_get_clean();

    return ['sidebar' => $sidebar, 'main' => $main];
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

    $tplName = 'plugin_zberOne_slide-item';

    // 模板未编译时先重建，开发期改动 tpl 文件后无需手动刷新
    if (!$zbp->template->HasTemplate($tplName)) {
        $zbp->BuildTemplate();
    }

    $zbp->template->SetTags('zberOneTabSlot', $slot);
    $zbp->template->SetTags('zberOneTabMenu', zberOne_GetTabOneMenu($kind));
    $zbp->template->SetTags('zberOneTabPanels', zberOne_GetTabOnePanels($kind));

    $zbp->template->Display($tplName);
}

/**
 * 按请求参数取表单提交的字段数据.
 *
 * @param string $type 子级项目
 *
 * @return array 字段名 => 提交值（字符串）
 */
function zberOne_ReadPostFields($type)
{
    $out = [];
    foreach (zberOne_FormFields($type) as $f) {
        $out[$f['name']] = (string) GetVars($f['name'], 'POST', '');
    }

    return $out;
}

/**
 * cmd.php ajax 入口（act=ajax&src=zberOne）：权限与 CSRF 校验后分派，按内置 JsonReturn/JsonError 输出.
 *
 * cmd.php 的 ajax 动作只要求访客级权限，写操作必须在这里自行补校验。
 *
 * @param string $src cmd.php 传来的来源标识
 */
function zberOne_CmdAjax($src)
{
    global $zbp;

    if ('zberOne' !== $src) {
        return;
    }
    if (!$zbp->CheckRights('root')) {
        JsonError(6, '没有权限', null);
    }
    if (!CheckCSRFTokenValid()) {
        JsonError(5, 'CSRF 校验失败', null);
    }

    $result = zberOne_Ajax();
    if (!$result['ok']) {
        JsonError(1, $result['message'], null);
    }

    JsonReturn([
        'sidebar' => $result['sidebar'],
        'main' => $result['main'],
        'activePanel' => $result['activePanel'],
    ]);
}

/**
 * ajax 业务分派：按 op 执行新增 / 编辑 / 删除，返回重渲染后的整栏 HTML.
 *
 * op / kind / type / idx 走 URL 查询串（GET），表单字段走 POST 主体。
 *
 * @return array ['ok' => bool, 'message' => string, 'sidebar' => html, 'main' => html, 'activePanel' => id]
 */
function zberOne_Ajax()
{
    $act = (string) GetVars('op', 'GET', '');
    $kind = (string) GetVars('kind', 'GET', zberOne_base::KIND_ME);
    $type = (string) GetVars('type', 'GET', '');
    $idxRaw = GetVars('idx', 'GET', null);

    $ok = false;
    $message = '';

    $data = new zberOne_base($kind);
    if (zberOne_base::KIND_ME !== $data->Kind()) {
        $message = '只允许编辑「我」的数据';
    } elseif (!in_array($type, zberOne_base::Types(), true)) {
        $message = '未知的数据类型';
    } else {
        $idx = (null === $idxRaw || '' === $idxRaw) ? null : (int) $idxRaw;
        $fields = zberOne_ReadPostFields($type);

        if ('add' === $act) {
            if (zberOne_base::TYPE_ONE === $type) {
                $ok = $data->AddOne($fields);
            } elseif (zberOne_base::TYPE_TOOT === $type) {
                $ok = $data->AddToot($fields);
            } elseif (zberOne_base::TYPE_POST === $type) {
                $ok = $data->AddPost($fields);
            } elseif (zberOne_base::TYPE_VIDEO === $type) {
                $ok = $data->AddVideo($fields);
            }
            $message = $ok ? '添加成功' : '添加失败';
        } elseif ('update' === $act) {
            if (zberOne_base::TYPE_ONE === $type) {
                $ok = $data->UpdateOne($fields);
            } elseif (null !== $idx) {
                if (zberOne_base::TYPE_TOOT === $type) {
                    $ok = $data->UpdateToot($idx, $fields);
                } elseif (zberOne_base::TYPE_POST === $type) {
                    $ok = $data->UpdatePost($idx, $fields);
                } elseif (zberOne_base::TYPE_VIDEO === $type) {
                    $ok = $data->UpdateVideo($idx, $fields);
                }
            }
            $message = $ok ? '编辑成功' : '编辑失败';
        } elseif ('delete' === $act) {
            if (zberOne_base::TYPE_ONE === $type) {
                $message = '信息不支持删除';
            } elseif (null === $idx) {
                $message = '缺少序号';
            } else {
                if (zberOne_base::TYPE_TOOT === $type) {
                    $ok = $data->DeleteToot($idx);
                } elseif (zberOne_base::TYPE_POST === $type) {
                    $ok = $data->DeletePost($idx);
                } elseif (zberOne_base::TYPE_VIDEO === $type) {
                    $ok = $data->DeleteVideo($idx);
                }
                $message = $ok ? '删除成功' : '删除失败';
            }
        } else {
            $message = '未知的操作';
        }
    }

    $parts = zberOne_RenderTabOneParts($kind);

    return [
        'ok' => $ok,
        'message' => $message,
        'sidebar' => $parts['sidebar'],
        'main' => $parts['main'],
        'activePanel' => zberOne_PanelId($kind, $type),
    ];
}
