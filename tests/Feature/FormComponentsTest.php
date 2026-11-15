<?php

namespace Mrj\Foundation\Tests\Feature;

use Illuminate\Support\Facades\Blade;
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
        $this->assertStringContainsString('is-invalid', $html);
    }

    public function test_input_has_no_is_invalid_class_without_an_error(): void
    {
        $html = view('components.form.input', ['name' => 'email'])->render();

        $this->assertStringNotContainsString('is-invalid', $html);
    }

    /**
     * A repeated single-value row (option_keys[]) flashes as an array of
     * every row submitted; this component renders one row with no index to
     * pick from, so it must fall back to the given value instead of printing
     * the literal string "Array".
     */
    public function test_input_falls_back_to_the_given_value_when_old_input_is_an_array(): void
    {
        Session::flash('_old_input', ['option_keys' => ['active', 'inactive']]);

        $html = view('components.form.input', ['name' => 'option_keys[]', 'value' => 'fallback'])->render();

        $this->assertStringContainsString('value="fallback"', $html);
        $this->assertStringNotContainsString('Array', $html);
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

    /**
     * Without an empty option a browser selects the first one, so an unfiltered list showed
     * a filter chip (and submitted a filter) nobody chose.
     */
    public function test_single_select_with_a_placeholder_starts_on_an_empty_option(): void
    {
        $html = Blade::render('<x-form.select name="type" :options="$options" data-placeholder="All types" />', [
            'options' => ['import' => 'Import', 'export' => 'Export'],
        ]);

        $this->assertMatchesRegularExpression('/<select[^>]*>\s*<option value=""><\/option>\s*<option value="import"\s*>/', $html);
        $this->assertDoesNotMatchRegularExpression('/\sselected\b/', $html);
    }

    public function test_placeholder_select_with_a_collection_of_options_gets_an_empty_option(): void
    {
        $html = Blade::render('<x-form.select name="user_id" :options="$options" data-placeholder="Pick one" required />', [
            'options' => collect([5 => 'Jane', 6 => 'John']),
        ]);

        $this->assertMatchesRegularExpression('/<select[^>]*required[^>]*>\s*<option value=""><\/option>\s*<option value="5"/', $html);
    }

    public function test_placeholder_select_keeps_the_chosen_value_selected(): void
    {
        $html = Blade::render('<x-form.select name="type" :options="$options" selected="export" data-placeholder="All types" />', [
            'options' => ['import' => 'Import', 'export' => 'Export'],
        ]);

        $this->assertStringContainsString('<option value=""></option>', $html);
        $this->assertMatchesRegularExpression('/<option value="export"\s+selected\s*>/', $html);
    }

    public function test_no_extra_empty_option_when_the_options_have_one_or_there_is_no_placeholder(): void
    {
        $withBlank = Blade::render('<x-form.select name="status" :options="$options" data-placeholder="All" />', [
            'options' => ['' => 'All', '1' => 'Active'],
        ]);
        $noPlaceholder = Blade::render('<x-form.select name="status" :options="$options" />', [
            'options' => ['1' => 'Active', '0' => 'Inactive'],
        ]);
        $multiple = Blade::render('<x-form.select name="roles" :options="$options" multiple data-placeholder="Roles" />', [
            'options' => ['1' => 'Admin'],
        ]);

        $this->assertSame(1, substr_count($withBlank, '<option value=""'));
        $this->assertStringNotContainsString('<option value=""', $noPlaceholder);
        $this->assertStringNotContainsString('<option value=""', $multiple);
    }

    public function test_single_select_takes_the_first_item_of_array_old_input(): void
    {
        Session::flash('_old_input', ['type' => ['export', 'import']]);

        $html = view('components.form.select', [
            'name' => 'type[]',
            'options' => ['import' => 'Import', 'export' => 'Export'],
        ])->render();

        $this->assertMatchesRegularExpression('/<option value="export"\s+selected\s*>/', $html);
        $this->assertDoesNotMatchRegularExpression('/<option value="import"\s+selected\s*>/', $html);
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
