<?php

namespace GoodCodec;

class GoodCodec
{
    const UTF8 = "UTF-8";
    protected static $utf8_map = array(
        "utf-8" => 1, "Utf-8" => 1, "uTf-8" => 1, "UTf-8" => 1,
        "utF-8" => 1, "UtF-8" => 1, "uTF-8" => 1, "UTF-8" => 1,
    );

    public static function cut_to_pieces($itr, $converter, $size = NULL)
    {
        return GoodCodecSQL::cut_to_pieces($itr, $converter, $size);
    }

    public static function mysql_encode_str($str, $noquote = 0)
    {
        return GoodCodecSQL::mysql_encode_str($str, $noquote);
    }

    public static function mysql_encode_row($row, $noquote = 0)
    {
        return GoodCodecSQL::mysql_encode_row($row, $noquote);
    }

    public static function mysql_decode_str($str)
    {
        return GoodCodecSQL::mysql_decode_str($str);
    }

    public static function tsv_encode_str($str, $out_charset = "UTF-8", $in_charset = "UTF-8", $append_bom = 0, $null = "NULL")
    {
        return GoodCodecTSV::tsv_encode_str($str, $out_charset, $in_charset, $append_bom, $null);
    }

    public static function tsv_encode_row($row, $out_charset = "UTF-8", $in_charset = "UTF-8", $append_bom = 0, $null = "\\N")
    {
        return GoodCodecTSV::tsv_encode_row($row, $out_charset, $in_charset, $append_bom, $null);
    }

    public static function tsv_encode_table($data, $out_charset = "UTF-8", $in_charset = "UTF-8", $append_bom = 0, $null = "\\N", $newline = "\n")
    {
        return GoodCodecTSV::tsv_encode_table($data, $out_charset, $in_charset, $append_bom, $null, $newline);
    }

    public static function tsv_decode_stream($stream, $close_stream, $skip_lines = 0, $in_charset = "UTF-8", $out_charset = "UTF-8", $remove_bom = 0)
    {
        foreach (GoodCodecTSV::tsv_decode_stream($stream, $close_stream, $skip_lines, $in_charset, $out_charset, $remove_bom) as $row) {
            (yield $row);
        }
    }

    public static function tsv_decode_str($str, $skip_lines = 0, $in_charset = "UTF-8", $out_charset = "UTF-8", $remove_bom = 0)
    {
        return GoodCodecTSV::tsv_decode_str($str, $skip_lines, $in_charset, $out_charset, $remove_bom);
    }

    public static function tsv_fast_decode_stream($stream, $close_stream, $skip_lines = 0, $in_charset = "UTF-8", $out_charset = "UTF-8", $remove_bom = 0)
    {
        foreach (GoodCodecTSV::tsv_fast_decode_stream($stream, $close_stream, $skip_lines, $in_charset, $out_charset, $remove_bom) as $row) {
            (yield $row);
        }
    }

    public static function tsv_fast_decode_str($str, $skip_lines = 0, $in_charset = "UTF-8", $out_charset = "UTF-8", $remove_bom = 0)
    {
        return GoodCodecTSV::tsv_fast_decode_str($str, $skip_lines, $in_charset, $out_charset, $remove_bom);
    }

    public static function csv_encode_str($str, $out_charset = "UTF-8", $in_charset = "UTF-8", $append_bom = 0, $null = "\\N", $delimiter = ",", $enclosure = "\"", $force_quote = 0)
    {
        return GoodCodecCSV::csv_encode_str($str, $out_charset, $in_charset, $append_bom, $null, $delimiter, $enclosure, $force_quote);
    }

    public static function csv_encode_row($row, $out_charset = "UTF-8", $in_charset = "UTF-8", $append_bom = 0, $null = "\\N", $delimiter = ",", $enclosure = "\"", $force_quote = 0)
    {
        return GoodCodecCSV::csv_encode_row($row, $out_charset, $in_charset, $append_bom, $null, $delimiter, $enclosure, $force_quote);
    }

    public static function csv_encode_table_excel($data, $out_charset = "UTF-8")
    {
        return GoodCodecCSV::csv_encode_table_excel($data, $out_charset);
    }

    public static function csv_encode_table($data, $out_charset = "UTF-8", $in_charset = "UTF-8", $append_bom = 0, $null = "\\N", $delimiter = ",", $enclosure = "\"", $force_quote = 0, $newline = "\n")
    {
        return GoodCodecCSV::csv_encode_table($data, $out_charset, $in_charset, $append_bom, $null, $delimiter, $enclosure, $force_quote, $newline);
    }

    public static function csv_decode_stream($stream, $close_stream, $skip_lines = 0, $in_charset = "UTF-8", $out_charset = "UTF-8", $remove_bom = 0, $null = array("\N"), $delimiter = ",", $enclosure = "\"")
    {
        foreach (GoodCodecCSV::csv_decode_stream($stream, $close_stream, $skip_lines, $in_charset, $out_charset, $remove_bom, $null, $delimiter, $enclosure) as $row) {
            (yield $row);
        }
    }

    public static function csv_decode_str($str, $skip_lines = 0, $in_charset = "UTF-8", $out_charset = "UTF-8", $remove_bom = 0, $null = array("\\N"), $delimiter = ",", $enclosure = "\"")
    {
        return GoodCodecCSV::csv_decode_str($str, $skip_lines, $in_charset, $out_charset, $remove_bom, $null, $delimiter, $enclosure);
    }

    public static function sql_token_get_all($str)
    {
        return GoodCodecSQL::sql_token_get_all($str);
    }

    public static function mysql_build_values($tmpl, $data = NULL)
    {
        return GoodCodecSQL::mysql_build_values($tmpl, $data);
    }

    public static function mysql_build_values_cut_to_pieces($tmpl, $itr, $size = NULL)
    {
        return GoodCodecSQL::mysql_build_values_cut_to_pieces($tmpl, $itr, $size);
    }

    public static function mysql_bind_param()
    {
        return call_user_func_array(array(__NAMESPACE__ . "\\GoodCodecSQL", "mysql_bind_param"), \func_get_args());
    }
}
