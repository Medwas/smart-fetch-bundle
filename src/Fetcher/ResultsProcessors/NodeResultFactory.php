<?php

namespace Verclam\SmartFetchBundle\Fetcher\ResultsProcessors;

use Doctrine\ORM\QueryBuilder;

class NodeResultFactory
{

    /**
     * @param array{
     *     queryBuilder: QueryBuilder,
     *     result: array|object
     * } $data
     * @return NodeResult
     */
    public function create(array $data): NodeResult
    {
        $result = new NodeResult();

        foreach ($data as $key => $value) {
            $setter = 'set' . ucfirst($key);
            $result->$setter($value);
        }

        return $result;
    }

}