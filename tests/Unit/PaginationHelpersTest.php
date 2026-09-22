<?php

declare(strict_types=1);

namespace Mrj\Foundation\Tests\Unit;

use Mrj\Foundation\Tests\TestCase;

class PaginationHelpersTest extends TestCase
{
    public function test_per_page_reads_the_configured_default_and_options(): void
    {
        $this->assertSame(10, perPage());

        config(['foundation.pagination' => ['default' => 20, 'max' => 100, 'options' => [20, 40]]]);

        $this->assertSame(20, perPage());
    }

    public function test_per_page_falls_back_to_default_for_an_option_outside_the_configured_list(): void
    {
        config(['foundation.pagination' => ['default' => 20, 'max' => 100, 'options' => [20, 40]]]);

        request()->merge(['per_page' => 999]);

        $this->assertSame(20, perPage());
    }

    public function test_capped_per_page_reads_the_configured_maximum(): void
    {
        config(['foundation.pagination.max' => 30]);

        $this->assertSame(30, cappedPerPage(999));
    }

    public function test_get_par_page_paginate_reflects_configured_options(): void
    {
        config(['foundation.pagination.options' => [5, 15]]);

        $this->assertSame(['5' => '5', '15' => '15'], getParPagePaginate());
    }
}
