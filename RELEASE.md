# RELEASE

バージョニングはセマンティックバージョニングでは**ありません**。

| バージョン   | 説明
|:--           |:--
| メジャー     | 大規模な仕様変更の際にアップします（クラス構造・メソッド体系などの根本的な変更）。<br>多くの場合、メジャーバージョンアップ対応は多大なコストを伴います
| マイナー     | 小規模な仕様変更の際にアップします（中機能追加・メソッドの追加など）。<br>マイナーバージョンアップ対応は1日程度の修正で終わるようにします
| パッチ       | バグフィックス・小機能追加の際にアップします（基本的には互換性を維持するバグフィックス）。<br>パッチバージョンアップは特殊なことをしてない限り何も行う必要はありません

なお、下記の一覧のプレフィックスは下記のような意味合いです。

- change: 仕様変更
- feature: 新機能
- fixbug: バグ修正
- refactor: 内部動作の変更
- `*` 付きは互換性破壊

## x.y.z

- コードが汚すぎるのでバージョンを上げてリファクタ

## 2.1.0

- package update
  - 特に互換性は壊れていないけど backport マージや利便性を考えてマイナーアップとする

## 2.0.6

- [bin] box を v4 系に変更
- [fixbug] php8.2 でのエラーを修正

## 2.0.5

- [composer] update
  - doctrine で BC break な変更があったっぽいので

## 2.0.4

- [change] 最新追従
  - RDBMS 固有の型も出力できるようになった
  - 精度のデフォルト 10,0 がなくなった

## 2.0.3

- [refactor] Views だけアロー関数になっていなかったので修正
- [fixbug] format に null が来て即死する不具合を修正
- [fixbug] テーブル名に特殊な文字（ドットなど）があると誤作動する不具合を修正

## 2.0.2

- [composer] update
  - 式デフォルト対応

## 2.0.1

- [feature] 引数も config で指定できるように修正

## 2.0.0

- [template] スキーマオブジェクトが増えたのでテンプレートも修正
- [*change] include,exclude,callback を全オジェクトに適用
- [*change] xlsx サポートを削除
  - ERD も viz.js のみにする
- [feature] doctrine の最新追従
  - routine 対応
  - event 対応

## 1.0.8

- [demo] デモがかなり古く、動かなかったのでメンテ
- [design] 見た目の修正と変更
- [feature] viz.js によってクライアントサイドで非表示非表示が切り替えられるようになった
- [feature] dot のレンダリングで viz.js に対応
- [fixbug] インデックスの並び順が乱れていたので主キー優先名前順に変更

## 1.0.7

- [feature] 内部を doctrine 3.3 に変更
  - FULLTEXT や 生成列に対応した

## 1.0.6

- [feature][box.json] 外部からテンプレートを指定する時に html 以下を指定するのが大変なので alias を指定する
- [fixbug][template/html] おかしな挙動を修正
- [feature][Describer] vars でカスタム変数を流し込めるように変更

## 1.0.5

- [feature] html 出力に対応
- [feature] view の詳細化
- [feature] dot の出力を変更

## 1.0.4

- [refactor] composer update

## 1.0.3

- [fixbug] 主キーがないテーブルが含まれているとエラーになる不具合を修正

## 1.0.2

- bump version
  - php: 7.4
  - doctrine: 3.*
  - box: 3.*
- [fixbug] config ファイルが効かない不具合を修正

## 1.0.1

- php:7.2
- 依存ライブラリの更新

## 1.0.0

- 公開
