<?php declare(strict_types=1);

namespace Laser\Core\Framework\DataAbstractionLayer\Version;

use Laser\Core\Framework\Log\Package;

#[Package('core')]
class VersionEvents
{
    /**
     * @Event("Laser\Core\Framework\DataAbstractionLayer\Event\EntityWrittenEvent")
     */
    final public const VERSION_WRITTEN_EVENT = 'version.written';

    /**
     * @Event("Laser\Core\Framework\DataAbstractionLayer\Event\EntityDeletedEvent")
     */
    final public const VERSION_DELETED_EVENT = 'version.deleted';

    /**
     * @Event("Laser\Core\Framework\DataAbstractionLayer\Event\EntityLoadedEvent")
     */
    final public const VERSION_LOADED_EVENT = 'version.loaded';

    /**
     * @Event("Laser\Core\Framework\DataAbstractionLayer\Event\EntitySearchResultLoadedEvent")
     */
    final public const VERSION_SEARCH_RESULT_LOADED_EVENT = 'version.search.result.loaded';

    /**
     * @Event("Laser\Core\Framework\DataAbstractionLayer\Event\EntityAggregationResultLoadedEvent")
     */
    final public const VERSION_AGGREGATION_LOADED_EVENT = 'version.aggregation.result.loaded';

    /**
     * @Event("Laser\Core\Framework\DataAbstractionLayer\Event\EntityIdSearchResultLoadedEvent")
     */
    final public const VERSION_ID_SEARCH_RESULT_LOADED_EVENT = 'version.id.search.result.loaded';

    /**
     * @Event("Laser\Core\Framework\DataAbstractionLayer\Event\EntityWrittenEvent")
     */
    final public const VERSION_COMMIT_WRITTEN_EVENT = 'version_commit.written';

    /**
     * @Event("Laser\Core\Framework\DataAbstractionLayer\Event\EntityDeletedEvent")
     */
    final public const VERSION_COMMIT_DELETED_EVENT = 'version_commit.deleted';

    /**
     * @Event("Laser\Core\Framework\DataAbstractionLayer\Event\EntityLoadedEvent")
     */
    final public const VERSION_COMMIT_LOADED_EVENT = 'version_commit.loaded';

    /**
     * @Event("Laser\Core\Framework\DataAbstractionLayer\Event\EntitySearchResultLoadedEvent")
     */
    final public const VERSION_COMMIT_SEARCH_RESULT_LOADED_EVENT = 'version_commit.search.result.loaded';

    /**
     * @Event("Laser\Core\Framework\DataAbstractionLayer\Event\EntityAggregationResultLoadedEvent")
     */
    final public const VERSION_COMMIT_AGGREGATION_LOADED_EVENT = 'version_commit.aggregation.result.loaded';

    /**
     * @Event("Laser\Core\Framework\DataAbstractionLayer\Event\EntityIdSearchResultLoadedEvent")
     */
    final public const VERSION_COMMIT_ID_SEARCH_RESULT_LOADED_EVENT = 'version_commit.id.search.result.loaded';

    /**
     * @Event("Laser\Core\Framework\DataAbstractionLayer\Event\EntityWrittenEvent")
     */
    final public const VERSION_COMMIT_DATA_WRITTEN_EVENT = 'version_commit_data.written';

    /**
     * @Event("Laser\Core\Framework\DataAbstractionLayer\Event\EntityDeletedEvent")
     */
    final public const VERSION_COMMIT_DATA_DELETED_EVENT = 'version_commit_data.deleted';

    /**
     * @Event("Laser\Core\Framework\DataAbstractionLayer\Event\EntityLoadedEvent")
     */
    final public const VERSION_COMMIT_DATA_LOADED_EVENT = 'version_commit_data.loaded';

    /**
     * @Event("Laser\Core\Framework\DataAbstractionLayer\Event\EntitySearchResultLoadedEvent")
     */
    final public const VERSION_COMMIT_DATA_SEARCH_RESULT_LOADED_EVENT = 'version_commit_data.search.result.loaded';

    /**
     * @Event("Laser\Core\Framework\DataAbstractionLayer\Event\EntityAggregationResultLoadedEvent")
     */
    final public const VERSION_COMMIT_DATA_AGGREGATION_LOADED_EVENT = 'version_commit_data.aggregation.result.loaded';

    /**
     * @Event("Laser\Core\Framework\DataAbstractionLayer\Event\EntityIdSearchResultLoadedEvent")
     */
    final public const VERSION_COMMIT_DATA_ID_SEARCH_RESULT_LOADED_EVENT = 'version_commit_data.id.search.result.loaded';
}
