<?php

namespace Motomedialab\LaravelSelfHealingUrls\Tests\Feature;

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Str;
use Motomedialab\LaravelSelfHealingUrls\Middleware\DisableSelfHealingUrls;
use Motomedialab\LaravelSelfHealingUrls\Tests\Stubs\TestModel;
use Motomedialab\LaravelSelfHealingUrls\Tests\TestCase;

class SelfHealingUrlTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function defineRoutes($router): void
    {
        $router->middleware('web')
            ->get('/test-path/{model}', fn (TestModel $model) => $model->name)
            ->name('test-model-show');

        $router
            ->middleware([DisableSelfHealingUrls::class, 'web'])
            ->get('/test-path-unbound/{model}', fn (TestModel $model) => $model->name)
            ->name('test-model-show-unbound');
    }

    public function test_it_can_build_a_route_url()
    {
        $model = TestModel::factory()->create();

        $value = route('test-model-show', $model);

        $this->assertStringEndsWith(
            '/test-path/'.Str::slug($model->name).'-'.$model->route_binding_id,
            $value
        );
    }

    public function test_it_redirects_to_correct_url_with_invalid_slug()
    {
        $model = TestModel::factory()->create();

        $wrongRoute = str_replace(Str::slug($model->name), 'wrong-slug', route('test-model-show', compact('model')));

        $this->get($wrongRoute)->assertRedirect($model->getModelUrl());
    }

    public function test_it_returns_model_with_correct_slug()
    {
        $model = TestModel::factory()->create();

        $response = $this->get(route('test-model-show', compact('model')));

        $response->assertOk();
        $response->assertSee($model->name);
    }

    public function test_it_disables_self_healing_when_middleware_is_set()
    {
        $model = TestModel::factory()->create();

        $this->get('/test-path-unbound/'.$model->getKey())
            ->assertStatus(200)
            ->assertSee($model->name);
    }
}
