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

namespace Sonata\AdminSearchBundle\Filter;

use Sonata\AdminBundle\Datagrid\ProxyQueryInterface;
use Sonata\AdminBundle\Filter\Filter as BaseFilter;
use Sonata\AdminBundle\Filter\Model\FilterData;

abstract class Filter extends BaseFilter
{
    protected $active = false;

    /**
     * {@inheritdoc}
     */
    public function apply(ProxyQueryInterface $query, FilterData $filterData): void
    {
        if ($filterData->hasValue()) {
            [$alias, $field] = $this->association($query, $filterData->getValue());
            $this->setActive(true);
            $this->filter($query, $alias, $field, ['type' => $filterData->getType(), 'value' => $filterData->getValue()]);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function association(ProxyQueryInterface $queryBuilder, $value)
    {
        $alias = $queryBuilder->entityJoin($this->getParentAssociationMappings());

        return [$alias, $this->getFieldName()];
    }

    /**
     * @param mixed               $parameter
     * @param ProxyQueryInterface $queryBuilder
     */
    protected function applyWhere(ProxyQueryInterface $queryBuilder, $parameter)
    {
        if ($this->getCondition() === self::CONDITION_OR) {
            $queryBuilder->orWhere($parameter);
        } else {
            $queryBuilder->andWhere($parameter);
        }

        // filter is active since it's added to the queryBuilder
        $this->active = true;
    }

    /**
     * @param ProxyQueryInterface $queryBuilder
     *
     * @return string
     */
    protected function getNewParameterName(ProxyQueryInterface $queryBuilder)
    {
        // dots are not accepted in a DQL identifier so replace them
        // by underscores.
        return str_replace('.', '_', $this->getName()) . '_' . $queryBuilder->getUniqueParameterId();
    }
}
