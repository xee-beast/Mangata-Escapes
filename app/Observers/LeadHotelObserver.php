<?php

namespace App\Observers;

use App\Models\File;
use App\Models\LeadHotel;

class LeadHotelObserver
{
    public function updated(LeadHotel $leadHotel)
    {
        $oldProposalDocument = $leadHotel->getOriginal('proposal_document_id');

        if ($leadHotel->wasChanged('proposal_document_id') && !is_null($oldProposalDocument)) {
            File::find($oldProposalDocument)->delete();
        }
    }

    public function deleted(LeadHotel $leadHotel)
    {
        if ($leadHotel->proposal_document()->exists()) {
            $leadHotel->proposal_document->delete();
        }
    }
}
