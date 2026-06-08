<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;


class GalleryAssetPaginator
{
    public const PER_PAGE = 20;

    /**
     * Paginate CGI image or video gallery cards (raw / branded / merged per generation).
     */
    public static function paginateCgiMedia(
        Builder $baseQuery,
        string $media,
        bool $canViewBranded,
        int $perPage = self::PER_PAGE,
    ): LengthAwarePaginator {
        $isImage = $media === 'image';

        $variants = [
            ['variant' => 'raw', 'column' => $isImage ? 'image_url' : 'video_url', 'sort' => 1],
        ];

        if ($canViewBranded) {
            $variants[] = [
                'variant' => 'branded',
                'column' => $isImage ? 'branded_image_url' : 'branded_video_url',
                'sort' => 2,
            ];
        }

        $variants[] = [
            'variant' => 'merged',
            'column' => $isImage ? 'merged_image_url' : 'merged_video_url',
            'sort' => 3,
        ];

        $baseQuery = (clone $baseQuery)->reorder();

        return self::paginateFromUnion($baseQuery, $variants, $perPage, function ($rows, $models) use ($isImage) {
            return $rows->map(function ($row) use ($models, $isImage) {
                $model = $models->get($row->id);
                if (!$model) {
                    return null;
                }

                $raw = match ($row->variant) {
                    'raw'     => $isImage ? $model->image_url : $model->video_url,
                    'branded' => $isImage ? $model->branded_image_url : $model->branded_video_url,
                    'merged'  => $isImage ? $model->merged_image_url : $model->merged_video_url,
                    default   => null,
                };

                $url = PublicMediaUrl::forMedia($raw);
                if ($url === '') {
                    return null;
                }

                return (object) [
                    'variant' => $row->variant,
                    'model'   => $model,
                    'url'     => $url,
                    'user_id' => $model->user_id,
                ];
            })->filter()->values();
        });
    }

    /**
     * Paginate occasion gallery cards (raw / branded / merged per campaign).
     */
    public static function paginateOccasionMedia(
        Builder $baseQuery,
        string $tab,
        int $perPage = self::PER_PAGE,
    ): LengthAwarePaginator {
        $variants = match ($tab) {
            'branded' => [
                ['variant' => 'branded', 'column' => 'branded_image_url', 'sort' => 1],
            ],
            'non_branded' => [
                ['variant' => 'raw', 'column' => 'image_url', 'sort' => 1],
            ],
            'merge' => [
                ['variant' => 'merged', 'column' => 'merged_image_url', 'sort' => 1],
            ],
            default => [
                ['variant' => 'raw', 'column' => 'image_url', 'sort' => 1],
                ['variant' => 'branded', 'column' => 'branded_image_url', 'sort' => 2],
                ['variant' => 'merged', 'column' => 'merged_image_url', 'sort' => 3],
            ],
        };

        $baseQuery = (clone $baseQuery)->reorder();

        return self::paginateFromUnion($baseQuery, $variants, $perPage, function ($rows, $models) {
            return $rows->map(function ($row) use ($models) {
                $model = $models->get($row->id);
                if (!$model) {
                    return null;
                }

                $raw = match ($row->variant) {
                    'raw'     => $model->image_url,
                    'branded' => $model->branded_image_url,
                    'merged'  => $model->merged_image_url,
                    default   => null,
                };

                $url = PublicMediaUrl::forMedia($raw);
                if ($url === '') {
                    return null;
                }

                return (object) [
                    'variant'    => $row->variant,
                    'model'      => $model,
                    'url'        => $url,
                    'isBranded'  => $row->variant === 'branded',
                    'isMerged'   => $row->variant === 'merged',
                ];
            })->filter()->values();
        });
    }

  /**
     * @param  array<int, array{variant: string, column: string, sort: int}>  $variants
     */
    private static function paginateFromUnion(
        Builder $baseQuery,
        array $variants,
        int $perPage,
        callable $mapPage,
    ): LengthAwarePaginator {
        $model = $baseQuery->getModel();
        $union = null;

        foreach ($variants as $variant) {
            $part = (clone $baseQuery)
                ->whereNotNull($variant['column'])
                ->where($variant['column'], '!=', '')
                ->select([
                    $model->getQualifiedKeyName() . ' as id',
                    DB::raw("'" . $variant['variant'] . "' as variant"),
                    $model->getTable() . '.created_at as created_at',
                    DB::raw((int) $variant['sort'] . ' as sort_order'),
                ]);

            $union = $union ? $union->unionAll($part) : $part;
        }

        if ($union === null) {
            return new LengthAwarePaginator(collect(), 0, $perPage, 1, [
                'path'  => request()->url(),
                'query' => request()->query(),
            ]);
        }

        $page = LengthAwarePaginator::resolveCurrentPage();
        $sub  = DB::query()->fromSub($union, 'gallery_assets');

        $total = (clone $sub)->count();

        $rows = (clone $sub)
            ->orderByDesc('created_at')
            ->orderBy('sort_order')
            ->forPage($page, $perPage)
            ->get();

        $with = array_keys($baseQuery->getEagerLoads());
        $models = $model->newQuery()
            ->when(!empty($with), fn ($q) => $q->with($with))
            ->whereIn($model->getQualifiedKeyName(), $rows->pluck('id')->unique())
            ->get()
            ->keyBy($model->getKeyName());

        $items = $mapPage($rows, $models);

        return new LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()],
        );
    }
}
