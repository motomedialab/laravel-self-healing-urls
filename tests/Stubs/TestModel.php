<?php

namespace Motomedialab\LaravelSelfHealingUrls\Tests\Stubs;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Motomedialab\LaravelSelfHealingUrls\HasSelfHealingUrls;

class TestModel extends Model
{
    use HasFactory;
    use HasSelfHealingUrls;

    protected $guarded = [];

    public function getRouteBindingSlug(): string
    {
        return Str::of($this->name)->slug();
    }

    protected static function newFactory(): TestModelFactory
    {
        return new TestModelFactory();
    }
}