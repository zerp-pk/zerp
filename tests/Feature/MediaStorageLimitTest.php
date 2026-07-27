<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * zerp-pk/zerp#89: the first upload on a fresh company failed with
 * "Storage limit exceeded", because seeded plans carried a 0 KB quota.
 * The same check also never counted the incoming files.
 *
 * DatabaseTransactions, not RefreshDatabase: this suite runs against a real
 * database and must not wipe it.
 */
class MediaStorageLimitTest extends TestCase
{
    use DatabaseTransactions;

    private function company(int $storageLimitKb): User
    {
        $plan = Plan::create([
            'name' => 'Test ' . uniqid(),
            'storage_limit' => $storageLimitKb,
            'modules' => [],
        ]);

        $user = new User();
        $user->name = 'acme';
        $user->email = 'acme' . uniqid() . '@x.test';
        $user->password = Hash::make('x');
        $user->type = 'company';
        $user->lang = 'en';
        $user->active_plan = $plan->id;
        $user->storage_limit = $storageLimitKb;
        $user->email_verified_at = now();
        $user->save();

        $user->givePermissionTo(Permission::firstOrCreate(
            ['name' => 'create-media', 'guard_name' => 'web'],
            ['add_on' => 'user', 'module' => 'media', 'label' => 'Create Media'],
        ));

        return $user->fresh();
    }

    private function upload(User $company, int $sizeKb)
    {
        Storage::fake(config('filesystems.default'));

        return $this->actingAs($company)->post(route('media.batch'), [
            'files' => [UploadedFile::fake()->create('note.pdf', $sizeKb, 'application/pdf')],
        ]);
    }

    public function test_a_company_with_a_real_plan_quota_can_upload(): void
    {
        $response = $this->upload($this->company(5 * 1024 * 1024), 100);

        $response->assertOk();
    }

    public function test_an_upload_larger_than_the_quota_is_rejected(): void
    {
        // Previously passed: sum('size') on UploadedFile objects returned 0, so the
        // incoming file was never weighed against the limit.
        $response = $this->upload($this->company(50), 100);

        $response->assertStatus(422);
        $response->assertJsonPath('message', 'Storage limit exceeded');
    }

    public function test_a_plan_cannot_be_saved_without_a_quota(): void
    {
        // A 0 GB plan is what produced the bug; it is no longer a valid plan.
        $validator = \Validator::make(
            ['storage_limit' => 0],
            ['storage_limit' => (new \App\Http\Requests\StorePlanRequest())->rules()['storage_limit']],
        );

        $this->assertTrue($validator->fails());
    }
}
