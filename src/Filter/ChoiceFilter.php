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

use Elastica\Util;
use Sonata\AdminBundle\Datagrid\ProxyQueryInterface;
use Sonata\AdminBundle\Filter\Model\FilterData;
use Sonata\AdminBundle\Form\Type\Filter\FilterDataType;
use Sonata\AdminBundle\Form\Type\Operator\ContainsOperatorType;
use Sonata\AdminBundle\Form\Type\Operator\EqualOperatorType;
use Sonata\AdminBundle\Form\Type\Operator\StringOperatorType;

class ChoiceFilter extends Filter
{
    public function filter(ProxyQueryInterface $query, string $field, FilterData $data): void
    {
        if (!$data->hasValue()) {
            return;
        }

        $type = $data->getType() ?? StringOperatorType::TYPE_CONTAINS;
        [$firstOperator, $secondOperator] = $this->getOperators((int) $type);

        if (\is_array($data->getValue())) {
            if (\count($data->getValue()) === 0) {
                return;
            }
        } else {
            if ($data->getValue() === '' || $data->getValue() === null || $data->getValue() === false || $data->getValue() === 'all') {
                return;
            }
        }
        $queryBuilder = $query->getQueryBuilder();
        $innerQuery = $queryBuilder
            ->query()
            ->terms($field, [Util::escapeTerm($data->getValue())]);
        if ($firstOperator === 'must') {
            $query->addMust($innerQuery);
        } else {
            $query->addMustNot($innerQuery);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultOptions(): array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getRenderSettings(): array
    {
        return [FilterDataType::class, [
            'operator_type' => EqualOperatorType::class,
            'field_type'    => $this->getFieldType(),
            'field_options' => $this->getFieldOptions(),
            'label'         => $this->getLabel(),
        ]];
    }

    private function getOperators($type): array
    {
        $choices = [
            ContainsOperatorType::TYPE_CONTAINS     => ['must', 'terms'],
            ContainsOperatorType::TYPE_NOT_CONTAINS => ['must_not', 'terms'],
        ];

        if (!isset($choices[$type])) {
            throw new \OutOfRangeException(sprintf(
                'The type "%s" is not supported, allowed one are "%s".',
                $type,
                implode('", "', array_keys($choices))
            ));
        }

        return $choices[$type];
    }
}
