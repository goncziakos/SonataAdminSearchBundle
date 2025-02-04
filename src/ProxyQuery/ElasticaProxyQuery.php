<?php

declare(strict_types=1);

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\AdminSearchBundle\ProxyQuery;

use Elastica\Query;
use Elastica\Query\AbstractQuery;
use Elastica\Query\BoolQuery;
use Elastica\QueryBuilder;
use Elastica\Search;
use FOS\ElasticaBundle\Finder\TransformedFinder;
use FOS\ElasticaBundle\Paginator\TransformedPaginatorAdapter;
use Sonata\AdminSearchBundle\Datagrid\ProxyQueryInterface;

class ElasticaProxyQuery implements ProxyQueryInterface
{
    protected ?string $sortBy = null;

    protected ?string $sortOrder = null;

    protected ?int $firstResult = null;

    protected ?int $maxResults = null;

    private int $uniqueParameterId;

    private TransformedFinder $finder;
    private Query $query;
    private BoolQuery $boolQuery;
    private QueryBuilder $queryBuilder;

    public function __construct(TransformedFinder $finder)
    {
        $this->finder = $finder;
        $this->boolQuery = new BoolQuery();
        $this->query = new Query($this->boolQuery);
        $this->queryBuilder = new QueryBuilder();
    }

    /**
     * {@inheritdoc}
     */
    public function __call($name, $args)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function execute(array $params = [], $hydrationMode = null): TransformedPaginatorAdapter
    {
        // TODO find method names

        // Sorted field and sort order
        $sortBy = $this->getSortBy();
        $sortOrder = $this->getSortOrder();

        if ($sortBy && $sortOrder) {
            $this->query->setSort([$sortBy => ['order' => strtolower($sortOrder)]]);
        }

        return $this->finder->createPaginatorAdapter(
            $this->query,
            [
                Search::OPTION_SIZE => $this->getMaxResults(),
                Search::OPTION_FROM => $this->getFirstResult(),
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function setSortBy(?array $parentAssociationMappings, array $fieldMapping): self
    {
        $alias = '';

        foreach ((array) $parentAssociationMappings as $associationMapping) {
            $alias .= $associationMapping['fieldName'] . '.';
        }

        $this->sortBy = $alias . $fieldMapping['fieldName'];

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getSortBy(): ?string
    {
        return $this->sortBy;
    }

    /**
     * {@inheritdoc}
     */
    public function setSortOrder(string $sortOrder): self
    {
        $this->sortOrder = $sortOrder;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getSortOrder(): ?string
    {
        return $this->sortOrder;
    }

    /**
     * {@inheritdoc}
     */
    public function setFirstResult(?int $firstResult): self
    {
        $this->firstResult = $firstResult;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getFirstResult(): ?int
    {
        return $this->firstResult;
    }

    /**
     * {@inheritdoc}
     */
    public function setMaxResults(?int $maxResults): self
    {
        $this->maxResults = $maxResults;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getMaxResults(): ?int
    {
        return $this->maxResults;
    }


    public function getUniqueParameterId(): int
    {
        return $this->uniqueParameterId++;
    }

    public function entityJoin(array $associationMappings)
    {
        // TODO
    }

    public function addMust($args): void
    {
        $this->boolQuery->addMust($args);
    }

    public function addShould(AbstractQuery $args): void
    {
        $this->boolQuery->addShould($args);
    }

    public function addMustNot(AbstractQuery $args): void
    {
        $this->boolQuery->addMustNot($args);
    }

    public function addFilter(AbstractQuery $args): void
    {
        $this->boolQuery->addFilter($args);
    }

    public function getQueryBuilder(): QueryBuilder
    {
        return $this->queryBuilder;
    }

    public function getElasticaQuery(): Query
    {
        return $this->query;
    }
}
