# NOVA 共用レンタルサーバー配備手順

| 項目 | 内容 |
| --- | --- |
| 文書状態 | Draft |
| 文書Version | 0.1.0 |
| 対象 | ロリポップ！スタンダード、mixhost共用レンタルサーバー |
| Application | PHP 8.3以上、Laravel 13.x |
| Database | MySQL 8.0以上、MariaDB 10.11以上 |

## 1. 原則

- 本番サーバーでNode.js、npm、Viteを実行しない。
- Composer依存関係とfrontend AssetはGitHub Actionsで生成する。
- 本番にはdeploy artifactを配置する。
- Redis、常駐Worker、Supervisor、WebSocket serverを要求しない。
- Queueはdatabase driverを使用する。
- Schedulerと短命Queue Workerはcronから実行する。
- Application本体と`.env`をWeb公開領域へ置かない。
- Web serverのDocumentRootは可能な限りLaravelの`public`へ向ける。

## 2. 対象サーバーの事前確認

配備前に管理画面またはSSHで次を確認する。

- WebとCLIのPHPが8.3以上であること
- 必須PHP extensionが有効であること
- MySQL 8系またはMariaDB 10.11系であること
- cronを1分間隔で登録できること
- SSHまたはSFTPでartifactを配置できること
- `storage`と`bootstrap/cache`へPHP実行Userが書き込めること

mixhostは収容サーバーによってMariaDB 10.11または10.5が提供される。NOVAの対象はMariaDB 10.11以上であるため、契約・移設前に収容サーバーのVersionを確認する。

Web経由のPHP VersionとCLIのPHP Versionが異なる場合がある。両方を確認し、cronではPHP 8.3以上のbinaryを明示する。

## 3. GitHub Actionsの成果物

CIは次の順でdeploy artifactを生成する。

1. `composer install`で開発用依存関係を取得する。
2. coding style、static analysis、unit testを実行する。
3. MySQL 8系とMariaDB 10.11系でMigrationとintegration testを実行する。
4. `npm ci`と`npm run build`を実行する。
5. production用に`composer install --no-dev --classmap-authoritative`を実行する。
6. application source、`vendor`、`public/build`をartifactへ格納する。

artifactへ含めるもの:

```text
app/
bootstrap/
config/
database/
public/
resources/
routes/
storage/              # 書き込み用directory構造のみ
vendor/
artisan
composer.json
composer.lock
```

artifactへ含めないもの:

```text
.env
.git/
node_modules/
tests/
開発用cache
利用者がアップロードしたAsset
```

`public/build/manifest.json`が存在しないartifactは配備しない。

## 4. 推奨directory構成

```text
/home/account/
 ├─ nova/
 │   ├─ releases/
 │   │   └─ 20260720-000001/
 │   ├─ shared/
 │   │   ├─ .env
 │   │   └─ storage/
 │   └─ current -> releases/20260720-000001
 └─ public_html/またはWeb公開directory
```

symlinkを利用できない場合は、`current`を固定directoryとして上書きせず、旧releaseを残した状態で新releaseへ切り替えられる手順を用意する。

`.env`と永続化する`storage`はrelease外へ置く。releaseからshared領域へsymlinkできない場合は、配備時に安全なcopyまたはサーバー固有のpath設定を行う。

## 5. DocumentRootを変更できる場合

独自ドメインまたはsubdomainのDocumentRootを次へ設定する。

```text
/home/account/nova/current/public
```

この構成ではLaravel標準の`public/index.php`と`.htaccess`をそのまま使用する。

配備手順:

1. 新しいrelease directoryへartifactを展開する。
2. shared `.env`と`storage`を接続する。
3. `storage`と`bootstrap/cache`の権限を確認する。
4. PHP 8.3以上で`php artisan migrate --force`を実行する。
5. `php artisan optimize:clear`を実行する。
6. `php artisan config:cache`を実行する。
7. `php artisan route:cache`が成功する場合だけ適用する。
8. `current`を新releaseへ切り替える。
9. health endpointを確認する。

config cacheには環境固有の値が含まれるため、CIではなく本番環境で生成する。

## 6. DocumentRootを変更できない場合

Application本体を公開領域外へ配置し、Laravelの`public`の内容だけを既定DocumentRootへ配置する。

```text
/home/account/nova/current/       # Application、vendor、storage
/home/account/public_html/        # publicの内容だけ
```

公開directoryへ配置するもの:

- `index.php`
- `.htaccess`
- `build/`
- faviconなどの公開静的Asset
- 必要になった時点の公開Asset用directory

公開directoryへ配置してはならないもの:

- `.env`
- `vendor`
- `app`
- `config`
- `database`
- `routes`
- private storage
- Composer metadata

公開側`index.php`は、次の参照先だけを配備先の絶対pathへ変更する。

- maintenance file: `nova/current/storage/framework/maintenance.php`
- Composer autoloader: `nova/current/vendor/autoload.php`
- Laravel bootstrap: `nova/current/bootstrap/app.php`

この変更はdeploy templateとして管理し、サーバー上で手編集しない。絶対pathにUser名や契約情報が含まれる場合は公開Repositoryへcommitせず、GitHub Actionsのdeploy設定またはサーバー側templateで注入する。

Laravel標準の`.htaccess`を公開directoryへ配置し、すべてのApplication routeを公開側`index.php`へ転送する。

## 7. Environment設定

最小構成例:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://example.com

DB_CONNECTION=mysql
DB_HOST=database-host
DB_PORT=3306
DB_DATABASE=nova
DB_USERNAME=nova
DB_PASSWORD=change-me

QUEUE_CONNECTION=database
CACHE_STORE=file
SESSION_DRIVER=file
FILESYSTEM_DISK=local
```

MariaDBでもLaravel側のconnection名は`mysql`を使用する。

`.env`をGitへcommitしない。APP_KEY、DB password、mail credential、API tokenはGitHub Actions artifactにも含めない。

## 8. SchedulerとQueue

Laravel Schedulerを毎分起動する。

一般的なcron command:

```text
* * * * * cd /absolute/path/nova/current && /absolute/path/php8.3 artisan schedule:run >> /dev/null 2>&1
```

ロリポップ！のcron管理画面など、実行fileを指定する形式では、公開領域外にscheduler起動用scriptを配置し、そのscriptからPHP 8.3以上で`artisan schedule:run`を実行する。管理画面でdomainのPHP Versionを変更した後はcron設定も再作成・再確認する。

Schedulerから短命Queue Workerを起動する例:

```php
use Illuminate\Support\Facades\Schedule;

Schedule::command(
    'queue:work database --stop-when-empty --max-time=45 --tries=3'
)->everyMinute()->withoutOverlapping();
```

要件:

- Workerはqueueが空または45秒経過で終了する。
- Jobは冪等にする。
- Jobの最大実行時間を共有サーバーの制限内へ収める。
- 長いCSV importなどは小さなJobへ分割する。
- failed_jobsを監視・削除するScheduler taskを用意する。
- file cacheで`withoutOverlapping`が機能する一台構成を標準とする。

## 9. Filesystem

Core MVPはlocal diskだけで動作させる。

- private fileは`storage/app/private`へ保存する。
- Webから直接公開しない。
- downloadは認可済みController経由で返す。
- 公開Assetが必要な場合だけpublic diskを利用する。
- symlinkを作成できない環境では、Laravel Storageを通したController配信または配備時copyを使用する。

将来S3互換storageへ移行する場合も、DomainはLaravel Filesystem adapterのinterfaceだけを参照する。

## 10. Deploy後確認

- HTTPSでhealth endpointが200を返す。
- `APP_DEBUG=false`である。
- `.env`、`vendor`、`composer.json`へWebからアクセスできない。
- login、CSRF、sessionが動作する。
- DB接続とMigration Versionが正しい。
- Protocol Draftの保存と取得ができる。
- QueueへJobを登録し、cron実行後に処理される。
- mail testが設定済みtransportを通る。
- local filesystemへの書き込みと認可付き読み出しができる。
- `public/build/manifest.json`が参照できる。

## 11. Rollback

1. 旧releaseを削除せず残す。
2. DB Migrationは後方互換を基本とし、deploy直後の旧code rollbackを可能にする。
3. 問題発生時は`current`または公開側indexの参照先を旧releaseへ戻す。
4. destructive Migrationは別releaseで段階実行する。
5. rollback後にconfig、route、view cacheを再生成する。

## 12. 参照資料

- [Laravel 13 Release Notes](https://laravel.com/docs/13.x/releases)
- [Laravel Deployment](https://laravel.com/docs/13.x/deployment)
- [Laravel Database](https://laravel.com/docs/13.x/database)
- [Laravel Filesystem](https://laravel.com/docs/13.x/filesystem)
- [Laravel Task Scheduling](https://laravel.com/docs/13.x/scheduling)
- [ロリポップ！PHP・MySQL仕様](https://lolipop.jp/manual/hp/cgi/)
- [ロリポップ！cron設定](https://lolipop.jp/manual/user/cron/)
- [ロリポップ！SSH設定](https://lolipop.jp/manual/user/ssh/)
- [mixhost PHP仕様](https://help.mixhost.jp/articles/115003735811)
- [mixhost Database仕様](https://help.mixhost.jp/articles/115003742232)
