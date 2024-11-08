// extends prototype
(function (global) {
    global.$ = document.querySelector.bind(document);
    global.$$ = document.querySelectorAll.bind(document);
    global.Timer = function (interval, callback) {
        this.timerId = null;
        this.interval = interval;
        this.callback = callback;
    };
    global.Timer.throttle = function (cb, interval) {
        var lastTime = Date.now() - interval;
        return function () {
            if ((lastTime + interval) < Date.now()) {
                lastTime = Date.now();
                cb.apply(this, arguments);
            }
        };
    };
    global.Timer.debounce = function (cb, interval) {
        var timer;
        return function () {
            clearTimeout(timer);
            timer = setTimeout(() => cb.apply(this, arguments), interval);
        };
    };
    global.Timer.prototype.start = function () {
        clearTimeout(this.timerId);
        this.timerId = setTimeout(this.callback, this.interval);
    };
    global.Timer.prototype.stop = function () {
        clearTimeout(this.timerId);
    };
    global.requestIdleCallback = global.requestIdleCallback || function (callback) {
        setTimeout(callback);
    };
    Array.prototype.findLastIndex = function (predicate, thisArg) {
        for (let i = this.length - 1; i >= 0; i--) {
            if (predicate.call(thisArg, this[i], i, this)) {
                return i;
            }
        }
        return -1;
    };
    Element.prototype.$ = Element.prototype.querySelector;
    Element.prototype.$$ = Element.prototype.querySelectorAll;
    Element.prototype.appendChildren = function (nodes) {
        const children = document.createElements(nodes);
        children.forEach(child => this.appendChild(child));
        return children;
    };
    Document.prototype.createElements = function (nodes) {
        const elements = [];
        for (const [tagname, attributes] of Object.entries(nodes)) {
            const element = document.createElement(tagname);
            for (const [name, attr] of Object.entries(attributes)) {
                if (name === 'class') {
                    element.className = (attr instanceof Array ? attr : [attr]).join(' ');
                }
                else if (name === 'dataset') {
                    for (const [name, value] of Object.entries(attr)) {
                        element.dataset[name] = value;
                    }
                }
                else if (name === 'children') {
                    const append = function (e) {
                        if (e instanceof Array) {
                            e.forEach(e => append(e));
                        }
                        else if (e instanceof Node || typeof (e) === 'string') {
                            element.append(e);
                        }
                        else if (e instanceof Object) {
                            element.appendChildren(e);
                        }
                    };
                    append(attr);
                }
                else {
                    element.setAttribute(name, attr);
                }
            }
            elements.push(element);
        }
        return elements;
    };
    SVGElement.prototype.matches = function (selector) {
        const matches = this.ownerDocument.querySelectorAll(selector);
        return Array.prototype.find.call(matches, e => e === this) !== undefined;
    };
    EventTarget.prototype.on = function (event, selector, handler, data) {
        if (typeof (selector) === 'function') {
            data = handler;
            handler = selector;
            selector = null;
        }
        if (typeof (data) === 'boolean') {
            data = {
                capture: data,
            };
        }
        if (data == null) {
            data = {};
        }

        if (event === 'intersect') {
            const opt = Object.assign({
                root: null,
                rootMargin: '0px',
                threshold: 0,
            }, data);
            const observer = new IntersectionObserver(function (entries, observer) {
                entries.forEach(e => handler(Object.assign(e, {observer: observer})));
            }, opt);
            const targets = selector == null ? [this] : this.$$(selector);
            targets.forEach(node => observer.observe(node));
            return observer;
        }
        if (event === 'mutate') {
            const opt = Object.assign({
                attributes: false,
                attributeOldValue: false,
                characterData: false,
                characterDataOldValue: false,
                childList: false,
                subtree: false,
            }, data);
            const observer = new MutationObserver(function (entries, observer) {
                entries.forEach(e => handler(Object.assign(e, {observer: observer})));
            });
            const targets = selector == null ? [this] : this.$$(selector);
            targets.forEach(node => observer.observe(node, opt));
            return observer;
        }

        for (const evt of event.split(' ')) {
            this.addEventListener(evt, function (e) {
                for (var target = e.target; target && target !== document; target = target.parentNode) {
                    if (selector == null || target.matches(selector)) {
                        handler.call(target, e);
                        break;
                    }
                }
            }, data);
        }
    };
    HTMLInputElement.prototype.getValue = HTMLSelectElement.prototype.getValue = function () {
        if (this.tagName === 'SELECT') {
            return this.$('option:checked').value;
        }
        else if (this.type === 'checkbox') {
            return '' + this.checked;
        }
        else {
            return this.value;
        }
    };
    HTMLInputElement.prototype.setValue = HTMLSelectElement.prototype.setValue = function (value) {
        if (this.tagName === 'SELECT') {
            this.$(`option[value="${value}"]`).selected = true;
        }
        else if (this.type === 'checkbox') {
            this.checked = value === 'true';
        }
        else {
            this.value = value;
        }
    };
    HTMLImageElement.prototype.toDataURL = function (mimetype) {
        const canvas = document.createElement('canvas');
        canvas.width = this.width;
        canvas.height = this.height;
        canvas.getContext('2d').drawImage(this, 0, 0);
        return canvas.toDataURL(mimetype);
    };
    Blob.prototype.toDataURL = function () {
        const reader = new FileReader();
        const dataurl = new Promise(function (resolve, reject) {
            reader.onload = e => resolve(reader.result);
            reader.onerror = e => reject(reader.error);
        });
        reader.readAsDataURL(this);
        return dataurl;
    };
})(window);

document.addEventListener('DOMContentLoaded', function () {
    const html = $('html');
    const body = $('body');
    const scroller = $('.wy-side-scroll');
    const outline = $('.markdown-toc');
    const article = $('.markdown-body>article');
    const sentinel = $('.sentinel');
    const controlPanel = $('.control-panel');
    const relationship = $('#relationship');

    /// トグルイベント
    document.on('click', '[data-toggle-class]', function (e) {
        const target = e.target;
        const targets = target.dataset.toggleTarget ? $$(e.target.dataset.toggleTarget) : [target];
        targets.forEach(e => e.classList.toggle(target.dataset.toggleClass));
    });

    /// コンパネ
    const SAVENAME = 'ht-setting';
    const alldata = html.matches('[data-exported]') ? {} : JSON.parse(localStorage.getItem(SAVENAME) ?? '{}');
    const directory = location.pathname.split('/').slice(0, -1).join('/');
    controlPanel.on('change', function (e) {
        if (!e.target.validity.valid) {
            return;
        }
        if (!e.target.matches('.savedata')) {
            return;
        }
        controlPanel.sync();
        controlPanel.save();
    });
    controlPanel.sync = function () {
        this.$$('.savedata').forEach(function (input) {
            html.dataset[input.id] = input.getValue();
            if (input.id === 'tocWidth') {
                $('.wy-nav-side').style.width = ''; // reset dragging
                document.documentElement.style.setProperty('--side-width', input.getValue() + 'px');
            }
            if (input.id === 'fontFamily') {
                document.documentElement.style.setProperty('--font-family', input.getValue());
            }
        });
    };
    controlPanel.save = function () {
        const savedata = {};
        this.$$('.savedata').forEach(function (input) {
            savedata[input.id] = input.getValue();
        });
        alldata[directory] = savedata;
        localStorage.setItem(SAVENAME, JSON.stringify(alldata));
    };
    controlPanel.load = function () {
        const dir = Object.keys(alldata).sort((a, b) => b.length - a.length).find(dir => directory.indexOf(dir) === 0);
        const savedata = alldata[dir] ?? Object.assign({}, html.dataset);
        this.$$('.savedata').forEach(function (input) {
            input.setValue(savedata[input.id] ?? input.dataset.defaultValue);
        });
        controlPanel.sync();
    };
    controlPanel.load();

    /// DOM ビルド
    /// セクション由来のアウトラインの構築
    const levels = (new Array(6)).fill(0);
    const idmap = {};
    article.$$('.section').forEach(function (section) {
        const sectionId = `toc-${section.id}`;
        const sectionTitle = section.$('.section-header').textContent;
        const sectionLevel = +section.dataset.sectionLevel;

        levels[sectionLevel - 1]++;
        levels.fill(0, sectionLevel);

        const leading = levels.findIndex(v => v !== 0);
        const trailing = levels.findLastIndex(v => v !== 0);
        const currentLevels = levels.slice(leading, trailing + 1);
        const blockId = currentLevels.join('.');
        const parentId = currentLevels.slice(0, -1).join('.');
        section.querySelector('.section-header').dataset.blockId = blockId;

        idmap[blockId] = sectionId;
        const parent = document.getElementById(idmap[parentId]);
        if (parent) {
            parent.dataset.childCount = (+parent.dataset.childCount + 1) + '';
        }

        outline.appendChildren({
            a: {
                id: sectionId,
                href: `#${section.id}`,
                title: sectionTitle,
                class: ['toc-h', `toc-h${sectionLevel}`],
                dataset: {
                    sectionCount: '0',
                    sectionLevel: sectionLevel,
                    blockId: blockId,
                    parentBlockId: parentId,
                    childCount: '0',
                    state: '',
                },
                children: [
                    sectionTitle,
                    {
                        a: {
                            class: 'toggler icon',
                        },
                    },
                ],
            },
        });
    });

    /// アウトラインのハイライト監視
    article.on('intersect', '.section', function (e) {
        let toch = document.getElementById(`toc-${e.target.id}`);
        if (html.dataset.tocActive === 'false') {
            while (toch.clientHeight <= 1) {
                toch = toch.previousElementSibling;
            }
        }
        toch.dataset.sectionCount = (Math.max(+toch.dataset.sectionCount + (e.isIntersecting ? +1 : -1), 0)) + '';
    }, {
        rootMargin: '0px 0px 0px 0px',
    });

    // アウトラインの自動開閉
    const outlineTimer = new Timer(10, function () {
        const tochs = outline.$$('.toc-h');
        const actives = Array.prototype.filter.call(tochs, e => e.dataset.sectionCount > 0);
        const firstIndex = Array.prototype.indexOf.call(tochs, actives[0]);
        const lastIndex = Array.prototype.indexOf.call(tochs, actives[actives.length - 1]);
        const min = firstIndex === -1 ? 0 : firstIndex - 3;
        const max = lastIndex === -1 ? tochs.length - 1 : lastIndex + 3;

        tochs.forEach(function (toch, i) {
            if ((min <= i && i <= max)) {
                toch.classList.add('visible');
                while (toch) {
                    toch = outline.$(`[data-block-id="${toch.dataset.parentBlockId}"]`);
                    toch?.classList?.add('visible');
                }
            }
            else {
                toch.classList.remove('visible');
            }
        });
    });
    outline.on('mutate', '[data-section-count]', function (e) {
        if (e.attributeName === 'data-section-count' && html.dataset.tocActive === 'true') {
            if (e.oldValue !== e.target.dataset.sectionCount) {
                outlineTimer.start();
            }
        }
    }, {
        attributes: true,
        attributeOldValue: true,
    });

    /// アウトラインの開閉ボタン
    outline.on('mouseenter', 'a.toc-h', function (e) {
        const toch = e.target;
        if (toch.dataset.sectionLevel >= html.dataset.tocLevel) {
            if (+toch.dataset.childCount) {
                const tochs = outline.$$(`[data-parent-block-id="${toch.dataset.blockId}"]`);
                const visibles = Array.prototype.filter.call(tochs, e => e.matches('.visible,.forced-visible'));
                if (tochs.length === visibles.length) {
                    toch.dataset.state = 'close';
                }
                else {
                    toch.dataset.state = 'open';
                }
            }
        }
    }, true);
    outline.on('mouseleave', 'a.toc-h', function (e) {
        e.target.dataset.state = '';
    }, true);
    outline.on('click', 'a.toggler', function (e) {
        const toch = e.target.parentElement;
        if (toch.dataset.state === 'open') {
            toch.dataset.state = 'close';
            outline.$$(`[data-parent-block-id="${toch.dataset.blockId}"]`).forEach(e => e.classList.add('forced-visible', 'visible'));
        }
        else {
            toch.dataset.state = 'open';
            outline.$$(`[data-parent-block-id^="${toch.dataset.blockId}"]`).forEach(e => e.classList.remove('forced-visible', 'visible'));
        }
        e.preventDefault();
        return false;
    });

    /// アウトラインのクリックイベント
    let intoViewScrolling = false;
    outline.on('click', 'a.toc-h', function (e) {
        e.preventDefault();
        const section = document.getElementById(e.target.getAttribute('href').substring(1));
        intoViewScrolling = true;
        section.scrollIntoView({
            behavior: 'smooth',
            block: 'start',
        });
        section.on('intersect', function (e) {
            if (e.isIntersecting) {
                e.observer.unobserve(e.target);
                requestIdleCallback(function () {
                    intoViewScrolling = false;
                });
            }
        }, {
            rootMargin: '0px 0px -99.99% 0px',
        });
    });

    // アウトラインのスクロールの自動追従
    const followMenuTimer = new Timer(32, function () {
        if ($('.wy-nav-side').clientWidth > 0) {
            const visibles = $$('.toc-h:not([data-section-count="0"])');
            visibles[Math.floor(visibles.length / 2)].scrollIntoView({
                behavior: 'smooth',
                block: 'center',
            });
        }
    });
    document.on('scroll', function (e) {
        if (!intoViewScrolling && html.dataset.tocFollow === 'true') {
            followMenuTimer.stop();
            followMenuTimer.start();
        }
    });

    /// スクロールバーを自動的に隠す
    const scrollHideTimer = new Timer(3000, function () {
        scroller.classList.remove('scrolling');
    });
    scroller.on('scroll', function () {
        scrollHideTimer.stop();
        scroller.classList.add('scrolling');
        scrollHideTimer.start();
    });

    // アウトラインのドラッグリサイズ
    $('.wy-nav-side').on('mutate', Timer.debounce(function (e) {
        $('#tocWidth').value = e.target.getBoundingClientRect().width;
        $('#tocWidth').dispatchEvent(new Event('change', {bubbles: true}));
    }, 50), {
        attributes: true,
        attributeFilter: ["style"],
    });

    /// ページ内ジャンプ
    document.on('click', '.reference', function (e) {
        const href = e.target.getAttribute('href');
        $(href).scrollIntoView({
            behavior: 'smooth',
            block: 'start',
        });
        history.pushState(null, null, href);
        e.preventDefault();
    });
    /// フルスクリーン
    document.on('click', '.toggle-full', function () {
        this.classList.toggle('on');
        this.closest('.section').classList.toggle('fullscreen');
    });

    /// リレーション図
    if (window.Viz === undefined) {
        window.Viz = function () {};
    }
    const viz = new window.Viz();
    const relationship_dot = $('.relationship-dot');
    const relationship_svg = $('.relationship-svg');
    const fade = function (node, eitherIn_Out, duration, callback) {
        const target = +eitherIn_Out;
        const start = performance.now();

        node.style.opacity = 1 - target;
        requestAnimationFrame(function tick(timestamp) {
            const easing = (timestamp - start) / duration;
            if (easing < 1) {
                node.style.opacity = Math.abs(1 - target - Math.min(easing, 1));
                requestAnimationFrame(tick);
            }
            else {
                node.style.opacity = target;
                callback(node);
            }
        });
    };
    const renderDot = function () {
        if (!relationship_dot) {
            return;
        }
        const filter_names = Array.from(relationship.$$('[data-table-name]'))
            .filter(e => !e.$('input').checked)
            .map(e => e.dataset.tableName)
        ;
        let dot = relationship_dot.textContent;
        if (filter_names && filter_names.length) {
            const filter_name = filter_names.map(s => ` ${s} `).join('|');
            const subgraphs = `# subgraph-begin:[^\n]*?(${filter_name}).*?# subgraph-end:[^\n]*?(${filter_name})`;
            const relations = `# edge-begin:[^\n]*?(${filter_name}).*?# edge-end:[^\n]*?(${filter_name})`;
            dot = dot.replaceAll(new RegExp(`(${subgraphs})|(${relations})`, 'sg'), '# filtered');
        }
        viz.renderSVGElement(dot)
            .then(function (svg) {
                const old = relationship_svg.$('svg:last-child');
                if (old) {
                    const duration = 444;
                    svg.setAttribute('viewBox', old.getAttribute('viewBox'));

                    svg.style.position = 'relative';
                    fade(svg, true, duration, function () {});

                    old.style.position = 'absolute';
                    fade(old, false, duration, node => node.remove());
                }
                relationship_svg.appendChild(svg);
            })
            .catch(error => console.error(error))
        ;
    };
    renderDot();

    relationship.on('mouseover', '[data-table-name]', function (e) {
        $('.relationship').$$(`.table-${e.target.dataset.tableName}`).forEach(function (node) {
            node.classList.add('active');
        });
    });
    relationship.on('mouseout', '[data-table-name]', function (e) {
        $('.relationship').$$(`.table-${e.target.dataset.tableName}`).forEach(function (node) {
            node.classList.remove('active');
        });
    });

    relationship.on('change', '.all-checkbox', function (e) {
        $('.relationship').$$('[data-table-name]>input').forEach(function (node) {
            node.checked = e.target.checked;
        });
        renderDot();
    });
    relationship.on('change', '[data-table-name]', function (e) {
        const checkboxes = Array.from($('.relationship').$$('[data-table-name]>input'));
        const all_checked = checkboxes.every(e => e.checked);
        const any_checked = checkboxes.some(e => e.checked);
        const all_checkbox = relationship.$('.all-checkbox');
        if (all_checked) {
            all_checkbox.checked = true;
        }
        if (!any_checked) {
            all_checkbox.checked = false;
        }
        all_checkbox.indeterminate = !all_checked && any_checked;
        renderDot();
    });
    relationship.on('mousedown', 'svg', function (e) {
        const [x, y, ] = this.getAttribute('viewBox').split(' ').map(v => parseFloat(v));
        this.dragging = {startX: x, mouseX: e.offsetX, startY: y, mouseY: e.offsetY};
        e.preventDefault();
    });
    relationship.on('mousemove', 'svg', function (e) {
        if (this.dragging) {
            const [, , w, h] = this.getAttribute('viewBox').split(' ').map(v => parseFloat(v));
            const scale = w / this.clientWidth;
            const newX = this.dragging.startX + (this.dragging.mouseX - e.offsetX) * scale;
            const newY = this.dragging.startY + (this.dragging.mouseY - e.offsetY) * scale;
            this.setAttribute('viewBox', [newX, newY, w, h].join(' '));
        }
        e.preventDefault();
    });
    relationship.on('mouseup', 'svg', function (e) {
        this.dragging = undefined;
        e.preventDefault();
    });
    relationship.on('wheel', 'svg', function (e) {
        const ratio = 1.2;
        const scale = (1 / e.deltaY < 0 ? 1 / ratio : ratio) - 1;
        const [x, y, w, h] = this.getAttribute('viewBox').split(' ').map(v => parseFloat(v));

        const targetX = w * scale * e.offsetX / this.clientWidth;
        const targetY = h * scale * e.offsetY / this.clientHeight;
        const targetW = w * scale;
        const targetH = h * scale;

        this.setAttribute('viewBox', [x - targetX, y - targetY, w + targetW, h + targetH].join(' '));

        e.preventDefault();
    });
    relationship.on('dblclick', 'g.cluster', function (e) {
        const g = this;
        relationship.$$('[data-table-name]').forEach(function (elm) {
            if (g.classList.contains(`table-${elm.dataset.tableName}`)) {
                elm.$('input').checked = false;
            }
        });
        renderDot();
    });
    relationship.on('click', 'text', function (e) {
        relationship.$('#toggle-active').classList.add('on');
        relationship.$('.relationship').classList.remove('noactive-edge');
        const columns = Array.prototype.filter.call(e.target.closest('g').classList, e => e.startsWith('column-'));
        if (columns.length) {
            const nodes = $('.relationship').$$(columns.map(e => `.${e}`).join(','));
            const allActive = Array.prototype.every.call(nodes, node => node.classList.contains('active'));
            nodes.forEach(function (node) {
                node.classList.toggle('active', !allActive);
            });
        }
    });
    relationship.on('auxclick', 'text', function (e) {
        if (e.which === 2) {
            const cluster = e.target.closest('.cluster');
            if (cluster) {
                $('#' + cluster.id.replace('relationship:', '')).classList.add('fullscreen');
            }
        }
    });
    relationship.on('click', '#toggle-edge', function () {
        this.classList.toggle('on');
        relationship.$('.relationship').classList.toggle('invisible-edge');
    });
    relationship.on('click', '#toggle-active', function () {
        this.classList.toggle('on');
        relationship.$('.relationship').classList.toggle('noactive-edge');
    });
    relationship.on('click', '#clear-all', function () {
        $('.relationship').$$('.active').forEach(function (node) {
            node.classList.remove('active');
        });
    });

    requestIdleCallback(function () {
        /// stop initial animation
        document.documentElement.style.setProperty('--initial-animation-ms', '500ms');

        /// 記事末尾の空白を確保（ジャンプしたときに「え？どこ？」となるのを回避する）
        const lastSection = $('.section:last-child');
        if (lastSection) {
            const height = sentinel.offsetTop - lastSection.offsetTop + parseInt(getComputedStyle(lastSection).marginTop);
            sentinel.style.height = `calc(100vh - ${height}px)`;
        }
    });
});
