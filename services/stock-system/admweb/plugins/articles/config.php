<?php
/*
function OPT_CONF($keys, $name, $is = false, $example = '')
{
	global $isOpens, $tagname, $ex;
	$isOpens[$keys] = $is;
	$tagname[$keys] = $name;
	$ex[$keys] = $example;
}
*/
$isOpens = array();
$aArticleConfig = array();
$tagname = array();
$ex = array();

////================TAO TEST==================////                 
OPT_CONF('isOnOff', 'DISPLAY', true);
OPT_CONF('isTitle', 'Title', true);
OPT_CONF('isShortMessage', 'Short Message', false);
OPT_CONF('isIcon', 'Picture icon', true);
OPT_CONF('isIcon2', 'Picture icon2', false);
OPT_CONF('isAuthor', 'ชื่อผู้เขียน', false);
OPT_CONF('isDisplaydate', 'Start Date', false);
OPT_CONF('isKeyswords', 'คีย์เวิร์ด', false);
OPT_CONF('isPreview', 'Preview', false);
OPT_CONF('isAttach', 'Attach', false); //
OPT_CONF('isAttachFile', 'AttachFile', false); //
OPT_CONF('isAttachPic', 'AttachPic', false); //
OPT_CONF('isContent_icon', 'ICON แต่ละภาษา', false);
OPT_CONF('isContent', 'รายละเอียดเนื้อหา Content', false);
OPT_CONF('isContent2', 'รายละเอียดเนื้อหา content2', false);
OPT_CONF('isContent3', 'รายละเอียดเนื้อหา content3', false);
OPT_CONF('isContent4', 'รายละเอียดเนื้อหา content4', false);
OPT_CONF('isContentExtra1', 'รายละเอียด 1', false);
OPT_CONF('isContentExtra2', 'รายละเอียด 2', false);
OPT_CONF('isContentExtra3', 'รายละเอียด 3', false);
OPT_CONF('isSlug', 'URL Name', false);
OPT_CONF('isContentAttach', 'Attach ไฟล์ในแต่ละภาษา', false);
OPT_CONF('isExtra1', 'exname 1', false, 'ex1');
OPT_CONF('isExtra2', 'exname 2', false, 'ex2');
OPT_CONF('isExtra3', 'exname 3', false, 'ex3');
OPT_CONF('isExtra4', 'exname 4', false, 'ex4');
OPT_CONF('isExtra5', 'exname 5', false, 'ex5');
OPT_CONF('isExtra6', 'exname 6', false, 'ex6');
OPT_CONF('isExtra7', 'exname 7', false, 'ex7');
OPT_CONF('isExtra8', 'exname 8', false, 'ex8');
OPT_CONF('isExtra9', 'exname 9', false, 'ex9');
OPT_CONF('isExtra10', 'exname 10', false, 'ex10');
OPT_CONF('isSeo', 'SEO', false);
OPT_CONF('isSeoArticleList', 'SeoArticleList', false); //
OPT_CONF('isCheckedOption', 'CheckedOption', false); //
OPT_CONF('isEndDate', 'EndDate', false); //
OPT_CONF('isTypes', 'เลือกประเภท Type', false);
OPT_CONF('isTypeName', 'Please Select Zone', false);
OPT_CONF('isIconType', 'IconType', false); //
OPT_CONF('isOpenGroup', 'เลือกหมวด', false);
OPT_CONF('isIconGroup', 'อัพโหลดรูปไอคอนของหมวด', false);
OPT_CONF('isIconGroup2', 'อัพโหลดรูปไอคอนของหมวด2', false);
OPT_CONF('isGroupDetail', 'GroupDetail', false); //
OPT_CONF('isGroupSlug', 'URL Name', false); //
OPT_CONF('isGroupDetailExtra1', 'GroupDetailExtra1', false); //
OPT_CONF('isGroupDetailExtra2', 'GroupDetailExtra2', false); //
OPT_CONF('isGroupName', 'GroupName', false); //
OPT_CONF('isExtraOptionGroup', 'checkbox list', false);
OPT_CONF('isGroupExtra1', 'GroupExtra1', false); //
OPT_CONF('isGroupAttachPic', 'isGroupAttachPic', false); //
OPT_CONF('isGroupExtra2', 'GroupExtra2', false); //
OPT_CONF('isGroupExtra3', 'GroupExtra3', false); //
OPT_CONF('isGroupExtra4', 'GroupExtra4', false); //
OPT_CONF('isGroupExtra5', 'GroupExtra5', false); //
OPT_CONF('isSeoGroupInner', 'SeoGroupInner', false); //
OPT_CONF('isSearch', 'Is Search', false);
OPT_CONF('EDTNAME', 'ชื่อ Editor ที่ใช้งาน', GlobalConfig_get('editortype', 'ckeditor')); //ckeditor, summernote

OPT_CONF('isTags', 'Tags', false); //
OPT_CONF('isMark', 'Mark', false); //error

$aArticleConfig['isCheckedOptionData'] = array('Test1');
$aArticleConfig['aExtraOptionGroup']  = array('แสดงทั้งหมด', 'เฉพาะผู้ดูแล', 'เฉพาะสมาชิก');
$aArticleConfig['attach']        = array('jpg', 'gif', 'pdf', 'doc', 'docx');
$aArticleConfig['icon']          = array('jpg', 'gif', 'png');
$aArticleConfig['articles_url']  = '';
$aArticleConfig['widthIcon']     = '0';
$aArticleConfig['editorheight']  = '300';
$aArticleConfig['iconNotics']   = array(
	'Dimensions, widths, desired images ' . $aArticleConfig['widthIcon'] . 'px',
	'The file extension that can be uploaded is ' . implode(' , ', $aArticleConfig['icon']),
);
$aArticleConfig['attachNotics'] = array(
	'The file extension that can be uploaded is ' . implode(' , ', $aArticleConfig['attach']),
);

$tagname['name']      = 'Articles List';
$tagname['titledes']  = (isset($tagname['titledes']) && $tagname['titledes'] != '') ? $tagname['titledes'] : $tagname['name'];
