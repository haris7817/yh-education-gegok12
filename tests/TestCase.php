<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Safety guard: abort before any test (and before RefreshDatabase's
     * migrate:fresh) if the test database is NOT an isolated SQLite connection.
     *
     * Without this, a misconfigured test run could point the default MySQL
     * connection at the live development database and wipe it. The guard runs
     * before parent::setUp() so RefreshDatabase can never execute migrate:fresh
     * against a real MySQL/PostgreSQL database.
     */
    protected function setUp(): void
    {
        // Boot the application first so config() resolves, but BEFORE parent::setUp()
        // runs the trait hooks (e.g. RefreshDatabase's migrate:fresh).
        $this->refreshApplication();

        $connection = config('database.default');
        $driver     = config("database.connections.{$connection}.driver");
        $database   = config("database.connections.{$connection}.database");

        $isInMemorySqlite = $driver === 'sqlite'
            && in_array($database, [':memory:', null, ''], true);

        if (! $isInMemorySqlite) {
            // Abort BEFORE parent::setUp() (which runs RefreshDatabase's migrate:fresh),
            // so a misconfigured test environment can never wipe the real MySQL database.
            $this->markTestSkipped(
                "DB safety guard: test DB is '{$driver}'/'{$database}', not in-memory SQLite. "
                . "Skipping to avoid wiping real data. Fix DB_CONNECTION=sqlite / DB_DATABASE=:memory: "
                . "for the testing environment, then re-run."
            );
        }

        parent::setUp();
    }
}
