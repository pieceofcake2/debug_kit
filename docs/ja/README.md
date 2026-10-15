# DebugKit ドキュメント

DebugKit は、CakePHP 2 アプリケーションにデバッグ用のツールバーを追加します。このプラグインは、
[pieceofcake2/cakephp](https://github.com/pieceofcake2/cakephp) 向けの
[cakephp/debug_kit](https://github.com/cakephp/debug_kit) のフォークです。

- [インストール](#installation)
- [ツールバー](#the-toolbar)
- [パネル](panels.md): 組み込みのパネルと独自のパネルの開発
- [設定](configuration.md): ツールバーの設定、リクエスト履歴のストレージ、キャッシュエンジン
- [2.2.x からのアップグレード](upgrading.md)
- 2.3.x と 2.2.x の [ツールバーのスクリーンショット](screenshots.md)
- [クレジット](#credits)

<a id="installation"></a>

## インストール

[Composer](https://getcomposer.org/) でプラグインをインストールします。

```bash
composer require --dev pieceofcake2/debug_kit
```

プラグインのタイプは `cakephp-plugin` なので、`composer/installers` によってプラグインディレクトリに
インストールされます。正しい場所にインストールされるように、アプリケーションの `composer.json` で
`installer-paths` を設定してください。

| 構成 | `installer-paths` |
| --- | --- |
| モダン (`config/`、`src/`、`templates/`、`plugins/`) | `"plugins/{$name}/": ["type:cakephp-plugin"]` |
| レガシー (`app/Config/`、`app/Plugin/`) | `"app/Plugin/{$name}/": ["type:cakephp-plugin"]` |

<a id="enable-the-plugin"></a>

### プラグインを有効にする

`config/bootstrap.php` (レガシー構成では `app/Config/bootstrap.php`) でプラグインを読み込みます。
すでに `CakePlugin::loadAll()` を使っている場合は不要です。

```php
CakePlugin::load('DebugKit');
```

`src/Controller/AppController.php` (レガシー構成では `app/Controller/AppController.php`) に
ツールバーのコンポーネントを追加します。

```php
class AppController extends Controller
{
    public $components = ['DebugKit.Toolbar'];
}
```

`config/core.php` で `debug` が 1 以上のときに、ツールバーが表示されます。

レイアウト (通常は `templates/Layouts/default.ctp`、レガシー構成では
`app/View/Layouts/default.ctp`) から `sql_dump` エレメントを削除してください。このエレメントは
Sql Log パネルが読み込む前にクエリログを空にしてしまうので、パネルに "No query logs" と表示されます。

<a id="the-toolbar"></a>

## ツールバー

2.3.0 から、ツールバーは DebugKit 5 (cakephp/debug_kit 5.x) の見た目とアーキテクチャを、
CakePHP 2 に合わせて取り入れています。

- HTML ページの右下に小さな CakePHP のアイコンが表示されます。クリックするとツールバーが開き、
  ページの下部にパネルごとのボタンが並びます。ボタンをクリックすると、パネルの内容がスライドして
  表示されます。<kbd>Esc</kbd> でパネルまたはツールバーを閉じ、矢印キーでパネルを切り替えます。
- ツールバーは iframe の中に描画されます。そのため、アプリケーションの CSS や JavaScript がツールバーを
  壊すことはなく、ツールバーがアプリケーションを壊すこともありません。
- 各リクエストの最後 (`ToolbarComponent::shutdown()`、リダイレクトの場合は `beforeRedirect()`) に、
  パネルが描画されて [リクエスト履歴のストレージ](configuration.md#request-history-storage) に
  保存され、小さなローダースクリプト (`webroot/js/inject-iframe.js`) が `</body>` の前に挿入されます。
  その後、ツールバー (`/debug_kit/requests/view/<id>`) と各パネル
  (`/debug_kit/panels/view/<id>/<panel>`) が、プラグインのコントローラーへの別々のリクエストで
  読み込まれます。
- 記録されたすべてのレスポンスには `X-DEBUGKIT-ID` ヘッダーが付きます。ページが行った Ajax リクエスト
  (`XMLHttpRequest` と `fetch()`) は History パネルに一覧表示され、History パネルで任意のリクエストを
  選ぶと、そのリクエストのパネルがツールバーに表示されます。
- ツールバーは HTML のレスポンスにだけ挿入されます。JSON、XML、Ajax、`requestAction()`、
  リダイレクトのレスポンスは記録されますが (History パネルから確認できます)、レスポンス自体は
  変更されません。DebugKit プラグイン自体へのリクエストは記録されません。

プラグインのコントローラーは、デフォルトのプラグインルート (`/:plugin/:controller/:action/*`) に
依存しています。このルートは、`routes.php` の `require CAKE . 'Config' . DS . 'routes.php';` で
読み込まれます。

![ツールバー](../img/toolbar.png)

<a id="credits"></a>

## クレジット

ツールバーの見た目とアーキテクチャは、[cakephp/debug_kit](https://github.com/cakephp/debug_kit) 5.x
(Copyright (c) Cake Software Foundation, Inc.、MIT License) から移植したものです。コピーして変更した
ファイルには元の著作権表示が残されています。一覧は [NOTICE](../../NOTICE) をご覧ください。
Raleway フォントは SIL Open Font License 1.1 でライセンスされています (`webroot/css/raleway-OFL.txt`)。

<a id="origin"></a>

## 由来

このパッケージは [cakephp/debug_kit](https://github.com/cakephp/debug_kit) (Packagist では
`cakephp/debug_kit`、アップストリームの最終バージョンは 2.2.9) のメンテナンスされたフォークで、
元の作者は Mark Story と CakePHP コミュニティ (Cake Software Foundation, Inc.) です。
[pieceofcake2](https://github.com/pieceofcake2) が、モダンな PHP で動く CakePHP 2 向けに
メンテナンスしています。オリジナルからの変更点は
[フォーク以降のコミット履歴](https://github.com/pieceofcake2/debug_kit/compare/80d8765...2.x)
で確認できます。
