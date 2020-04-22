# GoodCodec
fast csv / tsv / mysql quote encoder and decoder

mysql generate sql with bind param, generate values

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
|NULL|\N => NULL <br> NULL => 'NULL' <br> an empty unquoted string => DEFAULT VALUE <br> input_format_csv_unquoted_null_literal_as_null <br>input_format_defaults_for_omitted_fields| \N |\N or NULL|
|BEGIN & END BLANK| trim |  |as is|
|ENCODING|byte|byte|UTF-8|

## why not use php function fgetcsv fputcsv str_getcsv

1. must set locale with setlocale(LC_CTYPE,"C") or CP936 UTF-8, and country, so hard, and not easy
1. working with string must use fopen fputcsv fclose
1. bom support?
1. null value support? cannot distinguish quote or unquote value,it's important for null value
1. str_getcsv cannot line by line try str_getcsv("1,2,\r\n3,4\n\n\n\n\n1")?

## aim to do 

1. can import data from excel, MySQL, ClickHouse with default setting 
1. can output data to excel, MySQL, ClickHouse with their default setting
1. so ESCAPE is not support in CSV (consider TSV for MySQL import & export)
