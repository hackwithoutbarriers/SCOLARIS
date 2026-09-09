<?php

namespace App\Database\Schema\Grammars;

use Illuminate\Database\Schema\Grammars\PostgresGrammar;

class NeonPostgresGrammar extends PostgresGrammar
{
    public function supportsSchemaTransactions(): bool
    {
        return false;
    }
}
