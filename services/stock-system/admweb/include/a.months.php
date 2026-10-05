<?php
function Arrays_months($reqlang='', $short=0)
{
	global $lang;
	if ($reqlang == '') {
		$reqlang = $lang;
	}
	$months = array();
	if ($short == 0) {
    	$months['en'] = array(
    		'01'=>'January', 
    		'02'=>'February', 
    		'03'=>'March', 
    		'04'=>'April', 
    		'05'=>'May', 
    		'06'=>'June', 
    		'07'=>'July', 
    		'08'=>'August', 
    		'09'=>'September', 
    		'10'=>'October', 
    		'11'=>'November', 
    		'12'=>'December'
    	);
    	$months['th'] = array(
    		'01'=>'มกราคม', 
    		'02'=>'กุมภาพันธ์', 
    		'03'=>'มีนาคม', 
    		'04'=>'เมษายน', 
    		'05'=>'พฤษภาคม', 
    		'06'=>'มิถุนายน', 
    		'07'=>'กรกฏาคม', 
    		'08'=>'สิงหาคม', 
    		'09'=>'กันยายน', 
    		'10'=>'ตุลาคม', 
    		'11'=>'พฤศจิกายน', 
    		'12'=>'ธันวาคม'
    	);
	} else {
	    $months['en'] = array(
	        '01'=>'JAN',
	        '02'=>'FEB',
	        '03'=>'MAR',
	        '04'=>'APR',
	        '05'=>'MAY',
	        '06'=>'JUN',
	        '07'=>'JUL',
	        '08'=>'AUG',
	        '09'=>'SEP',
	        '10'=>'OCT',
	        '11'=>'NOV',
	        '12'=>'DEC'
	    );
	    $months['th'] = array(
	        '01'=>'ม.ค.',
	        '02'=>'ก.พ.',
	        '03'=>'มี.ค.',
	        '04'=>'เม.ย.',
	        '05'=>'พ.ค.',
	        '06'=>'มิ.ย.',
	        '07'=>'ก.ค.',
	        '08'=>'ส.ค.',
	        '09'=>'ก.ย.',
	        '10'=>'ต.ค.',
	        '11'=>'พ.ย.',
	        '12'=>'ธ.ค.'
	    );
	}
	return (isset($months[$reqlang])) ? $months[$reqlang] : $months['th'];
}


/* 
$d = date('Y-m-d H:i:s');
echo thai_date($d);
 */
function ThaiDate($datetime) {
    $thai_months = [
        "", "มกราคม", "กุมภาพันธ์", "มีนาคม", "เมษายน", "พฤษภาคม", "มิถุนายน",
        "กรกฎาคม", "สิงหาคม", "กันยายน", "ตุลาคม", "พฤศจิกายน", "ธันวาคม"
    ];

    $dt = new DateTime($datetime);
    $day = (int)$dt->format('d');
    $month = (int)$dt->format('m');
    $year = (int)$dt->format('Y') + 543; // แปลง ค.ศ. → พ.ศ.
    $time = $dt->format('H:i:s');

    return "$day {$thai_months[$month]} $year $time น.";
}

