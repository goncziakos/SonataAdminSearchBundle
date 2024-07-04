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

namespace Sonata\AdminSearchBundle\Builder;

use Sonata\AdminBundle\Admin\AdminInterface;
use Sonata\AdminBundle\Builder\DatagridBuilderInterface;
use Sonata\AdminBundle\Datagrid\DatagridInterface;
use Sonata\AdminBundle\FieldDescription\FieldDescriptionInterface;
use Sonata\AdminBundle\FieldDescription\TypeGuesserInterface;
use Sonata\AdminBundle\Form\Type\ModelAutocompleteType;
use Sonata\AdminSearchBundle\Filter\ModelFilter;
use Sonata\DoctrineORMAdminBundle\Filter\ModelAutocompleteFilter;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

/**
 * Admin search bundle wraps existing datagrid builder (orm, odm, phpcr)
 * and provides efficient datagrid builder based on smart engine (elasticsearch, )
 * Some filter fields could not be stored in the smart engine so we have to fallback
 * on the original datagrid builder (orm, odm, phpcr).
 */
class DatagridBuilder implements DatagridBuilderInterface
{
    public function __construct(
        private TypeGuesserInterface $guesser,
        private DatagridBuilderInterface $smartDatagridBuilder,
        private array $originalAdminDatagridBuilders = []
    ) {
    }

    /**
     * {@inheritdoc}
     */

    public function fixFieldDescription(FieldDescriptionInterface $fieldDescription): void
    {
        if ([] !== $fieldDescription->getFieldMapping()) {
            $fieldDescription->setOption('field_mapping', $fieldDescription->getOption('field_mapping', $fieldDescription->getFieldMapping()));
        }

        if ([] !== $fieldDescription->getAssociationMapping()) {
            $fieldDescription->setOption('association_mapping', $fieldDescription->getOption('association_mapping', $fieldDescription->getAssociationMapping()));
        }

        if ([] !== $fieldDescription->getParentAssociationMappings()) {
            $fieldDescription->setOption('parent_association_mappings', $fieldDescription->getOption('parent_association_mappings', $fieldDescription->getParentAssociationMappings()));
        }

        $fieldDescription->setOption('field_name', $fieldDescription->getOption('field_name', $fieldDescription->getFieldName()));

        if (
            ModelFilter::class === $fieldDescription->getType() && null === $fieldDescription->getOption('field_type')
            || EntityType::class === $fieldDescription->getOption('field_type')
        ) {
            $fieldDescription->setOption('field_options', array_merge([
                'class' => $fieldDescription->getTargetModel(),
            ], $fieldDescription->getOption('field_options', [])));
        }

        /**
         * NEXT_MAJOR: Remove the ModelAutocompleteFilter::class check.
         *
         * @psalm-suppress DeprecatedClass
         *
         * @see https://github.com/sonata-project/SonataDoctrineORMAdminBundle/pull/1545
         */
        if (
            ModelAutocompleteFilter::class === $fieldDescription->getType() && null === $fieldDescription->getOption('field_type')
            || ModelAutocompleteType::class === $fieldDescription->getOption('field_type')
        ) {
            $fieldDescription->setOption('field_options', array_merge([
                'class' => $fieldDescription->getTargetModel(),
                'model_manager' => $fieldDescription->getAdmin()->getModelManager(),
                'admin_code' => $fieldDescription->getAdmin()->getCode(),
                'context' => 'filter',
            ], $fieldDescription->getOption('field_options', [])));
        }

        if ($fieldDescription->describesAssociation()) {
            $fieldDescription->getAdmin()->attachAdminClass($fieldDescription);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function addFilter(DatagridInterface $datagrid, ?string $type, FieldDescriptionInterface $fieldDescription): void
    {
        if (null === $type) {
            $guessType = $this->guesser->guess($fieldDescription);
            if (null === $guessType) {
                throw new \InvalidArgumentException(sprintf(
                    'Cannot guess a type for the field description "%s", You MUST provide a type.',
                    $fieldDescription->getName()
                ));
            }

            /** @phpstan-var class-string $type */
            $type = $guessType->getType();
            $fieldDescription->setType($type);

            foreach ($guessType->getOptions() as $name => $value) {
                if (\is_array($value)) {
                    $fieldDescription->setOption($name, array_merge($value, $fieldDescription->getOption($name, [])));
                } else {
                    $fieldDescription->setOption($name, $fieldDescription->getOption($name, $value));
                }
            }
        } else {
            $fieldDescription->setType($type);
        }

        $this->fixFieldDescription($fieldDescription);

        $this->getAdminDatagridBuilder($fieldDescription->getAdmin())
            ->addFilter(
                $datagrid,
                $type,
                $fieldDescription,
            );
    }

    /**
     * {@inheritdoc}
     */
    public function getBaseDatagrid(AdminInterface $admin, array $values = []): DatagridInterface
    {
        // Check if we use smart or original datagrid builder
        $smartDatagrid = $this->smartDatagridBuilder->isSmart($admin, $values);

        return $this->getAdminDatagridBuilder($admin, $smartDatagrid)->getBaseDatagrid($admin, $values);
    }

    private function getAdminDatagridBuilder($admin, $smartDatagrid = true)
    {
        if ($smartDatagrid) {
            return $this->smartDatagridBuilder;
        }

        // Search the original datagrid builder for the specified admin
        return $this->originalAdminDatagridBuilders[$admin->getCode()];
    }
}
