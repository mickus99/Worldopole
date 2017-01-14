<?php


// -----------------------------------------------------------------------------------------------------------
// Pokestops datas
// Total pokestops
// Total lured
// -----------------------------------------------------------------------------------------------------------

$captcha_file	= SYS_PATH.'/core/json/captcha.stats.json';
$capdatas	= json_decode(file_get_contents($captcha_file), true);

if ($config->system->captcha_key=="") {
	$captcha['timestamp'] = $timestamp;
	// get amount of accounts requiring a captcha
	$req = "SELECT COUNT(*) as total "
			. "FROM workerstatus WHERE message LIKE '%encountering a captcha%' "
			. "AND last_modified >= UTC_TIMESTAMP() - INTERVAL 60 SECOND";
	$result 	= $mysqli->query($req);
	$data 		= $result->fetch_object();
	$captcha['captcha_accs'] = $data->total;
	// Add the datas in file
	$capdatas[] 	= $captcha;
} else {
	if (!empty($capdatas)) {
		$lastCaptcha = array_pop($capdatas);
	} else {
		$lastCaptcha["timestamp"]=strtotime("-7 days");
	}
	$lastCaptchaDate = date("Y-m-d", $lastCaptcha["timestamp"]);
	$startTime = strtotime($lastCaptchaDate);
	$endTime = strtotime(date("Y-m-d"));
	$timeDiff = abs($endTime - $startTime);
	$numberDays = intval($timeDiff/86400);  // 86400 seconds in one day
	echo $lastCaptcha["timestamp"]."\n";
	if ($numberDays>7) {
		$numberDays=7;
	}
	while ($numberDays>=0) {
		$day = $startTime+($numberDays*86400);
		$captchaUrl =
				"http://2captcha.com/res.php?key=" .
				$config->system->captcha_key . "&action=getstats&date=" . date("Y-m-d", $day);
		$fileContents= file_get_contents($captchaUrl);
		$capXml = simplexml_load_string($fileContents);
		foreach ($capXml as $key => $value) {
			if (($numberDays==0 && ($value->Attributes()->hour >= date("H", $lastCaptcha["timestamp"])))
					|| $numberDays>0) {
				$captcha['timestamp'] =
						strtotime($value->Attributes()->date . " " . $value->Attributes()->hour . ":00");
				$captcha['captcha_accs'] = (string)$value->volume;
				$capdatas[] 	= $captcha;
			}
		}
		$numberDays--;
	}
}
$json 		= json_encode($capdatas);
file_put_contents($captcha_file, $json);
