# GoodCodec
fast csv / tsv / mysql quote encoder and decoder
mysql generate sql with bind param
complete tested for clickhouse / mysql use

# CSV

[RFC4180](https://tools.ietf.org/html/rfc4180)

| | Excel |MySQL|ClickHouse|PHP|
|----|-----|----|-----|-----|
|DELIMITER|COMMA(,) | | |COMMA(,)|
|LINEBREAK|CRLF(\r\n) | | |SYSTEM PHP_EOL|
|ENCLOSURE|DQUOTE(") | | |DQUOTE(") |
|QUOTE| noquote as possible | | string/date->quote<br>number->noquote | \\x20 \\t \\r \\n \\" \\\\ , |
|ESCAPE|none| | |BACKSLASH(\\) |
|NULL| -- | \N, NULL | \N, NULL,<br>  empty string without quote| -- |
|BEGIN & END BLANK| as is | as is | trim | as is |
|ENCODING|SYSTEM(CP936,...) <br> or UTF8-BOM| UTF-8 | UTF-8 | SYSTEM,<br> setlocale(LC_CTYPE,"C") |
