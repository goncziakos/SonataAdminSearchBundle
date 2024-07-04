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

use Sonata\AdminBundle\Form\Type\Filter\DateTimeRangeType;

class DateTimeRangeFilter extends AbstractDateFilter implements RangeFilterInterface
{
    protected bool $time = true;

    protected bool $range = true;

    protected function getFilterTypeClass(): string
    {
        return DateTimeRangeType::class;
    }
}
