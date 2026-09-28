# lu2 本地副本

LuLu UI（https://l-ui.com/）edge 主题，取用版本 `2026.7.14`。

| 本地文件 | 来源（unpkg 路径） |
| --- | --- |
| Tab.css | `lu2/theme/edge/css/common/ui/Tab.css` |
| Tips.css | `lu2/theme/edge/css/common/ui/Tips.css` |
| Table.css | `lu2/theme/edge/css/common/ui/Table.css` |
| form.css | `lu2/theme/edge/css/common/form.css` |
| Tab.js | `lu2/theme/edge/js/common/ui/Tab.js` |
| Validate.js | `lu2/theme/edge/js/common/ui/Validate.js` |
| ErrorTip.js | `lu2/theme/edge/js/common/ui/ErrorTip.js`（Validate.js 依赖） |
| Follow.js | `lu2/theme/edge/js/common/ui/Follow.js`（ErrorTip.js 依赖） |

升级：按上表逐个下载覆盖。JS 之间是**同目录**相对 import（`Validate.js → ErrorTip.js → Follow.js`），因此全部平铺在本目录；CSS 的图标均为内联 data URI，无外链。上游若新增依赖，需一并下载到本目录，缺失会在浏览器里表现为 404 + 组件失效。
