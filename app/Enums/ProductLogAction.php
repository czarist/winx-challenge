<?php

namespace App\Enums;

enum ProductLogAction: string
{
    case Created = 'created';
    case Updated = 'updated';
    case Deleted = 'deleted';
}
