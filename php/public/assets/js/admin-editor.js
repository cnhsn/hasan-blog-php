/**
 * Minimal dependency-free rich text editor for the admin panel.
 * Uses contenteditable + document.execCommand — deprecated but still
 * universally implemented, and avoids vendoring a third-party editor
 * library (no build step for this project at all).
 *
 * Output is the editable surface's innerHTML, sanitized again
 * server-side on save (App\Security\Sanitizer) regardless of what the
 * browser produces here.
 */
(() => {
  function initEditor(root) {
    const hiddenInput = document.getElementById(root.dataset.target);
    const toolbar = root.querySelector('.editor-toolbar');
    const surface = root.querySelector('.editor-surface');

    surface.innerHTML = hiddenInput.value || '';

    const sync = () => { hiddenInput.value = surface.innerHTML; };
    surface.addEventListener('input', sync);
    surface.addEventListener('blur', sync);

    toolbar.addEventListener('click', (event) => {
      const button = event.target.closest('button[data-cmd]');
      if (!button) return;
      event.preventDefault();
      surface.focus();

      const cmd = button.dataset.cmd;
      if (cmd === 'formatBlock') {
        document.execCommand('formatBlock', false, button.dataset.value);
      } else if (cmd === 'createLink') {
        const url = window.prompt('Link URL (https://, http:// or mailto:)');
        if (url) document.execCommand('createLink', false, url);
      } else if (cmd === 'insertCode') {
        const selection = window.getSelection();
        const text = selection && selection.toString() ? selection.toString() : 'code';
        document.execCommand('insertHTML', false, `<code>${escapeHtml(text)}</code>`);
      } else if (cmd === 'insertCodeBlock') {
        const selection = window.getSelection();
        const text = selection && selection.toString() ? selection.toString() : '';
        document.execCommand('insertHTML', false, `<pre><code>${escapeHtml(text)}</code></pre><p><br></p>`);
      } else if (cmd === 'insertImage') {
        openMediaPicker((media) => {
          document.execCommand('insertHTML', false,
            `<img src="${media.url}" alt="${escapeHtml(media.alt)}" width="${media.width}" height="${media.height}">`);
        });
      } else {
        document.execCommand(cmd, false);
      }
      sync();
    });
  }

  function escapeHtml(value) {
    const div = document.createElement('div');
    div.textContent = value;
    return div.innerHTML;
  }

  let pickerCallback = null;

  function openMediaPicker(onSelect) {
    pickerCallback = onSelect;
    const modal = document.getElementById('media-picker-modal');
    if (!modal) return;
    modal.hidden = false;
    const grid = modal.querySelector('.media-picker-grid');
    grid.innerHTML = '<p>Loading…</p>';

    fetch(modal.dataset.endpoint, { headers: { 'X-Requested-With': 'fetch' } })
      .then((r) => r.json())
      .then((items) => {
        grid.innerHTML = '';
        items.forEach((item) => {
          const button = document.createElement('button');
          button.type = 'button';
          button.className = 'media-picker-item';
          button.innerHTML = `<img src="${item.thumbUrl}" alt="">`;
          button.addEventListener('click', () => {
            if (pickerCallback) pickerCallback(item);
            modal.hidden = true;
          });
          grid.appendChild(button);
        });
      });
  }

  document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.rich-text-editor').forEach(initEditor);

    const modal = document.getElementById('media-picker-modal');
    if (modal) {
      modal.querySelector('.media-picker-close')?.addEventListener('click', () => {
        modal.hidden = true;
      });
    }

    // Generic single-image pickers (cover fields etc.)
    document.querySelectorAll('[data-media-picker-for]').forEach((button) => {
      button.addEventListener('click', () => {
        const targetId = button.dataset.mediaPickerFor;
        const hidden = document.getElementById(targetId);
        const preview = document.getElementById(targetId + '_preview');
        openMediaPicker((media) => {
          hidden.value = media.id;
          if (preview) preview.innerHTML = `<img src="${media.thumbUrl}" alt="" style="max-width:120px;border-radius:4px">`;
        });
      });
    });

    initGalleryEditor();
  });

  function galleryItemHtml(media, caption) {
    return `
      <img src="${media.thumbUrl}" alt="">
      <input type="hidden" name="gallery_media_ids[]" value="${media.id}">
      <input type="text" name="gallery_captions[${media.id}]" value="${caption ? escapeHtml(caption) : ''}" placeholder="Caption (optional)" style="flex:1">
      <button type="button" class="button small danger gallery-remove">Remove</button>
    `;
  }

  function initGalleryEditor() {
    const list = document.getElementById('gallery-list');
    const addButton = document.getElementById('gallery-add-button');
    if (!list || !addButton) return;

    list.addEventListener('click', (event) => {
      if (event.target.classList.contains('gallery-remove')) {
        event.target.closest('.gallery-editor-item')?.remove();
      }
    });

    addButton.addEventListener('click', () => {
      openMediaPicker((media) => {
        const item = document.createElement('div');
        item.className = 'gallery-editor-item';
        item.innerHTML = galleryItemHtml(media, '');
        list.appendChild(item);
      });
    });
  }
})();
