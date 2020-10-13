<?php


namespace App\Contracts;


interface Mapper
{
    public function single($item);

    public function list($items);
}