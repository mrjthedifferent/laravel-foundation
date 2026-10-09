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

    public function test_where_like_is_case_insensitive(): void
    {
        Setting::create(['key' => 'a', 'group' => 'G', 'type' => 'text', 'value' => 'Broiler Feed']);

        $query = new class(Setting::query()) extends QueryBuilder
        {
            public function search(string $term): self
            {
                $this->whereLike(['value'], $term);

                return $this;
            }
        };

        $this->assertCount(1, $query->search('broiler')->get());
    }

    public function test_apply_like_searches_a_plain_builder_with_escaping_across_columns(): void
    {
        Setting::create(['key' => 'a', 'group' => 'G', 'type' => 'text', 'value' => '100% done']);
        Setting::create(['key' => 'b_special', 'group' => 'G', 'type' => 'text', 'value' => 'other']);
        Setting::create(['key' => 'c', 'group' => 'G', 'type' => 'text', 'value' => 'nothing']);

        $byValue = Setting::query();
        QueryBuilder::applyLike($byValue, ['key', 'value'], '100%');
        $this->assertSame(['a'], $byValue->pluck('key')->all());

        // "_" is matched literally, not as a single-character wildcard.
        $byKey = Setting::query();
        QueryBuilder::applyLike($byKey, ['key', 'value'], 'b_s');
        $this->assertSame(['b_special'], $byKey->pluck('key')->all());
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
