<?php

namespace Sonata\AdminSearchBundle\Datagrid;

use Elastica\Query;
use Elastica\Query\AbstractQuery;
use Elastica\QueryBuilder;
use Sonata\AdminBundle\Datagrid\ProxyQueryInterface as BaseProxyQueryInterface;

interface ProxyQueryInterface extends BaseProxyQueryInterface
{
    public function getQueryBuilder(): QueryBuilder;

    public function getElasticaQuery(): Query;

    public function addMust(AbstractQuery $args): void;

    public function addMustNot(AbstractQuery $args): void;

    public function addShould(AbstractQuery $args): void;

    public function addFilter(AbstractQuery $args): void;
}
