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

use DateTime;
use Sonata\AdminBundle\Datagrid\ProxyQueryInterface;
use Sonata\AdminBundle\Filter\Model\FilterData;
use Sonata\AdminBundle\Form\Type\Filter\FilterDataType;
use Sonata\AdminBundle\Form\Type\Operator\DateOperatorType;
use Sonata\AdminBundle\Form\Type\Operator\DateRangeOperatorType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType as SymfonyDateTimeType;

abstract class AbstractDateFilter extends Filter
{
    protected bool $range = false;

    protected bool $time = false;

    public function filter(ProxyQueryInterface $query, string $field, FilterData $data): void
    {
        if (!$data->hasValue()) {
            return;
        }

        $format = \array_key_exists('format', $this->getFieldOptions()) ? $this->getFieldOptions()['format'] : 'c';
        $queryBuilder = $query->getQueryBuilder();

        // NEXT_MAJOR: Use ($this instanceof RangeFilterInterface) for if statement, remove deprecated range.
        if (!($range = $this instanceof RangeFilterInterface)) {
            @trigger_error(
                sprintf(
                    'Using `range` property is deprecated since version 1.x, will be removed in 2.0.' .
                    ' Implement %s instead.',
                    RangeFilterInterface::class
                ),
                \E_USER_DEPRECATED
            );

            $range = $this->range;
        }

        if ($range) {
            // additional data check for ranged items
            if (!\array_key_exists('start', $data->getValue()) || !\array_key_exists('end', $data->getValue())) {
                return;
            }

            if (!$data->getValue()['start'] || !$data->getValue()['end']) {
                return;
            }

            // transform types
            if ($this->getOption('input_type') === 'timestamp') {
                $data->getValue()['start'] = $data->getValue()['start'] instanceof DateTime ? $data->getValue()['start']->getTimestamp() : 0;
                $data->getValue()['end'] = $data->getValue()['end'] instanceof DateTime ? $data->getValue()['end']->getTimestamp() : 0;
            }

            $type = $data->getType() ?: DateRangeOperatorType::TYPE_BETWEEN;

            $innerQuery = $queryBuilder
                ->query()
                ->range($field, [
                    'gte' => $data->getValue()['start']->format($format),
                    'lte' => $data->getValue()['end']->format($format),
                ]);

            if ($type === DateRangeOperatorType::TYPE_NOT_BETWEEN) {
                $query->addMustNot($innerQuery);
            } else {
                $query->addMust($innerQuery);
            }
        } else {
            if (!$data->getValue()) {
                return;
            }

            // default type for simple filter
            $type = $data->getType() ?: DateOperatorType::TYPE_GREATER_EQUAL;
            // just find an operator and apply query
            $operator = $this->getOperator($type);

            $value = $data->getValue();
            // transform types
            if ($this->getOption('input_type') === 'timestamp') {
                $value = $value instanceof DateTime ? $value->getTimestamp() : 0;
            }
            if($value instanceof DateTime ) {
                $value = $value->format($format);
            }

            // null / not null only check for col
            if (\in_array($operator, ['missing', 'exists'], true)) {
                $innerQuery = $queryBuilder
                    ->query()
                    ->exists($field);
            } elseif ($operator === '=') {
                $innerQuery = $queryBuilder
                    ->query()
                    ->range($field, [
                        'gte' => $value,
                        'lte' => $value,
                    ]);
            } else {
                $innerQuery = $queryBuilder
                    ->query()
                    ->range($field, [
                        $operator => $value,
                    ]);
            }

            if ($operator === 'missing') {
                $query->addMustNot($innerQuery);
            } else {
                $query->addMust($innerQuery);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultOptions(): array
    {
        return [
            'input_type' => SymfonyDateTimeType::class,
        ];
    }

    final public function getFormOptions(): array
    {
        return [
            'field_type' => $this->getFieldType(),
            'field_options' => $this->getFieldOptions(),
            'label' => $this->getLabel(),
            'operator_type' => $this->range ? DateRangeOperatorType::class : DateOperatorType::class,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getRenderSettings(): array
    {
        return [
            $this->getFilterTypeClass(),
            [
                'field_type'    => $this->getFieldType(),
                'field_options' => $this->getFieldOptions(),
                'label'         => $this->getLabel(),
            ],
        ];
    }

    /**
     * @return string
     *
     * NEXT_MAJOR: Make this method abstract
     */
    protected function getFilterTypeClass()
    {
        @trigger_error(
            __METHOD__ . ' should be implemented. It will be abstract in 2.0.',
            \E_USER_DEPRECATED
        );

        return FilterDataType::class;
    }

    /**
     * Resolves DataType:: constants to SQL operators.
     *
     * @param int $type
     *
     * @return string
     */
    protected function getOperator($type)
    {
        $type = (int) $type;

        $choices = [
            DateOperatorType::TYPE_EQUAL         => '=',
            DateOperatorType::TYPE_GREATER_EQUAL => 'gte',
            DateOperatorType::TYPE_GREATER_THAN  => 'gt',
            DateOperatorType::TYPE_LESS_EQUAL    => 'lte',
            DateOperatorType::TYPE_LESS_THAN     => 'lt',
        ];

        return $choices[$type] ?? '=';
    }
}
