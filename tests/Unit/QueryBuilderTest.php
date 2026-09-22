<?php

namespace Mrj\Foundation\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Settings\Models\Setting;
use Mrj\Foundation\Support\QueryBuilder;
use Mrj\Foundation\Tests\TestCase;

class QueryBuilderTest extends TestCase
{
    use RefreshDatabase;

    public function test_where_like_matches_a_literal_percent_sign_not_a_wildcard(): void
    {
        Setting::create(['key' => 'a', 'group' => 'G', 'type' => 'text', 'value' => '100% done']);
        Setting::create(['key' => 'b', 'group' => 'G', 'type' => 'text', 'value' => 'unrelated']);

        $query = new class(Setting::query()) extends QueryBuilder
        {
            public function search(string $term): self
            {
                $this->whereLike(['value'], $term);

                return $this;
            }
        };

        $results = $query->search('100%')->get();

        $this->assertCount(1, $results);
        $this->assertSame('a', $results->first()->key);
    }

    public function test_paginate_clamps_to_the_configured_maximum(): void
    {
        config(['foundation.pagination.max' => 5]);

        for ($i = 0; $i < 10; $i++) {
            Setting::create(['key' => "key_{$i}", 'group' => 'G', 'type' => 'text', 'value' => 'v']);
        }

        $query = new class(Setting::query()) extends QueryBuilder {};

        $this->assertSame(5, $query->paginate(999)->perPage());
    }
}
