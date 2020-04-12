# GoodCodec
fast csv / tsv / mysql quote encoder and decoder
mysql generate sql with bind param
complete tested for clickhouse / mysql use

# CSV

[RFC4180](https://tools.ietf.org/html/rfc4180)

| | Excel |MySQL|ClickHouse|PHP|
|----|-----|----|-----|-----|
|DELIMITER|COMMA(,) | | | |
|LINEBREAK|CRLF(\r\n) | | | |
|ENCLOSURE|DQUOTE(") | | | |
|ESCAPE|NONE| | | |
|NULL| -- | \N, NULL | \N, NULL,<br>  empty string without quote| -- |
|BEGIN & END BLANK| as is | | trim | |
|ENCODING|SYSTEM(CP936,...) <br> or UTF8-BOM| UTF-8 | UTF-8 | SYSTEM,<br> setlocale(LC_CTYPE,"C") |
