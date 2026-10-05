import {
	ClassicEditor,
	Autoformat,
	AutoImage,
	AutoLink,
	Autosave,
	BalloonToolbar,
	Bold,
	Code,
	CodeBlock,
	Essentials,
	FindAndReplace,
	FullPage,
	Fullscreen,
	GeneralHtmlSupport,
	Heading,
	HtmlComment,
	HtmlEmbed,
	FontBackgroundColor,
	FontColor,
	FontFamily,
	FontSize,
	ImageBlock,
	ImageCaption,
	ImageInline,
	ImageInsert,
	ImageInsertViaUrl,
	ImageResize,
	ImageStyle,
	ImageTextAlternative,
	ImageToolbar,
	ImageUpload,
	Italic,
	Link,
	LinkImage,
	List,
	ListProperties,
	Mention,
	Paragraph,
	PasteFromOffice,
	PlainTableOutput,
	ShowBlocks,
	SimpleUploadAdapter,
	SourceEditing,
	Table,
	TableCaption,
	TableCellProperties,
	TableColumnResize,
	TableLayout,
	TableProperties,
	TableToolbar,
	TextTransformation,
	TodoList,
	Underline,
	WordCount,
	CKFinder,
	CKFinderUploadAdapter,
	ButtonView
} from 'ckeditor5';

import translations from 'ckeditor5/translations/th.js';

const LICENSE_KEY = 'GPL';
const feature = window.aoCK5Feature || {};
const isCodeBlockEnabled = feature.codeBlock === true;
const isHtmlEmbedEnabled = feature.insertHTML === true;

function aoReplaceImageKeepAlt(editor, url) {
	const el = editor.model.document.selection.getSelectedElement();
	const alt = el && el.getAttribute('alt');
	editor.execute('replaceImageSource', { source: url });
	if (alt) {
		editor.execute('imageTextAlternative', { newValue: alt });
	}
}

function aoApplyMediaUrl(editor, url) {
	const replaceCmd = editor.commands.get('replaceImageSource');
	if (replaceCmd && replaceCmd.isEnabled) {
		aoReplaceImageKeepAlt(editor, url);
	} else {
		editor.execute('insertImage', { source: url });
	}
}

function AoMediaPlugin(editor) {
	editor.ui.componentFactory.add('aoMedia', function () {
		const button = new ButtonView();
		button.set({ label: 'คลังรูป', withText: true, tooltip: true });
		button.on('execute', function () {
			if (window.aoMedia) {
				window.aoMedia.open(function (url) {
					aoApplyMediaUrl(editor, url);
				});
			}
		});
		return button;
	});
}

function AoReplaceImagePlugin(editor) {
	editor.ui.componentFactory.add('aoReplaceImage', function () {
		const button = new ButtonView();
		button.set({ label: 'แทนที่รูป', withText: true, tooltip: true });
		const replaceCmd = editor.commands.get('replaceImageSource');
		if (replaceCmd) {
			button.bind('isEnabled').to(replaceCmd, 'isEnabled');
		} else {
			button.isEnabled = false;
		}
		button.on('execute', function () {
			if (window.aoMedia && editor.commands.get('replaceImageSource')) {
				window.aoMedia.open(function (url) {
					aoReplaceImageKeepAlt(editor, url);
				});
			}
		});
		return button;
	});
}

const editorConfig = {
	fontSize: {
		options: ['10px', '12px', '14px', '16px', '18px', '20px', '24px', '28px', '32px', '36px'],
		supportAllValues: true
	},
	toolbar: {
		items: [
			'undo',
			'redo',
			'|',
			'sourceEditing',
			'showBlocks',
			'findAndReplace',
			'fullscreen',
			'|',
			'heading',
			'|',
			'fontSize',
			'fontFamily',
			'fontColor',
			'fontBackgroundColor',
			'|',
			'bold',
			'italic',
			'code',
			'|',
			'link',
			'insertImage',
			'aoMedia',
			'insertTable',
			...(isCodeBlockEnabled ? ['codeBlock'] : []),
			...(isHtmlEmbedEnabled ? ['htmlEmbed'] : []),
			'|',
			'bulletedList',
			'numberedList'
		],
		shouldNotGroupWhenFull: true
	},
	plugins: [
		Autoformat,
		AutoImage,
		AutoLink,
		Autosave,
		BalloonToolbar,
		Bold,
		Code,
		...(isCodeBlockEnabled ? [CodeBlock] : []),
		...(isHtmlEmbedEnabled ? [HtmlEmbed] : []),
		Essentials,
		FindAndReplace,
		FullPage,
		Fullscreen,
		FontBackgroundColor,
		FontColor,
		FontFamily,
		FontSize,
		GeneralHtmlSupport,
		Heading,
		HtmlComment,
		ImageBlock,
		ImageCaption,
		ImageInline,
		ImageInsert,
		ImageInsertViaUrl,
		ImageResize,
		ImageStyle,
		ImageTextAlternative,
		ImageToolbar,
		ImageUpload,
		Italic,
		Link,
		LinkImage,
		List,
		ListProperties,
		Mention,
		Paragraph,
		PasteFromOffice,
		PlainTableOutput,
		ShowBlocks,
		SimpleUploadAdapter,
		SourceEditing,
		Table,
		TableCaption,
		TableCellProperties,
		TableColumnResize,
		TableLayout,
		TableProperties,
		TableToolbar,
		TextTransformation,
		TodoList,
		Underline,
		WordCount,
		CKFinder,
		CKFinderUploadAdapter
	],
	extraPlugins: [AoMediaPlugin, AoReplaceImagePlugin],
	ckfinder: {
		uploadUrl: '/admweb/include/editor/ckfinder/core/connector/php/connector.php?command=QuickUpload&type=Files&responseType=json'
	},
	balloonToolbar: ['bold', 'italic', '|', 'link', 'insertImage', '|', 'bulletedList', 'numberedList'],
	fullscreen: {
		onEnterCallback: container =>
			container.classList.add(
				'editor-container',
				'editor-container_classic-editor',
				'editor-container_include-word-count',
				'editor-container_include-fullscreen',
				'main-container'
			)
	},
	heading: {
		options: [
			{ model: 'paragraph', title: 'Paragraph', class: 'ck-heading_paragraph' },
			{ model: 'heading1', view: 'h1', title: 'Heading 1', class: 'ck-heading_heading1' },
			{ model: 'heading2', view: 'h2', title: 'Heading 2', class: 'ck-heading_heading2' },
			{ model: 'heading3', view: 'h3', title: 'Heading 3', class: 'ck-heading_heading3' },
			{ model: 'heading4', view: 'h4', title: 'Heading 4', class: 'ck-heading_heading4' },
			{ model: 'heading5', view: 'h5', title: 'Heading 5', class: 'ck-heading_heading5' },
			{ model: 'heading6', view: 'h6', title: 'Heading 6', class: 'ck-heading_heading6' }
		]
	},
	htmlSupport: {
		allow: [{ name: /^.*$/, styles: true, attributes: true, classes: true }]
	},
	image: {
		toolbar: [
			'aoReplaceImage',
			'|',
			'toggleImageCaption',
			'imageTextAlternative',
			'|',
			'imageStyle:inline',
			'imageStyle:wrapText',
			'imageStyle:breakText',
			'|',
			'resizeImage'
		]
	},
	simpleUpload: {
		uploadUrl: '/admbuilder/media.php?ac=upload'
	},
	licenseKey: LICENSE_KEY,
	link: {
		addTargetToExternalLinks: true,
		defaultProtocol: 'https://',
		decorators: {
			toggleDownloadable: {
				mode: 'manual',
				label: 'Downloadable',
				attributes: { download: 'file' }
			}
		}
	},
	list: {
		properties: { styles: true, startIndex: true, reversed: true }
	},
	mention: {
		feeds: [{ marker: '@', feed: [] }]
	},
	placeholder: 'Type or paste your content here!',
	table: {
		contentToolbar: ['tableColumn', 'tableRow', 'mergeTableCells', 'tableProperties', 'tableCellProperties']
	},
	language: 'th'
};

window.aoCK5 = {
	create: function (el) {
		return ClassicEditor.create(el, editorConfig);
	}
};
