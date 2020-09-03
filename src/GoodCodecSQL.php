<?php

namespace GoodCodec;

class GoodCodecSQL
{

    protected static $utf8_map = array(
        "utf-8" => 1, "Utf-8" => 1, "uTf-8" => 1, "UTf-8" => 1,
        "utF-8" => 1, "UtF-8" => 1, "uTF-8" => 1, "UTF-8" => 1,
    );

    public static function mysql_encode_str($str, $noquote = 0)
    {
        if ($str === NULL) {
            return "NULL";
        } else {
            return $noquote ? $str : "'" . \strtr($str, array("\000" => "\\0", "\n" => "\\n", "\r" => "\\r", "\\" => "\\\\", "'" => "\\'", "\"" => "\\\"")) . "'";
        }
    }

    public static function mysql_encode_row($row, $noquote = 0)
    {
        if ($row === NULL || \is_scalar($row)) {
            return self::mysql_encode_str($row, $noquote);
        } elseif (!\is_array($row)) {
            throw new \ErrorException("param type error");
        }
        if (\count($row) == 0) {
            \trigger_error("no values in rows,this likely to cause an error in SQL in-list");
            return ""; //Notice: this likely to cause an error in SQL in-list
        }
        $s = "";
        foreach ($row as $k => $str) {
            if ($k != 0) {
                $s .= "," . self::mysql_encode_str($str, $noquote);
            } else {
                $s .= self::mysql_encode_str($str, $noquote);
            }
        }
        return $s;
    }

    public static function mysql_decode_str($str)
    {
        if (@$str[0] === "'") {
            return \strtr(substr($str, 1, -1), array("\\0" => "\0", "\\n" => "\n", "\\r" => "\r", "\\\\" => "\\", "\\'" => "'", "\\\"" => "\"", "\\Z" => "\032"));
        } else {
            return \strcasecmp($str, "NULL") ? $str : NULL;
        }
    }

    private static function get_next_splitter($last, $str, $index, $map)
    {
        if ($index >= \strlen($str)) {
            if (@$map[""] === true) {
                return array($last, $index);
            }
            return false;
        }
        $c = $str[$index];
        if (isset($map[$c])) {
            $r = self::get_next_splitter($last . $c, $str, $index + 1, $map[$c]);
            if ($r) {
                return $r;
            }
        }
        if ($last === "") {
            return false;
        } elseif (@$map[""] === true) {
            return array($last, $index);
        }
        return false;
    }

    private static function generate_splitter_map_set_map($str, &$map)
    {
        if ($str === "") {
            $map[""] = true;
        } else {
            if (!isset($map[$str[0]])) {
                $map[$str[0]] = array();
            }
            self::generate_splitter_map_set_map(\substr($str, 1), $map[$str[0]]);
        }
    }

    private static function generate_splitter_map($splitters)
    {
        $map = array();
        foreach ($splitters as $splitter) {
            self::generate_splitter_map_set_map($splitter, $map);
        }
        return $map;
    }

    private static function get_next_whitespace($str, $index)
    {
        $s = @$str[$index];
        for (;;) {
            $c = @$str[++$index];
            switch ($c) {
                case "\r":
                case "\n":
                case " ":
                case "\t":
                    $s .= $c;
                    break;
                default:
                    return array($s, $index);
            }
        }
        throw new \ErrorException("BUG");
    }

    private static function get_next_quote($str, $index)
    {
        $s = $quote = @$str[$index];
        for (;;) {
            $c = @$str[++$index];
            if ($c === "") {
                \trigger_error("uncomplete quote $quote");
                return array($s, $index);
            }
            $s .= $c;
            if ($c === "\\") {
                if ($quote === "'" || $quote === "\"") {
                    $c = @$str[++$index];
                    if ($c === "") {
                        \trigger_error("uncomplete quote $quote");
                        return array($s, $index);
                    }
                    $s .= $c;
                }
            } elseif ($c === $quote) {
                return array($s, $index + 1);
            }
        }
        throw new \ErrorException("BUG");
    }

    private static function get_next_single_line_comment($str, $index)
    {
        $c = @$str[++$index];
        if ($c === ">") { //"->",//clickhouse lamba / json operator
            $c = @$str[++$index];
            if ($c === ">") { // "->>"
                return array("->>", ++$index);
            } else {
                return array("->", $index);
            }
        } elseif ($c === "-") {
            for ($s = "--";;) {
                $c = @$str[++$index];
                if ($c === "\r" || $c === "\n" || $c === "") {
                    return array($s, $index);
                } else {
                    $s .= $c;
                }
            }
            throw new \ErrorException("BUG");
        } else {
            return array("-", $index);
        }
    }

    private static function get_next_multi_line_comment($str, $index)
    {
        $c = @$str[++$index];
        if ($c === "*") {
            for ($s = "/*", $last_c = "";; $last_c = $c) {
                $c = @$str[++$index];
                $s .= $c;
                if ($c === "/" && $last_c === "*") {
                    return array($s, $index + 1);
                }
                if ($c === "") {
                    return array($s, $index);
                }
            }
            throw new \ErrorException("BUG");
        } else {
            return array("/", $index);
        }
    }
    public static function sql_token_get_all($str)
    {
        static $map = NULL;
        static $cache_key;
        static $cache_value;
        if ($map === NULL) {
            $splitters = array(
                "<=>",
                "\r\n", "!=", ">=", "<=", "<>", "<<", ">>", ":=", "&&", "||", "@@",
                "[", "]", //clickhouse array
                ">", "<", "!", "^", "&", "|", "=", "(", ")", "\t", "\r", "\n", " ", "@", ":", "+", "*", "%", ";", ",", ".",
                "\\",
                //support above and: / /* */ -- - ->
            );
            $map = self::generate_splitter_map($splitters);
        }
        if ($str === "") {
            return array();
        } elseif (\is_int($str) || \is_float($str)) { //float number
            return (string) $str;
        }
        if ($cache_key === $str) {
            return $cache_value;
        }
        $ss = array();
        $s = "";
        for ($index = 0;;) {
            $c = @$str[$index];
            switch ($c) {
                case "'":
                case "\"":
                case "`":
                    if ($s !== "") {
                        $ss[] = $s;
                        $s = "";
                    }
                    list($r, $index) = self::get_next_quote($str, $index, $c);
                    $ss[] = $r;
                    break;
                case "-":
                    if ($s !== "") {
                        $ss[] = $s;
                        $s = "";
                    }
                    list($r, $index) = self::get_next_single_line_comment($str, $index);
                    $ss[] = $r;
                    break;
                case "/":
                    if ($s !== "") {
                        $ss[] = $s;
                        $s = "";
                    }
                    list($r, $index) = self::get_next_multi_line_comment($str, $index);
                    $ss[] = $r;
                    break;
                case "\r":
                case "\n":
                case " ":
                case "\t":
                    if ($s !== "") {
                        $ss[] = $s;
                        $s = "";
                    }
                    list($r, $index) = self::get_next_whitespace($str, $index);
                    $ss[] = $r;
                    break;
                case "": //END
                    if ($ss !== "") {
                        $ss[] = $s;
                        $s = "";
                    }
                    break 2;
                default:
                    if ($r = self::get_next_splitter("", $str, $index, $map)) {
                        if ($s !== "") {
                            $ss[] = $s;
                            $s = "";
                        }
                        $ss[] = $r[0];
                        $index = $r[1];
                    } else {
                        $s .= $c;
                        $index++;
                    }
            }
        }
        $cache_key = $str;
        $cache_value = $ss;
        return $ss;
    }

    public static function mysql_build_values($tmpl, $data = NULL)
    {
        if ($data === NULL) {
            list($data, $tmpl) = array($tmpl, $data);
        }
        $ss = array();
        if ($tmpl === NULL) {
            foreach ($data as $k => $row) {
                $ss[] = "(" . self::mysql_encode_row($row, 0) . ")";
            }
        } else {
            foreach ($data as $k => $row) {
                $ss[] = self::mysql_bind_param($tmpl, $row);
            }
        }
        return $ss;
    }

    public static function mysql_build_values_cut_to_pieces($tmpl, $itr, $size = NULL)
    {
        if ($size === NULL) {
            list($itr, $size) = array($tmpl, $itr);
        }
        if (\is_scalar($tmpl)) {
            $tmpl = self::sql_token_get_all($tmpl);
        }
        foreach (self::cut_to_pieces($itr, $size) as $data) {
            (yield self::mysql_build_values($tmpl, $data));
        }
    }

    public static function cut_to_pieces($itr, $converter, $size = NULL)
    {
        if ($size === NULL) {
            list($converter, $size) = array(NULL, $converter);
        }
        if($size === NULL && $converter === NULL && \is_array($itr)){
            if(\count($itr)>0){
                (yield $itr);
            }
            return;
        }
        $ar = array();
        foreach ($itr as $row) {
            $ar[] = $converter === NULL ? $row : $converter($row);
            if ($size!==NULL && \count($ar) >= $size) {
                (yield $ar);
                $ar = array();
            }
        }
        if (\count($ar) > 0) {
            (yield $ar);
        }
    }

    public static function mysql_bind_param_array($tmpl,$rows,$glue=","){
        $ss=array();
        foreach($rows as $row){
            $ss[]=self::mysql_bind_param($tmpl,$row);
        }
        return \implode($glue,$ss);
    }

    public static function mysql_bind_param()
    {
        $args = \func_get_args();
        $bind_param = array();
        if (\is_array($args[0])) {
            $args = $args[0];
        }
        if (isset($args[1]) && is_array($args[1])) {
            foreach ($args[1] as $k => &$v) {
                if (\preg_match("{^\\d+$}", $k)) {
                    $bind_param[$k + 1] = $v;
                } else {
                    $bind_param[$k] = $v;
                }
            }
        } else {
            for ($i = \count($args); $i-- > 1;) {
                $bind_param[$i] = $args[$i];
            }
        }
        $ss = self::sql_token_get_all($args[0]);
        //?
        //?s
        //?s?gogo
        for ($idx = 1, $i = 0; $i < \count($ss); $i++) {
            $s = $ss[$i];
            if (@$s[0] === "?") {
                if (!\preg_match("{^\\?([a-zA-Z0-9_]*)(?:\\?([a-zA-Z0-9_]+))?$}", $s, $m)) {
                    continue;
                }
                $k = isset($m[2]) ? $m[2] : $idx++;
                if (\key_exists($k, $bind_param)) {
                    $p = $bind_param[$k];
                } else {
                    if (isset($bind_param[""])) {
                        $p = \call_user_func($bind_param[""], $k);
                    } else {
                        throw new \ErrorException("cannot find key $k in param list");
                    }
                }
                switch ($m[1]) { //1 => 1, array() =>"",warning, array(1,2)=>1,2
                    case "": //raw no change
                        $ss[$i] = self::mysql_encode_row($p, 1);
                        break;
                    case "s":
                        $ss[$i] = self::mysql_encode_row($p, 0);
                        break;
                }
            }
        }
        return \implode($ss);
    }
}
