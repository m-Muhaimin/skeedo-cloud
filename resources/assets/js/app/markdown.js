'use strict';

import { Remarkable } from 'remarkable';
import hljs from 'highlight.js'
import { linkify } from 'remarkable/linkify';
import katex from 'katex';

hljs.configure({ ignoreUnescapedHTML: true });
hljs.safeMode();


const rkatex = (md) => {
    const inlineStart = '\\(';
    const inlineEnd = '\\)';
    const blockStart = '\\[';
    const blockEnd = '\\]';

    /**
     * Render the contents as KaTeX
     */
    const renderKatex = (source, displayMode) => katex.renderToString(source, {
        displayMode: displayMode,
        throwOnError: false,
        output: 'mathml'
    });

    /**
     * Parse block KaTeX delimited by \[ and \]
     * Updated to handle \[ ... \] on the same line
     */
    const parseBlockKatex = (state, startLine, endLine) => {
        let haveEndMarker = false;
        let pos = state.bMarks[startLine] + state.tShift[startLine];
        let max = state.eMarks[startLine];

        if (pos + blockStart.length > max) { return false; }

        if (state.src.slice(pos, pos + blockStart.length) !== blockStart) { return false; }

        pos += blockStart.length;

        // Check if the block end delimiter \] is on the same line
        let endPos = state.src.indexOf(blockEnd, pos);
        if (endPos !== -1 && endPos < max) {
            // \[ and \] on the same line
            const content = state.src.slice(pos, endPos).trim();

            state.line = startLine + 1;
            state.tokens.push({
                type: 'katex',
                params: null,
                content: content,
                lines: [startLine, state.line],
                level: state.level,
                block: false
            });
            return true;
        }

        // If \] is not on the same line, search in the following lines
        let nextLine = startLine;
        for (; ;) {
            nextLine++;
            if (nextLine >= endLine) { break; }

            pos = state.bMarks[nextLine] + state.tShift[nextLine];
            max = state.eMarks[nextLine];

            if (state.src.slice(pos, pos + blockEnd.length) === blockEnd) {
                haveEndMarker = true;
                break;
            }

            if (nextLine - startLine > 100) { // Prevent infinite loops
                break;
            }
        }

        if (!haveEndMarker) { return false; }

        state.line = nextLine + 1;
        const content = state.getLines(startLine + 1, nextLine, state.tShift[startLine], false).trim();

        state.tokens.push({
            type: 'katex',
            params: null,
            content: content,
            lines: [startLine, state.line],
            level: state.level,
            block: false
        });
        return true;
    };

    /**
     * Parse inline KaTeX delimited by \( and \)
     */
    const parseInlineKatex = (state, silent) => {
        const start = state.pos;
        const max = state.posMax;

        if (state.src.slice(start, start + inlineStart.length) !== inlineStart) { return false; }

        let end = state.src.indexOf(inlineEnd, start + inlineStart.length);
        if (end === -1) { return false; }

        if (!silent) {
            const content = state.src.slice(start + inlineStart.length, end).trim();
            state.push({
                type: 'katex',
                content: content,
                block: false,
                level: state.level
            });
        }

        state.pos = end + inlineEnd.length;
        return true;
    };

    md.inline.ruler.before('escape', 'katex_inline', parseInlineKatex);
    md.block.ruler.before('blockquote', 'katex_block', parseBlockKatex, {
        alt: ['paragraph', 'reference', 'blockquote', 'list']
    });

    md.renderer.rules.katex = (tokens, idx) => renderKatex(tokens[idx].content, tokens[idx].block);
};

var md = new Remarkable({
    html: false,        // Enable HTML tags in source
    xhtmlOut: false,        // Use '/' to close single tags (<br />)
    breaks: true,        // Convert '\n' in paragraphs into <br>
    langPrefix: 'language-',  // CSS language prefix for fenced blocks

    // Enable some language-neutral replacement + quotes beautification
    typographer: false,

    // Double + single quotes replacement pairs, when typographer enabled,
    // and smartquotes on. Set doubles to '«»' for Russian, '„“' for German.
    quotes: '“”‘’',

    // Highlighter function. Should return escaped HTML,
    // or '' if the source string is not changed
    highlight: function (str, lang) {
        if (lang && hljs.getLanguage(lang)) {
            try {
                return hljs.highlight(str, { language: lang }).value;
            } catch (err) { }
        }

        try {
            return hljs.highlightAuto(str).value;
        } catch (err) { }

        return ''; // use external default escaping
    }
});

md.use(linkify);
md.use(rkatex);

export function markdownToHtml(text, animate = false) {
    // Preprocess the text to handle KaTeX blocks
    text = preprocessKaTeX(text);

    let html = md.render(text);

    // Parse HTML into a DOM tree
    let elem = document.createElement('div');
    elem.innerHTML = html;

    // Recursively wrap words in text nodes, skipping code/math/etc
    function wrapWords(node) {
        // Skip certain tags
        const skipTags = ['CODE', 'PRE', 'KBD', 'SCRIPT', 'STYLE', 'MATH'];
        if (node.nodeType === Node.ELEMENT_NODE && skipTags.includes(node.tagName)) {
            return;
        }
        // If it's a text node, wrap each word
        if (node.nodeType === Node.TEXT_NODE) {
            // Only wrap if not just whitespace
            if (node.textContent.trim().length > 0) {
                // Split by word, keeping whitespace
                const parts = node.textContent.split(/(\s+)/);
                const frag = document.createDocumentFragment();
                for (const part of parts) {
                    if (part.trim().length === 0) {
                        frag.appendChild(document.createTextNode(part));
                    } else {
                        const span = document.createElement('span');
                        span.className = 'fade-in-word';
                        span.textContent = part;
                        frag.appendChild(span);
                    }
                }
                node.parentNode.replaceChild(frag, node);
            }
            return;
        }
        // Recurse for child nodes
        node.childNodes.forEach(wrapWords);
    }

    if (animate) {
        wrapWords(elem);
    }

    elem.querySelectorAll('pre code').forEach((el) => {
        let text = el.innerText.trim();

        let actions = document.createElement('div');
        actions.classList.add('actions');

        let lang = document.createElement('span');
        lang.classList.add('lang');
        let match = el.className.match(/language-(\w+)/);
        lang.innerText = match ? match[1] : 'text';

        actions.appendChild(lang);

        let copy = document.createElement('x-copy');
        copy.classList.add('copy');

        let icon = document.createElement('i');
        icon.classList.add('ti', 'ti-copy');

        copy.appendChild(icon);
        copy.setAttribute('data-copy', text);

        actions.appendChild(copy);

        el.closest('pre').prepend(actions);

        const wrapper = document.createElement('div');
        wrapper.classList.add('overflow-x-auto');
        wrapper.classList.add('p-4');
        wrapper.classList.add('bg-line-super-dimmed');
        wrapper.classList.add('rounded-b-[14px]');
        actions.after(wrapper);
        wrapper.appendChild(el);
    });

    return elem.innerHTML;
}

function preprocessKaTeX(text) {
    const lines = text.split('\n');
    const processedLines = [];
    let previousLineEmpty = true;  // Assume start of document is an "empty line"

    for (let i = 0; i < lines.length; i++) {
        const line = lines[i];
        const trimmedLine = line.trim();

        if (trimmedLine.startsWith('\\[') && !previousLineEmpty && i !== 0) {
            // If this line starts a KaTeX block and the previous line wasn't empty
            // (and it's not the first line of the document), insert an empty line
            processedLines.push('');
        }

        processedLines.push(line);
        previousLineEmpty = trimmedLine === '';
    }

    return processedLines.join('\n');
}