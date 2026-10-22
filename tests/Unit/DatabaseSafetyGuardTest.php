<?php

namespace Tests\Unit;

use Tests\TestCase;

/**
 * Verifies the test-database safety guard (Tests\TestCase::setUp).
 *
 * This test extends Tests\TestCase (so the guard runs) but does NOT use
 * RefreshDatabase and performs NO destructive DB operation. If the test
 * environment is correctly isolated to in-memory SQLite, setUp() passes and
 * this assertion confirms it. If the environment were pointed at a real
 * MySQL database, the guard would throw in setUp() BEFORE any migration,
 * proving destructive tests cannot reach the development database.
 */
class DatabaseSafetyGuardTest extends TestCase
{
    public function test_tests_run_only_against_in_memory_sqlite(): void
    {
        $connection = config('database.default');
        $this->assertSame('sqlite', config("database.connections.{$connection}.driver"));
        $this->assertSame(':memory:', config("database.connections.{$connection}.database"));
    }
}
