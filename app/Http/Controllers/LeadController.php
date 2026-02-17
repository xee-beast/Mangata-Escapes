<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\GetFiles;
use App\Http\Requests\ConvertLeadProposalDocument;
use App\Http\Requests\SendSupplierEmail;
use App\Http\Requests\StoreLead;
use App\Http\Requests\SyncLeadOptions;
use App\Http\Requests\UpdateLead;
use App\Http\Requests\UpdateLeadHotelRequests;
use App\Http\Resources\LeadResource;
use App\Jobs\ProposalDocumentConversionJob;
use App\Models\Brand;
use App\Models\ContactedUsOption;
use App\Models\Lead;
use App\Models\LeadHotel;
use App\Models\LeadProvider;
use App\Models\Provider;
use App\Models\ReferralSourceOption;
use App\Models\TravelAgent;
use App\Notifications\DuplicateLead;
use App\Notifications\NewLeadFromForm;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

class LeadController extends Controller
{
    use GetFiles;

    public function __construct()
    {
        $this->authorizeResource(Lead::class, 'lead');
    }

    public function index(Request $request)
    {
        $leads = Lead::with('travelAgent');
        $travelAgent = $request->query('travelAgent', '');
        $status = $request->query('status', '');
        $search = $request->query('search', '');
        $isFit = $request->query('isFit', '');
        $isCanadian = $request->query('isCanadian', '');
        $active = $request->query('active');
        $weddingYear = $request->query('weddingYear', '');
        $contactedUsYear = $request->query('contactedUsYear', '');
        $departure = $request->query('departure', '');

        if (!$request->user()->hasPermissionTo('view all leads') && $request->user()->hasPermissionTo('view own leads') && $request->user()->travel_agent) {
            $leads->where('travel_agent_id', $request->user()->travel_agent->id);
        }

        if (!empty($travelAgent)) {
            $leads->whereHas('travelAgent', function ($query) use ($travelAgent) {
                $query->where('id', $travelAgent);
            });
        }

        if (!empty($status)) {
            $leads->where('status', $status);
        }

        if ($isFit !== null && $isFit !== '') {
            $leads->where('is_fit', $isFit);
        }

        if ($isCanadian !== null && $isCanadian !== '') {
            $leads->where('is_canadian', $isCanadian);
        }

        if ($active === 'true') {
            $leads->active();
        }

        if (!empty($weddingYear)) {
            $leads->whereYear('wedding_date', $weddingYear);
        }

        if (!empty($contactedUsYear)) {
            $leads->whereYear('contacted_us_date', $contactedUsYear);
        }

        if (!empty($departure)) {
            $leads->where('departure', $departure);
        }

        if (!empty($search)) {
            $leads->where(function ($query) use ($search) {
                $query->where('bride_first_name', 'like', '%' . $search . '%')
                    ->orWhere('bride_last_name', 'like', '%' . $search . '%')
                    ->orWhere('groom_first_name', 'like', '%' . $search . '%')
                    ->orWhere('groom_last_name', 'like', '%' . $search . '%')
                    ->orWhere('phone', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%');
            });
        }

        return LeadResource::collection($leads->paginate($request->query('paginate', 10)))
            ->additional([
                'can' => [
                    'create' => $request->user()->can('create', Lead::class),
                    'viewAllLeads' => $request->user()->hasPermissionTo('view all leads'),
                    'viewOwnLeads' => $request->user()->hasPermissionTo('view own leads'),
                ],
                'travelAgents' => TravelAgent::active()->select(
                    'travel_agents.id AS value',
                    DB::raw("CONCAT(travel_agents.first_name, ' ', travel_agents.last_name) AS text")
                )->get(),
                'weddingYears' => Lead::selectRaw('DISTINCT YEAR(wedding_date) as year')
                    ->whereNotNull('wedding_date')
                    ->when($active === 'true', fn($q) => $q->active())
                    ->orderBy('year', 'desc')
                    ->pluck('year')
                    ->map(function($year) {
                        return ['value' => $year, 'text' => $year];
                    }),
                'contactedUsYears' => Lead::selectRaw('DISTINCT YEAR(contacted_us_date) as year')
                    ->whereNotNull('contacted_us_date')
                    ->when($active === 'true', fn($q) => $q->active())
                    ->orderBy('year', 'desc')
                    ->pluck('year')
                    ->map(function($year) {
                        return ['value' => $year, 'text' => $year];
                    }),
                'referralSourceOptions' => ReferralSourceOption::select('option')->get(),
                'contactedUsOptions' => ContactedUsOption::select('option')->get(),
            ]);
    }

    public function store(StoreLead $request)
    {
        $lead = new Lead([
            'bride_first_name' => ucwords($request->input('brideFirstName')),
            'bride_last_name' => ucwords($request->input('brideLastName')),
            'groom_first_name' => ucwords($request->input('groomFirstName')),
            'groom_last_name' => ucwords($request->input('groomLastName')),
            'departure' => $request->input('departure'),
            'phone' => $request->input('phone'),
            'text_agreement' => $request->input('textAgreement') ?? false,
            'email' => $request->input('email'),
            'destinations' => $request->input('destinations'),
            'wedding_date' => $request->input('weddingDate'),
            'status' => 'Unassigned',
            'travel_agent_requested' => $request->input('travelAgentRequested'),
            'referral_source' => $request->input('referralSource'),
            'facebook_group' => $request->input('facebookGroup'),
            'referred_by' => $request->input('referredBy'),
            'message' => $request->input('message'),
            'contacted_us_by' => $request->input('contactedUsBy'),
            'contacted_us_date' => $request->input('contactedUsDate') ? $request->input('contactedUsDate') : now(),
        ]);

        $lead->save();

        return (new LeadResource($lead))->response()->setStatusCode(201);
    }

    public function show(Lead $lead)
    {
        return (new LeadResource($lead->loadMissing(['leadProviders.leadHotels.proposal_document', 'leadProviders.leadHotels.brand'])))->additional([
            'can' => [
                'updateAllLeads' => auth()->user()->hasPermissionTo('update all leads'),
                'updateOwnLeads' => auth()->user()->hasPermissionTo('update own leads'),
            ],
            'travelAgents' => TravelAgent::active()->select(
                    'travel_agents.id AS value',
                    DB::raw("CONCAT(travel_agents.first_name, ' ', travel_agents.last_name) AS text")
                )->get(),
            'providers' => Provider::select(
                    'providers.id',
                    'providers.email',
                    'providers.abbreviation',
                    'providers.id AS value',
                    'providers.name AS text'
                )->with('specialists', function($query) {
                    $query->select(
                        'specialists.id',
                        'specialists.provider_id',
                        'specialists.name',
                        'specialists.email',
                        'specialists.id AS value',
                        DB::raw("CONCAT(specialists.name, ' - ', specialists.email) AS text"),
                    );
                })->get(),
            'referralSourceOptions' => ReferralSourceOption::select(
                    'option AS value',
                    'option AS text',
                )->get(),
            'contactedUsOptions' => ContactedUsOption::select(
                    'option AS value',
                    'option AS text',
                )->get(),
            'brands' => Brand::select(
                    'id AS value',
                    'name AS text',
                )->orderBy('name', 'asc')->get(),
        ]);
    }

    public function update(UpdateLead $request, Lead $lead)
    {
        $status = ($request->input('status') === 'Unassigned' && $lead->status === 'Unassigned' && $request->input('travelAgentId')) ? 'Assigned' : $request->input('status');

        if ($request->user()->hasPermissionTo('update all leads')) {
            $travel_agent_id = $request->input('travelAgentId');
            $assigned_at = ($request->input('travelAgentId') && $request->input('travelAgentId') != $lead->travel_agent_id) ? now() : $lead->assigned_at;
        } else {
            $travel_agent_id = $lead->travel_agent_id;
            $assigned_at = $lead->assigned_at;
        }

        $lead->update([
            'is_fit' => $request->input('isFit'),
            'is_canadian' => $request->input('isCanadian'),
            'travel_agent_id' => $travel_agent_id,
            'assigned_at' => $assigned_at,
            'bride_first_name' => ucwords($request->input('brideFirstName')),
            'bride_last_name' => ucwords($request->input('brideLastName')),
            'groom_first_name' => ucwords($request->input('groomFirstName')),
            'groom_last_name' => ucwords($request->input('groomLastName')),
            'departure' => $request->input('departure'),
            'phone' => $request->input('phone'),
            'text_agreement' => $request->input('textAgreement') ?? false,
            'email' => $request->input('email'),
            'venue' => $request->input('venue'),
            'site' => $request->input('site'),
            'number_of_people' => $request->input('numberOfPeople'),
            'number_of_rooms' => $request->input('numberOfRooms'),
            'destinations' => $request->input('destinations'),
            'wedding_date' => $request->input('weddingDate'),
            'wedding_date_confirmed' => $request->input('weddingDateConfirmed'),
            'travel_start_date' => $request->input('travelStartDate'),
            'travel_end_date' => $request->input('travelEndDate'),
            'status' => $status,
            'travel_agent_requested' => $request->input('travelAgentRequested'),
            'referral_source' => $request->input('referralSource'),
            'facebook_group' => $request->input('facebookGroup'),
            'referred_by' => $request->input('referredBy'),
            'message' => $request->input('message'),
            'contract_sent_on' => $request->input('contractSentOn'),
            'last_attempt' => $request->input('lastAttempt'),
            'responded_on' => $request->input('respondedOn'),
            'release_rooms_by' => $request->input('releaseRoomsBy'),
            'balance_due_date' => $request->input('balanceDueDate'),
            'cancellation_date' => $request->input('cancellationDate'),
            'notes' => $request->input('notes'),
            'contacted_us_by' => $request->input('contactedUsBy'),
            'contacted_us_date' => $request->input('contactedUsDate'),
        ]);

        return new LeadResource($lead);
    }

    public function updateNotes(Request $request, Lead $lead)
    {
        $request->validate([
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $lead->update([
            'notes' => $request->input('notes'),
        ]);

        return response()->json(['notes' => $lead->notes], 200);
    }

    public function updateHotelRequests(UpdateLeadHotelRequests $request, Lead $lead)
    {
        $this->authorize('update', $lead);

        $providerIds = [];

        foreach ($request->input('leadProviders', []) as $providerData) {
            $leadProvider = isset($providerData['id'])
                ? LeadProvider::find($providerData['id'])
                : null;

            if ($leadProvider) {
                $leadProvider->update([
                    'provider_id' => $providerData['providerId'],
                    'id_at_provider' => $providerData['idAtProvider'] ?? null,
                    'specialist_id' => $providerData['specialistId'] ?? null,
                ]);
            } else {
                $leadProvider = $lead->leadProviders()->create([
                    'provider_id' => $providerData['providerId'],
                    'id_at_provider' => $providerData['idAtProvider'] ?? null,
                    'specialist_id' => $providerData['specialistId'] ?? null,
                ]);
            }

            $providerIds[] = $leadProvider->id;
            $hotelIds = [];

            foreach ($providerData['leadHotels'] ?? [] as $hotelData) {
                $leadHotel = isset($hotelData['id'])
                    ? LeadHotel::find($hotelData['id'])
                    : null;

                if ($leadHotel) {
                    $leadHotel->update([
                        'hotel' => $hotelData['hotel'],
                        'brand_id' => $hotelData['brandId'],
                        'requested_on' => $hotelData['requestedOn'],
                        'wedding_date' => $hotelData['weddingDate'] ?? null,
                        'travel_start_date' => $hotelData['travelStartDate'] ?? null,
                        'travel_end_date' => $hotelData['travelEndDate'] ?? null,
                        'received_on' => $hotelData['receivedOn'] ?? null,
                    ]);
                } else {
                    $leadHotel = $leadProvider->leadHotels()->create([
                        'hotel' => $hotelData['hotel'],
                        'brand_id' => $hotelData['brandId'],
                        'requested_on' => $hotelData['requestedOn'],
                        'wedding_date' => $hotelData['weddingDate'] ?? null,
                        'travel_start_date' => $hotelData['travelStartDate'] ?? null,
                        'travel_end_date' => $hotelData['travelEndDate'] ?? null,
                        'received_on' => $hotelData['receivedOn'] ?? null,
                    ]);
                }

                if (isset($hotelData['proposalDocument']) && is_array($hotelData['proposalDocument'])) {
                    $leadHotel->proposal_document()->associate($this->getFile($hotelData['proposalDocument'])->id);
                } else {
                    $leadHotel->proposal_document()->dissociate();
                }

                $leadHotel->save();

                $hotelIds[] = $leadHotel->id;
            }

            $leadProvider->leadHotels()
                ->whereNotIn('id', $hotelIds)
                ->get()
                ->each(function ($leadHotel) {
                    $leadHotel->delete();
                });
        }

        $lead->leadProviders()
            ->whereNotIn('id', $providerIds)
            ->get()
            ->each(function ($leadProvider) {
                $leadProvider->leadHotels()
                    ->get()
                    ->each(function ($leadHotel) {
                        $leadHotel->delete();
                    });

                $leadProvider->delete();
            });

        if ($lead->leadProviders()->whereHas('leadHotels')->exists()) {
            $pending = $lead->leadProviders()
                ->whereHas('leadHotels', function ($q) {
                        $q->whereNull('received_on');
                })
                ->exists();

            if (!$pending && in_array($lead->status, ['Assigned', 'Pending Rates'])) {
                $lead->update(['status' => 'Received Rates']);
            } elseif ($pending && in_array($lead->status, ['Assigned', 'Received Rates'])) {
                $lead->update(['status' => 'Pending Rates']);
            }
        } else {
            if ($lead->status === 'Received Rates' || $lead->status === 'Pending Rates') {
                $lead->update(['status' => 'Assigned']);
            }
        }

        return new LeadResource($lead->loadMissing(['leadProviders.leadHotels.proposal_document', 'leadProviders.leadHotels.brand']));
    }

    public function handleWebhook(Request $request)
    {
        $secret = config('services.tally.signing_secret');
        $receivedSignature = $request->header('Tally-Signature');
        $calculatedSignature = $secret ? base64_encode(hash_hmac('sha256', $request->getContent(), $secret, true)) : null;
        $valid = $secret && $receivedSignature && $calculatedSignature && hash_equals($calculatedSignature, $receivedSignature);

        if (!$valid) {
            return response()->json(['success' => false], 401);
        }

        $data = $request->json('data', []);
        $fields = $data['fields'] ?? [];
        $fieldMap = [];
        $normalize = fn($s) => strtolower(trim(preg_replace('/\s+/', ' ', (string) $s)));

        foreach ($fields as $field) {
            if (isset($field['label'])) {
                $fieldMap[$normalize($field['label'])] = $field;
            }
        }

        $get = fn($label, $default = null) => $fieldMap[$normalize($label)]['value'] ?? $default;

        $getOption = function ($label) use ($fieldMap, $normalize) {
            $field = $fieldMap[$normalize($label)] ?? null;

            if (!$field || !isset($field['options'])) {
                return null;
            }

            $value = is_array($field['value']) ? ($field['value'][0] ?? null) : $field['value'];
            foreach ($field['options'] as $opt) {
                if (($opt['id'] ?? null) === $value) {
                    return $opt['text'] ?? $value;
                }
            }

            return $value;
        };

        $brideFirst = ucwords(trim((string) $get("Wedding Couple's Names", '')));
        $brideLast = '';
        $groomFirst = '';
        $groomLast = '';
        $lastNameCount = 0;

        foreach ($fields as $field) {
            $label = strtolower(trim($field['label'] ?? ''));
            if ($label === 'last name') {
                $lastNameCount++;
                if ($lastNameCount === 1) {
                    $brideLast = ucwords(trim((string) ($field['value'] ?? '')));
                } elseif ($lastNameCount === 2) {
                    $groomLast = ucwords(trim((string) ($field['value'] ?? '')));
                }
            } elseif ($label === 'first name') {
                $groomFirst = ucwords(trim((string) ($field['value'] ?? '')));
            }
        }

        $phone = trim((string) $get('Best Contact Number', ''));
        $email = trim((string) $get('Email Address', ''));

        if (empty($email)) {
            $email = 'example_' . time() . '@gmail.com';
        }

        $weddingDate = null;
        $weddingDateConfirmed = false;
        $weddingDatePreference = null;
        $weddingDateAnswer = $getOption('Do you have a wedding date?');

        if ($weddingDateAnswer === 'I have a firm date.') {
            $weddingDateConfirmed = true;

        } elseif ($weddingDateAnswer === 'I have a date range.') {
            $weddingDateConfirmed = false;
            $weddingDatePreferenceExist = trim((string) $get('Please tell us your date preferences (ex, Spring 2027 or Saturdays in November 2026).', ''));
            
            if($weddingDatePreferenceExist) {
                $weddingDatePreference = 'Wedding Date Preferences: '. $weddingDatePreferenceExist;
            }
        }

        if ($get('Wedding Date')) {
            try {
                $weddingDate = Carbon::parse($get('Wedding Date'))->toDateString();
            } catch (\Exception $e) {
            }
        }

        $contactedUsDate = isset($data['createdAt']) ? Carbon::parse($data['createdAt'])->toDateString() : null;

        $referralSource = $getOption('How did you hear about us?');
        $facebookGroup = $referralSource === 'Facebook Group (please include which)' ? trim((string) $get('If you found us through a Facebook Group, which group?', '')) : null;
        $referredBy = null;

        if ($referralSource === 'Referral (please include who)') {
            $referredBy = trim((string) $get('If you were referred, please include the name of the person who referred you.', ''));
        } elseif ($referralSource === 'Other') {
            $referredBy = trim((string) $get('If you chose other, please let us know how you heard about us.', ''));
        }

        $destinations = [];
        $destinationField = $fieldMap[$normalize('Destination')] ?? $fieldMap[$normalize('Destination(s)')] ?? null;
        $otherDest = '';
        foreach ($fieldMap as $normalizedLabel => $field) {
            if (strpos($normalizedLabel, 'other locations') !== false || strpos($normalizedLabel, 'other location') !== false) {
                $value = $field['value'] ?? null;
                if ($value) {
                    $otherDest = trim((string) $value);
                    break;
                }
            }
        }

        if ($destinationField) {
            if (isset($destinationField['options']) && is_array($destinationField['value'])) {
                foreach ($destinationField['options'] as $opt) {
                    if (isset($opt['id']) && in_array($opt['id'], $destinationField['value'])) {
                        $text = trim($opt['text'] ?? $opt['id']);
                        if (strcasecmp($text, 'Other') === 0) {
                            if ($otherDest) {
                                $destinations[] = $otherDest;
                            }
                        } else {
                            $destinations[] = $text;
                        }
                    }
                }
            } elseif (!empty($destinationField['value'])) {
                $destinations[] = trim((string) $destinationField['value']);
            }
        }

        if ($otherDest && !in_array($otherDest, $destinations)) {
            $destinations[] = $otherDest;
        }

        $departure = $getOption('Where is your group departing from?');
        $textAgreement = $getOption('Please check here if you agree to receive communication regarding your inquiry via text message. (We will email you first!)') === 'Yes';
        $message = trim((string) ($get('Leave us a message', $get('Leave us a message!', ''))));

        $travelAgentRequested = null;
        $formName = $data['formName'] ?? '';
        if (preg_match('/—(.+)$/', $formName, $matches)) {
            $travelAgentRequested = trim($matches[1] ?? '');
        }

        $existingLead = Lead::where('email', $email)->first();

        if ($existingLead) {
            $submissionData = [
                'bride_first_name' => $brideFirst ?: null,
                'bride_last_name' => $brideLast ?: null,
                'groom_first_name' => $groomFirst ?: null,
                'groom_last_name' => $groomLast ?: null,
                'phone' => $phone ?: null,
                'email' => $email,
                'text_agreement' => $textAgreement,
                'travel_agent_requested' => $travelAgentRequested,
                'contacted_us_by' => 'Contact Us Form',
                'contacted_us_date' => $contactedUsDate,
                'departure' => $departure,
                'destinations' => count($destinations) ? implode(', ', $destinations) : null,
                'wedding_date' => $weddingDate,
                'wedding_date_confirmed' => $weddingDateConfirmed,
                'referral_source' => $referralSource,
                'facebook_group' => $facebookGroup,
                'referred_by' => $referredBy,
                'notes' => $weddingDatePreference,
                'message' => $message ?: null,
            ];

            Notification::route('mail', config('emails.admin'))->notify((new DuplicateLead($existingLead, $submissionData))->delay(now()->addSeconds(10)));

            return response()->json(['success' => false, 'error' => 'Email already exists'], 400);
        }

        $lead = Lead::create([
            'bride_first_name' => $brideFirst ?: null,
            'bride_last_name' => $brideLast ?: null,
            'groom_first_name' => $groomFirst ?: null,
            'groom_last_name' => $groomLast ?: null,
            'phone' => $phone ?: null,
            'text_agreement' => $textAgreement,
            'travel_agent_requested' => $travelAgentRequested,
            'email' => $email,
            'contacted_us_by' => 'Contact Us Form',
            'contacted_us_date' => $contactedUsDate,
            'destinations' => count($destinations) ? implode(', ', $destinations) : null,
            'departure' => $departure,
            'wedding_date' => $weddingDate,
            'wedding_date_confirmed' => $weddingDateConfirmed,
            'notes' => $weddingDatePreference,
            'status' => 'Unassigned',
            'referral_source' => $referralSource,
            'facebook_group' => $facebookGroup,
            'referred_by' => $referredBy,
            'message' => $message ?: null,
        ]);

        Notification::route('mail', config('emails.admin'))->notify((new NewLeadFromForm($lead))->delay(now()->addSeconds(10)));

        return response()->json(['success' => true, 'lead_id' => $lead->id]);
    }

    public function destroy(Lead $lead)
    {   
        $lead->delete();

        return response()->json()->setStatusCode(204);
    }

    public function syncLeadOptions(SyncLeadOptions $request)
    {
        if (!auth()->user()->hasPermissionTo('update all leads')) {
            abort(403, 'You do not have permission to sync lead options.');
        }

        DB::transaction(function () use ($request) {
            ReferralSourceOption::query()->delete();
            ContactedUsOption::query()->delete();

            ReferralSourceOption::insert($request->referralSourceOptions);
            ContactedUsOption::insert($request->contactedUsOptions);
        });

        return response()->json([
            'status'  => 'success',
            'message' => 'Lead options synced successfully.'
        ], 200);
    }

    public function sendSupplierEmail(SendSupplierEmail $request, Lead $lead)
    {
        $this->authorize('update', $lead);

        $lead->load('travelAgent');

        $data = $request->validated();
        $to = $data['email'];
        $form_cc = isset($data['cc']) ? array_map('trim', explode(',', $data['cc'])) : [];
        $default_cc = [config('emails.operations'), config('emails.admin')];

        if ($lead->travelAgent && $lead->travelAgent->email) {
            $default_cc[] = $lead->travelAgent->email;
        }

        $cc = array_unique(array_merge($form_cc, $default_cc));
        $subject = sprintf('New Lead: %s%s - Rate Request', $lead->name, (!empty($data['supplierIdentifier']) ? ' - ' . $data['supplierIdentifier'] : ''));
        $body = $data['body'];

        try {
            Mail::raw($body, function ($message) use ($to, $cc, $subject) {
                $message->from(config('emails.admin'), 'Barefoot Bridal')->to($to)->cc($cc)->subject($subject);
            });

            return response()->json([
                'success' => true,
                'message' => 'The email has been sent successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'errors' => [
                    'email' => ['Failed to send the email to the supplier.']
                ]
            ], 500);
        }
    }

    public function convertProposalDocument(Lead $lead, ConvertLeadProposalDocument $request)
    {
        $leadHotel = $request->all();

        try {
            ProposalDocumentConversionJob::dispatch($lead, $leadHotel);

            return response()->json()->setStatusCode(204);
        } catch (\Throwable $e) {
            return response()->json([
                'errors' => ['proposalDocument' => ['Failed to process the document.']]
            ], 500);
        }
    }
}
