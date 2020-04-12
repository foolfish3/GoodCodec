<?php


function microtime_float($reset = 0){
    static $last = NULL;
    list ($usec, $sec) = explode(" ", microtime());
    $now = (float) $usec + (float) $sec;
    if ($last === NULL || $reset == 1) {
        $last = $now;
    }
    return round($now - $last, 3);
}

echo 'Current PHP version: ' . phpversion(),"\n";
echo "memory_get_peak_usage() = ".intval(memory_get_peak_usage()/1000/1000)."M"," memory_get_peak_usage(true) = ",intval(memory_get_peak_usage(true)/1000/1000)."M","\n";
microtime_float(1);
$str=random_bytes(5000000);
echo "create: ".microtime_float()." ".strlen($str)."\n";
$chars="abcdefghijklmnopqrstuvwxyz0123456789ABCDEFGHIJKLMNOPQRSTUVWYXZ+/";
$length=strlen($str);
//不干一
$count=0;
microtime_float(1);
for($i=0;$i<$length;$i++){
	$c=$str[$i];
}
echo "不干一: ".microtime_float()." $count \n";

//不干二
$count=0;
microtime_float(1);
for($i=0;$i<$length;$i++){
	$c=$str[$i];
	$count++;
}
echo "不干二: ".microtime_float()." $count \n";

//方案一
function func(&$count,$c){
	$count++;
}
$map1=array();
for($i=0;$i<strlen($chars);$i++){
	$map1[$chars[$i]]="func";
}
$count=0;
microtime_float(1);
for($i=0;$i<$length;$i++){
	$c=$str[$i];
	if(isset($map1[$c])){
		$func=$map1[$c];
		$func($count,$c);
	}
}
echo "方案一: ".microtime_float()." $count \n";

//方案二
$fa=function() use(&$count,&$c){
	$count++;	
};
$map2=array();
for($i=0;$i<strlen($chars);$i++){
	$map2[$chars[$i]]=$fa;
}
$count=0;
microtime_float(1);
for($i=0;$i<$length;$i++){
	$c=$str[$i];
	if(isset($map2[$c])){
		$func=$map2[$c];
		$func();
	}
}
echo "方案二: ".microtime_float()." $count enclosure和普通函数有差不多的性能 call_user_func和\$func() 也是差不多的性能\n";

//方案三
$map3=array();
for($i=0;$i<strlen($chars);$i++){
	$map3[$chars[$i]]=1;
}
$count=0;
microtime_float(1);
for($i=0;$i<$length;$i++){
	$c=$str[$i];
	if(isset($map3[$c])){
		switch($map3[$c]){
			case 1:
				$count++;
		}
	}
}
echo "方案三: ".microtime_float()." $count 胜出!!! 各方面都可代替方案一和方案二，速度还更快，写起来也不用写额外函数更方便\n";

//方案四
$count=0;
microtime_float(1);
for($i=0;$i<$length;$i++){
	$c=$str[$i];
	switch($c){
		case 'a': case 'b': case 'c': case 'd': case 'e': case 'f': case 'g': case 'h': case 'i': case 'j': case 'k': case 'l': case 'm': case 'n': case 'o': case 'p': case 'q': case 'r': case 's': case 't': case 'u': case 'v': case 'w': case 'x': case 'y': case 'z':
		case '0': case '1': case '2': case '3': case '4': case '5': case '6': case '7': case '8': case '9':
		case 'A': case 'B': case 'C': case 'D': case 'E': case 'F': case 'G': case 'H': case 'I'; case 'J': case 'K': case 'L': case 'M': case 'N': case 'O': case 'P': case 'Q': case 'R': case 'S': case 'T': case 'U': case 'V': case 'W': case 'Y': case 'X': case 'Z': 
		case '+': case '/': 
		//case 'a':
		//case 'b':
		//case 'c':
			$count++;break;
		
	}
}
echo "方案四: ".microtime_float()." $count switch 数字超慢!!!\n";

//方案五
$count=0;
microtime_float(1);
for($i=0;$i<$length;$i++){
	$c=$str[$i];
	switch($c){
		case 'a': case 'b': case 'c': case 'd': case 'e': case 'f': case 'g': case 'h': case 'i': case 'j': case 'k': case 'l': case 'm': case 'n': case 'o': case 'p': case 'q': case 'r': case 's': case 't': case 'u': case 'v': case 'w': case 'x': case 'y': case 'z':
		case '~': case '`': case '!': case '@': case '#': case '$': case '%': case '^': case '&': case '*':
		case 'A': case 'B': case 'C': case 'D': case 'E': case 'F': case 'G': case 'H': case 'I'; case 'J': case 'K': case 'L': case 'M': case 'N': case 'O': case 'P': case 'Q': case 'R': case 'S': case 'T': case 'U': case 'V': case 'W': case 'Y': case 'X': case 'Z': 
		case '+': case '/': 
		//case 'a':
		//case 'b':
		//case 'c':
			$count++;break;
		
	}
}
echo "方案五: ".microtime_float()." $count 胜出!!! 只要不用数字,这是最快的方案\n";

//方案五
$count=0;
microtime_float(1);
for($i=0;$i<$length;$i++){
	$c=$str[$i];
	$n=ord($c);
	switch($n){
		case 97: case 98: case 99: case 100: case 101: case 102: case 103: case 104: case 105: case 106: case 107: case 108: case 109: case 110: case 111: case 112: case 113: case 114: case 115: case 116: case 117: case 118: case 119: case 120: case 121: case 122:
		case 48: case 49: case 50: case 51: case 52: case 53: case 54: case 55: case 56: case 57:
		case 65: case 66: case 67: case 68: case 69: case 70: case 71: case 72: case 73: case 74:
		case 75: case 76: case 77: case 78: case 79: case 80: case 81: case 82: case 83: case 84:
		case 85: case 86: case 87: case 88: case 89: case 90:
		case 43: case 47: 
		//case 'a':
		//case 'b':
		//case 'c':
			$count++;break;
		
	}
}
echo "方案六: ".microtime_float()." $count 主要是ord不够快\n";
echo "memory_get_peak_usage() = ".intval(memory_get_peak_usage()/1000/1000)."M"," memory_get_peak_usage(true) = ",intval(memory_get_peak_usage(true)/1000/1000)."M","\n";
echo '
//各种实验结论:
//数组很耗内存 5M字符串拆成数组要450M
//$i<5000000 ~= $i<$length ~= $i<strlen($str) ~= isset($str[$i]) >> $str[$i]!=="" >> $str[$i]!=""
//switch($str[$i]) 单个的时候和直接等于差不多
//"a" 三个等于和两个等于一样速度 "1",""等有两意性的字符串，三个等于比两个等于快
//isset($map[$str[$i]]) 挺快的和直接等于差不多
//@$map[$str[$i]] 非常慢
//fgetc比直接访问要慢
//$ss[]=$c; 很快 ~= $new_str.=$str[$i]; 很快
//$new_str=$new_str.$str[$i]; 非常非常慢
//loop => 0.1
//switch+loop => 0.16
//switch variable+loop => 0.60
//switch+calluserfunc => 0.21
//方案三和方案五胜出
';
exit;

//各种实验结论:
//数组很耗内存 5M字符串拆成数组要450M
//$i<5000000 ~= $i<$length ~= $i<strlen($str) ~= isset($str[$i]) >> $str[$i]!=="" >> $str[$i]!=""
//switch($str[$i]) 单个的时候和直接等于差不多
//"a" 三个等于和两个等于一样速度 "1",""等有两意性的字符串，三个等于比两个等于快
//isset($map[$str[$i]]) 挺快的和直接等于差不多
//@$map[$str[$i]] 非常慢
//fgetc比直接访问要慢
//$ss[]=$c; 很快 ~= $new_str.=$str[$i]; 很快
//$new_str=$new_str.$str[$i]; 非常非常慢
//loop => 0.1
//switch+loop => 0.16
//switch variable+loop => 0.60
//switch+calluserfunc => 0.21
//方案三和方案五胜出
