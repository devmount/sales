<?php

namespace App\Enums;

enum DocumentType: string
{
    case INVOICE = 'invoice';
    case QUOTE = 'quote';
}
