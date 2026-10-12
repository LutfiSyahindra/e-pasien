(() => {
    const form = document.getElementById('landingPageEditor');
    if (!form) return;

    let dirty = false;
    const status = form.querySelector('[data-editor-status]');
    const markDirty = () => {
        dirty = true;
        status.textContent = 'Ada perubahan yang belum disimpan.';
    };
    form.addEventListener('input', markDirty);
    form.addEventListener('change', markDirty);
    form.addEventListener('submit', () => {
        dirty = false;
        form.querySelector('button[type="submit"]').disabled = true;
        status.textContent = 'Menyimpan perubahan...';
    });
    window.addEventListener('beforeunload', (event) => {
        if (dirty) {
            event.preventDefault();
            event.returnValue = '';
        }
    });

    document.querySelectorAll('.landing-editor-navigation a[href^="#"]').forEach((link) => {
        link.addEventListener('click', () => {
            const panel = document.getElementById(link.hash.slice(1));
            if (panel) panel.open = true;
        });
    });

    form.addEventListener('invalid', (event) => {
        const panel = event.target.closest('details');
        if (panel) panel.open = true;
    }, true);

    const reindex = (collection) => {
        const key = collection.dataset.editorCollection;
        const rows = [...collection.querySelector('[data-editor-rows]').children];
        const minimum = Number(collection.dataset.min);
        rows.forEach((row, index) => {
            row.querySelector('[data-row-number]').textContent = index + 1;
            row.querySelectorAll('[data-field]').forEach((input) => {
                const field = input.dataset.field;
                input.name = `content[${key}][${index}][${field}]`;
                input.id = `editor-${key}-${index}-${field}`;
                row.querySelectorAll('[data-field-label]').forEach((label) => {
                    if (label.dataset.fieldLabel === field) label.htmlFor = input.id;
                });
            });
            row.querySelector('[data-row-up]').disabled = index === 0;
            row.querySelector('[data-row-down]').disabled = index === rows.length - 1;
            row.querySelector('[data-row-remove]').disabled = rows.length <= minimum;
        });
        collection.querySelector('[data-collection-count]').textContent = rows.length;
        collection.querySelector('[data-row-add]').disabled = rows.length >= 30;
    };

    form.querySelectorAll('[data-editor-collection]').forEach((collection) => {
        reindex(collection);
        collection.addEventListener('click', (event) => {
            const button = event.target.closest('button');
            if (!button || button.disabled) return;
            const rows = collection.querySelector('[data-editor-rows]');
            const row = button.closest('[data-editor-row]');
            if (button.hasAttribute('data-row-add')) {
                rows.append(collection.querySelector('[data-row-template]').content.cloneNode(true));
                reindex(collection);
                rows.lastElementChild.querySelector('input:not([type="hidden"])')?.focus();
            } else if (button.hasAttribute('data-row-remove')) {
                row.remove();
            } else if (button.hasAttribute('data-row-up') && row.previousElementSibling) {
                rows.insertBefore(row, row.previousElementSibling);
            } else if (button.hasAttribute('data-row-down') && row.nextElementSibling) {
                rows.insertBefore(row.nextElementSibling, row);
            } else {
                return;
            }
            reindex(collection);
            markDirty();
        });
    });

    form.querySelectorAll('[data-image-editor]').forEach((editor) => {
        const input = editor.querySelector('[data-image-input]');
        const reset = editor.querySelector('[data-image-reset]');
        const preview = editor.querySelector('[data-image-preview]');
        let objectUrl;
        const update = () => {
            if (objectUrl) URL.revokeObjectURL(objectUrl);
            if (input.files[0]) {
                objectUrl = URL.createObjectURL(input.files[0]);
                preview.src = objectUrl;
                reset.checked = false;
            } else {
                preview.src = reset.checked ? preview.dataset.defaultSrc : preview.dataset.currentSrc;
            }
        };
        input.addEventListener('change', update);
        reset.addEventListener('change', () => {
            if (reset.checked) input.value = '';
            update();
        });
        update();
    });
})();
