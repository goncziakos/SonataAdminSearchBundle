<?php

declare(strict_types=1);

namespace Sonata\AdminSearchBundle\Filter;

use Sonata\AdminBundle\Filter\Model\FilterData;
use Sonata\AdminBundle\Form\Type\Operator\StringOperatorType;
use Sonata\AdminBundle\Search\SearchableFilterInterface;
use Sonata\AdminSearchBundle\Datagrid\ProxyQueryInterface;

class StringFilter extends Filter implements SearchableFilterInterface
{
    public const TRIM_NONE = 0;
    public const TRIM_LEFT = 1;
    public const TRIM_RIGHT = 2;
    public const TRIM_BOTH = self::TRIM_LEFT | self::TRIM_RIGHT;

    public const CHOICES = [
        StringOperatorType::TYPE_CONTAINS => ['must', 'match'],
        StringOperatorType::TYPE_NOT_CONTAINS => ['must_not', 'match'],
        StringOperatorType::TYPE_EQUAL => ['must', 'match_phrase'],
    ];

    private const MEANINGLESS_TYPES = [
        StringOperatorType::TYPE_CONTAINS,
        StringOperatorType::TYPE_NOT_CONTAINS,
        StringOperatorType::TYPE_EQUAL
    ];

    public function filter(ProxyQueryInterface $query, string $field, FilterData $data): void
    {
        if (!$data->hasValue()) {
            return;
        }

        $value = $this->trim((string)($data->getValue() ?? ''));
        $type = $data->getType() ?? StringOperatorType::TYPE_CONTAINS;

        $allowEmpty = $this->getOption('allow_empty', false);
        \assert(\is_bool($allowEmpty));

        // ignore empty value if it doesn't make sense
        if ('' === $value && (!$allowEmpty || \in_array($type, self::MEANINGLESS_TYPES, true))) {
            return;
        }

        [$firstOperator, $secondOperator] = $this->getOperators($type);

        $values = [
            'query' => str_replace(['\\', '"'], ['\\\\', '\"'], $value),
            'operator' => 'and',
        ];
        $queryBuilder = $query->getQueryBuilder();
        $matchQuery = $secondOperator === 'match_phrase' ?
            $queryBuilder->query()->match_phrase($field, $values) :
            $queryBuilder->query()->match($field, $values);

        if ($firstOperator === 'must') {
            $query->addMust($matchQuery);
            return;
        }
        $query->addMustNot($matchQuery);
    }

    public function isSearchEnabled(): bool
    {
        return $this->getOption('global_search');
    }

    public function getDefaultOptions(): array
    {
        return [
            'force_case_insensitivity' => false,
            'trim' => self::TRIM_BOTH,
            'allow_empty' => false,
            'global_search' => true,
        ];
    }

    public function getFormOptions(): array
    {
        return [
            'field_type' => $this->getFieldType(),
            'field_options' => $this->getFieldOptions(),
            'label' => $this->getLabel(),
            'operator_type' => StringOperatorType::class,
        ];
    }

    private function getOperators($type): ?array
    {
        if (!isset(self::CHOICES[$type])) {
            throw new \OutOfRangeException(sprintf(
                'The type "%s" is not supported, allowed one are "%s".',
                $type,
                implode('", "', array_keys(self::CHOICES))
            ));
        }

        return self::CHOICES[$type];
    }

    private function trim(string $string): string
    {
        $trimMode = $this->getOption('trim', self::TRIM_BOTH);

        if ($trimMode === self::TRIM_LEFT) {
            return ltrim($string);
        }

        if ($trimMode === self::TRIM_RIGHT) {
            return rtrim($string);
        }

        if ($trimMode === self::TRIM_BOTH) {
            return trim($string);
        }

        return $string;
    }
}
