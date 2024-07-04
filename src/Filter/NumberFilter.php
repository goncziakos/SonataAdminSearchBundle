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
use Sonata\AdminBundle\Filter\Model\FilterData;
use Sonata\AdminBundle\Form\Type\Operator\NumberOperatorType;

class NumberFilter extends Filter
{
    public function filter(ProxyQueryInterface $query, string $field, FilterData $data): void
    {
        if (!$data->hasValue()) {
            return;
        }

        $type = $data->getType() ?? NumberOperatorType::TYPE_EQUAL ;
        $operator = $this->getOperator($type);

        $queryBuilder = $query->getQueryBuilder();

        if ($operator === null) {
            // Match query to get equality
            $innerQuery = $queryBuilder
                ->query()
                ->match($field, $data->getValue());
        } else {
            // Range query
            $innerQuery = $queryBuilder
                ->query()
                ->range($field, [
                    $operator => $data->getValue(),
                ]);
        }

        $query->addMust($innerQuery);
    }

    public function getDefaultOptions(): array
    {
        return [];
    }

    public function getFormOptions(): array
    {
        return [
            'field_type'    => $this->getFieldType(),
            'field_options' => $this->getFieldOptions(),
            'label'         => $this->getLabel(),
        ];
    }

    private function getOperator(int $type): ?string
    {
        $choices = [
            NumberOperatorType::TYPE_EQUAL         => null,
            NumberOperatorType::TYPE_GREATER_EQUAL => 'gte',
            NumberOperatorType::TYPE_GREATER_THAN  => 'gt',
            NumberOperatorType::TYPE_LESS_EQUAL    => 'lte',
            NumberOperatorType::TYPE_LESS_THAN     => 'lt',
        ];

        return $choices[$type];
    }
}
