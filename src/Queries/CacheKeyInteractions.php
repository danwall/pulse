<?php

namespace Laravel\Pulse\Queries;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterval as Interval;
use Illuminate\Config\Repository;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Collection;

/**
 * @internal
 */
class CacheKeyInteractions
{
    use Concerns\InteractsWithConnection;

    /**
     * Create a new query instance.
     */
    public function __construct(
        protected DatabaseManager $db,
        protected Repository $config,
    ) {
        //
    }

    /**
     * Run the query.
     *
     * @return \Illuminate\Support\Collection<string, object>
     */
    public function __invoke(Interval $interval): Collection
    {
        $now = new CarbonImmutable();

        return $this->connection()->table('pulse_cache_interactions')
            ->selectRaw('MAX(`key`) AS `key`, COUNT(*) AS `count`, SUM(`hit`) AS `hits`')
            ->where('date', '>', $now->subSeconds((int) $interval->totalSeconds)->toDateTimeString())
            ->groupBy('key_hash')
            ->orderByDesc('count')
            ->limit(101)
            ->get();
    }
}