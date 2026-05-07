import tinymce from 'tinymce';

import 'tinymce/icons/default';
import 'tinymce/themes/silver';
import 'tinymce/models/dom';

import 'tinymce/plugins/advlist';
import 'tinymce/plugins/autolink';
import 'tinymce/plugins/code';
import 'tinymce/plugins/codesample';
import 'tinymce/plugins/image';
import 'tinymce/plugins/link';
import 'tinymce/plugins/lists';
import 'tinymce/plugins/media';
import 'tinymce/plugins/searchreplace';
import 'tinymce/plugins/table';
import 'tinymce/plugins/wordcount';

// Load the editor UI skin into the host document so tinymce.init({ skin: false })
// finds it pre-resolved instead of fetching from a public URL we don't host.
import 'tinymce/skins/ui/oxide/skin.min.css';
import 'tinymce/skins/ui/oxide-dark/skin.min.css';

// Content CSS lives inside TinyMCE's iframe, so we import it as raw strings
// and feed it to `content_style`.
import contentDefaultCss from 'tinymce/skins/content/default/content.min.css?inline';
import contentDarkCss from 'tinymce/skins/content/dark/content.min.css?inline';

// Self-hosted GPL distribution — no Cloud handshake.
const licenseKey = 'gpl';

function detectLanguage() {
    const raw = (document.documentElement.lang || 'en').replace('-', '_');
    if (raw.startsWith('pt')) return 'pt_BR';
    if (raw.startsWith('es')) return 'es';
    return 'en';
}

function configFor(textarea) {
    const compact = textarea.getAttribute('data-tinymce') === 'compact';

    return {
        height: compact ? 220 : 480,
        menubar: false,
        plugins: compact
            ? 'autolink link lists searchreplace wordcount'
            : 'advlist autolink code codesample image link lists media searchreplace table wordcount',
        toolbar: compact
            ? 'undo redo | bold italic underline | link bullist numlist'
            : 'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | forecolor backcolor | link image media table | bullist numlist outdent indent | code',
        font_family_formats: [
            'Figtree=Figtree, ui-sans-serif, system-ui, sans-serif',
            'System default=ui-sans-serif, system-ui, sans-serif',
            'Andale Mono=andale mono,times',
            'Arial=arial,helvetica,sans-serif',
            'Arial Black=arial black,avant garde',
            'Book Antiqua=book antiqua,palatino',
            'Comic Sans MS=comic sans ms,sans-serif',
            'Courier New=courier new,courier',
            'Georgia=georgia,palatino',
            'Helvetica=helvetica',
            'Impact=impact,chicago',
            'Symbol=symbol',
            'Tahoma=tahoma,arial,helvetica,sans-serif',
            'Terminal=terminal,monaco',
            'Times New Roman=times new roman,times',
            'Trebuchet MS=trebuchet ms,geneva',
            'Verdana=verdana,geneva',
        ].join(';'),
        font_size_formats: '10px 12px 14px 16px 18px 20px 24px 30px 36px 48px 60px 72px',
        table_advtab: !compact,
        table_row_advtab: !compact,
        table_cell_advtab: !compact,
        table_appearance_options: !compact,
        table_toolbar: compact
            ? false
            : 'tableprops tablecellprops tabledelete | tableinsertrowbefore tableinsertrowafter tabledeleterow | tableinsertcolbefore tableinsertcolafter tabledeletecol | tablecellbackgroundcolor tablecellbordercolor tableborderwidth tableborderstyle',
        branding: false,
        promotion: false,
        relative_urls: false,
        convert_urls: false,
    };
}

/**
 * Push the editor's current HTML into the Livewire component property the
 * textarea names via `data-livewire-prop`. We use `defer = true` so per-keystroke
 * updates don't fire a network request — the property is bundled with the next
 * action call (e.g. wire:submit="save").
 */
function syncToLivewire(textarea, content) {
    const prop = textarea.getAttribute('data-livewire-prop');
    if (!prop || !window.Livewire) return;

    const root = textarea.closest('[wire\\:id]');
    const id = root?.getAttribute('wire:id');
    if (!id) return;

    const cmp = window.Livewire.find(id);
    if (cmp) {
        cmp.set(prop, content, true);
    }
}

function initTextareas(root = document) {
    if (!root.querySelectorAll) return;

    root.querySelectorAll('textarea[data-tinymce]:not([data-tinymce-initialized])').forEach((textarea) => {
        textarea.setAttribute('data-tinymce-initialized', 'true');
        const dark = document.documentElement.classList.contains('dark');
        const language = detectLanguage();

        tinymce.init({
            ...configFor(textarea),
            target: textarea,
            skin: false,
            content_css: false,
            content_style: (dark ? contentDarkCss : contentDefaultCss)
                + 'body { font-family: Figtree, ui-sans-serif, system-ui, sans-serif; font-size: 16px; }',
            license_key: licenseKey,
            ...(language !== 'en' ? {
                language,
                language_url: `/tinymce/langs/${language}.js`,
            } : {}),
            setup(editor) {
                editor.on('change keyup blur undo redo', () => {
                    editor.save();
                    syncToLivewire(textarea, editor.getContent());
                });
            },
        });
    });
}

function destroyTextareas(root = document) {
    if (!root.querySelectorAll) return;

    root.querySelectorAll('textarea[data-tinymce][data-tinymce-initialized]').forEach((textarea) => {
        const editor = tinymce.get(textarea.id) ?? tinymce.activeEditor;
        if (editor && editor.targetElm === textarea) {
            editor.remove();
        }
        textarea.removeAttribute('data-tinymce-initialized');
    });
}

/** Force every editor to push its current content into its bound Livewire property. */
function flushAllEditors() {
    if (!window.tinymce) return;
    window.tinymce.editors.forEach((ed) => {
        if (!ed.targetElm) return;
        ed.save();
        syncToLivewire(ed.targetElm, ed.getContent());
    });
}

document.addEventListener('DOMContentLoaded', () => initTextareas());
document.addEventListener('livewire:navigated', () => initTextareas());
document.addEventListener('livewire:navigating', () => destroyTextareas());

window.addEventListener('alpine:init', () => initTextareas());

window.tinymceFlushAll = flushAllEditors;