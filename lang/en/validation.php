<?php

declare(strict_types=1);

return [
    'tax_number' => [
        'format' => 'The :attribute field must be a valid Hungarian tax number, e.g. 12345678-1-23.',
        'checksum' => 'The :attribute field has an invalid check digit.',
        'vat_code' => 'The :attribute field has an invalid VAT code (9th digit).',
        'region' => 'The :attribute field has an invalid territorial code (last two digits).',
        'not_allowed' => 'The :attribute field has a VAT code that is not accepted here.',
    ],
    'eu_vat_number' => [
        'format' => 'The :attribute field must be a valid Hungarian EU VAT number, e.g. HU12345678.',
        'checksum' => 'The :attribute field has an invalid check digit.',
        'tax_number_mismatch' => 'The :attribute field does not match the given tax number.',
    ],
    'tax_id' => [
        'format' => 'The :attribute field must be a valid Hungarian tax identification number: 10 digits starting with 8.',
        'checksum' => 'The :attribute field has an invalid check digit.',
        'birth_date' => 'The :attribute field contains an invalid birth date.',
        'birth_date_mismatch' => 'The :attribute field does not match the given birth date.',
    ],
    'taj' => [
        'format' => 'The :attribute field must be a valid TAJ number of 9 digits.',
        'checksum' => 'The :attribute field has an invalid check digit.',
    ],
    'bank_account' => [
        'format' => 'The :attribute field must be a valid Hungarian bank account number.',
        'length' => 'The :attribute field has an invalid length.',
        'checksum' => 'The :attribute field has an invalid check digit.',
    ],
    'iban' => [
        'format' => 'The :attribute field must be a valid Hungarian IBAN: HU followed by 26 digits.',
        'checksum' => 'The :attribute field has an invalid check digit.',
    ],
    'company_number' => [
        'format' => 'The :attribute field must be a valid Hungarian company registration number, e.g. 01-09-123456.',
        'court' => 'The :attribute field has an invalid court of registration code (first two digits).',
        'company_form' => 'The :attribute field has an invalid company form code (3rd and 4th digits).',
        'not_allowed' => 'The :attribute field has a company form that is not accepted here.',
    ],
    'postal_code' => [
        'format' => 'The :attribute field must be a valid Hungarian postal code of 4 digits.',
        'range' => 'The :attribute field is not an existing Hungarian postal code.',
        'not_allowed' => 'The :attribute field must be a Budapest postal code.',
        'settlement_mismatch' => 'The :attribute field is not a postal code of the given settlement.',
    ],
];
