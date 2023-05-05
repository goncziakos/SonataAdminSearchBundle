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

namespace Sonata\AdminSearchBundle\Model;

use FOS\ElasticaBundle\Finder\PaginatedFinderInterface;
use Sonata\AdminBundle\Admin\AdminInterface;

interface FinderProviderInterface
{
    /**
     * @param AdminInterface $admin Sonata Admin interface
     *
     * @return PaginatedFinderInterface
     */
    public function getFinderByAdmin(AdminInterface $admin);

    /**
     * @param string $adminId Sonata Admin service id
     *
     * @return PaginatedFinderInterface
     */
    public function getFinderByAdminId($adminId);

    /**
     * @param AdminInterface $admin Sonata Admin interface
     *
     * @return string
     */
    public function getFinderIdByAdmin(AdminInterface $admin);

    /**
     * @param string $adminId Sonata Admin service id
     *
     * @return string
     */
    public function getFinderIdByAdminId($adminId);

    /**
     * @param AdminInterface $admin Sonata Admin interface
     *
     * @return array
     */
    public function getActionsByAdmin(AdminInterface $admin);

    /**
     * @param string $adminId Sonata Admin service id
     *
     * @return array
     */
    public function getActionsByAdminId($adminId);
}
