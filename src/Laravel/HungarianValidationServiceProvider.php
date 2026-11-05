<?php

declare(strict_types=1);

namespace IntrAgile\HungarianValidation\Laravel;

use Faker\Generator;
use Illuminate\Contracts\Validation\Factory;
use Illuminate\Support\ServiceProvider;
use IntrAgile\HungarianValidation\Faker\HungarianIdentifierProvider;

final class HungarianValidationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        if (class_exists(Generator::class)) {
            // Also covers the per-locale generators the fake() helper registers. Those are
            // cached for the whole process and resolved again by every application instance.
            $this->app->afterResolving(Generator::class, static function (Generator $faker): void {
                $providers = $faker->getProviders();

                foreach (is_array($providers) ? $providers : [] as $provider) {
                    if ($provider instanceof HungarianIdentifierProvider) {
                        return;
                    }
                }

                $faker->addProvider(new HungarianIdentifierProvider($faker));
            });
        }
    }

    public function boot(): void
    {
        $lang = __DIR__.'/../../lang';

        $this->loadTranslationsFrom($lang, 'hungarian-validation');

        $this->publishes([$lang => $this->app->langPath('vendor/hungarian-validation')], 'hungarian-validation-lang');

        $this->callAfterResolving('validator', static function (Factory $factory): void {
            StringRules::register($factory);
        });
    }
}
