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
        // 激活的左右栏内 → 切换面板
        onSideClick(e.target, slide);
    });

    // 默认显示第一个来源（我）
    activate(0);
})();
