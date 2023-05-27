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

use Elastica\Query\AbstractQuery;
use Elastica\Search;
use FOS\ElasticaBundle\Finder\TransformedFinder;
use Sonata\AdminBundle\Datagrid\ProxyQueryInterface;

class ElasticaProxyQuery implements ProxyQueryInterface
{
    protected ?string $sortBy = null;

    protected ?string $sortOrder = null;

    protected ?int $firstResult = null;

    protected ?int $maxResults = null;

    /**
     * @var array
     */
    protected $results;

    private $finder;
    private $query;
    private $boolQuery;

    public function __construct(TransformedFinder $finder)
    {
        $this->finder = $finder;
        $this->query = new \Elastica\Query();
        $this->boolQuery = new \Elastica\Query\BoolQuery();
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
    public function execute(array $params = [], $hydrationMode = null)
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
    public function setSortBy(array $parentAssociationMappings, array $fieldMapping): self
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

    /**
     * {@inheritdoc}
     */
    public function getResults()
    {
        return $this->results;
    }

    public function getSingleScalarResult()
    {
        // TODO
    }

    public function getUniqueParameterId()
    {
        // TODO
    }

    public function entityJoin(array $associationMappings)
    {
        // TODO
    }

    public function addMust($args)
    {
        $this->boolQuery->addMust($args);
        $this->query = new \Elastica\Query($this->boolQuery);
    }

    public function addMustNot($args)
    {
        $this->boolQuery->addMustNot($args);
        $this->query = new \Elastica\Query($this->boolQuery);
    }

    /**
     * Add should part to query.
     *
     * @param AbstractQuery|array $args Should query
     *
     * @return ElasticaProxyQuery
     */
    public function addShould($args)
    {
        $this->boolQuery->addShould($args);
        $this->query = new \Elastica\Query($this->boolQuery);
    }
}
