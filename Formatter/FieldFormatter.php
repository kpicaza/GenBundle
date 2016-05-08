<?php

namespace Kpicaza\GenBundle\Formatter;

use Doctrine\ORM\Mapping\ClassMetadataInfo;

/**
 * Class FieldFormatter.
 */
class FieldFormatter
{
    /**
     * @param $mappings
     * @param $associationMappings
     *
     * @return mixed
     */
    public function formatMappingsWithRelations($mappings, $associationMappings)
    {
        $formattedMappings = $this->formatMappings($mappings);

        return $this->formatRelationMappings($formattedMappings, $associationMappings);
    }

    /**
     * @param $mappings
     *
     * @return mixed
     */
    public function formatMappings($mappings)
    {
        $types = $this->getTypes();

        foreach ($mappings as $key => $mapping) {
            $mappings[$key] = $types[$mappings[$key]['type']];
        }

        return $mappings;
    }

    /**
     * @param $mappings
     * @param $associationMappings
     *
     * @return mixed
     */
    protected function formatRelationMappings($mappings, $associationMappings)
    {
        $types = $this->getTypes();

        foreach ($associationMappings as $fieldName => $relation) {
            if ($relation['type'] !== ClassMetadataInfo::ONE_TO_MANY) {
                $mappings[sprintf('%s_%s', $fieldName, 'id')] = $types['string'];
            }
        }

        return $mappings;
    }

    /**
     * @return array
     */
    protected function getTypes()
    {
        return array(
            'string' => 'Type\\TextType::class',
            'text' => 'Type\\TextareaType::class',
            'datetime' => 'Type\\DateTimeType::class',
            'integer' => 'Type\\NumberType::class',
            'boolean' => 'Type\\NumberType::class',
        );
    }
}
