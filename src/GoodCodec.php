<?php
namespace GoodCodec;

class GoodCodec{
	
	public static function csv_encode_str($str,$null="\\N",$delimiter = "," ,$enclosure = "\"", $escape = "\\"){
		if($str===NULL){
			return $null;
		}
		//"\0"=>1,"\x0B"=>1,
        $map=array(" "=>1,"\t"=>1,"\r"=>1,"\n"=>1,$delimiter=>1,$escape=>1,$enclosure=>1);
        $s2="";
		$quote=false;
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
		return $quote?"\"$s2\"":$s2;
    }

    public static function csv_encode_row($row,$null="\\N",$delimiter = "," ,$enclosure = "\"", $escape = "\\"){
		//"\0"=>1,"\x0B"=>1,
		$map=array(" "=>1,"\t"=>1,"\r"=>1,"\n"=>1,$delimiter=>1,$escape=>1,$enclosure=>1);
		$s="";
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
		return $s;
	}

	public static function csv_encode_table($data,$null="\\N",$delimiter = "," ,$enclosure = "\"", $escape = "\\",$newline = "\n"){
		//"\0"=>1,"\x0B"=>1,
		$map=array(" "=>1,"\t"=>1,"\r"=>1,"\n"=>1,$delimiter=>1,$escape=>1,$enclosure=>1);
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
		return $s;
	}

	public static function csv_decode_stream($stream,$close_stream,$skip_lines=0,$null="\\N",$delimiter = "," ,$enclosure = "\"", $escape = "\\"){
		if(($c=\fgetc($stream))===false){
			if($close_stream){
				\fclose($stream);
			}
			return;
		}
        $s="";
		$row=array();
		if($delimiter==="," && $enclosure==="\"" && $escape==="\\"){//a little fast
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
N1:							switch($c){
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
								case "\\":
									($c=\fgetc($stream))!==false or $c="";
									if($c==="\""){
										$s.="\"";
									}else{
										$s.="\\";
										goto N1;
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
						if($s===$null){
							$s=NULL;
						}
				}
			}
			return;
		}
		$map=array(""=>0,$delimiter=>1,$enclosure=>2,$escape=>3,"\r"=>4,"\n"=>4);
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
N2:							if(isset($map[$c])){
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
									case 3:
										($c=\fgetc($stream))!==false or $c="";
										if($c===$enclosure){
											$s.=$enclosure;
										}else{
											$s.=$escape;
											goto N2;
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
			if($s===$null){
				$s=NULL;
			}
        }
        return;
	}

	public static function csv_decode_str($str,$skip_lines=0,$null="\\N",$delimiter = "," ,$enclosure = "\"", $escape = "\\"){
		if($str===""){
			return array();
		}
        $s="";
		$data=$row=array();
		$length=\strlen($str)+1;
		if($delimiter==="," && $enclosure==="\"" && $escape==="\\"){//a little fast
			for($index=0;$index<$length;){
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
N1:							switch($c){
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
								case "\\":
									$c2=@$str[++$index];
									if($c2==="\""){
										$s.="\"";
									}else{
										$s.=$c;
										$c=$c2;
										goto N1;
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
						if($s===$null){
							$s=NULL;
						}
				}
			}
			return $data;
		}
		$map=array(""=>0,$delimiter=>1,$enclosure=>2,$escape=>3,"\r"=>4,"\n"=>4);
		$map2=array(""=>0,$delimiter=>1,"\r"=>4,"\n"=>5);
        for($index=0;$index<$length;){
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
N2:							if(isset($map[$c])){
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
									case 3:
										$c2=@$str[++$index];
										if($c2===$enclosure){
											$s.=$enclosure;
										}else{
											$s.=$c;
											$c=$c2;
											goto N2;
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
			if($s===$null){
				$s=NULL;
			}
        }
        return $data;
	}
}
