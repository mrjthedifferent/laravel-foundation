<?php

namespace Modules\User\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\User\Models\UserDocument;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DocumentControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['is_active' => true]);
        $role = Role::firstOrCreate(['name' => 'admin']);
        $role->givePermissionTo(
            Permission::firstOrCreate(
                ['name' => 'Edit User'],
                ['module_name' => 'User', 'guard_name' => 'web']
            )
        );
        $this->admin->assignRole($role);
    }

    public function test_authorized_user_can_upload_document(): void
    {
        Storage::fake('public');

        $targetUser = User::factory()->create();

        $response = $this->actingAs($this->admin)->post(
            route('admin.users.documents.store', $targetUser),
            [
                'document_type' => 'nid',
                'file' => UploadedFile::fake()->image('front.jpg', 800, 600),
                'document_number' => 'NID123456',
            ]
        );

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Document uploaded successfully');

        $this->assertDatabaseHas('user_documents', [
            'user_id' => $targetUser->id,
            'document_type' => 'nid',
            'document_number' => 'NID123456',
        ]);
    }

    public function test_unauthorized_user_cannot_upload_document(): void
    {
        $unauthorizedUser = User::factory()->create(['is_active' => true]);
        $targetUser = User::factory()->create();

        $response = $this->actingAs($unauthorizedUser)->post(
            route('admin.users.documents.store', $targetUser),
            [
                'document_type' => 'nid',
                'file' => UploadedFile::fake()->image('front.jpg'),
            ]
        );

        $response->assertForbidden();
    }

    public function test_upload_document_validates_required_fields(): void
    {
        $targetUser = User::factory()->create();

        $response = $this->actingAs($this->admin)->post(
            route('admin.users.documents.store', $targetUser),
            []
        );

        $response->assertSessionHasErrors(['document_type', 'file']);
    }

    public function test_authorized_user_can_delete_document(): void
    {
        $targetUser = User::factory()->create();
        $document = UserDocument::factory()->create([
            'user_id' => $targetUser->id,
            'document_type' => 'nid',
        ]);

        $response = $this->actingAs($this->admin)->delete(
            route('admin.users.documents.destroy', [$targetUser, $document->id])
        );

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Document deleted successfully');

        $this->assertDatabaseMissing('user_documents', ['id' => $document->id]);
    }

    public function test_unauthorized_user_cannot_delete_document(): void
    {
        $unauthorizedUser = User::factory()->create(['is_active' => true]);
        $targetUser = User::factory()->create();
        $document = UserDocument::factory()->create([
            'user_id' => $targetUser->id,
            'document_type' => 'nid',
        ]);

        $response = $this->actingAs($unauthorizedUser)->delete(
            route('admin.users.documents.destroy', [$targetUser, $document->id])
        );

        $response->assertForbidden();
    }

    public function test_cannot_delete_document_belonging_to_different_user(): void
    {
        $targetUser = User::factory()->create();
        $otherUser = User::factory()->create();
        $document = UserDocument::factory()->create([
            'user_id' => $otherUser->id,
            'document_type' => 'nid',
        ]);

        $response = $this->actingAs($this->admin)->delete(
            route('admin.users.documents.destroy', [$targetUser, $document->id])
        );

        $response->assertSessionHas('error');
        $this->assertDatabaseHas('user_documents', ['id' => $document->id]);
    }

    public function test_upload_rejects_invalid_document_type(): void
    {
        $targetUser = User::factory()->create();

        $response = $this->actingAs($this->admin)->post(
            route('admin.users.documents.store', $targetUser),
            [
                'document_type' => 'invalid_type',
                'file' => UploadedFile::fake()->image('front.jpg'),
            ]
        );

        $response->assertSessionHasErrors(['document_type']);
    }
}
