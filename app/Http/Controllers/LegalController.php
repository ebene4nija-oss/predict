<?php

namespace App\Http\Controllers;

/**
 * Terms, privacy and refund policy.
 *
 * Both payment gateways require all three to be publicly reachable before a
 * live subscription merchant account is approved, and the doc's compliance
 * section needs somewhere for the 18+ and "predictions, not guarantees" terms
 * to live in full rather than as a footer line.
 *
 * Operator details come from config so the pages carry a real legal entity
 * rather than the product name — see `config/legal.php`.
 */
class LegalController extends Controller
{
    public function terms()
    {
        return view('legal.terms', $this->operator());
    }

    public function privacy()
    {
        return view('legal.privacy', $this->operator());
    }

    public function refunds()
    {
        return view('legal.refunds', $this->operator());
    }

    /**
     * @return array<string, mixed>
     */
    protected function operator(): array
    {
        return [
            'operator' => config('legal.operator'),
            'jurisdiction' => config('legal.jurisdiction'),
            'supportEmail' => config('legal.support_email'),
            'address' => config('legal.address'),
            'updatedAt' => config('legal.updated_at'),
        ];
    }
}
