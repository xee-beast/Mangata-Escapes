<?php

namespace App\Jobs;

use App\Services\ProposalDocumentConversionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProposalDocumentConversionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $lead;
    protected $leadHotel;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($lead, $leadHotel)
    {
        $this->lead = $lead;
        $this->leadHotel = $leadHotel;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(ProposalDocumentConversionService $proposalDocumentConversionService)
    {
        $proposalDocumentConversionService->convertProposalDocument($this->lead, $this->leadHotel);
    }
}
