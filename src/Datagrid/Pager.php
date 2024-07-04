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

namespace Sonata\AdminSearchBundle\Datagrid;

use FOS\ElasticaBundle\Paginator\TransformedPaginatorAdapter;
use Sonata\AdminBundle\Datagrid\Pager as BasePager;

class Pager extends BasePager
{
    private ?TransformedPaginatorAdapter $paginator = null;

    /**
     * {@inheritdoc}
     */
    public function init(): void
    {
        $query = $this->getQuery();
        $query->setFirstResult(null);
        $query->setMaxResults($this->getMaxPerPage());

        if ($this->getPage() === 0 || $this->getMaxPerPage() === 0) {
            $this->setLastPage(0);
        } elseif ($this->countResults() === 0) {
            $this->setLastPage(1);
        } else {
            $offset = ($this->getPage() - 1) * $this->getMaxPerPage();
            $query->setFirstResult($offset);
            $query->setMaxResults($this->getMaxPerPage());
            $this->setLastPage((int) ceil($this->countResults() / $this->getMaxPerPage()));
        }
    }

    public function getCurrentPageResults(): iterable
    {
        return $this->getPaginator()->getResults(
            $this->getQuery()->getFirstResult(),
            $this->getQuery()->getMaxResults()
        )->toArray();
    }

    public function countResults(): int
    {
        return $this->getPaginator()->getTotalHits();
    }

    protected function getPaginator(): TransformedPaginatorAdapter
    {
        if ($this->paginator === null) {
            $this->paginator = $this->getQuery()->execute();
        }

        return $this->paginator;
    }
}
