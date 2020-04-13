<?php
namespace GoodCodec;

class GoodCodec{
	
	public static function csv_encode_str($str, $null="NULL", $enclosure = "\""){
		if($str===NULL){
			return $null;
		}
		$s=$enclosure;
		$str=(string)$str;
		$length=\strlen($str);
		for($i=0;$i<$length;$i++){
			$c=$str[$i];
			if($c===$enclosure){
				$s.=$c;
			}
			$s.=$c;
		}
		return $s.$enclosure;
	}

    public static function csv_encode_row($row, $null="NULL", $delimiter = ",", $enclosure = "\""){
		$s="";
		foreach($row as $idx=>$str){
			if($idx!==0){
				$s.=$delimiter;
			}
			if($str===NULL){
				$s.=$null;
				continue;	
			}
			$s.=$enclosure;
			$str=(string)$str;
			$length=\strlen($str);
			for($i=0;$i<$length;$i++){
				$c=$str[$i];
				if($c===$enclosure){
					$s.=$c;
				}
				$s.=$c;
			}
			$s.=$enclosure;
		}
		return $s;
	}

	public static function csv_encode_table_excel($data, $out_charset="UTF-8", $in_charset="UTF-8",$null = "", $delimiter = ",", $enclosure = "\"", $newline = "\r\n"){
		$map=array("\t"=>1,"\r"=>1,"\n"=>1,$delimiter=>1,$enclosure=>1);
		$s="";
		foreach($data as $row){
			foreach($row as $idx=>$str){
				if($idx!==0){
					$s.=",";
				}
				if($str===NULL){
					$s.=$null;
					continue;	
				}
				$s2="";
				$quote=false;
				$str=(string)$str;
				$length=\strlen($str);
				for($i=0;$i<$length;$i++){
					$c=$str[$i];
					if(isset($map[$c])){
						$quote=true;
						if($c===$enclosure){
							$s2.=$c;
						}
					}
					$s2.=$c;
				}
				$s.=($quote?"\"$s2\"":$s2);
			}
			$s.=$newline;
		}
		if($out_charset===NULL||$out_charset==="UTF-8"||preg_match("{^\\s*utf\\-?8\\s*$}si",$out_charset)){
			$out_charset="UTF-8";
		}
		
		if($in_charset===NULL||$in_charset==="UTF-8"||preg_match("{^\\s*utf\\-?8\\s*$}si",$in_charset)){
			$in_charset="UTF-8";
		}
		if($out_charset!=$in_charset){
			$s=iconv($in_charset,$out_charset,$s);
		}
		if($out_charset==="UTF-8" && preg_match("{[\\x80\\xFF]}",$str)){
			return "\xEF\xBB\xBF".$s;
		}else{
			return $s;
		}
	}

	public static function csv_encode_table($data, $null="NULL", $delimiter = ",", $enclosure = "\"", $newline = "\n"){
		$s="";
		foreach($data as $row){
			foreach($row as $idx=>$str){
				if($idx!==0){
					$s.=$delimiter;
				}
				if($str===NULL){
					$s.=$null;
					continue;	
				}
				$s.=$enclosure;
				$str=(string)$str;
				$length=\strlen($str);
				for($i=0;$i<$length;$i++){
					$c=$str[$i];
					if($c===$enclosure){
						$s.=$c;
					}
					$s.=$c;
				}
				$s.=$enclosure;
			}
			$s.=$newline;
		}
		return $s;
	}

	public static function csv_decode_stream($stream, $close_stream, $skip_lines=0, $null=array("\N","NULL"), $remove_bom=1, $delimiter = ",", $enclosure = "\""){
		if(($c=\fgetc($stream))===false){
			if($close_stream){
				\fclose($stream);
			}
			return;
		}
		if($null===NULL){
			$null=array("\N","NULL");
		}elseif(is_scalar($null)){
			$null=array($null);
		}
		$map3=array();
		foreach($null as $v){
			$map3[$v]=1;
		}
		if($remove_bom){
			$remove_bom=1;
		}
		$s="";
		$row=array();
		if($delimiter==="," && $enclosure==="\"" ){//a little fast
			for(;;){
				switch($c){
					case ",":
						$row[]=$s;
						$s="";
						($c=\fgetc($stream))!==false or $c="";
					break;
					case "\"":
						for(;;){
							($c=\fgetc($stream))!==false or $c="";
							switch($c){
								case "\"":
									($c=\fgetc($stream))!==false or $c="";
									if($c==="\""){
										$s.="\"";
									}else{
										for(;;){
											switch($c){
												case "\r": case "\n": case "," : case "" :
												break 2;
											}
											$s.=$c;
											($c=\fgetc($stream))!==false or $c="";
										}
										break 2;
									}
								break;
								case ""://at the end,still cannot find matched $enclosure
								break 2;
								default:
									$s.=$c;
							}
						}		
					break;
					case "":
						$row[]=$s;
						if($skip_lines>0){
							$skip_lines--;
						}else{
							(yield $row);
						}
					break 2;//=== END ===
					case "\r":
						for(;;){
							($c=\fgetc($stream))!==false or $c="";
							if($c!=="\n"){
								break;
							}
						}
						$row[]=$s;
						$s="";
						if($skip_lines>0){
							$skip_lines--;
						}else{
							(yield $row);
						}
						$row=array();
						if($c===""){
							break 2;//=== END ===
						}
					break;
					case "\n":
						for(;;){
							($c=\fgetc($stream))!==false or $c="";
							if($c!=="\r"){
								break;
							}
						}
						$row[]=$s;
						$s="";
						if($skip_lines>0){
							$skip_lines--;
						}else{
							(yield $row);
						}
						$row=array();
						if($c===""){
							break 2;//=== END ===
						}
					break;
					default:
						for(;;){
							switch($c){
								case "\r": case "\n": case "," : case "" :
								break 2;
							}
							$s.=$c;
							($c=\fgetc($stream))!==false or $c="";
						}
						if($remove_bom && @$s[0]==="\xEF" && @$s[1]==="\xBB" && @$s[2]==="\xBF"){
							$remove_bom=0;
							$s=\substr($s,0,3);
							if($c===""){
								break 2;
							}
						}
						if(isset($map3[$s])){
							$s=NULL;
						}
				}
			}
			if($close_stream){
				\fclose($stream);
			}
			return;
		}
		$map=array(""=>0,$delimiter=>1,$enclosure=>2,"\r"=>4,"\n"=>5);
		$map2=array(""=>0,$delimiter=>1,"\r"=>4,"\n"=>5);
        for(;;){
			if(isset($map[$c])){
				switch($map[$c]){
					case 0:
						$row[]=$s;
						if($skip_lines>0){
							$skip_lines--;
						}else{
							(yield $row);
						}
					break 2;
					case 1://,
						$row[]=$s;
						$s="";
						($c=\fgetc($stream))!==false or $c="";
					continue 2;
					case 2://"
						for(;;){
							($c=\fgetc($stream))!==false or $c="";
							if(isset($map[$c])){
								switch($map[$c]){
									case 0:
										continue 4;//at the end,still cannot find matched $enclosure
									case 2:
										($c=\fgetc($stream))!==false or $c="";
										if($c===$enclosure){
											$s.=$enclosure;
										}else{
											for(;;){
												switch($c){
													case "\r": case "\n": case "," : case "" :
													break 2;
												}
												$s.=$c;
												($c=\fgetc($stream))!==false or $c="";
											}
											continue 4;
										}
									continue 2;
								}
							}
							$s.=$c;
						}
					throw new \Error("BUG");
					case 4:
						for(;;){
							($c=\fgetc($stream))!==false or $c="";
							if($c!=="\n"){
								break;
							}
						}
						$row[]=$s;
						$s="";
						if($skip_lines>0){
							$skip_lines--;
						}else{
							(yield $row);
						}
						$row=array();
						if($c===""){
							break 2;//=== END ===
						}
					continue 2;
					case 5:
						for(;;){
							($c=\fgetc($stream))!==false or $c="";
							if($c!=="\r"){
								break;
							}
						}
						$row[]=$s;
						$s="";
						if($skip_lines>0){
							$skip_lines--;
						}else{
							(yield $row);
						}
						$row=array();
						if($c===""){
							break 2;//=== END ===
						}
					continue 2;
				}
			}
			for(;;){
				if(isset($map2[$c])){
					break;
				}
				$s.=$c;
				($c=\fgetc($stream))!==false or $c="";
			}
			if($remove_bom && @$s[0]==="\xEF" && @$s[1]==="\xBB" && @$s[2]==="\xBF"){
				$remove_bom=0;
				$s=\substr($s,0,3);
				if($c===""){
					break;
				}
			}
			if(isset($map3[$s])){
				$s=NULL;
			}
		}
		if($close_stream){
			\fclose($stream);
		}
        return;
	}

	public static function csv_decode_str($str,$skip_lines=0,$null=array("\N","NULL"),$remove_bom=1,$delimiter = "," ,$enclosure = "\""){
		if($str===""||$str==="\xEF\xBB\xBF"){
			return array();
		}
		if($null===NULL){
			$null=array("\N","NULL");
		}elseif(is_scalar($null)){
			$null=array($null);
		}
		$map3=array();
		foreach($null as $v){
			$map3[$v]=1;
		}
		$index=0;
		if($remove_bom && $str[0]==="\xEF" && @$str[1]==="\xBB" && @$str[2]==="\xBF"){
			$index=3;
		}
        $s="";
		$data=$row=array();
		$length=\strlen($str)+1;
		if($delimiter==="," && $enclosure==="\""){//a little fast
			for(;$index<$length;){
				$c=@$str[$index];
				switch($c){
					case ",":
						$row[]=$s;
						$s="";
						$index++;
					break;
					case "\"":
						$index++;
						for(;$index<$length;$index++){
							$c=@$str[$index];
							switch($c){
								case "\"":
									$c=@$str[++$index];
									if($c==="\""){
										$s.="\"";
									}else{
										for($old_index=$index;$index<$length;$index++){
											switch(@$str[$index]){
												case "\r": case "\n": case "," : case "" :
												break 2;
											}
										}
										$s.=\substr($str,$old_index,$index-$old_index);
										break 2;
									}
								break;
								case ""://at the end,still cannot find matched $enclosure
								break 2;
								default:
									$s.=$c;
							}
						}		
					break;
					case "":
						$row[]=$s;
						if($skip_lines>0){
							$skip_lines--;
						}else{
							$data[]=$row;
						}
					break 2;//=== END ===
					case "\r":
						for(;;){
							$c=@$str[++$index];
							if($c!=="\n"){
								break;
							}
						}
						$row[]=$s;
						$s="";
						if($skip_lines>0){
							$skip_lines--;
						}else{
							$data[]=$row;
						}
						$row=array();
						if($c===""){
							break 2;//=== END ===
						}
					break;
					case "\n":
						for(;;){
							$c=@$str[++$index];
							if($c!=="\r"){
								break;
							}
						}
						$row[]=$s;
						$s="";
						if($skip_lines>0){
							$skip_lines--;
						}else{
							$data[]=$row;
						}
						$row=array();
						if($c===""){
							break 2;//=== END ===
						}
					break;
					default:
						for($old_index=$index;$index<$length;$index++){
							switch(@$str[$index]){
								case "\r": case "\n": case "," : case "" :
								break 2;
							}
						}
						$s=\substr($str,$old_index,$index-$old_index);
						if(isset($map3[$s])){
							$s=NULL;
						}
				}
			}
			return $data;
		}
		$map=array(""=>0,$delimiter=>1,$enclosure=>2,"\r"=>4,"\n"=>5);
		$map2=array(""=>0,$delimiter=>1,"\r"=>4,"\n"=>5);
        for(;$index<$length;){
			$c=@$str[$index];
			if(isset($map[$c])){
				switch($map[$c]){
					case 0:
						$row[]=$s;
						if($skip_lines>0){
							$skip_lines--;
						}else{
							$data[]=$row;
						}
					break 2;
					case 1:
						$row[]=$s;
						$s="";
						$index++;
					continue 2;
					case 2:
						$index++;
						for(;$index<$length;$index++){
							$c=@$str[$index];
							if(isset($map[$c])){
								switch($map[$c]){
									case 0:
										continue 4;//at the end,still cannot find matched $enclosure
									case 2:
										$c=@$str[++$index];
										if($c===$enclosure){
											$s.=$enclosure;
										}else{
											for($old_index=$index;$index<$length;$index++){
												if(isset($map2[@$str[$index]])){
													break;
												}
											}
											$s.=\substr($str,$old_index,$index-$old_index);
											continue 4;
										}
									continue 2;
								}
							}
							$s.=$c;
						}
					throw new \Error("BUG");
					case 4:
						for(;;){
							$c=@$str[++$index];
							if($c!=="\n"){
								break;
							}
						}
						$row[]=$s;
						$s="";
						if($skip_lines>0){
							$skip_lines--;
						}else{
							$data[]=$row;
						}
						$row=array();
						if($c===""){
							break 2;//=== END ===
						}
					continue 2;
					case 5:
						for(;;){
							$c=@$str[++$index];
							if($c!=="\r"){
								break;
							}
						}
						$row[]=$s;
						$s="";
						if($skip_lines>0){
							$skip_lines--;
						}else{
							$data[]=$row;
						}
						$row=array();
						if($c===""){
							break 2;//=== END ===
						}
					continue 2;
				}
			}
			for($old_index=$index;$index<$length;$index++){
				if(isset($map2[@$str[$index]])){
					break;
				}
			}
			$s=\substr($str,$old_index,$index-$old_index);
			if(isset($map3[$s])){
				$s=NULL;
			}
        }
        return $data;
	}
}
