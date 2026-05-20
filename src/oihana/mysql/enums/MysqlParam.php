<?php

namespace oihana\mysql\enums;

use oihana\reflect\traits\ConstantsTrait;

/**
 * The mysql specific parameters.
 */
class MysqlParam
{
    use ConstantsTrait ,
        MysqlParamTrait ;
}