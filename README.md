# What is wpPostAble

Library provides a functionality for associating your models with WordPress WP_Post model.
Once you create the instance, wpPostAble creates the WP_Post object and stores this in your instance.

You can manage your instance with such methods as

- $instance->getTitle();
- $instance->setTitle();
- $instance->getMetaField();
- $instance->setMetaField();
- $instance->getStatus();
- $instance->setStatus();
- $instance->getPost();
- $instance->getPostType();
- $instance->savePost();
- $instance->loadPost();
- $instance->publish();
- $instance->draft();

and others.

# How to use
## Preparing step by step.

1. Create your own class based on wpPostAble interface

    ```php
    use iTRON\wpPostAble\wpPostAble;
    
    class Item implements wpPostAble{
        use wpPostAbleTrait;
    }
    ```

2. Call `wpPostAble()` method in the beginning `__construct()` of your class.

   Pass to it two parameters

   `$post_type` _string_ WP post type, associated with your class

   `$post_id`   _int_    Post ID for existing post, or nothing for creating new post

   ```php
      /**
       * @throws Exception\wppaLoadPostException
       * @throws Exception\wppaCreatePostException
       */
      public function __construct() {
         $this->wpPostAble( POST_TYPE, $post_id );
         
         // Do anything you need
      }
   ```

## Now you are able to use your class

Create new post

```php
$item = new Item();
```

or load from existing

```php
$item = new Item( $post_id );
```


Once you create an instance, wpPostAble creates new post in WordPress as a draft.

Let's try change the title
```php
$item->setTitle('The best item');
```
Now you have set title, and let's try to save it in database
```php
$item->savePost();
```

Maybe it's time to publish?
```php
$item->publish();
```

You can do it by single line
```php
$item->setTitle('The best item')->publish();
```
