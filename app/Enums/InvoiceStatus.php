<?php

namespace App\Enums;

enum InvoiceStatus: string
{
    case INVALID = 'i';
    case RUNNING = 'r';
    case SENT = 's';
    case PAID = 'p';
}
