<?php

namespace App\Console\Commands;

use App\Models\Genre;
use App\Models\Movie;
use Dom\HTMLDocument;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

use function Laravel\Prompts\select;

class MovieDownloaderCommand extends Command
{
    private const MODE_SCAN_ALL = 'scan';

    private const MODE_DOWNLOAD_MOVIES = 'download';

    private const URL = 'https://kinovasek.net';

    private const CHUNK_SIZE = 100;

    private const DOWNLOAD_DIR = 'app/movies';

    private const STALE_PROCESSING_HOURS = 6;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:movie-downloader-command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Download movies of the selected genre';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $mode = $this->resolveMode();

        match ($mode) {
            self::MODE_DOWNLOAD_MOVIES => $this->downloadMovies(),
            self::MODE_SCAN_ALL => $this->fillGenre(),
            default => $this->fillMoviesByLink($mode),
        };
    }

    private function fillMoviesByLink(string $mode): void
    {
        $url = self::URL.$mode;

        $firstPage = $this->loadDocument($url);
        $lastPage = $this->getLastPage($firstPage);

        $bar = $this->output->createProgressBar($lastPage);
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %elapsed:6s% | %message%');
        $bar->setMessage('saved: 0');
        $bar->start();

        $buffer = [];
        $saved = 0;

        for ($page = 1; $page <= $lastPage; $page++) {
            $document = $page === 1
                ? $firstPage
                : $this->loadDocument(rtrim($url, '/')."/page/{$page}/");

            foreach ($this->parseMovies($document) as $movie) {
                $buffer[$movie['hash']] ??= $movie;
            }

            if (count($buffer) >= self::CHUNK_SIZE) {
                $saved += $this->saveMovies($buffer);
                $buffer = [];
            }

            $bar->setMessage("saved: {$saved}");
            $bar->advance();
        }

        $saved += $this->saveMovies($buffer);

        $bar->setMessage("saved: {$saved}");
        $bar->finish();
        $this->newLine(2);

        $this->info("Pages: {$lastPage}, new movies saved: {$saved}");
    }

    private function downloadMovies(): void
    {
        $total = $this->pendingMovies()->count();

        if ($total === 0) {
            $this->info('No movies to download.');

            return;
        }

        $current = 0;
        $downloaded = 0;
        $failed = 0;

        while (($movies = $this->claimMovies())->isNotEmpty()) {
            foreach ($movies as $movie) {
                $current++;
                $this->line("[{$current}/{$total}] {$movie->name}");

                // Blocks until the file is fully downloaded, so movies go strictly one by one
                try {
                    $this->downloadMovie($movie);
                    $movie->update(['is_downloaded' => true]);
                    $downloaded++;
                    $this->info('  downloaded');
                } catch (Throwable $e) {
                    $failed++;
                    $this->error("  failed: {$e->getMessage()}");
                }
            }
        }

        $this->newLine();
        $this->info("Downloaded: {$downloaded}, failed: {$failed}");
    }

    /**
     * Movies that are not downloaded and not taken by another run
     * (a run that crashed releases its movies after STALE_PROCESSING_HOURS).
     *
     * @return Builder<Movie>
     */
    private function pendingMovies(): Builder
    {
        return Movie::query()
            ->where('is_downloaded', false)
            ->where(static function (Builder $query) {
                $query->whereNull('processing_started_at')
                    ->orWhere('processing_started_at', '<', now()->subHours(self::STALE_PROCESSING_HOURS));
            });
    }

    /**
     * Takes the next chunk of movies and marks them with processing_started_at.
     *
     * @return Collection<int, Movie>
     */
    private function claimMovies(): Collection
    {
        return DB::transaction(function () {
            $movies = $this->pendingMovies()
                ->orderBy('id')
                ->limit(self::CHUNK_SIZE)
                ->lockForUpdate()
                ->get();

            if ($movies->isNotEmpty()) {
                Movie::query()
                    ->whereKey($movies->modelKeys())
                    ->update(['processing_started_at' => now()]);
            }

            return $movies;
        });
    }

    private function downloadMovie(Movie $movie): void
    {
        $url = $this->findDownloadUrl($movie->link);

        $dir = storage_path(self::DOWNLOAD_DIR);
        File::ensureDirectoryExists($dir);

        $path = "{$dir}/{$movie->name}.mp4";

        $bar = $this->output->createProgressBar();
        $bar->setFormat(' %current% MB [%bar%] %elapsed:6s%');
        $bar->start();

        $mb = 1024 * 1024;

        try {
            // go.php redirects to the file only when opened from the movie page,
            // and the file host answers 404 without the site referer. Guzzle drops
            // the Referer header on redirects unless 'referer' => true is set.
            $response = Http::timeout(0)
                ->withHeaders(['Referer' => $movie->link])
                ->sink($path)
                ->withOptions([
                    'allow_redirects' => ['max' => 5, 'referer' => true],
                    'progress' => static function (int $totalBytes, int $doneBytes) use ($bar, $mb) {
                        // The size becomes known once the file response headers arrive
                        if ($totalBytes > 0 && $bar->getMaxSteps() === 0) {
                            $bar->setMaxSteps(intdiv($totalBytes, $mb));
                            $bar->setFormat(' %current%/%max% MB [%bar%] %percent:3s%% %elapsed:6s%');
                        }

                        $bar->setProgress(intdiv($doneBytes, $mb));
                    },
                ])
                ->get($url)
                ->throw();

            if (! str_starts_with($response->header('Content-Type'), 'video/')) {
                throw new RuntimeException("Not a video file ({$response->header('Content-Type')}): {$response->effectiveUri()}");
            }

            $bar->finish();
        } catch (Throwable $e) {
            // Don't leave an empty or partly downloaded file behind
            File::delete($path);

            throw $e;
        } finally {
            $this->newLine();
        }
    }

    /**
     * Opens the movie page and returns the link of the last (best quality) file.
     */
    private function findDownloadUrl(string $link): string
    {
        $links = $this->loadDocument($link)->querySelectorAll('.v_links a');

        if ($links->length === 0) {
            throw new RuntimeException('Download link not found');
        }

        $href = trim($links->item($links->length - 1)->getAttribute('href') ?? '');

        if ($href === '') {
            throw new RuntimeException('Download link is empty');
        }

        return $href;
    }

    private function loadDocument(string $url): HTMLDocument
    {
        $html = Http::timeout(30)
            ->retry(3, 1000)
            ->get($url)
            ->throw()
            ->body();

        return HTMLDocument::createFromString($html, LIBXML_NOERROR);
    }

    private function getLastPage(HTMLDocument $document): int
    {
        $links = $document->querySelectorAll('.pagi-nav .navigation a');

        if ($links->length === 0) {
            return 1;
        }

        return max(1, (int) trim($links->item($links->length - 1)->textContent));
    }

    /**
     * @return list<array{name: string, link: string, hash: string}>
     */
    private function parseMovies(HTMLDocument $document): array
    {
        $movies = [];
        foreach ($document->querySelectorAll('#dle-content a.shfilm') as $anchor) {
            $name = trim($anchor->querySelector('.shfiltitle span')->textContent ?? '');
            $link = trim($anchor->getAttribute('href') ?? '');

            if ($name && $link) {
                $movies[] = [
                    'name' => $name,
                    'link' => $link,
                    'hash' => md5($name),
                ];
            }
        }

        return $movies;
    }

    /**
     * Inserts only movies whose hash is not in the database yet.
     *
     * @param  array<string, array{name: string, link: string, hash: string}>  $movies  keyed by hash
     */
    private function saveMovies(array $movies): int
    {
        if (! $movies) {
            return 0;
        }

        $existing = Movie::query()
            ->whereIn('hash', array_keys($movies))
            ->pluck('hash')
            ->all();

        $new = array_diff_key($movies, array_flip($existing));

        if (! $new) {
            return 0;
        }

        $now = now();
        $rows = array_map(
            static fn (array $movie) => $movie + ['created_at' => $now, 'updated_at' => $now],
            array_values($new),
        );

        Movie::query()->insert($rows);

        return count($rows);
    }

    private function fillGenre(): void
    {
        $url = self::URL.'/zhanry.html';

        $html = Http::timeout(30)
            ->get($url)
            ->throw()
            ->body();

        $document = HTMLDocument::createFromString($html, LIBXML_NOERROR);

        $genres = [];
        foreach ($document->querySelectorAll('#dle-content .zzzpg ul li a') as $anchor) {
            $anchor->querySelector('span')?->remove();

            $name = trim($anchor->textContent);
            $link = trim($anchor->getAttribute('href') ?? '');

            if ($name && $link) {
                $genres[$link] = [
                    'name' => $name,
                    'link' => $link,
                ];
            }
        }

        if ($genres) {
            Genre::query()
                ->upsert(array_values($genres), ['link'], ['name']);

            $this->info('Genres saved: '.count($genres));
        } else {
            $this->error('Genres could not be saved.');
        }
    }

    private function resolveMode(): string
    {
        $modeList = Genre::query()
            ->orderBy('name')
            ->pluck('name', 'link')
            ->all();

        $modeList[self::MODE_SCAN_ALL] = 'All Scan...';
        $modeList[self::MODE_DOWNLOAD_MOVIES] = 'Download movies...';

        return select(
            label: 'What to run?',
            options: $modeList,
        );
    }
}
