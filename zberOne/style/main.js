// 「一个」标签页的滑动与面板切换
(function () {
    const root = document.getElementById('zber-one-root');
    if (!root) {
        return;
    }
    const track = root.querySelector('.zber-one-track');
    const slides = root.querySelectorAll('.zber-one-slide');

    function activate(index) {
        for (let i = 0; i < slides.length; i++) {
            const slide = slides[i];
            if (i === index) {
                slide.classList.add('active');
            } else {
                slide.classList.remove('active');
            }
            // 激活项左侧的来源向左渐隐、右侧的向右渐隐，遮罩方向相反不能共用
            if (i < index) {
                slide.classList.add('is-before');
                slide.classList.remove('is-after');
            } else if (i > index) {
                slide.classList.add('is-after');
                slide.classList.remove('is-before');
            } else {
                slide.classList.remove('is-before', 'is-after');
            }
        }
        if (track) {
            // 左移 index 个 slide 宽，再右移 index 个漏出宽度：
            // 激活项占据主要空间，前一个来源在左侧漏出一条、后一个在右侧漏出一条
            // （漏出宽度见 style.css 的 --zber-one-peek）
            track.style.transform = 'translateX(calc(' + index + ' * var(--zber-one-peek) - ' + index + ' * 100%))';
        }
    }

    // 显示右栏里指定的面板
    function showPanel(slide, panelId) {
        if (!panelId) {
            return;
        }
        const panels = slide.querySelectorAll('.zber-one-panel');
        for (let i = 0; i < panels.length; i++) {
            if (panels[i].id === panelId) {
                panels[i].classList.add('active');
            } else {
                panels[i].classList.remove('active');
            }
        }
    }

    // 从点击目标向上找祖先（跨自定义元素边界，closest() 在 <ui-tab> 上不可靠）
    function closestOf(el, selector, boundary) {
        let node = el;
        while (node && node !== boundary) {
            if (node.matches && node.matches(selector)) {
                return node;
            }
            node = node.parentNode;
        }

        return null;
    }

    // 从点击目标向上找到左栏里的「子项」或「主项目标题」
    function closestSide(el, slide) {
        let node = el;
        while (node && node !== slide) {
            if (node.classList && node.classList.contains('zber-one-item')) {
                return {
                    item: node,
                    trigger: null
                };
            }
            if (node.tagName === 'UI-TAB') {
                return {
                    item: null,
                    trigger: node
                };
            }
            node = node.parentNode;
        }

        return null;
    }

    // 点击左栏：主项目标题（dt）与子项都带 data-panel，指向要显示的右栏面板
    function onSideClick(el, slide) {
        const hit = closestSide(el, slide);
        if (!hit) {
            return;
        }
        const node = hit.item || hit.trigger;
        const target = closestOf(node, '[data-panel]', slide);
        if (target) {
            showPanel(slide, target.getAttribute('data-panel'));
        }
    }

    // 用服务端返回的整栏 HTML 整体替换左栏与右栏容器，并恢复当前可见面板
    // 服务端返回的是容器本身（.zber-one-side / .zber-one-main），所以换 outerHTML 而不是 innerHTML
    function applyColumns(slide, sidebarHtml, mainHtml, activePanelId) {
        if (sidebarHtml) {
            const side = slide.querySelector('.zber-one-side');
            if (side) {
                side.outerHTML = sidebarHtml;
            }
        }
        if (mainHtml) {
            const main = slide.querySelector('.zber-one-main');
            if (main) {
                main.outerHTML = mainHtml;
            }
        }
        if (activePanelId) {
            showPanel(slide, activePanelId);
        }
    }

    // 发起一次写操作请求，成功后替换整栏；失败提示 err.msg（cmd.php 内置 JSON 格式：{data, err:{code,msg}}）
    function requestWrite(slide, url, body) {
        const options = { method: 'POST', credentials: 'same-origin' };
        if (body) {
            options.body = body;
        }

        return fetch(url, options)
            .then(function (res) { return res.json(); })
            .then(function (json) {
                if (!json || !json.err || json.err.code !== 0) {
                    window.alert((json && json.err && json.err.msg) ? json.err.msg : '操作失败');

                    return;
                }
                const data = json.data || {};
                applyColumns(slide, data.sidebar, data.main, data.activePanel);
            })
            .catch(function () {
                window.alert('请求失败');
            });
    }

    // 提交表单（无刷新）：把表单字段序列化后交给 requestWrite
    function onFormSubmit(form, slide) {
        const body = new FormData(form);
        requestWrite(slide, form.getAttribute('action'), body);
    }

    // 点击删除：先确认，再带 token 请求
    function onDelete(link, slide) {
        if (!window.confirm('确认删除？')) {
            return;
        }
        requestWrite(slide, link.getAttribute('href'), null);
    }

    // 点击「添加 / 编辑」：切到对应的表单面板
    function onEditToggle(el, slide) {
        const panelId = el.getAttribute('data-panel');
        if (panelId) {
            showPanel(slide, panelId);
        }
    }

    root.addEventListener('click', function (e) {
        const slide = closestOf(e.target, '.zber-one-slide', root);
        if (!slide) {
            return;
        }
        const index = Array.prototype.indexOf.call(slides, slide);
        if (index < 0) {
            return;
        }
        // 点到右侧漏出的那一部分（未激活的来源）→ 滑动切换
        if (!slide.classList.contains('active')) {
            activate(index);
            return;
        }

        // 写操作入口优先于面板切换
        const delLink = closestOf(e.target, '[data-zber-del]', slide);
        if (delLink) {
            e.preventDefault();
            onDelete(delLink, slide);
            return;
        }
        const editLink = closestOf(e.target, '[data-zber-edit]', slide);
        if (editLink) {
            e.preventDefault();
            onEditToggle(editLink, slide);
            return;
        }
        const addLink = closestOf(e.target, '[data-zber-add]', slide);
        if (addLink) {
            e.preventDefault();
            onEditToggle(addLink, slide);
            return;
        }
        const cancelBtn = closestOf(e.target, '[data-zber-cancel]', slide);
        if (cancelBtn) {
            e.preventDefault();
            showPanel(slide, cancelBtn.getAttribute('data-panel'));
            return;
        }

        // 激活的左右栏内 → 切换面板
        onSideClick(e.target, slide);
    });

    // 表单提交无刷新
    root.addEventListener('submit', function (e) {
        const slide = closestOf(e.target, '.zber-one-slide', root);
        if (!slide) {
            return;
        }
        const form = closestOf(e.target, '[data-zber-form]', slide);
        if (!form) {
            return;
        }
        e.preventDefault();
        onFormSubmit(form, slide);
    });

    // 默认显示第一个来源（我）
    activate(0);
})();
