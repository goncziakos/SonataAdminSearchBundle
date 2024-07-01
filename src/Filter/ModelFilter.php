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
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use RuntimeException;
use Sonata\AdminBundle\Datagrid\ProxyQueryInterface;
use Sonata\AdminBundle\FieldDescription\FieldDescriptionInterface;
use Sonata\AdminBundle\Form\Type\Filter\DefaultType;
use Sonata\AdminBundle\Form\Type\Operator\EqualOperatorType;
use Sonata\Form\Type\EqualType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

class ModelFilter extends Filter
{
    /**
     * {@inheritdoc}
     */
    public function filter(ProxyQueryInterface $queryBuilder, $alias, $field, $data)
    {
        if (!$data || !\is_array($data) || !\array_key_exists('value', $data)) {
            return;
        }

        if ($data['value'] instanceof Collection) {
            $data['value'] = $data['value']->toArray();
        }

        if (\is_array($data['value'])) {
            $this->handleMultiple($queryBuilder, $alias, $data);
        } else {
            $this->handleModel($queryBuilder, $alias, $data);
        }
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

    /**
     * {@inheritdoc}
     */
    public function getRenderSettings(): array
    {
        return [DefaultType::class, [
            'field_type'       => $this->getFieldType(),
            'field_options'    => $this->getFieldOptions(),
            'operator_type'    => $this->getOption('operator_type'),
            'operator_options' => $this->getOption('operator_options'),
            'label'            => $this->getLabel(),
        ]];
    }

    /**
     * For the record, the $alias value is provided by the association method (and the entity join method)
     *  so the field value is not used here.
     *
     * @param string              $alias
     * @param mixed               $data
     * @param ProxyQueryInterface $queryBuilder
     *
     * @return mixed
     */
    protected function handleMultiple(ProxyQueryInterface $queryBuilder, $alias, $data)
    {
        if (\count($data['value']) === 0) {
            return;
        }

        $parameterName = $this->getNewParameterName($queryBuilder);

        if (isset($data['type']) && $data['type'] === EqualOperatorType::TYPE_NOT_EQUAL) {
            $this->applyWhere($queryBuilder, $queryBuilder->expr()->notIn($alias, ':' . $parameterName));
        } else {
            $this->applyWhere($queryBuilder, $queryBuilder->expr()->in($alias, ':' . $parameterName));
        }

        $queryBuilder->setParameter($parameterName, $data['value']);
    }

    /**
     * @param string              $alias
     * @param mixed               $data
     * @param ProxyQueryInterface $queryBuilder
     *
     * @return mixed
     */
    protected function handleModel(ProxyQueryInterface $queryBuilder, $alias, $data)
    {
        if (empty($data['value'])) {
            return;
        }

        $parameterName = $this->getNewParameterName($queryBuilder);

        if (isset($data['type']) && $data['type'] === EqualOperatorType::TYPE_NOT_EQUAL) {
            $this->applyWhere($queryBuilder, sprintf('%s != :%s', $alias, $parameterName));
        } else {
            $this->applyWhere($queryBuilder, sprintf('%s = :%s', $alias, $parameterName));
        }

        $queryBuilder->setParameter($parameterName, $data['value']);
    }

    /**
     * {@inheritdoc}
     */
    protected function association(ProxyQueryInterface $queryBuilder, $value)
    {
        $types = [
            FieldDescriptionInterface::TYPE_ONE_TO_ONE,
            FieldDescriptionInterface::TYPE_ONE_TO_MANY,
            FieldDescriptionInterface::TYPE_MANY_TO_MANY,
            FieldDescriptionInterface::TYPE_MANY_TO_ONE,
        ];

        if (!\in_array($this->getOption('mapping_type'), $types, true)) {
            throw new RuntimeException('Invalid mapping type');
        }

        $associationMappings = $this->getParentAssociationMappings();
        $associationMappings[] = $this->getAssociationMapping();
        $alias = $queryBuilder->entityJoin($associationMappings);

        return [$alias, false];
    }
}
