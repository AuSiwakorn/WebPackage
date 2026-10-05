
// Forms-Text-Editor.js
// ====================================================================
// This file should not be included in your project.
// This is just a sample how to initialize plugins or components.
//
// - ThemeOn.net -


$(document).on('nifty.ready', function() {

    // SUMMERNOTE
    // =================================================================
    // Require Summernote
    // http://hackerwins.github.io/summernote/
    // =================================================================
    $('.demo-summernote, #demo-summernote, #demo-summernote-full-width').summernote({
        minHeight: 600,
        //fontNames: ['Arial', 'Arial Black', 'Comic Sans MS', 'Courier New'],
        toolbar: [
        	['codeview', ['codeview']],
        	['fullscreen', ['fullscreen']],
        	['hr', ['hr']],
        	['float', ['floatLeft', 'floatRight', 'floatNone']],
        	['style', ['bold', 'italic', 'underline']],
        	['font', ['strikethrough', 'superscript', 'subscript']],
        	['fontsize', ['fontsize', 'style']],
        	['para', ['ul', 'ol', 'paragraph']],
        	['color', ['color']],
        	['link', ['linkDialogShow', 'unlink']],
        	['table', ['table']],
        	['picture', ['picture', 'video']],
        	// ['video', ['video',]],
        	//['undo', ['undo',]],
          	//['redo', ['redo',]],
          	//['style', ['style', 'clear']],
        	//['fontname', ['fontname']],
          	//['remove', ['removeMedia']],
            //['height', ['height']],
            //['imagesize', ['imageSize100', 'imageSize50', 'imageSize25']],
          	],
    });




    // SUMMERNOTE AIR-MODE
    // =================================================================
    // Require Summernote
    // http://hackerwins.github.io/summernote/
    // =================================================================
    $('#demo-summernote-airmode').summernote({
        airMode: true
    });





    // SUMMERNOTE CLICK TO EDIT
    // =================================================================
    // Require Summernote
    // http://hackerwins.github.io/summernote/
    // =================================================================
    $('#demo-edit-text').on('click', function(){
        $('#demo-summernote-edit').summernote({focus: true});
    });


    $('#demo-save-text').on('click', function(){
        $('#demo-summernote-edit').summernote('destroy');
    });

})
