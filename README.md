# What is wpPostAble

Library provides a functionality for associating your models with WordPress WP_Post model.
Each instance holds a WP_Post, creating one only when no existing post or ID is supplied.

## Project documentation

- [Versioning and compatibility policy](VERSIONING.md)
- [1.0 API reference](docs/API.md)
- [Upgrading from 0.7.x to 1.0](docs/UPGRADING-1.0.md)
- [Changelog](CHANGELOG.md)
- [Local WordPress development](docs/LOCAL-DEVELOPMENT.md)
- [Testing and coverage](docs/TESTING.md)
- [Release runbook](docs/RELEASING.md)
- [Contributing](CONTRIBUTING.md)
- [1.0 delivery plan](docs/plan-1.0.md)

The 1.0 interface has 19 methods. The most common operations include

- `$instance->getTitle();`
- `$instance->setTitle();`
- `$instance->getSlug();`
- `$instance->setSlug();`
- `$instance->getMenuOrder();`
- `$instance->setMenuOrder();`
- `$instance->getMetaField();`
- `$instance->setMetaField();`
- `$instance->getStatus();`
- `$instance->setStatus();`
- `$instance->getPost();`
- `$instance->getPostType();`
- `$instance->savePost();`
- `$instance->publish();`
- `$instance->draft();`

See the [API reference](docs/API.md) for exact PHP 7.4-compatible signatures,
return behavior, exceptions, hooks, and persistence rules. There is no public
reload or rebind operation; create another model instance to load another post.

Use

- `$instance->getParam();`
- `$instance->setParam();`

to manage the JSON parameter map stored in `WP_Post::post_content_filtered`.
`setParam()` changes the in-memory post, returns `void`, and requires
`savePost()` for persistence.

# How to use

1. Create your own class based on wpPostAble interface

    ```php
   use iTRON\wpPostAble\wpPostAble;
   use iTRON\wpPostAble\wpPostAbleTrait;
   use iTRON\wpPostAble\Exceptions\wppaCreatePostException;
   use iTRON\wpPostAble\Exceptions\wppaLoadPostException;
    
   class Item implements wpPostAble {
      use wpPostAbleTrait;
      
      const POST_TYPE = 'item';
   }
    ```

2. Call the private `wpPostAble()` trait initializer at the beginning of the
   `__construct()` method of the class that imports the trait.

   Pass to it two parameters

   `$post_type` _string_ WP post type, associated with your class

   `$post_id`   `int|WP_Post|null`    Existing post or ID, or nothing for creating a new post

   ```php
      /**
       * @param int|WP_Post|null $post_id
       *
       * @throws wppaLoadPostException
       * @throws wppaCreatePostException
       */
      public function __construct( $post_id = null ) {
         $this->wpPostAble( self::POST_TYPE, $post_id );
         
         // Do anything you need
      }
   ```

   `wpPostAble()` is a required constructor-only trait-composition seam. It is
   not public, protected, or overridable. The associated `$post` property and
   the `loadPost()`/`loadPostObject()` helpers are also private. Use
   `getPost()` for supported access to the mutable `WP_Post`, and construct a
   new model when a different post must be loaded.

## Now you are able to use your class

Create new post

```php
$item = new Item();
```

or load from existing one

```php
$item = new Item( $post_id );
```

or reuse an existing `WP_Post` without looking it up again:

```php
$post = get_post( $post_id );
if ( ! $post instanceof WP_Post ) {
   throw new RuntimeException( 'Post not found.' );
}

$item = new Item( $post );
```

Passing `null` or `0` creates a new post. Passing a non-zero integer loads by ID.
Passing a `WP_Post` validates its post type, retains the same object instance,
loads metadata, and runs the normal loading filters and actions.
Other input types, including numeric strings, throw `TypeError`.

When you create an instance without an existing post or ID, wpPostAble creates a
new draft in WordPress.

Let's try change the title
```php
$item->setTitle('The best item');
```
Set a slug through the same in-memory, chainable API:

```php
$item->setSlug('the-best-item');
```

Set WordPress's integer `menu_order` field in memory in the same way:

```php
$item->setMenuOrder(-7);
```

The title, slug, and menu order are still only in memory. Persist them explicitly:

```php
$item->savePost();
```

`setSlug()` keeps the supplied value on the current `WP_Post` and does not save
automatically. During `savePost()`, WordPress Core may normalize the slug or make
it unique. Reload the model to observe the persisted Core value:

```php
$item = new Item( $item->getPost()->ID );
$slug = $item->getSlug();
$menuOrder = $item->getMenuOrder();
```

New posts start with menu order `0`. `setMenuOrder()` accepts any integer and
does not save automatically. The library does not impose a range, reorder other
posts, or change how WordPress queries use `menu_order`.

Maybe it's time to publish?
```php
$item->publish();
```

You can do it by single line
```php
$item->setTitle('The best item')->setSlug('the-best-item')->setMenuOrder(-7)->publish();
```

For the complete contract, see [API.md](docs/API.md). Consumers moving from
`0.7.x` should follow [UPGRADING-1.0.md](docs/UPGRADING-1.0.md), especially when
they implement the interface manually, override trait methods, or previously
accessed protected `$post` directly.
