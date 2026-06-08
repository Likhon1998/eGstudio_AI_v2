<?php

namespace App\Services;

use App\Models\CgiGeneration;
use App\Models\Occasion;
use App\Models\OccasionSocialPost;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardStatsService
{
    private const LINE_DAYS = 14;

    private const CACHE_SECONDS = 60;

    public static function forgetForUser(int $userId): void
    {
        Cache::forget("dashboard.stats.{$userId}");
    }

    public static function forUser(int $userId): array
    {
        return Cache::remember(
            "dashboard.stats.{$userId}",
            self::CACHE_SECONDS,
            fn () => self::computeForUser($userId),
        );
    }

    /**
     * SQL: column holds a non-empty media path or URL.
     */
    private static function hasMediaSql(string $column): string
    {
        return "(NULLIF(TRIM({$column}), '') IS NOT NULL)";
    }

    private static function sumWhenHasMedia(string $column, string $alias): string
    {
        $check = self::hasMediaSql($column);

        return "SUM(CASE WHEN {$check} THEN 1 ELSE 0 END) as {$alias}";
    }

    private static function int(mixed $value): int
    {
        return (int) ($value ?? 0);
    }

    private static function cgiAssetSelect(): string
    {
        return implode(', ', [
            self::sumWhenHasMedia('image_url', 'img_raw'),
            self::sumWhenHasMedia('branded_image_url', 'img_branded'),
            self::sumWhenHasMedia('merged_image_url', 'img_templated'),
            self::sumWhenHasMedia('video_url', 'vid_raw'),
            self::sumWhenHasMedia('branded_video_url', 'vid_branded'),
            self::sumWhenHasMedia('merged_video_url', 'vid_templated'),
        ]);
    }

    private static function occasionAssetSelect(): string
    {
        return implode(', ', [
            self::sumWhenHasMedia('image_url', 'img_raw'),
            self::sumWhenHasMedia('branded_image_url', 'img_branded'),
            self::sumWhenHasMedia('merged_image_url', 'img_templated'),
        ]);
    }

    private static function cgiAssetSumSql(): string
    {
        return implode(' + ', [
            'CASE WHEN '.self::hasMediaSql('image_url').' THEN 1 ELSE 0 END',
            'CASE WHEN '.self::hasMediaSql('branded_image_url').' THEN 1 ELSE 0 END',
            'CASE WHEN '.self::hasMediaSql('merged_image_url').' THEN 1 ELSE 0 END',
        ]);
    }

    private static function cgiVideoSumSql(): string
    {
        return implode(' + ', [
            'CASE WHEN '.self::hasMediaSql('video_url').' THEN 1 ELSE 0 END',
            'CASE WHEN '.self::hasMediaSql('branded_video_url').' THEN 1 ELSE 0 END',
            'CASE WHEN '.self::hasMediaSql('merged_video_url').' THEN 1 ELSE 0 END',
        ]);
    }

    private static function occasionAssetSumSql(): string
    {
        return implode(' + ', [
            'CASE WHEN '.self::hasMediaSql('image_url').' THEN 1 ELSE 0 END',
            'CASE WHEN '.self::hasMediaSql('branded_image_url').' THEN 1 ELSE 0 END',
            'CASE WHEN '.self::hasMediaSql('merged_image_url').' THEN 1 ELSE 0 END',
        ]);
    }

    private static function todayWhenHasMedia(string $column, string $alias): string
    {
        $check = self::hasMediaSql($column);

        return "SUM(CASE WHEN {$check} AND updated_at BETWEEN ? AND ? THEN 1 ELSE 0 END) as {$alias}";
    }

    /**
     * @param  array<string, string>  $columns  column => alias
     */
    private static function todaySelectSql(array $columns): string
    {
        $parts = [];

        foreach ($columns as $column => $alias) {
            $parts[] = self::todayWhenHasMedia($column, $alias);
        }

        return implode(', ', $parts);
    }

    private static function todayBindings(Carbon $start, Carbon $end, int $fieldCount): array
    {
        $bindings = [];
        for ($i = 0; $i < $fieldCount; $i++) {
            $bindings[] = $start;
            $bindings[] = $end;
        }

        return $bindings;
    }

    private static function computeForUser(int $userId): array
    {
        $start = Carbon::now('Asia/Dhaka')->startOfDay()->utc();
        $end   = Carbon::now('Asia/Dhaka')->endOfDay()->utc();

        $cgi = CgiGeneration::query()
            ->where('user_id', $userId)
            ->selectRaw(self::cgiAssetSelect())
            ->first();

        $cgiTodayColumns = [
            'image_url' => 'img_raw',
            'branded_image_url' => 'img_branded',
            'merged_image_url' => 'img_templated',
            'video_url' => 'vid_raw',
            'branded_video_url' => 'vid_branded',
            'merged_video_url' => 'vid_templated',
        ];
        $cgiToday = CgiGeneration::query()
            ->where('user_id', $userId)
            ->selectRaw(self::todaySelectSql($cgiTodayColumns), self::todayBindings($start, $end, count($cgiTodayColumns)))
            ->first();

        $occasion = Occasion::query()
            ->where('user_id', $userId)
            ->selectRaw(self::occasionAssetSelect())
            ->first();

        $occTodayColumns = [
            'image_url' => 'img_raw',
            'branded_image_url' => 'img_branded',
            'merged_image_url' => 'img_templated',
        ];
        $occToday = Occasion::query()
            ->where('user_id', $userId)
            ->selectRaw(self::todaySelectSql($occTodayColumns), self::todayBindings($start, $end, count($occTodayColumns)))
            ->first();

        $postedTotal = OccasionSocialPost::where('user_id', $userId)->count();
        $postedToday = OccasionSocialPost::where('user_id', $userId)
            ->whereBetween('created_at', [$start, $end])
            ->count();

        $images = [
            'raw'       => self::int($cgi->img_raw ?? 0),
            'branded'   => self::int($cgi->img_branded ?? 0),
            'templated' => self::int($cgi->img_templated ?? 0),
        ];
        $videos = [
            'raw'       => self::int($cgi->vid_raw ?? 0),
            'branded'   => self::int($cgi->vid_branded ?? 0),
            'templated' => self::int($cgi->vid_templated ?? 0),
        ];
        $occImages = [
            'raw'       => self::int($occasion->img_raw ?? 0),
            'branded'   => self::int($occasion->img_branded ?? 0),
            'templated' => self::int($occasion->img_templated ?? 0),
        ];
        $imagesToday = [
            'raw'       => self::int($cgiToday->img_raw ?? 0),
            'branded'   => self::int($cgiToday->img_branded ?? 0),
            'templated' => self::int($cgiToday->img_templated ?? 0),
        ];
        $videosToday = [
            'raw'       => self::int($cgiToday->vid_raw ?? 0),
            'branded'   => self::int($cgiToday->vid_branded ?? 0),
            'templated' => self::int($cgiToday->vid_templated ?? 0),
        ];
        $occImagesToday = [
            'raw'       => self::int($occToday->img_raw ?? 0),
            'branded'   => self::int($occToday->img_branded ?? 0),
            'templated' => self::int($occToday->img_templated ?? 0),
        ];
        $cgiImagesToday = array_sum($imagesToday);
        $cgiVideosToday = array_sum($videosToday);
        $occImagesTodaySum = array_sum($occImagesToday);
        $cgiTodayTotal  = $cgiImagesToday + $cgiVideosToday;
        $occTodayTotal  = $occImagesTodaySum + $postedToday;
        $occAssetTotal  = array_sum($occImages);

        $lifetimeTotal = array_sum($images) + array_sum($videos) + $occAssetTotal + $postedTotal;

        return [
            'cgi' => [
                'images' => $images,
                'videos' => $videos,
            ],
            'occasion' => [
                'images'  => $occImages,
                'branded' => $occImages['branded'],
                'posted'  => $postedTotal,
            ],
            'today' => [
                'total'                  => $cgiTodayTotal + $occTodayTotal,
                'cgi'                    => $cgiTodayTotal,
                'cgi_images'             => $cgiImagesToday,
                'cgi_videos'             => $cgiVideosToday,
                'occasion'               => $occTodayTotal,
                'cgi_image_raw'          => $imagesToday['raw'],
                'cgi_image_branded'      => $imagesToday['branded'],
                'cgi_image_templated'    => $imagesToday['templated'],
                'cgi_video_raw'          => $videosToday['raw'],
                'cgi_video_branded'      => $videosToday['branded'],
                'cgi_video_templated'    => $videosToday['templated'],
                'occasion_image_raw'     => $occImagesToday['raw'],
                'occasion_image_branded' => $occImagesToday['branded'],
                'occasion_image_templated' => $occImagesToday['templated'],
                'occasion_branded'       => $occImagesToday['branded'],
                'posted'                 => $postedToday,
            ],
            'lifetime_total' => $lifetimeTotal,
            'charts' => self::buildCharts(
                $userId,
                $images,
                $videos,
                $occImages,
                $postedTotal,
            ),
        ];
    }

    private static function buildCharts(
        int $userId,
        array $images,
        array $videos,
        array $occImages,
        int $postedTotal,
    ): array {
        return [
            'line' => self::buildLineChart($userId),
            'pie'  => self::buildPieChart($images, $videos, $occImages, $postedTotal),
        ];
    }

    /**
     * SQL expression for grouping rows by calendar day in Asia/Dhaka (driver-aware).
     */
    private static function sqlDayInTimezone(string $column = 'updated_at'): string
    {
        $tz = 'Asia/Dhaka';

        return match (DB::connection()->getDriverName()) {
            'pgsql' => "(timezone('{$tz}', {$column} AT TIME ZONE 'UTC'))::date",
            'sqlite' => "date({$column}, '+6 hours')",
            default => "DATE(CONVERT_TZ({$column}, '+00:00', '+06:00'))",
        };
    }

    private static function buildLineChart(int $userId): array
    {
        $tz = 'Asia/Dhaka';
        $rangeStart = Carbon::now($tz)->subDays(self::LINE_DAYS - 1)->startOfDay()->utc();
        $rangeEnd   = Carbon::now($tz)->endOfDay()->utc();

        $dayExpr = self::sqlDayInTimezone('updated_at');
        $hasAnyCgiMedia = '('.self::hasMediaSql('image_url')
            .' OR '.self::hasMediaSql('branded_image_url')
            .' OR '.self::hasMediaSql('merged_image_url')
            .' OR '.self::hasMediaSql('video_url')
            .' OR '.self::hasMediaSql('branded_video_url')
            .' OR '.self::hasMediaSql('merged_video_url').')';

        $hasAnyOccMedia = '('.self::hasMediaSql('image_url')
            .' OR '.self::hasMediaSql('branded_image_url')
            .' OR '.self::hasMediaSql('merged_image_url').')';

        $cgiByDay = self::groupCountsByDay(
            CgiGeneration::query()
                ->where('user_id', $userId)
                ->whereRaw($hasAnyCgiMedia)
                ->whereBetween('updated_at', [$rangeStart, $rangeEnd])
                ->selectRaw("{$dayExpr} as day")
                ->selectRaw('SUM('.self::cgiAssetSumSql().') as img_count')
                ->selectRaw('SUM('.self::cgiVideoSumSql().') as vid_count')
                ->groupByRaw($dayExpr)
        );

        $occByDay = self::groupCountsByDay(
            Occasion::query()
                ->where('user_id', $userId)
                ->whereRaw($hasAnyOccMedia)
                ->whereBetween('updated_at', [$rangeStart, $rangeEnd])
                ->selectRaw("{$dayExpr} as day")
                ->selectRaw('SUM('.self::occasionAssetSumSql().') as occ_count')
                ->groupByRaw($dayExpr)
        );

        $postsByDay = self::groupCountsByDay(
            OccasionSocialPost::query()
                ->where('user_id', $userId)
                ->whereBetween('created_at', [$rangeStart, $rangeEnd])
                ->selectRaw(self::sqlDayInTimezone('created_at').' as day')
                ->selectRaw('COUNT(*) as post_count')
                ->groupByRaw(self::sqlDayInTimezone('created_at'))
        );

        $labels = [];
        $cgiImages = [];
        $cgiVideos = [];
        $occasion = [];
        $total = [];

        for ($i = self::LINE_DAYS - 1; $i >= 0; $i--) {
            $day = Carbon::now($tz)->subDays($i);
            $dayKey = $day->format('Y-m-d');

            $labels[] = $day->format('M j');

            $imgCount = (int) ($cgiByDay[$dayKey]->img_count ?? 0);
            $vidCount = (int) ($cgiByDay[$dayKey]->vid_count ?? 0);
            $occCount = (int) ($occByDay[$dayKey]->occ_count ?? 0);
            $postCount = (int) ($postsByDay[$dayKey]->post_count ?? 0);
            $occTotal = $occCount + $postCount;

            $cgiImages[] = $imgCount;
            $cgiVideos[] = $vidCount;
            $occasion[] = $occTotal;
            $total[] = $imgCount + $vidCount + $occTotal;
        }

        return [
            'labels'    => $labels,
            'cgiImages' => $cgiImages,
            'cgiVideos' => $cgiVideos,
            'occasion'  => $occasion,
            'total'     => $total,
        ];
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Support\Collection<string, object>
     */
    private static function groupCountsByDay($query)
    {
        return $query->get()->keyBy(fn ($row) => self::normalizeDayKey($row->day));
    }

    private static function normalizeDayKey(mixed $day): string
    {
        if ($day instanceof \DateTimeInterface) {
            return $day->format('Y-m-d');
        }

        return substr((string) $day, 0, 10);
    }

    private static function buildPieChart(
        array $images,
        array $videos,
        array $occImages,
        int $postedTotal,
    ): array {
        $segments = [
            ['label' => 'CGI Image · Raw',         'value' => $images['raw'],            'color' => '#38bdf8'],
            ['label' => 'CGI Image · Branded',     'value' => $images['branded'],        'color' => '#34d399'],
            ['label' => 'CGI Image · Templated',   'value' => $images['templated'],      'color' => '#fb923c'],
            ['label' => 'CGI Video · Raw',         'value' => $videos['raw'],            'color' => '#f472b6'],
            ['label' => 'CGI Video · Branded',     'value' => $videos['branded'],        'color' => '#a78bfa'],
            ['label' => 'CGI Video · Templated',   'value' => $videos['templated'],      'color' => '#c084fc'],
            ['label' => 'Occasion Image · Raw',    'value' => $occImages['raw'],         'color' => '#22d3ee'],
            ['label' => 'Occasion Image · Branded','value' => $occImages['branded'],     'color' => '#60a5fa'],
            ['label' => 'Occasion Image · Merged', 'value' => $occImages['templated'],   'color' => '#818cf8'],
            ['label' => 'Occasion · Posted',       'value' => $postedTotal,            'color' => '#f43f5e'],
        ];

        return array_values(array_filter($segments, fn ($s) => $s['value'] > 0));
    }
}
