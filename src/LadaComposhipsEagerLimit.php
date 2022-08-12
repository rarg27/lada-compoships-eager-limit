<?php

namespace Rarg27\LadaComposhipsEagerLimit;

use Mpyw\ComposhipsEagerLimit\ComposhipsEagerLimit;
use Rarg27\LadaComposhipsEagerLimit\Database\Query\Builder as MixedBuilder;
use Spiritix\LadaCache\Database\LadaCacheTrait;

trait LadaComposhipsEagerLimit
{
    use ComposhipsEagerLimit,
        LadaCacheTrait;

    protected function newBaseQueryBuilder()
    {
        $connection = $this->getConnection();
        $grammar = $connection->withTablePrefix($this->getQueryGrammar($connection));

        return new MixedBuilder(
            $connection,
            $grammar,
            $connection->getPostProcessor(),
            app()->make('lada.handler'),
            $this
        );
    }
}