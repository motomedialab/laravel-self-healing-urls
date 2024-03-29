<?php

namespace Motomedialab\LaravelSelfHealingUrls;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\ColumnDefinition;

/**
 * @mixin Model
 */
trait HasSelfHealingUrls
{
    /**
     * Generate the migration for our healing URL.
     */
    public static function selfHealingUrlMigration(Blueprint $table, bool $rollback = false): ?ColumnDefinition
    {
        $model = new self;
        $column = $model->getRouteBindingKeyName();

        if ($rollback) {
            $table->dropColumn($column);

            return null;
        }

        $migration = $table->string($column);

        if (! $table->creating()) {
            $migration->after($model->getKeyName());
        }

        return $migration->unique();
    }

    /**
     * Determine the database key that should be used for our
     * route binding.
     */
    public function getRouteBindingKeyName(): string
    {
        return 'route_binding_id';
    }

    /**
     * When creating our model, ensure it has an entirely unique
     * route binding ID.
     */
    protected static function bootHasSelfHealingUrls(): void
    {
        $attempts = 0;
        $exists = function (self $model) use ($attempts) {

            if ($attempts > 3) {
                throw new \Exception(
                    class_basename($model).'::generateHealingUniqueId does not have enough '.
                    'entropy and failed URL generation. This method should generate a very random ID.'
                );
            }

            return $model
                ->newQuery()
                ->where($model->getRouteBindingKeyName(), $model->getRouteBindingKey())
                ->exists();
        };

        static::creating(function (self $model) use ($exists, &$attempts) {
            do {
                // enforce a unique ID for our model
                $model->setAttribute($model->getRouteBindingKeyName(), $model->generateHealingUniqueId());
                $attempts++;
            } while ($exists($model));
        });
    }

    /**
     * Determine the current key/unique ID for our route binding.
     */
    public function getRouteBindingKey(): string
    {
        return $this->getAttribute($this->getRouteBindingKeyName());
    }

    /**
     * Generate a unique ID.
     */
    public function generateHealingUniqueId(): string
    {
        return substr(uniqid(), -8);
    }

    /**
     * Determine our absolute URL to this post.
     */
    public function getRouteKey(): string
    {
        return $this->getRouteBindingSlug().'-'.$this->getRouteBindingKey();
    }

    /**
     * Determine the slug that our sel- healing URL should use.
     */
    abstract public function getRouteBindingSlug(): string;

    /**
     * Generate our query to resolve our model from the database
     * using the route binding key.
     */
    public function resolveRouteBindingQuery($query, $value, $field = null)
    {
        $uniqId = $this->resolveRouteBindingParameters($value)[2] ?? null;

        return $query->where($this->getRouteBindingKeyName(), $uniqId);
    }

    /**
     * Extract our binding parameters.
     */
    public function resolveRouteBindingParameters(string $value): ?array
    {
        preg_match('/^(.*)-(.*)$/', $value, $matches);

        if (count($matches) !== 3) {
            return null;
        }

        return $matches;
    }

    /**
     * Resolve our model from the given parameters.
     */
    public function resolveRouteBinding($value, $field = null): ?Model
    {
        $model = parent::resolveRouteBinding($value, $field);

        $slug = $this->resolveRouteBindingParameters($value)[1] ?? null;

        if ($model && ($model->getRouteBindingSlug() !== $slug)) {
            abort(301, 'Moved Permanently', ['Location' => $model->getModelUrl()]);
        }

        return $model;
    }

    /**
     * Automatically determine the URI for this model. It's recommended
     * to extend this method.
     */
    public function getModelUrl(): string
    {
        if (request()?->route()) {
            return route(request()->route()->getName(), $this);
        }

        throw new \Exception('Unable to determine self-healing URL. Extend the getModelUri() method');
    }
}
