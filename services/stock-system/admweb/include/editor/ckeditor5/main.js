/**
 * This configuration was generated using the CKEditor 5 Builder. You can modify it anytime using this link:
 * https://ckeditor.com/ckeditor-5/builder/#installation/NoFgNARATAdAbDADBSBGKqCccqMagVgKgIGYC5MCRFc9TEAOOWrQxzBgxqRxlCAFMAdikRhgqMFPGzpAXTQAzQYgCGAYzgR5QA==
 */

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
	// Emoji,
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
	// SpecialCharacters,
	// SpecialCharactersArrows,
	// SpecialCharactersCurrency,
	// SpecialCharactersEssentials,
	// SpecialCharactersLatin,
	// SpecialCharactersMathematical,
	// SpecialCharactersText,
	Table,
	TableCaption,
	TableCellProperties,
	TableColumnResize,
	TableLayout,
	TableProperties,
	TableToolbar,
	// TextPartLanguage,
	TextTransformation,
	// Title,
	TodoList,
	Underline,
	WordCount,
	CKFinder,
	CKFinderUploadAdapter
} from 'ckeditor5';

import translations from 'ckeditor5/translations/th.js';

/**
 * Create a free account with a trial: https://portal.ckeditor.com/checkout?plan=free
 */
const LICENSE_KEY = 'GPL'; // or <YOUR_LICENSE_KEY>.
const isCodeBlockEnabled = typeof ckeditorFeatureConfig !== 'undefined' && ckeditorFeatureConfig.codeBlock === true;
const isHtmlEmbedEnabled = typeof ckeditorFeatureConfig !== 'undefined' && ckeditorFeatureConfig.insertHTML === true;

const editorConfig = {
	fontSize: {
		options: [
			'10px',
			'12px',
			'14px',
			'16px',
			'18px',
			'20px',
			'24px',
			'28px',
			'32px',
			'36px'
		],
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
			// 'textPartLanguage',
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
			// 'emoji',
			// 'specialCharacters', // ยกเลิก comment ถ้าต้องการใช้
			'link',
			'insertImage',
			'insertTable',
			// 'insertTableLayout',
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
		Code, // เพิ่ม Code plugin
		...(isCodeBlockEnabled ? [CodeBlock] : []),
		...(isHtmlEmbedEnabled ? [HtmlEmbed] : []),
		// Emoji,
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
		// HtmlEmbed, // ไม่ต้องซ้ำเพราะอยู่ในเงื่อนไขแล้ว
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
		// SpecialCharacters,
		// SpecialCharactersArrows,
		// SpecialCharactersCurrency,
		// SpecialCharactersEssentials,
		// SpecialCharactersLatin,
		// SpecialCharactersMathematical,
		// SpecialCharactersText,
		Table,
		TableCaption,
		TableCellProperties,
		TableColumnResize,
		TableLayout,
		TableProperties,
		TableToolbar,
		// TextPartLanguage,
		TextTransformation,
		// Title,
		TodoList,
		Underline,
		WordCount,
		CKFinder,
		CKFinderUploadAdapter
	],
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
			{
				model: 'paragraph',
				title: 'Paragraph',
				class: 'ck-heading_paragraph'
			},
			{
				model: 'heading1',
				view: 'h1',
				title: 'Heading 1',
				class: 'ck-heading_heading1'
			},
			{
				model: 'heading2',
				view: 'h2',
				title: 'Heading 2',
				class: 'ck-heading_heading2'
			},
			{
				model: 'heading3',
				view: 'h3',
				title: 'Heading 3',
				class: 'ck-heading_heading3'
			},
			{
				model: 'heading4',
				view: 'h4',
				title: 'Heading 4',
				class: 'ck-heading_heading4'
			},
			{
				model: 'heading5',
				view: 'h5',
				title: 'Heading 5',
				class: 'ck-heading_heading5'
			},
			{
				model: 'heading6',
				view: 'h6',
				title: 'Heading 6',
				class: 'ck-heading_heading6'
			}
		]
	},
	htmlSupport: {
		allow: [
			{
				name: /^.*$/,
				styles: true,
				attributes: true,
				classes: true
			}
		]
	},
	image: {
		toolbar: [
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
		uploadUrl: '/admweb/include/editor/ckeditor5/upload.php'
	},
	licenseKey: LICENSE_KEY,
	link: {
		addTargetToExternalLinks: true,
		defaultProtocol: 'https://',
		decorators: {
			toggleDownloadable: {
				mode: 'manual',
				label: 'Downloadable',
				attributes: {
					download: 'file'
				}
			}
		}
	},
	list: {
		properties: {
			styles: true,
			startIndex: true,
			reversed: true
		}
	},
	mention: {
		feeds: [
			{
				marker: '@',
				feed: [
					/* See: https://ckeditor.com/docs/ckeditor5/latest/features/mentions.html */
				]
			}
		]
	},
	placeholder: 'Type or paste your content here!',
	table: {
		contentToolbar: ['tableColumn', 'tableRow', 'mergeTableCells', 'tableProperties', 'tableCellProperties']
	},
	language: 'th' // เพิ่มภาษาไทย
};

const editors = {};

editorLanguages.forEach(kLang => {
	const editorElements = document.querySelectorAll('[id^="editor"][id$="_' + kLang + '"]');

	editorElements.forEach(editorElement => {
		const editorId = editorElement.id;
		const editorNumberMatch = editorId.match(/^editor(\d+)_/);
		if (!editorNumberMatch) {
			console.warn(`ID ของ editor ไม่ได้อยู่ในรูปแบบที่คาดไว้: ${editorId}`);
			return;
		}
		const editorNumber = editorNumberMatch[1];
		const outputId = `output${editorNumber}_${kLang}`;

		ClassicEditor.create(editorElement, editorConfig)
			.then(editor => {
				editors[editorId] = editor;

				const suffix = editorId.replace('editor', '').replace('_', '');

				const wordCount = editor.plugins.get('WordCount');
				const wordCountContainer = document.getElementById(`editor-word-count-${suffix}`);
				if (wordCountContainer) {
					wordCountContainer.appendChild(wordCount.wordCountContainer);
				}

				const menuBarElement = document.getElementById(`editor-menu-bar-${suffix}`);
				if (menuBarElement) {
					menuBarElement.appendChild(editor.ui.view.menuBarView.element);
				}

				document.querySelector('form').addEventListener('submit', function () {
					const currentOutputElement = document.getElementById(outputId);
					if (currentOutputElement) {
						currentOutputElement.value = editor.getData();
					} else {
						console.warn(`ไม่พบ element สำหรับ output ID: ${outputId}`);
					}
				});

				return editor;
			})
			.catch(error => {
				console.error(`มีปัญหาในการเริ่มต้น Editor (${editorId}):`, error);
				if (error.name === 'CKEditorError' && error.code) {
					console.error('CKEditor Specific Error Code:', error.code);
					console.error('อ่านข้อมูลเพิ่มเติมเกี่ยวกับ Error นี้:', `https://ckeditor.com/docs/ckeditor5/latest/support/error-codes.html#error-${error.code}`);
				} else {
					console.error('ข้อมูล Error เพิ่มเติม:', error);
				}
			});
	});
});