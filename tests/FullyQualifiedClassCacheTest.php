<?php
namespace Consolidation\AnnotatedCommand;

use Consolidation\AnnotatedCommand\Parser\Internal\FullyQualifiedClassCache;
use PHPUnit\Framework\TestCase;

class FullyQualifiedClassCacheTest extends TestCase
{
    function testFqcn()
    {
        $reflectionMethod = new \ReflectionMethod('\Consolidation\TestUtils\alpha\AlphaCommandFile', 'exampleTableTwo');
        $filename = $reflectionMethod->getFileName();

        $fqcnCache = new FullyQualifiedClassCache();

        $handle = fopen($filename, "r");
        $this->assertTrue($handle !== false);

        $namespaceName = $this->callProtected($fqcnCache, 'readNamespace', [$handle]);

        $this->assertEquals('Consolidation\TestUtils\alpha', $namespaceName);

        $usedClasses = $this->callProtected($fqcnCache, 'readUseStatements', [$handle]);

        $this->assertTrue(isset($usedClasses['RowsOfFields']));
        $this->assertEquals('Consolidation\OutputFormatters\StructuredData\RowsOfFields', $usedClasses['RowsOfFields']);

        fclose($handle);

        $fqcn = $fqcnCache->qualify($filename, 'RowsOfFields');
        $this->assertEquals('Consolidation\OutputFormatters\StructuredData\RowsOfFields', $fqcn);

        $fqcn = $fqcnCache->qualify($filename, 'ClassWithoutUse');
        $this->assertEquals('ClassWithoutUse', $fqcn);

        $fqcn = $fqcnCache->qualify($filename, 'ExampleAliasedClass');
        $this->assertEquals('Consolidation\TestUtils\ExampleAliasedClass', $fqcn);
    }

    function callProtected($object, $method, $args = [])
    {
        $r = new \ReflectionMethod($object, $method);
        $r->setAccessible(true);
        return $r->invokeArgs($object, $args);
    }
}
