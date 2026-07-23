<?php

namespace Tests\Feature;

use App\Models\AddOn;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * DatabaseTransactions, not RefreshDatabase: this runs against the developer's own
 * database, and wiping it to test an endpoint would be a poor trade.
 */
class TranslationTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Modules moved to Composer packages, but the endpoint only looked in the old
     * packages/local path, so every module string fell back to its English key in
     * every language. It failed silently: File::exists() is simply false and the
     * loop merges nothing.
     */
    public function test_an_enabled_modules_translations_are_merged(): void
    {
        $addon = AddOn::where('is_enable', true)
            ->whereNotNull('package_name')
            ->get()
            ->first(fn ($a) => File::exists(base_path("vendor/zerp/{$a->package_name}/src/Resources/lang/fr.json")));

        if (!$addon) {
            $this->markTestSkipped('needs an installed module shipping fr.json; run app:install');
        }

        $packageStrings = json_decode(
            File::get(base_path("vendor/zerp/{$addon->package_name}/src/Resources/lang/fr.json")),
            true,
        );

        // A key this module translates, that the core does not already carry: proves
        // the merge, rather than the core file happening to hold the same string.
        $core = json_decode(File::get(resource_path('lang/fr.json')), true) ?? [];
        $key = collect($packageStrings)
            ->filter(fn ($value, $k) => $k !== $value && !array_key_exists($k, $core))
            ->keys()
            ->first();

        $this->assertNotNull($key, "{$addon->package_name} carries no translated key of its own");

        $response = $this->get(route('languages.translations', 'fr'));

        $response->assertOk();
        $this->assertSame(
            $packageStrings[$key],
            $response->json("translations.{$key}"),
            "the {$addon->module} module's translations are not reaching the browser",
        );
    }

    /**
     * Edits are stored as app-owned overrides, because the module directory is
     * Composer-managed and replaced on every update. An override has to beat the
     * defaults of *every* module, not just its own: modules share key names, so
     * merging an override alongside its own module let the next module's default
     * overwrite it again.
     */
    public function test_a_module_override_beats_every_modules_shipped_string(): void
    {
        $addon = AddOn::where('is_enable', true)->whereNotNull('package_name')->first();

        if (!$addon) {
            $this->markTestSkipped('needs an installed module; run app:install');
        }

        // A key several modules define, so the merge order actually gets exercised.
        $key = 'Employee';
        $override = resource_path("lang/modules/{$addon->package_name}/fr.json");
        $preexisting = File::exists($override) ? File::get($override) : null;

        File::ensureDirectoryExists(dirname($override));
        File::put($override, json_encode([$key => 'ZZ-OVERRIDE']));

        try {
            $response = $this->get(route('languages.translations', 'fr'));

            $response->assertOk();
            $this->assertSame('ZZ-OVERRIDE', $response->json("translations.{$key}"));
            $this->assertSame(
                'Tableau de bord',
                $response->json('translations.Dashboard'),
                'an override must not displace the strings around it',
            );
        } finally {
            $preexisting === null ? File::delete($override) : File::put($override, $preexisting);
        }
    }

    public function test_a_disabled_modules_strings_are_not_sent(): void
    {
        $addon = AddOn::where('is_enable', true)
            ->whereNotNull('package_name')
            ->get()
            ->first(fn ($a) => File::exists(base_path("vendor/zerp/{$a->package_name}/src/Resources/lang/fr.json")));

        if (!$addon) {
            $this->markTestSkipped('needs an installed module shipping fr.json; run app:install');
        }

        $before = count($this->get(route('languages.translations', 'fr'))->json('translations'));

        // Rolled back by DatabaseTransactions.
        $addon->update(['is_enable' => false]);

        $after = count($this->get(route('languages.translations', 'fr'))->json('translations'));

        $this->assertLessThan($before, $after, "disabling {$addon->module} did not shrink the payload");
    }
}
