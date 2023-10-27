<?php

    namespace Verclam\SmartFetchBundle\Fetcher\Condition;

    use Verclam\SmartFetchBundle\Fetcher\Condition\Attributes\Condition;

    class ConditionFactory
    {
        public const FILTER_BY = 'filterBy';
        public function generate(array $options): Condition
        {
            return match($options['type']) {
                self::FILTER_BY => $this->generateFilterBy($options),
                default => throw new \Exception('Unknown type')
            };
        }

        private function generateFilterBy(array $options): Condition
        {
            $options['value'] ??= null;
            $options['options'] ??= [];

            return new Condition(
                property: $options['property'],
                operator: $options['operator'],
                options: $options['options'],
                value: $options['value']
            );
        }

    }