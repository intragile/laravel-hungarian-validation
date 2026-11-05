<?php

declare(strict_types=1);

return [
    'tax_number' => [
        'format' => 'A(z) :attribute nem érvényes adószám. A helyes formátum: 12345678-1-23.',
        'checksum' => 'A(z) :attribute ellenőrzőszáma hibás.',
        'vat_code' => 'A(z) :attribute áfakódja (9. számjegy) érvénytelen.',
        'region' => 'A(z) :attribute területi kódja (utolsó két számjegy) érvénytelen.',
        'not_allowed' => 'A(z) :attribute áfakódja itt nem elfogadható.',
    ],
    'eu_vat_number' => [
        'format' => 'A(z) :attribute nem érvényes közösségi adószám. A helyes formátum: HU12345678.',
        'checksum' => 'A(z) :attribute ellenőrzőszáma hibás.',
        'tax_number_mismatch' => 'A(z) :attribute nem egyezik a megadott adószámmal.',
    ],
    'tax_id' => [
        'format' => 'A(z) :attribute nem érvényes adóazonosító jel. Az adóazonosító jel 10 számjegyű, és 8-cal kezdődik.',
        'checksum' => 'A(z) :attribute ellenőrzőszáma hibás.',
        'birth_date' => 'A(z) :attribute érvénytelen születési dátumot tartalmaz.',
        'birth_date_mismatch' => 'A(z) :attribute nem egyezik a megadott születési dátummal.',
    ],
    'taj' => [
        'format' => 'A(z) :attribute nem érvényes TAJ-szám. A TAJ-szám 9 számjegyű.',
        'checksum' => 'A(z) :attribute ellenőrzőszáma hibás.',
    ],
    'bank_account' => [
        'format' => 'A(z) :attribute nem érvényes bankszámlaszám.',
        'length' => 'A(z) :attribute hossza nem megfelelő.',
        'checksum' => 'A(z) :attribute ellenőrzőszáma hibás.',
    ],
    'iban' => [
        'format' => 'A(z) :attribute nem érvényes magyar IBAN. A helyes formátum: HU és 26 számjegy.',
        'checksum' => 'A(z) :attribute ellenőrzőszáma hibás.',
    ],
    'company_number' => [
        'format' => 'A(z) :attribute nem érvényes cégjegyzékszám. A helyes formátum: 01-09-123456.',
        'court' => 'A(z) :attribute cégbírósági kódja (első két számjegy) érvénytelen.',
        'company_form' => 'A(z) :attribute cégformakódja (3-4. számjegy) érvénytelen.',
        'not_allowed' => 'A(z) :attribute cégformája itt nem elfogadható.',
    ],
    'postal_code' => [
        'format' => 'A(z) :attribute nem érvényes irányítószám. Az irányítószám 4 számjegyű.',
        'range' => 'A(z) :attribute nem létező irányítószám.',
        'not_allowed' => 'A(z) :attribute nem budapesti irányítószám.',
    ],
];
