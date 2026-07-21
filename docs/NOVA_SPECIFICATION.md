# NOVA 仕様書

> **NOVA — Notation for Open and Versatile Archives**  
> **Record anything. Define everything.**

| 項目 | 内容 |
| --- | --- |
| 文書状態 | Draft |
| 文書Version | 0.3.0 |
| 対象 | NOVA MVPおよび将来拡張の基礎設計 |
| 最終更新日 | 2026-07-20 |

---

## 1. 文書の目的

本書は、NOVAのプロダクト思想、用語、情報モデル、Category、Protocol、LogBook、Protocol Record、Open Record、バリデーション、履歴管理、公開機能、Matrix、API、データベース、および段階的MVPの実装範囲を定義する。

本書では、会話の中で合意済みの事項と、現時点での設計提案、今後決定が必要な事項を区別して記載する。

- **必須**: NOVAとして満たす必要がある要件
- **推奨**: 現時点で採用を推奨する設計
- **将来**: MVPでは実装しないが拡張可能性を残す事項
- **未決**: 実装開始前または該当機能着手前に決定する事項

本文中の`interface`および`type`表記は、JSON構造を簡潔に示す言語中立のIDLとして使用している。実装時はPHP 8.3以上のValue Object、readonly DTO、Enumへ置き換え、TypeScript runtimeをBackend要件としない。

## 2. プロダクト概要

NOVAは、ユーザーが記録対象の構造とルールを自ら定義して構造化Recordを保存でき、Protocolを必要としない自由記述も残せる、一般公開型の汎用アーカイブサービスである。

車、日記、読書記録などの構造化用途を個別機能として実装するのではなく、共通のProtocol、LogBook、Protocol Recordの仕組みで表現する。定義せず直ちに残したい情報は、LogBookに所属しないOpen Recordとして記録する。

NOVAが提供する中心価値は、次の体験である。

> なんでも残せる。

自由度そのものを目的にするだけでなく、ユーザーがProtocolによって記録方針を先に決め、実際の記録時に迷ったり面倒に感じたりしないことを重視する。

## 3. プロダクト原則

### 3.1 基本原則

1. Record anything. Define everything.
2. すべての記録対象を共通の仕組みで扱う。
3. 定義された項目については、型と制約を保証する。
4. Protocol Recordは常にProtocolで定義されたFieldだけを受け付ける。
5. 構造化してきれいに扱いたければProtocolとLogBookを定義する。
6. とにかく残したければ、Protocolを必要としないOpen Recordの長文bodyを使用する。
7. 異常値は現実に起きた正しいデータである可能性があるため、原則としてWarningとする。
8. 過去のRecordは、作成時点のProtocol Versionによって解釈できる状態を維持する。
9. Protocol変更やRecord編集によって過去を上書きしない。

### 3.2 バリデーション原則

1. サーバー側バリデーションを唯一の正とする。
2. フロント側バリデーションはUX改善目的に限定する。
3. Webフォーム、API、CSVインポート、Webhookなど、すべての入力経路を同じバリデーション処理へ通す。
4. NormalizeとValidateを明確に分離する。
5. Protocolの検証仕様はJSON Schema Draft 2020-12へ寄せる。
6. 任意JavaScriptを検証式として実行しない。
7. バリデーションは最初のErrorで停止せず、可能な限り全件を返す。
8. 表示文言ではなく、安定したcodeとruleをクライアント処理の基準にする。

## 4. 用語

### 4.1 Protocol

Recordの構造、型、制約、表示方法などを定義する設計図。

- LogBookごとに採用される。
- 公開、検索、取り込みが可能である。
- 取り込み後は元Protocolから独立する。
- Published Versionはimmutableとする。
- 内容の同一性をhashで検査できる。

### 4.2 Protocol Version

特定時点のProtocolを固定した版。

- Recordは必ず投稿時点のProtocol Versionを保持する。
- 過去Recordを新Versionで自動再検証しない。
- Protocol更新時は既存Versionを変更せず、新しいVersionを作成する。

### 4.3 Category（仮称）

複数のLogBookを整理・表示するための単純なグルーピング単位。

- 複数のLogBookを含むことができる。
- LogBookは0個または1個のCategoryへ所属できる。
- Categoryに所属しないLogBookも許可する。
- Protocolを持たない。
- Recordを直接持たない。
- Recordの構造、Normalize、Validate、保存処理へ介入しない。
- Categoryを削除しても配下のLogBookを削除しない。
- MVPではCategoryのネストを許可しない。

名称は仮称であり、将来的にCollectionまたはSpaceへ変更する可能性がある。内部識別子とユーザー向け表示名を過度に結合しない。

例:

```text
Category: プレオ
 ├─ 車両情報LogBook
 ├─ 給油LogBook
 ├─ 整備LogBook
 └─ 走行記録LogBook
```

### 4.4 LogBook

同じ種類・同じ構造のProtocol Recordを保存する独立したデータセット。

例:

- 車両情報LogBook
- 給油LogBook
- 整備LogBook
- 日記LogBook
- 読書LogBook

LogBook単位で公開可否を設定する。

MVPでは、一つのLogBookは同時に一つのCurrent Protocol Versionだけを使用する。異なる種類のProtocol Recordを同じLogBookへ混在させない。複数LogBookを同じ対象単位でまとめる用途はCategoryが担う。

### 4.5 Protocol Record

LogBookへ追加される一件のデータ。UI上では一枚の付箋に相当する概念を想定する。

例として給油LogBookでは、一回の給油が一Recordとなり、その中に日時、給油量、金額などを保持する。

### 4.6 Open Record（仮称）

ProtocolやLogBookを必要とせず、単一の長文bodyを中心として自由に情報を残すRecord。

- LogBookへ所属しない。
- Protocol Versionを参照しない。
- Field Builder、JSON Schema、payload、Matrixを使用しない。
- Categoryへの所属は任意とする。
- title、tags、visibility、occurredAt、source、Revisionなど、Protocol Recordと共通化できるドメイン概念は再利用する。

### 4.7 Record Revision

Recordの作成、編集、削除によって生成される変更履歴。

- Recordを編集しても過去Revisionを削除しない。
- create、update、deleteのすべてが、その時点の完全snapshotを保持する。
- 通常表示とMatrixでは最新の非削除Revisionを使用する。
- 削除は物理削除ではなく、完全snapshotを持つdelete Revisionによる論理削除とする。

### 4.8 Timeline

LogBook内で発生したRecord追加、編集、削除、Protocol変更などを時系列に表示する機能。

### 4.9 Matrix

LogBook内の最新Record値を入力とし、統計値を出力する機能。

MVP 2では単一の統計値を出力する。将来的には他LogBook参照、Record作成時の値取得、ユーザーページへの配置などへ拡張する。

## 5. 全体情報モデル

```text
User
 ├─ Profile
 ├─ Category
 │   └─ LogBook
 ├─ Protocol
 │   └─ Protocol Version
 ├─ LogBook
 │   ├─ Current Protocol Version
 │   ├─ Protocol Record
 │   │   └─ Record Revision
 │   ├─ Timeline Event
 │   └─ Matrix
 └─ Open Record
     └─ Open Record Revision
```

主要な関係は次のとおりとする。

1. Userは複数のCategory、Protocol、LogBook、Open Recordを所有できる。
2. Protocolは複数のVersionを持つ。
3. Categoryは複数のLogBookを含み、LogBookは0個または1個のCategoryへ所属する。
4. LogBookは現在使用するProtocol Versionを一つだけ持つ。
5. Protocol Recordは一つのLogBookに属する。
6. Protocol Record Revisionは作成時に使用したProtocol Versionへ固定される。
7. Open RecordはLogBookへ所属せず、Protocol Versionを参照しない。
8. Recordの現在値と削除状態はcurrent Revisionによって決まる。

## 6. Protocol仕様

### 6.1 Protocol構造

推奨する論理構造を以下に示す。

```ts
type ProtocolState = "draft" | "published"; // 将来: "withdrawn"
type ProtocolVisibility = "private" | "unlisted" | "public";

interface Protocol {
  id: string;
  ownerId: string;
  slug: string;
  visibility: ProtocolVisibility;
  createdAt: string;
  updatedAt: string;
}

interface ProtocolVersion {
  id: string;
  protocolId: string;
  version: string;
  state: ProtocolState;

  schema: JsonSchema202012;
  metadata: NovaMetadata;
  advisories: AdvisoryRule[];

  schemaHash?: string;
  contentHash?: string;
  createdAt: string;
  publishedAt?: string;
}
```

### 6.2 標準仕様との境界

Protocolの検証仕様は[JSON Schema Draft 2020-12](https://json-schema.org/draft/2020-12)を採用する。

- 型、required、値域、文字数、配列、enumなどはJSON Schemaで表現する。
- NOVA独自のValidation Rule配列を重複して持たない。
- UI表示設定、入力Widget、単位、アイコンなどはNOVA metadataとして分離する。
- attachment、imageなど外部状態を必要とする検証はNOVA固有Validatorで扱う。
- `format`はNOVAサーバー側でassertionとして有効化する。

### 6.3 Protocol Recordの厳格性

Protocolに基づくLogBookは常にStrict相当とし、Protocol modeおよびStrict/Open切り替えは持たない。

- rootおよび各groupは原則として`additionalProperties: false`とする。
- 未定義FieldはValidation Errorとして保存を拒否する。
- 未定義Fieldを別領域へ移動して保存しない。
- 自由記録はOpen Recordが担う。
- json型FieldはProtocol作者が明示的に定義したFieldであり、引き続き利用できる。

### 6.4 DraftとPublished

#### Draft

- 不完全な状態で保存できる。
- 一時的に不正な状態でも保存できる。
- 編集時はrevisionによる楽観ロックを推奨する。
- Recordの正式検証には使用しない。
- 所有者だけが閲覧できる。
- 通常の公開Protocolページには表示しない。

#### Published

- 完全に有効でなければ公開できない。
- 確定後はimmutableとする。
- 更新は新しいProtocol Versionとして公開する。
- Record検証に利用できる。
- Protocol本体のvisibilityに従って閲覧できる。
- Publishedは「一般公開済み」ではなく「変更不能な確定版」を意味する。
- privateなProtocolのPublished Versionも許可する。

将来のstateとして`withdrawn`を追加できる余地を残す。withdrawn Versionは新規LogBookへの適用および新規取り込み候補から除外するが、過去Recordの表示・解釈には引き続き使用でき、immutableとする。MVPではwithdrawnを実装しない。

### 6.5 Protocol visibility

visibilityはProtocol VersionではなくProtocol本体が持つ。Version単位のvisibilityはMVPでは実装しない。

| visibility | 閲覧 | 検索 | 他ユーザーの取り込み |
| --- | --- | --- | --- |
| private | 所有者のみ | 表示しない | 不可 |
| unlisted | URLを知るユーザー | 表示しない | 可 |
| public | 誰でも | 表示する | 可 |

- Protocolをpublicまたはunlistedからprivateへ戻すことを許可する。
- 非公開化しても、すでに他ユーザーが取り込んだ独立コピーは削除・変更しない。
- Draft共有が将来必要になった場合は、Version visibilityではなく期限付きDraft Preview Linkなどの別機能として設計する。

### 6.6 Version

- Version文字列はSemVer 2.0.0形式を採用する。
- 同一Protocol内でVersionは一意とする。
- Published Versionの内容は変更不可とする。
- Version番号の互換性判断は作者が行う。
- NOVAは将来的に推奨Version bumpを提示できるが、MVPでは自動互換性判定を行わない。

### 6.7 hash

以下の二種類を推奨する。

| hash | 対象 | 用途 |
| --- | --- | --- |
| schemaHash | schemaおよび検証動作へ影響する設定 | 検証上の同一性判定 |
| contentHash | schema、metadata、advisoriesを含む全定義 | Protocol全体の同一性判定 |

hashにはアルゴリズム名と正規化方式Versionを併記する。

JSON正規化には[RFC 8785 JSON Canonicalization Scheme](https://datatracker.ietf.org/doc/html/rfc8785)を候補とする。ただし、IEEE 754範囲外の整数や任意精度小数を扱う場合は別途数値表現を決定する必要がある。

同一Protocol内にschemaHashまたはcontentHashが一致する別Versionが存在することを許可する。同一Schemaまたは同一内容の公開はDB Errorにせず、Publish時のWarning候補とする。

### 6.8 Protocol公開時検証

公開時に最低限、以下を検証する。

- field IDの重複
- 予約語の使用
- 不正なfield ID
- 不正な正規表現
- default値と型の不一致
- `minimum > maximum`
- `minLength > maxLength`
- enum値の重複
- requiredとnullable表現の矛盾
- groupおよびarrayの不正構造
- 循環参照
- 未対応型
- 不正な表示設定
- 不正または重複したProtocol Version
- 使用を許可していないJSON Schema keyword
- 外部remote `$ref`
- Schemaの深さ、Field数、enum数などの上限超過

## 7. Field仕様

### 7.1 Field ID

Field IDはRecord payload内のproperty名として使用する。

推奨制約:

```text
^[a-z][a-zA-Z0-9_]{0,63}$
```

以下を予約語とする。

- `__proto__`
- `prototype`
- `constructor`
- `payload`
- `source`
- `id`
- `logBookId`
- `protocolId`
- `protocolVersion`
- `occurredAt`
- `receivedAt`

予約語の最終範囲は実装前に確定する。

### 7.2 対応型

MVPで以下を対象とする。

#### 基本型

- 1行テキスト
- 複数行テキスト
- 整数
- 小数
- 真偽値
- 日付
- 日時
- URL

#### 選択型

- 単一選択
- 複数選択

#### 実用型

- 単位付き数値
- 位置情報
- 添付ファイル
- 画像
- JSON

#### 構造型

- array
- group

### 7.3 JSON Schema対応

| NOVA型 | JSON Schema |
| --- | --- |
| 1行テキスト | `type: "string"` |
| 複数行テキスト | `type: "string"`＋UI metadata |
| 整数 | `type: "integer"` |
| 小数 | `type: "number"` |
| 真偽値 | `type: "boolean"` |
| 日付 | `type: "string", format: "date"` |
| 日時 | `type: "string", format: "date-time"` |
| URL | `type: "string", format: "uri"` |
| 単一選択 | `enum` |
| 複数選択 | `type: "array"`, `items.enum`, `uniqueItems` |
| 単位付き数値 | 構造化object＋NOVA metadata |
| 位置情報 | latitude、longitudeなどを持つobject |
| 添付ファイル | Asset参照object |
| 画像 | Image Asset参照object |
| JSON | 任意または制限付きJSON Schema |
| array | `type: "array"`, `items` |
| group | `type: "object"`, `properties` |

### 7.4 nullable

独自の`nullable` keywordは保存形式として使用せず、JSON Schemaのunion typeへ変換する。

```json
{
  "type": ["string", "null"]
}
```

### 7.5 Field metadata

```ts
interface NovaMetadata {
  fields: Record<string, FieldMetadata>;
  order: string[];
  recordTitle?: {
    fieldPath?: string;
    template?: string;
  };
}

interface FieldMetadata {
  kind: NovaFieldKind;
  label: string;
  description?: string;
  placeholder?: string;
  widget?: string;
  unit?: {
    allowed: string[];
    default?: string;
  };
  display?: Record<string, unknown>;
}
```

metadataのField参照にはJSON Pointerを使用する。

## 8. Validation Rule

### 8.1 単項目制約

以下をMVPで扱う。

- required
- nullable相当のunion type
- minimum
- maximum
- exclusiveMinimum
- exclusiveMaximum
- minLength
- maxLength
- pattern
- enum
- minItems
- maxItems
- uniqueItems
- default
- unit

原則としてJSON Schema keywordをそのまま使用する。`unit`はNOVA metadataと、measurement objectのSchema制約を組み合わせて表現する。

### 8.2 default

JSON Schemaの`default`は値の自動注入を意味しない。

MVPでは以下を原則とする。

- `default`はフォーム初期値の提案に使用する。
- サーバーは欠損Fieldへ自動注入しない。
- 将来自動注入する場合は、NOVA metadataで明示的に有効化する。

### 8.3 Warningルール

Warningは保存を妨げない。

想定例:

- 日時が未来
- 前回Recordより走行距離が小さい
- 通常値から大きく外れている
- 位置情報の精度が低い

Core MVPではWarning評価パイプラインを実装する。履歴比較、外れ値、位置精度などの高度なWarningは将来対応とする。未定義FieldはWarningではなくErrorである。

WarningはRecord Revision保存時点の評価結果を`validationWarnings`としてsnapshot保存する。この結果は、受信時点のProtocol、Recordデータ、周辺状態に基づく確定履歴であり、過去Revision表示時に自動再評価または上書きしない。

表示時点で再計算する参考情報が将来必要になった場合は`runtimeAdvisories`として分離する。runtimeAdvisoriesはMVP対象外とする。

### 8.4 将来の相関制約

想定例:

- `endedAt >= startedAt`
- `fullTank = true`のとき`volume`必須
- `status = error`のとき`reason`必須

任意JavaScriptは使用しない。

推奨する拡張形式:

```ts
interface CorrelationConstraint {
  id: string;
  severity: "error" | "warning";
  expressionLanguage: "nova-expr-v1";
  expression: ExpressionNode;
  code: string;
  messageKey: string;
}
```

式は型検査可能なASTとして保存し、許可された演算子のみ実行する。式言語自体にVersionを持たせる。

## 9. Normalize仕様

### 9.1 責務

Normalizeは、入力チャネル固有の表現を、Protocol検証可能な共通JSON表現へ変換する。

許可する変換は、意味が一意に決まるものに限定する。

許可例:

- `"24.3"`から`24.3`
- `"true"`から`true`
- `"false"`から`false`
- RFC 3339文字列から正規化されたdatetime文字列
- Field設定で許可されている場合の空文字から`null`

禁止例:

- `"yes"`から`true`
- `"24km"`から`24`
- `"tomorrow"`からdatetime
- Localeを推測した曖昧な日付変換
- 単位を推測した変換

### 9.2 インターフェース

```ts
interface Normalizer {
  normalize(
    input: unknown,
    protocol: ProtocolVersion
  ): NormalizationResult;
}

interface NormalizationResult {
  success: boolean;
  value?: NormalizedProtocolRecordInput;
  errors: ValidationIssue[];
  transformations: NormalizationTransformation[];
}
```

Normalizeに失敗した場合はStoreへ進まない。

Open RecordはProtocol用Normalizerとは別の`OpenRecordNormalizer`を使用する。title、body、tags、occurredAt、visibility、sourceの共通表現だけをNormalizeし、Field変換やJSON Schema変換は行わない。bodyの内容を推測、要約、Markdown変換してはならない。

## 10. Validate仕様

### 10.1 責務

ValidateはNormalize済みデータに対して、以下を行う。

1. Record envelope検証
2. Protocol Versionの存在確認
3. Protocol VersionがRecord受付可能な状態か確認
4. JSON Schemaによるpayload検証
5. NOVA固有型の検証
6. Asset参照の存在、所有権、状態確認
7. 未定義Fieldが存在しないことの確認
8. Warning評価とsnapshot生成

### 10.2 Error

Recordを保存できない問題。

- 型不一致
- 必須項目不足
- 値域外
- enum不一致
- 不明なProtocol Version
- 不正なデータ構造
- 不正なAsset参照
- 未定義Field
- payloadサイズなど安全上限の超過

### 10.3 Warning

保存可能だが確認を促す問題。

- 異常値
- 未来日時
- 前回値との不自然な差
- 位置情報の低精度

### 10.4 ValidationResult

```ts
type JsonPointer = string;

interface ValidationIssue {
  severity: "error" | "warning";
  path: JsonPointer;
  schemaPath?: JsonPointer;
  rule: string;
  code: string;
  messageKey: string;
  params?: Record<string, JsonValue>;
  expected?: JsonValue;
  actual?: JsonValue;
  message?: string;
}

interface BaseValidationResult {
  valid: boolean;
  errors: ValidationIssue[];
  warnings: ValidationIssue[];
}

interface ProtocolValidationResult extends BaseValidationResult {
  protocolVersionId: string;
}

interface OpenRecordValidationResult extends BaseValidationResult {
  protocolVersionId?: never;
}
```

`path`には[RFC 6901 JSON Pointer](https://datatracker.ietf.org/doc/rfc6901/)を使用する。

Field検証Errorの`path`はpayload相対とし、`/odometer`のように表現する。Record envelopeのErrorは`/occurredAt`のように表現する。この基準はすべての入力経路で共通とする。

### 10.5 多言語対応

- `code`は機械処理用の安定識別子とする。
- `rule`は違反したSchemaまたはNOVAルールを表す。
- `messageKey`は翻訳辞書のキーとする。
- `params`は翻訳文へ埋め込む値を持つ。
- `message`は要求言語に応じて生成する表示用文字列とする。
- クライアントは`message`の文面を条件分岐に使用してはならない。

### 10.6 エラー例

```json
{
  "valid": false,
  "protocolVersionId": "pv_01...",
  "errors": [
    {
      "severity": "error",
      "path": "/odometer",
      "schemaPath": "/properties/odometer/minimum",
      "rule": "minimum",
      "expected": 0,
      "actual": -1,
      "code": "value_too_small",
      "messageKey": "validation.value_too_small",
      "params": { "minimum": 0 },
      "message": "0以上で入力してください"
    }
  ],
  "warnings": []
}
```

## 11. Record仕様

### 11.1 Record入力・保存項目

最低限、以下を保持する。

```ts
interface StoredProtocolRecordRevision {
  id: string;
  recordId: string;
  logBookId: string;
  protocolId: string;
  protocolVersion: string;
  protocolVersionId: string;
  revisionNumber: number;
  parentRevisionId?: string;
  operation: "create" | "update" | "delete";
  occurredAt: string;
  receivedAt: string;
  payload: Record<string, JsonValue>;
  source: RecordSource;
  validationWarnings: ValidationIssue[];
}
```

### 11.2 occurredAtとreceivedAt

- `occurredAt`: 実際にイベントが発生した日時
- `receivedAt`: NOVAがRecordを受信した日時

datetimeはRFC 3339形式を使用する。

### 11.3 payload

```json
{
  "payload": {
    "odometer": 142381
  }
}
```

- payloadにはProtocolで定義されたFieldのみを保存する。
- 未定義FieldはErrorとし、保存しない。
- Protocol Recordにextensionsは持たせない。
- 自由記録が必要な場合はOpen Recordを使用する。

### 11.4 source

入力元に依存しない共通形式を定義する。

```ts
interface RecordSource {
  type: "web" | "api" | "csv" | "webhook" | "system";
  integrationId?: string;
  importId?: string;
  externalId?: string;
  metadata?: Record<string, JsonValue>;
}
```

sourceへ秘密情報、Webhook署名、アクセストークンなどを保存してはならない。

### 11.5 Open Record

Open RecordはProtocol Recordとは別の保存モデルおよびAPIで扱う。

```ts
type RecordVisibility = "private" | "unlisted" | "public";

interface OpenRecordRevision {
  id: string;
  recordId: string;
  ownerId: string;
  categoryId?: string;
  revisionNumber: number;
  parentRevisionId?: string;
  operation: "create" | "update" | "delete";
  title?: string;
  body: string;
  tags?: string[];
  visibility: RecordVisibility;
  occurredAt: string;
  receivedAt: string;
  source: RecordSource;
  validationWarnings: ValidationIssue[];
}

interface OpenRecord {
  id: string;
  ownerId: string;
  categoryId?: string;
  currentRevisionId: string;
  createdAt: string;
}
```

必須要件:

- bodyは必須の単一長文テキストとする。
- titleとtagsは任意とする。
- Categoryへの所属は任意とする。
- Protocol、Protocol Version、LogBook、Field定義、JSON Schema、payload、Matrix、LogBook Timelineを使用しない。
- Open Record固有のRevision履歴表示は持つことができるが、LogBook Timelineへ混在させない。
- create、update、delete RevisionはProtocol Recordと同じ完全snapshot方式を採用する。
- 更新時はProtocol Recordと同じ`baseRevisionId`による楽観ロックを行う。
- Core MVPではprivateを実装し、unlistedとpublicはMVP 1で追加する。
- Asset参照は共通Asset機能としてMVP 2以降に追加できる。

## 12. Record履歴

Protocol RecordとOpen Recordの編集はGitに類似したRevision方式とする。すべてのRevisionは差分ではなく、そのRevision時点の完全snapshotを保持する。

1. create Revisionは作成時点の本文、sourceなどを完全保存する。
2. update Revisionは更新後の完全snapshotを保存し、差分だけを保存しない。
3. delete Revisionは削除直前の完全snapshotを保持し、空のtombstoneにしない。
4. Protocol Recordのdelete Revisionは削除直前Revisionと同じProtocol Versionを引き継ぐ。
5. `records.current_revision_id`および`open_records.current_revision_id`はdelete Revisionも指すことができる。
6. 通常一覧とMatrixは、current RevisionのoperationがdeleteであるRecordを除外する。
7. Timelineと履歴画面では全Revisionを確認できる。
8. Protocol Record Revisionは検証に使用したProtocol Versionを保持する。
9. 保存時のWarningは各Revisionの`validationWarnings`へsnapshot保存し、自動再評価しない。

完全snapshot方式により、Revision単体での表示、過去Protocol Versionによる解釈、復元を単純にする。Asset本体はpayloadへ埋め込まず参照だけを保持するため、Revisionごとの容量増加を抑える。

Protocol変更後に古いRecordを編集する場合、新Revisionは編集時に選択されたProtocol Versionで検証し、そのVersionへ固定する。具体的な編集UIとVersion選択方針は未決事項とする。

### 12.1 Record更新の楽観ロック

Protocol RecordおよびOpen Recordの更新・削除APIは`baseRevisionId`を必須とする。

更新時に`current_revision_id == baseRevisionId`をTransaction内で確認する。一致しない場合は`409 Conflict`を返し、新RevisionおよびTimeline Eventを作成しない。レスポンスには現在のRevision IDを含めることができる。

MVPでは履歴を一本道に限定する。`UNIQUE(record_id, revision_number)`をDBで保証し、parent Revisionの分岐はapplication層のlockと`baseRevisionId`検査によって防止する。将来branchを許可できるよう、`parent_revision_id`自体にはDB UNIQUEを設定しない。

## 13. Record保存フロー

すべての入力経路で以下を共有する。

```text
Input
  ↓
Request envelope・認証・認可・サイズ検査
  ↓
Protocol Version取得
  ↓
Normalize
  ↓
Validate
  ↓
Warning評価
  ↓
Errorあり ──→ 422、保存しない
  ↓
Transaction Store
  ↓
201＋Warning
```

Protocol Record作成の詳細手順:

1. Content-Typeとリクエストサイズを検査する。
2. JSONとしての構造を検査する。
3. UserがLogBookへRecordを追加できるか認可する。
4. 指定されたProtocol Versionを取得する。
5. LogBookの現在Versionとの競合を確認する。
6. Normalizeする。
7. JSON Schema検証を行う。
8. NOVA固有検証を行う。
9. Warningを評価し、validationWarnings snapshotを生成する。
10. Errorがあれば全件を返して保存しない。
11. RecordとRevisionをTransactionで保存する。
12. Timeline Eventを保存する。
13. 保存結果とWarningを返す。

更新・削除時は次の順序をTransaction内で実行する。

1. Recordまたはcurrent Revisionをlockする。
2. `baseRevisionId`と`current_revision_id`の一致を確認する。
3. 不一致ならRollbackし、409を返す。
4. Normalizeする。
5. Validateする。
6. 完全snapshotの新Revisionを挿入する。
7. `current_revision_id`を新Revisionへ更新する。
8. Timeline Eventを挿入する。
9. Commitする。

Open RecordはProtocol Version取得とJSON Schema検証を行わず、Open Record固有のNormalize・Validateを通した後、同じRevision／楽観ロック手順で保存する。

## 14. API仕様案

### 14.1 Record作成

```http
POST /api/logbooks/{logBookId}/records
Content-Type: application/json
```

```json
{
  "protocolVersionId": "pv_01...",
  "occurredAt": "2026-07-20T10:30:00+09:00",
  "payload": {},
  "source": {
    "type": "web"
  }
}
```

### 14.2 Protocol Record更新・削除

```http
PATCH /api/logbooks/{logBookId}/records/{recordId}
DELETE /api/logbooks/{logBookId}/records/{recordId}
```

```json
{
  "baseRevisionId": "rev_01...",
  "occurredAt": "2026-07-20T10:30:00+09:00",
  "payload": {},
  "source": {
    "type": "web"
  }
}
```

DELETEでも`baseRevisionId`をrequest bodyまたは明示的なconditional headerで必須とする。MVPの具体的なtransport表現はAPI契約確定時に統一する。

競合レスポンス例:

```json
{
  "code": "revision_conflict",
  "baseRevisionId": "rev_old",
  "currentRevisionId": "rev_current"
}
```

### 14.3 Open Record

```http
POST /api/open-records
PATCH /api/open-records/{recordId}
DELETE /api/open-records/{recordId}
```

```json
{
  "baseRevisionId": "rev_01...",
  "title": "任意の題名",
  "body": "自由に残す長文",
  "tags": ["memo"],
  "categoryId": "cat_01...",
  "visibility": "private",
  "occurredAt": "2026-07-20T10:30:00+09:00",
  "source": {
    "type": "web"
  }
}
```

作成時は`baseRevisionId`を送信しない。更新・削除時は必須とする。

### 14.4 HTTP status

| status | 用途 |
| --- | --- |
| 201 | Warningを含む場合も含め保存成功 |
| 400 | 不正JSON、不正なrequest envelope |
| 401 | 未認証 |
| 403 | 権限不足 |
| 404 | LogBookなどの対象が存在しない |
| 409 | Protocol Version競合、`baseRevisionId`競合 |
| 413 | payloadまたは添付が上限超過 |
| 422 | NormalizeまたはValidate失敗 |

### 14.5 成功レスポンス案

```json
{
  "record": {
    "id": "rec_01...",
    "revisionId": "rev_01...",
    "revisionNumber": 1,
    "protocolVersionId": "pv_01...",
    "occurredAt": "2026-07-20T10:30:00+09:00",
    "receivedAt": "2026-07-20T01:30:01Z",
    "payload": {}
  },
  "validation": {
    "valid": true,
    "errors": [],
    "warnings": []
  }
}
```

## 15. Category仕様

### 15.1 基本機能

```ts
interface Category {
  id: string;
  ownerId: string;
  name: string;
  description?: string;
  visibility: "private" | "unlisted" | "public";
  sortOrder: number;
  createdAt: string;
  updatedAt: string;
}
```

- Categoryは所有者、名前、説明、visibility、表示順を持つ。
- Categoryは複数のLogBookを含むことができる。
- LogBookはCategoryなしでも存在できる。
- 一つのLogBookが同時に所属できるCategoryは最大一つとする。
- MVPではCategoryのネストを許可しない。
- CategoryはProtocolまたはRecordを直接持たない。
- CategoryはProtocol RecordのNormalize、Validate、Storeへ一切影響しない。

### 15.2 削除

Category削除時は、配下LogBookの`category_id`を`null`へ更新してからCategoryを削除する。LogBook、Record、Protocol Version、Matrix、Timeline Eventは削除しない。DBの外部キーは`ON DELETE SET NULL`を使用する。

### 15.3 Open Recordとの関係

Open Recordも任意でCategoryへ所属できる。現在所属は`open_records.category_id`で管理する。Category削除時は現在所属を`null`へ設定し、Open Record自体と過去Revisionは削除・更新しない。各Open Record Revisionの`categoryId`は保存時点の所属を示す履歴snapshotとして保持する。

## 16. LogBook仕様

### 16.1 基本機能

```ts
interface LogBook {
  id: string;
  ownerId: string;
  categoryId?: string;
  name: string;
  description?: string;
  visibility: "private" | "unlisted" | "public";
  currentProtocolVersionId: string;
  createdAt: string;
  updatedAt: string;
}
```

- Protocolを取り込んで作成できる。
- 取り込んだProtocolは元Protocolから独立する。
- LogBookごとに公開可否を設定できる。
- 現在使用するProtocol Versionを持つ。
- Record Timelineを持つ。
- Matrixを持つ。
- 0個または1個のCategoryへ所属できる。
- 同じ種類・同じ構造のProtocol Recordだけを含む。
- 同時に使用するCurrent Protocol Versionは一つだけとする。

### 16.2 Protocol変更

Protocol変更は新しいProtocol Versionとして扱う。

- 変更そのものをTimeline Eventとして保存・表示する。
- 既存Recordは元のProtocol Versionを参照し続ける。
- 過去Recordを新Versionで自動再検証しない。
- 新しいRecordは新Versionで検証する。

## 17. Protocol公開・検索・取り込み

### 17.1 公開

Protocolは公開範囲を持つ。

- private: 所有者だけが閲覧でき、検索および取り込みの対象外とする。
- unlisted: URLを知るユーザーが閲覧・取り込みできるが、検索へ表示しない。
- public: 誰でも閲覧・取り込みでき、検索へ表示する。

visibilityはProtocol本体へ設定し、すべてのPublished Versionへ適用する。DraftはProtocol visibilityにかかわらず所有者以外へ表示しない。publicまたはunlistedからprivateへ変更した後は新規外部閲覧および取り込みを拒否する。

### 17.2 検索

公開Protocolをジャンルで検索できる。

MVPの検索対象候補:

- Protocol名
- 説明
- ジャンル
- 作者
- Field名

### 17.3 取り込み

- 取り込み時点のPublished Versionをコピーする。
- コピー後は元Protocolから独立する。
- 元作者の更新を自動反映しない。
- schemaHashにより同一性を判定できる。
- fork元や作者への帰属表示は未決事項とする。
- 元Protocolが後からprivateになっても、取り込み済みコピーは引き続き利用できる。
- 取り込み後のコピーは元Protocolから独立し、元Protocolの削除・変更・visibility変更の影響を受けない。

### 17.4 初期作例

NOVA運営側は、Protocolの仕組みと「すべてを同じ仕組みで記録できる」ことを示す作例を提供する。

初期候補:

- 車両情報
- 給油記録
- 整備記録
- 日記
- 読書記録

作例は特別な組み込み機能を使用せず、一般ユーザーが作成するProtocolと同じ機能だけで構成する。ユーザーが必要なProtocolを自由に作成・公開できることを基本姿勢とする。

## 18. Matrix仕様

### 18.1 MVP 2

MVP 2では、LogBookの最新有効Record Revisionを入力として、単一統計値を出力する。

初期集計候補:

- count
- sum
- average
- minimum
- maximum

Matrix定義は将来集計項目を追加できる拡張構造にする。

```ts
interface MatrixDefinition {
  id: string;
  logBookId: string;
  name: string;
  operation: "count" | "sum" | "average" | "minimum" | "maximum";
  fieldPath?: string;
}
```

### 18.2 将来拡張

- 絞り込み条件
- 日付範囲
- group by
- 表、グラフ
- ユーザープロフィールへの配置
- 他LogBookでの利用
- 他LogBookのRecord値参照
- Record作成時の値取得

## 19. 公開ページ

公開ページはMVP 1以降で実装する。Core MVPの公開範囲はprivateのみとする。

### 19.1 プロフィール

- 表示名
- 自己紹介
- 公開Categoryおよび公開LogBook一覧
- 公開Open Record一覧

Protocolに基づく構造化記録とOpen Recordを別セクションとして表示できる設計にする。

### 19.2 公開CategoryとLogBook

- LogBook名
- 説明
- 使用Protocolの情報
- 公開対象RecordのTimelineまたは一覧
- Matrix統計値は実装進行に応じて追加可能

Record単位の細かな公開範囲はMVP必須とせず、LogBook単位で公開可否を判断する。

### 19.3 公開Open Record

- Open Recordのtitle、body、tags、occurredAtを表示する。
- current RevisionがdeleteのOpen Recordは表示しない。
- unlistedはURLを知るユーザーだけが閲覧でき、公開一覧へ表示しない。
- publicはプロフィールの公開Open Recordセクションへ表示できる。

## 20. データベース設計案

データベースはMySQL 8.0以上またはMariaDB 10.11以上を対象とする。PostgreSQL固有機能および`jsonb`へ依存しない。可変構造データだけをLaravel Migrationの`json`カラムへ保存し、MySQLとMariaDBの内部実装差をアプリケーション層から意識しない設計とする。

検索、unique制約、relation、並び替え、公開範囲、現在Revision、Version、hashなど、Core MVPで問い合わせに使用する値は通常カラムとして保持する。JSON内部への高度なDB query、generated column、関数index、DB固有JSON indexをCore MVPの必須要件にしない。

### 20.1 protocols

```text
id
owner_id
slug
visibility
created_at
updated_at
```

### 20.2 protocol_versions

```text
id
protocol_id
version
state
schema json
metadata json
advisories json
schema_hash
content_hash
hash_algorithm
canonicalization_version
revision
created_at
published_at
```

主要制約:

```text
UNIQUE(protocol_id, version)
```

schemaHashおよびcontentHashの一致は許可し、検索用INDEXだけを作成する。

```sql
CREATE INDEX protocol_versions_schema_hash_idx
    ON protocol_versions (schema_hash);

CREATE INDEX protocol_versions_content_hash_idx
    ON protocol_versions (content_hash);
```

### 20.3 categories

```text
id
owner_id
name
description
visibility
sort_order
created_at
updated_at
```

CategoryはProtocolまたはRecordへの外部キーを持たず、整理・表示だけを担う。

### 20.4 logbooks

```text
id
owner_id
category_id nullable
name
description
visibility
current_protocol_version_id
created_at
updated_at
```

`category_id`はcategoriesを参照し、Category削除時は`ON DELETE SET NULL`とする。

### 20.5 records

Protocol Recordのidentityと現在Revisionを保持する。

```text
id
logbook_id
current_revision_id
created_at
```

### 20.6 record_revisions

```text
id
record_id
revision_number
parent_revision_id
protocol_version_id
operation
occurred_at
received_at
payload json
source json
validation_warnings json
actor_id
created_at
```

主要制約:

```text
UNIQUE(record_id, revision_number)
```

create、update、deleteの全行が完全snapshotである。delete行も削除直前のpayload、source、validationWarningsを保持し、削除直前Revisionと同じprotocol_version_idを引き継ぐ。`records.current_revision_id`はdelete Revisionを参照できる。

MVPでは履歴を一本道に限定するが、将来branchを許可できるよう`parent_revision_id`へDB UNIQUEは設定せず、Transaction内のlockと`baseRevisionId`検査で分岐を防ぐ。

### 20.7 open_records

```text
id
owner_id
category_id nullable
current_revision_id
created_at
```

`category_id`は現在の整理上の所属を表し、Category削除時に`ON DELETE SET NULL`とする。

### 20.8 open_record_revisions

```text
id
record_id
category_id_snapshot nullable
revision_number
parent_revision_id
operation
title nullable
body text
tags json nullable
visibility
occurred_at
received_at
source json
validation_warnings json
actor_id
created_at
```

主要制約:

```text
UNIQUE(record_id, revision_number)
```

`category_id_snapshot`は保存時点のCategory IDを保持する履歴値であり、categoriesへの削除連動FKを設定しない。Category削除後も過去Revisionを書き換えない。すべてのRevisionは完全snapshotであり、`open_records.current_revision_id`はdelete Revisionを参照できる。

### 20.9 logbook_events

```text
id
logbook_id
event_type
record_revision_id nullable
protocol_version_id nullable
actor_id
occurred_at
created_at
metadata json
```

### 20.10 matrices

```text
id
logbook_id
name
definition json
created_at
updated_at
```

### 20.11 MySQL／MariaDB互換規則

- すべてのテーブルはInnoDB相当のtransactionおよびrow lockを利用できる構成とする。
- 文字コードは`utf8mb4`を使用する。
- MigrationはLaravel Schema Builderで表現し、MySQLとMariaDBで意味が異なるDDLを避ける。
- JSONカラムのdefault値、generated column、JSON path index、DB固有関数をCore MVPのMigrationで使用しない。
- JSON値の妥当性、Protocol Schema、Record payloadの意味検証はDomain／Application層で完了してから保存する。
- unique制約対象はJSON内へ置かず通常カラムへ展開する。
- ID、外部キー、Version、visibility、state、operation、revision_number、occurred_at、received_at、hashは通常カラムとする。
- `baseRevisionId`競合防止では、LaravelのDB transaction内で対象Recordを`lockForUpdate()`相当によって取得し、current Revision一致を検査する。
- Migrationとintegration testをMySQL 8系およびMariaDB 10.11系の両方で実行する。

## 21. セキュリティ要件

### 21.1 ProtocolおよびSchema

- 外部URLを参照するremote `$ref`を禁止する。
- Protocol内部の`$defs`だけを許可する。
- 循環参照を公開時に拒否する。
- 任意JavaScriptを実行しない。
- 対応JSON Schema keywordをallowlist化する。
- Schema深度、Field数、enum数、array上限を設ける。
- 正規表現はRE2互換または実行時間を制限できる実装を使う。

### 21.2 Record

- リクエスト本文、Protocol Record payload、Open Record body、文字列、配列へ上限を設ける。
- `NaN`と`Infinity`を許可しない。
- prototype pollutionにつながるField IDを禁止する。
- `actual`をErrorへ含める際はサイズ制限と秘匿処理を行う。
- LogBook、Record、Assetへの認可を毎回確認する。

### 21.3 URL

- URLバリデーション時に対象URLへアクセスしない。
- 将来URLプレビューを実装する場合はSSRF対策を別途行う。

### 21.4 添付ファイルと画像

- Record本文へバイナリを直接埋め込まない。
- アップロード済みAsset IDを保存する。
- MIME typeをクライアント申告だけで信用しない。
- マルウェア検査完了前のAssetをRecordへ確定しない。
- ファイルサイズ、画像寸法、形式をサーバー側で検査する。

### 21.5 API、CSV、Webhook

- APIへRate limitを設ける。
- CSV exportではSpreadsheet formula injectionへ対処する。
- Webhookは署名、timestamp、nonceでリプレイを防止する。
- アクセストークンや秘密値をRecord sourceへ保存しない。

### 21.6 NOVA JSON交換形式

- 拡張子、Content-Type、ファイル名だけを信用せず、構文解析後の`$nova`とEnvelopeを検証する。
- 未知の`$nova`とformatVersionを推測処理せず拒否する。
- JSONの最大byte数、最大深度、配列要素数、entry数を制限する。
- duplicate property、prototype pollution候補キー、不正Unicodeを拒否する。
- contentHashをImport前に検証する。
- `.nva`のAsset referenceへ自動でネットワークアクセスしない。
- metadata、sourceInstance、generatorを権限判断や所有者判断に使用しない。
- Import対象のIDをそのまま信頼せず、所有権と衝突を検査して内部IDへmappingする。
- Import全体を事前検証し、失敗時に中途半端なRecordやProtocolを残さない。
- 将来ZIPコンテナを導入する場合はZip Slip、展開後容量、entry数、圧縮率、symlinkを検査する。

## 22. 非機能要件

### 22.1 一貫性

- RecordとRevision、Timeline Eventは同一Transactionで保存する。
- Record更新時はcurrent Revisionをlockし、`baseRevisionId`一致をTransaction内で検査する。
- Published Protocol VersionはDBレベルでも更新禁止にする。
- Recordが存在しないProtocol Versionを参照できないようにする。
- Category削除でLogBookおよびOpen Recordを削除しない。

### 22.2 性能

- コンパイル済みValidatorをschemaHash単位でキャッシュする。
- Timeline用に`logbook_id, occurred_at`へ索引を設ける。
- 現在Revision参照を直接保持し、通常表示で履歴全件走査を避ける。
- Matrixは初期段階ではオンデマンド計算とし、必要になった時点でキャッシュまたは集計テーブルを導入する。

### 22.3 監査性

- Record Revisionを保持する。
- Protocol変更をTimelineに記録する。
- Record Revisionへactorとsourceを保持する。
- create、update、deleteの各Revisionへ完全snapshotを保持する。
- 保存時点のWarningをvalidationWarningsとしてRevisionへ固定する。
- receivedAtはサーバー時刻で設定し、クライアントに変更させない。

### 22.4 多言語

- Validation codeと翻訳文を分離する。
- Protocolのユーザー入力label・descriptionの多言語化は将来課題とする。

### 22.5 実行プロセス

- Redisを必須にしない。
- 常駐Queue Workerを必須にしない。
- WebSocket serverおよび常駐Realtime processを必須にしない。
- Queueの標準driverは`database`とする。
- Queue処理はcronから起動される短命な`queue:work --stop-when-empty`で処理できるようにする。
- Schedulerはcronから毎分`php artisan schedule:run`を実行する。
- 共有レンタルサーバーの実行時間および負荷制限を考慮し、一つのJobを小さく再実行可能に設計する。
- Realtime更新が必要な画面はCore MVPでは通常HTTP requestまたはpollingで実現する。

### 22.6 Laravel抽象化とローカル動作

- filesystem、mail、queue、cacheはLaravelのContractまたは抽象化を通して利用する。
- Core DomainからLaravel Facadeを直接呼び出さない。
- filesystemは`local` driverだけでも全Core MVP機能が動作する。
- cacheは`file`または`database` driverで動作し、Redis固有lockへ依存しない。
- sessionは`file`または`database` driverを利用できる。
- mail transportは環境設定で切り替え、Domain層はMailer実装を知らない。
- Queueを利用できない開発・保守環境では`sync` driverへ切り替えられるようにする。

## 23. 段階的MVPスコープ

最初のVertical Sliceと初回公開版を分離し、次の段階で実装する。

### 23.1 Core MVP — 最初のVertical Slice

- アカウント
- Protocol Builder
- Protocol Draft／Publish
- immutableなProtocol Version
- Protocol visibilityのprivate
- JSON Schema Draft 2020-12への変換・検証
- 基本Field型
- 単一選択
- group
- array
- json型
- LogBook作成
- Category
- Protocol Record作成・編集・論理削除
- Protocol Record Revisionの完全snapshot
- `baseRevisionId`による楽観ロック
- NormalizeとValidateの分離
- 全Error収集
- validationWarnings snapshot
- Webフォーム
- Open Record
- Open Record Revisionの完全snapshot
- Open Recordのprivate visibility

### 23.2 MVP 1 — 初回公開版

- REST API
- CSV import／export
- Timeline
- public／unlisted visibility
- 公開プロフィール
- 公開Category／LogBook
- 公開Open Record
- measurement
- location
- multiEnum
- URL
- `.nvp`、`.nvl`、`.nvr`、`.nvo`のJSON export／import

### 23.3 MVP 2

- 共通Asset基盤
- 画像・添付ファイル
- Protocol公開・検索・取り込み
- Matrixの基本統計
- 高度な公開ページ
- `.nva` JSON ArchiveとAsset manifest

### 23.4 MVP後

- 相関制約の実行
- 前回Recordとの比較Warning
- 外れ値検出
- 高度な位置情報Warning
- runtimeAdvisories
- 他LogBook参照
- Record作成時の他LogBook値取得
- Protocol間の自動互換性判定
- 自動Migration
- withdrawn Protocol Version
- remote `$ref`
- 任意カスタム型・プラグイン
- Matrixの絞り込み、group by、グラフ
- Protocol更新の自動追従
- Draft Preview Link

## 24. NOVA JSON交換形式

NOVAは、Protocol、LogBook、Protocol Record、Open Recordおよび複数データのArchiveに対して、JSONベースの独自エクスポート形式を定義する。NOVA本体のVersionとエクスポート形式の`formatVersion`は独立してVersion管理する。

### 24.1 ファイル種別

| 拡張子 | `$nova` | 内容 | MIME type |
| --- | --- | --- | --- |
| `.nvp` | `protocol` | NOVA Protocol | `application/vnd.nova.protocol+json` |
| `.nvl` | `logbook` | NOVA LogBook | `application/vnd.nova.logbook+json` |
| `.nvr` | `record` | NOVA Protocol Record | `application/vnd.nova.record+json` |
| `.nvo` | `open-record` | NOVA Open Record | `application/vnd.nova.open-record+json` |
| `.nva` | `archive` | NOVA JSON Archive | `application/vnd.nova.archive+json` |

拡張子とMIME typeは判定の補助情報とし、ファイル本文の`$nova`を正とする。拡張子から想定した種別と`$nova`が異なる場合、汎用Importerは本文に従って種別を判定したうえで`file_extension_mismatch` Warningを表示する。特定種別だけを受け付けるAPIでは`file_type_mismatch` Errorとして拒否する。

### 24.2 共通Envelope

すべての形式は次のEnvelopeを持つ。

```ts
type NovaFileKind =
  | "protocol"
  | "logbook"
  | "record"
  | "open-record"
  | "archive";

interface NovaContentHash {
  algorithm: "sha-256";
  canonicalization: string;
  value: string;
}

interface NovaExportEnvelope<TKind extends NovaFileKind, TData> {
  $nova: TKind;
  formatVersion: string;
  exportedAt: string;
  data: TData;

  generator?: {
    name: string;
    version: string;
  };
  contentHash?: NovaContentHash;
  sourceInstance?: string;
  metadata?: Record<string, JsonValue>;
}
```

必須項目:

- `$nova`: 本文のファイル種別識別子
- `formatVersion`: エクスポート形式のVersion
- `exportedAt`: RFC 3339形式の出力日時
- `data`: 種別固有データ

推奨項目:

- `generator`: 出力した実装名とNOVA本体Version
- `contentHash`: 改ざん・破損検知用hash
- `sourceInstance`: 出力元Instanceの識別情報
- `metadata`: 交換形式の意味を変更しない補助情報

`generator.version`はNOVA本体または生成実装のVersionであり、`formatVersion`とは連動させない。`contentHash`は`$nova`、`formatVersion`、`data`を規定の方法でcanonicalizeした値を対象とし、`exportedAt`、generator、sourceInstance、metadataおよびcontentHash自身は対象外とする。

### 24.3 Versionと安全な判定

- `formatVersion`は`MAJOR.MINOR.PATCH`形式とし、エクスポート形式の変更だけで更新する。
- NOVA本体Versionが更新されても、交換形式が変わらなければformatVersionを変更しない。
- ImporterはJSONを構文解析した後、最初にEnvelopeを検証する。
- 未対応の`$nova`を推測または別形式として処理せず、`unsupported_nova_file_type`で拒否する。
- 未知または未対応の`formatVersion`をbest effortで読み込まず、`unsupported_format_version`で拒否する。
- 対応Versionは実装内の明示的な互換性表で管理する。
- contentHashが存在する場合はImport前に検証し、不一致なら`content_hash_mismatch`で拒否する。
- sourceInstance、generator、metadataは信頼済み情報として扱わない。
- Envelope検証後に、`$nova`ごとのdata Schemaで全件検証する。

### 24.4 `.nvp` — Protocol

`.nvp`はProtocol本体と、選択されたProtocol Versionを保持する。

```ts
interface NovaProtocolExportData {
  protocol: Protocol;
  versions: ProtocolVersion[];
}
```

- 各Published Versionは完全なschema、metadata、advisories、hashを含む。
- 所有者によるbackupではDraftを含めてもよい。
- 外部共有用exportではDraftを既定で除外する。
- Import時はPublished Versionのimmutabilityとhashを再検証する。

### 24.5 `.nvl` — LogBook

`.nvl`はLogBook、使用Protocol Version、Protocol Recordおよび全Revision履歴を保持できる。

```ts
interface NovaLogBookExportData {
  logBook: ExportedLogBook;
  category?: ExportedCategoryReference;
  protocol: {
    protocol: Protocol;
    versions: ProtocolVersion[];
    currentProtocolVersionId: string;
  };
  records: Array<{
    record: ExportedProtocolRecord;
    revisions: StoredProtocolRecordRevision[];
  }>;
  matrices?: MatrixDefinition[];
}
```

- `versions`にはCurrent Protocol Versionと、含まれるRevisionが参照するすべてのProtocol Versionを含める。
- 各Recordはidentityと全Revisionを保持し、current Revisionを特定できる。
- delete Revisionも完全snapshotとして含める。
- Categoryは整理情報の参照snapshotであり、Record検証へ影響しない。
- MatrixはMVP 2以降で任意に含められる。

### 24.6 `.nvr` — Protocol Record

`.nvr`は一つのProtocol Record、全Revision履歴、および解釈に必要なProtocol Versionを保持する。

```ts
interface NovaRecordExportData {
  logBook?: ExportedLogBookReference;
  record: ExportedProtocolRecord;
  revisions: StoredProtocolRecordRevision[];
  protocol: {
    protocol: Protocol;
    versions: ProtocolVersion[];
  };
}
```

Revisionが参照するProtocol Versionを欠いた`.nvr`は不完全としてImportを拒否する。LogBook情報は参照snapshotであり、Import先で既存LogBookへ関連付けるか、新規LogBookを作成するかを選択できる。

### 24.7 `.nvo` — Open Record

`.nvo`はLogBookおよびProtocolに属さない一つのOpen Recordと全Revision履歴を保持する。

```ts
interface NovaOpenRecordExportData {
  record: ExportedOpenRecord;
  revisions: OpenRecordRevision[];
  category?: ExportedCategoryReference;
}
```

- Protocol、Protocol Version、LogBook、payloadを含めない。
- create、update、deleteの完全snapshotを含める。
- Categoryは任意の整理情報として扱う。

### 24.8 `.nva` — JSON Archive

`.nva`は複数種別のNOVAデータをまとめるJSON Archiveである。

```ts
interface NovaArchiveExportData {
  entries: NovaArchiveEntry[];
  assets?: AssetManifestEntry[];
}

interface NovaArchiveEntry {
  id: string;
  $nova: Exclude<NovaFileKind, "archive">;
  formatVersion: string;
  data: JsonValue;
  contentHash?: NovaContentHash;
}

interface AssetManifestEntry {
  assetId: string;
  fileName: string;
  mediaType: string;
  size: number;
  sha256: string;
  reference?: string;
}
```

- entriesはProtocol、LogBook、Protocol Record、Open Recordを混在できる。
- 各entryも種別とformatVersionを明示し、個別に検証可能とする。
- Asset本体をbase64でJSONへ埋め込まない。
- assetsはAssetの識別子、metadata、hash、外部またはコンテナ内参照を持つmanifestとする。
- JSON `.nva`単体にAsset本体が存在しない場合、Importerは参照解決方針をユーザーへ提示し、暗黙に外部URLへアクセスしない。

### 24.9 将来の添付込みコンテナ

`.nva`は常にJSON文書とし、同じ拡張子へZIPを格納しない。添付ファイル込みのZIPコンテナを将来導入する場合は、別拡張子、別MIME type、magic bytes、およびmanifest配置を定めた明示的なコンテナ仕様を新設する。JSON `.nva`とZIPコンテナを拡張子だけで曖昧に判別する設計は禁止する。

### 24.10 Export／Import API案

```http
GET  /api/protocols/{protocolId}/export
GET  /api/logbooks/{logBookId}/export
GET  /api/logbooks/{logBookId}/records/{recordId}/export
GET  /api/open-records/{recordId}/export
POST /api/imports
```

Export APIは種別に対応するMIME typeと`Content-Disposition`を返す。Import APIはEnvelopeの`$nova`からImporterを選択し、検証完了前にDB、Asset、外部参照を変更しない。

## 25. 実装・ホスティング構成

### 25.1 対象環境

- PHP 8.3以上
- Laravel 13.x
- MySQL 8.0以上またはMariaDB 10.11以上
- ApacheまたはLiteSpeed互換Web server
- Laravelが要求するPHP extensions
- cronおよび通常のPHP CLI
- local filesystem

ロリポップ！スタンダードプランとmixhost共用レンタルサーバーで、root権限、Docker、Redis、Supervisor、常駐Worker、WebSocket server、Node.js runtimeを使わず動作できることを基準とする。mixhostは提供サーバーによってMariaDB Versionが異なるため、MariaDB 10.11を提供するサーバーを対象とする。

### 25.2 Backend

- 実装言語はPHP 8.3以上とする。
- FrameworkはLaravel 13.xを使用する。
- HTTP、routing、request validation、authentication、authorization、Eloquent、Migration、Queue、Scheduler、Mail、Filesystem、CacheにはLaravelを利用する。
- Core DomainはLaravel、Eloquent、HTTP、認証方式から分離したplain PHPとして実装する。
- ComposerのPSR-4 autoloadだけでDomain unit testを実行できる構成にする。

### 25.3 Layer構造

```text
app/
 ├─ Domain/
 │   ├─ Category/
 │   ├─ Protocol/
 │   ├─ Record/
 │   ├─ OpenRecord/
 │   ├─ Validation/
 │   ├─ Normalization/
 │   ├─ Interchange/
 │   └─ Matrix/
 ├─ Application/
 │   ├─ Contracts/
 │   └─ UseCases/
 ├─ Infrastructure/
 │   ├─ Persistence/
 │   │   ├─ Eloquent/
 │   │   └─ Repositories/
 │   ├─ Filesystem/
 │   ├─ Mail/
 │   ├─ Queue/
 │   └─ Validation/
 ├─ Http/
 │   ├─ Controllers/
 │   ├─ Middleware/
 │   ├─ Requests/
 │   └─ Resources/
 └─ Models/
resources/
 ├─ views/
 ├─ js/
 └─ css/
tests/
 ├─ Unit/Domain/
 ├─ Feature/
 └─ Integration/Database/
```

依存方向は`Http / Console / Infrastructure → Application → Domain`とする。DomainからLaravel namespace、Eloquent Model、Facade、HTTP Request、認証Userを参照してはならない。

### 25.4 Application service

Web、REST API、CSV、Webhookは同じApplication UseCaseを呼び出す。

```text
Web Form ─┐
REST API ─┼─→ CreateRecord UseCase → Normalize → Validate → Repository
CSV ──────┤
Webhook ──┘
```

- Repository、Clock、ID generator、Transaction、Filesystem、Mailer、Queueはinterfaceを介してApplication／Domainへ提供する。
- Eloquent Modelは永続化adapterとして扱い、Domain Entityを兼用しない。
- authentication結果はHttp層でApplication用Actorへ変換する。
- authorization policyはHttp／Application境界で評価し、DomainへLaravel Guardを渡さない。

### 25.5 Frontend

- Laravel Bladeを基本のserver-rendered UIとする。
- JavaScriptおよびCSSはLaravel Viteでbuildする。
- Node.jsは開発環境およびGitHub Actionsのfrontend buildだけで使用する。
- 本番サーバーへは`public/build`を含むbuild済み静的Assetを配置する。
- 本番サーバーで`npm install`、`npm ci`、`npm run build`を実行しない。
- Core MVPはWebSocketを使わず、通常HTTP、form submission、fetch、必要に応じたpollingで実装する。

### 25.6 QueueとScheduler

- `.env`の標準値を`QUEUE_CONNECTION=database`とする。
- jobs、failed_jobsなどLaravel標準のdatabase queue用Migrationを含める。
- 常駐`queue:work`を前提にしない。
- Schedulerから短命Workerを起動し、キューが空になった時点または制限時間で終了させる。
- cronは毎分`php artisan schedule:run`を実行する。
- Queue Jobは冪等性を持ち、timeout後の再実行に耐えるようにする。
- Redis／Horizonは将来の任意構成とし、Core MVPの依存関係へ含めない。

### 25.7 GitHub Actions

CIは最低限、次を実行する。

1. PHP 8.3以上でComposer依存関係を取得する。
2. PHP coding style、static analysis、unit testを実行する。
3. MySQL 8系とMariaDB 10.11系のmatrixでMigrationおよびintegration testを実行する。
4. Node.jsを使ってfrontend依存関係を取得し、静的Assetをbuildする。
5. production向けComposer installと`public/build`を含むdeploy artifactを生成する。
6. production serverではartifactを展開し、Node.js処理を行わない。

### 25.8 Deployment

- Web serverのDocumentRootをLaravelの`public`ディレクトリへ向ける構成を標準とする。
- `.env`、`vendor`、`storage`、`bootstrap/cache`、application sourceをWeb公開領域へ露出させない。
- DocumentRootを変更できない場合は、application本体を公開領域外へ置き、`public`の内容だけを既定DocumentRootへ配置する。
- DocumentRoot固定構成では、公開側`index.php`から公開領域外の`vendor/autoload.php`と`bootstrap/app.php`を絶対pathで参照する。
- `storage`および`bootstrap/cache`へWeb serverの書き込み権限を付与する。
- deploy後に`php artisan migrate --force`と安全なcache生成を実行する。
- 詳細手順は[共用レンタルサーバー配備手順](DEPLOYMENT_SHARED_HOSTING.md)で管理する。

## 26. テスト方針

### 26.1 Protocol検証

- 各対応Field型の正常系・異常系
- 予約語
- Field ID重複
- 不正正規表現
- default不一致
- 制約の上下限矛盾
- enum重複
- 不正group・array
- 循環参照
- remote `$ref`
- Schema上限
- Published immutable
- private、unlisted、publicの閲覧・検索・取り込み可否
- Draftが所有者以外から閲覧できないこと
- Published VersionがProtocol本体のvisibilityに従うこと
- public／unlistedからprivateへ変更後に新規外部閲覧できないこと
- 取り込み済みコピーが元Protocolの非公開化後も利用できること
- schemaHashまたはcontentHashが一致する別VersionをDBが許容すること
- 同一Schemaまたは同一内容のPublish Warning

### 26.2 Category

- 一つのCategoryへ複数LogBookを所属できること
- LogBookがCategoryなしでも存在できること
- 一つのLogBookを複数Categoryへ所属させられないこと
- Category削除でLogBookが削除されず、category_idがnullになること
- Category削除でOpen Recordが削除されず、現在所属のcategory_idがnullになること
- Category削除で過去Open Record RevisionのcategoryId snapshotが変更されないこと
- CategoryがProtocol、Normalize、Validate、Record保存へ影響しないこと
- Categoryをネストできないこと

### 26.3 Normalize

- 許可された一意な変換
- 曖昧変換の拒否
- 空文字とnull
- 未定義FieldをErrorとして扱い、別領域へ移動しないこと
- Web/API/CSVで同じ正規化結果になること

### 26.4 Protocol Record

- 全Errorが返ること
- Warningだけなら保存できること
- 未定義Fieldが常にErrorになること
- 未定義Fieldをextensionsなどへ移動しないこと
- 明示定義されたjson型Fieldを保存できること
- Protocol Version固定
- 過去Recordを自動再検証しないこと
- Revision作成と現在値更新の原子性
- create、update、deleteがすべて完全snapshotであること
- delete Revision単体で削除前内容を表示できること
- delete Revisionが削除直前と同じProtocol Versionを参照すること
- `UNIQUE(record_id, revision_number)`が保証されること
- current_revision_idがdelete Revisionを指せること
- 通常一覧およびMatrixから削除済みRecordが除外されること
- validationWarningsが保存時点のsnapshotであり、再評価で上書きされないこと

### 26.5 Record更新と競合

- 正しいbaseRevisionIdで更新できること
- 古いbaseRevisionIdで409になること
- 競合時にRevisionおよびTimeline Eventを作成しないこと
- concurrent updateでlost updateが発生しないこと
- 更新Transactionの途中失敗ですべてRollbackされること
- Open Recordにも同じ楽観ロックが適用されること

### 26.6 Open Record

- LogBookなしで作成できること
- Protocol Versionなしで作成できること
- bodyが必須であること
- 長文bodyを保存できること
- create、update、deleteが完全snapshotであること
- baseRevisionId競合で409になること
- Categoryへ任意所属できること
- private、unlisted、publicの可視性が正しく適用されること
- Protocol RecordとAPI、DB、Normalize、Validateが混同されないこと

### 26.7 NOVA JSON交換形式

- 各拡張子、`$nova`、MIME typeの対応
- 拡張子と`$nova`不一致時に本文を正として判定すること
- 種別限定APIが不一致を拒否すること
- 未対応`$nova`を安全に拒否すること
- 未対応formatVersionをbest effortで処理しないこと
- NOVA本体VersionとformatVersionが独立していること
- contentHashの正常系と不一致拒否
- `.nvp`がProtocolとVersionを保持すること
- `.nvl`がCurrent Versionおよび全Revisionの参照Versionを含むこと
- `.nvr`がRecordと完全なRevision履歴を保持すること
- 必要なProtocol Versionを欠く`.nvr`を拒否すること
- `.nvo`がLogBookおよびProtocol参照を持たないこと
- `.nva`内の各entryを個別検証すること
- `.nva`へAsset本体のbase64埋め込みを許可しないこと
- Asset外部参照へImport時に暗黙アクセスしないこと
- JSON `.nva`をZIPとして処理しないこと

### 26.8 Database・実行環境互換性

- 全MigrationがMySQL 8系で適用・rollbackできること
- 全MigrationがMariaDB 10.11系で適用・rollbackできること
- MySQLとMariaDBで同じRepository contract testが通ること
- JSONカラムへ保存したSchema、metadata、payloadを同じDomain valueへ復元できること
- Core MVPのqueryがJSON内部検索やDB固有関数を必要としないこと
- 通常カラムのunique制約、relation、indexが両DBで機能すること
- transactionと`lockForUpdate()`相当によるconcurrent update test
- database queueを短命Workerで処理できること
- file cache、file session、local filesystemだけでCore MVPが動作すること
- Redis、常駐Worker、WebSocket、Node.js runtimeが存在しない本番相当環境でFeature testが通ること
- production artifactにbuild済み`public/build`が含まれること

### 26.9 Architecture

- Domain unit testがLaravel Applicationをbootせず実行できること
- Domain namespaceがIlluminate、Eloquent、Http、Authへ依存していないこと
- Web、API、CSV、Webhookが同じApplication UseCaseを通ること
- filesystem、mail、queue、cacheがLaravel abstractionまたはApplication contractを通ること

### 26.10 セキュリティ

- 深すぎるJSON
- 巨大配列
- ReDoS候補pattern
- prototype pollution候補キー
- 不正Asset参照
- 権限外LogBook
- Webhookリプレイ

## 27. 未決事項

実装計画を確定する前に、以下を決定する。

1. 認証方式
2. 最初に本番配備する共有レンタルサーバー
3. Assetの本番保存先
4. `number`およびmeasurementの精度保証
5. PHP整数範囲を超えるintegerの表現
6. decimalをJSON numberまたは文字列のどちらで保存するか
7. ProtocolおよびRecordの最大サイズ
8. Field IDの確定規則と予約語一覧
9. Protocol fork元および作者帰属の表示方針
10. 古いRecordを編集する際のProtocol Version選択方針
11. Matrixの初期集計に絞り込み条件を含めるか
12. 公開LogBookにおけるRecord表示形式
13. Categoryの正式名称
14. Open Recordの正式名称
15. Open RecordのbodyでMarkdownを許可するか
16. Open Recordのbody最大長
17. Open RecordへAssetを追加する時期と参照形式
18. Category visibilityをLogBook公開へ継承するか
19. withdrawn Protocol Versionの導入時期
20. Protocol Recordのextensionsカラムを物理的に完全削除するか、将来互換の予約領域としてDBだけに残すか
21. 将来の添付込みZIPコンテナの拡張子、MIME type、manifest仕様
22. NOVA JSON交換形式の初回`formatVersion`

## 28. 実装前の意思決定優先順位

次の順序で決定する。

1. 数値表現と精度保証
2. Protocolの保存SchemaとField ID規則
3. Record Revisionの編集時Version方針
4. CategoryおよびOpen Recordの正式名称と公開範囲
5. 認証、公開範囲、Asset保存
6. NOVA JSON交換形式の初回formatVersion
7. API契約
8. 画面構成
9. Matrixの詳細

この順序は、後続設計への影響が大きいものから並べている。

---

## 付録A. Protocol例

```json
{
  "protocol": {
    "id": "fuel",
    "ownerId": "usr_01...",
    "slug": "fuel",
    "visibility": "public"
  },
  "version": {
    "id": "pv_01...",
    "protocolId": "fuel",
    "version": "1.2.0",
    "state": "published",
  "schema": {
    "$schema": "https://json-schema.org/draft/2020-12/schema",
    "$id": "urn:nova:protocol:fuel:1.2.0",
    "type": "object",
    "properties": {
      "odometer": {
        "title": "走行距離",
        "type": "object",
        "properties": {
          "value": {
            "type": "number",
            "minimum": 0
          },
          "unit": {
            "type": "string",
            "const": "km"
          }
        },
        "required": ["value", "unit"],
        "additionalProperties": false
      },
      "fullTank": {
        "title": "満タン",
        "type": "boolean"
      },
      "memo": {
        "title": "メモ",
        "type": ["string", "null"],
        "maxLength": 2000
      }
    },
    "required": ["odometer"],
    "additionalProperties": false
  },
  "metadata": {
    "order": ["/odometer", "/fullTank", "/memo"],
    "fields": {
      "/odometer": {
        "kind": "measurement",
        "label": "走行距離",
        "unit": {
          "allowed": ["km"],
          "default": "km"
        }
      },
      "/fullTank": {
        "kind": "boolean",
        "label": "満タン"
      },
      "/memo": {
        "kind": "text",
        "label": "メモ"
      }
    }
  },
    "advisories": []
  }
}
```

## 付録B. Record例

```json
{
  "id": "rev_01...",
  "recordId": "rec_01...",
  "logBookId": "log_01...",
  "protocolId": "fuel",
  "protocolVersion": "1.2.0",
  "protocolVersionId": "pv_01...",
  "revisionNumber": 1,
  "operation": "create",
  "occurredAt": "2026-07-20T10:30:00+09:00",
  "receivedAt": "2026-07-20T01:30:01Z",
  "payload": {
    "odometer": {
      "value": 142381,
      "unit": "km"
    },
    "fullTank": true,
    "memo": null
  },
  "source": {
    "type": "web"
  },
  "validationWarnings": []
}
```

## 付録C. Open Record例

```json
{
  "id": "orev_01...",
  "recordId": "orec_01...",
  "ownerId": "usr_01...",
  "categoryId": "cat_01...",
  "revisionNumber": 1,
  "operation": "create",
  "title": "今日のメモ",
  "body": "Protocolを作る前に、まず残しておきたい内容。",
  "tags": ["memo"],
  "visibility": "private",
  "occurredAt": "2026-07-20T10:30:00+09:00",
  "receivedAt": "2026-07-20T01:30:01Z",
  "source": {
    "type": "web"
  },
  "validationWarnings": []
}
```

## 付録D. `.nvo`エクスポート例

```json
{
  "$nova": "open-record",
  "formatVersion": "1.0.0",
  "exportedAt": "2026-07-20T02:00:00Z",
  "generator": {
    "name": "NOVA",
    "version": "0.1.0"
  },
  "data": {
    "record": {
      "id": "orec_01...",
      "currentRevisionId": "orev_01..."
    },
    "revisions": [
      {
        "id": "orev_01...",
        "recordId": "orec_01...",
        "revisionNumber": 1,
        "operation": "create",
        "body": "自由に残す長文",
        "visibility": "private",
        "occurredAt": "2026-07-20T10:30:00+09:00",
        "receivedAt": "2026-07-20T01:30:01Z",
        "source": { "type": "web" },
        "validationWarnings": []
      }
    ]
  }
}
```

## 付録E. 参照標準

- [JSON Schema Draft 2020-12](https://json-schema.org/draft/2020-12)
- [JSON Schema Core](https://json-schema.org/draft/2020-12/json-schema-core)
- [RFC 6901 — JSON Pointer](https://datatracker.ietf.org/doc/rfc6901/)
- [RFC 3339 — Date and Time on the Internet](https://datatracker.ietf.org/doc/html/rfc3339)
- [RFC 8785 — JSON Canonicalization Scheme](https://datatracker.ietf.org/doc/html/rfc8785)
- [Semantic Versioning 2.0.0](https://semver.org/)
- [Laravel 13 Release Notes](https://laravel.com/docs/13.x/releases)
- [Laravel 13 Database](https://laravel.com/docs/13.x/database)
- [Laravel Filesystem](https://laravel.com/docs/13.x/filesystem)
- [MySQL JSON Data Type](https://dev.mysql.com/doc/refman/8.4/en/json.html)
- [MariaDB JSON Data Type](https://mariadb.com/kb/en/json-data-type/)
- [ロリポップ！PHP・MySQL仕様](https://lolipop.jp/manual/hp/cgi/)
- [ロリポップ！cron設定](https://lolipop.jp/manual/user/cron/)
- [mixhost PHP仕様](https://help.mixhost.jp/articles/115003735811)
- [mixhost Database仕様](https://help.mixhost.jp/articles/115003742232)
