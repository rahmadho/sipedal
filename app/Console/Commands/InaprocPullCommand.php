<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\PullStatus;
use App\Models\Endpoint;
use App\Models\PullLog;
use App\Services\Inaproc\EndpointRegistry;
use App\Services\Pull\KlpdCredentials;
use App\Services\Pull\PullService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;

/**
 * inaproc:pull — tarik data (F12).
 */
class InaprocPullCommand extends Command
{
    protected $signature = 'inaproc:pull
        {--klpd= : Kode KLPD tunggal}
        {--tahun= : Tahun anggaran}
        {--all : Tarik seluruh KLPD terdaftar secara paralel}
        {--endpoint=* : Batasi ke endpoint tertentu}
        {--grup= : Batasi ke satu grup (rup, tender, ekatalog, bela, master)}
        {--dry-run : Tampilkan rencana tanpa memanggil API}
        {--refresh-derived : Abaikan cache endpoint turunan}
        {--sync : Jalankan langsung tanpa queue}';

    protected $description = 'Menarik data INAPROC per KLPD + tahun anggaran';

    public function handle(
        EndpointRegistry $registry,
        KlpdCredentials $credentials,
        PullService $pullService,
    ): int {
        $tahun = $this->resolveTahun();

        if ($tahun === null) {
            return self::FAILURE;
        }

        $klpds = $this->resolveKlpds($credentials);

        if ($klpds === null) {
            return self::FAILURE;
        }

        $endpoints = $this->resolveEndpoints($registry);

        if ($endpoints->isEmpty()) {
            $this->error('Tidak ada endpoint yang cocok dengan filter.');

            return self::FAILURE;
        }

        $this->renderPlan($klpds, $endpoints, $tahun);

        if ($this->option('dry-run')) {
            $this->newLine();
            $this->info('Mode dry-run: tidak ada request ke API.');

            return self::SUCCESS;
        }

        if (! $this->confirmRun()) {
            $this->warn('Dibatalkan.');

            return self::SUCCESS;
        }

        return $this->option('sync')
            ? $this->runSync($klpds, $endpoints, $tahun, $credentials, $pullService)
            : $this->runQueued($klpds, $tahun);
    }

    private function resolveTahun(): ?int
    {
        $tahun = $this->option('tahun');

        if ($tahun === null || $tahun === '') {
            $tahun = $this->ask('Tahun anggaran', (string) now()->year);
        }

        if (! is_numeric($tahun) || (int) $tahun < 2000 || (int) $tahun > 2100) {
            $this->error('Tahun anggaran tidak valid: '.$tahun);

            return null;
        }

        return (int) $tahun;
    }

    /**
     * @return array<int, string>|null
     */
    private function resolveKlpds(KlpdCredentials $credentials): ?array
    {
        if ($this->option('all')) {
            $all = array_keys($credentials->all());

            if ($all === []) {
                $this->error('Tidak ada KLPD terdaftar di berkas kredensial.');

                return null;
            }

            return $all;
        }

        $klpd = $this->option('klpd');

        if ($klpd === null || $klpd === '') {
            $registered = array_keys($credentials->all());

            if ($registered === []) {
                $this->error('Tidak ada KLPD terdaftar. Isi berkas kredensial lebih dulu:');
                $this->line('  '.config('inaproc.klpd_file'));

                return null;
            }

            $klpd = $this->choice('Pilih KLPD', $registered);
        }

        return [(string) $klpd];
    }

    /**
     * @return \Illuminate\Support\Collection<int, Endpoint>
     */
    private function resolveEndpoints(EndpointRegistry $registry)
    {
        $names = (array) $this->option('endpoint');
        $group = $this->option('grup');

        $endpoints = $registry->all(true);

        if ($names !== []) {
            $endpoints = $endpoints->filter(fn (Endpoint $e) => in_array($e->name, $names, true))->values();
        }

        if ($group !== null && $group !== '') {
            $endpoints = $endpoints->filter(fn (Endpoint $e) => $e->grup === $group)->values();
        }

        return $endpoints;
    }

    /**
     * @param  array<int, string>  $klpds
     * @param  \Illuminate\Support\Collection<int, Endpoint>  $endpoints
     */
    private function renderPlan(array $klpds, $endpoints, int $tahun): void
    {
        $this->info('Rencana tarik');
        $this->line('  Tahun       : '.$tahun);
        $this->line('  KLPD        : '.implode(', ', $klpds));
        $this->line('  Endpoint    : '.$endpoints->count().' aktif');
        $this->line('  Total tugas : '.$endpoints->count() * count($klpds));

        $byGroup = $endpoints->groupBy('grup')->map->count();

        foreach ($byGroup as $group => $count) {
            $this->line(sprintf('    - %-10s %d', $group, $count));
        }
    }

    private function confirmRun(): bool
    {
        if ($this->option('no-interaction')) {
            return true;
        }

        return $this->confirm('Jalankan tarik sekarang?', true);
    }

    /**
     * @param  array<int, string>  $klpds
     */
    private function runQueued(array $klpds, int $tahun): int
    {
        $names = (array) $this->option('endpoint');
        $refresh = (bool) $this->option('refresh-derived');

        $jobs = array_map(
            fn (string $klpd) => new \App\Jobs\PullKlpdJob(
                kodeKlpd: $klpd,
                tahun: $tahun,
                endpointNames: $names === [] ? null : $names,
                refreshDerived: $refresh,
            ),
            $klpds,
        );

        $this->info(sprintf('Mendispatch %d job ke queue (%s).', count($jobs), config('queue.default')));
        $this->line('Jalankan worker: php artisan queue:work --queue=default');

        Bus::batch($jobs)->name('inaproc-pull-'.$tahun)->dispatch();

        return self::SUCCESS;
    }

    /**
     * @param  array<int, string>  $klpds
     * @param  \Illuminate\Support\Collection<int, Endpoint>  $endpoints
     */
    private function runSync(
        array $klpds,
        $endpoints,
        int $tahun,
        KlpdCredentials $credentials,
        PullService $pullService,
    ): int {
        $totals = ['OK' => 0, 'KOSONG' => 0, 'GAGAL' => 0, 'FORBIDDEN' => 0, 'NONAKTIF' => 0];

        foreach ($klpds as $klpd) {
            $entry = $credentials->for($klpd);
            $token = $entry['token'] ?? '';

            if ($token === '') {
                $this->warn(sprintf('[%s] dilewati — token belum tersedia', $klpd));

                continue;
            }

            $credentialKey = $credentials->credentialKey($klpd, $token);

            $this->newLine();
            $this->info(sprintf('[%s] %s', $klpd, $entry['label'] ?? ''));

            $bar = $this->output->createProgressBar($endpoints->count());
            $bar->start();

            $perKlpd = ['OK' => 0, 'KOSONG' => 0, 'GAGAL' => 0, 'FORBIDDEN' => 0, 'NONAKTIF' => 0];

            foreach ($endpoints as $endpoint) {
                $log = $pullService->pullEndpoint($endpoint, $klpd, $tahun, $token, $credentialKey);

                $perKlpd[$log->status] = ($perKlpd[$log->status] ?? 0) + 1;
                $totals[$log->status] = ($totals[$log->status] ?? 0) + 1;

                $bar->advance();
            }

            $bar->finish();
            $this->newLine(2);
            $this->line(sprintf(
                '  OK=%d KOSONG=%d GAGAL=%d FORBIDDEN=%d',
                $perKlpd['OK'],
                $perKlpd['KOSONG'],
                $perKlpd['GAGAL'],
                $perKlpd['FORBIDDEN'],
            ));
        }

        $this->newLine();
        $this->info('Ringkasan keseluruhan');
        $this->table(
            ['Status', 'Jumlah'],
            array_map(static fn (string $k, int $v): array => [$k, $v], array_keys($totals), $totals),
        );

        // Exit code ≠ 0 bila terdapat kegagalan (untuk monitoring).
        return ($totals['GAGAL'] > 0 || $totals['FORBIDDEN'] > 0) ? self::FAILURE : self::SUCCESS;
    }
}
