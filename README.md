Database Describer
====

## Description

稼働している実データベースから仕様書や ERD を生成するツールです。
ただ一つのコマンドで構成されます。

## Install

```json
{
    "require": {
        "ryunosuke/db-describer": "dev-master"
    }
}
```

## Demo

```sh
cd /path/to/clonedir/demo
mysql < mysql.sql
php ../describe.phar mysql://localhost/test_describer --delimiter ":"
```

## Usage

コマンドラインツールが付属しています。また、 phar もあります。
依存を避けるため phar の利用を推奨します。下記の記述例は phar が前提です。

```sh
Usage:
  describe [options] [--] <dsn> [<outdir>]

Arguments:
  dsn                        Specify Database DSN
  outdir                     Specify Output directory

Options:
  -m, --mode[=MODE]          Specify Output file([html|spec|erd|all]) [default: ["all"]] (multiple values allowed)
  -i, --include=INCLUDE      Specify Include table (multiple values allowed)
  -e, --exclude=EXCLUDE      Specify Exclude table (multiple values allowed)
  -l, --delimiter=DELIMITER  Specify Comment delimiter for summary [default: "\n"]
  -t, --template=TEMPLATE    Specify Spec template
  -d, --dot=DOT              Specify dot location [default: "dot"]
  -c, --columns=COLUMNS      Specify Erd columns([related|all]) [default: "related"]
  -C, --config=CONFIG        Specify Configuration filepath [default: "config.php"]
  -h, --help                 Display this help message
  -q, --quiet                Do not output any message
  -V, --version              Display this application version
      --ansi                 Force ANSI output
      --no-ansi              Disable ANSI output
  -n, --no-interaction       Do not ask any interactive question
  -v|vv|vvv, --verbose       Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug

Help:
  describe Database.
```

### dsn

対象のデータベース DSN URL を指定します。具体的には下記のような文字列です。

- mysql://user:pass@localhost/dbname?charset=utf8

要するに DSN の各種パーツを URL に見立てて構築します。
一部省略（localhost など）できたりしますが、裏仕様なので割愛します。

### outdir

ファイルを出力するディレクトリを指定します。
省略した場合はカレントディレクトリです。

### --mode(m)

データベース仕様書（xlsx）と ERD（pdf）のどちらを出力するか指定します。

- spec: データベース仕様書
- erd: ERD
- all: 両方

省略した場合は 'all' です。

- e.g. `--mode erd` : ERD のみ出力する

### --include(-i)

出力対象のテーブル名を正規表現で指定します。
指定の前後に `^$` は付きません。包含一致です。

multiple なので複数のオプションで複数指定できます。その場合は `OR` 動作です。

- e.g. `--include "^t_article"` : t_article.* に一致するテーブルを出力対象にする
- e.g. `--include "^t_article" --include "^t_customer"` : `t_article.*` or `t_customer.*` に一致するテーブルを出力対象にする

### --exclude(-e)

除外対象のテーブル名を正規表現で指定します。
指定の前後に `^$` は付きません。包含一致です。

multiple なので複数のオプションで複数指定できます。その場合は `OR` 動作です。

- e.g. `--exclude "^t_article"` : t_article.* に一致するテーブルは出力されない
- e.g. `--exclude "^t_article" --exclude "^t_customer"` : `t_article.*` or `t_customer.*` に一致するテーブルは出力されない

### --delimiter(-l)

コメントを「論理名」と「備考」に分ける区切り文字を指定します。デフォルトは LF です。

```
これは論理名です
これは備考です
これも備考です
```

上記は「これは論理名です」が論理名になり、「これは備考です\nこれも備考です」が備考になります。

### --template(t)

データベース仕様書の xlsx テンプレートを指定します。
省略した場合は組み込みのテンプレートを使います。

### --dot(d)

graphviz へのパスを指定します。
省略した場合は `dot` です。

### --columns(c)

ERD の出力カラムを指定します。

- related: 主キー・外部キーカラムのみ
- all: 全カラム

省略した場合は 'related' です。

### --config(C)

各種設定を外部ファイルから指定します。
一部、コマンドライン引数と重複しています。

```php
<?php return [
    // --include 引数と同じ（同時指定時は引数が優先）
    'include'        => [],
    // --exclude 引数と同じ（同時指定時は引数が優先）
    'exclude'        => [],
    // カスタムリレーションを指定します
    'relation'       => [
        // 下記の ALTER 文が（擬似的に）適用されたとして外部キーがあるように扱います
        // ALTER TABLE t_child ADD CONSTRAINT fk_Child_Parent1 FOREIGN KEY (parent_id) REFERENCES t_parent1 (id)
        // ALTER TABLE t_child ADD CONSTRAINT fk_Child_Parent2 FOREIGN KEY (other_id) REFERENCES t_parent2 (p_id)
        't_child' => [
            't_parent1' => [
                'fk_Child_Parent1' => [
                    'parent_id' => 'id',
                ],
            ],
            't_parent2' => [
                'fk_Child_Parent2' => [
                    'other_id' => 'p_id',
                ],
            ],
        ],
    ],
    // 接続コネクションのコールバックです
    'connectionCallback' => function (\Doctrine\DBAL\Connection $connection) {
        // dbms 特有の型を追加
        $types = \Doctrine\DBAL\Types\MySql\SpatialType::addSpatialTypes();
        foreach ($types as $dbType => $type) {
            $connection->getDatabasePlatform()->registerDoctrineTypeMapping($dbType, $type->getName());
        }
    },
    // スキーマ情報を漁るときのコールバックです
    'schemaCallback' => function (\Doctrine\DBAL\Schema\Schema $schema) {
        // 特別なテーブルを追加
        $table = $schema->createTable('aaaaa');
        $table->addColumn('xx', 'string');
        $table->setPrimaryKey(['xx']);
    },
    // テーブル情報を漁るときのコールバックです
    'tableCallback'  => function (\Doctrine\DBAL\Schema\Table $table) {
        // false を返すと除外される（exclude と同じ効果）
        if ($table->getName() === 't_hoge') {
            return false;
        }
        // t_fuga のコメントを上書き
        if ($table->getName() === 't_fuga') {
            $table->addOption('comment', 'コメントです');
        }
    },
    // ビュー情報を漁るときのコールバックです
    'viewCallback'   => function (\Doctrine\DBAL\Schema\View $view) {
        // false を返すと除外される（exclude と同じ効果）
        if ($view->getName() === 't_view') {
            return false;
        }
    },
    // --template 引数と同じ（同時指定時は引数が優先）
    'template'       => 'standard.xlsx',
    // カスタムシートの変数を指定します
    'sheets'         => [
        // sheetName もレンダリングされるようにします
        'sheetName' => [
            'value1' => 'hoge',
            'value2' => 'fuga',
        ],
        // index, table キーは組み込みのテンプレート変数とマージされます
        'index'     => [
            // something vars
        ],
        'table'     => [
            // something vars
        ],
    ],
    // --dot 引数と同じ（同時指定時は引数が優先）
    'dot'            => 'dot',
    // --columns 引数と同じ（同時指定時は引数が優先）
    'columns'        => 'related',
    // graphviz における Graph の属性です
    'graph'          => [],
    // graphviz における Node の属性です
    'node'           => [],
    // graphviz における Edge の属性です
    'edge'           => [],
];
```

このような php ファイルを用意して `-C` で読み込ませると処理の前に様々な処理を挟めるようになります。
具体的には上記のコメントのように「特別な型・テーブルを追加したい」「名前ではない指定で除外したい」「テーブル情報をいじりたい」などです。
全部指定する必要はなく、必要なものだけで OK です。
