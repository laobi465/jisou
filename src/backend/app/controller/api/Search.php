<?php
declare(strict_types=1);

namespace app\controller\api;

use app\common\ApiResponse;
use app\common\ErrorCode;
use app\provider\Query;
use app\service\AggregatorService;
use think\Request;

/**
 * 搜索 Controller
 */
final class Search
{
    public function __construct(
        private readonly AggregatorService $aggregator
    ) {
    }

    public function search(Request $request): \think\Response
    {
        $keyword = (string) $request->param('q', '');
        if (mb_strlen(trim($keyword)) < 2) {
            return $this->json(ApiResponse::error(ErrorCode::QUERY_TOO_SHORT, '搜索词至少 2 个字符'));
        }

        $sources = array_filter(explode(',', (string) $request->param('sources', '')), fn ($s) => $s !== '');
        $page    = (int) $request->param('page', 1);
        $size    = (int) $request->param('size', 20);

        try {
            $query = new Query(
                keyword: $keyword,
                sources: $sources,
                page: $page,
                size: $size,
                filters: $this->extractFilters($request)
            );
        } catch (\InvalidArgumentException $e) {
            return $this->json(ApiResponse::error(ErrorCode::PARAM_FORMAT_INVALID, $e->getMessage()));
        }

        $result = $this->aggregator->search($query);

        return $this->json(ApiResponse::success([
            'total'          => $result['total'],
            'took_ms'        => $result['took_ms'],
            'sources_used'   => $result['sources_used'],
            'sources_failed' => $result['sources_failed'],
            'page'           => $page,
            'size'           => $size,
            'items'          => array_map(fn ($item) => [
                'hash'         => $item->urlHash,
                'title'        => $item->title,
                'source'       => $item->source,
                'source_url'   => $item->sourceUrl,
                'size_bytes'   => $item->sizeBytes,
                'file_type'    => $item->fileType,
                'origin_url'   => $item->originUrl,
                'extracted_at' => $item->extractedAt,
            ], $result['items']),
        ]));
    }

    /**
     * 提取筛选参数
     */
    private function extractFilters(Request $request): array
    {
        $filters = [];

        $timeRange = $request->param('time_range');
        if ($timeRange !== null) {
            $filters['time_range'] = (string) $timeRange;
        }

        $minSize = $request->param('min_size');
        if ($minSize !== null) {
            $filters['min_size'] = (int) $minSize;
        }

        $maxSize = $request->param('max_size');
        if ($maxSize !== null) {
            $filters['max_size'] = (int) $maxSize;
        }

        $status = $request->param('status');
        if ($status !== null) {
            $filters['status'] = (string) $status;
        }

        return $filters;
    }

    private function json(ApiResponse $response): \think\Response
    {
        return json($response->jsonSerialize())->code(200)->header([
            'Content-Type' => 'application/json; charset=utf-8',
        ]);
    }
}
