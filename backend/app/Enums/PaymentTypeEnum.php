<?php

namespace App\Enums;

enum PaymentTypeEnum:string
{
    case VCARD = 'VCARD';
    case MBWAY = 'MBWAY';
    CASE PAYPAL = 'PAYPAL';
    CASE IBAN = 'IBAN';
    CASE MB = 'MB';
    CASE VISA = 'VISA';
}
