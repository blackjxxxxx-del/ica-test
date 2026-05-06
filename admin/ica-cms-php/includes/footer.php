    </div><!-- /page-body -->
</div><!-- /main-content -->
</div><!-- /d-flex -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?php if (!empty($useQuill)): ?>
<link href="https://cdn.quilljs.com/1.3.7/quill.snow.css" rel="stylesheet">
<script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>
<style>
#quill-editor { background:#fff; border-radius:0 0 6px 6px; }
#quill-editor .ql-editor { min-height:420px; font-family:-apple-system,BlinkMacSystemFont,'Sarabun',sans-serif; font-size:15px; line-height:1.8; }
#quill-editor .ql-editor img { max-width:100%; height:auto; border-radius:8px; margin:8px 0; display:block; }
.ql-toolbar.ql-snow { border-radius:6px 6px 0 0; background:#f8fafc; }

/* Upload progress overlay */
#img-upload-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,0.45); z-index:9999; align-items:center; justify-content:center; flex-direction:column; gap:16px; }
#img-upload-overlay.show { display:flex; }
#img-upload-overlay .spinner { width:48px; height:48px; border:4px solid rgba(255,255,255,0.3); border-top-color:#fff; border-radius:50%; animation:ql-spin 0.7s linear infinite; }
#img-upload-overlay p { color:#fff; font-size:15px; font-weight:500; margin:0; }
@keyframes ql-spin { to { transform:rotate(360deg); } }
</style>

<!-- Upload overlay -->
<div id="img-upload-overlay">
    <div class="spinner"></div>
    <p>กำลังอัพโหลดรูป...</p>
</div>

<script>
const quill = new Quill('#quill-editor', {
    theme: 'snow',
    modules: {
        toolbar: [
            [{ header: [1, 2, 3, false] }],
            ['bold', 'italic', 'underline', 'strike'],
            [{ color: [] }, { background: [] }],
            [{ list: 'ordered' }, { list: 'bullet' }],
            [{ align: [] }],
            ['link', 'image', 'video', 'blockquote', 'code-block'],
            [{ indent: '-1' }, { indent: '+1' }],
            ['clean']
        ]
    }
});

// ── Override image handler: upload to server instead of base64 ──
quill.getModule('toolbar').addHandler('image', () => {
    const input = document.createElement('input');
    input.type   = 'file';
    input.accept = 'image/jpeg,image/png,image/webp,image/gif';
    input.onchange = async () => {
        const file = input.files[0];
        if (!file) return;

        const overlay = document.getElementById('img-upload-overlay');
        overlay.classList.add('show');

        const fd = new FormData();
        fd.append('file', file);

        try {
            const res  = await fetch('../api/upload-image.php', { method: 'POST', body: fd });
            const data = await res.json();
            if (data.ok) {
                const range = quill.getSelection(true);
                quill.insertEmbed(range.index, 'image', data.url, Quill.sources.USER);
                quill.setSelection(range.index + 1, Quill.sources.SILENT);
            } else {
                alert('อัพโหลดไม่สำเร็จ: ' + (data.error || 'ลองใหม่อีกครั้ง'));
            }
        } catch (e) {
            alert('เกิดข้อผิดพลาด กรุณาลองใหม่');
        } finally {
            overlay.classList.remove('show');
        }
    };
    input.click();
});

// Load existing content
const contentInput = document.getElementById('content-input');
if (contentInput.value) {
    quill.clipboard.dangerouslyPasteHTML(contentInput.value);
}

// Sync to hidden input on submit
document.querySelector('form').addEventListener('submit', function() {
    contentInput.value = quill.root.innerHTML;
});
</script>
<?php endif; ?>
</body>
</html>
