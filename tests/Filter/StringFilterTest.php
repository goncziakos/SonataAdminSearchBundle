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

namespace Sonata\AdminSearchBundle\Tests\Filter;

use FOS\ElasticaBundle\Finder\TransformedFinder;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Sonata\AdminSearchBundle\Filter\StringFilter;
use Sonata\AdminSearchBundle\ProxyQuery\ElasticaProxyQuery;

class StringFilterTest extends TestCase
{
    /**
     * @var ElasticaProxyQuery
     */
    protected $proxyQuery;

    protected function setUp(): void
    {
        $finder = $this->createMock(TransformedFinder::class);

        $this->proxyQuery = new ElasticaProxyQuery($finder);
    }

    public function testFilterSimple(): void
    {
        $filter = new StringFilter();
        $value = 'bar';

        $filter->filter($this->proxyQuery, 'filter', 'foo', ['value' => $value]);

        $queryReflection = new ReflectionClass($this->proxyQuery);
        $queryProperty = $queryReflection->getProperty('query');

        $queryProperty->setAccessible(true);

        $queryArray = $queryProperty->getValue($this->proxyQuery)->toArray();

        static::assertSame($value, $queryArray['query']['bool']['must'][0]['match']['foo']['query']);
    }

    /**
     * Check if filter query with special characters can be translated into JSON.
     */
    public function testFilterSpecialCharacters(): void
    {
        $filter = new StringFilter();
        $value = 'bar \ + - && || ! ( ) { } [ ] ^ " ~ * ? : baz';

        $filter->filter($this->proxyQuery, 'filter', 'foo', ['value' => $value]);

        $queryReflection = new ReflectionClass($this->proxyQuery);
        $queryProperty = $queryReflection->getProperty('query');

        $queryProperty->setAccessible(true);

        $queryArray = $queryProperty->getValue($this->proxyQuery)->toArray();

        static::assertSame(str_replace(['\\', '"'], ['\\\\', '\"'], $value), $queryArray['query']['bool']['must'][0]['match']['foo']['query']);
    }
}
