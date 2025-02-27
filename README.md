# GoodCodec

一个高性能的 PHP 编解码库，专门用于处理 CSV、TSV 和 MySQL 字符串的编码和解码。

## 特性

- 支持 CSV 文件的编码和解码，兼容 Excel 格式
- 支持 TSV 文件的编码和解码
- 支持 MySQL 字符串的编码和解码
- 支持字符集转换（UTF-8 和其他编码）
- 支持 BOM 头的处理
- 高性能实现，适用于大数据处理

## 安装

通过 Composer 安装：

```bash
composer require foolfish3/goodcodec
```

## 要求

- PHP >= 5.5

## 使用方法

### CSV 处理

```php
use GoodCodec\GoodCodec;

// 编码单个 CSV 字段
$encoded = GoodCodec::csv_encode_str("Hello, World", "UTF-8", "UTF-8", false, "\\N", ",", "\"", false);

// 编码一行 CSV 数据
$row = ["id" => 1, "name" => "John", "email" => "john@example.com"];
$encoded_row = GoodCodec::csv_encode_row($row, "UTF-8", "UTF-8", false, "\\N", ",", "\"", false);

// 编码整个 CSV 表格（Excel 兼容格式）
$data = [
    ["id", "name", "email"],
    [1, "John", "john@example.com"],
    [2, "Jane", "jane@example.com"]
];
$encoded_table = GoodCodec::csv_encode_table_excel($data, "UTF-8");

// 编码整个 CSV 表格（自定义格式）
$encoded_table = GoodCodec::csv_encode_table(
    $data,
    "UTF-8",      // 输出字符集
    "UTF-8",      // 输入字符集
    false,        // 是否添加 BOM
    "\\N",       // 空值替换
    ",",         // 分隔符
    "\"",       // 引号字符
    false,        // 是否强制引号
    "\n"         // 换行符
);

// 解码 CSV 字符串
$csv_string = "1,John,john@example.com\n2,Jane,jane@example.com";
$decoded = GoodCodec::csv_decode_str($csv_string, 0, "UTF-8", "UTF-8", false, ["\\N"], ",", "\"");

// 流式解码（适用于大文件）
$handle = fopen("large_file.csv", "r");
$rows = GoodCodec::csv_decode_stream(
    $handle,      // 文件句柄
    true,         // 是否关闭流
    0,            // 跳过行数
    "UTF-8",      // 输入字符集
    "UTF-8",      // 输出字符集
    false,        // 是否移除 BOM
    ["\\N"],     // 空值标记
    ",",         // 分隔符
    "\""         // 引号字符
);
foreach ($rows as $row) {
    // 处理每一行数据
}
```

### TSV 处理

```php
use GoodCodec\GoodCodec;

// 编码 TSV 字段
$encoded = GoodCodec::tsv_encode_str("Hello\tWorld", "UTF-8", "UTF-8", false, "NULL");

// 编码 TSV 行
$row = ["id" => 1, "name" => "John", "email" => "john@example.com"];
$encoded = GoodCodec::tsv_encode_row($row, "UTF-8", "UTF-8", false, "\\N");

// 编码 TSV 表格
$data = [
    ["id", "name", "email"],
    [1, "John", "john@example.com"],
    [2, "Jane", "jane@example.com"]
];
$encoded_table = GoodCodec::tsv_encode_table($data, "UTF-8", "UTF-8", false, "\\N", "\n");

// 解码 TSV 字符串
$tsv_string = "1\tJohn\tjohn@example.com";
$decoded = GoodCodec::tsv_decode_str($tsv_string, 0, "UTF-8", "UTF-8", false);

// 快速解码（性能优化版本）
$decoded_fast = GoodCodec::tsv_fast_decode_str($tsv_string, 0, "UTF-8", "UTF-8", false);

// 流式解码（适用于大文件）
$handle = fopen("large_file.tsv", "r");
$rows = GoodCodec::tsv_decode_stream($handle, true, 0, "UTF-8", "UTF-8", false);
foreach ($rows as $row) {
    // 处理每一行数据
}

// 快速流式解码
$handle = fopen("large_file.tsv", "r");
$rows = GoodCodec::tsv_fast_decode_stream($handle, true, 0, "UTF-8", "UTF-8", false);
foreach ($rows as $row) {
    // 处理每一行数据
}
```

### MySQL 字符串处理

```php
use GoodCodec\GoodCodec;

// 编码 MySQL 字符串
$encoded = GoodCodec::mysql_encode_str("It's a string");
// 输出: 'It\'s a string'

// 解码 MySQL 字符串
$decoded = GoodCodec::mysql_decode_str("'It\'s a string'");
// 输出: It's a string

// 构建 MySQL VALUES 语句
$data = [
    [1, "John", "john@example.com"],
    [2, "Jane", "jane@example.com"]
];
$values = GoodCodec::mysql_build_values($data);

// 参数绑定（支持多种类型：?s 字符串，?i 整数）
$sql = GoodCodec::mysql_bind_param(
    "SELECT * FROM users WHERE id = ?i AND name = ?s",
    1,
    "John"
);

// 批量参数绑定
$rows = [
    [1, "John"],
    [2, "Jane"]
];
$sql = GoodCodec::mysql_bind_param_array(
    "(id = ?i AND name = ?s)",
    $rows,
    " OR "
);
// 输出: (id = 1 AND name = 'John') OR (id = 2 AND name = 'Jane')

$sql = GoodCodec::mysql_bind_param_array(
    "(?i,?s)",
    $rows,
    ","
);
// 输出: (1,'John'),(2,'Jane')

// 大数据分片处理
$iterator = function() {
    yield [1, "John"];
    yield [2, "Jane"];
};
$pieces = GoodCodec::mysql_build_values_cut_to_pieces(
    "(?,?)",
    $iterator(),
    1000 // 每片大小
);

// SQL 词法分析
$tokens = GoodCodec::sql_token_get_all("SELECT * FROM users");
```
## 高级特性

## 性能优化

- TSV 解码提供了快速版本 `tsv_fast_decode_str()`，性能约为标准版本的 8 倍
- 所有编解码方法都经过优化，适用于大数据处理
- 支持流式处理，可以处理大文件而不会占用过多内存

## 许可证

MIT License