<?php

namespace Mrj\Foundation\Tests\Unit;

use Mrj\Foundation\Tests\TestCase;
use Mrj\Foundation\View\Components\Sparkline;

class SparklineTest extends TestCase
{
    public function test_a_series_with_a_trend_is_drawn_within_the_viewbox(): void
    {
        $spark = new Sparkline([1, 4, 2, 8]);

        $this->assertTrue($spark->hasData);
        $this->assertStringStartsWith('M', $spark->linePath);
        $this->assertSame(98.0, $spark->lastX);
        // The peak sits at the top padding, the last value (the peak) too.
        $this->assertSame(2.0, $spark->lastY);
    }

    public function test_nothing_is_drawn_for_a_single_point_or_all_zeros(): void
    {
        $this->assertFalse((new Sparkline([5]))->hasData);
        $this->assertFalse((new Sparkline([0, 0, 0]))->hasData);
        $this->assertFalse((new Sparkline([]))->hasData);
    }

    public function test_it_renders_an_svg_hidden_from_screen_readers(): void
    {
        $html = view('components.sparkline', ['hasData' => true, 'gradientId' => 'g', 'areaPath' => 'M0 0', 'linePath' => 'M0 0'])->render();

        $this->assertStringContainsString('aria-hidden="true"', $html);
    }
}
