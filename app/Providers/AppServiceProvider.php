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
        // NOTE: the case-insensitive-model-name compatibility shim lives in
        // bootstrap/legacy_models.php (composer "files" autoload) — it must run
        // before any model reference, which a service provider cannot.

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
            'contact' => \App\Models\contact::class,
            'company' => \App\Models\company::class,
            'project' => \App\Models\Project::class,
        ]);

        // @money($amount) — formats in the current client's company currency.
        Blade::directive('money', fn ($expr) => "<?php echo \\App\\Support\\Money::client($expr); ?>");

        // Admin/staff changes -> client-portal notifications (and chat pings).
        \App\Support\AlertHooks::register();

        // Dynamically apply database-configured SMTP settings if host is configured
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('company_settings')) {
                $cs = \App\Models\CompanySetting::find(1);
                if ($cs) {
                    if (!empty($cs->mail_from_address)) {
                        config(['mail.from.address' => $cs->mail_from_address]);
                    }
                    if (!empty($cs->mail_from_name)) {
                        config(['mail.from.name' => $cs->mail_from_name]);
                    }
                    if (!empty($cs->smtp_host) && !empty($cs->smtp_port)) {
                        config([
                            'mail.default' => 'smtp',
                            'mail.mailers.smtp.host' => $cs->smtp_host,
                            'mail.mailers.smtp.port' => (int) $cs->smtp_port,
                            'mail.mailers.smtp.username' => $cs->smtp_username ?: null,
                            'mail.mailers.smtp.password' => $cs->smtp_password ?: null,
                            'mail.mailers.smtp.scheme' => $cs->smtp_encryption ?: null,
                        ]);
                    }
                }
            }
        } catch (\Throwable $e) {
            // Failsafe during migration or bootstrap
        }
    }
}
