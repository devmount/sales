<?php

namespace App\Enums;

enum InvoiceStatus: string
{
    case RUNNING = 'r';
    case SENT = 's';
    case PAID = 'p';
}
