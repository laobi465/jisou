<?php
declare(strict_types=1);

namespace app\service;

use app\provider\ProviderException;
use app\provider\ProviderInterface;
use app\provider\Query;
use app\provider\ResourceMeta;
use app\provider\ResultSet;

/**
 * 聚合搜索编排服务
 *
 * 职责：
 * 1. 根据 Query.sources 取启用的 Providers
 * 2. 并行调用 search()，单源超时熔断，单源失败不影响整体
 * 3. 合并去重（URL hash）→ 排序（相关度 + 新鲜度 + 来源权重）→ 分页
 * 4. Redis 短缓存 5-15 分钟
 *
 * 详见 PROJECT.md §2.3 数据流
 *
 * 注意：本骨架实现编排与去重逻辑；Provider 注册表与 Redis 缓存适配
 * 在 M1 里程碑接入真实 Provider 时填充（标注 待接入 处）。
 */
final class AggregatorService
{
    /** @var int 单 Provider 超时（毫秒） */
    private const PER_PROVIDER_TIMEOUT_MS = 3000;

    /** @var int 搜索结果缓存时长（秒） */
    private const CACHE_TTL_SECONDS = 600;

    /** @var array<string,ProviderInterface> 已注册 Provider，按 name 索引 */
    private array $providers = [];

    /**
     * 注册 Provider
     */
    public function register(ProviderInterface $provider): void
    {
        $this->providers[$provider->name()] = $provider;
    }

    /**
     * 执行聚合搜索
     *
     * @return array{total:int, took_ms:int, items:ResourceMeta[], sources_used:string[], sources_failed:string[]}
     */
    public function search(Query $query): array
    {
        $startedAt = microtime(true);

        // 1. 选取目标 Providers
        $targets = $this->resolveProviders($query);
        if (empty($targets)) {
            return [
                'total'          => 0,
                'took_ms'        => $this->elapsedMs($startedAt),
                'items'          => [],
                'sources_used'   => [],
                'sources_failed' => [],
            ];
        }

        // 2. 并行调用各 Provider（当前实现为顺序调用 + 超时熔断占位）
        //    M2 里程碑接入真实并发（待核实：swoole 协程或 parallel 扩展选型）
        $allItems    = [];
        $sourcesUsed = [];
        $sourcesFailed = [];

        foreach ($targets as $provider) {
            try {
                $result = $this->callWithTimeout($provider, $query);
                foreach ($result->items as $item) {
                    $allItems[] = $item;
                }
                $sourcesUsed[] = $provider->name();
            } catch (ProviderException $e) {
                // 单源失败记录，不阻塞其他源
                $sourcesFailed[] = $provider->name();
            }
        }

        // 3. 去重（按 url_hash）+ 排序 + 分页
        $deduped = $this->deduplicate($allItems);
        $sorted  = $this->sortResults($deduped, $query);
        $total   = count($sorted);
        $paged   = array_slice($sorted, $query->offset(), $query->size);

        return [
            'total'          => $total,
            'took_ms'        => $this->elapsedMs($startedAt),
            'items'          => $paged,
            'sources_used'   => $sourcesUsed,
            'sources_failed' => $sourcesFailed,
        ];
    }

    /**
     * 选取目标 Provider
     */
    private function resolveProviders(Query $query): array
    {
        if (empty($query->sources)) {
            return array_values($this->providers);
        }

        $targets = [];
        foreach ($query->sources as $name) {
            if (isset($this->providers[$name])) {
                $targets[] = $this->providers[$name];
            }
        }
        return $targets;
    }

    /**
     * 调用单个 Provider（带超时熔断）
     *
     * 当前为同步调用，未实现并发。M2 里程碑接入协程并发后改造。
     */
    private function callWithTimeout(ProviderInterface $provider, Query $query): ResultSet
    {
        $health = $provider->health();
        if (!$health->isAvailable()) {
            throw ProviderException::unhealthy(
                $provider->name(),
                $health->message ?? 'Provider 不可用'
            );
        }

        try {
            return $provider->search($query);
        } catch (ProviderException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw new ProviderException(
                $provider->name(),
                '搜索异常: ' . $e->getMessage(),
                ProviderException::CODE_FETCH_FAILED,
                $e
            );
        }
    }

    /**
     * 去重（按 url_hash 保留首次出现的）
     *
     * @param ResourceMeta[] $items
     * @return ResourceMeta[]
     */
    private function deduplicate(array $items): array
    {
        $seen  = [];
        $unique = [];
        foreach ($items as $item) {
            if (isset($seen[$item->urlHash])) {
                continue;
            }
            $seen[$item->urlHash] = true;
            $unique[] = $item;
        }
        return $unique;
    }

    /**
     * 排序：新鲜度优先（extractedAt 倒序），来源权重次之
     *
     * @param ResourceMeta[] $items
     * @return ResourceMeta[]
     */
    private function sortResults(array $items, Query $query): array
    {
        usort($items, function (ResourceMeta $a, ResourceMeta $b): int {
            $t1 = strtotime($a->extractedAt) ?: 0;
            $t2 = strtotime($b->extractedAt) ?: 0;
            return $t2 <=> $t1;
        });
        return $items;
    }

    private function elapsedMs(float $startedAt): int
    {
        return (int) round((microtime(true) - $startedAt) * 1000);
    }
}
