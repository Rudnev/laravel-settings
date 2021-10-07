<?php

declare(strict_types=1);

namespace Rudnev\Settings\Contracts;

use Rudnev\Settings\Scopes\Scope;

interface StoreContract
{
    /**
     * Get the settings store name.
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Set the settings store name.
     *
     * @param  string  $name
     * @return void
     */
    public function setName(string $name): void;

    /**
     * Get the scope.
     *
     * @return \Rudnev\Settings\Scopes\Scope
     */
    public function getScope(): Scope;

    /**
     * Set the scope.
     *
     * @param  \Rudnev\Settings\Scopes\Scope  $scope
     * @return void
     */
    public function setScope(Scope $scope): void;

    /**
     * Determine if an item exists in the settings store.
     *
     * @param  string  $key
     * @return bool
     */
    public function has(string $key): bool;

    /**
     * Retrieve an item from the settings store by key.
     *
     * @param  string  $key
     * @return mixed
     */
    public function get(string $key);

    /**
     * Retrieve multiple items from the settings store by key.
     *
     * Items not found in the settings store will have a null value.
     *
     * @param  iterable  $keys
     * @return array
     */
    public function getMultiple(iterable $keys): array;

    /**
     * Get all the settings items.
     *
     * @return array
     */
    public function all(): array;

    /**
     * Store an item in the settings store.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return void
     */
    public function set(string $key, $value): void;

    /**
     * Store multiple items in the settings store.
     *
     * @param  iterable  $values
     * @return void
     */
    public function setMultiple(iterable $values): void;

    /**
     * Remove an item from the settings store.
     *
     * @param  string  $key
     * @return bool
     */
    public function forget(string $key): bool;

    /**
     * Remove multiple items from the settings store.
     *
     * @param  iterable  $keys
     * @return bool
     */
    public function forgetMultiple(iterable $keys): bool;

    /**
     * Remove all items from the settings store.
     *
     * @return bool
     */
    public function flush(): bool;

    /**
     * Set the scope.
     *
     * @param  \Rudnev\Settings\Scopes\Scope  $scope
     * @return \Rudnev\Settings\Contracts\StoreContract
     */
    public function scope(Scope $scope): self;
}
