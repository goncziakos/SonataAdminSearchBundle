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

use Doctrine\Common\Collections\Collection;
use Sonata\AdminBundle\Filter\Model\FilterData;
use Sonata\AdminBundle\Form\Type\Operator\EqualOperatorType;
use Sonata\AdminSearchBundle\Datagrid\ProxyQueryInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

class ModelFilter extends Filter
{
    /**
     * {@inheritdoc}
     */
    public function filter(ProxyQueryInterface $query, string $field, FilterData $data): void
    {
        if (!$data->hasValue()) {
            return;
        }

        $value = $data->getValue();

        if ($value instanceof Collection) {
            $value = $value->toArray();
        }

        if (!is_array($value)) {
            $value = [$value];
        }

        $value = array_map(function (object $value) {
            return $value->getId();
        }, $value);

        $terms = $query->getQueryBuilder()->query()->terms($this->getFieldName(), $value);
        if ($data->isType(EqualOperatorType::TYPE_NOT_EQUAL)) {
            $query->addMustNot($terms);
            return;
        }
        $query->addMust($terms);
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultOptions(): array
    {
        return [
            'mapping_type'     => false,
            'field_name'       => false,
            'field_type'       => EntityType::class,
            'field_options'    => [],
            'operator_type'    => EqualOperatorType::class,
            'operator_options' => [],
        ];
    }

    public function getFormOptions(): array
    {
        return [
            'field_type' => $this->getFieldType(),
            'field_options' => $this->getFieldOptions(),
            'operator_type' => $this->getOption('operator_type'),
            'operator_options' => $this->getOption('operator_options'),
            'label' => $this->getLabel(),
        ];
    }

}
