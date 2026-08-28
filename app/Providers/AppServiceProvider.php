<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Must happen in register(), not boot(): Livewire's own service
        // provider calls ComponentHookRegistry::boot() during ITS boot()
        // phase, which snapshots whatever hooks are registered at that
        // instant and wires up the mount/hydrate listeners those hooks rely
        // on to ever fire on a component. Registering this hook from our
        // boot() (all providers' register() run before any provider's boot())
        // was a no-op — the hook was added to the list too late to be
        // included in that snapshot, so it silently never intercepted a
        // single call. Modules with their own explicit hasPermission() check
        // inside the write method (deals/quotations/contacts, etc.) were
        // never actually protected by this hook; this was their only layer.
        \Livewire\Livewire::componentHook(\App\Livewire\Hooks\RestrictEditing::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Task::related_type stores these short strings (not FQCNs), so
        // Task::related() (a morphTo) needs a map to resolve them —
        // otherwise it tries to instantiate a class literally named
        // "deal"/"contact"/"company"/"project" and fails.
        Relation::morphMap([
            'deal' => \App\Models\deal::class,
            'contact' => \App\Models\Contact::class,
            'company' => \App\Models\company::class,
            'project' => \App\Models\Project::class,
        ]);

        // @money($amount) — formats in the current client's company currency.
        Blade::directive('money', fn ($expr) => "<?php echo \\App\\Support\\Money::client($expr); ?>");

        // Admin/staff changes -> client-portal notifications (and chat pings).
        \App\Support\AlertHooks::register();
    }
}
