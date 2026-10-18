(() => {
    /* ---------- Rich text editors (Quill) ---------- */
    const toolbar = [
        [{ header: [3, 4, false] }],
        ['bold', 'italic', 'underline'],
        [{ list: 'bullet' }, { list: 'ordered' }],
        ['link', 'blockquote'],
        ['clean'],
    ];

    const initEditor = (wrapper) => {
        if (wrapper.dataset.ready || typeof Quill === 'undefined') return;
        const textarea = wrapper.querySelector('textarea');
        const host = document.createElement('div');
        // Stored HTML is already sanitized on the server and shaped for Quill (see html_for_editor).
        host.innerHTML = textarea.value || '';
        wrapper.appendChild(host);
        const quill = new Quill(host, {
            theme: 'snow',
            modules: { toolbar },
            placeholder: 'Začněte psát…',
        });
        // The server sanitizer cleans this up (drops Quill's UI spans, turns bullet lists into <ul>).
        const sync = () => { textarea.value = quill.getText().trim() ? quill.root.innerHTML : ''; };
        quill.on('text-change', sync);
        sync();
        wrapper.dataset.ready = '1';
    };

    document.querySelectorAll('[data-editor]').forEach(initEditor);

    /* ---------- Course terms ---------- */
    const termsBox = document.querySelector('[data-terms]');
    const template = document.querySelector('[data-term-template]');
    const addButton = document.querySelector('[data-term-add]');
    let termIndex = Date.now();

    const pad = (n) => String(n).padStart(2, '0');
    const formatDate = (value) => {
        const [y, m, d] = value.split('-').map(Number);
        return `${d}. ${m}. ${y}`;
    };
    // Builds e.g. "27.–29. 11. 2026" from two ISO dates, like the old site wrote them.
    const dateRangeText = (start, end) => {
        if (!start) return '';
        if (!end || end === start) return formatDate(start);
        const [y1, m1, d1] = start.split('-').map(Number);
        const [y2, m2, d2] = end.split('-').map(Number);
        if (y1 === y2 && m1 === m2) return `${d1}.–${d2}. ${m2}. ${y2}`;
        if (y1 === y2) return `${d1}. ${m1}. – ${d2}. ${m2}. ${y2}`;
        return `${formatDate(start)} – ${formatDate(end)}`;
    };

    const wireTerm = (card) => {
        const label = card.querySelector('[data-term-label]');
        const start = card.querySelector('[data-term-start]');
        const end = card.querySelector('[data-term-end]');
        const text = card.querySelector('[data-term-text]');
        const title = card.querySelector('[data-term-title]');
        let lastAuto = text.value === dateRangeText(start.value, end.value) ? text.value : null;

        const refreshTitle = () => {
            title.textContent = [label.value, text.value].filter(Boolean).join(' ') || 'Nový termín';
        };
        const autoFill = () => {
            if (start.value && end.value && end.value < start.value) end.value = start.value;
            if (start.value && !end.value) end.min = start.value;
            // Only overwrite the display text if the user hasn't typed their own.
            if (text.value === '' || text.value === lastAuto) {
                text.value = dateRangeText(start.value, end.value);
                lastAuto = text.value;
            }
            refreshTitle();
        };
        start.addEventListener('change', autoFill);
        end.addEventListener('change', autoFill);
        label.addEventListener('input', refreshTitle);
        text.addEventListener('input', refreshTitle);

        card.querySelector('[data-term-remove]').addEventListener('click', () => {
            const hasContent = label.value || text.value || card.querySelector('textarea[name$="[content]"]').value;
            if (!hasContent || confirm('Odebrat tento termín? Změna se projeví po uložení kurzu.')) card.remove();
        });
        card.querySelector('[data-term-up]').addEventListener('click', () => {
            const prev = card.previousElementSibling;
            if (prev) termsBox.insertBefore(card, prev);
        });
        card.querySelector('[data-term-down]').addEventListener('click', () => {
            const next = card.nextElementSibling;
            if (next) termsBox.insertBefore(next, card);
        });
        card.querySelectorAll('[data-editor]').forEach(initEditor);
    };

    if (termsBox) {
        termsBox.querySelectorAll('[data-term]').forEach(wireTerm);
        addButton?.addEventListener('click', () => {
            const html = template.innerHTML.replaceAll('__INDEX__', String(termIndex++));
            const holder = document.createElement('div');
            holder.innerHTML = html.trim();
            const card = holder.firstElementChild;
            termsBox.appendChild(card);
            wireTerm(card);
            card.querySelector('[data-term-label]').focus();
        });
    }

    /* ---------- Slug preview ---------- */
    const slugSource = document.querySelector('[data-slug-source]');
    const slug = document.querySelector('[data-slug]');
    if (slugSource && slug) {
        const slugify = (s) => s.normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
        let touched = slug.value !== '';
        slug.addEventListener('input', () => { touched = slug.value !== ''; });
        slugSource.addEventListener('input', () => { if (!touched) slug.placeholder = slugify(slugSource.value) || 'vytvoří se z názvu'; });
    }

    /* ---------- Unsaved changes warning ---------- */
    const courseForm = document.querySelector('[data-course-form]');
    if (courseForm) {
        let dirty = false;
        courseForm.addEventListener('input', () => { dirty = true; });
        courseForm.addEventListener('change', () => { dirty = true; });
        document.addEventListener('click', (e) => { if (e.target.closest('[data-term-add], [data-term-remove], [data-term-up], [data-term-down]')) dirty = true; });
        courseForm.addEventListener('submit', () => { dirty = false; });
        window.addEventListener('beforeunload', (e) => { if (dirty) { e.preventDefault(); e.returnValue = ''; } });
    }

    /* ---------- Confirm destructive actions ---------- */
    document.querySelectorAll('form[data-confirm]').forEach((form) => {
        form.addEventListener('submit', (e) => {
            if (!confirm(form.dataset.confirm)) e.preventDefault();
        });
    });

    /* ---------- Close "•••" menus when clicking elsewhere ---------- */
    document.addEventListener('click', (e) => {
        document.querySelectorAll('details.menu[open]').forEach((menu) => {
            if (!menu.contains(e.target)) menu.removeAttribute('open');
        });
    });
})();
