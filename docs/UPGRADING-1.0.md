# Upgrading from 0.7.x to 1.0

wpPostAble 1.0 freezes the API described in [API.md](API.md). Trait consumers
that do not override methods and already use `getPost()` normally need only a
dependency update and verification. Manual interface implementations, method
overrides, and code that accessed protected trait state require review.

No database or stored-data migration is required. Existing post fields,
metadata serialization, and the JSON parameter location remain unchanged.

## Runtime and dependency preparation

Version 1.0 continues the `0.7.0` minimums of PHP 7.4, WordPress 6.0, and the
JSON PHP extension. Confirm these before changing the Composer constraint.

Validate a release candidate explicitly rather than following the moving
`dev-master` branch:

```json
{
  "require": {
    "hokoo/wppostable": "1.0.0-rc.1"
  }
}
```

After the stable release, use `^1.0` to receive compatible 1.x updates without
adopting 2.0 automatically.

## Update manual interface implementations and overrides

The 1.0 interface contains 19 methods. It adds the already-public parameter
methods to the interface and aligns return declarations with the trait wherever
PHP 7.4 can express the contract:

```php
public function getPost(): WP_Post;
public function savePost(): self;
public function deletePost(): void;
public function getPostType(): string;
public function getTitle(): string;
public function setTitle(string $title): self;
public function getSlug(): string;
public function setSlug(string $slug): self;
public function getMenuOrder(): int;
public function setMenuOrder(int $menuOrder): self;
public function getStatus(): string;
public function setStatus(string $status): self;
public function setMetaField(string $meta_key, $meta_value): self;
public function getMetaField(string $meta_key);
public function getMetaFields(): array;
public function getParam(string $param);
public function setParam(string $param, $value): void;
public function publish(): self;
public function draft(): self;
```

Classes using `wpPostAbleTrait` without overrides receive these implementations
automatically. A manual implementation or override must use a compatible
signature. In particular:

- add `getParam()` and `setParam()` if the trait is not used;
- add the exact return declarations shown above;
- keep `getParam()` and `getMetaField()` natively untyped; their documented
  result is `mixed|null`, while native `mixed` requires PHP 8;
- keep `setParam()` non-chainable with a `void` return;
- preserve parameter names if callers on PHP 8 may use named arguments.

Run the consumer test suite on PHP 7.4 as well as its newest supported PHP
version. A signature mismatch can fail while the class is loaded, before a test
method executes.

## Keep initialization inside the adopting class

The initializer remains a private constructor-only trait-composition seam:

```php
/** @param int|WP_Post|null $post_id */
private function wpPostAble(string $post_type, $post_id = 0): self
```

The class that imports the trait should call it from its own constructor. Do not
call it from external code or treat it as an overridable method. When the trait
is imported by a base class, let that base class perform initialization; a child
cannot directly call the inherited private method.

Version 1.0 accepts exactly an integer, `WP_Post`, or `null`. Numeric strings,
floats, booleans, and arbitrary post-like objects now throw `TypeError`. Validate
and convert trusted external IDs explicitly:

```php
$post_id = filter_var($input, FILTER_VALIDATE_INT);
if (false === $post_id) {
    throw new InvalidArgumentException('Invalid post ID.');
}

$item = new Item((int) $post_id);
```

A `WP_Post` can be passed directly to avoid another lookup. Existing downstream
constructors typed as `int` must widen their own PHP 7.4-compatible boundary if
they want to expose this option; the library does not change consumer
constructors automatically.

## Replace direct `$post` access

The trait's association property changes from protected to private. Replace
reads and field mutations with `getPost()`:

```php
// 0.7.x
$id = $this->post->ID;
$other_id = $other->post->ID;
$this->post->post_title = 'Updated';

// 1.0
$id = $this->getPost()->ID;
$other_id = $other->getPost()->ID;
$this->getPost()->post_title = 'Updated';
```

Do not assign or unset the association itself. To associate a model with a
different post, construct a new model instance. `getPost()` returns the same
mutable `WP_Post`, so supported field mutations retain their previous behavior
and are persisted by `savePost()`.

After a successful `deletePost()`, the model is invalid and `getPost()` is no
longer a valid operation.

## Replace attempted `loadPost()` calls

`loadPost()` was private in released Composer source; its old README listing was
incorrect. It is not promoted to the 1.0 public API.

```php
// Unsupported
$item->loadPost($other_id);

// Supported
$item = new Item($other_id);
```

The same rule applies to `loadPostObject()`. Version 1.0 has no public reload or
rebind operation because replacing the post on a live model would also need a
defined reset policy for loaded and dirty metadata.

## Add slug and menu-order methods

Manual implementations must add `getSlug()`, `setSlug()`, `getMenuOrder()`, and
`setMenuOrder()` with the signatures above. Trait consumers receive them
automatically.

Both setters change only the in-memory `WP_Post` and are chainable. Call
`savePost()` to persist them. WordPress Core may normalize or uniquify a slug;
reload the model to observe the persisted value. Menu order accepts any integer,
and the library does not reorder related posts.

## Handle stable fallback error codes

An original `WP_Error` returned by WordPress is still preserved unchanged. If
Core instead returns an empty non-error result, 1.0 supplies a classifiable
fallback:

| Operation | Exception | Code | Data |
|---|---|---|---|
| Create | `wppaCreatePostException` | `create_post_failed` | `['post_type' => string]` |
| Save | `wppaSavePostException` | `save_post_failed` | `['post_id' => int]` |

Deletion continues to use `delete_post_failed` with `['post_id' => int]` when
Core does not return a deleted `WP_Post`.

If code previously treated an empty fallback code as a special case, switch to
the stable code. Do not match exact exception message text.

## Exception compatibility and safe logging

The existing exception classes remain non-final. Their public constructors,
inheritance, public context-property existence, getters, and constants remain
supported for 1.x. Prefer getters in new code; direct property mutation,
serialization shape, arbitrary invalid constructor combinations, and exact
message wording are not guaranteed.

Do not serialize or log an entire exception object. It can retain the model,
post content, loaded metadata, and arbitrary `WP_Error::error_data`. Log only
allowlisted scalar fields needed for diagnosis, such as exception class, stable
error/reason code, operation, post ID or post type, and a correlation ID. Escape
diagnostic text for its destination and redact it before returning it through an
HTTP response.

## Verification checklist

- Install the exact RC rather than relying on an existing lock or `dev-master`.
- Load every manual `wpPostAble` implementation to catch signature errors.
- Exercise create, ID load, and direct `WP_Post` initialization.
- Exercise title, slug, menu order, status, metadata, and parameter persistence.
- Exercise create/save/delete and JSON failure handlers by stable codes or
  getters rather than message strings.
- Search consumer source for `->post`, `loadPost(`, trait method overrides,
  direct exception-property access, and manual `implements wpPostAble` classes.
- Run on PHP 7.4 and the consumer's newest supported PHP version.
- Use an isolated WordPress database or remove every created fixture through
  WordPress Core after the check.
