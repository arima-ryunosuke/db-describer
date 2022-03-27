/* override theme.css */
:root {
    --initial-animation-ms: 0ms;
    --side-width: 300px;
    --font-family: serif;
}

h1, h2, h3, h4, h5, h6 {
    border-bottom: 1px solid #E1E4E5;
}

h2, h3, h4, h5, h6 {
    margin-top: 20px;
}

.caption {
    display: block;
    font-weight: bold;
    margin-bottom: 4px;
}

.wy-nav-content {
    max-width: 100%;
    height: unset;
    padding: 0 1.4em;
}

.wy-tray-container li {
    width: var(--side-width);
}

.wy-menu-vertical {
    width: var(--side-width);
}

.wy-side-nav-search {
    width: var(--side-width);
}

.wy-nav-side {
    width: var(--side-width);
}

.wy-nav-content-wrap {
    margin-left: var(--side-width);
}

.rst-versions {
    width: var(--side-width);
}

.wy-side-scroll {
    transition: width var(--initial-animation-ms) 0s ease;
    width: calc(var(--side-width) + 20px);
    overscroll-behavior: contain;
}

.wy-side-scroll.scrolling {
    transition-duration: 0s;
    width: var(--side-width);
}

.wy-side-scroll::-webkit-scrollbar {
    width: 12px;
}

.wy-side-scroll::-webkit-scrollbar-track {
    background: #e1e1e1;
}

.wy-side-scroll::-webkit-scrollbar-thumb {
    border-radius: 10px;
    background: #a9a9a9;
}

.rst-content code {
    white-space: inherit;
    font-size: 90%;
}

.rst-content pre div.code {
    padding: 8px 12px;
    line-height: 16px;
    max-width: 100%;
    border: solid 1px #e1e4e5;
    font-size: 90%;
    font-family: SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", Courier, monospace;
    overflow-x: auto;
}

.rst-content .section {
    clear: both;
}

.rst-content .section ol li > *,
.rst-content .section ul li > *,
.rst-content .section dl dd > * {
    margin-top: 0;
}

.rst-content .section ol li > ol,
.rst-content .section ol li > ul,
.rst-content .section ol li > dl,
.rst-content .section ul li > ol,
.rst-content .section ul li > ul,
.rst-content .section ul li > dl,
.rst-content .section dl dd > ol,
.rst-content .section dl dd > ul,
.rst-content .section dl dd > dl {
    margin-bottom: 0;
}

.rst-content table.docutils {
    width: 100%
}

.rst-content table.docutils:not(.field-list) tr th {
    background: #e1e4e5;
}

.rst-content dl dt {
    background: rgba(225, 228, 229, 1);
    margin-bottom: 4px;
    padding: 0 8px;
}

.rst-content dl dd dl dt {
    background: rgba(225, 228, 229, 0.7);
}

.rst-content dl dd dl dd dl dt {
    background: rgba(225, 228, 229, 0.4);
}

.rst-content dl dd p,
.rst-content dl dd pre,
.rst-content dl dd ol,
.rst-content dl dd ul {
    margin-bottom: 0px !important;
}

.rst-content dl.field-list {
    display: table;
    border-collapse: separate;
    border-spacing: 4px 3px;
}

.rst-content dl.field-list > .dtdd-container {
    display: table-row;
}

.rst-content dl.field-list > .dtdd-container > dt,
.rst-content dl.field-list > .dtdd-container > dd {
    display: table-cell;
}

.rst-content dl.field-list > .dtdd-container > dt {
    white-space: nowrap;
}

.rst-content dl.field-list > .dtdd-container dd pre {
    white-space: pre-wrap;
}

.rst-content details {
    margin-bottom: 24px;
}

.rst-content details summary {
    cursor: pointer;
}

.rst-content blockquote {
    margin: 0 0 24px;
    border-left: 5px solid #e2e2e2;
    color: #777;
    padding: 0 0 0 1em;
}

.rst-content blockquote blockquote {
    margin-bottom: 0;
}

.rst-content blockquote p {
    margin-bottom: 12px;
}

.rst-content .admonition-title:empty {
    display: none;
}

.rst-content .sidebar.right {
    padding: 0;
    margin: 0;
    background: transparent;
    border: none;
    width: auto;
}

.rst-content .sidebar.right p {
    margin-bottom: 0;
}

.rst-content .sidebar-title:empty {
    display: none;
}

.rst-footer-buttons {
    display: flex;
}

.rst-footer-buttons:before, .rst-footer-buttons:after {
    display: initial;
    content: unset;
}

.rst-footer-buttons div:nth-child(1) {
    width: calc(100% / 3);
    text-align: left;
}

.rst-footer-buttons div:nth-child(2) {
    width: calc(100% / 3);
    text-align: center;

}

.rst-footer-buttons div:nth-child(3) {
    width: calc(100% / 3);
    text-align: right;
}

.rst-versions .rst-other-versions dd {
    display: block;
}

@media screen and (max-width: 768px) {
    .wy-nav-side {
        width: 0;
    }

    .wy-nav-content-wrap {
        margin-left: 0;
    }

    .rst-versions {
        width: 85%;
    }

    .sentinel {
        display: none;
    }
}

/* dbobject */

table {
    position: relative;
    width: 100%;
    border-spacing: 0 2px;
    border-collapse: separate;
}

tbody:empty:before {
    content: 'なし';
    position: absolute;
    left: 0px;
    right: 0px;
    text-align: center;
    border-bottom: 1px dotted #ddd;
}

tbody:empty:after {
    content: 'dummy';
    display: block;
    visibility: hidden;
}

caption {
    text-align: left;
}

th, td {
    padding: 2px 4px;
}

th {
    background: #d8d8d8;
    text-align: left;
}

td {
    border-bottom: 1px dotted #ddd;
}

div.cards {
    display: flex;
    flex-wrap: wrap;
}

div.cards .card {
    width: 25%;
    min-width: 300px;
}

dl.side-by-side {
    display: grid;
    grid-template-columns: max-content auto;
    margin: 0;
}

.table_info {
    margin-top: 16px;
}

.table_info table {
    table-layout: fixed;
    white-space: break-spaces;
}

.table_wrapper {
    margin-top: 16px;
    overflow-x: auto;
}

.table_wrapper table {
    white-space: nowrap;
}

.sheet {
    padding: 8px;
    background: white;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
    margin: 32px 0;
    overflow: auto;
}

.sheet .section-header {
    margin: 0;
    background: #3f3f3f;
    color: #eeeeee;
    padding: 5px;
    border: none;
}

/* table of contents */

.markdown-toc {
    padding-bottom: 8px;
}

:not([data-font-family=""]) * {
    font-family: var(--font-family);
}

[data-toc-number="true"] [data-block-id]:before {
    content: attr(data-block-id) ' ';
    color: #999999;
}

[data-toc-level="1"] .toc-h2,
[data-toc-level="1"] .toc-h3,
[data-toc-level="1"] .toc-h4,
[data-toc-level="1"] .toc-h5,
[data-toc-level="1"] .toc-h6,
[data-toc-level="2"] .toc-h3,
[data-toc-level="2"] .toc-h4,
[data-toc-level="2"] .toc-h5,
[data-toc-level="2"] .toc-h6,
[data-toc-level="3"] .toc-h4,
[data-toc-level="3"] .toc-h5,
[data-toc-level="3"] .toc-h6,
[data-toc-level="4"] .toc-h5,
[data-toc-level="4"] .toc-h6,
[data-toc-level="5"] .toc-h6 {
    visibility: hidden;
    max-height: 0;
    padding-top: 0;
    padding-bottom: 0;
}

.toc-h.visible,
.toc-h.forced-visible {
    visibility: visible;
    max-height: 36px;
    padding-top: .4045em;
    padding-bottom: .4045em;
}

.toc-h {
    transition-property: all;
    transition-delay: 0s;
    transition-duration: var(--initial-animation-ms);
    transition-timing-function: ease;
    text-overflow: ellipsis;
    white-space: nowrap;
    overflow: hidden;
}

.toc-h:not([data-section-count="0"]) {
    font-weight: bold;
    background-color: #4e4a4a;
}

<?php foreach(range(1, 6) as $n): ?>

a.toc-h<?= $n ?> {
    font-size: <?= 100 - (($n - 1) * 4) ?>%;
    padding-left: <?= ($n - 1) + 1.35 ?>rem;
}

a.toc-h<?= $n ?> a.toggler {
    left: <?= ($n - 2) + 1.1 ?>rem;
}

<?php endforeach ?>

a.toggler {
    position: absolute;
    top: 0;
    left: 0;
    font-size: inherit;
    display: inline-block;
    padding: .4045em 0;
    text-align: center;
}

a.toggler:before {
    line-height: inherit;
    opacity: 0.7;
}

a.toggler:hover:before {
    opacity: 1;
}

[data-state=""] a.toggler:before {
    content: "";
}

[data-state="open"] a.toggler:before {
    content: "";
}

[data-state="close"] a.toggler:before {
    content: "";
}

/* article */

small.metadata {
    font-family: sans-serif;
    display: block;
    text-align: right;
}

.section-level-h1 small.metadata {
    font-size: 100%;
    margin-top: -3.3rem;
}

.section-level-h2 small.metadata {
    font-size: 90%;
    margin-top: -2.7rem;
}

<?php foreach(range(1, 6) as $n): ?>
<?php foreach(range(1, 6) as $m): ?>

[data-section-indent="<?= $n ?>"] section[data-section-level="<?= $m ?>"] {
    padding-left: <?= ($m) * $n ?>rem;
}

<?php endforeach ?>
<?php endforeach ?>

html:not([data-section-indent="0"]) section[data-section-level] .section-header {
    margin-left: -1rem;
}

[data-break-line="false"] br.break-line {
    display: none;
}

[data-link-url="false"] a.link-url {
    color: #404040;
    cursor: text;
    pointer-events: none;
}

[data-toggle-class] * {
    pointer-events: none;
}

.admonition-title:empty {
    display: none;
}

.admonition-body {
    white-space: pre-line;
}

.internal-file {
    /*
    transform: scale(0.5);
    transform-origin: top left;
    height: 50%;
    width: 200%;
    */
    zoom: 0.5;
    background: white;
    padding: 2rem;
}

pre[data-label]:not([data-label=""]):before {
    content: attr(data-label);
    background: gray;
    color: #fff;
    padding: 2px;
    position: absolute;
    margin-top: 1px;
    margin-left: 1px;
    font-size: 85%;
}

pre[data-label]:not([data-label=""]) div.code {
    padding-top: 32px
}

.badge {
    margin-right: 5px;
    padding: 3px 6px 3px 0;
    font-size: 80%;
    white-space: nowrap;
    border-radius: 4px;
    color: white;
    background-color: #666666;
}

.badge[data-badge-title=""] {
    padding-left: 6px;
}

.badge:not([data-badge-title=""]):before {
    content: attr(data-badge-title);
    padding: 3px 6px;
    margin-right: 6px;
    border-top-left-radius: 4px;
    border-bottom-left-radius: 4px;
}

.badge[data-badge-title=""].info {
    background: #6ab0de;
}

.badge[data-badge-title=""].success {
    background: #1abc9c;
}

.badge[data-badge-title=""].notice {
    background: #f0b37e;
}

.badge[data-badge-title=""].alert {
    background: #f29f97;
}

.badge:not([data-badge-title=""]).info:before {
    background: #6ab0de;
}

.badge:not([data-badge-title=""]).success:before {
    background: #1abc9c;
}

.badge:not([data-badge-title=""]).notice:before {
    background: #f0b37e;
}

.badge:not([data-badge-title=""]).alert:before {
    background: #f29f97;
}

@page {
    margin: 1.5cm 1.0cm;
    size: A4;
}

@media print {
    * {
        overflow: visible !important;
    }

    body {
        color: #000000;
    }

    .wy-nav-content-wrap {
        margin-left: 0;
        background: white;
    }

    .wy-nav-content {
        padding: 0;
    }

    .wy-grid-for-nav {
        position: static;
    }

    h1.main-header {
        background: transparent;
        color: #333333;
        font-size: 320%;
        text-align: center;
    }

    h2.sub-header {
        background: transparent;
        color: #333333;
        font-size: 220%;
        text-align: center;
    }

    h1 {
        background: #3f3f3f;
        color: #eeeeee;
        padding: 8px;
        border: none;
    }

    h2 {
        background: #3f3f3f;
        color: #eeeeee;
        padding: 5px;
        border: none;
    }

    h3 {
        border-bottom: 1px dotted #666;
    }

    .section {
        page-break-inside: avoid;
    }

    .sheet {
        page-break-after: always;
    }

    .main-section {
        padding-top: 5cm;
    }

    .sub-section {
        page-break-before: avoid;
    }
    .relation-button {
        display: none;
    }

    header {
        display: none;
    }

    .rst-content p {
        line-height: 1.8;
    }

    .rst-content pre {
        white-space: pre-wrap;
    }

    .rst-content table.docutils td {
        background: transparent !important;
    }

    .sentinel {
        display: none;
    }
}

/* control panel */

input[type="search"] {
    -webkit-appearance: searchfield;
}

input[type="search"]::-webkit-search-cancel-button {
    -webkit-appearance: searchfield-cancel-button;
}

.option-title {
    color: #fcfcfc;
    display: inline-block;
    width: 210px;
}

.option-input {
    display: inline-block;
    height: 18px;
    vertical-align: text-bottom;
    padding: 0;
}

[type="checkbox"].option-input {
    width: 16px;
    cursor: pointer;
}

[type="number"].option-input {
    width: 60px;
    text-align: right;
}

[type="search"].option-input {
    width: 90px;
}

select.option-input {
    width: 90px;
}

/* ERD */

.fullscreen {
    position: fixed;
    left: 0;
    top: 0;
    right: 0;
    bottom: 0;
    width: 100%;
    height: 100%;
    z-index: 401;
    margin: 0;
}

.fullscreen .relationship {
    width: 100%;
    height: 100%;
}

.fullscreen .relationship svg {
    width: 100%;
    height: 100%;
}

.relationship svg {
    width: 100%;
    height: auto;
}

.relation-button {
    margin: 5px 0;
}

.relation-button button {
    padding: 0;
    width: 30px;
    height: 30px;
    line-height: 0;
    border: 3px outset #ddd;
    margin-right: 3px;
}

.relation-button button.on {
    border: 3px inset #ddd;
}

.relation-button button.oneshot:active {
    border: 3px inset #ddd;
}

.relationship {
    width: 100%;
    max-height: 90vh;
    overflow: auto;
    background: white;
}

.relationship svg {
    cursor: move;
}

.relationship.invisible-edge g.edge:not(.active) * {
    display: none;
}

.relationship g path {
    pointer-events: none;
}

.relationship g text {
    cursor: pointer;
}

.relationship:not(.noactive-edge) g.active path {
    stroke-width: 3px;
}

.relationship:not(.noactive-edge) g.active text {
    font-weight: bold;
}

/* utility */

a.disabled {
    pointer-events: none;
    color: gray;
}

.singlefile .hidden-single {
    display: none;
}

.downloaded .hidden-download {
    display: none;
}

.no {
    text-align: right;
    width: 50px;
}

.wrap {
    white-space: break-spaces;
}

.float-right {
    float: right;
}