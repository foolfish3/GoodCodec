<?php
namespace GoodCodec;

class GoodCodec{
	public const UTF8="UTF-8";
	function mysql_encode_str($str,$noquote=0) {
		if($str===NULL){
			return "NULL";
		}else{
			return $noquote?$str:"'".strtr($str,array("\000"=>"\\0","\n"=>"\\n","\r"=>"\\r","\\"=>"\\\\","'"=>"\\'","\""=>"\\\""))."'";
		}
	}

	public static function mysql_encode_row($row,$noquote=0) {
		if($row===NULL||\is_scalar($row)){
			return self::mysql_encode_str($row,$noquote);
		}elseif(!\is_array($row)){
			throw new \ErrorException("param type error");
		}
		if (\count($row) == 0) {
			\trigger_error("no values in rows,this likely to cause an error in SQL in-list"); 
			return "";//Notice: this likely to cause an error in SQL in-list
		}
		$s="";
		foreach ($row as $k=>$str) {
			if($k!=0){
				$s.=",".self::mysql_encode_str($str,$noquote);
			}else{
				$s.=self::mysql_encode_str($str,$noquote);
			}
		}
		return $s;
	}

	/* use less
	public static function mysql_encode_table($data,$noquote=0) {
		if(count($data)==0){
			\trigger_error("no rows in data,this likely to cause an error in SQL values");
			return "";
		}
		$s="(";
		foreach ($data as $k=>$row) {
			if($k!=0){
				$s.="),(".self::mysql_encode_row($row,$noquote);
			}else{
				$s.=self::mysql_encode_row($row,$noquote);
			}
		}
		return $s.")";
	}*/

	function mysql_decode_str($str){
		if(@$str[0]==="'"){
			return strtr(substr($str,1,-1),array("\\0"=>"\0","\\n"=>"\n","\\r"=>"\r","\\\\"=>"\\","\\'"=>"'","\\\""=>"\"","\\Z"=>"\032"));
		}else{
			return \strcasecmp($str,"NULL")?$str:NULL;
		}
	}

	public static function tsv_encode_str($str,$out_charset="UTF-8", $in_charset="UTF-8",$append_bom=0,$null="NULL"){
		if($out_charset==="UTF-8"||$out_charset===NULL||self::isUTF8($out_charset)){
			$out_charset="UTF-8";
		}
		if($in_charset==="UTF-8"||$in_charset===NULL||self::isUTF8($in_charset)){
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
		if($out_charset==="UTF-8"||$out_charset===NULL||self::isUTF8($out_charset)){
			$out_charset="UTF-8";
		}
		if($in_charset==="UTF-8"||$in_charset===NULL||self::isUTF8($in_charset)){
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
		if($out_charset==="UTF-8"||$out_charset===NULL||self::isUTF8($out_charset)){
			$out_charset="UTF-8";
		}
		if($in_charset==="UTF-8"||$in_charset===NULL||self::isUTF8($in_charset)){
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
		if($out_charset==="UTF-8"||$out_charset===NULL||self::isUTF8($out_charset)){
			$out_charset="UTF-8";
		}
		if($in_charset==="UTF-8"||$in_charset===NULL||self::isUTF8($in_charset)){
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
		$row=array();
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
		if($out_charset==="UTF-8"||$out_charset===NULL||self::isUTF8($out_charset)){
			$out_charset="UTF-8";
		}
		if($in_charset==="UTF-8"||$in_charset===NULL||self::isUTF8($in_charset)){
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
						\trigger_error("last char is uncomplete \\");
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
						\trigger_error("last char is uncomplete \\x");
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
						\trigger_error("last char is uncomplete \\xN");
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

	public static function csv_encode_str($str,$out_charset="UTF-8", $in_charset="UTF-8",$append_bom=0,$null="NULL",$delimiter = ",",$enclosure = "\"",$force_quote=0){
		if($str===NULL){
			return $null;
		}
		if($out_charset==="UTF-8"||$out_charset===NULL||self::isUTF8($out_charset)){
			$out_charset="UTF-8";
		}
		if($in_charset==="UTF-8"||$in_charset===NULL||self::isUTF8($in_charset)){
			$in_charset="UTF-8";
		}
		$need_iconv=$in_charset!=="UTF-8";
		if($str===NULL){
			$s=$null;
		}elseif($str===""){
			$s="$enclosure$enclosure";
		}else{
			$quote=$force_quote;
			if(!$force_quote && \is_string($str)){
				$str=$need_iconv?\iconv($in_charset,"UTF-8",$str):$str;
				$quote=\strpbrk($str,"\r\n\\'\",$enclosure$delimiter")!==false;
			}
			$s=$quote?$enclosure.\strtr($str,array($enclosure=>$enclosure.$enclosure)).$enclosure:$str;
		}
		if($out_charset!=="UTF-8"){
			return iconv("UTF-8",$out_charset,$s);
		}elseif($append_bom && $out_charset==="UTF-8" && preg_match("{[\\x80-\\xFF]}",$s)){
			return "\xEF\xBB\xBF".$s;
		}else{
			return $s;
		}
	}
	protected static function isUTF8($str){
		static $map=array(
			"utf-8"=>1,"Utf-8"=>1,"uTf-8"=>1,"UTf-8"=>1,
			"utF-8"=>1,"UtF-8"=>1,"uTF-8"=>1,"UTF-8"=>1,
		);
		return isset($map[$str]);
	}
    public static function csv_encode_row($row, $out_charset="UTF-8", $in_charset="UTF-8",$append_bom=0, $null="NULL", $delimiter = ",", $enclosure = "\"",$force_quote=0){
		if($out_charset==="UTF-8"||$out_charset===NULL||self::isUTF8($out_charset)){
			$out_charset="UTF-8";
		}
		if($in_charset==="UTF-8"||$in_charset===NULL||self::isUTF8($in_charset)){
			$in_charset="UTF-8";
		}
		$s="";
		foreach($row as $idx=>$str){
			if($idx!==0){
				$s.=$delimiter;
			}
			$s.=self::csv_encode_str($str,"UTF-8",$in_charset,0,$null,$delimiter,$enclosure,$force_quote);
		}
		if($out_charset!=="UTF-8"){
			return iconv("UTF-8",$out_charset,$s);
		}elseif($append_bom && $out_charset==="UTF-8" && preg_match("{[\\x80-\\xFF]}",$s)){
			return "\xEF\xBB\xBF".$s;
		}else{
			return $s;
		}
	}

	public static function csv_encode_table_excel($data, $out_charset="UTF-8"){
		return self::csv_encode_table($data,$out_charset,"UTF-8",1,"",",","\"",0,"\r\n");
	}

	public static function csv_encode_table($data,$out_charset="UTF-8", $in_charset="UTF-8",$append_bom=0,$null="NULL", $delimiter = ",", $enclosure = "\"",$force_quote=0,$newline = "\n"){
		if($out_charset==="UTF-8"||$out_charset===NULL||self::isUTF8($out_charset)){
			$out_charset="UTF-8";
		}
		if($in_charset==="UTF-8"||$in_charset===NULL||self::isUTF8($in_charset)){
			$in_charset="UTF-8";
		}
		$s="";
		foreach($data as $row){
			$s.=self::csv_encode_row($row,"UTF-8",$in_charset,0,$null,$delimiter,$enclosure,$force_quote);
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
		if($out_charset==="UTF-8"||$out_charset===NULL||self::isUTF8($out_charset)){
			$out_charset="UTF-8";
		}
		if($in_charset==="UTF-8"||$in_charset===NULL||self::isUTF8($in_charset)){
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
										\trigger_error("at the end,still cannot find matched enclosure $enclosure ");
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
		if($out_charset==="UTF-8"||$out_charset===NULL||self::isUTF8($out_charset)){
			$out_charset="UTF-8";
		}
		if($in_charset==="UTF-8"||$in_charset===NULL||self::isUTF8($in_charset)){
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
										\trigger_error("at the end,still cannot find matched enclosure $enclosure ");
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


	private static function get_next_splitter($last,$str,$index,$map){
		if($index>=\strlen($str)){
			if(@$map[""]===true){
				return array($last,$index);
            }
			return false;
		}
		$c=$str[$index];
		if(isset($map[$c])){
			$r=self::get_next_splitter($last.$c,$str,$index+1,$map[$c]);
			if($r){
				return $r;
			}
        }
        if($last===""){
 			return false;
		}elseif(@$map[""]===true){
			return array($last,$index);
        }
 		return false;
	}

	private static function generate_splitter_map_set_map($str,&$map) {
		if($str===""){
			$map[""]=true;
		}else{
			if(!isset($map[$str[0]])){
				$map[$str[0]]=array();
			}
			self::generate_splitter_map_set_map(\substr($str,1),$map[$str[0]]);
		}
	}

	private static function generate_splitter_map($splitters){
		$map=array();
		foreach($splitters as $splitter){
			self::generate_splitter_map_set_map($splitter,$map);
        }
		return $map;
	}

	private static function get_next_blank($str,$index){
		$s=@$str[$index];
		for(;;){
			$c=@$str[++$index];
			switch($c){
				case "\r":case "\n":case " ":case "\t":
					$s.=$c;
				break;
				default:
					return array($s,$index);
			}
		}
		throw new \Error("BUG");
	}

    private static function get_next_quote($str,$index){
		$s=$quote=@$str[$index];
        for(;;){
			$c=@$str[++$index];
			if($c===""){
				\trigger_error("uncomplete quote $quote");
				return array($s,$index);
			}
            $s.=$c;
            if($c==="\\"){
                if($quote==="'"||$quote==="\""){
					$c=@$str[++$index];
					if($c===""){
						\trigger_error("uncomplete quote $quote");
						return array($s,$index);
					}
					$s.=$c;
                }
            }elseif($c===$quote){
                return array($s,$index+1);
            }
        }
        throw new \Error("BUG");
    }

    private static function get_next_single_line_comment($str,$index){
		$c=@$str[++$index];
        if($c===">"){//"->",//clickhouse lamba / json operator
			$c=@$str[++$index];
			if($c===">"){// "->>"
                return array("->>",++$index);
            }else{
                return array("->",$index);
            }
        }elseif($c==="-"){
            for($s="--";;){
                $c=@$str[++$index];
                if($c==="\r"||$c==="\n"||$c===""){
                    return array($s,$index);
                }else{
                    $s.=$c;
                }
            }
            throw new \Error("BUG");
        }else{
            return array("-",$index);
        }
    }

    private static function get_next_multi_line_comment($str,$index){
        $c=@$str[++$index];
        if($c==="*"){
            for($s="/*",$last_c="";;$last_c=$c){
				$c=@$str[++$index];$s.=$c;
				if($c==="/" && $last_c==="*"){
					return array($s,$index+1);
				}
				if($c===""){
					return array($s,$index);
				}
            }
            throw new \Error("BUG");
        }else{
            return array("/",$index);
        }
    }
	public static function sql_token_get_all($str){
		static $map=NULL;
		static $cache_key;
		static $cache_value;
        if($map===NULL){
            $splitters=array(
                "<=>",
                "\r\n","!=",">=", "<=", "<>", "<<", ">>", ":=","&&","||","@@",
                "[","]",//clickhouse array
                ">","<","!","^","&","|","=","(", ")", "\t","\r","\n"," ","@",":","+","*","%",";",",",".",
                "\\",
                //support above and: / /* */ -- - ->
            );
			$map=self::generate_splitter_map($splitters);
        }
        if($str===""){
            return array();
		}elseif(\is_int($str)||\is_float($str)){//float number
			return (string)$str;
		}
		if($cache_key===$str){
			return $cache_value;
		}
        $ss=array();
        $s="";
        for($index=0;;){
            $c=@$str[$index];
            switch($c){
                case "'":
                case "\"":
                case "`":
                	if($s!==""){$ss[]=$s;$s="";}
                    list($r,$index)=self::get_next_quote($str,$index,$c);
                    $ss[]=$r;
                break;
                case "-":
                    if($s!==""){$ss[]=$s;$s="";}
                    list($r,$index)=self::get_next_single_line_comment($str,$index);
                    $ss[]=$r;
                break;
                case "/":
                    if($s!==""){$ss[]=$s;$s="";}
                    list($r,$index)=self::get_next_multi_line_comment($str,$index);
                    $ss[]=$r;
				break;
				case "\r":case "\n":case " ":case "\t":
					if($s!==""){$ss[]=$s;$s="";}
                    list($r,$index)=self::get_next_blank($str,$index);
                    $ss[]=$r;
				break;
                case ""://END
                    if($ss!==""){$ss[]=$s;$s="";}
                break 2;
				default:
                    if($r=self::get_next_splitter("",$str,$index,$map)){
						if($s!==""){$ss[]=$s;$s="";}
						$ss[]=$r[0];
                        $index=$r[1];
                    }else{
						$s.=$c;
						$index++;
                    }
            }
		}
		$cache_key=$str;
		$cache_value=$ss;
        return $ss;
	}

	public static function mysql_build_values($tmpl,$data=NULL){
		if($data===NULL){
			list($data,$tmpl)=array($tmpl,$data);
		}
		$ss=array();
		if($tmpl===NULL){
			foreach ($data as $k=>$row) {
				$ss[]="(".self::mysql_encode_row($row,0).")";
			}
		}else{
			foreach ($data as $k=>$row) {
				$ss[]=self::mysql_bind_param($tmpl,$row);
			}
		}
		return $ss;
	}

	public static function mysql_build_values_cut_to_pieces($tmpl,$itr,$size=NULL){
		if ($size === NULL) {
			list ($itr,$size) = array($tmpl,$itr);
		}
		if(\is_scalar($tmpl)){
			$tmpl=self::sql_token_get_all($tmpl);
		}
		foreach(self::cut_to_pieces($itr,$size) as $data){
			(yield self::mysql_build_values($tmpl,$data));
		}
	}

	public static function cut_to_pieces($itr, $converter, $size = NULL){
		if ($size === NULL) {
			list ($converter, $size) = array(NULL,$converter);
		}
		$ar = array();
		foreach ($itr as $row) {
			$ar[] = $converter === NULL ? $row : $converter($row);
			if (count($ar) >= $size) {
				(yield $ar);
				$ar = array();
			}
		}
		if (count($ar) >= 0) {
			(yield $ar);
		}
	}

	//mysql_bind_param($sql,$param1,$param2,...)
    //mysql_bind_param($sql,[$param1,$param2,...])
    //mysql_bind_param([$sql,$param1,$param2,...])
    //mysql_bind_param([$sql,[$param1,$param2,...]])
	public static function mysql_bind_param(){
        $args=\func_get_args();
        $bind_param=array();
        if(\is_array($args[0])){
            $args=$args[0];
        }
        if(isset($args[1]) && is_array($args[1])){
            foreach($args[1] as $k=>&$v){
                if(preg_match("{^\\d+$}",$k)){
                    $bind_param[$k+1]=$v;
                }else{
                    $bind_param[$k]=$v;
                }
            }
        }else{
            for($i=\count($args);$i-->1;){
                $bind_param[$i]=$args[$i];
            }
		}
		$ss=self::sql_token_get_all($args[0]);
        //?
        //?s
        //?s?gogo
        for($idx=1,$i=0;$i<\count($ss);$i++){
            $s=$ss[$i];
            if(@$s[0]==="?"){
                if(!\preg_match("{^\\?([a-zA-Z0-9_]*)(?:\\?([a-zA-Z0-9_]+))?$}",$s,$m)){
                    continue;
                }
                $k=isset($m[2])?$m[2]:$idx++;
                if(\key_exists($k,$bind_param)){
					$p=$bind_param[$k];
                }else{
					if(isset($bind_param[""])){
						$p=\call_user_func($bind_param[""],$k);
					}else{
						throw new \ErrorException("cannot find key $k in param list");
					}
				}
                switch($m[1]){//1 => 1, array() =>"",warning, array(1,2)=>1,2
					case ""://raw no change
						$ss[$i]=self::mysql_encode_row($p,1);
					break;
					case "s":
						$ss[$i]=self::mysql_encode_row($p,0);
                    break;
                }
            }
        }
        return implode($ss);
    }
}
/*
//$data=[[1,NULL,"a\\\"a",4],["中文",6,7,8]];
//var_dump(GoodCodec::mysql_build_values($data));
//var_dump(GoodCodec::mysql_build_values("(?s,?,?s,?)",$data));
$a=GoodCodec::sql_token_get_all("select  * from `aaa` .vvvv =>1  ");
$a=GoodCodec::mysql_bind_param("select 1,2,3,4 where 1=?s",111);
echo(GoodCodec::csv_encode_table([[1,NULL,'',4],[5,6,7,8]]));
echo(GoodCodec::csv_encode_table([[1,NULL,"a\\\"a",4],[5,6,7,8]]));
echo(GoodCodec::csv_encode_table([[1,NULL,"a\\\"a",4],["中文",6,7,8]]));
echo(GoodCodec::csv_encode_table_excel([[1,NULL,3,4],[5,6,7,8]]));
echo(GoodCodec::csv_encode_table_excel([[1,NULL,"a\\\"a",4],[5,6,7,8]]));
echo(GoodCodec::csv_encode_table_excel([[1,NULL,"a\\\"a",4],["中文",6,7,8]]));
$fp = fopen("php://temp/maxmemory:50000000", 'rw');
fwrite($fp,iconv("UTf-8","GBK","中文"));
rewind($fp);
//echo stream_get_contents($fp);
foreach(GoodCodec::csv_decode_stream($fp,1,0,"GBK") as $row ){
	var_dump($row);
}*/
