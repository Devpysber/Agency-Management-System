<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Estimate;
use App\Models\Quotation;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class DocumentController extends Controller
{
    /**
     * The company the current client login is scoped to.
     * EnsureClientScope already keeps non-client roles out of client.* routes,
     * but we still scope every query by company_id so one client can never pull
     * another company's document by guessing an id.
     */
    private function companyId(): ?int
    {
        return auth()->user()?->contact?->company_id;
    }

    public function estimate(Request $request, $id)
    {
        $companyId = $this->companyId();
        abort_unless($companyId, 403);

        $estimate = Estimate::with('items')
            ->where('company_id', $companyId)
            ->findOrFail($id);

        $pdf = Pdf::loadView('pdf.estimate', ['estimate' => $estimate])
            ->setPaper('a4');

        return $pdf->download($estimate->estimate_number . '.pdf');
    }

    public function quotation(Request $request, $id)
    {
        $companyId = $this->companyId();
        abort_unless($companyId, 403);

        $quotation = Quotation::where('company_id', $companyId)->findOrFail($id);

        $ref = 'Quotation-' . $quotation->id;

        $pdf = Pdf::loadView('pdf.quotation', ['quotation' => $quotation])
            ->setPaper('a4');

        return $pdf->download($ref . '.pdf');
    }
}
