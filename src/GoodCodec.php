<?php
namespace GoodCodec;

class GoodCodec{

	function mysql_encode_str($str) {
		if($str===NULL){
			return "NULL";
		}else{
			return "'".strtr($str,array("\000"=>"\\0","\n"=>"\\n","\r"=>"\\r","\\"=>"\\\\","'"=>"\\'","\""=>"\\\""))."'";
		}
	}

	public static function mysql_encode_row($row) {
		if($row===NULL||\is_scalar($row)){
			return self::mysql_encode_str($row);
		}elseif(!\is_array($row)){
			throw new \ErrorException("param type error");
		}
		if (\count($row) == 0) {
			return "";//Notice: this likely to cause an error in SQL in-list
		}
		$s="";
		foreach ($row as $k=>$str) {
			if($k!=0){
				$s.=",".self::mysql_encode_str($str);
			}else{
				$s.=self::mysql_encode_str($str);
			}
		}
		return $s;
	}

	public static function mysql_encode_table($data) {
		if(count($data)==0){
			return "";//Notice: this likely to cause an error in SQL in-list
		}
		$s="(";
		foreach ($data as $k=>$row) {
			if($k!=0){
				$s.="),(".self::mysql_encode_row($row);
			}else{
				$s.=self::mysql_encode_row($row);
			}
		}
		return $s.")";
	}

	function mysql_decode_str($str){
		if(@$str[0]==="'"){
			return strtr(substr($str,1,-1),array("\\0"=>"\0","\\n"=>"\n","\\r"=>"\r","\\\\"=>"\\","\\'"=>"'","\\\""=>"\"","\\Z"=>"\032"));
		}else{
			return \strcasecmp($str,"NULL")?$str:NULL;
		}
	}

	public static function tsv_encode_str($str,$out_charset="UTF-8", $in_charset="UTF-8",$append_bom=0,$null="NULL"){
		if($out_charset==="UTF-8"||$out_charset===NULL||preg_match("{^\\s*utf\\-?8\\s*$}si",$out_charset)){
			$out_charset="UTF-8";
		}
		if($in_charset==="UTF-8"||$in_charset===NULL||preg_match("{^\\s*utf\\-?8\\s*$}si",$in_charset)){
			$in_charset="UTF-8";
		}
		$need_iconv=$in_charset!=="UTF-8";
		if($str===NULL){
			return $null;
		}
		$s=\strtr($need_iconv?\iconv($in_charset,"UTF-8",$str):(string)$str,array("\x08"=>"\\b","\x0c"=>"\\f","\r"=>"\\r","\n"=>"\\n","\t"=>"\\t","\x00"=>"\0","'"=>"\\'","\\"=>"\\\\"));
		if($append_bom && $out_charset==="UTF-8" && preg_match("{[\\x80-\\xFF]}",$s)){
			return "\xEF\xBB\xBF".$s;
		}else{
			return $s;
		}
	}

	public static function tsv_encode_row($row,$out_charset="UTF-8", $in_charset="UTF-8",$append_bom=0,$null="\\N"){
		if($out_charset==="UTF-8"||$out_charset===NULL||preg_match("{^\\s*utf\\-?8\\s*$}si",$out_charset)){
			$out_charset="UTF-8";
		}
		if($in_charset==="UTF-8"||$in_charset===NULL||preg_match("{^\\s*utf\\-?8\\s*$}si",$in_charset)){
			$in_charset="UTF-8";
		}
		$need_iconv=$in_charset!=="UTF-8";
		$s="";
		foreach($row as $k=>$str){
			if($k!=0){
				$s.="\t";
			}
			if($str===NULL){
				$s.=$null;
			}
			$s.=\strtr($need_iconv?\iconv($in_charset,"UTF-8",$str):(string)$str,array("\x08"=>"\\b","\x0c"=>"\\f","\r"=>"\\r","\n"=>"\\n","\t"=>"\\t","\x00"=>"\0","'"=>"\\'","\\"=>"\\\\"));
		}
		if($append_bom && $out_charset==="UTF-8" && preg_match("{[\\x80-\\xFF]}",$s)){
			return "\xEF\xBB\xBF".$s;
		}else{
			return $s;
		}
	}

	public static function tsv_encode_table($data,$out_charset="UTF-8", $in_charset="UTF-8",$append_bom=0,$null="\\N",$newline="\n"){
		if($out_charset==="UTF-8"||$out_charset===NULL||preg_match("{^\\s*utf\\-?8\\s*$}si",$out_charset)){
			$out_charset="UTF-8";
		}
		if($in_charset==="UTF-8"||$in_charset===NULL||preg_match("{^\\s*utf\\-?8\\s*$}si",$in_charset)){
			$in_charset="UTF-8";
		}
		$need_iconv=$in_charset!=="UTF-8";
		$s="";
		foreach ($data as $row) {
			foreach($row as $k=>$str){
				if($k!=0){
					$s.="\t";
				}
				if($str===NULL){
					$s.=$null;
				}
				$s.=\strtr($need_iconv?\iconv($in_charset,"UTF-8",$str):(string)$str,array("\x08"=>"\\b","\x0c"=>"\\f","\r"=>"\\r","\n"=>"\\n","\t"=>"\\t","\x00"=>"\0","'"=>"\\'","\\"=>"\\\\"));
			}
			$s.=$newline;
		}
		if($append_bom && $out_charset==="UTF-8" && preg_match("{[\\x80-\\xFF]}",$s)){
			return "\xEF\xBB\xBF".$s;
		}else{
			return $s;
		}
	}

	public static function tsv_decode_stream($stream, $close_stream,$skip_lines=0,$in_charset="UTF-8", $out_charset="UTF-8",$remove_bom=0){
		static $map=array(0=>0,1=>1,2=>2,3=>3,4=>4,5=>5,6=>6,7=>7,8=>8,9=>9,"a"=>10,"A"=>10,"b"=>11,"B"=>11,"c"=>12,"C"=>12,"d"=>13,"D"=>13,"e"=>14,"E"=>14,"f"=>15,"F"=>15);
		static $map2=array("b"=>"\x08","f"=>"\x0c","r"=>"\r","n"=>"\n","t"=>"\t","0"=>"\x00","'"=>"'","\\"=>"\\","a"=>"\x07","v"=>"\x0b");
		if($out_charset==="UTF-8"||$out_charset===NULL||preg_match("{^\\s*utf\\-?8\\s*$}si",$out_charset)){
			$out_charset="UTF-8";
		}
		if($in_charset==="UTF-8"||$in_charset===NULL||preg_match("{^\\s*utf\\-?8\\s*$}si",$in_charset)){
			$in_charset="UTF-8";
		}
		$detect_bom= $remove_bom && $in_charset==="UTF-8"?"\xEF":NULL;
		$need_iconv=$out_charset==="UTF-8";
		$filter=NULL;
		if($in_charset!=="UTF-8"){
			$filter = \stream_filter_append($stream, "convert.iconv.$in_charset.utf-8", STREAM_FILTER_READ);
		}
		if(($c=\fgetc($stream))===false){
			if($filter){
				stream_filter_remove($filter);
			}
			if($close_stream){
				\fclose($stream);
			}
			return;
		}
		$data=$row=array();
		$s="";
		$state=0;
		for(;;){
			($c=\fgetc($stream))!==false or $c="";
RESEND:		switch($state){
				case 0:
					switch($c){
						case "":
							$row[]=$need_iconv&&$s!==NULL?iconv("UTF-8",$out_charset,$s):$s;
							if($skip_lines>0){
								$skip_lines--;
							}else{
								(yield $row);
							}
							break 2;//END no \n but END
						case "\\":
							$state=1;
						break;
						case "\r"://过滤掉后面N个\n
							for(;;){
								($c=\fgetc($stream))!==false or $c="";
								if($c!=="\n"){
									break;
								}
							}
							$row[]=$need_iconv&&$s!==NULL?iconv("UTF-8",$out_charset,$s):$s;
							$s="";
							if($skip_lines>0){
								$skip_lines--;
							}else{
								(yield $row);
							}
							$row=array();
							if($c===""){
								break 3;//NORMAL END
							}
							goto RESEND;
						break;
						case "\t":
							$row[]=$need_iconv&&$s!==NULL?iconv("UTF-8",$out_charset,$s):$s;
							$s="";
						break;
						case "\n"://过滤掉后面N个\r
							for(;;){
								($c=\fgetc($stream))!==false or $c="";
								if($c!=="\r"){
									break;
								}
							}
							$row[]=$need_iconv&&$s!==NULL?iconv("UTF-8",$out_charset,$s):$s;
							$s="";
							if($skip_lines>0){
								$skip_lines--;
							}else{
								(yield $row);
							}
							$row=array();
							if($c===""){
								break 3;//NORMAL END
							}
							goto RESEND;
						break;
						default:
							if($detect_bom && $c===$detect_bom){
								if($detect_bom==="\xEF"){
									$detect_bom="\xBB";
								}elseif($detect_bom==="\xBB"){
									$detect_bom="\xBF";
								}else{
									$detect_bom=NULL;
									$s="";
									($c=\fgetc($stream))!==false or $c="";
									if($c===""){
										break 3;
									}
									continue 3;
								}
							}else{
								$detect_bom=NULL;
							}
							$s.=$c;
					}
				break;
				case 1:
					if($c===""){
						$row[]=$need_iconv&&$s!==NULL?iconv("UTF-8",$out_charset,$s):$s;
						if($skip_lines>0){
							$skip_lines--;
						}else{
							(yield $row);
						}
						break 2;//END last char is uncomplete \
					}
					if(isset($map2[$c])){
						$s.=$map2[$c];
						$state=0;
					}else if($c=="x"){
						$state=2;
					}else if($c=="N"){
						if($s!==""){//TODO check it \N in string,what will happen
							$s.=$c;
							$state=0;
						}else{
							$row[]=null;
							$s="";
							$state=0;
						}
					}else{
						$s.=$c;
						$state=0;
					}
				break;
				case 2:
					if($c===""){
						$row[]=$need_iconv&&$s!==NULL?iconv("UTF-8",$out_charset,$s):$s;
						if($skip_lines>0){
							$skip_lines--;
						}else{
							(yield $row);
						}
						break 2;//END last char is uncomplete \x
					}
					$oct=$map[$c]*16;//warning if not 0-9A-Fa-f
					$state=3;
				break;
				case 3:
					if($c===""){
						$row[]=$need_iconv&&$s!==NULL?iconv("UTF-8",$out_charset,$s):$s;
						if($skip_lines>0){
							$skip_lines--;
						}else{
							(yield $row);
						}
						break 2;//END last char is uncomplete \xN
					}
					$s.=\chr($oct+$map[$c]);//warning if not 0-9A-Fa-f
					$state=0;
				break;
				default:
					throw new \Error("BUG");
			}
		}
		if($filter){
			stream_filter_remove($filter);
		}
		if($close_stream){
			\fclose($stream);
		}
		return;
	}


	public static function tsv_decode_str($str,$skip_lines=0,$in_charset="UTF-8", $out_charset="UTF-8",$remove_bom=0){
		static $map=array(0=>0,1=>1,2=>2,3=>3,4=>4,5=>5,6=>6,7=>7,8=>8,9=>9,"a"=>10,"A"=>10,"b"=>11,"B"=>11,"c"=>12,"C"=>12,"d"=>13,"D"=>13,"e"=>14,"E"=>14,"f"=>15,"F"=>15);
		static $map2=array("b"=>"\x08","f"=>"\x0c","r"=>"\r","n"=>"\n","t"=>"\t","0"=>"\x00","'"=>"'","\\"=>"\\","a"=>"\x07","v"=>"\x0b");
		if($str===""){
			return array();
		}
		if($out_charset==="UTF-8"||$out_charset===NULL||preg_match("{^\\s*utf\\-?8\\s*$}si",$out_charset)){
			$out_charset="UTF-8";
		}
		if($in_charset==="UTF-8"||$in_charset===NULL||preg_match("{^\\s*utf\\-?8\\s*$}si",$in_charset)){
			$in_charset="UTF-8";
		}
		$detect_bom= $remove_bom && $in_charset==="UTF-8"?"\xEF":NULL;
		$need_iconv=$out_charset==="UTF-8";
		if($in_charset!=="UTF-8"){
			$str=iconv($in_charset,"UTF-8",$str);
		}
		$index=0;
		$data=$row=array();
		$s="";
		$state=0;
		for(;;){
			$c=@$str[++$index];
RESEND:		switch($state){
				case 0:
					switch($c){
						case "":
							$row[]=$need_iconv&&$s!==NULL?iconv("UTF-8",$out_charset,$s):$s;
							if($skip_lines>0){
								$skip_lines--;
							}else{
								$data[]=$row;
							}
							break 2;//END no \n but END
						case "\\":
							$state=1;
						break;
						case "\r"://过滤掉后面N个\n
							for(;;){
								$c=@$str[++$index];
								if($c!=="\n"){
									break;
								}
							}
							$row[]=$need_iconv&&$s!==NULL?iconv("UTF-8",$out_charset,$s):$s;
							$s="";
							if($skip_lines>0){
								$skip_lines--;
							}else{
								$data[]=$row;
							}
							$row=array();
							if($c===""){
								break 3;//NORMAL END
							}
							goto RESEND;
						break;
						case "\t":
							$row[]=$need_iconv&&$s!==NULL?iconv("UTF-8",$out_charset,$s):$s;
							$s="";
						break;
						case "\n"://过滤掉后面N个\r
							for(;;){
								$c=@$str[++$index];
								if($c!=="\r"){
									break;
								}
							}
							$row[]=$need_iconv&&$s!==NULL?iconv("UTF-8",$out_charset,$s):$s;
							$s="";
							if($skip_lines>0){
								$skip_lines--;
							}else{
								$data[]=$row;
							}
							$row=array();
							if($c===""){
								break 3;//NORMAL END
							}
							goto RESEND;
						break;
						default:
							if($detect_bom && $c===$detect_bom){
								if($detect_bom==="\xEF"){
									$detect_bom="\xBB";
								}elseif($detect_bom==="\xBB"){
									$detect_bom="\xBF";
								}else{
									$detect_bom=NULL;
									$s="";
									$c=@$str[++$index];
									if($c===""){
										break 3;
									}
									continue 3;
								}
							}else{
								$detect_bom=NULL;
							}
							$s.=$c;
					}
				break;
				case 1:
					if($c===""){
						$row[]=$need_iconv&&$s!==NULL?iconv("UTF-8",$out_charset,$s):$s;
						if($skip_lines>0){
							$skip_lines--;
						}else{
							$data[]=$row;
						}
						break 2;//END last char is uncomplete \
					}
					if(isset($map2[$c])){
						$s.=$map2[$c];
						$state=0;
					}else if($c=="x"){
						$state=2;
					}else if($c=="N"){
						if($s!==""){//TODO check it \N in string,what will happen
							$s.=$c;
							$state=0;
						}else{
							$row[]=null;
							$s="";
							$state=0;
						}
					}else{
						$s.=$c;
						$state=0;
					}
				break;
				case 2:
					if($c===""){
						$row[]=$need_iconv&&$s!==NULL?iconv("UTF-8",$out_charset,$s):$s;
						if($skip_lines>0){
							$skip_lines--;
						}else{
							$data[]=$row;
						}
						break 2;//END last char is uncomplete \x
					}
					$oct=$map[$c]*16;//warning if not 0-9A-Fa-f
					$state=3;
				break;
				case 3:
					if($c===""){
						$row[]=$need_iconv&&$s!==NULL?iconv("UTF-8",$out_charset,$s):$s;
						if($skip_lines>0){
							$skip_lines--;
						}else{
							$data[]=$row;
						}
						break 2;//END last char is uncomplete \xN
					}
					$s.=\chr($oct+$map[$c]);//warning if not 0-9A-Fa-f
					$state=0;
				break;
				default:
					throw new \Error("BUG");
			}
		}
		return $data;
	}

	public static function csv_encode_str($str,$out_charset="UTF-8", $in_charset="UTF-8",$append_bom=0,$null="NULL", $enclosure = "\""){
		if($str===NULL){
			return $null;
		}
		if($out_charset==="UTF-8"||$out_charset===NULL||preg_match("{^\\s*utf\\-?8\\s*$}si",$out_charset)){
			$out_charset="UTF-8";
		}
		if($in_charset==="UTF-8"||$in_charset===NULL||preg_match("{^\\s*utf\\-?8\\s*$}si",$in_charset)){
			$in_charset="UTF-8";
		}
		$need_iconv=$in_charset!=="UTF-8";
		$s.=$enclosure.\strtr($need_iconv?\iconv($in_charset,"UTF-8",$str):(string)$str,array($enclosure=>$enclosure.$enclosure)).$enclosure;
		if($out_charset!=="UTF-8"){
			return iconv("UTF-8",$out_charset,$s);
		}elseif($append_bom && $out_charset==="UTF-8" && preg_match("{[\\x80-\\xFF]}",$s)){
			return "\xEF\xBB\xBF".$s;
		}else{
			return $s;
		}
	}

    public static function csv_encode_row($row, $out_charset="UTF-8", $in_charset="UTF-8",$append_bom=0, $null="NULL", $delimiter = ",", $enclosure = "\""){
		if($out_charset==="UTF-8"||$out_charset===NULL||preg_match("{^\\s*utf\\-?8\\s*$}si",$out_charset)){
			$out_charset="UTF-8";
		}
		if($in_charset==="UTF-8"||$in_charset===NULL||preg_match("{^\\s*utf\\-?8\\s*$}si",$in_charset)){
			$in_charset="UTF-8";
		}
		$need_iconv=$in_charset!=="UTF-8";
		$s="";
		foreach($row as $idx=>$str){
			if($idx!==0){
				$s.=$delimiter;
			}
			if($str===NULL){
				$s.=$null;
				continue;
			}
			$s.=$enclosure.\strtr($need_iconv?\iconv($in_charset,"UTF-8",$str):(string)$str,array($enclosure=>$enclosure.$enclosure)).$enclosure;
		}
		if($out_charset!=="UTF-8"){
			return iconv("UTF-8",$out_charset,$s);
		}elseif($append_bom && $out_charset==="UTF-8" && preg_match("{[\\x80-\\xFF]}",$s)){
			return "\xEF\xBB\xBF".$s;
		}else{
			return $s;
		}
	}

	public static function csv_encode_table_excel($data, $out_charset="UTF-8", $in_charset="UTF-8",$append_bom=1,$null = "", $delimiter = ",", $enclosure = "\"", $newline = "\r\n"){
		if($out_charset==="UTF-8"||$out_charset===NULL||preg_match("{^\\s*utf\\-?8\\s*$}si",$out_charset)){
			$out_charset="UTF-8";
		}
		if($in_charset==="UTF-8"||$in_charset===NULL||preg_match("{^\\s*utf\\-?8\\s*$}si",$in_charset)){
			$in_charset="UTF-8";
		}
		$need_iconv=$in_charset!=="UTF-8";
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
				$str=$need_iconv?\iconv($in_charset,"UTF-8",$str):(string)$str;
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

		if($out_charset!=="UTF-8"){
			return iconv("UTF-8",$out_charset,$s);
		}elseif($append_bom && $out_charset==="UTF-8" && preg_match("{[\\x80-\\xFF]}",$s)){
			return "\xEF\xBB\xBF".$s;
		}else{
			return $s;
		}
	}

	public static function csv_encode_table($data,$out_charset="UTF-8", $in_charset="UTF-8",$append_bom=0,$null="NULL", $delimiter = ",", $enclosure = "\"", $newline = "\n"){
		if($out_charset==="UTF-8"||$out_charset===NULL||preg_match("{^\\s*utf\\-?8\\s*$}si",$out_charset)){
			$out_charset="UTF-8";
		}
		if($in_charset==="UTF-8"||$in_charset===NULL||preg_match("{^\\s*utf\\-?8\\s*$}si",$in_charset)){
			$in_charset="UTF-8";
		}
		$need_iconv=$in_charset!=="UTF-8";
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
				$s.=$enclosure.\strtr($need_iconv?\iconv($in_charset,"UTF-8",$str):(string)$str,array($enclosure=>$enclosure.$enclosure)).$enclosure;
			}
			$s.=$newline;
		}
		if($out_charset!=="UTF-8"){
			return iconv("UTF-8",$out_charset,$s);
		}elseif($append_bom && $out_charset==="UTF-8" && preg_match("{[\\x80-\\xFF]}",$s)){
			return "\xEF\xBB\xBF".$s;
		}else{
			return $s;
		}
	}

	public static function csv_decode_stream($stream, $close_stream,$skip_lines=0,$in_charset="UTF-8", $out_charset="UTF-8",$remove_bom=0,$null=array("\N","NULL"), $delimiter = ",", $enclosure = "\""){
		if($out_charset==="UTF-8"||$out_charset===NULL||preg_match("{^\\s*utf\\-?8\\s*$}si",$out_charset)){
			$out_charset="UTF-8";
		}
		if($in_charset==="UTF-8"||$in_charset===NULL||preg_match("{^\\s*utf\\-?8\\s*$}si",$in_charset)){
			$in_charset="UTF-8";
		}
		$detect_bom=$remove_bom && $in_charset==="UTF-8"?"\xEF":NULL;
		$need_iconv=$out_charset==="UTF-8";
		//convert.iconv.<input-encoding>.<output-encoding>
		$filter=NULL;
		if($in_charset!=="UTF-8"){
			$filter = \stream_filter_append($stream, "convert.iconv.$in_charset.utf-8", STREAM_FILTER_READ);
		}
		if(($c=\fgetc($stream))===false){
			if($filter){
				stream_filter_remove($filter);
			}
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
		$s="";
		$row=array();
		$map=array(""=>0,$delimiter=>1,$enclosure=>2,"\r"=>4,"\n"=>5);
		$map2=array(""=>0,$delimiter=>1,"\r"=>4,"\n"=>5);
        for(;;){
			if(isset($map[$c])){
				switch($map[$c]){
					case 0:
						$row[]=$need_iconv&&$s!==NULL?iconv("UTF-8",$out_charset,$s):$s;
						if($skip_lines>0){
							$skip_lines--;
						}else{
							(yield $row);
						}
					break 2;
					case 1://,
						$row[]=$need_iconv&&$s!==NULL?iconv("UTF-8",$out_charset,$s):$s;
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
												if(isset($map2[$c])){
													break;
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
						$row[]=$need_iconv&&$s!==NULL?iconv("UTF-8",$out_charset,$s):$s;
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
						$row[]=$need_iconv&&$s!==NULL?iconv("UTF-8",$out_charset,$s):$s;
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
				if($detect_bom && $c===$detect_bom){
					if($detect_bom==="\xEF"){
						$detect_bom="\xBB";
					}elseif($detect_bom==="\xBB"){
						$detect_bom="\xBF";
					}else{
						$detect_bom=NULL;
						$s="";
						($c=\fgetc($stream))!==false or $c="";
						if($c===""){
							break 2;
						}
						continue 2;
					}
				}else{
					$detect_bom=NULL;
				}
				if(isset($map2[$c])){
					break;
				}
				$s.=$c;
				($c=\fgetc($stream))!==false or $c="";
			}
			if(isset($map3[$s])){
				$s=NULL;
			}
		}
		if($filter){
			stream_filter_remove($filter);
		}
		if($close_stream){
			\fclose($stream);
		}
        return;
	}

	public static function csv_decode_str($str,$skip_lines=0,$in_charset="UTF-8",$out_charset="UTF-8",$remove_bom=0,$null=array("\N","NULL"),$delimiter = "," ,$enclosure = "\""){
		if($str===""){
			return array();
		}
		if($out_charset==="UTF-8"||$out_charset===NULL||preg_match("{^\\s*utf\\-?8\\s*$}si",$out_charset)){
			$out_charset="UTF-8";
		}
		if($in_charset==="UTF-8"||$in_charset===NULL||preg_match("{^\\s*utf\\-?8\\s*$}si",$in_charset)){
			$in_charset="UTF-8";
		}
		$detect_bom= $remove_bom && $in_charset==="UTF-8"?"\xEF":NULL;
		$need_iconv=$out_charset==="UTF-8";
		if($in_charset!=="UTF-8"){
			$str=iconv($in_charset,"UTF-8",$str);
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
        $s="";
		$data=$row=array();
		//$map=array(""=>0,$delimiter=>1,$enclosure=>2,"\r"=>4,"\n"=>5);
		$map=array(""=>0,$delimiter=>1,$enclosure=>2,"\r"=>4,"\n"=>5);
		$map2=array(""=>0,$delimiter=>1,"\r"=>4,"\n"=>5);
        for(;;){
			$c=@$str[$index];
			if(isset($map[$c])){
				switch($map[$c]){
					case 0:
						$row[]=$need_iconv&&$s!==NULL?iconv("UTF-8",$out_charset,$s):$s;
						if($skip_lines>0){
							$skip_lines--;
						}else{
							$data[]=$row;
						}
					break 2;
					case 1:
						$row[]=$need_iconv&&$s!==NULL?iconv("UTF-8",$out_charset,$s):$s;
						$s="";
						$index++;
					continue 2;
					case 2:
						for(;;){
							$c=@$str[++$index];
							if(isset($map[$c])){
								switch($map[$c]){
									case 0:
										continue 4;//at the end,still cannot find matched $enclosure
									case 2:
										$c=@$str[++$index];
										if($c===$enclosure){
											$s.=$enclosure;
										}else{
											for(;;){
												if(isset($map2[$c])){
													break;
												}
												$s.=$c;
												$c=@$str[++$index];
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
							$c=@$str[++$index];
							if($c!=="\n"){
								break;
							}
						}
						$row[]=$need_iconv&&$s!==NULL?iconv("UTF-8",$out_charset,$s):$s;
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
						$row[]=$need_iconv&&$s!==NULL?iconv("UTF-8",$out_charset,$s):$s;
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
			for(;;){
				if($detect_bom && $c===$detect_bom){
					if($detect_bom==="\xEF"){
						$detect_bom="\xBB";
					}elseif($detect_bom==="\xBB"){
						$detect_bom="\xBF";
					}else{
						$detect_bom=NULL;
						$s="";
						$c=@$str[++$index];
						if($c===""){
							break 2;
						}
						continue 2;
					}
				}else{
					$detect_bom=NULL;
				}
				if(isset($map2[$c])){
					break;
				}
				$s.=$c;
				$c=@$str[++$index];
			}
			if(isset($map3[$s])){
				$s=NULL;
			}
        }
        return $data;
	}
}
/*
echo(GoodCodec::csv_encode_table([[1,NULL,3,4],[5,6,7,8]]));
echo(GoodCodec::csv_encode_table([[1,NULL,"a\\\"a",4],[5,6,7,8]]));
echo(GoodCodec::csv_encode_table([[1,NULL,"a\\\"a",4],["中文",6,7,8]]));
echo(GoodCodec::csv_encode_table_excel([[1,NULL,3,4],[5,6,7,8]]));
echo(GoodCodec::csv_encode_table_excel([[1,NULL,"a\\\"a",4],[5,6,7,8]]));
echo(GoodCodec::csv_encode_table_excel([[1,NULL,"a\\\"a",4],["中文",6,7,8]]));
$fp = fopen("php://temp/maxmemory:50000000", 'rw');
fwrite($fp,iconv("UTF-8","GBK","中文"));
rewind($fp);
foreach(GoodCodec::csv_decode_stream($fp,1,"GBK") as $row ){
	var_dump($row);
}
*/