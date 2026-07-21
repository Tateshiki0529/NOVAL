# ADR 0001: Core MVP implementation decisions

Status: Accepted for Core MVP  
Date: 2026-07-21

The specification intentionally leaves several product decisions open. The
following conservative defaults unblock the first vertical slice without
committing MVP 1 public interfaces prematurely.

- Authentication uses Laravel's session guard with email and password.
- Core MVP resources are private and owner-scoped. `unlisted` and `public`
  values remain reserved for MVP 1 and are rejected by Core write paths.
- Field IDs match `^[a-z][a-zA-Z0-9_]{0,63}$`. The specification's reserved
  words are enforced together with `__proto__`, `prototype`, and
  `constructor` at every object boundary.
- Integers must fit PHP's signed 64-bit range. Decimal values use finite JSON
  numbers. Arbitrary-precision decimal representation remains an explicit
  follow-up decision.
- Protocol schema limits are 16 levels, 256 fields, 256 enum values per enum,
  and 1 MiB of encoded schema and metadata combined.
- Protocol Record payloads are limited to 256 KiB. Arrays are limited to
  1,000 items unless a lower schema `maxItems` is present.
- Open Record body is plain text, required, and limited to 65,536 characters.
  Titles are limited to 200 characters and tags to 20 values of 64 characters.
- Core updates retain the current revision's Protocol Version. Moving an old
  Record to another Version requires an explicit later workflow.
- Category and Open Record remain stable internal names while their eventual
  user-facing names are undecided.
- JSON exchange `formatVersion` and public REST contracts remain deferred to
  MVP 1. Web forms call the same Application use cases planned for those
  adapters.
- Protocol hashes use SHA-256 over recursively key-sorted UTF-8 JSON under
  canonicalization label `noval-json-v1`. RFC 8785 migration is deferred until
  arbitrary-precision number representation is decided.

These choices must be revisited before MVP 1 compatibility is declared.
