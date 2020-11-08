<?php

namespace crocodicstudio\cbmodel\Core;


class CBModelTemporary
{
    private $data;

    public function put($repoClassName, $repoMethodName, $repoId, $data)
    {
        $this->data[$repoClassName][$repoMethodName][$repoId] = $data;
    }

    public function get($repoClassName, $repoMethodName, $repoId)
    {
        return @$this->data[$repoClassName][$repoMethodName][$repoId];
    }
}