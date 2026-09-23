<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminProfileAvatarTest extends TestCase
{
    use DatabaseTransactions;

    public function test_admin_can_upload_view_and_remove_profile_photo()
    {
        Storage::fake('local');
        $admin = $this->admin();

        $this->actingAs($admin)->put(route('admin.profile.update'), [
            'name' => 'Admin Foto',
            'username' => $admin->username,
            'email' => 'admin.foto@example.test',
            'avatar' => UploadedFile::fake()->image('admin.png', 900, 600),
        ])->assertRedirect(route('admin.profile.edit'));

        $admin->refresh();
        $this->assertNotNull($admin->avatar_path);
        Storage::disk('local')->assertExists($admin->avatar_path);
        $image = getimagesize(Storage::disk('local')->path($admin->avatar_path));
        $this->assertSame('image/webp', $image['mime']);
        $this->assertSame([320, 320], [$image[0], $image[1]]);

        $this->actingAs($admin)->get(route('admin.profile.avatar'))
            ->assertOk()->assertHeader('Content-Type', 'image/webp');

        $oldPath = $admin->avatar_path;
        $this->actingAs($admin)->put(route('admin.profile.update'), [
            'name' => $admin->name,
            'username' => $admin->username,
            'email' => $admin->email,
            'remove_avatar' => '1',
        ])->assertRedirect(route('admin.profile.edit'));

        $this->assertNull($admin->fresh()->avatar_path);
        Storage::disk('local')->assertMissing($oldPath);
    }

    public function test_admin_profile_rejects_script_and_fake_image()
    {
        Storage::fake('local');
        $admin = $this->admin();
        $fakeImage = UploadedFile::fake()->createWithContent('foto.jpg', '<script>alert(1)</script>');

        $this->actingAs($admin)->from(route('admin.profile.account'))->put(route('admin.profile.update'), [
            'name' => '<script>alert(1)</script>',
            'username' => 'admin<script>',
            'email' => 'admin@example.test',
            'avatar' => $fakeImage,
        ])->assertRedirect(route('admin.profile.account'))
            ->assertSessionHasErrors(['name', 'username', 'avatar']);
    }

    public function test_admin_cannot_read_avatar_outside_own_storage_prefix()
    {
        Storage::fake('local');
        $admin = $this->admin();
        $foreignPath = 'admin-profile-avatars/'.($admin->id + 1).'/foreign.webp';
        Storage::disk('local')->put($foreignPath, 'not-the-current-admin-avatar');
        $admin->update(['avatar_path' => $foreignPath]);

        $this->actingAs($admin)->get(route('admin.profile.avatar'))->assertNotFound();
    }

    private function admin(): User
    {
        return User::create([
            'name' => 'Admin Profil',
            'username' => 'admin_profile_'.uniqid(),
            'email' => 'admin.profile.'.uniqid().'@example.test',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'status' => 'active',
        ]);
    }
}
