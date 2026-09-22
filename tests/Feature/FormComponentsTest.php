<?php

namespace Mrj\Foundation\Tests\Feature;

use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\View;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Modules\User\Enum\Gender;
use Mrj\Foundation\Tests\TestCase;

class FormComponentsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Real requests get $errors from Illuminate\View\Middleware\ShareErrorsFromSession,
        // and old()'s session from Illuminate\Session\Middleware\StartSession attaching it
        // to the request — rendering a view directly in a test bypasses both entirely.
        View::share('errors', new ViewErrorBag);

        $this->startSession();
        $this->app['request']->setLaravelSession($this->app['session.store']);
    }

    public function test_input_renders_label_required_asterisk_and_value(): void
    {
        $html = view('components.form.input', ['name' => 'name', 'label' => 'Full Name', 'value' => 'Jane', 'required' => true])->render();

        $this->assertStringContainsString('for="name"', $html);
        $this->assertStringContainsString('Full Name', $html);
        $this->assertStringContainsString('<span class="text-danger">*</span>', $html);
        $this->assertStringContainsString('value="Jane"', $html);
        $this->assertStringContainsString('form-control form-control-sm', $html);
    }

    public function test_input_prefers_old_input_over_the_passed_value(): void
    {
        Session::flash('_old_input', ['name' => 'Typed Value']);

        $html = view('components.form.input', ['name' => 'name', 'value' => 'Stored Value'])->render();

        $this->assertStringContainsString('value="Typed Value"', $html);
    }

    public function test_input_handles_bracketed_field_names_for_old_input_and_errors(): void
    {
        Session::flash('_old_input', ['phone' => '+8801700000000']);

        $html = view('components.form.input', ['name' => 'phone', 'value' => null])->render();

        $this->assertStringContainsString('value="+8801700000000"', $html);
    }

    public function test_password_and_file_inputs_never_repopulate_from_old_input(): void
    {
        Session::flash('_old_input', ['password' => 'leaked-password']);

        $html = view('components.form.input', ['name' => 'password', 'type' => 'password'])->render();

        $this->assertStringNotContainsString('leaked-password', $html);
        $this->assertStringNotContainsString('value=', $html);
    }

    public function test_input_renders_validation_error(): void
    {
        $errors = new ViewErrorBag;
        $errors->put('default', new MessageBag(['email' => ['The email field is required.']]));

        $html = view('components.form.input', ['name' => 'email'])->with('errors', $errors)->render();

        $this->assertStringContainsString('The email field is required.', $html);
    }

    public function test_select_marks_the_matching_option_selected(): void
    {
        $html = view('components.form.select', [
            'name' => 'gender',
            'options' => ['male' => 'Male', 'female' => 'Female'],
            'selected' => 'female',
        ])->render();

        $this->assertMatchesRegularExpression('/<option value="female"\s+selected\s*>/', $html);
        $this->assertDoesNotMatchRegularExpression('/<option value="male"\s+selected\s*>/', $html);
    }

    public function test_select_unwraps_a_backed_enum_selected_value(): void
    {
        $html = view('components.form.select', [
            'name' => 'gender',
            'options' => ['male' => 'Male', 'female' => 'Female'],
            'selected' => Gender::Female,
        ])->render();

        $this->assertMatchesRegularExpression('/<option value="female"\s+selected\s*>/', $html);
    }

    public function test_select_multiple_marks_every_selected_option(): void
    {
        $html = view('components.form.select', [
            'name' => 'roles[]',
            'options' => [1 => 'Admin', 2 => 'User', 3 => 'Editor'],
            'selected' => [1, 3],
            'multiple' => true,
        ])->render();

        $this->assertMatchesRegularExpression('/<option value="1"\s+selected\s*>/', $html);
        $this->assertMatchesRegularExpression('/<option value="3"\s+selected\s*>/', $html);
        $this->assertDoesNotMatchRegularExpression('/<option value="2"\s+selected\s*>/', $html);
        $this->assertStringContainsString('multiple', $html);
    }

    public function test_textarea_renders_value_inside_the_tag(): void
    {
        $html = view('components.form.textarea', ['name' => 'description', 'value' => 'Some notes'])->render();

        $this->assertStringContainsString('>Some notes</textarea>', $html);
    }

    public function test_checkbox_renders_checked_when_true(): void
    {
        $html = view('components.form.checkbox', ['name' => 'is_visible', 'checked' => true])->render();

        $this->assertStringContainsString('checked', $html);
    }

    public function test_checkbox_renders_unchecked_when_false(): void
    {
        $html = view('components.form.checkbox', ['name' => 'is_visible', 'checked' => false])->render();

        $this->assertStringNotContainsString('checked', $html);
    }

    public function test_file_input_never_carries_a_value_attribute(): void
    {
        $html = view('components.form.file', ['name' => 'image', 'label' => 'Profile Image'])->render();

        $this->assertStringContainsString('type="file"', $html);
        $this->assertStringNotContainsString('value=', $html);
    }
}
