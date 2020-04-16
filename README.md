# GoodCodec
fast csv / tsv / mysql quote encoder and decoder
mysql generate sql with bind param
complete tested for clickhouse / mysql use

# CSV

[RFC4180](https://tools.ietf.org/html/rfc4180)

[MySQL load-data](https://dev.mysql.com/doc/refman/8.0/en/load-data.html) [MySQL select-into](https://dev.mysql.com/doc/refman/8.0/en/select-into.html)

[ClickHouse Input and Output Formats](https://clickhouse.tech/docs/en/interfaces/formats/#csv)

[BOM](http://www.unicode.org/faq/utf_bom.html) 


| |Excel Import|Excel Export|
|----:|:-----|:----|
|DELIMITER|COMMA(,) |COMMA(,) |
|LINEBREAK|CRLF CR LF|CRLF|
|ENCLOSURE|DQUOTE(") |DQUOTE(") noquote as possible|
|ESCAPE|none|none|
|NULL| -| -|
|BEGIN & END BLANK| as is | as is |
|ENCODING|SYSTEM(CP936,...) or UTF8-BOM| SYSTEM(CP936,...) |

| |ClickHouse Import|ClickHouse Export|MySQL|
|----:|:-----|:----|:----|
|DELIMITER|COMMA(,) |COMMA(,)<br>format_csv_delimiter|config|
|LINEBREAK|CRLF CR LF|LF|config|
|ENCLOSURE|DQUOTE(") |DQUOTE(") quote as possible|config|
|ESCAPE|none|none|config|
|NULL|\N or NULL or an empty unquoted string<br>input_format_csv_unquoted_null_literal_as_null | \N |\N or NULL|
|BEGIN & END BLANK| trim |  |as is|
|ENCODING|byte|byte| |

| | Excel |MySQL|ClickHouse|PHP|
|----|-----|----|-----|-----|
|DELIMITER|COMMA(,) |config|COMMA(,) |config|
|LINEBREAK|CRLF(\r\n)|config|CRLF(\r\n) |SYSTEM PHP_EOL<br>auto_detect_line_endings|
|ENCLOSURE|DQUOTE(") | |DQUOTE(")|DQUOTE(") |
|QUOTE| noquote as possible | | string/date->quote<br>number->noquote | \\x20 \\t \\r \\n \\" \\\\ , |
|ESCAPE|none| |none |BACKSLASH(\\) |
|NULL| -- | \N, NULL | \N, NULL,<br>  empty string without quote| -- |
|BEGIN & END BLANK| as is | as is | trim | as is |
|ENCODING|SYSTEM(CP936,...) <br> or UTF8-BOM| UTF-8 | UTF-8 | SYSTEM,<br> setlocale(LC_CTYPE,"C") |
