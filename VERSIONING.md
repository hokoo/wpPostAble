# Versioning and compatibility policy

wpPostAble follows [Semantic Versioning 2.0.0](https://semver.org/). This document
defines which changes are compatible, which runtime behavior is protected, and
how Composer versions are selected.

## Version source and release names

Git tags are the canonical source of package versions. `composer.json` must not
contain a `version` field: [Composer derives library versions from VCS
tags](https://getcomposer.org/doc/02-libraries.md#library-versioning), and
Packagist publishes those versioned refs.

All new release tags use complete, unprefixed SemVer:

- `0.7.0` for the next stable pre-1.0 baseline;
- `1.0.0-rc.1` for the first 1.0 release candidate;
- `1.0.0` for the first stable release with the 1.x compatibility promise.

Further release candidates increment the numeric suffix, for example
`1.0.0-rc.2`. A suffix such as `-rc.1`, rather than the GitHub Release checkbox,
determines pre-release ordering for Composer.

The historical tags `0.1`, `0.2`, `0.2.1`, `0.2.2`, `0.3`, `0.4`, `0.4.1`,
`0.4.2`, `0.5`, `0.6`, `0.6.1`, and `0.6.2` predate this convention. They remain
valid historical identifiers and will not be renamed, replaced, or moved.

Published releases are immutable. A published tag must always identify the same
commit, and its release artifacts must not be replaced with different contents.
A correction is published as a new version.

## Choosing the next version

Before `1.0.0`:

- PATCH is for a backward-compatible bug or security fix to the current `0.x`
  contract.
- MINOR is for a feature, deprecation, or any compatibility-contract change,
  including an incompatible pre-1.0 change.

Starting with `1.0.0`:

- PATCH is for a backward-compatible bug or security fix.
- MINOR is for a backward-compatible public addition or a deprecation.
- MAJOR is for an incompatible public change or removal.

A change is incompatible when supported consumer code, hook callbacks, stored
data, or supported runtime versions must change to keep working. Calling a
change a bug fix does not make it PATCH if it breaks documented behavior.

Examples:

| Change | Before 1.0 | Starting with 1.0 |
|---|---:|---:|
| Fix an internal error without changing the contract | PATCH | PATCH |
| Add an optional, backward-compatible trait method | MINOR | MINOR |
| Deprecate a public method while keeping it operational | MINOR | MINOR |
| Remove or rename a public method | MINOR | MAJOR |
| Add a required method to `wpPostAble` | MINOR | MAJOR unless existing implementers remain compatible |
| Change a hook name or reorder its arguments | MINOR | MAJOR |
| Incompatibly change the metadata or parameter format | MINOR | MAJOR |
| Raise the minimum PHP or WordPress version | MINOR | MAJOR |

Documentation, tests, and internal refactoring do not require a release by
themselves. If such a change is released, it does not justify a version lower
than another change included in the same release.

## Supported platforms

The 1.0 support baseline is:

- PHP 7.4 or newer;
- WordPress 6.0 or newer;
- the JSON PHP extension, as required by `composer.json`.

The lower bounds are part of the 1.x compatibility contract. Raising either
lower bound requires a new major version. The project tests PHP 7.4 and PHP 8.4,
and WordPress 6.0 and the latest stable WordPress release. A future upstream
release can expose a compatibility defect; a compatible correction remains a
PATCH change.

## What the compatibility promise covers

For a tagged 1.x release, SemVer protects:

- the Composer package name `hokoo/wppostable` and the PSR-4 namespace
  `iTRON\wpPostAble\`;
- the interface, trait, exception types, and public or protected members listed
  below;
- documented method inputs, return behavior, chainability, and exception types;
- documented WordPress hook names, order, timing, defaults, and arguments;
- the metadata and JSON persistence contracts described below.

Private helpers and private state not identified as an implementer integration
point are implementation details. Tests, fixtures, Docker services, Make targets,
and scripts are development interfaces rather than the runtime API.

Diagnostic exception-message wording is not stable. Exception classes, their
inheritance and implemented interfaces, public context accessors, reason
constants, and machine-readable error codes are stable.

### Interface and trait methods

The following table records the implementation present before the 1.0 API
freeze. “Interface” means the method is declared by `wpPostAble`; every listed
public trait method is consumer-facing even where the interface currently omits
it.

| Method | Interface | Current return behavior | Protected behavior |
|---|:---:|---|---|
| `getPost(): WP_Post` | yes | active associated `WP_Post` | Returns the same mutable post object held by the model. It is not valid after a successful delete. |
| `savePost()` | yes | `$this` (`self`) | Persists the current post and explicitly changed metadata; throws `wppaSavePostException` on a WordPress error or empty result. |
| `deletePost()` | yes | `null` | Calls `wp_delete_post($id)` with Core's default force behavior. Success invalidates the model; failure preserves it and throws `wppaDeletePostException`. |
| `getPostType()` | yes | `string` | Returns the post type supplied during initialization. |
| `getTitle()` | yes | `string` | Returns `WP_Post::post_title`. |
| `setTitle(string $title)` | yes | `$this` (`self`) | Changes the in-memory title and is chainable; persistence requires a save. |
| `getSlug(): string` | yes | `string` | Returns the current in-memory `WP_Post::post_name`. |
| `setSlug(string $slug): self` | yes | `$this` (`self`) | Stores the supplied slug in memory and is chainable; Core normalization and persistence require a save and reload. |
| `getMenuOrder(): int` | yes | `int` | Returns the current in-memory `WP_Post::menu_order`. |
| `setMenuOrder(int $menuOrder): self` | yes | `$this` (`self`) | Stores any integer menu order in memory and is chainable; persistence requires a save. It does not reorder other posts. |
| `getStatus()` | yes | `string` | Returns `WP_Post::post_status`. |
| `setStatus(string $status)` | yes | `$this` (`self`) | Changes the in-memory status and is chainable; persistence requires a save. |
| `setMetaField(string $meta_key, $meta_value)` | yes | `$this` (`self`) | Changes the in-memory single-value view, marks only that key dirty, and is chainable. |
| `getMetaField(string $meta_key)` | yes | mixed or `null` | Returns the loaded/assigned value, or `null` when the key is absent. |
| `getMetaFields()` | yes | `array` | Returns the in-memory single-value metadata map. |
| `publish()` | yes | `$this` (`self`) | Sets status to `publish` and immediately saves. |
| `draft()` | yes | `$this` (`self`) | Sets status to `draft` and immediately saves. |
| `getParam(string $param)` | no | mixed or `null` | Reads one value from the JSON parameter map and throws `wppaParamException` for invalid stored data. |
| `setParam(string $param, $value)` | no | `null` | Re-encodes the in-memory JSON parameter map; it is not chainable and persistence requires a save. |

The native signatures in the released source remain authoritative where the
interface currently omits a return type that the trait declares. Changing a
parameter type, narrowing an accepted input, changing a return type or
chainability, or adding a new failure for an input that was previously supported
is a compatibility change.

Classes using `wpPostAbleTrait` receive the slug and menu-order implementations
automatically. A class that implements `wpPostAble` manually, or overrides the
corresponding trait methods, must add compatible `getSlug(): string`,
`setSlug(string $slug): self`, `getMenuOrder(): int`, and
`setMenuOrder(int $menuOrder): self` signatures before adopting the 1.0
interface. These accessors use existing `WP_Post` fields, so no stored-data
migration is required.

The trait also exposes the protected property `$post` to the adopting class and
its subclasses. It contains the active `WP_Post` and becomes `null` after a
successful delete. Its exact long-term status is an implementer-surface question
to be resolved by the T12 1.0 contract freeze; until then it must not be silently
renamed, removed, or made private.

### Implementer initialization surface

The trait provides this required integration method:

```php
/** @param int|WP_Post|null $post_id */
private function wpPostAble(string $post_type, $post_id = 0): self
```

An adopting class calls it from its own constructor. A zero ID or `null` creates
and then loads a persisted WordPress post; a non-zero integer loads an existing
post through `get_post()`. A supplied `WP_Post` skips creation and lookup, retains
the same object identity, and enters the same post-type validation, metadata,
and loading-hook pipeline as an ID lookup. Unsupported values throw native
`TypeError`; this includes numeric strings, floats, and booleans that PHP could
weakly coerce through the earlier native `int` parameter. Repeated initialization
of an instance that already contains a `WP_Post` returns the same instance
without creating or loading again. Creation can throw `wppaCreatePostException`;
loading can throw `wppaLoadPostException`.

This method is `private` in the current trait but is documented and required by
implementers. Its untyped native parameter is a PHP 7.4 compatibility boundary;
the `int|WP_Post|null` docblock and runtime `TypeError` validation define the
accepted union. T12 must explicitly settle its visibility; it is not being
treated as an accidental private helper.

`loadPost(int $post_id): self` is currently private even though the historical
README lists it among instance operations. External calls are therefore not
supported by the current code. T12 must resolve this documentation/code mismatch
before `1.0.0-rc.1` rather than silently adding it to or excluding it from the
frozen 1.0 API.

The remaining private trait methods and the private properties `$post_type`,
`$post_meta`, and `$dirty_post_meta` are implementation details.

### Exceptions

All names below are in `iTRON\wpPostAble\Exceptions`.

Their current public constructor signatures are:

```php
new wppaException(wpPostAble $postable, $message = "", $code = 0, ?Throwable $previous = null)
new wppaCreatePostException(wpPostAble $postable, WP_Error $error, $message = "", $code = 0, ?Throwable $previous = null)
new wppaLoadPostException($post_id, wpPostAble $postable, $message = "", $code = 0, ?Throwable $previous = null)
new wppaSavePostException(wpPostAble $postable, WP_Error $error, $message = "", $code = 0, ?Throwable $previous = null)
new wppaDeletePostException(wpPostAble $postable, WP_Post $post, WP_Error $error, $message = "", $code = 0, ?Throwable $previous = null)
new wppaParamException(wpPostAble $postable, string $param_name, string $operation, string $reason, int $json_error_code, $message = "", ?Throwable $previous = null)
```

| Type | Public contract |
|---|---|
| `wpException` | Interface declaring `getError(): WP_Error`. |
| `wppaException` | Extends `Exception`; public `$postable`; constructor accepts the associated `wpPostAble`, message, code, and optional previous `Throwable`; `getPostable(): wpPostAble` returns that instance. |
| `wppaCreatePostException` | Extends `wppaException` and implements `wpException`; public `$error`; `getError(): WP_Error` exposes the creation error. |
| `wppaLoadPostException` | Extends `wppaException`; public `$post_id`; `getPostID()` exposes the ID that failed to load or match the expected post type. |
| `wppaSavePostException` | Extends `wppaException` and implements `wpException`; public `$error`; `getError(): WP_Error` exposes the save error; `getPost()` returns the associated model's post. |
| `wppaDeletePostException` | Extends `wppaException` and implements `wpException`; public `$post` and `$error`; `getPost(): WP_Post` preserves the original post and `getError(): WP_Error` exposes `delete_post_failed`. |
| `wppaParamException` | Extends `wppaException`; exposes operation constants `OPERATION_READ` and `OPERATION_WRITE`, reason constants `REASON_INVALID_JSON`, `REASON_INVALID_ROOT`, and `REASON_ENCODE_FAILED`, plus `getParamName()`, `getOperation()`, `getReason()`, and `getJsonErrorCode()`. Its exception code equals the JSON error code. |

The public constructors and public context properties of these classes are part
of the current observable surface. T12 must decide whether direct exception
construction and property access remain recommended in 1.0; they remain
protected unless that decision is made before the RC.

### WordPress hooks

Every library hook is dispatched first under a global name beginning with
`\wpPostAbleTrait`, then under a class-specific name formed by prefixing the
adopting class FQCN. The final argument is that FQCN. Both forms, their order, and
their argument contracts are protected.

For filters, the class-specific filter receives the result of the global filter
as its first value, followed by the original unfiltered arguments and the class
name.

| Hook | Kind and arguments | Contract |
|---|---|---|
| `\wpPostAbleTrait\init\defaultStatus` | filter: global `(string $default, string $class)`; class-specific `($global_result, string $original_default, string $class)` | Default is `draft`; final result becomes `post_status` for a newly created post. |
| `\wpPostAbleTrait\init\defaultTitle` | filter with the same shape | Default is `draft`; final result becomes `post_title`. |
| `\wpPostAbleTrait\init\defaultContent` | filter with the same shape | Default is `Empty.`; final result becomes `post_content`. |
| `\wpPostAbleTrait\loadPost\equalPostType` | filter: global `(bool $equal, string $class)`; class-specific `($global_result, bool $original_equal, string $class)` | Final truthiness decides whether the loaded `WP_Post` is accepted. |
| `\wpPostAbleTrait\loadPost\loadMeta` | filter: global `(bool $load, wpPostAble $instance, string $class)`; class-specific `($global_result, bool $original_load, wpPostAble $instance, string $class)` | Default is `true`; a false final value skips the metadata read. |
| `\wpPostAbleTrait\loadPost\loading` | action: `(wpPostAble &$instance, string $class)` for both names | Runs after the post is assigned and optional metadata loading completes; the instance argument is passed by reference. |
| `\wpPostAbleTrait\deletePost\beforeDeletePost` | action: `(int $post_id, WP_Post $post, string $class)` for both names | Runs immediately before the Core delete call, including attempts that later fail. |
| `\wpPostAbleTrait\deletePost\afterDeletePost` | action: `(int $post_id, WP_Post $post, string $class)` for both names | Runs only after Core reports success and receives the original post object. |

For example, if `Vendor\Item` adopts the trait, the class-specific default-title
filter is `Vendor\Item\wpPostAbleTrait\init\defaultTitle`.

### Post and metadata persistence

- Creating a model inserts a real WordPress post immediately. The initial status,
  title, and content use the hook defaults above.
- Loading requires an existing `WP_Post` whose type is accepted by the post-type
  filter.
- Passing an existing `WP_Post` retains that exact object and does not call
  `wp_insert_post()` or `get_post()`; metadata loading and load hooks are
  otherwise identical to loading by ID.
- Title, status, parameter, and arbitrary mutations made to the returned
  `WP_Post` are in memory until `savePost()`, `publish()`, or `draft()` succeeds.
- `setSlug()` assigns the supplied value directly to `WP_Post::post_name` without
  sanitizing or saving it. WordPress Core may normalize or uniquify that value
  during a save; reload the model to observe the final persisted slug.
- `setMenuOrder()` assigns any supplied integer directly to
  `WP_Post::menu_order` without saving it. `savePost()` persists it through the
  normal Core update; the library neither restricts its range nor reorders other
  posts.
- Metadata is loaded as a single-value map. For a key with multiple database
  rows, the first row is exposed. Values are passed through WordPress
  `maybe_unserialize()` once.
- `setMetaField()` marks the named key dirty even if the assigned value equals
  the loaded value. A successful save sends only explicitly dirty keys through
  WordPress `meta_input` and then clears the dirty set. A failed save preserves
  it for retry.
- Unmodified metadata, including all rows of a multi-value key, is not rewritten
  by a save. Explicitly setting a multi-value key delegates to WordPress's
  single-key update behavior and may update every row for that key; multi-value
  mutation is not modeled by this API.
- Scalar and structured values use normal WordPress metadata serialization.
  There is no metadata-delete operation in the current API.
- `deletePost()` delegates trash-versus-permanent deletion to WordPress by not
  supplying a force flag. A successful Core result invalidates the model; a
  false or null result keeps the original post available for retry.

Changing the storage location, collapsing untouched multi-value rows, changing
the one-value read model, or changing serialization in a way that existing
stored values cannot round-trip is an incompatible persistence change.

### JSON parameter persistence

`getParam()` and `setParam()` use `WP_Post::post_content_filtered` as one JSON
parameter map:

- an empty string is an empty map;
- an object root is the canonical shape;
- an existing array root is accepted as a numeric-key map and is normalized to
  an object after a successful write;
- scalar roots, including JSON `null`, booleans, numbers, and strings, are
  rejected with `REASON_INVALID_ROOT`;
- malformed JSON is rejected with `REASON_INVALID_JSON`;
- missing keys and keys containing JSON `null` both read as PHP `null`;
- nested values decode using PHP's normal `json_decode()` object/array behavior;
- writes encode the whole map with `JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE`;
- encoding failures use `REASON_ENCODE_FAILED` and expose the relevant JSON error
  code; failures leave the original field byte-for-byte unchanged;
- a successful `setParam()` changes only the in-memory `WP_Post`; `savePost()` is
  required to persist it.

The field location, accepted root shapes, key behavior, atomic failure behavior,
and encoded object format are protected storage contracts. There is no parameter
delete operation in the current API.

## Deprecation and removal

In 1.x, a public API is deprecated in a MINOR release and remains operational for
the rest of the major series. The deprecation notice must identify the
replacement or migration path in the changelog and relevant public
documentation. Runtime notices may supplement, but do not replace, that notice.

A deprecated 1.x API may be removed or incompatibly changed only in 2.0.0. The
same rule applies to methods, interfaces, exception context, hooks, persistence
formats, and supported-platform lower bounds. Before 1.0, deprecation or removal
uses a MINOR increment and must still include migration guidance.

## Composer version selection

Typical constraints are:

```json
{
  "require": {
    "hokoo/wppostable": "^0.7.0"
  }
}
```

`^0.7.0` accepts compatible `0.7.x` releases but not `0.8.0`. After the stable
contract is published, use `^1.0` to accept compatible 1.x releases but not 2.0.
To validate the release candidate explicitly, require `1.0.0-rc.1`.

`dev-master` follows a moving branch rather than an immutable release and does
not provide release reproducibility. It should be used only for deliberate
development testing.

## 1.0 freeze follow-up

The following planned work is intentionally visible rather than being silently
treated as already stable:

- adding the existing `getParam()` and `setParam()` operations to the interface;
- resolving the visibility/status of `wpPostAble()`, `loadPost()`, protected
  `$post`, and direct exception construction/property access.

[T12, “Freeze and document the complete 1.0 public
API”](https://github.com/hokoo/wpPostAble/issues/21), must update this inventory
with the implemented signatures and resolve every item before `1.0.0-rc.1`.
After the final `1.0.0` tag, any incompatible change to the frozen result requires
2.0.0.
