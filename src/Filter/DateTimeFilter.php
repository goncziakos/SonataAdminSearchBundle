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

use Sonata\AdminBundle\Form\Type\Filter\DateTimeType;

class DateTimeFilter extends AbstractDateFilter
{
    protected bool $time = true;

    /**
     * {@inheritdoc}
     */
    protected function getFilterTypeClass()
    {
        return DateTimeType::class;
    }
}
