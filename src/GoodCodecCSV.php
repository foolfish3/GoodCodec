<?php

namespace GoodCodec;

class GoodCodecCSV
{

    protected static $utf8_map = array(
        "utf-8" => 1, "Utf-8" => 1, "uTf-8" => 1, "UTf-8" => 1,
        "utF-8" => 1, "UtF-8" => 1, "uTF-8" => 1, "UTF-8" => 1,
    );

    const BOM = "\xEF\xBB\xBF";

    //$force_quote 0 try to noquote everything but for clickhouse import compatible
    //$force_quote 1 quote everything
    //$force_quote 2 try to noquote everything,same as excel
    public static function csv_encode_str($str, $out_charset = "UTF-8", $in_charset = "UTF-8", $append_bom = 0, $null = "\\N", $delimiter = ",", $enclosure = "\"", $force_quote = 0)
    {
        if ($out_charset === "UTF-8" || $out_charset === NULL || isset(self::$utf8_map[$out_charset])) {
            $out_charset = "UTF-8";
        }
        if ($in_charset === "UTF-8" || $in_charset === NULL || isset(self::$utf8_map[$in_charset])) {
            $in_charset = "UTF-8";
        }
        $append_bom = isset($append_bom) ? $append_bom : 0;
        $null = isset($null) ? $null : "\\N";
        $delimiter = isset($delimiter) ? $delimiter : ",";
        $enclosure = isset($enclosure) ? $enclosure : "\"";
        $force_quote = isset($force_quote) ? $force_quote : 0;

        $need_iconv = $in_charset !== "UTF-8";
        if ($str === NULL) {
            return $null;
        } elseif ($str === "") {
            $s = $force_quote == 2 ? "" : "$enclosure$enclosure";
        } else {
            //jetbrain warning $quote = 0;
            if ($force_quote == 1) {
                $quote = 1;
                //} elseif (!\is_string($str)) {
            } elseif ($force_quote == 0) { //try to noquote everything but for clickhouse import compatible
                $str = $need_iconv ? \iconv($in_charset, "UTF-8", $str) : $str;
                $quote = \strpbrk($str, " \t\r\n\\'\",$enclosure$delimiter") !== false;
            } elseif ($force_quote == 2) { //try to noquote everything,same as excel
                $str = $need_iconv ? \iconv($in_charset, "UTF-8", $str) : $str;
                $quote = \strpbrk($str, "\r\n$enclosure$delimiter") !== false;
            } else {
                throw new \LogicException("BUG");
            }
            $s = $quote ? $enclosure . \strtr($str, array($enclosure => $enclosure . $enclosure)) . $enclosure : $str;
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

    public static function csv_encode_row($row, $out_charset = "UTF-8", $in_charset = "UTF-8", $append_bom = 0, $null = "\\N", $delimiter = ",", $enclosure = "\"", $force_quote = 0)
    {
        if ($out_charset === "UTF-8" || $out_charset === NULL || isset(self::$utf8_map[$out_charset])) {
            $out_charset = "UTF-8";
        }
        if ($in_charset === "UTF-8" || $in_charset === NULL || isset(self::$utf8_map[$in_charset])) {
            $in_charset = "UTF-8";
        }
        $append_bom = isset($append_bom) ? $append_bom : 0;
        $null = isset($null) ? $null : "\\N";
        $delimiter = isset($delimiter) ? $delimiter : ",";
        $enclosure = isset($enclosure) ? $enclosure : "\"";
        $force_quote = isset($force_quote) ? $force_quote : 0;
        $s = "";
        $idx = -1;
        foreach ($row as $str) {
            if (++$idx !== 0) {
                $s .= $delimiter;
            }
            $s .= self::csv_encode_str($str, "UTF-8", $in_charset, 0, $null, $delimiter, $enclosure, $force_quote);
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

    public static function csv_encode_table_excel($data, $out_charset = "UTF-8")
    {
        return self::csv_encode_table($data, $out_charset, "UTF-8", 1, "", ",", "\"", 2, "\r\n");
    }

    public static function csv_encode_table($data, $out_charset = "UTF-8", $in_charset = "UTF-8", $append_bom = 0, $null = "\\N", $delimiter = ",", $enclosure = "\"", $force_quote = 0, $newline = "\n")
    {
        if ($out_charset === "UTF-8" || $out_charset === NULL || isset(self::$utf8_map[$out_charset])) {
            $out_charset = "UTF-8";
        }
        if ($in_charset === "UTF-8" || $in_charset === NULL || isset(self::$utf8_map[$in_charset])) {
            $in_charset = "UTF-8";
        }
        $append_bom = isset($append_bom) ? $append_bom : 0;
        $null = isset($null) ? $null : "\\N";
        $delimiter = isset($delimiter) ? $delimiter : ",";
        $enclosure = isset($enclosure) ? $enclosure : "\"";
        $force_quote = isset($force_quote) ? $force_quote : 0;
        $newline = isset($newline) ? $newline : "\n";
        $s = "";
        foreach ($data as $row) {
            $s .= self::csv_encode_row($row, "UTF-8", $in_charset, 0, $null, $delimiter, $enclosure, $force_quote);
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

    public static function csv_decode_stream($stream, $close_stream, $skip_lines = 0, $in_charset = "UTF-8", $out_charset = "UTF-8", $remove_bom = 0, $null = array("\N"), $delimiter = ",", $enclosure = "\"")
    {
        if ($out_charset === "UTF-8" || $out_charset === NULL || isset(self::$utf8_map[$out_charset])) {
            $out_charset = "UTF-8";
        }
        if ($in_charset === "UTF-8" || $in_charset === NULL || isset(self::$utf8_map[$in_charset])) {
            $in_charset = "UTF-8";
        }
        $remove_bom = isset($remove_bom) ? $remove_bom : 0;
        if ($null === NULL) {
            $null = array("\N");
        } elseif (\is_scalar($null)) {
            $null = array($null);
        }
        $delimiter = isset($delimiter) ? $delimiter : ",";
        $enclosure = isset($enclosure) ? $enclosure : "\"";
        $detect_bom = $remove_bom && $in_charset === "UTF-8" ? "\xEF" : NULL;
        $need_iconv = $out_charset !== "UTF-8";
        //convert.iconv.<input-encoding>.<output-encoding>
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
        $map3 = array();
        foreach ($null as $v) {
            $map3[$v] = 1;
        }
        $s = "";
        $row = array();
        $map = array("" => 0, $delimiter => 1, $enclosure => 2, "\r" => 4, "\n" => 5);
        $map2 = array("" => 0, $delimiter => 1, "\r" => 4, "\n" => 5);
        for (; ;) {
            if (isset($map[$c])) {
                switch ($map[$c]) {
                    case 0:
                        $row[] = $need_iconv && $s !== NULL ? \iconv("UTF-8", $out_charset, $s) : $s;
                        // jetbrain warning
						// if ($skip_lines > 0) {
                        //     $skip_lines--;
                        // } else {
                        //    (yield $row);
                        // }
                        if ($skip_lines === 0) {
                            (yield $row);
                        }
                        break 2;
                    case 1: //,
                        $row[] = $need_iconv && $s !== NULL ? \iconv("UTF-8", $out_charset, $s) : $s;
                        $s = "";
                        ($c = \fgetc($stream)) !== false or $c = "";
                        continue 2;
                    case 2: //"
                        for (; ;) {
                            ($c = \fgetc($stream)) !== false or $c = "";
                            if (isset($map[$c])) {
                                switch ($map[$c]) {
                                    case 0:
                                        \trigger_error("at the end,still cannot find matched enclosure $enclosure ");
                                        continue 4; //at the end,still cannot find matched $enclosure
                                    case 2:
                                        ($c = \fgetc($stream)) !== false or $c = "";
                                        if ($c === $enclosure) {
                                            $s .= $enclosure;
                                        } else {
                                            for (; ;) {
                                                if (isset($map2[$c])) {
                                                    break;
                                                }
                                                $s .= $c;
                                                ($c = \fgetc($stream)) !== false or $c = "";
                                            }
                                            continue 4;
                                        }
                                        continue 2;
                                }
                            }
                            $s .= $c;
                        }
                    //jetbrain warning throw new \LogicException("BUG");
                    case 4:
                        for (; ;) {
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
                            break 2; //=== END ===
                        }
                        continue 2;
                    case 5:
                        for (; ;) {
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
                            break 2; //=== END ===
                        }
                        continue 2;
                }
            }
            for (; ;) {
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
                            break 2;
                        }
                        continue 2;
                    }
                } else {
                    $detect_bom = NULL;
                }
                if (isset($map2[$c])) {
                    break;
                }
                $s .= $c;
                ($c = \fgetc($stream)) !== false or $c = "";
            }
            if (isset($map3[$s])) {
                $s = NULL;
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

    public static function csv_decode_str($str, $skip_lines = 0, $in_charset = "UTF-8", $out_charset = "UTF-8", $remove_bom = 0, $null = array("\\N"), $delimiter = ",", $enclosure = "\"")
    {
        if ($str === "") {
            return array();
        }
        if ($out_charset === "UTF-8" || $out_charset === NULL || isset(self::$utf8_map[$out_charset])) {
            $out_charset = "UTF-8";
        }
        if ($in_charset === "UTF-8" || $in_charset === NULL || isset(self::$utf8_map[$in_charset])) {
            $in_charset = "UTF-8";
        }
        $remove_bom = isset($remove_bom) ? $remove_bom : 0;
        if ($null === NULL) {
            $null = array("\N");
        } elseif (\is_scalar($null)) {
            $null = array($null);
        }
        $delimiter = isset($delimiter) ? $delimiter : ",";
        $enclosure = isset($enclosure) ? $enclosure : "\"";
        $detect_bom = $remove_bom && $in_charset === "UTF-8" ? "\xEF" : NULL;
        $need_iconv = $out_charset !== "UTF-8";
        if ($in_charset !== "UTF-8") {
            $str = \iconv($in_charset, "UTF-8", $str);
        }
        $map3 = array();
        foreach ($null as $v) {
            $map3[$v] = 1;
        }
        $index = 0;
        $s = "";
        $data = $row = array();
        $map = array("" => 0, $delimiter => 1, $enclosure => 2, "\r" => 4, "\n" => 5);
        $map2 = array("" => 0, $delimiter => 1, "\r" => 4, "\n" => 5);
        for (; ;) {
            $c = @$str[$index];
            if (isset($map[$c])) {
                switch ($map[$c]) {
                    case 0:
                        $row[] = $need_iconv && $s !== NULL ? \iconv("UTF-8", $out_charset, $s) : $s;
                        // jetbrain warning
                        // if ($skip_lines > 0) {
                        //    $skip_lines--;
                        // } else {
                        //    $data[] = $row;
                        // }
                        if ($skip_lines === 0) {
                            $data[] = $row;
                        }
                        break 2;
                    case 1:
                        $row[] = $need_iconv && $s !== NULL ? \iconv("UTF-8", $out_charset, $s) : $s;
                        $s = "";
                        $index++;
                        continue 2;
                    case 2:
                        for (; ;) {
                            $c = @$str[++$index];
                            if (isset($map[$c])) {
                                switch ($map[$c]) {
                                    case 0:
                                        \trigger_error("at the end,still cannot find matched enclosure $enclosure ");
                                        continue 4; //at the end,still cannot find matched $enclosure
                                    case 2:
                                        $c = @$str[++$index];
                                        if ($c === $enclosure) {
                                            $s .= $enclosure;
                                        } else {
                                            for (; ;) {
                                                if (isset($map2[$c])) {
                                                    break;
                                                }
                                                $s .= $c;
                                                $c = @$str[++$index];
                                            }
                                            continue 4;
                                        }
                                        continue 2;
                                }
                            }
                            $s .= $c;
                        }
                    //jetbrain warning throw new \LogicException("BUG");
                    case 4:
                        for (; ;) {
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
                            break 2; //=== END ===
                        }
                        continue 2;
                    case 5:
                        for (; ;) {
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
                            break 2; //=== END ===
                        }
                        continue 2;
                }
            }
            for (; ;) {
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
                            break 2;
                        }
                        continue 2;
                    }
                } else {
                    $detect_bom = NULL;
                }
                if (isset($map2[$c])) {
                    break;
                }
                $s .= $c;
                $c = @$str[++$index];
            }
            if (isset($map3[$s])) {
                $s = NULL;
            }
        }
        return $data;
    }
}
