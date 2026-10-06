<?php

/**
 * zberOne_base — usr-data 目录各主项目数据的读写封装.
 *
 * 数据分两级：
 *   kind  父级，谁的：me（读 usr-data）/ other（将来由 id 从外部获取）
 *   type  子级，哪一项：one（信息）/ toot（说说）/ post（文章）/ video（视频）/ git（仓库）
 *
 * 只有「我」的数据落在本地：数据目录 zberOne/usr-data/，每个子项目一个 json
 *   one.json   信息：{id, name, description}
 *   toot.json  说说：[{text, created_at}]（created_at 由写入时自动生成）
 *   post.json  文章：[{title, url, created_at}]（created_at 由写入时自动生成）
 *   video.json 视频：[{title, url, created_at}]（created_at 由写入时自动生成）
 *   git.json   仓库：[{title, url, created_at}]（created_at 由写入时自动生成）
 *
 * 读写只接受「我」（kind = me）自己的数据；other 的数据来自外部，Load 恒空、写入一律拒绝。
 */
if (!class_exists('zberOne_base')) {
    class zberOne_base
    {
        /** 父级来源：「我」 */
        public const KIND_ME = 'me';

        /** 父级来源：他人（将来由 id 从外部获取） */
        public const KIND_OTHER = 'other';

        /** 子级项目：信息 */
        public const TYPE_ONE = 'one';

        /** 子级项目：说说 */
        public const TYPE_TOOT = 'toot';

        /** 子级项目：文章 */
        public const TYPE_POST = 'post';

        /** 子级项目：视频 */
        public const TYPE_VIDEO = 'video';

        /** 子级项目：仓库 */
        public const TYPE_GIT = 'git';

        /**
         * 允许的数据来源列表。
         *
         * @var array
         */
        private static $kinds = [
            self::KIND_ME,
            self::KIND_OTHER,
        ];

        /**
         * 允许的数据类型列表。
         *
         * @var array
         */
        private static $types = [
            self::TYPE_ONE,
            self::TYPE_TOOT,
            self::TYPE_POST,
            self::TYPE_VIDEO,
            self::TYPE_GIT,
        ];

        /**
         * 当前数据来源，构造时确定。
         *
         * @var string
         */
        private $kind = self::KIND_ME;

        /**
         * usr-data 目录绝对路径。
         *
         * @var string
         */
        private $dataDir = '';

        /**
         * 已读取数据的内存缓存（按类型）。
         *
         * @var array
         */
        private $cache = [];

        /**
         * 发布到 pub-data/ 时写入的部署地址（url 字段）。
         *
         * 由调用方在写入「我」的数据前注入（如后台 ajax 处的站点地址）；
         * 缺省为空串，仅在发布 one 时作为 url 字段带出。
         *
         * @var string
         */
        private $pubUrl = '';

        /**
         * 外部注入的「他人」数据（按类型，请求内有效）。
         *
         * 由 zberOne_LoadHubOther() 从远程发布文件拉取后经 SetOtherData() 注入；
         * 未注入时 Load() 对 other 仍返回空数组。
         *
         * @var array
         */
        private static $otherData = [];

        /**
         * 构造函数。
         *
         * @param string $kind 数据来源：KIND_ME（默认）/ KIND_OTHER，无效值回落到 KIND_ME
         */
        public function __construct($kind = self::KIND_ME)
        {
            $this->dataDir = dirname(__DIR__) . '/usr-data';
            if (in_array($kind, self::$kinds, true)) {
                $this->kind = $kind;
            }
        }

        /**
         * 注入「他人」数据：把远程发布 JSON（PublishOne 的产出）按类型拆存.
         *
         * pub 结构：{info:{...}, toot:[], post:[], video:[], git:[]}；
         * info 拆到 one，其余列表按各自键拆存，非数组一律按空数组处理。
         *
         * @param array $pub 远程发布文件解析结果
         */
        public static function SetOtherData(array $pub)
        {
            $data = [
                self::TYPE_ONE => (isset($pub['info']) && is_array($pub['info'])) ? $pub['info'] : [],
                self::TYPE_TOOT => (isset($pub['toot']) && is_array($pub['toot'])) ? array_values($pub['toot']) : [],
                self::TYPE_POST => (isset($pub['post']) && is_array($pub['post'])) ? array_values($pub['post']) : [],
                self::TYPE_VIDEO => (isset($pub['video']) && is_array($pub['video'])) ? array_values($pub['video']) : [],
                self::TYPE_GIT => (isset($pub['git']) && is_array($pub['git'])) ? array_values($pub['git']) : [],
            ];
            self::$otherData = $data;
        }

        /**
         * 全部可用数据类型。
         *
         * @return array
         */
        public static function Types()
        {
            return self::$types;
        }

        /**
         * 当前数据来源。
         *
         * @return string
         */
        public function Kind()
        {
            return $this->kind;
        }

        /**
         * 数据目录绝对路径。
         *
         * @return string
         */
        public function Dir()
        {
            return $this->dataDir;
        }

        /**
         * 指定类型对应的 json 文件路径；类型无效时返回空字符串。
         *
         * @param string $type
         *
         * @return string
         */
        public function Path($type)
        {
            if (!in_array($type, self::$types, true)) {
                return '';
            }

            return $this->dataDir . '/' . $type . '.json';
        }

        /**
         * 读取指定类型的数据（带内存缓存）；文件缺失或解析失败时返回空数组。
         *
         * 只有「我」的数据来自 usr-data；other 的数据来自外部注入（SetOtherData），
         * 未注入时返回空数组。
         *
         * @param string $type
         *
         * @return array
         */
        public function Load($type)
        {
            if (self::KIND_ME !== $this->kind) {
                if (!in_array($type, self::$types, true)) {
                    return [];
                }

                return array_key_exists($type, self::$otherData) ? self::$otherData[$type] : [];
            }
            if (!in_array($type, self::$types, true)) {
                return [];
            }
            if (array_key_exists($type, $this->cache)) {
                return $this->cache[$type];
            }

            $data = [];
            $path = $this->Path($type);
            if (is_readable($path)) {
                $json = json_decode((string) file_get_contents($path), true);
                if (is_array($json)) {
                    $data = $json;
                }
            }

            $this->cache[$type] = $data;

            return $data;
        }

        /**
         * 信息。
         *
         * @return array
         */
        public function One()
        {
            return $this->Load(self::TYPE_ONE);
        }

        /**
         * 说说。
         *
         * @return array
         */
        public function Toots()
        {
            return $this->Load(self::TYPE_TOOT);
        }

        /**
         * 文章。
         *
         * @return array
         */
        public function Posts()
        {
            return $this->Load(self::TYPE_POST);
        }

        /**
         * 视频。
         *
         * @return array
         */
        public function Videos()
        {
            return $this->Load(self::TYPE_VIDEO);
        }

        /**
         * 仓库。
         *
         * @return array
         */
        public function Gits()
        {
            return $this->Load(self::TYPE_GIT);
        }

        /**
         * 清空内存缓存，使后续 Load 重新读取文件。
         *
         * @param null|string $type 指定类型；为空时清空全部
         */
        public function ClearCache($type = null)
        {
            if (null === $type || '' === $type) {
                $this->cache = [];

                return;
            }
            unset($this->cache[$type]);
        }

        /* ---------- 初始化（安装时生成实际数据文件） ---------- */

        /**
         * 初始化「我」的数据文件。
         *
         * 目录不存在时创建；文件缺失时按默认结构生成。已有文件一律不覆盖。
         *
         * @return bool
         */
        public function InitFiles()
        {
            if (!is_dir($this->dataDir) && !@mkdir($this->dataDir, 0755, true) && !is_dir($this->dataDir)) {
                return false;
            }

            foreach (self::$types as $type) {
                $path = $this->dataDir . '/' . $type . '.json';
                if (is_file($path)) {
                    continue;
                }

                $this->writeJson($path, $this->defaultData($type));
            }

            $this->cache = [];

            return true;
        }

        /* ---------- 写入（只写「我」自己的数据） ---------- */

        /**
         * 写入指定类型的数据（整份覆盖）。
         *
         * 只有「我」（KIND_ME）有写入入口；目录不存在时自动创建。
         * 写盘成功后清空该类型的内存缓存，使后续 Load 重新读盘。
         *
         * @param string     $type
         * @param null|array $data 待写入的数据
         *
         * @return bool
         */
        public function Save($type, $data = null)
        {
            if (self::KIND_ME !== $this->kind) {
                return false;
            }
            if (!in_array($type, self::$types, true)) {
                return false;
            }
            if (!is_dir($this->dataDir) && !@mkdir($this->dataDir, 0755, true) && !is_dir($this->dataDir)) {
                return false;
            }

            $ok = $this->writeJson($this->Path($type), is_array($data) ? $data : []);
            if ($ok) {
                $this->ClearCache($type);
            }

            return $ok;
        }

        /**
         * 设置发布到 pub-data/ 时使用的部署地址（url 字段）。
         *
         * @param string $url 当前部署地址（如站点 host）
         *
         * @return static
         */
        public function SetPubUrl($url)
        {
            $this->pubUrl = (string) $url;

            return $this;
        }

        /**
         * 整份覆盖写「信息」。
         *
         * @param null|array $data
         *
         * @return bool
         */
        public function SaveOne($data = null)
        {
            return $this->Save(self::TYPE_ONE, $data);
        }

        /**
         * 整份覆盖写「说说」。
         *
         * @param null|array $data
         *
         * @return bool
         */
        public function SaveToots($data = null)
        {
            return $this->Save(self::TYPE_TOOT, $data);
        }

        /**
         * 整份覆盖写「文章」。
         *
         * @param null|array $data
         *
         * @return bool
         */
        public function SavePosts($data = null)
        {
            return $this->Save(self::TYPE_POST, $data);
        }

        /**
         * 整份覆盖写「视频」。
         *
         * @param null|array $data
         *
         * @return bool
         */
        public function SaveVideos($data = null)
        {
            return $this->Save(self::TYPE_VIDEO, $data);
        }

        /**
         * 整份覆盖写「仓库」。
         *
         * @param null|array $data
         *
         * @return bool
         */
        public function SaveGits($data = null)
        {
            return $this->Save(self::TYPE_GIT, $data);
        }

        /**
         * 新增/覆盖「信息」（one 是单对象，整条替换）。
         *
         * @param null|array $data
         *
         * @return bool
         */
        public function AddOne($data = null)
        {
            return $this->SaveOne($this->pick(self::TYPE_ONE, $data));
        }

        /**
         * 追加一条「说说」。
         *
         * 发布时间由服务端在写入时确定，不接受调用方传入。
         *
         * @param null|array $data
         *
         * @return bool
         */
        public function AddToot($data = null)
        {
            $list = $this->Toots();
            $item = $this->pick(self::TYPE_TOOT, $data);
            $item['created_at'] = date('Y-m-d H:i:s');
            $list[] = $item;

            return $this->SaveToots(array_values($list));
        }

        /**
         * 追加一条「文章」。
         *
         * 发布时间由服务端在写入时确定，不接受调用方传入。
         *
         * @param null|array $data
         *
         * @return bool
         */
        public function AddPost($data = null)
        {
            $list = $this->Posts();
            $item = $this->pick(self::TYPE_POST, $data);
            $item['created_at'] = date('Y-m-d H:i:s');
            $list[] = $item;

            return $this->SavePosts(array_values($list));
        }

        /**
         * 追加一条「视频」。
         *
         * 发布时间由服务端在写入时确定，不接受调用方传入。
         *
         * @param null|array $data
         *
         * @return bool
         */
        public function AddVideo($data = null)
        {
            $list = $this->Videos();
            $item = $this->pick(self::TYPE_VIDEO, $data);
            $item['created_at'] = date('Y-m-d H:i:s');
            $list[] = $item;

            return $this->SaveVideos(array_values($list));
        }

        /**
         * 追加一条「仓库」。
         *
         * 发布时间由服务端在写入时确定，不接受调用方传入。
         *
         * @param null|array $data
         *
         * @return bool
         */
        public function AddGit($data = null)
        {
            $list = $this->Gits();
            $item = $this->pick(self::TYPE_GIT, $data);
            $item['created_at'] = date('Y-m-d H:i:s');
            $list[] = $item;

            return $this->SaveGits(array_values($list));
        }

        /**
         * 覆盖「信息」（one 是单对象，与 AddOne 等价）。
         *
         * @param null|array $data
         *
         * @return bool
         */
        public function UpdateOne($data = null)
        {
            return $this->SaveOne($this->pick(self::TYPE_ONE, $data));
        }

        /**
         * 修改第 $idx 条「说说」。
         *
         * 发布时间由服务端在写入时刷新，不接受调用方传入。
         *
         * @param int        $idx  序号
         * @param null|array $data
         *
         * @return bool
         */
        public function UpdateToot($idx, $data = null)
        {
            $list = $this->Toots();
            $idx = (int) $idx;
            if (!isset($list[$idx])) {
                return false;
            }
            $item = $this->pick(self::TYPE_TOOT, $data);
            $item['created_at'] = date('Y-m-d H:i:s');
            $list[$idx] = $item;

            return $this->SaveToots(array_values($list));
        }

        /**
         * 修改第 $idx 条「文章」。
         *
         * 发布时间由服务端在写入时刷新，不接受调用方传入。
         *
         * @param int        $idx  序号
         * @param null|array $data
         *
         * @return bool
         */
        public function UpdatePost($idx, $data = null)
        {
            $list = $this->Posts();
            $idx = (int) $idx;
            if (!isset($list[$idx])) {
                return false;
            }
            $item = $this->pick(self::TYPE_POST, $data);
            $item['created_at'] = date('Y-m-d H:i:s');
            $list[$idx] = $item;

            return $this->SavePosts(array_values($list));
        }

        /**
         * 修改第 $idx 条「视频」。
         *
         * 发布时间由服务端在写入时刷新，不接受调用方传入。
         *
         * @param int        $idx  序号
         * @param null|array $data
         *
         * @return bool
         */
        public function UpdateVideo($idx, $data = null)
        {
            $list = $this->Videos();
            $idx = (int) $idx;
            if (!isset($list[$idx])) {
                return false;
            }
            $item = $this->pick(self::TYPE_VIDEO, $data);
            $item['created_at'] = date('Y-m-d H:i:s');
            $list[$idx] = $item;

            return $this->SaveVideos(array_values($list));
        }

        /**
         * 修改第 $idx 条「仓库」。
         *
         * 发布时间由服务端在写入时刷新，不接受调用方传入。
         *
         * @param int        $idx  序号
         * @param null|array $data
         *
         * @return bool
         */
        public function UpdateGit($idx, $data = null)
        {
            $list = $this->Gits();
            $idx = (int) $idx;
            if (!isset($list[$idx])) {
                return false;
            }
            $item = $this->pick(self::TYPE_GIT, $data);
            $item['created_at'] = date('Y-m-d H:i:s');
            $list[$idx] = $item;

            return $this->SaveGits(array_values($list));
        }

        /**
         * 删除第 $idx 条「说说」。
         *
         * @param int $idx 序号
         *
         * @return bool
         */
        public function DeleteToot($idx)
        {
            $list = $this->Toots();
            $idx = (int) $idx;
            if (!isset($list[$idx])) {
                return false;
            }
            unset($list[$idx]);

            return $this->SaveToots(array_values($list));
        }

        /**
         * 删除第 $idx 条「文章」。
         *
         * @param int $idx 序号
         *
         * @return bool
         */
        public function DeletePost($idx)
        {
            $list = $this->Posts();
            $idx = (int) $idx;
            if (!isset($list[$idx])) {
                return false;
            }
            unset($list[$idx]);

            return $this->SavePosts(array_values($list));
        }

        /**
         * 删除第 $idx 条「视频」。
         *
         * @param int $idx 序号
         *
         * @return bool
         */
        public function DeleteVideo($idx)
        {
            $list = $this->Videos();
            $idx = (int) $idx;
            if (!isset($list[$idx])) {
                return false;
            }
            unset($list[$idx]);

            return $this->SaveVideos(array_values($list));
        }

        /**
         * 删除第 $idx 条「仓库」。
         *
         * @param int $idx 序号
         *
         * @return bool
         */
        public function DeleteGit($idx)
        {
            $list = $this->Gits();
            $idx = (int) $idx;
            if (!isset($list[$idx])) {
                return false;
            }
            unset($list[$idx]);

            return $this->SaveGits(array_values($list));
        }

        /**
         * 发布 one 到 pub-data/：生成 `<id>.json`（整合全部 usr-data + url），id 缺失则不生成.
         *
         * 在写操作成功后由调用方触发（见 zberOne_Ajax）。仅对「我」生效。
         * 注意：one.id 变更时不会清理 pub-data/ 下的旧 `<旧id>.json`，
         * 旧文件会残留并继续可被拉取，变更 id 后需手动删除旧文件。
         */
        public function PublishOne()
        {
            if (self::KIND_ME !== $this->kind) {
                return;
            }

            $one = $this->One();
            $newId = isset($one['id']) ? (string) $one['id'] : '';
            $pubDir = dirname($this->dataDir) . '/pub-data';

            // id 缺失：不生成
            if ('' === $newId) {
                return;
            }

            $file = $this->pubFileName($newId);
            if ('' === $file) {
                return;
            }
            if (!is_dir($pubDir) && !@mkdir($pubDir, 0755, true) && !is_dir($pubDir)) {
                return;
            }

            // 整合 usr-data/ 内全部数据：info（one 单对象，附部署地址 url）+ 各列表
            $info = $this->One();
            $info['url'] = $this->pubUrl;
            $pub = [
                'info' => $info,
                'toot' => $this->Toots(),
                'post' => $this->Posts(),
                'video' => $this->Videos(),
                'git' => $this->Gits(),
            ];
            $this->writeJson($pubDir . '/' . $file, $pub);
        }

        /**
         * 指定类型允许的字段名列表（输出顺序即持久化顺序）。
         *
         * @param string $type
         *
         * @return array
         */
        private function fields($type)
        {
            switch ($type) {
                case self::TYPE_ONE:
                    return ['id', 'name', 'description'];

                case self::TYPE_TOOT:
                    return ['text', 'created_at'];

                case self::TYPE_POST:
                case self::TYPE_VIDEO:
                case self::TYPE_GIT:
                    return ['title', 'url', 'created_at'];
            }

            return [];
        }

        /**
         * 只取该类型白名单内的字段，缺失补空串，丢弃未知字段。
         *
         * @param string     $type
         * @param null|array $data
         *
         * @return array
         */
        private function pick($type, $data)
        {
            $data = is_array($data) ? $data : [];
            $out = [];
            foreach ($this->fields($type) as $key) {
                $out[$key] = isset($data[$key]) ? (string) $data[$key] : '';
            }

            return $out;
        }

        /**
         * 指定类型的默认数据结构。
         *
         * @param string $type
         *
         * @return array
         */
        private function defaultData($type)
        {
            if (self::TYPE_ONE === $type) {
                return ['id' => '', 'name' => '', 'description' => ''];
            }

            return [];
        }

        /**
         * 写入 json 文件（原子：先写临时文件再 rename 覆盖）。
         *
         * @param string $path
         * @param array  $data
         *
         * @return bool
         */
        private function writeJson($path, $data)
        {
            $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            if (false === $json) {
                return false;
            }

            $tmp = $path . '.tmp';
            if (false === @file_put_contents($tmp, $json . "\n")) {
                return false;
            }
            if (!@rename($tmp, $path)) {
                @unlink($tmp);

                return false;
            }

            return true;
        }

        /**
         * pub-data/ 下的发布文件名（`<id>.json`），非法 id 返回空串。
         *
         * id 仅取最后一段路径（basename）以杜绝目录穿越；纯点号视为非法。
         *
         * @param string $id
         *
         * @return string
         */
        private function pubFileName($id)
        {
            $safe = basename((string) $id);
            if ('' === $safe || '.' === $safe || '..' === $safe) {
                return '';
            }

            return $safe . '.json';
        }
    }
}
