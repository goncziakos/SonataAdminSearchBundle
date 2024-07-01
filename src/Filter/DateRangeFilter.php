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

use Sonata\AdminBundle\Form\Type\Filter\DateRangeType;

class DateRangeFilter extends AbstractDateFilter implements RangeFilterInterface
{
    /**
     * {@inheritdoc}
     */
    protected function getFilterTypeClass()
    {
        return DateRangeType::class;
    }
}
