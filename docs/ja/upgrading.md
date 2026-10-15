# 2.2.x からのアップグレード

2.3.0 ではツールバーが置き換えられましたが、PHP の API はそのままです。`DebugPanel` を継承した
パネルクラスとそのエレメント、コンポーネントの設定、`DebugTimer`、`DebugMemory`、`DebugKitDebugger`、
FireCake、`ToolbarAccess` コントローラーは引き続き動作します。動作の変更点は次のとおりです。

- ツールバーはページの中に描画されるのではなく、右下の iframe の中に表示されます。また、
  `View.afterLayout` ではなく `Controller.shutdown` で挿入されます。レイアウトなしで描画されるページにも、
  HTML で `</body>` タグがあれば、ツールバーが表示されるようになりました。
- `HtmlToolbarHelper::send()` は何もしなくなりました。Ajax のリクエストと HTML 以外のリクエストは、
  これまでどおり FirePHP (`FirePhpToolbarHelper`) を使います。
- リクエスト履歴は、ユーザーエージェントごとにキャッシュに保存されるのではなく、リクエスト履歴の
  ストレージ (`tmp/debug_kit.sqlite`) に保存され、すべてのクライアントで共有されます。それに合わせて
  `History` パネルの内容も変わりました。従来のキャッシュの状態も引き続き書き込まれますが、
  `ToolbarComponent::loadState()` と `ToolbarAccessController::history_state()` は非推奨です。
- `webroot/js/js_debug_toolbar.js` と `webroot/css/debug_toolbar.css` は削除され、ツールバーは
  `webroot/css/style.css` と `webroot/js/main.js` を使います。`ToolbarComponent::$javascript` と
  `ToolbarComponent::$css` のデフォルト値は空の配列になりました。これらには、ツールバーの iframe に
  読み込む追加のアセットを指定します。
- ツールバーには jQuery も、2.x のツールバーの `DEBUGKIT` JavaScript API (`DEBUGKIT.module()`、
  `DEBUGKIT.loader`、`DEBUGKIT.$`、`DEBUGKIT.Util.Cookie` など) も含まれなくなりました。パネルの
  `css`/`javascript` ファイル (`DebugPanel::$css`、`DebugPanel::$javascript`) は引き続きツールバーの
  iframe に読み込まれますが、パネルの JavaScript は jQuery や古い API に依存してはいけません。必要なものを
  バンドルし、`DEBUGKIT.loader.register()` の代わりに `initPanel` イベント
  ([パネルのエレメント](panels.md#panel-elements) を参照してください) を使ってください。
- 組み込みのエレメントは独自の `<h2>` タイトルを出力しなくなりました。パネルのタイトルはツールバーが
  表示します。
- `ToolbarHelper::getQueryLogs()` は、接続のクエリログをクリアしなくなりました。
- `debug_kit` 接続は Sql Log パネルに表示されません。
