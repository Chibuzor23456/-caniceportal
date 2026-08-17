<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Quotation;
use Illuminate\View\View;

class VerificationController extends Controller
{
    /**
     * Intentionally public (Section 11): confirmation data only, never
     * document content, so a signed agreement can be independently verified
     * without exposing anything sensitive.
     */
    public function __invoke(string $reference): View
    {
        $quotation = Quotation::query()
            ->where('reference', $reference)
            ->with('signature', 'client')
            ->firstOrFail();

        return view('quotations.verify', ['quotation' => $quotation]);
    }
}
