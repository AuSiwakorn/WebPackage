<?php
/*
$sqlArray[_DBPREFIX_ . 'menu'] = "
CREATE TABLE IF NOT EXISTS `" . _DBPREFIX_ . "menu` (
  `menu_id` int(11) NOT NULL,
  `menu_parent` int(11) NOT NULL,
  `department_id` int(11) NOT NULL,
  `menu_name` varchar(255) CHARACTER SET utf8 NOT NULL,
  `keysname` varchar(32) CHARACTER SET utf8 NOT NULL,
  `status` int(2) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type_pages` varchar(155) NOT NULL,
  `sorttime` int(11) NOT NULL,
  `userlist` text NOT NULL,
  PRIMARY KEY  (`menu_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;
";

$sqlArray[_DBPREFIX_ . 'distributors'] = "
CREATE TABLE IF NOT EXISTS `" . _DBPREFIX_ . "distributors` (
		`id` int(11) NOT NULL,
		`distribut_name` varchar(255) NOT NULL,
		`country` varchar(55) NOT NULL,
		`user` varchar(155) NOT NULL,
		`password` varchar(50) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;
";
*/