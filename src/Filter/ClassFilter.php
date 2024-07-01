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
use Sonata\AdminBundle\Form\Type\Filter\FilterDataType;
use Sonata\AdminBundle\Form\Type\Operator\EqualOperatorType;
use Symfony\Component\Form\ChoiceList\ArrayChoiceList;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class ClassFilter extends Filter
{
    /**
     * {@inheritdoc}
     */
    public function filter(ProxyQueryInterface $queryBuilder, $alias, $field, $data)
    {
        if (!$data || !\is_array($data) || !\array_key_exists('value', $data)) {
            return;
        }

        if ($data['value'] === '') {
            return;
        }

        $data['type'] = !isset($data['type']) ? EqualOperatorType::TYPE_EQUAL : $data['type'];

        $operator = $this->getOperator((int) $data['type']);

        if (!$operator) {
            $operator = 'INSTANCE OF';
        }

        $this->applyWhere($queryBuilder, sprintf('%s %s %s', $alias, $operator, $data['value']));
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
            'field_type'    => ChoiceType::class,
            'field_options' => [
                'required'    => false,
                'choice_list' => new ArrayChoiceList(
                    array_values($this->getOption('sub_classes')),
                    array_keys($this->getOption('sub_classes'))
                ),
            ],
            'label'         => $this->getLabel(),
        ]];
    }

    private function getOperator($type): ?string
    {
        $choices = [
            EqualOperatorType::TYPE_EQUAL     => 'INSTANCE OF',
            EqualOperatorType::TYPE_NOT_EQUAL => 'NOT INSTANCE OF',
        ];

        return $choices[$type] ?? null;
    }
}
