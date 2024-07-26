<?php

namespace Christophrumpel\MissingLivewireAssertions;

use Livewire\Features\SupportTesting\Testable;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class MissingLivewireAssertionsServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('missing-livewire-assertions');
    }

    public function bootingPackage(): void
    {
        Testable::mixin(new CustomLivewireAssertionsMixin());
    }
}
