# Self-healing URLs in Laravel

This package was inspired [Aaron Francis](https://www.youtube.com/watch?v=a6lnfyES-LA) on YouTube - thanks Aaron!
This lightweight package allows you to create URLs that are able to self-heal, regardless of the slug provided.

This is great for SEO purposes, allowing you to change slugs without worrying, and will force a 301 redirect to the
correct URL.

This technique is commonly used on well known websites such as Amazon and Medium to allow slugs to change without
breaking the actual URL.

An example of this would be visiting `https://your-site.com/posts/old-slug-12345` automatically redirecting you to
`https://your-site.com/posts/new-slug-12345`. It does this based on the persisted unique ID at the end of the slug.

This makes use of Laravel's pre-existing route model binding.

## Installation

You can install the package via composer:

```bash
composer require motomedialab/laravel-self-healing-urls
```

## Usage

To use this package, simply install it, apply the provided trait to your model and tell
the trait where the models slug can be found.

In the below examples I've used a `Post` model, but this really can apply to any model you like.

```php
<?php

namespace App\Models;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use MotoMediaLab\LaravelSelfHealingUrls\HasSelfHealingUrl;

class Post extends Model {

    use HasSelfHealingUrl;
    
    public function getRouteBindingSlug(): string
    {
        return Str::slug($this->title);
    }

}
```

Once you've done this, you'll also need to add another column to your migrations.
This column will store the unique value that should be used within the URL.

I've added a helper to do this:

```php
<?php

use App\Models\Post;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            Post::selfHealingUrlMigration($table);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            Post::selfHealingUrlMigration($table, true);
        });
    }
};
```

## Extending

While the above will get you going, the package is highly extensible by overriding the traits default methods.
A few examples below:

##### Override the binding column name stored in the database:

```php
public function getRouteBindingKeyName(): string
{
    return 'new_binding_key';
}
```

*Note*: You will need to re-run migrations after changing this key.

##### Override the way the unique ID is generated:

```php
public function getRouteBindingKeyName(): string
{
    return Str::random(4); // generate random four character binding key
}
```

By default, the unique ID is determined using PHP's `uniqid` function. Regardless, on model creation,
it'll automatically attempt to set a unique ID, and automatically avoid conflicts by generating up to three times.
If it fails to generate a unique key after three attempts, an exception will be thrown.

##### Update the way the absolute URL to the model is determined (I recommend doing this)

```php
public function getModelUrl(): string
{
    return route('posts.show', $this);
}
```

By default, because I don't know your routing structure, it attempts to recreate an absolute
URL based on the matched routes name. If no matched route exists, then redirecting will fail
and this method should be overridden.

### Security

If you discover any security related issues, please email technical@motocom.co.uk instead of using the issue tracker.

### License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
