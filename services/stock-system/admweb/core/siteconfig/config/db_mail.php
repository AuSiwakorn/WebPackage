<?php
$__dbConfigWebsite = array();
$_k = 0;

//Email Contactus : ----------------------- 
$_k++;
$__dbConfigWebsite[$_k]['mail_income']['title'] = 'E-mail Incomming : ';
$__dbConfigWebsite[$_k]['mail_income']['des'] 	= 'Incomming Mail';
$__dbConfigWebsite[$_k]['mail_income']['type'] 	= 'text';
$__dbConfigWebsite[$_k]['mail_income']['notics'] 	= 'E-mail Default : '.ADMIN_EMAIL;
$__dbConfigWebsite[$_k]['mail_income']['customtags'] 	= '';

$__dbConfigWebsite[$_k]['mail_outgoing']['title'] = 'Email Outgoing: ';
$__dbConfigWebsite[$_k]['mail_outgoing']['des'] 	= 'Outgoing E-mail';
$__dbConfigWebsite[$_k]['mail_outgoing']['type'] 	= 'text';
$__dbConfigWebsite[$_k]['mail_outgoing']['notics'] 	= 'E-mail Default : '.ADMIN_EMAIL;
$__dbConfigWebsite[$_k]['mail_outgoing']['customtags'] 	= '';
