<?php

namespace Laravel\Pulse\Livewire;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Laravel\Pulse\Livewire\Concerns\HasPeriod;
use Laravel\Pulse\Livewire\Concerns\ShouldNotReportUsage;
use Livewire\Component;

class SlowJobs extends Component
{
    use HasPeriod, ShouldNotReportUsage;

    /**
     * Render the component.
     */
    public function render(callable $query): Renderable
    {
        [$slowJobs, $time, $runAt] = $this->slowJobs($query);

        $this->dispatch('slow-jobs:dataLoaded');

        return view('pulse::livewire.slow-jobs', [
            'time' => $time,
            'runAt' => $runAt,
            'slowJobs' => $slowJobs,
        ]);
    }

    /**
     * Render the placeholder.
     */
    public function placeholder(): Renderable
    {
        return view('pulse::components.placeholder', ['class' => 'col-span-3']);
    }

    /**
     * The slow jobs.
     */
    protected function slowJobs(callable $query): array
    {
        return Cache::remember("illuminate:pulse:slow-jobs:{$this->period}", $this->periodCacheDuration(), function () use ($query) {
            $now = new CarbonImmutable;

            $start = hrtime(true);

            $slowJobs = $query($this->periodAsInterval());

            // $slowJobs = DB::table('pulse_jobs')
            //     ->selectRaw('`job`, COUNT(*) as count, MAX(duration) AS slowest')
            //     ->where('date', '>=', $now->subHours($this->periodAsHours())->toDateTimeString())
            //     ->where('duration', '>=', config('pulse.slow_job_threshold'))
            //     ->groupBy('job')
            //     ->orderByDesc('slowest')
            //     ->get()
            //     ->all();

            $time = (int) ((hrtime(true) - $start) / 1000000);

            return [$slowJobs, $time, $now->toDateTimeString()];
        });
    }
}
