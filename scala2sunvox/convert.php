<?php
$scalaLines = preg_split('/\r\n|\r|\n/', $_POST['scalaTuning']);
$pos = strpos($scalaLines[0], '!');
if ($pos !== false && $pos == 0) {
	$firstLine = preg_split('/\s|[.]/', $scalaLines[0]);
	if (count($firstLine) < 2) {
		echo 'scl not valid';
		exit;
	}
	$fileName = preg_split('/\\\\|\//', $firstLine[1]);
	header('Content-Type: application/octet-stream');
	header('Content-Disposition: attachment; filename="' . $fileName[count($fileName) - 1] . '.curve16bit"');
} else {
	echo 'scl not valid';
	exit;
}
$descriptionLineFound = false;
$numberOfNotes = 0;
$pitchValues = array();
foreach ($scalaLines as $line) {
	$pos = strpos($line, '!');
	if ($pos !== false && $pos == 0) {
		continue;
	}
	if (!$descriptionLineFound) {
		$descriptionLineFound = true;
		continue;
	}
	if ($numberOfNotes == 0) {
		$numberOfNotes = trim($line);
		if ($numberOfNotes == 0) {
			echo 'scl not valid';
			exit;			
		}
		continue;
	}
	$lineSegments = preg_split('/\s/', trim($line));
	if (count($lineSegments) > 1) {
		$line = $lineSegments[0];
	}
	$pos = strpos($line, '.');
	if ($pos !== false) {
		array_push($pitchValues, trim($line) / 100);
		continue;
	}
	$intervalValue = trim($line);
	$pos = strpos($intervalValue, '/');
	if ($pos !== false && $pos > 0) {
		$intervalValue = explode('/', $intervalValue);
		$intervalValue = trim($intervalValue[0]) / trim($intervalValue[1]);
	}
	if ($intervalValue == '') {
		continue;
	}
	$pitchValue = 12 * log($intervalValue, 2);
	array_push($pitchValues, $pitchValue);
	
}
if (count($pitchValues) != $numberOfNotes) {
	echo 'scl not valid';
	exit;
}
foreach ($pitchValues as $value) {
	if (!is_numeric($value)) {
		echo 'scl not valid';
		exit;
	}
}
$rootFrequency = $_POST['rootFrequency'];
if (!is_numeric($rootFrequency)) {
	echo 'root frequency not valid';
	exit;
}
$rootPitch = 12 * log($rootFrequency / 440, 2) + 121;
if ($rootPitch < 0x40 || $rootPitch >= 0xC0) {
	echo 'root frequency not valid';
	exit;
}
// echo 'root pitch ' . $rootPitch . "\n";
$rootNote = $_POST['rootNote'] + $_POST['rootOctave'] * 12;
//echo 'root note ' . $rootNote . "\n";
$noteValues = array();
$startNote = $rootNote;
while ($startNote > 0) {
	$rootPitch -= $pitchValues[$numberOfNotes - 1];
	$startNote -= $numberOfNotes;
}
$rootPitch -= $pitchValues[$numberOfNotes - 1];
$note = $numberOfNotes;
for ($i = $startNote; $i < 128; $i++) {
	if ($note < $numberOfNotes - 1) {
		$pitch = $rootPitch + $pitchValues[$note];
		$note++;
	} else {
		$rootPitch += $pitchValues[$numberOfNotes - 1];
		$pitch = $rootPitch;
		$note = 0;
	}
	if ($i >= 0) {
		if ($pitch < 1) {
			$pitch = 1;
		} else if ($pitch > 0xF2) {
			$pitch = 0xF2;
		}
		$pitch = round($pitch * 256);
		array_push($noteValues, $pitch);
	}
}
foreach ($noteValues as $value) {
	$loByte = $value % 256;
	$hiByte = ($value - $loByte) / 256;
	// echo $loByte . ' ' . $hiByte . "\n";
	echo chr($loByte);
	echo chr($hiByte);
}
// print_r($pitchValues);
// print_r($noteValues);





?>