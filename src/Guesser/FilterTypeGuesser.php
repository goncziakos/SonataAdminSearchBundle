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

namespace Sonata\AdminSearchBundle\Guesser;

use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Sonata\AdminBundle\FieldDescription\FieldDescriptionInterface;
use Sonata\AdminBundle\FieldDescription\TypeGuesserInterface;
use Sonata\AdminSearchBundle\Filter\BooleanFilter;
use Sonata\AdminSearchBundle\Filter\DateFilter;
use Sonata\AdminSearchBundle\Filter\DateTimeFilter;
use Sonata\AdminSearchBundle\Filter\NumberFilter;
use Sonata\AdminSearchBundle\Filter\StringFilter;
use Sonata\Form\Type\BooleanType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Guess\Guess;
use Symfony\Component\Form\Guess\TypeGuess;

class FilterTypeGuesser implements TypeGuesserInterface
{
    /**
     * {@inheritdoc}
     */
    public function guess(FieldDescriptionInterface $fieldDescription): ?TypeGuess
    {
        $options = [
            'field_name'                  => $fieldDescription->getFieldName(),
            'parent_association_mappings' => $fieldDescription->getParentAssociationMappings(),
            'field_type'                  => null,
            'field_options'               => [],
            'options'                     => [],
        ];
        // FIXME: Try to implement association using elastica
        /*
        if ($metadata->hasAssociation($propertyName)) {
            $mapping = $metadata->getAssociationMapping($propertyName);

            switch ($mapping['type']) {
                case ClassMetadataInfo::ONE_TO_ONE:
                case ClassMetadataInfo::ONE_TO_MANY:
                case ClassMetadataInfo::MANY_TO_ONE:
                case ClassMetadataInfo::MANY_TO_MANY:

                    $options['operator_type']    = 'sonata_type_equal';
                    $options['operator_options'] = array();

                    $options['field_type']    = 'entity';
                    $options['field_options'] = array(
                        'class' => $mapping['targetEntity']
                    );

                    $options['field_name']   = $mapping['fieldName'];
                    $options['mapping_type'] = $mapping['type'];

                    return new TypeGuess('doctrine_orm_model', $options, Guess::HIGH_CONFIDENCE);
            }
        }*/

        switch ($fieldDescription->getMappingType()) {
            case 'boolean':
                $options['field_type'] = BooleanType::class;
                $options['field_options'] = [];

                return new TypeGuess(BooleanFilter::class, $options, Guess::HIGH_CONFIDENCE);
            case 'datetime':
            case 'vardatetime':
            case 'datetimetz':
                return new TypeGuess(DateTimeFilter::class, $options, Guess::HIGH_CONFIDENCE);
            case 'date':
                return new TypeGuess(DateFilter::class, $options, Guess::HIGH_CONFIDENCE);
            case 'decimal':
            case 'float':
                $options['field_type'] = NumberType::class;

                return new TypeGuess(NumberFilter::class, $options, Guess::MEDIUM_CONFIDENCE);
            case 'integer':
            case 'bigint':
            case 'smallint':
                $options['field_type'] = NumberType::class;

                return new TypeGuess(NumberFilter::class, $options, Guess::MEDIUM_CONFIDENCE);
            case 'string':
            case 'text':
                $options['field_type'] = TextType::class;

                return new TypeGuess(StringFilter::class, $options, Guess::HIGH_CONFIDENCE);
            case 'time':
                return new TypeGuess(DateTimeFilter::class, $options, Guess::HIGH_CONFIDENCE);
            default:
                return new TypeGuess(StringFilter::class, $options, Guess::LOW_CONFIDENCE);
        }
    }
}
