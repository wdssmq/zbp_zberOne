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
 * 「他人」数据装载：从 hub-data/hub-NN.json 两层随机选站，拉取其远程发布数据注入 other.
 *
 * hub-data/ 下按两位数字分页（hub-01.json、hub-02.json…），每项 {id, name, description, url}：
 * 先随机选一个文件，再在该文件内容里随机选一项，按 url + 站内插件发布路径拼出
 * 远程 pub-data/<id>.json 地址（PublishOne 的产出），拉取解析后注入 zberOne_base 的
 * 静态 other 槽，请求内所有 new zberOne_base(other) 的取数点随之生效。
 *
 * 拉取结果落盘静态缓存（other-cache/<id>.json，含写入时间戳），过期时间由
 * $zbp->Config('zberOne')->cache_ttl 控制（单位小时，当前写死 24）；缓存命中时
 * 直接注入不再发请求。结果在函数内 static 缓存，整个请求只装载一次。
 * 任一步失败（无文件 / JSON 非法 / id 或 url 缺失 / 拉取失败）返回 null，调用处回落空态。
 *
 * @return null|array 选中的 hub 项（含 id / name 等）；失败为 null
 */
function zberOne_LoadHubOther()
{
    static $hit = null;
    static $loaded = false;
    if ($loaded) {
        return $hit;
    }
    $loaded = true;

    // 两层随机之第一层：随机选一个分页文件
    $files = glob(__DIR__ . '/hub-data/hub-??.json');
    if (!is_array($files) || 0 === count($files)) {
        return null;
    }
    $file = $files[mt_rand(0, count($files) - 1)];

    $json = json_decode((string) @file_get_contents($file), true);
    if (!is_array($json) || 0 === count($json)) {
        return null;
    }

    // 两层随机之第二层：文件内容里随机选一项
    $item = $json[mt_rand(0, count($json) - 1)];
    if (!is_array($item)) {
        return null;
    }
    $id = isset($item['id']) ? basename(trim((string) $item['id'])) : '';
    $url = isset($item['url']) ? trim((string) $item['url']) : '';
    if ('' === $id || preg_match('/^\.+$/', $id) || '' === $url || 0 !== strpos($url, 'http')) {
        return null;
    }

    // 静态文件缓存命中且未过期 → 直接注入
    // 缓存结构：{lstTime: 写入时间戳, pub: 远程发布 JSON 原文解析结果}
    $cacheDir = __DIR__ . '/other-cache';
    $cacheFile = $cacheDir . '/' . $id . '.json';
    global $zbp;
    $ttl = 24 * 3600;
    if ($zbp->HasConfig('zberOne') && $zbp->Config('zberOne')->HasKey('cache_ttl')) {
        $ttl = max(0, (int) $zbp->Config('zberOne')->cache_ttl) * 3600;
    }
    $cache = null;
    if (is_readable($cacheFile)) {
        $tmp = json_decode((string) @file_get_contents($cacheFile), true);
        if (is_array($tmp) && isset($tmp['lstTime'], $tmp['pub']) && is_array($tmp['pub'])
            && (time() - (int) $tmp['lstTime']) < $ttl) {
            $cache = $tmp;
        }
    }
    if (null !== $cache) {
        zberOne_base::SetOtherData($cache['pub']);
        $hit = $item;

        return $item;
    }

    // 远程发布文件地址：站点根 + 插件发布路径 + <id>.json
    $pubUrl = rtrim($url, '/') . '/zb_users/plugin/zberOne/pub-data/' . rawurlencode($id) . '.json';

    $body = '';
    $http = null;
    if (class_exists('Network')) {
        $http = Network::Create();
        $http->open('GET', $pubUrl);
        $http->send();
        if (200 == $http->status) {
            $body = (string) $http->responseText;
        }
    }
    if ('' === $body && ini_get('allow_url_fopen')) {
        $ctx = stream_context_create(['http' => ['timeout' => 3]]);
        $body = (string) @file_get_contents($pubUrl, false, $ctx);
    }
    if ('' === $body) {
        return null;
    }

    $pub = json_decode($body, true);
    if (!is_array($pub) || !isset($pub['info']) || !is_array($pub['info'])) {
        return null;
    }
    zberOne_base::SetOtherData($pub);

    // 落盘静态缓存（失败不影响本次注入）
    $hit = $item;
    if (!is_dir($cacheDir) && !@mkdir($cacheDir, 0755, true) && !is_dir($cacheDir)) {
        return $item;
    }
    $json = json_encode(['lstTime' => time(), 'pub' => $pub], JSON_UNESCAPED_UNICODE);
    if (false !== $json) {
        @file_put_contents($cacheFile, $json . "\n");
    }

    return $item;
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
 *   id     来源标识：me 固定为空（数据在 usr-data/one.json）；other 为 hub 随机选中
 *          站点的 id（装载失败时为 any）
 *   name   该来源的显示名（滑动切换区域用），other 取 hub 项的 name
 *   label  该来源下四个主项目的标题前缀
 *
 * @return array
 */
function zberOne_GetTabOneSlots()
{
    // other 的数据按需装载：随机选 hub 站点并拉取其发布数据（请求内只拉一次）
    $hub = zberOne_LoadHubOther();

    return [
        ['kind' => zberOne_base::KIND_ME, 'id' => '', 'name' => '我', 'label' => '我的'],
        [
            'kind' => zberOne_base::KIND_OTHER,
            'id' => (null !== $hub && isset($hub['id'])) ? (string) $hub['id'] : 'any',
            'name' => (null !== $hub && isset($hub['name']) && '' !== trim((string) $hub['name'])) ? (string) $hub['name'] : '他人',
            'label' => '他人的',
        ],
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
 *   type   对应的子级项目（one / toot / post / video / git）
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
        ['title' => $prefix . '仓库', 'type' => 'git', 'id' => $groupName . '-git', 'name' => $groupName, 'open' => false, 'empty' => '（暂无仓库）', 'items' => []],
    ];

    $listMap = [
        'toot' => $data->Toots(),
        'post' => $data->Posts(),
        'video' => $data->Videos(),
        'git' => $data->Gits(),
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
 * @param string          $type 子级项目（one / toot / post / video / git）
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
        zberOne_base::TYPE_GIT => '仓库',
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
        case zberOne_base::TYPE_GIT:
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
 * 「一个」标签页 - 指定来源的右栏静态面板数据（首屏静态渲染用）.
 *
 * 每组两个面板，数量固定、与数据条数无关：
 *   组面板（id = zber-panel-<kind>-<type>）   点左栏组标题（dt）时显示，里面列出该组的子项
 *   表单面板（id 带 'form' 后缀）             新增与编辑共用同一块；静态这块是新增态，
 *                                             信息组没有「新增」概念，静态就是编辑态
 *
 * 条目级面板（子项展示面板，数量随数据条数增长）不在这里产出，
 * 改由 zberOne_RenderPanel 按被点到的那个面板 id 单独构建（见 zberOne_GetTabOneItemPanel）：
 * 取一条说说就只读 toot.json，不必把文章 / 视频的数据一并读进来。
 * 编辑某条时那块表单面板换成编辑态，同样是按需单取（见 zberOne_GetTabOneForm）。
 *
 * 面板字段见 zberOne_GetTabOneGroupPanel / zberOne_GetTabOneForm。
 *
 * @param string $kind 数据来源
 *
 * @return array
 */
function zberOne_GetTabOnePanels($kind = zberOne_base::KIND_ME)
{
    $panels = [];

    foreach (zberOne_base::Types() as $type) {
        $group = zberOne_GetTabOneGroupPanel($kind, $type);
        if (null !== $group) {
            $panels[] = $group;
        }
        $form = zberOne_GetTabOneForm($kind, $type);
        if (null !== $form) {
            $panels[] = $form;
        }
    }

    return $panels;
}

/**
 * 取指定来源下某个类型的数据列表（只读该类型自己的 json）.
 *
 * @param string $kind 数据来源
 * @param string $type 子级项目；信息是单对象不是列表，恒返回空数组
 *
 * @return array
 */
function zberOne_GetTabOneList($kind, $type)
{
    $data = new zberOne_base($kind);

    switch ($type) {
        case zberOne_base::TYPE_TOOT:
            return $data->Toots();

        case zberOne_base::TYPE_POST:
            return $data->Posts();

        case zberOne_base::TYPE_VIDEO:
            return $data->Videos();

        case zberOne_base::TYPE_GIT:
            return $data->Gits();
    }

    return [];
}

/**
 * 单个组面板：点左栏组标题（dt）时显示的那个.
 *
 * 信息组渲染数据源本身的字段行（无子项）；说说 / 文章 / 视频 / 仓库渲染该组的子项列表。
 * 只读该类型自己的数据，不牵连其它类型。
 *
 * 面板字段：
 *   id / type / tpl / active / formIdx    公共字段（tpl 为模板注册名）
 *   formIdx                                该面板当前绑定的编辑对象序号，只有表单面板的编辑态有值
 *                                          （null 表示「这块面板不代表某条数据」）；
 *                                          模板据此往元素上打 data-zber-idx，前端靠它判断态对不对
 *   formPanel                         信息组的「编辑」入口（信息没有子项，编辑入口挂在组面板上）
 *   rows                              信息组：字段行（无数据时为空数组，模板回落到 empty）
 *   colHead                           其余组：子项表格首列的表头文字（说说列的是正文摘要，其余列标题）
 *   items                             其余组：子项列表，每项 title + value + idx + delUrl + formPanel + editIdx
 *   addUrl                            其余组：组面板顶部「添加」链接（指向表单面板的新增态）
 *   empty                             该组无数据时的提示
 *
 * 写入口径：addUrl / formPanel / delUrl 只对「我」生成，「他人」恒为空串。
 * formPanel 是新增与编辑共用的那块表单面板 id；editIdx 是「点编辑要改哪一条」的序号
 * （前端拿它把这块面板换成编辑态），点「添加」时不带。
 *
 * @param string $kind 数据来源
 * @param string $type 子级项目（one / toot / post / video / git）
 *
 * @return null|array 来源或类型无效时返回 null
 */
function zberOne_GetTabOneGroupPanel($kind, $type)
{
    $slot = zberOne_GetTabOneSlot($kind);
    if (null === $slot || !in_array($type, zberOne_base::Types(), true)) {
        return null;
    }

    $canWrite = (zberOne_base::KIND_ME === $kind);
    $panel = [
        'id' => zberOne_PanelId($kind, $type),
        'type' => $type,
        'tpl' => '',
        'active' => (zberOne_base::TYPE_ONE === $type),
        'formIdx' => null,
        'head' => $slot['label'] . zberOne_TypeLabel($type),
        'formPanel' => '',
        'rows' => [],
        'colHead' => '',
        'items' => [],
        'addUrl' => '',
        'empty' => '',
    ];

    // 信息组：组面板即数据源本身的字段行
    if (zberOne_base::TYPE_ONE === $type) {
        $data = new zberOne_base($kind);
        $one = $data->One();
        if (count($one) > 0) {
            $panel['rows'] = [
                ['label' => 'ID', 'value' => isset($one['id']) ? $one['id'] : '', 'key' => 'id'],
                ['label' => '名称', 'value' => isset($one['name']) ? $one['name'] : '', 'key' => 'name'],
                ['label' => '简介', 'value' => isset($one['description']) ? $one['description'] : '', 'key' => 'description'],
            ];
        }
        $panel['tpl'] = 'plugin_zberOne_panel-one';
        $panel['formPanel'] = $canWrite ? zberOne_PanelId($kind, $type, 'form') : '';
        $panel['empty'] = $canWrite
            ? '暂无数据（' . basename($data->Dir()) . '/one.json）'
            : '暂无数据（hub 随机站点拉取失败，稍后重试）';

        return $panel;
    }

    // 其余组：组面板把该组全部子项列成表格
    $panel['tpl'] = 'plugin_zberOne_panel-group';
    $panel['colHead'] = (zberOne_base::TYPE_TOOT === $type) ? '内容' : '标题';
    foreach (zberOne_GetTabOneList($kind, $type) as $i => $item) {
        $panel['items'][] = [
            'title' => (zberOne_base::TYPE_TOOT === $type) ? zberOneTab_TootTitle($item) : zberOneTab_EntryTitle($item),
            'value' => (zberOne_base::TYPE_TOOT === $type)
                ? (isset($item['text']) ? (string) $item['text'] : '')
                : (isset($item['url']) ? trim((string) $item['url']) : ''),
            'idx' => $i,
            'delUrl' => $canWrite ? zberOne_AjaxUrl(['op' => 'delete', 'kind' => $kind, 'type' => $type, 'idx' => $i]) : '',
            'formPanel' => $canWrite ? zberOne_PanelId($kind, $type, 'form') : '',
            'editIdx' => $canWrite ? $i : null,
        ];
    }
    $panel['addUrl'] = $canWrite ? zberOne_PanelId($kind, $type, 'form') : '';
    $panel['empty'] = '（暂无' . zberOne_TypeLabel($type) . '）';

    return $panel;
}

/**
 * 单个表单面板：新增与编辑共用这一块（id 恒为 zber-panel-<kind>-<type>-form）.
 *
 * $idx 为空 = 新增态：字段留空、提交到 op=add；
 * $idx 有值 = 编辑态：预填该条现值、提交到 op=update&idx=<序号>。
 * 信息组是单对象、没有「新增」一说，$idx 为空时也走编辑态（预填现值 + op=update）。
 * 序号越界（数据刚被改过或删掉）时返回 null，由调用处当作「面板不存在」。
 *
 * 面板字段：
 *   id / type / tpl / active / head   公共字段（head 带「添加」或「编辑」）
 *   formIdx                           该面板当前代表的编辑对象序号；新增态为 null
 *   fields                            字段列表，每项 name + label + value
 *   action                            提交地址（已带 token）
 *   formPanel / backPanel             自身的面板 id / 「取消」回到的组面板 id
 *   empty                             恒为空串（表单面板不由 empty 兜底）
 *
 * 只有「我」有写入入口，「他人」返回 null。
 *
 * @param string   $kind 数据来源
 * @param string   $type 子级项目（one / toot / post / video / git）
 * @param null|int $idx  要编辑的序号；省略 = 新增态
 *
 * @return null|array 来源 / 类型无效、不是「我」，或序号越界时返回 null
 */
function zberOne_GetTabOneForm($kind, $type, $idx = null)
{
    $slot = zberOne_GetTabOneSlot($kind);
    if (null === $slot || !in_array($type, zberOne_base::Types(), true)) {
        return null;
    }
    if (zberOne_base::KIND_ME !== $kind) {
        return null;
    }

    // one 是单对象，没有「新增」：$idx 为空时也按编辑处理并预填现值
    $isOne = (zberOne_base::TYPE_ONE === $type);
    $isEdit = true;
    $editIdx = null;

    if (null !== $idx) {
        // 信息没有列表（zberOne_GetTabOneList 恒为空），传了序号自然落到「不存在」
        $list = zberOne_GetTabOneList($kind, $type);
        $idx = (int) $idx;
        if (!isset($list[$idx])) {
            return null;
        }
        $item = $list[$idx];
        $editIdx = $idx;
    } elseif ($isOne) {
        $item = (new zberOne_base($kind))->One();
    } else {
        $item = [];
        $isEdit = false;
    }

    $fields = [];
    foreach (zberOne_FormFields($type) as $f) {
        $fields[] = [
            'name' => $f['name'],
            'label' => $f['label'],
            'value' => isset($item[$f['name']]) ? (string) $item[$f['name']] : '',
        ];
    }

    $params = ['op' => ($isEdit ? 'update' : 'add'), 'kind' => $kind, 'type' => $type];
    if (null !== $editIdx) {
        $params['idx'] = $editIdx;
    }

    return [
        'id' => zberOne_PanelId($kind, $type, 'form'),
        'type' => $type,
        'tpl' => 'plugin_zberOne_panel-form',
        'active' => false,
        'formIdx' => $editIdx,
        'head' => $slot['label'] . zberOne_TypeLabel($type) . ($isEdit ? ' · 编辑' : ' · 添加'),
        'fields' => $fields,
        'action' => zberOne_AjaxUrl($params),
        'formPanel' => zberOne_PanelId($kind, $type, 'form'),
        'backPanel' => zberOne_PanelId($kind, $type),
        'empty' => '',
    ];
}

/**
 * 单个条目级展示面板：说说 / 文章 / 视频里的某一条.
 *
 * 由 zberOne_RenderPanel 按被点到的面板 id 单条构建，只读该类型自己的数据。
 * 序号越界（数据刚被改过或删掉了）时返回 null，由调用处当作「面板不存在」。
 * 编辑该条用的表单不在这里 —— 新增与编辑共用组里的那块表单面板，
 * 这里只给出 formPanel（表单面板 id）与 editIdx（该条序号）供前端切到编辑态。
 *
 * 面板字段：id / type / tpl / active / head / idx / editIdx / formPanel / delUrl，
 *   说说另有 text + meta，文章 / 视频 / 仓库另有 url + meta（发布时间，缺失显示 —）。
 *   editIdx 给「编辑」入口用（换成编辑态时带的序号）；formIdx 恒为 null ——
 *   展示面板不代表编辑态，别让前端把它当成「已经是编辑某条的态」。
 *
 * @param string $kind 数据来源
 * @param string $type 子级项目（toot / post / video / git；信息没有条目级面板）
 * @param int    $idx  子项序号
 *
 * @return null|array 来源 / 类型无效或数据不存在时返回 null
 */
function zberOne_GetTabOneItemPanel($kind, $type, $idx)
{
    $slot = zberOne_GetTabOneSlot($kind);
    if (null === $slot || zberOne_base::TYPE_ONE === $type) {
        return null;
    }

    $idx = (int) $idx;
    $list = zberOne_GetTabOneList($kind, $type);
    if (!isset($list[$idx])) {
        return null;
    }

    $canWrite = (zberOne_base::KIND_ME === $kind);
    $item = $list[$idx];
    $isToot = (zberOne_base::TYPE_TOOT === $type);
    $title = $isToot ? zberOneTab_TootTitle($item) : zberOneTab_EntryTitle($item);
    $panel = [
        'id' => zberOne_PanelId($kind, $type, $idx),
        'type' => $type,
        'tpl' => $isToot ? 'plugin_zberOne_panel-toot' : 'plugin_zberOne_panel-link',
        'active' => false,
        'idx' => $idx,
        'editIdx' => $canWrite ? $idx : null,
        'formIdx' => null,
        'head' => $slot['label'] . zberOne_TypeLabel($type) . ' · ' . $title,
        'delUrl' => $canWrite ? zberOne_AjaxUrl(['op' => 'delete', 'kind' => $kind, 'type' => $type, 'idx' => $idx]) : '',
        'formPanel' => $canWrite ? zberOne_PanelId($kind, $type, 'form') : '',
    ];

    if ($isToot) {
        $createdAt = isset($item['created_at']) ? trim((string) $item['created_at']) : '';
        $panel['text'] = isset($item['text']) ? (string) $item['text'] : '';
        $panel['meta'] = '发布时间：' . ('' !== $createdAt ? $createdAt : '—');
    } else {
        $createdAt = isset($item['created_at']) ? trim((string) $item['created_at']) : '';
        $panel['url'] = isset($item['url']) ? trim((string) $item['url']) : '';
        $panel['meta'] = '发布时间：' . ('' !== $createdAt ? $createdAt : '—');
    }

    return $panel;
}

/**
 * 按面板 id 只构建那一个面板（组面板 / 表单面板 / 条目展示面板）.
 *
 * 面板 id 的构成见 zberOne_PanelId：
 *   zber-panel-<kind>-<type>          组面板
 *   zber-panel-<kind>-<type>-form     表单面板（新增与编辑共用一块），态由 $idx 决定
 *   zber-panel-<kind>-<type>-<序号>   条目级展示面板
 *
 * @param string   $kind
 * @param string   $panelId
 * @param null|int $idx     只对表单面板有意义：要编辑的序号；省略 = 新增态
 *
 * @return null|array 面板 id 不成立时返回 null
 */
function zberOne_GetTabOnePanel($kind, $panelId, $idx = null)
{
    if (null === zberOne_GetTabOneSlot($kind)) {
        return null;
    }

    // 剥掉 'zber-panel-<kind>-' 后剩下的就是 <type>、<type>-form 或 <type>-<序号>
    $prefix = 'zber-panel-' . $kind . '-';
    $panelId = (string) $panelId;
    if (0 !== strpos($panelId, $prefix)) {
        return null;
    }

    $parts = explode('-', substr($panelId, strlen($prefix)));
    $type = array_shift($parts);
    if (!in_array($type, zberOne_base::Types(), true)) {
        return null;
    }

    // 表单面板：序号不作 id 的一部分（否则新增与编辑就成了两块），只从参数进来
    if (['form'] === $parts) {
        if (null !== $idx) {
            $idx = (string) $idx;
            if ('' === $idx || !ctype_digit($idx)) {
                return null;
            }
            $idx = (int) $idx;
        }

        return zberOne_GetTabOneForm($kind, $type, $idx);
    }

    // 无序号 = 这个组的组面板
    if ([] === $parts) {
        return zberOne_GetTabOneGroupPanel($kind, $type);
    }

    // 一个纯数字序号 = 该组的某条子项展示面板
    if (1 === count($parts) && ctype_digit($parts[0])) {
        return zberOne_GetTabOneItemPanel($kind, $type, (int) $parts[0]);
    }

    return null;
}

/**
 * 渲染指定来源里单个面板的 HTML 片段（供右栏按需取用）.
 *
 * 返回面板元素本身（.zber-one-panel），由前端插进右栏；
 * 面板 id 不成立、序号越界（数据已变）时返回空串。与静态渲染共用 tpl/panel-shell.php。
 *
 * @param string   $kind    数据来源
 * @param string   $panelId 面板 id
 * @param null|int $idx     只对表单面板有意义：要编辑的序号；省略 = 新增态
 *
 * @return string
 */
function zberOne_RenderPanel($kind, $panelId, $idx = null)
{
    global $zbp;

    $panel = zberOne_GetTabOnePanel($kind, $panelId, $idx);
    if (null === $panel) {
        return '';
    }

    $tplName = 'plugin_zberOne_panel-shell';
    if (!$zbp->template->HasTemplate($tplName)) {
        $zbp->BuildTemplate();
    }

    // panel-shell.php 读的是调用处的局部 $panel，include 在同一作用域里才拿得到
    ob_start();

    include $zbp->template->GetTemplate($tplName);

    return ob_get_clean();
}

/**
 * 渲染指定来源的左栏 / 右栏 HTML 片段（供首屏与 ajax 端点共用）.
 *
 * 只返回两栏的外层容器本身（.zber-one-side / .zber-one-main），
 * 由前端整体替换对应容器，避免把容器套进容器里。
 * 两段都从同一次 GetTabOneMenu / GetTabOnePanels 取数，
 * 保证菜单项 data-panel 与面板 id 成对一致。
 * 右栏只渲染静态面板（组面板 + 新增态表单面板），
 * 条目展示面板与表单面板的编辑态由 zberOne_RenderPanel 按需单取。
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
    // 条目展示面板与表单面板的编辑态都按需单取，
    // 基址（含 token 与 kind）挂在 slide 上，脚本只需再补 panel=<id>（编辑态再加 idx=<序号>）
    $zbp->template->SetTags('zberOneTabAjaxUrl', zberOne_AjaxUrl(['op' => 'panel', 'kind' => $kind]));

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
 * op=panel 是只读的取面板，其余 op 是写操作。
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

    // 取单个面板：静态只渲染组面板与表单面板（新增态），
    // 条目展示面板、以及表单面板的编辑态都点开时才由这里给（编辑态靠 idx 参数）
    if ('panel' === (string) GetVars('op', 'GET', '')) {
        $panelId = (string) GetVars('panel', 'GET', '');
        $idx = GetVars('idx', 'GET', null);
        $html = zberOne_RenderPanel((string) GetVars('kind', 'GET', zberOne_base::KIND_ME), $panelId, $idx);
        if ('' === $html) {
            JsonError(1, '面板不存在', null);
        }
        JsonReturn(['panel' => $html, 'id' => $panelId]);

        return;
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
    global $zbp;

    $act = (string) GetVars('op', 'GET', '');
    $kind = (string) GetVars('kind', 'GET', zberOne_base::KIND_ME);
    $type = (string) GetVars('type', 'GET', '');
    $idxRaw = GetVars('idx', 'GET', null);

    $ok = false;
    $message = '';

    $data = new zberOne_base($kind);
    // 发布到 pub-data/ 的部署地址（url 字段）取当前站点地址
    $data->SetPubUrl(isset($zbp->host) ? $zbp->host : '');

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
            } elseif (zberOne_base::TYPE_GIT === $type) {
                $ok = $data->AddGit($fields);
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
                } elseif (zberOne_base::TYPE_GIT === $type) {
                    $ok = $data->UpdateGit($idx, $fields);
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
                } elseif (zberOne_base::TYPE_GIT === $type) {
                    $ok = $data->DeleteGit($idx);
                }
                $message = $ok ? '删除成功' : '删除失败';
            }
        } else {
            $message = '未知的操作';
        }
    }

    // 数据保存成功后发布到 pub-data/（整份重发，含全部 usr-data + url）
    if ($ok) {
        $data->PublishOne();
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
