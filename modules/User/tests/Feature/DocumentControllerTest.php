<?php

namespace Modules\User\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\User\Enum\DocumentType;
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

    /**
     * A stored type DocumentType doesn't list (legacy data, or one a project
     * added) used to throw a ValueError from the enum cast and crash the page.
     */
    public function test_user_page_renders_a_document_type_the_enum_does_not_list(): void
    {
        $this->admin->givePermissionTo(
            Permission::firstOrCreate(['name' => 'View User'], ['module_name' => 'User', 'guard_name' => 'web'])
        );

        $targetUser = User::factory()->create();
        $documentId = UserDocument::factory()->create(['user_id' => $targetUser->id, 'document_type' => 'nid'])->getKey();
        UserDocument::whereKey($documentId)->update(['document_type' => 'trade_license']);

        $this->actingAs($this->admin)
            ->get(route('admin.users.show', $targetUser))
            ->assertOk()
            ->assertSee('Trade License');

        $document = UserDocument::findOrFail($documentId);
        $this->assertSame('trade_license', $document->document_type);
        $this->assertSame('trade_license', $document->toArray()['document_type']);
    }

    public function test_a_listed_document_type_still_reads_as_the_enum(): void
    {
        $document = UserDocument::findOrFail(UserDocument::factory()->create(['document_type' => 'passport'])->getKey());

        $this->assertSame(DocumentType::Passport, $document->document_type);
        $this->assertSame('passport', $document->toArray()['document_type']);
        $this->assertSame('Passport', $document->typeLabel());
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

        // $targetUser->documents()->findOrFail() correctly 404s: the document
        // exists, but not scoped under this user.
        $response->assertNotFound();
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
