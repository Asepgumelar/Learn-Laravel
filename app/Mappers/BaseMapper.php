<?php


namespace App\Mappers;


abstract class BaseMapper
{
    abstract function list($items);

    abstract function single($item);
}