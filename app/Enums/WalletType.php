<?php

namespace App\Enums;

enum WalletType: string
{
    case SAVINGS = 'savings';
    case CASH = 'cash';
    case CREDIT_CARD = 'credit card';
    case DEBIT_CARD = 'debit card';
    case BANK_ACCOUNT = 'bank account';
    case INVESTMENT = 'investment';
    case OTHER = 'other';
}
