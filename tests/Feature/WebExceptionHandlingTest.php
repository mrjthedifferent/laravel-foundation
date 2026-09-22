<?php

namespace Mrj\Foundation\Tests\Feature;

use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Support\Facades\Route;
use Mrj\Foundation\Exceptions\Handler;
use Mrj\Foundation\Tests\TestCase;
use RuntimeException;

class WebExceptionHandlingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // The test suite's own TestCase doesn't apply Foundation::singletons()
        // (a project's bootstrap/app.php does that), so the custom Handler
        // isn't bound by default; bind it here to exercise its actual render().
        $this->app->singleton(ExceptionHandler::class, Handler::class);

        Route::middleware('web')->get('/__test-error', function (): void {
            throw new RuntimeException('boom');
        });
    }

    /**
     * Every admin controller used to wrap its Action calls in a try/catch
     * that flashed this same fallback message; the handler now does it in
     * one place for any web request's unhandled server error.
     */
    public function test_an_unhandled_exception_flashes_a_friendly_message_in_production(): void
    {
        app()['env'] = 'production';
        config(['app.debug' => false]);

        $response = $this->from('/previous')->get('/__test-error');

        $response->assertRedirect('/previous');
        $response->assertSessionHas('error', 'Something went wrong. Please try again.');
    }

    public function test_the_real_error_still_surfaces_outside_production(): void
    {
        config(['app.debug' => true]);

        $response = $this->get('/__test-error');

        $response->assertStatus(500);
        $response->assertSee('boom');
    }
}
