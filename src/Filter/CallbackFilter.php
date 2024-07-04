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

use RuntimeException;
use Sonata\AdminBundle\Datagrid\ProxyQueryInterface;
use Sonata\AdminBundle\Filter\Model\FilterData;
use Sonata\AdminBundle\Form\Type\Filter\FilterDataType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

class CallbackFilter extends Filter
{
    public function filter(ProxyQueryInterface $query, string $field, FilterData $data): void
    {
        if (!\is_callable($this->getOption('callback'))) {
            throw new RuntimeException(
                sprintf('Please provide a valid callback option "filter" for field "%s"', $this->getName())
            );
        }

        $this->active = \call_user_func($this->getOption('callback'), $query, $field, $data);
    }

    public function getDefaultOptions(): array
    {
        return [
            'callback'         => null,
            'field_type'       => $this->getFieldType(),
            'operator_type'    => HiddenType::class,
            'operator_options' => [],
        ];
    }

    public function getRenderSettings(): array
    {
        return [FilterDataType::class, [
            'field_type'       => $this->getFieldType(),
            'field_options'    => $this->getFieldOptions(),
            'operator_type'    => $this->getOption('operator_type'),
            'operator_options' => $this->getOption('operator_options'),
            'label'            => $this->getLabel(),
        ]];
    }
}
