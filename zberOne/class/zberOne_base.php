<?php

/**
 * zberOne_base — usr-data 目录各主项目数据的读取封装.
 *
 * 数据目录：zberOne/usr-data/
 * 每个主项目一个 json 文件：
 *   me.json        「我」：{id, name, description}
 *   my-toot.json   我的说说：[{text, created_at}]
 *   my-post.json   我的文章：[{title, url}]
 *   my-video.json  我的视频：[{title, url}]
 *
 * Save 系列方法为预留的写入入口，持久化逻辑尚未实现。
 */
if (!class_exists('zberOne_base')) {
    class zberOne_base
    {
        /** 「我」 */
        public const TYPE_ME = 'me';

        /** 我的说说 */
        public const TYPE_TOOT = 'my-toot';

        /** 我的文章 */
        public const TYPE_POST = 'my-post';

        /** 我的视频 */
        public const TYPE_VIDEO = 'my-video';

        /**
         * 当前默认操作的数据类型。
         *
         * @var string
         */
        public $type = '';

        /**
         * 允许的数据类型列表。
         *
         * @var array
         */
        private static $types = [
            self::TYPE_ME,
            self::TYPE_TOOT,
            self::TYPE_POST,
            self::TYPE_VIDEO,
        ];

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
         * @param string $type 默认操作的数据类型，可选
         */
        public function __construct($type = '')
        {
            $this->dataDir = dirname(__DIR__) . '/usr-data';
            if (in_array($type, self::$types, true)) {
                $this->type = $type;
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
         * @param null|string $type 为空时使用 $this->type
         *
         * @return string
         */
        public function Path($type = null)
        {
            $type = $this->resolveType($type);
            if ('' === $type) {
                return '';
            }

            return $this->dataDir . '/' . $type . '.json';
        }

        /**
         * 读取指定类型的数据（带内存缓存）；文件缺失或解析失败时返回空数组。
         *
         * @param null|string $type 为空时使用 $this->type
         *
         * @return array
         */
        public function Load($type = null)
        {
            $type = $this->resolveType($type);
            if ('' === $type) {
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
         * 「我」。
         *
         * @return array
         */
        public function Me()
        {
            return $this->Load(self::TYPE_ME);
        }

        /**
         * 我的说说。
         *
         * @return array
         */
        public function Toots()
        {
            return $this->Load(self::TYPE_TOOT);
        }

        /**
         * 我的文章。
         *
         * @return array
         */
        public function Posts()
        {
            return $this->Load(self::TYPE_POST);
        }

        /**
         * 我的视频。
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
            $type = $this->resolveType($type);
            if ('' === $type) {
                $this->cache = [];

                return;
            }
            unset($this->cache[$type]);
        }

        /* ---------- 写入（预留入口，尚未实现） ---------- */

        /**
         * 写入指定类型的数据。
         *
         * TODO: json_encode(JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) 后写入；
         *       目录不存在时自动创建；写入前备份原文件；失败时回滚。
         *
         * @param null|string $type 为空时使用 $this->type
         * @param null|array  $data 待写入的数据
         *
         * @return bool
         */
        public function Save($type = null, $data = null)
        {
            $type = $this->resolveType($type);
            if ('' === $type) {
                return false;
            }

            // 预留入口：写入逻辑尚未实现。
            return false;
        }

        /**
         * 写入「我」。
         *
         * @param null|array $data
         *
         * @return bool
         */
        public function SaveMe($data = null)
        {
            return $this->Save(self::TYPE_ME, $data);
        }

        /**
         * 写入我的说说。
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
         * 写入我的文章。
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
         * 写入我的视频。
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
         * 解析数据类型：入参为空时回退到 $this->type；无效类型返回空字符串。
         *
         * @param null|string $type
         *
         * @return string
         */
        private function resolveType($type)
        {
            if (null === $type || '' === $type) {
                $type = $this->type;
            }
            if (!in_array($type, self::$types, true)) {
                return '';
            }

            return $type;
        }
    }
}
