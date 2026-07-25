<?php
declare(strict_types=1);

namespace app\provider;

/**
 * 聚合搜索引擎 Provider 抽象接口
 *
 * 所有数据源（网盘爬虫 / TG 索引 / 第三方 API / 自建索引）
 * 必须实现此接口，由 AggregatorService 统一编排。
 *
 * 详见 SPEC.md §2.2.2
 */
interface ProviderInterface
{
    /**
     * 执行搜索
     *
     * @param Query $query 查询对象（关键词、来源、筛选、分页）
     * @return ResultSet 结果集（含 total、items、took_ms）
     *
     * @throws ProviderException Provider 不健康或超时时抛出
     */
    public function search(Query $query): ResultSet;

    /**
     * 获取资源元数据（详情 + 失效检测）
     *
     * @param string $url 资源原始链接
     * @return ResourceMeta 资源元数据
     *
     * @throws ProviderException 抓取失败时抛出
     */
    public function fetch(string $url): ResourceMeta;

    /**
     * 解析真实下载链接（可选能力）
     *
     * 仅当 capabilities() 声明支持 CAN_PARSE 时实现。
     * 不支持时返回 null，不得编造假链接。
     *
     * @param string $url 资源原始链接
     * @return DownloadUrl|null 解析到的下载链接，不支持或失败返回 null
     *
     * @throws ProviderException Provider 异常时抛出
     */
    public function parse(string $url): ?DownloadUrl;

    /**
     * 声明本 Provider 支持的能力
     *
     * 用于 AggregatorService 路由：
     * - CAN_SEARCH  支持 search()
     * - CAN_FETCH   支持 fetch()
     * - CAN_PARSE   支持 parse()
     */
    public function capabilities(): Capabilities;

    /**
     * 健康检查
     *
     * 用于超管后台展示 Provider 状态、AggregatorService 熔断判断。
     */
    public function health(): HealthStatus;

    /**
     * Provider 唯一标识（如 baidu / aliyun / telegram / self_indexed）
     */
    public function name(): string;
}
