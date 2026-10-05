window.AOEditor = (function ($) {
    function attach(el, target) {
        var $el = $(el);
        if ($el.attr('data-ao-inited') === '1') return;
        $el.attr('data-ao-inited', '1');

        var type = window.AO_EDITOR_TYPE || 'summernote';
        switch (type) {
            case 'ckeditor5':
                attachCK5($el, target);
                break;
            case 'ckeditor':
                attachCK4($el, target);
                break;
            default:
                attachSummernote($el, target);
        }
    }

    function attachCK4($el, target) {
        var el = $el[0];
        if (!el.id) el.id = 'aock4_' + Math.random().toString(36).slice(2);
        var editor = CKEDITOR.inline(el, {
            filebrowserBrowseUrl: '/admweb/include/editor/ckfinder/ckfinder.html',
            filebrowserImageBrowseUrl: '/admweb/include/editor/ckfinder/ckfinder.html?Type=Images',
            filebrowserUploadUrl: '/admweb/include/editor/ckfinder/core/connector/php/connector.php?command=QuickUpload&type=Files',
            filebrowserImageUploadUrl: '/admweb/include/editor/ckfinder/core/connector/php/connector.php?command=QuickUpload&type=Images'
        });
        editor.on('change', function () { $(target).val(editor.getData()); });
    }

    function attachCK5($el, target) {
        if (!window.aoCK5) {
            $el.removeAttr('data-ao-inited');
            console.warn('CK5 ยังโหลดไม่เสร็จ ลองคลิกอีกครั้ง');
            return;
        }
        window.aoCK5.create($el[0])
            .then(function (editor) {
                editor.model.document.on('change:data', function () {
                    $(target).val(editor.getData());
                });
            })
            .catch(function (err) { console.error('CK5 init error:', err); });
    }

    function attachSummernote($el, target) {
        var isMini = $el.hasClass('click-edit-minibox');
        $el.summernote({
            placeholder: 'เขียนเนื้อหาที่นี่...',
            height: isMini ? 190 : 200,
            toolbar: isMini
                ? [
                    ['font', ['bold', 'italic', 'underline']],
                    ['color', ['color']],
                    ['para', ['ul', 'ol', 'paragraph']],
                    ['insert', ['link']],
                    ['view', ['codeview']]
                ]
                : [
                    ['style', ['style']],
                    ['font', ['bold', 'italic', 'underline']],
                    ['color', ['color']],
                    ['para', ['ul', 'ol', 'paragraph']],
                    ['table', ['table']],
                    ['insert', ['link', 'picture']],
                    ['view', ['codeview']]
                ],
            callbacks: {
                onBlur: function () {
                    var markup = $el.summernote('code');
                    $el.html(markup);
                    $(target).val(markup);
                }
            }
        });
    }

    return { attach: attach };
})(jQuery);
