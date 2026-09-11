# wpPostAble 1.0 API reference

This document defines the consumer-facing contract frozen for wpPostAble 1.x.
The compatibility policy is in [VERSIONING.md](../VERSIONING.md), and projects
upgrading from `0.7.x` should also read [UPGRADING-1.0.md](UPGRADING-1.0.md).

## Runtime baseline

- PHP 7.4 or newer.
- WordPress 6.0 or newer.
- The JSON PHP extension.

The API deliberately avoids native `mixed`, union, `static`, readonly, and
asymmetric-visibility declarations that are unavailable on PHP 7.4. PHPDoc and
the runtime checks described below complete the native type contract.

## Adopting the trait

A model implements `iTRON\wpPostAble\wpPostAble`, uses
`iTRON\wpPostAble\wpPostAbleTrait`, and invokes the trait initializer from the
constructor of the same class:

```php
use iTRON\wpPostAble\wpPostAble;
use iTRON\wpPostAble\wpPostAbleTrait;
use WP_Post;

final class Item implements wpPostAble
{
    use wpPostAbleTrait;

    private const POST_TYPE = 'item';

    /** @param int|WP_Post|null $post_id */
    public function __construct($post_id = null)
    {
        $this->wpPostAble(self::POST_TYPE, $post_id);
    }
}
```

The initializer has this PHP 7.4-compatible signature:

```php
/** @param int|WP_Post|null $post_id */
private function wpPostAble(string $post_type, $post_id = 0): self
```

It is a required private trait-composition seam, not a public or protected
extension point. A class that imports the trait can call it from that class's
constructor. If a base class imports the trait, its own constructor must perform
initialization; a child class cannot call the inherited private method directly.
Do not expose it through the model's public API.

The second argument behaves as follows:

| Input | Result |
|---|---|
| `null` or integer `0` | Inserts a new WordPress post, then loads it. |
| Non-zero integer | Resolves the post with `get_post()` and loads it. |
| `WP_Post` | Reuses the exact object without insertion or lookup, then applies the normal type, metadata, and loading-hook path. |
| Any other value | Throws `TypeError` before a WordPress call or model mutation. |

Numeric strings, floats, and booleans are not accepted. Cast an external numeric
identifier to an integer only after validating it. A supplied `WP_Post` whose
type is not accepted throws `wppaLoadPostException` and remains unmodified.
Calling the initializer again after the model already holds a `WP_Post` returns
the same model without creating or loading another post.

The associated `$post` property is private. Use `getPost()` for supported access
to the mutable `WP_Post`. The private `loadPost()` and `loadPostObject()` methods
are implementation details; the library has no public reload or rebind method.
Create another model instance to load another post.

## The 19 interface methods

All methods below are declared by `wpPostAble` and implemented by
`wpPostAbleTrait`. Native signatures, including parameter names, are part of the
1.x compatibility contract. `getParam()` and `getMetaField()` are the only
methods with an untyped native return because their result is `mixed|null` and
native `mixed` is not available on PHP 7.4.

| Method | Behavior |
|---|---|
| `getPost(): WP_Post` | Returns the active mutable WordPress post object. It is not valid after a successful delete. |
| `savePost(): self` | Persists the current post and explicitly dirty metadata, clears dirty metadata after success, and returns the model. Throws `wppaSavePostException` on failure. |
| `deletePost(): void` | Delegates deletion to WordPress. Success invalidates the model; failure preserves it and throws `wppaDeletePostException`. |
| `getPostType(): string` | Returns the post type supplied during initialization. |
| `getTitle(): string` | Returns the in-memory `post_title`. |
| `setTitle(string $title): self` | Changes `post_title` in memory and returns the model. |
| `getSlug(): string` | Returns the in-memory `post_name`. |
| `setSlug(string $slug): self` | Stores the supplied slug in memory and returns the model. WordPress may normalize or uniquify it during save. |
| `getMenuOrder(): int` | Returns the in-memory `menu_order` as an integer. |
| `setMenuOrder(int $menuOrder): self` | Stores any integer menu order in memory and returns the model. It does not reorder other posts. |
| `getStatus(): string` | Returns the in-memory `post_status`. |
| `setStatus(string $status): self` | Changes `post_status` in memory and returns the model. |
| `setMetaField(string $meta_key, $meta_value): self` | Sets one value in the model's metadata view, marks that key dirty, and returns the model. |
| `getMetaField(string $meta_key)` | Returns a loaded or assigned metadata value, or `null` when absent. Native return is untyped; documented return is `mixed|null`. |
| `getMetaFields(): array` | Returns the in-memory single-value metadata map. |
| `getParam(string $param)` | Returns a JSON-backed parameter value or `null`. Native return is untyped; documented return is `mixed|null`. Throws `wppaParamException` for invalid stored JSON. |
| `setParam(string $param, $value): void` | Changes one JSON-backed parameter in memory. It is not chainable and does not save. Throws `wppaParamException` when the existing map or new value cannot be encoded safely. |
| `publish(): self` | Sets status to `publish`, saves immediately, and returns the model. |
| `draft(): self` | Sets status to `draft`, saves immediately, and returns the model. |

Except for `savePost()`, `publish()`, and `draft()`, setters change only the
current in-memory object. Call `savePost()` explicitly to persist those changes.

## Post lifecycle and persistence

- Creation inserts a real post immediately with filterable draft defaults.
- ID loading requires an existing post whose post type passes the type filter.
- `WP_Post` loading retains the supplied object identity and skips both
  `wp_insert_post()` and `get_post()`.
- `savePost()` sends the current public `WP_Post` fields to WordPress and adds
  only metadata keys explicitly changed through `setMetaField()` as
  `meta_input`.
- A successful save clears the dirty metadata set. A failed save leaves it
  available for retry.
- A successful delete invalidates the model. Further model operations are not
  supported; construct another instance if one is needed.
- `deletePost()` uses the default WordPress trash-versus-permanent behavior and
  does not force permanent deletion.

### Metadata

Metadata is loaded as a single-value map. When a key has multiple database
rows, the first row is exposed and its value is passed through WordPress
`maybe_unserialize()` once. Untouched metadata rows are not rewritten during an
unrelated save.

Calling `setMetaField()` marks the key dirty even when the assigned value equals
the loaded value. WordPress's normal single-key update semantics apply when that
key is saved, including for an existing multi-value key. The API does not model
multi-value updates and does not provide a metadata-delete operation.

### JSON-backed parameters

`getParam()` and `setParam()` use `WP_Post::post_content_filtered` as one JSON
map:

- an empty string represents an empty map;
- an object root is canonical;
- an existing array root is accepted as a numeric-key map and becomes an object
  after a successful write;
- scalar roots, including JSON `null`, booleans, numbers, and strings, throw
  `wppaParamException::REASON_INVALID_ROOT`;
- malformed JSON throws `REASON_INVALID_JSON`;
- missing keys and keys containing JSON `null` both read as PHP `null`;
- writes use `JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE`;
- an encoding failure throws `REASON_ENCODE_FAILED` and leaves the original
  field byte-for-byte unchanged;
- a successful `setParam()` remains in memory until `savePost()` succeeds.

The API does not provide a parameter-delete operation.

## Exceptions

Exception class names, inheritance, non-final status, public constructors,
public context-property existence, getters, constants, and the stable error
codes below are part of the 1.x contract. Exact human-readable message wording,
direct mutation effects, serialized object shape, and manually constructed
invalid combinations are not guaranteed.

All exception types are in `iTRON\wpPostAble\Exceptions`.

| Type | Stable context |
|---|---|
| `wppaException` | Extends `Exception`; public `$postable`; `getPostable(): wpPostAble`. |
| `wppaCreatePostException` | Extends `wppaException`, implements `wpException`; public `$error`; `getError(): WP_Error`. |
| `wppaLoadPostException` | Extends `wppaException`; public `$post_id`; `getPostID()` returns the rejected or unresolved ID. |
| `wppaSavePostException` | Extends `wppaException`, implements `wpException`; public `$error`; `getError(): WP_Error`; `getPost()` returns the associated post. |
| `wppaDeletePostException` | Extends `wppaException`, implements `wpException`; public `$post` and `$error`; `getPost(): WP_Post`; `getError(): WP_Error`. |
| `wppaParamException` | Extends `wppaException`; exposes parameter, operation, reason, and JSON error code through typed getters. |
| `wpException` | Interface declaring `getError(): WP_Error`. |

The preserved public constructor signatures are:

```php
new wppaException(wpPostAble $postable, $message = "", $code = 0, ?Throwable $previous = null)
new wppaCreatePostException(wpPostAble $postable, WP_Error $error, $message = "", $code = 0, ?Throwable $previous = null)
new wppaLoadPostException($post_id, wpPostAble $postable, $message = "", $code = 0, ?Throwable $previous = null)
new wppaSavePostException(wpPostAble $postable, WP_Error $error, $message = "", $code = 0, ?Throwable $previous = null)
new wppaDeletePostException(wpPostAble $postable, WP_Post $post, WP_Error $error, $message = "", $code = 0, ?Throwable $previous = null)
new wppaParamException(wpPostAble $postable, string $param_name, string $operation, string $reason, int $json_error_code, $message = "", ?Throwable $previous = null)
```

Use getters rather than public properties in new code. The properties remain
public for compatibility, not as a recommendation to mutate exception state.

### Machine-readable failures

When WordPress returns a `WP_Error`, creation and saving preserve that exact
object, including its codes and data. The library synthesizes the following
stable errors only for defensive empty-result paths:

| Operation | Exception | Stable `WP_Error` code | Stable data |
|---|---|---|---|
| Create | `wppaCreatePostException` | `create_post_failed` | `['post_type' => string]` |
| Save | `wppaSavePostException` | `save_post_failed` | `['post_id' => int]` |
| Delete returning no `WP_Post` | `wppaDeletePostException` | `delete_post_failed` | `['post_id' => int]` |

`wppaParamException` exposes `OPERATION_READ` or `OPERATION_WRITE`, one of
`REASON_INVALID_JSON`, `REASON_INVALID_ROOT`, or `REASON_ENCODE_FAILED`, and a
JSON error code. Its exception code is the JSON error code.

### Safe diagnostics

Do not serialize, dump, or log a complete exception object. Its object graph can
contain the model, the mutable post and its content, loaded metadata, and
arbitrary `WP_Error::error_data`, any of which may contain sensitive values.

For diagnostics, extract only allowlisted scalar fields needed by the caller,
such as the exception class, stable error/reason code, operation, post ID or
post type, and a request correlation ID. Treat messages and Core error data as
untrusted diagnostic text; escape them for their output context and do not
return them from an HTTP endpoint without an explicit authorization and
redaction decision.

## WordPress hooks

Each hook is dispatched first under its global name and then under a
class-specific name formed by prefixing the adopting class FQCN. The final
argument is that FQCN. For filters, the class-specific filter receives the
global result first, followed by the original arguments and class name.

| Hook | Kind and arguments | Contract |
|---|---|---|
| `\wpPostAbleTrait\init\defaultStatus` | Filter: global `(string $default, string $class)`; class-specific `($global_result, string $original_default, string $class)` | Default `draft`; final result becomes a new post's status. |
| `\wpPostAbleTrait\init\defaultTitle` | Filter with the same shape | Default `draft`; final result becomes a new post's title. |
| `\wpPostAbleTrait\init\defaultContent` | Filter with the same shape | Default `Empty.`; final result becomes a new post's content. |
| `\wpPostAbleTrait\loadPost\equalPostType` | Filter: global `(bool $equal, string $class)`; class-specific `($global_result, bool $original_equal, string $class)` | Final truthiness accepts or rejects the loaded post type. |
| `\wpPostAbleTrait\loadPost\loadMeta` | Filter: global `(bool $load, wpPostAble $instance, string $class)`; class-specific `($global_result, bool $original_load, wpPostAble $instance, string $class)` | Default `true`; a false result skips the metadata read. |
| `\wpPostAbleTrait\loadPost\loading` | Action: `(wpPostAble &$instance, string $class)` for both names | Runs after post assignment and optional metadata loading. The instance is passed by reference. |
| `\wpPostAbleTrait\deletePost\beforeDeletePost` | Action: `(int $post_id, WP_Post $post, string $class)` for both names | Runs before the Core delete call, including attempts that fail. |
| `\wpPostAbleTrait\deletePost\afterDeletePost` | Action: `(int $post_id, WP_Post $post, string $class)` for both names | Runs only after Core reports success and receives the original post object. |

For a class `Vendor\Item`, the class-specific default-title filter is
`Vendor\Item\wpPostAbleTrait\init\defaultTitle`.

## Deliberate exclusions

The 1.0 API does not include a public/protected initializer, reload or rebind
method, direct access to the model's `$post` association property, parameter or
metadata deletion, multi-value metadata mutation, automatic persistence, post
reordering, or PHP 8-only signatures.
