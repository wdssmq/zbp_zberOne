// 「一个」标签页的滑动、面板切换与条目级面板的按需取回
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

    // 面板按需取回：静态只渲染组面板与新增态表单面板，其余点开时才取
    // 取回的是面板元素本身（与静态渲染共用同一模板），插进右栏后再切 active
    // 每块面板记一个「最近一次请求的态」，用来挡连点、并丢弃被新意图取代的迟到响应
    const pendingPanels = new Map();

    function findPanel(slide, panelId) {
        const main = slide.querySelector('.zber-one-main');
        return main ? main.querySelector('.zber-one-panel[data-panel="' + panelId + '"]') : null;
    }

    // 面板当前的编辑对象序号；没有该标记 = null（组面板、新增态表单、条目展示面板）
    function panelIdx(panel) {
        const raw = panel.getAttribute('data-zber-idx');
        return (null === raw || '' === raw) ? null : parseInt(raw, 10);
    }

    function insertPanel(slide, html, panelId, idx) {
        const main = slide.querySelector('.zber-one-main');
        if (!main) {
            return;
        }
        const holder = document.createElement('div');
        holder.innerHTML = html;
        const panel = holder.firstElementChild;
        // 片段必须正好是这一个面板、且态对得上，否则宁可不插（免得把异常输出塞进右栏）
        if (!panel || panelId !== panel.getAttribute('data-panel') || panelIdx(panel) !== idx) {
            return;
        }
        // 新增与编辑共用同一块表单面板：右栏已有同 id 的那块时换掉它，不是再插一块
        const exist = findPanel(slide, panelId);
        if (exist) {
            exist.replaceWith(panel);
        } else {
            main.appendChild(panel);
        }
        showPanel(slide, panelId);
        initForms(panel, slide);
    }

    function loadPanel(slide, panelId, idx) {
        const base = slide.getAttribute('data-zber-ajax') || '';
        if (!base) {
            return;
        }
        const key = panelId + '#' + (null === idx ? '' : idx);
        // 同一块面板的同一个态还在路上时重复点只发一次请求
        if (pendingPanels.get(panelId) === key) {
            return;
        }
        pendingPanels.set(panelId, key);

        let url = base + '&panel=' + encodeURIComponent(panelId);
        if (null !== idx) {
            url += '&idx=' + encodeURIComponent(idx);
        }

        fetch(url, { credentials: 'same-origin' })
            .then(function (res) { return res.json(); })
            .then(function (json) {
                // 期间又点了别的态 → 这次的响应已经过时，丢掉
                if (pendingPanels.get(panelId) !== key) {
                    return;
                }
                pendingPanels.delete(panelId);
                if (!json || !json.err || json.err.code !== 0 || !json.data || !json.data.panel) {
                    window.alert((json && json.err && json.err.msg) ? json.err.msg : '面板加载失败');

                    return;
                }
                insertPanel(slide, json.data.panel, panelId, idx);
            })
            .catch(function () {
                if (pendingPanels.get(panelId) === key) {
                    pendingPanels.delete(panelId);
                }
                window.alert('面板加载失败');
            });
    }

    // 面板已在右栏里、且态与目标一致就直接切，否则取回来再切
    // idx 省略 = 不关心态（组面板 / 条目展示面板）；传 null = 表单面板的新增态
    function showOrLoadPanel(slide, panelId, idx) {
        if (!panelId) {
            return;
        }
        const panel = findPanel(slide, panelId);
        if (panel && (undefined === idx || panelIdx(panel) === idx)) {
            showPanel(slide, panelId);

            return;
        }
        loadPanel(slide, panelId, (undefined === idx) ? null : idx);
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
            showOrLoadPanel(slide, target.getAttribute('data-panel'));
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
            showOrLoadPanel(slide, activePanelId);
        }
        // 新表单要重新交给 Validate（旧实例随旧元素一起丢了）
        initForms(slide, slide);
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

    // 从事件目标向上找到所属的 slide 与表单
    function formContext(target) {
        const slide = closestOf(target, '.zber-one-slide', root);
        if (!slide) {
            return null;
        }
        const form = closestOf(target, '[data-zber-form]', slide);
        if (!form) {
            return null;
        }

        return { slide: slide, form: form };
    }

    // 显式给表单构建验证：校验全部通过时才由回调发起提交，不通过时库自己拦住提交
    function bindValidator(form, slide) {
        if ('1' === form.dataset.zberValidate) {
            return;
        }
        // 验证库未就绪（CDN 未加载等）时不做校验，提交照走
        if ('function' !== typeof window.Validate) {
            return;
        }
        new window.Validate(form, function () {
            onFormSubmit(form, slide);
        });
        form.dataset.zberValidate = '1';
    }

    // 为范围内的表单构建验证；整栏替换出来的新表单同样要构建
    function initForms(scope, slide) {
        const forms = scope.querySelectorAll('[data-zber-form]');
        for (let i = 0; i < forms.length; i++) {
            bindValidator(forms[i], slide || closestOf(forms[i], '.zber-one-slide', root));
        }
    }

    // 点击删除：先确认，再带 token 请求
    function onDelete(link, slide) {
        if (!window.confirm('确认删除？')) {
            return;
        }
        requestWrite(slide, link.getAttribute('href'), null);
    }

    // 点击「添加 / 编辑」：切到那块新增与编辑共用的表单面板
    // 「添加」不带 data-zber-idx（要新增态），「编辑」带上要改的那条的序号（要编辑态）
    function onFormOpen(el, slide) {
        const panelId = el.getAttribute('data-panel');
        if (!panelId) {
            return;
        }
        const idx = parseInt(el.getAttribute('data-zber-idx'), 10);
        showOrLoadPanel(slide, panelId, Number.isInteger(idx) ? idx : null);
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
            onFormOpen(editLink, slide);
            return;
        }
        const addLink = closestOf(e.target, '[data-zber-add]', slide);
        if (addLink) {
            e.preventDefault();
            onFormOpen(addLink, slide);
            return;
        }
        const cancelBtn = closestOf(e.target, '[data-zber-cancel]', slide);
        if (cancelBtn) {
            e.preventDefault();
            showOrLoadPanel(slide, cancelBtn.getAttribute('data-panel'));
            return;
        }

        // 激活的左右栏内 → 切换面板
        onSideClick(e.target, slide);
    });

    // 表单提交无刷新；已交给 Validate 的表单由它的成功回调负责提交
    root.addEventListener('submit', function (e) {
        const ctx = formContext(e.target);
        if (!ctx) {
            return;
        }
        if ('1' === ctx.form.dataset.zberValidate) {
            return;
        }
        e.preventDefault();
        onFormSubmit(ctx.form, ctx.slide);
    });

    // 默认显示第一个来源（我）
    activate(0);

    // 验证库是延迟执行的 module，构建验证要等它到位
    if ('loading' === document.readyState) {
        document.addEventListener('DOMContentLoaded', function () {
            initForms(root);
        });
    } else {
        initForms(root);
    }
})();
