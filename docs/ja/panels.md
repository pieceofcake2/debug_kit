# パネル

<a id="toolbar-panels"></a>

## ツールバーのパネル

ツールバーはいくつかのパネルで構成されており、ページの右下にある CakePHP のアイコンをクリックすると
表示されます。各パネルは、パネルクラスとビューエレメントからなります。通常、1 つのパネルは、ログや
リクエストの情報など、1 種類の情報を集めて表示します。表示するパネルを選んだり、独自のパネルを追加したり
できます。

<a id="built-in-panels"></a>

### 組み込みのパネル

- **History**: 過去のリクエスト (リダイレクトや Ajax リクエストを含む) を一覧表示し、そのパネルを
  ツールバーに表示します。リダイレクトするアクションをデバッグするときに便利です。
- **Session**: 現在セッションにある情報を表示します。
- **Request**: 現在のリクエストについての情報を表示します。GET、POST、Cake のパラメーター、現在のルートの
  情報、そしてコントローラーのコンポーネントに `CookieComponent` がある場合は Cookie です。
- **Sql Log**: データベース接続ごとの SQL ログを表示します。
- **Timer**: リクエストの間に設定されたタイマー (`DebugKitDebugger` を参照してください)、
  コンポーネントのコールバック時のメモリ使用量、メモリ使用量のピークを表示します。
- **Log**: リクエストの間に書き込まれたログのエントリーを表示します。
- **Variables**: コントローラーで設定されたビュー変数を表示します。
- **Environment**: PHP と CakePHP に関連する環境変数を表示します。
- **Include**: 読み込まれたファイルを種類ごとにまとめて表示します。

<a id="developing-your-own-panels"></a>

## 独自のパネルの開発

<a id="panel-classes"></a>

### パネルクラス

パネルクラスは、`Lib` パスの中の `Panel` ディレクトリに置きます。ファイル名はクラス名と一致している
必要があるので、`MyCustomPanel` は `src/Lib/Panel/MyCustomPanel.php` (レガシーレイアウトでは
`app/Lib/Panel/MyCustomPanel.php`) にあるものとみなされます。パネルは `DebugPanel` を継承する必要が
あります。

```php
App::uses('DebugPanel', 'DebugKit.Lib');

class MyCustomPanel extends DebugPanel
{
}
```

`tests/test_app/Plugin/DebugkitTestPlugin/Lib/Panel/PluginTestPanel.php` にあるパネルの例も参照して
ください。

パネルでは `css` と `javascript` プロパティ (配列) を定義して、ツールバーに追加の CSS や JavaScript を
読み込めます。これらはアプリケーションのページではなく、ツールバーの iframe に読み込まれます。

```php
class MyCustomPanel extends DebugPanel
{
    public $javascript = [
        '/my_plugin/js/custom_panel.js',
    ];
}
```

<a id="callbacks"></a>

### コールバック

パネルには、現在のリクエストを調べるための 2 つのコールバックがあります。

- `startup(Controller $controller)` は、コンポーネントの `startup()` の中で呼ばれます。
- `beforeRender(Controller $controller)` は、コンポーネントの `beforeRender()` の中で呼ばれます。
  戻り値は `$content` としてパネルのエレメントに渡されるので、自分でビュー変数を設定する必要は
  ありません。

```php
/**
 * Grabs the request parameters.
 *
 * @param \Controller $controller The controller.
 * @return array
 */
public function beforeRender(Controller $controller)
{
    return $controller->request->params;
}
```

パネルは短い概要を返すこともできます。概要は、ツールバーのパネルのボタンの横に表示されます (たとえば、
Sql Log パネルのクエリの数)。

```php
/**
 * @param mixed $content The content returned by beforeRender().
 * @return string
 */
public function summary($content = null)
{
    return (string)count($content);
}
```

<a id="panel-elements"></a>

### パネルのエレメント

各パネルには、パネルが返した内容を描画するビューエレメントがあります。エレメント名は、クラス名を
アンダースコア区切りにしたものです。`SessionPanel` は `session_panel.ctp` を、`SqlLogPanel` は
`sql_log_panel.ctp` を使います。エレメントは `Elements` ディレクトリに置いてください。

エレメントはリクエストの最後に描画され (ページが HTML のツールバーとともに描画される場合は、そのリクエストの
ビューで描画されます)、描画された HTML が保存されます。ツールバーはそれを
`<div class="panel-content" id="<panel>-tab">` の中に表示します。`<panel>` はアンダースコア区切りの
パネル名です。ツールバーには jQuery もほかのライブラリも含まれていないので、パネルの JavaScript は
必要なものをバンドルする必要があります。パネルが読み込まれるたびに、`document` で `initPanel`
イベントが発行され、`detail` には先頭に `panel` を付けたパネル名 (たとえば `panelmy_custom`) が入ります。

```js
document.addEventListener('initPanel', (event) => {
  if (event.detail !== 'panelmy_custom') {
    return;
  }
  document.querySelectorAll('#my_custom-tab .my-row').forEach((row) => {
    // ...
  });
});
```

パネルの内容の `<script>` 要素は、パネルが読み込まれたときに実行されます。

パネルのタイトルとエレメント名は、規約に従って決まります。変更するには、次のプロパティを設定します。

- `$title`: ツールバーのパネルのボタンに使うタイトル。
- `$elementName`: パネルの描画に使うエレメント。

<a id="panels-in-plugins"></a>

### プラグインのパネル

プラグインが提供するパネルも同じように動作しますが、エレメントが見つかるように、`$plugin` に
プラグイン名を設定する必要があります。

```php
class MyCustomPanel extends DebugPanel
{
    public $plugin = 'MyPlugin';
}
```

プラグインのパネルを読み込むには、プラグイン記法を使います。

```php
public $components = [
    'DebugKit.Toolbar' => ['panels' => ['MyPlugin.MyCustom']],
];
```

これで、すべてのデフォルトのパネルと、`MyPlugin` の独自のパネルが読み込まれます。
