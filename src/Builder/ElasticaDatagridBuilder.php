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
use Sonata\AdminBundle\Datagrid\Datagrid;
use Sonata\AdminBundle\Datagrid\DatagridInterface;
use Sonata\AdminBundle\FieldDescription\FieldDescriptionInterface;
use Sonata\AdminBundle\FieldDescription\TypeGuesserInterface;
use Sonata\AdminBundle\Filter\FilterFactoryInterface;
use Sonata\AdminBundle\Form\Type\ModelAutocompleteType;
use Sonata\AdminSearchBundle\Datagrid\Pager;
use Sonata\AdminSearchBundle\Filter\ModelFilter;
use Sonata\DoctrineORMAdminBundle\Filter\ModelFilter as ORMModelFilter;
use Sonata\AdminSearchBundle\Model\FinderProviderInterface;
use Sonata\AdminSearchBundle\ProxyQuery\ElasticaProxyQuery;
use Sonata\DoctrineORMAdminBundle\Filter\ModelAutocompleteFilter as ORMModelAutocompleteFilter;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormFactoryInterface;

class ElasticaDatagridBuilder implements DatagridBuilderInterface
{
    public function __construct(
        private FormFactoryInterface $formFactory,
        private FilterFactoryInterface $filterFactory,
        private TypeGuesserInterface $guesser,
        private FinderProviderInterface $finderProvider
    ) {
    }

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
            (ModelFilter::class === $fieldDescription->getType() || ORMModelFilter::class === $fieldDescription->getType()) &&
            null === $fieldDescription->getOption('field_type') || EntityType::class === $fieldDescription->getOption('field_type')
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
        if ((ORMModelAutocompleteFilter::class === $fieldDescription->getType())
            && (null === $fieldDescription->getOption('field_type') || ModelAutocompleteType::class === $fieldDescription->getOption('field_type'))
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
                    continue;
                }
                $fieldDescription->setOption($name, $fieldDescription->getOption($name, $value));
            }
        } else {
            $fieldDescription->setType($type);
        }

        $this->fixFieldDescription($fieldDescription);

        $fieldDescription->getAdmin()->addFilterFieldDescription($fieldDescription->getName(), $fieldDescription);

        $filter = $this->filterFactory->create($fieldDescription->getName(), $type, $fieldDescription->getOptions());
        $datagrid->addFilter($filter);
    }

    public function getBaseDatagrid(AdminInterface $admin, array $values = []): DatagridInterface
    {
        $pager = new Pager();

        $defaultOptions = [];
        $defaultOptions['csrf_protection'] = false;

        $formBuilder = $this->formFactory->createNamedBuilder(
            'filter',
            FormType::class,
            [],
            $defaultOptions
        );

        $proxyQuery = $admin->createQuery();
        // if the default modelmanager query builder is used, we need to replace it with elastica
        // if not, that means $admin->createQuery has been overriden by the user and already returns
        // an ElasticaProxyQuery object
        if (!$proxyQuery instanceof ElasticaProxyQuery) {
            $proxyQuery = new ElasticaProxyQuery($this->finderProvider->getFinderByAdmin($admin));
        }

        return new Datagrid(
            $proxyQuery,
            $admin->getList(),
            $pager,
            $formBuilder,
            $values
        );
    }

    /**
     * {@inheritdoc}
     */
    public function isSmart(AdminInterface $admin, array $values = []): bool
    {
        // All fields must be mapped in elastic, so we don’t worry about it
        return true;
    }
}
