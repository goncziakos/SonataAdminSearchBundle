<?php

declare(strict_types=1);

namespace Sonata\AdminSearchBundle\Filter;

use Elastica\Query\AbstractQuery;
use Sonata\AdminBundle\Filter\Filter as BaseFilter;
use Sonata\AdminBundle\Datagrid\ProxyQueryInterface as BaseProxyQueryInterface;
use Sonata\AdminBundle\Filter\Model\FilterData;
use Sonata\AdminSearchBundle\Datagrid\ProxyQueryInterface;

abstract class Filter extends BaseFilter
{
    protected bool $active = false;

    abstract public function filter(ProxyQueryInterface $query, string $field, FilterData $data): void;

    public function apply(BaseProxyQueryInterface $query, FilterData $filterData): void
    {
        if ($filterData->hasValue()) {
            $this->filter($query, $this->getFieldName(), $filterData);
            $this->setActive(true);
        }
    }

    protected function applyWhere(ProxyQueryInterface $query, AbstractQuery $parameter): void
    {
        if ($this->getCondition() === self::CONDITION_OR) {
            $query->addShould($parameter);
        } else {
            $query->addMust($parameter);
        }

        $this->active = true;
    }

    protected function getNewParameterName(ProxyQueryInterface $query): string
    {
        return str_replace('.', '_', $this->getName()) . '_' . $query->getUniqueParameterId();
    }
}
