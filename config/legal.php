<?php

/**
 * Operator identity shown on the public legal pages.
 *
 * These must name the entity that actually receives the money — the gateways
 * check that the details here match the registered merchant account, and terms
 * published under a product name rather than a legal entity are not
 * enforceable. Fill these in before going live.
 */
return [

    'operator' => env('LEGAL_OPERATOR', 'Guaranteed Correct'),

    'address' => env('LEGAL_ADDRESS', ''),

    'jurisdiction' => env('LEGAL_JURISDICTION', 'the Federal Republic of Nigeria'),

    'support_email' => env('LEGAL_SUPPORT_EMAIL', 'support@guaranteedcorrectscoretips.com'),

    /** Shown as the "last updated" date; bump whenever the wording changes. */
    'updated_at' => env('LEGAL_UPDATED_AT', '13 August 2026'),

];
