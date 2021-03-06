<?php

class SchemaTest extends PHPUnit_Framework_TestCase
{
    public function testSchemaFinder()
    {
        $finder = new LazyRecord\Schema\SchemaFinder;
        $finder->in( 'tests' );
        $finder->find();
        $schemas = $finder->getSchemas();
        ok( is_array($schemas) );

        foreach( $schemas as $schema ) {
            ok($schema);
        }
    }
}

