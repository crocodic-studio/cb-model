<?php

namespace Crocodicstudio\Cbmodel\Core;


class CBModelTemporary
{
    private $data;

    public function set($repoClassName, $repoMethodName, $repoId, $data)
    {
        $this->data[$repoClassName][$repoMethodName][$repoId] = $data;
    }

    public function get($repoClassName, $repoMethodName, $repoId)
    {
        return @$this->data[$repoClassName][$repoMethodName][$repoId];
    }
}