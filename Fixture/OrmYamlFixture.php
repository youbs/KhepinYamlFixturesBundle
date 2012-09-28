<?php

namespace Khepin\YamlFixturesBundle\Fixture;

use Doctrine\Common\Util\Inflector;

class OrmYamlFixture extends AbstractFixture {

    public function createObject($class, $data, $metadata, $options = array()){
        $mapping = array_keys($metadata->fieldMappings);
        $associations = array_keys($metadata->associationMappings);

        // Instantiate new object
        $constructorArgs = isset($options['constructor_args']) ? $options['constructor_args'] : array();
        $rc = new \ReflectionClass($class);
        $object = $rc->newInstanceArgs($constructorArgs);

        foreach ($data as $field => $value) {
            // Add the fields defined in the fistures file
            $method = Inflector::camelize('set_' . $field);
            //
            if (in_array($field, $mapping)) {
                // Dates need to be converted to DateTime objects
                $type = $metadata->fieldMappings[$field]['type'];
                if ($type == 'datetime' OR $type == 'date') {
                    $value = new \DateTime($value);
                }
                $object->$method($value);
            } else if (in_array($field, $associations)) { // This field is an association
                if (is_array($value)) { // The field is an array of associations
                    $referenceArray = array();
                    foreach ($value as $referenceObject) {
                        $referenceArray[] = $this->loader->getReference($referenceObject);
                    }
                    $object->$method($referenceArray);
                } else {
                    $object->$method($this->loader->getReference($value));
                }
            } else {
                // It's a method call that will set a field named differently
                // eg: FOSUserBundle ->setPlainPassword sets the password after
                // Encrypting it
                $object->$method($value);
            }
        }
        $this->runServiceCalls($object);

        return $object;
    }
}