# 設定

設定は、ほかのコンポーネントの設定と同じように、コンポーネントの宣言で渡します。

```php
public $components = [
    'DebugKit.Toolbar' => [/* settings */],
];
```

<a id="controlling-panels"></a>

## パネルの制御

`panels` キーで、読み込むパネルとその順番を指定します。

```php
public $components = [
    'DebugKit.Toolbar' => ['panels' => ['MyCustom', 'timer' => false]],
];
```

この例では、独自のパネル `MyCustomPanel` を追加し、組み込みの Timer パネルを取り除きます。

ほかのキーはパネルのコンストラクターに渡されます。たとえば、組み込みの History パネルは、
一覧表示する過去のリクエストの数を `history` キーで設定します。

```php
public $components = [
    'DebugKit.Toolbar' => ['history' => 10],
];
```

`panels` キー自体は、パネルのコンストラクターには渡されません。

<a id="forceenable"></a>

## forceEnable

`forceEnable` を指定すると、`Configure::read('debug')` に関係なくツールバーが表示されます。
本番モードでアプリケーションをプロファイリングするときに便利です。

```php
public $components = [
    'DebugKit.Toolbar' => ['forceEnable' => true],
];
```

<a id="autorun"></a>

## autoRun

`autoRun` は、ツールバーを自動的に実行するかどうかを制御します。`false` にすると、URL に
`?debug=true` のクエリ文字列があるときだけツールバーが有効になります。

```php
public $components = [
    'DebugKit.Toolbar' => ['autoRun' => false],
];
```

<a id="forceenable-without-debug-mode"></a>

## デバッグモードなしでの forceEnable

ツールバーのエンドポイント (`/debug_kit/requests/...`、`/debug_kit/panels/...`) は、デバッグモードでのみ
利用できます。`debug` を 0 にした状態で `forceEnable` を使うには、エンドポイントが有効になるように
`Configure` にも設定を書き込んでください。

```php
Configure::write('DebugKit.forceEnable', true);
```

<a id="request-history-storage"></a>

## リクエスト履歴のストレージ

記録されたリクエストと描画済みのパネルは、SQLite データベース `TMP/debug_kit.sqlite`
(通常は `tmp/debug_kit.sqlite`) に保存されます。`debug_kit` 接続は `ConnectionManager::create()` で
作成され、テーブルは自動的に作成されます。保持されるのは最新の 20 件のリクエストだけです。

PDO SQLite ドライバーが利用できない (またはデータベースを作成できない) 場合は、代わりに `debug_kit` の
キャッシュ設定にリクエストが保存されます ([キャッシュエンジン](#cache-engine) を参照してください)。
どちらも利用できない場合、リクエストは記録されず、ツールバーも表示されません。

| 設定 | デフォルト | 説明 |
| --- | --- | --- |
| `requestCount` | `20` | 保持するリクエストの数。 |
| `storage` | `auto` | `auto` はデータベースを使い、使えない場合はキャッシュを使います。`database` または `cache` を指定すると、そのどちらかに固定します。 |
| `connection` | `debug_kit` | 接続名。`database.php` でこの名前の接続を定義している場合は、SQLite ファイルの代わりにその接続が使われます (テーブルもその接続に作成されます)。MySQL では、`TEXT` は最大 64 KB までしか保持できないので、パネルの内容のカラムは `LONGTEXT` として作成されます。 |
| `database` | `TMP . 'debug_kit.sqlite'` | 接続を作成するときに使う SQLite ファイル。 |

ツールバーのエンドポイントは `Configure::read('DebugKit')` だけを読み込むので、ストレージの設定は
コンポーネントの設定ではなく、こちらに書いてください (コンポーネントもこれを読み込みます)。

```php
Configure::write('DebugKit', [
    'requestCount' => 50,
    'database' => TMP . 'debug_kit' . DS . 'requests.sqlite',
]);
```

<a id="cache-engine"></a>

## キャッシュエンジン

DebugKit は、内部のキャッシュにデフォルトで File キャッシュエンジンを使います。対象は、従来のツールバーの
状態 (`ToolbarComponent::loadState()`、`ToolbarHelper::readCache()`) と、
[キャッシュストレージ](#request-history-storage) を使う場合のリクエスト履歴です。ほかのエンジンを使うには
`cache` キーを使います。`Cache::config()` がサポートするエンジンであれば、どれでも使えます。

```php
public $components = [
    'DebugKit.Toolbar' => [
        'cache' => ['engine' => 'Memcached', 'servers' => ['127.0.0.1:11211']],
    ],
];
```
