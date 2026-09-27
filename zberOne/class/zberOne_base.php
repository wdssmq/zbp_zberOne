<?php

/**
 * zberOne_base — usr-data 目录各主项目数据的读取封装.
 *
 * 数据分两级：
 *   kind  父级，谁的：me（读 usr-data）/ other（将来由 id 从外部获取）
 *   type  子级，哪一项：one（信息）/ toot（说说）/ post（文章）/ video（视频）
 *
 * 只有「我」的数据落在本地：数据目录 zberOne/usr-data/，每个子项目一个 json
 *   one.json   信息：{id, name, description}
 *   toot.json  说说：[{text, created_at}]
 *   post.json  文章：[{title, url}]
 *   video.json 视频：[{title, url}]
 *
 * Save 系列方法为预留的写入入口，持久化逻辑尚未实现；只接受「我」自己的数据。
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
         * 只有「我」的数据来自 usr-data；other 将来由 id 从外部获取，当前恒为空数组。
         *
         * @param string $type
         *
         * @return array
         */
        public function Load($type)
        {
            if (self::KIND_ME !== $this->kind) {
                return [];
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

        /* ---------- 写入（预留入口，尚未实现；只写「我」自己的数据） ---------- */

        /**
         * 写入指定类型的数据。
         *
         * 只有「我」（KIND_ME）有写入入口；other 的数据来自外部，不接受写入。
         *
         * TODO: json_encode(JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) 后写入；
         *       目录不存在时自动创建。
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

            // 预留入口：写入逻辑尚未实现。
            return false;
        }

        /**
         * 写入信息。
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
         * 写入说说。
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
         * 写入文章。
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
         * 写入视频。
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
         * 写入 json 文件；Save 系列实现后可直接复用。
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

            return false !== file_put_contents($path, $json . "\n");
        }
    }
}
