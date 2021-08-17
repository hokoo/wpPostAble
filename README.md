#What is wpPostAble
Library provides a functionality for associating your classes with WordPress class WP_Post.
Once you create the instance, wpPostAble creates the WP_Post object and stores this in your instance.

You can manage your instance with such methods as

- $instance->getPost();
- $instance->getPostType();
- $instance->savePost();
- $instance->loadPost();
- $instance->getStatus();
- $instance->setStatus();
- $instance->getTitle();
- $instance->setTitle();

and others.

#How to use
##Preparing step by step.

1. Create your own class based on wpPostAble interface

    ```php
    use iTRON;
    
    class Item implements WPPostAble{
        use WPPostAbleTrait;
    }
    ```

2. By using this library, you should assure the existence following entities in your class:

   - Fields
     
    ```php
    private $post_type = 'post_type_assign_with';
    
    private $initial_method = '_init';
    ```
   
   Field `$post_type` stores name of post type, associated with your class. You should guarantee the existence this post type.
   
   Field `$initial_method` stores name of private method in your class. This method you should create as described below.

   - Methods
   ```php
   private function _init(): callable {
      return function ( self $group ){
         // $group is the $this passed by link
         // Do your initial actions
      };
   }
   ```
   
3. Finally, call `wpPostAble()` method in the beginning `__construct()` of your class.
   ```php
      /**
       * @throws Exception\WPPATLoadPostException
       * @throws Exception\WPPATCreatePostException
       */
      public function __construct( int $post_id = 0 ) {
         $this->wpPostAble( $post_id );
         
         // Do anything you need
      }
   ```

##Now you are able to use your class
```php
$item = new Item();
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
