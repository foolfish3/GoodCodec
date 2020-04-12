<?php
require_once '../src/GoodCodec.php';
setlocale(LC_CTYPE,"C");
function getcsv($fields){
	$fp = fopen("php://temp/maxmemory:50000000", 'rw');
	fputcsv($fp,$fields);
	rewind($fp);
	$s=stream_get_contents($fp);
	fclose($fp);
	return $s; 
}

for($i=0;$i<256;$i++){
	$c=chr($i);
	$s=GoodCodec\GoodCodec::csv_encode_str($c);
	if($s."\n"!=getcsv([$c])){
		echo "$i $c $s ".getcsv([$c]);
	}
}
echo "compele";