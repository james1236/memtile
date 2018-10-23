<?php

//Constants
$cheatMsg = ' (maintenance)';
$leaderboardFileName = 'leaderboard.json';
$blacklistFileName = 'blacklist.json';
$counterFileName = 'counter.json';
$uuidFileName = 'uuid.json';

//Decode obfuscated client data
$roundName = 'z1981';
$aiDirectionsName = 'z5649';
$nameName = 'z6066';
$dateTimeName = 'z7613';
$scoreName = 'z0274';
$aiTimerMaxName = 'z1092';
$uuidName = 'z9800';

//Open File
$handle = fopen($leaderboardFileName, 'a') or die('Leaderboard Unavailable (file)');
$banHandle = fopen($blacklistFileName, 'a') or die('Leaderboard Unavailable (blk)');

//Get/Check UUID
if (!isset($_POST[$uuidName])) {
	die('Leaderboard Unavailable (isset)');
}
$uuid = str_replace('*','',htmlspecialchars($_POST[$uuidName]));
$aiDirections = str_replace('*','',htmlspecialchars($_POST[$aiDirectionsName]));
$dateTime = str_replace('*','',htmlspecialchars($_POST[$dateTimeName]));

if ($uuid == "-2" || $aiDirections == "-2") {
	$counterHandle = fopen($counterFileName, "r"); 
	if ($counterHandle) {
		$counter = (int) fread($counterHandle,20); 
		fclose ($counterHandle); 
		$counter++; 
		$counterHandle = fopen($counterFileName, "w"); 
		fwrite($counterHandle,$counter); 
		fclose ($counterHandle); 
	}
	
	date_default_timezone_set('<RELACE WITH TIMEZONE>');
	$current_date = date('d/m/Y == H:i:s');
	
	$uuidHandle = fopen($uuidFileName, "a"); 
	fwrite($uuidHandle,"\r\n".$uuid.'@'.$dateTime.'#'.$current_date.','); 
	fclose ($uuidHandle); 
	die();
}
	
if ($uuid != "-1") {
	//Get Post
	$round = htmlspecialchars($_POST[$roundName]);
	$aiDirections = htmlspecialchars($_POST[$aiDirectionsName]);
	$name = htmlspecialchars($_POST[$nameName]);
	$dateTime = htmlspecialchars($_POST[$dateTimeName]);
	$score = htmlspecialchars($_POST[$scoreName]);
	$aiTimerMax = htmlspecialchars($_POST[$aiTimerMaxName]);
	
	//Validate Data
	validate('int', $uuid, false);
	validate('uuid', "", false);
	
	//Check if user is banned, if so, die
	$blacklistString = file_get_contents($blacklistFileName);
	$blacklist = explode(",", $blacklistString);
	foreach ($blacklist as $user) {
		if ($user == $uuid) {
			die('Leaderboard Unavailable' . $cheatMsg);
		}
	}

	//Validate Rest of Data
	validate('int', $round);
	validate('int', $score);
	validate('int', $aiTimerMax);

	validate('aiTimerMax');
	validate('aiDirections');
	validate('score');
	validate('name');

	//Remove all of that user's previous entries

	$leaderboard = file_get_contents($leaderboardFileName);
	$leaderboardWriteHandle = fopen($leaderboardFileName,"w");

	$len = 0;
	while($len != strlen($leaderboard)) {
		$len = strlen($leaderboard);
		$regex = '/{"uuid":'.$uuid.'[^*]*\*/';
		$leaderboard = preg_replace($regex,'',$leaderboard);
	}

	fwrite($leaderboardWriteHandle,$leaderboard);
	fclose($leaderboardWriteHandle);

	//Add new entry to leaderboard

	fclose($handle);
	$handle = fopen($leaderboardFileName, 'a');

	$data = str_replace('*','','{"uuid":'.$uuid.
			',"round":'.$round.
			',"aiDirections":"'.$aiDirections.
			'","name":"'.stripslashes($name).
			'","dateTime":"'.$dateTime.
			'","score":'.$score.
			',"aiTimerMax":'.$aiTimerMax.
	'}').'*';
	fwrite($handle, $data);
	fclose($handle);
}

//Sensitive Data Removal (hilarious and questionable method but it works suprisingly well with big data and I just wanted to get it finished)
$leaderboard = file_get_contents($leaderboardFileName);


$len = 0;
while($len != strlen($leaderboard)) {
	$len = strlen($leaderboard);
	//Remove all 10 digit numbers following '"uuid":' up to and including the next comma. Repeat until no matches
	$leaderboard = preg_replace('/.uuid":\d{10},/','',$leaderboard);
	//Remove all aiDirections
	$leaderboard = preg_replace('/.aiDirections":.[^"]*",/','',$leaderboard);
	//Remove aiTimerMax
	$leaderboard = preg_replace('/."aiTimerMax":\d{2}/','',$leaderboard);
	//Remove round
	$leaderboard = preg_replace('/.round":.[^,]*,/','',$leaderboard);
	//Remove dateTime
	$leaderboard = preg_replace('/.dateTime":.[^"]*",/','',$leaderboard);
}

//Remove all cheater entries
$len = 0;
while($len != strlen($leaderboard)) {
	$len = strlen($leaderboard);
	$leaderboard = preg_replace('/."cheater":.[^*]*\*/','',$leaderboard);
}

//Return Leaderboard
echo $leaderboard;
die();

//Validate Data
function validate($type, $var = "", $ban = true) {	
	global $aiTimerMax;
	global $round;
	global $aiDirections;
	global $uuid;
	global $name;
	global $score;
	global $cheatMsg;
	global $handle;
	global $banHandle;
	
	if ($type == 'int') {
		if (!ctype_digit($var)) {
			$data = '{"cheater":true,"uuid":'.$uuid.',"reason":"notAnInt"}*';
			fwrite($handle, $data);
			fclose($handle);
			if ($ban) {
				fwrite($banHandle, $uuid.',');
			}
			die('Leaderboard Unavailable' . $cheatMsg);
		}
	}
	if ($type == 'uuid') {
		if ((int)$uuid > 1000000000 || (int)$uuid < 0) {
			$data = '{"cheater":true,"uuid":'.$uuid.',"reason":"uuidInvalidRange"}*';
			fwrite($handle, $data);
			fclose($handle);
			if ($ban) {
				fwrite($banHandle, $uuid.',');
			}
			die('Leaderboard Unavailable' . $cheatMsg);
		}
		if (strlen($uuid) != 10) {
			$data = '{"cheater":true,"uuid":'.$uuid.',"reason":"uuidInvalidLength"}*';
			fwrite($handle, $data);
			fclose($handle);
			if ($ban) {
				fwrite($banHandle, $uuid.',');
			}
			die('Leaderboard Unavailable' . $cheatMsg);
		}
	}
	if ($type == 'aiTimerMax') {
		if (14 > (int)$aiTimerMax) {
			$data = '{"cheater":true,"uuid":'.$uuid.',"reason":"smallAiTimerMax"}*';
			fwrite($handle, $data);
			fclose($handle);
			if ($ban) {
				fwrite($banHandle, $uuid.',');
			}
			die('Leaderboard Unavailable' . $cheatMsg);
		}
		if ((int)$round >= 8 && (int)$aiTimerMax != 14) {
			$data = '{"cheater":true,"uuid":'.$uuid.',"reason":"aiTimerMaxTooBigForRoundNumber"}*';
			fwrite($handle, $data);
			fclose($handle);
			if ($ban) {
				fwrite($banHandle, $uuid.',');
			}
			die('Leaderboard Unavailable' . $cheatMsg);
		}		
	}
	if ($type == 'aiDirections') {
		if (strlen($aiDirections) != 11+(((int)$round)*3)) {
			$data = '{"cheater":true,"uuid":'.$uuid.',"reason":"aiDirectionsSizeUnfitForRound"}*';
			fwrite($handle, $data);
			fclose($handle);
			if ($ban) {
				fwrite($banHandle, $uuid.',');
			}
			die('Leaderboard Unavailable' . $cheatMsg);
		}
	}	
	if ($type == 'score') {
		//5*round base + triangular numbers formula - offset of 2 (gets you the min value for a score for a round number)
		if ($score < (($round-1)*5)+(($round-2)*($round-2+1))/2) {
			$data = '{"cheater":true,"uuid":'.$uuid.',"reason":"scoreUnderRoundRange"}*';
			fwrite($handle, $data);
			fclose($handle);
			if ($ban) {
				fwrite($banHandle, $uuid.',');
			}
			die('Leaderboard Unavailable' . $cheatMsg);
		}		
		//same formula as before but offset by +1 round (gets you the max value for a score for a round number)
		if ($score > ($round*5)+(($round-1)*$round)/2) {
			$data = '{"cheater":true,"uuid":'.$uuid.',"reason":"scoreOverRoundRange"}*';
			fwrite($handle, $data);
			fclose($handle);
			if ($ban) {
				fwrite($banHandle, $uuid.',');
			}
			die('Leaderboard Unavailable' . $cheatMsg);
		}
	}
	if ($type == 'name') {
		if (strlen($name) > 10) {
			die('Leaderboard Unavailable (change name)');
		}
		if (strlen($name)  == 0) {
			die('Leaderboard Unavailable (change name)');
		}
	}
}

?>
