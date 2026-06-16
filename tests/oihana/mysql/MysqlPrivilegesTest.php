<?php

namespace tests\oihana\mysql;

use PHPUnit\Framework\TestCase;

use oihana\mysql\enums\MysqlPrivileges;

/**
 * Covers {@see \oihana\mysql\enums\MysqlPrivileges} description helpers.
 */
class MysqlPrivilegesTest extends TestCase
{
    public function testAllDescriptionsReturnsTheMap(): void
    {
        $descriptions = MysqlPrivileges::allDescriptions() ;

        $this->assertNotEmpty( $descriptions ) ;
        $this->assertArrayHasKey( MysqlPrivileges::USAGE , $descriptions ) ;
    }

    public function testDescribeReturnsDescriptionForKnownPrivilege(): void
    {
        $this->assertSame(
            MysqlPrivileges::allDescriptions()[ MysqlPrivileges::USAGE ] ,
            MysqlPrivileges::describe( MysqlPrivileges::USAGE )
        ) ;
    }

    public function testDescribeReturnsNullForUnknownPrivilege(): void
    {
        $this->assertNull( MysqlPrivileges::describe( 'NOT_A_REAL_PRIVILEGE' ) ) ;
    }
}
