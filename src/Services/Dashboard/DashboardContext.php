<?php

namespace Mrj\Foundation\Services\Dashboard;

use Illuminate\Http\Request;

/**
 * What the viewer asked the dashboard for: the date range and whether to compare it
 * with the period before. Handed to every widget so they all answer the same question.
 *
 * @api
 */
final readonly class DashboardContext
{
    /** The windows the range picker offers. A fixed list: any other number means more buckets and points. */
    public const array WINDOWS = [7, 14, 30, 90];

    public const int DEFAULT_DAYS = 14;

    public function __construct(
        public int $days = self::DEFAULT_DAYS,
        public bool $compare = false,
    ) {}

    /**
     * ?range= is the parameter; ?days= is what the page used before ranges existed.
     */
    public static function fromRequest(Request $request): self
    {
        $asked = $request->integer('range') ?: $request->integer('days');

        return new self(
            in_array($asked, self::WINDOWS, true) ? $asked : self::DEFAULT_DAYS,
            $request->boolean('compare'),
        );
    }

    /**
     * Query-string parameters that keep this context, with some overridden (null drops one).
     *
     * @param  array<string, int|null>  $override
     * @return array<string, int>
     */
    public function query(array $override = []): array
    {
        $params = array_merge(['range' => $this->days, 'compare' => $this->compare ? 1 : null], $override);

        return array_filter($params, fn (?int $value): bool => $value !== null);
    }
}
