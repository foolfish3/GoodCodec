<?php

namespace GoodCodec;

class GoodCodecTSV
{

    protected static $utf8_map = array(
        "utf-8" => 1, "Utf-8" => 1, "uTf-8" => 1, "UTf-8" => 1,
        "utF-8" => 1, "UtF-8" => 1, "uTF-8" => 1, "UTF-8" => 1,
    );

    public static function tsv_encode_str($str, $out_charset = "UTF-8", $in_charset = "UTF-8", $append_bom = 0, $null = "NULL")
    {
        if ($out_charset === "UTF-8" || $out_charset === NULL || isset(self::$utf8_map[$out_charset])) {
            $out_charset = "UTF-8";
        }
        if ($in_charset === "UTF-8" || $in_charset === NULL || isset(self::$utf8_map[$in_charset])) {
            $in_charset = "UTF-8";
        }
        $need_iconv = $in_charset !== "UTF-8";
        if ($str === NULL) {
            $s = $null;
        } else {
            $s = \strtr($need_iconv ? \iconv($in_charset, "UTF-8", $str) : (string) $str, array("\x08" => "\\b", "\x0c" => "\\f", "\r" => "\\r", "\n" => "\\n", "\t" => "\\t", "\x00" => "\\0", "'" => "\\'", "\\" => "\\\\"));
        }
        if ($out_charset === "UTF-8") {
            if ($append_bom && \preg_match("{[\\x80-\\xFF]}", $s)) {
                return "\xEF\xBB\xBF" . $s;
            }
            return $s;
        } else {
            return \iconv("UTF-8", $out_charset, $s);
        }
    }

    public static function tsv_encode_row($row, $out_charset = "UTF-8", $in_charset = "UTF-8", $append_bom = 0, $null = "\\N")
    {
        if ($out_charset === "UTF-8" || $out_charset === NULL || isset(self::$utf8_map[$out_charset])) {
            $out_charset = "UTF-8";
        }
        if ($in_charset === "UTF-8" || $in_charset === NULL || isset(self::$utf8_map[$in_charset])) {
            $in_charset = "UTF-8";
        }
        $need_iconv = $in_charset !== "UTF-8";
        $s = "";
        foreach ($row as $k => $str) {
            if ($k != 0) {
                $s .= "\t";
            }
            if ($str === NULL) {
                $s .= $null;
            } else {
                $s .= \strtr($need_iconv ? \iconv($in_charset, "UTF-8", $str) : (string) $str, array("\x08" => "\\b", "\x0c" => "\\f", "\r" => "\\r", "\n" => "\\n", "\t" => "\\t", "\x00" => "\\0", "'" => "\\'", "\\" => "\\\\"));
            }
        }
        if ($out_charset === "UTF-8") {
            if ($append_bom && \preg_match("{[\\x80-\\xFF]}", $s)) {
                return "\xEF\xBB\xBF" . $s;
            }
            return $s;
        } else {
            return \iconv("UTF-8", $out_charset, $s);
        }
    }

    public static function tsv_encode_table($data, $out_charset = "UTF-8", $in_charset = "UTF-8", $append_bom = 0, $null = "\\N", $newline = "\n")
    {
        if ($out_charset === "UTF-8" || $out_charset === NULL || isset(self::$utf8_map[$out_charset])) {
            $out_charset = "UTF-8";
        }
        if ($in_charset === "UTF-8" || $in_charset === NULL || isset(self::$utf8_map[$in_charset])) {
            $in_charset = "UTF-8";
        }
        $need_iconv = $in_charset !== "UTF-8";
        $s = "";
        foreach ($data as $row) {
            foreach ($row as $k => $str) {
                if ($k != 0) {
                    $s .= "\t";
                }
                if ($str === NULL) {
                    $s .= $null;
                } else {
                    $s .= \strtr($need_iconv ? \iconv($in_charset, "UTF-8", $str) : (string) $str, array("\x08" => "\\b", "\x0c" => "\\f", "\r" => "\\r", "\n" => "\\n", "\t" => "\\t", "\x00" => "\\0", "'" => "\\'", "\\" => "\\\\"));
                }
            }
            $s .= $newline;
        }
        if ($out_charset === "UTF-8") {
            if ($append_bom && \preg_match("{[\\x80-\\xFF]}", $s)) {
                return "\xEF\xBB\xBF" . $s;
            }
            return $s;
        } else {
            return \iconv("UTF-8", $out_charset, $s);
        }
    }

    public static function tsv_decode_stream($stream, $close_stream, $skip_lines = 0, $in_charset = "UTF-8", $out_charset = "UTF-8", $remove_bom = 0)
    {
        static $map = array(0 => 0, 1 => 1, 2 => 2, 3 => 3, 4 => 4, 5 => 5, 6 => 6, 7 => 7, 8 => 8, 9 => 9, "a" => 10, "A" => 10, "b" => 11, "B" => 11, "c" => 12, "C" => 12, "d" => 13, "D" => 13, "e" => 14, "E" => 14, "f" => 15, "F" => 15);
        static $map2 = array("b" => "\x08", "f" => "\x0c", "r" => "\r", "n" => "\n", "t" => "\t", "0" => "\x00", "'" => "'", "\\" => "\\", "a" => "\x07", "v" => "\x0b");
        if ($out_charset === "UTF-8" || $out_charset === NULL || isset(self::$utf8_map[$out_charset])) {
            $out_charset = "UTF-8";
        }
        if ($in_charset === "UTF-8" || $in_charset === NULL || isset(self::$utf8_map[$in_charset])) {
            $in_charset = "UTF-8";
        }
        $detect_bom = $remove_bom && $in_charset === "UTF-8" ? "\xEF" : NULL;
        $need_iconv = $out_charset !== "UTF-8";
        $filter = NULL;
        if ($in_charset !== "UTF-8") {
            $filter = \stream_filter_append($stream, "convert.iconv.$in_charset.utf-8", STREAM_FILTER_READ);
        }
        if (($c = \fgetc($stream)) === false) {
            if ($filter) {
                \stream_filter_remove($filter);
            }
            if ($close_stream) {
                \fclose($stream);
            }
            return;
        }
        $row = array();
        $s = "";
        $state = 0;
        $oct = null; //suppress warnning
        for (;;) {
            switch ($state) {
                case 0:
                    switch ($c) {
                        case "":
                            $row[] = $need_iconv && $s !== NULL ? \iconv("UTF-8", $out_charset, $s) : $s;
                            if ($skip_lines > 0) {
                                $skip_lines--;
                            } else {
                                (yield $row);
                            }
                            break 3; //END no \n but END
                        case "\\":
                            $state = 1;
                            ($c = \fgetc($stream)) !== false or $c = "";
                            break;
                        case "\r": //过滤掉后面N个\n
                            for (;;) {
                                ($c = \fgetc($stream)) !== false or $c = "";
                                if ($c !== "\n") {
                                    break;
                                }
                            }
                            $row[] = $need_iconv && $s !== NULL ? \iconv("UTF-8", $out_charset, $s) : $s;
                            $s = "";
                            if ($skip_lines > 0) {
                                $skip_lines--;
                            } else {
                                (yield $row);
                            }
                            $row = array();
                            if ($c === "") {
                                break 3; //NORMAL END
                            }
                            break;
                        case "\t":
                            $row[] = $need_iconv && $s !== NULL ? \iconv("UTF-8", $out_charset, $s) : $s;
                            $s = "";
                            ($c = \fgetc($stream)) !== false or $c = "";
                            break;
                        case "\n": //过滤掉后面N个\r
                            for (;;) {
                                ($c = \fgetc($stream)) !== false or $c = "";
                                if ($c !== "\r") {
                                    break;
                                }
                            }
                            $row[] = $need_iconv && $s !== NULL ? \iconv("UTF-8", $out_charset, $s) : $s;
                            $s = "";
                            if ($skip_lines > 0) {
                                $skip_lines--;
                            } else {
                                (yield $row);
                            }
                            $row = array();
                            if ($c === "") {
                                break 3; //NORMAL END
                            }
                            break;
                        default:
                            if ($detect_bom && $c === $detect_bom) {
                                if ($detect_bom === "\xEF") {
                                    $detect_bom = "\xBB";
                                } elseif ($detect_bom === "\xBB") {
                                    $detect_bom = "\xBF";
                                } else {
                                    $detect_bom = NULL;
                                    $s = "";
                                    ($c = \fgetc($stream)) !== false or $c = "";
                                    if ($c === "") {
                                        break 3;
                                    }
                                    break;
                                }
                            } else {
                                $detect_bom = NULL;
                            }
                            $s .= $c;
                            ($c = \fgetc($stream)) !== false or $c = "";
                    }
                    break;
                case 1:
                    if ($c === "") {
                        $row[] = $need_iconv && $s !== NULL ? \iconv("UTF-8", $out_charset, $s) : $s;
                        if ($skip_lines > 0) {
                            $skip_lines--;
                        } else {
                            (yield $row);
                        }
                        \trigger_error("last char is uncomplete \\");
                        break 2; //END last char is uncomplete \
                    }
                    if (isset($map2[$c])) {
                        $s .= $map2[$c];
                        $state = 0;
                        ($c = \fgetc($stream)) !== false or $c = "";
                    } else if ($c == "x") {
                        $state = 2;
                        ($c = \fgetc($stream)) !== false or $c = "";
                    } else if ($c == "N") {
                        if ($s !== "") { //TODO check it \N in string,what will happen
                            $s .= $c;
                            $state = 0;
                            ($c = \fgetc($stream)) !== false or $c = "";
                        } else {
                            ($c = \fgetc($stream)) !== false or $c = "";
                            switch ($c) {
                                case "":
                                    $row[] = null;
                                    if ($skip_lines > 0) {
                                        $skip_lines--;
                                    } else {
                                        (yield $row);
                                    }
                                    break 3;
                                case "\r":
                                case "\n":
                                case "\t":
                                    $s = null;
                                    $state = 0;
                                    continue 3;
                            }
                            $s .= "N";
                            $state = 0;
                        }
                    } else {
                        $s .= $c;
                        $state = 0;
                        ($c = \fgetc($stream)) !== false or $c = "";
                    }
                    break;
                case 2:
                    if ($c === "") {
                        $row[] = $need_iconv && $s !== NULL ? \iconv("UTF-8", $out_charset, $s) : $s;
                        if ($skip_lines > 0) {
                            $skip_lines--;
                        } else {
                            (yield $row);
                        }
                        \trigger_error("last char is uncomplete \\x");
                        break 2; //END last char is uncomplete \x
                    }
                    $oct = $map[$c] * 16; //warning if not 0-9A-Fa-f
                    $state = 3;
                    ($c = \fgetc($stream)) !== false or $c = "";
                    break;
                case 3:
                    if ($c === "") {
                        $row[] = $need_iconv && $s !== NULL ? \iconv("UTF-8", $out_charset, $s) : $s;
                        if ($skip_lines > 0) {
                            $skip_lines--;
                        } else {
                            (yield $row);
                        }
                        \trigger_error("last char is uncomplete \\xN");
                        break 2; //END last char is uncomplete \xN
                    }
                    $s .= \chr($oct + $map[$c]); //warning if not 0-9A-Fa-f
                    $state = 0;
                    ($c = \fgetc($stream)) !== false or $c = "";
                    break;
                default:
                    throw new \ErrorException("BUG");
            }
        }
        if ($filter) {
            \stream_filter_remove($filter);
        }
        if ($close_stream) {
            \fclose($stream);
        }
        //jetbrain warning return;
    }

    public static function tsv_fast_decode_stream($stream, $close_stream, $skip_lines = 0, $in_charset = "UTF-8", $out_charset = "UTF-8", $remove_bom = 0)
    {
        $detect_bom = $remove_bom && $in_charset === "UTF-8" ? "\xEF" : NULL;
        $need_iconv = $out_charset !== "UTF-8";
        $filter = NULL;
        if ($in_charset !== "UTF-8") {
            $filter = \stream_filter_append($stream, "convert.iconv.$in_charset.utf-8", STREAM_FILTER_READ);
        }
        $s = \fgets($stream);
        if ($s === false || ($detect_bom && $s === "\xEF\xBB\xBF")) {
            if ($filter) {
                \stream_filter_remove($filter);
            }
            if ($close_stream) {
                \fclose($stream);
            }
            return;
        }
        if ($detect_bom && \substr($s, 0, 3) === "\xEF\xBB\xBF") {
            $s = \substr($s, 3);
        }
        for (;; $s = \fgets($stream)) {
            if ($s === false) {
                if ($filter) {
                    \stream_filter_remove($filter);
                }
                if ($close_stream) {
                    \fclose($stream);
                }
                return;
            }
            $s = \rtrim($s, "\r\n");
            if ($s === "") {
                continue;
            }
            $row = array();
            foreach (\explode("\t", $s) as $str) {
                $row[] = $str === "\N" ? null : ($need_iconv ? \iconv("UTF-8", $out_charset, \strtr($str, array("\\b" => "\x08", "\\f" => "\x0c", "\\r" => "\r", "\\n" => "\n", "\\t" => "\t", "\0" => "\x00", "\\'" => "'", "\\\\" => "\\"))) : \strtr($str, array("\\b" => "\x08", "\\f" => "\x0c", "\\r" => "\r", "\\n" => "\n", "\\t" => "\t", "\0" => "\x00", "\\'" => "'", "\\\\" => "\\")));
            }
            if ($skip_lines > 0) {
                $skip_lines--;
            } else {
                (yield $row);
            }
        }
    }


    //ONLY SUPPORT \b, \f, \r, \n, \t, \0, \', \\
    //NOT SUPPORT FOR SPEED => Parsing also supports the sequences \a, \v, and \xHH (hex escape sequences) and any \c sequences, where c is any character (these sequences are converted to c). Thus, reading data supports formats where a line feed can be written as \n or \, or as a line feed. 
    //about 8x faster than tsv_decode_str
    public static function tsv_fast_decode_str($str, $skip_lines = 0, $in_charset = "UTF-8", $out_charset = "UTF-8", $remove_bom = 0)
    {
        if ($in_charset !== "UTF-8") {
            $str = \iconv($in_charset, "UTF-8", $str);
        } elseif ($remove_bom) {
            if (\substr($str, 0, 3) === "\xEF\xBB\xBF") {
                $str = \substr($str, 3);
            }
        }
        $need_iconv = $out_charset !== "UTF-8";
        if ($str === "") {
            return array();
        }
        $ss = array();
        foreach (\explode("\n", \trim($str, "\n")) as $r) {
            //if($r===""){
            //    continue;
            //}
            $row = array();
            foreach (\explode("\t", $r) as $str) {
                $row[] = $str === "\N" ? null : ($need_iconv ? \iconv("UTF-8", $out_charset, \strtr($str, array("\\b" => "\x08", "\\f" => "\x0c", "\\r" => "\r", "\\n" => "\n", "\\t" => "\t", "\0" => "\x00", "\\'" => "'", "\\\\" => "\\"))) : \strtr($str, array("\\b" => "\x08", "\\f" => "\x0c", "\\r" => "\r", "\\n" => "\n", "\\t" => "\t", "\0" => "\x00", "\\'" => "'", "\\\\" => "\\")));
            }
            if ($skip_lines > 0) {
                $skip_lines--;
            } else {
                $ss[] = $row;
            }
        }
        return $ss;
    }

    public static function tsv_decode_str($str, $skip_lines = 0, $in_charset = "UTF-8", $out_charset = "UTF-8", $remove_bom = 0)
    {
        static $map = array(0 => 0, 1 => 1, 2 => 2, 3 => 3, 4 => 4, 5 => 5, 6 => 6, 7 => 7, 8 => 8, 9 => 9, "a" => 10, "A" => 10, "b" => 11, "B" => 11, "c" => 12, "C" => 12, "d" => 13, "D" => 13, "e" => 14, "E" => 14, "f" => 15, "F" => 15);
        static $map2 = array("b" => "\x08", "f" => "\x0c", "r" => "\r", "n" => "\n", "t" => "\t", "0" => "\x00", "'" => "'", "\\" => "\\", "a" => "\x07", "v" => "\x0b");
        if ($str === "") {
            return array();
        }
        if ($out_charset === "UTF-8" || $out_charset === NULL || isset(self::$utf8_map[$out_charset])) {
            $out_charset = "UTF-8";
        }
        if ($in_charset === "UTF-8" || $in_charset === NULL || isset(self::$utf8_map[$in_charset])) {
            $in_charset = "UTF-8";
        }
        $detect_bom = $remove_bom && $in_charset === "UTF-8" ? "\xEF" : NULL;
        $need_iconv = $out_charset !== "UTF-8";
        if ($in_charset !== "UTF-8") {
            $str = \iconv($in_charset, "UTF-8", $str);
        }
        $data = $row = array();
        $s = "";
        $state = 0;
        $oct = null; //suppress warnning
        $c = @$str[$index = 0];
        for (;;) {
            switch ($state) {
                case 0:
                    switch ($c) {
                        case "":
                            $row[] = $need_iconv && $s !== NULL ? \iconv("UTF-8", $out_charset, $s) : $s;
                            if ($skip_lines > 0) {
                                $skip_lines--;
                            } else {
                                $data[] = $row;
                            }
                            break 3; //END no \n but END
                        case "\\":
                            $state = 1;
                            $c = @$str[++$index];
                            break;
                        case "\r": //过滤掉后面N个\n
                            for (;;) {
                                $c = @$str[++$index];
                                if ($c !== "\n") {
                                    break;
                                }
                            }
                            $row[] = $need_iconv && $s !== NULL ? \iconv("UTF-8", $out_charset, $s) : $s;
                            $s = "";
                            if ($skip_lines > 0) {
                                $skip_lines--;
                            } else {
                                $data[] = $row;
                            }
                            $row = array();
                            if ($c === "") {
                                break 3; //NORMAL END
                            }
                            break;
                        case "\t":
                            $row[] = $need_iconv && $s !== NULL ? \iconv("UTF-8", $out_charset, $s) : $s;
                            $s = "";
                            $c = @$str[++$index];
                            break;
                        case "\n": //过滤掉后面N个\r
                            for (;;) {
                                $c = @$str[++$index];
                                if ($c !== "\r") {
                                    break;
                                }
                            }
                            $row[] = $need_iconv && $s !== NULL ? \iconv("UTF-8", $out_charset, $s) : $s;
                            $s = "";
                            if ($skip_lines > 0) {
                                $skip_lines--;
                            } else {
                                $data[] = $row;
                            }
                            $row = array();
                            if ($c === "") {
                                break 3; //NORMAL END
                            }
                            break;
                        default:
                            if ($detect_bom && $c === $detect_bom) {
                                if ($detect_bom === "\xEF") {
                                    $detect_bom = "\xBB";
                                } elseif ($detect_bom === "\xBB") {
                                    $detect_bom = "\xBF";
                                } else {
                                    $detect_bom = NULL;
                                    $s = "";
                                    $c = @$str[++$index];
                                    if ($c === "") {
                                        break 3;
                                    }
                                    break;
                                }
                            } else {
                                $detect_bom = NULL;
                            }
                            $s .= $c;
                            $c = @$str[++$index];
                    }
                    break;
                case 1:
                    if ($c === "") {
                        $row[] = $need_iconv && $s !== NULL ? \iconv("UTF-8", $out_charset, $s) : $s;
                        if ($skip_lines > 0) {
                            $skip_lines--;
                        } else {
                            $data[] = $row;
                        }
                        \trigger_error("last char is uncomplete \\");
                        break 2; //END last char is uncomplete \
                    }
                    if (isset($map2[$c])) {
                        $s .= $map2[$c];
                        $state = 0;
                        $c = @$str[++$index];
                    } else if ($c == "x") {
                        $state = 2;
                        $c = @$str[++$index];
                    } else if ($c == "N") {
                        if ($s !== "") {
                            \trigger_error("found \\N in part of string,it's not a normal way");
                            $s .= $c;
                            $state = 0;
                            $c = @$str[++$index];
                        } else {
                            $c = @$str[++$index];
                            switch ($c) {
                                case "":
                                    $row[] = null;
                                    if ($skip_lines > 0) {
                                        $skip_lines--;
                                    } else {
                                        $data[] = $row;
                                    }
                                    break 3;
                                case "\r":
                                case "\n":
                                case "\t":
                                    $s = null;
                                    $state = 0;
                                    continue 3;
                            }
                            \trigger_error("found \\N in part of string,it's not a normal way");
                            $s .= "N";
                            $state = 0;
                        }
                    } else {
                        $s .= $c;
                        $state = 0;
                        $c = @$str[++$index];
                    }
                    break;
                case 2:
                    if ($c === "") {
                        $row[] = $need_iconv && $s !== NULL ? \iconv("UTF-8", $out_charset, $s) : $s;
                        if ($skip_lines > 0) {
                            $skip_lines--;
                        } else {
                            $data[] = $row;
                        }
                        \trigger_error("last char is uncomplete \\x");
                        break 2; //END last char is uncomplete \x
                    }
                    $oct = $map[$c] * 16; //warning if not 0-9A-Fa-f
                    $state = 3;
                    $c = @$str[++$index];
                    break;
                case 3:
                    if ($c === "") {
                        $row[] = $need_iconv && $s !== NULL ? \iconv("UTF-8", $out_charset, $s) : $s;
                        if ($skip_lines > 0) {
                            $skip_lines--;
                        } else {
                            $data[] = $row;
                        }
                        \trigger_error("last char is uncomplete \\xN");
                        break 2; //END last char is uncomplete \xN
                    }
                    $s .= \chr($oct + $map[$c]); //warning if not 0-9A-Fa-f
                    $state = 0;
                    $c = @$str[++$index];
                    break;
                default:
                    throw new \ErrorException("BUG");
            }
        }
        return $data;
    }
}
