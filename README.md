# NOVAL

> **<ins>N</ins>otation for <ins>O</ins>pen and <ins>V</ins>ersatile <ins>A</ins>rchives <ins>L</ins>ayer**  
> **Record anything. Define everything.**

NOVALは、記録対象の構造とルールを自分で定義し、あらゆる情報を残せる汎用アーカイブサービスです。構造化されたProtocol Recordと、すぐに書き残せるOpen Recordを共通の履歴モデルで扱います。

現在はCore MVPの最初のVertical Sliceを実装中です。

## Implemented

- Laravel sessionによるアカウント登録・ログイン
- private Protocol DraftとimmutableなPublished Version
- JSON Schema Draft 2020-12の安全検査、Normalize、全Error検証
- CategoryとLogBook
- Protocol Recordのcreate／update／論理deleteと完全snapshot Revision
- `baseRevisionId`による楽観ロック
- private Open Recordと完全snapshot Revision
- SQLiteローカル開発、MySQL／MariaDB向けMigration
- server-rendered Blade UIとbuild済みVite Asset

未決事項のCore MVP既定値は[ADR 0001](docs/adr/0001-core-mvp-decisions.md)で管理します。

## Runtime

- PHP 8.3+
- Laravel 13.x
- MySQL 8.0+ / MariaDB 10.11+
- Redis・常駐Worker・WebSocket・本番Node.jsを必要としない共有レンタルサーバー互換構成

## Development

```bash
cp .env.example .env
composer install
php artisan key:generate
touch database/database.sqlite
php artisan migrate
npm install
npm run build
php artisan serve
```

品質検査:

```bash
composer format:check
composer analyse
composer test
npm run build
```

## Documentation

- [NOVAL仕様書](docs/NOVAL_SPECIFICATION.md)
- [共用レンタルサーバー配備手順](docs/DEPLOYMENT_SHARED_HOSTING.md)

## License

ソースコードは[GNU Affero General Public License v3.0 only](LICENSE)（SPDX: AGPL-3.0-only）で公開します。

Fork、改変、再配布はライセンスに従って行えます。ただし、NOVALの名称や公式ブランドを用いて、公式サービスまたは公式に承認されたサービスであると誤認させてはなりません。詳しくは[ブランドポリシー](TRADEMARKS.md)を参照してください。

## Copyright

Copyright © 2026 Tateshiki0529
