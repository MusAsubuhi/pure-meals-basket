<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->restartPostgresSequences();
    }

    /**
     * Several tests assume ids and numbering start at 1 in every test, which is
     * how SQLite behaves when a test's transaction is rolled back. Postgres does
     * not roll sequences back, so restart them to get the same starting point.
     */
    private function restartPostgresSequences(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        $sequences = DB::select(
            'select schemaname, sequencename from pg_sequences where schemaname = current_schema()'
        );

        foreach ($sequences as $sequence) {
            DB::statement(
                'alter sequence "'.$sequence->schemaname.'"."'.$sequence->sequencename.'" restart with 1'
            );
        }
    }
}
