<?php

namespace App\Services;

use App\Notifications\ProposalDocumentMail;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

class ProposalDocumentConversionService
{
		protected $client;

    public function __construct()
    {
        $this->client = new Client();
    }

    public function convertProposalDocument($lead, $leadHotel)
    {
				$prompt = '
You are an expert at reading hotel supplier rate proposals.
Each proposal belongs to a specific supplier and hotel.

If the supplier is CLASSIC VACATIONS, follow these instructions.

-------------------------------------------------------
INSTRUCTIONS FOR CLASSIC VACATIONS PROPOSALS START HERE
-------------------------------------------------------
Extract the rate information from the uploaded document using the following JSON schema.

{
		"type": "cv",
		"rates": [
				{
						"rates_valid": "October 1, 2025 - October 5, 2025",
						"header": ["Room Category", "Single", "Double"],
						"body": [
								["Deluxe Pool View Suite", "$100.00", "$80.00"]
						]
				}
		],
		"proposal_valid_until": "January 10, 2025",
		"min_nights": "3",
		"concessions": []
}

Follow these instructions:
1. The "type" will be "cv" which is the abbreviation for classic vacations.
2. There can be multiple tables in the document if rates are different for different date ranges. Make sure each table is extracted as a separate object in the "rates" array.
3. The "rates_valid" range is written above each table. It should be extracted as a string in the following format, "October 1, 2025 - October 5, 2025".
4. If there is only one table in the document that means that the rates are valid for the whole travel date range mentioned on the top of the first page of the document.
5. The "header" array must include the "Room Category" label, followed by all occupancy types in the exact order as they are written in the table header in the document. Occupancy type should be in full forms and not abbreviations, i.e., "Single" instead of "SGL" etc.
6. The "body" array has one sub-array per room, matching the order of the occupancy types in the header array. The first value is the room category, followed by the rates for each occupancy type.
		- If rate >= 0, format of the rate should be the same for each such rate and it should be upto two decimal places like "100.00".
		- If any rate is not available (na, NA, n/a, N/A etc. or any value that does not specify a rate value), write "N/A" for that rate in the room array.
7. The valid until date is usually written right below where the tables end. The "proposal_valid_until" date will be two days less than this date that is mentioned in the document.
8. If a valid until date is not mentioned in the document, look at the top of the first page of the document. You will find a prepared on date. Add 10 days to this date to get the "proposal_valid_until" date.
9. The "proposal_valid_until" date must exactly be in the following format - "January 10, 2025".
10. Sometimes there is also a "minimum length of stay" mentioned in the document, usually written right below where the tables end.
		- If the minimum length of stay is explicitly mentioned, write it in the "min_nights" field as a number like "4".
		- If it is not mentioned, set the "min_nights" to "3".
11. Here is how to extract the "concessions" array:
		- In the document, there is a section for "CONCESSIONS" and a section for "Classic Exclusive" offers. We will consider both these sections for extracting concessions and also use the word concessions for both concessions and offers.
		- I am also providing a list of concessions in the exact wording that I want below.
		- The document might have extra concessions that are not in the list that I am providing or it might have the concessions that I want to extract but the wording might not be correct. The document might also be missing any number of concessions that are present in the list that I am providing.
		- You must extract only those concessions that are present in both the document and the list that I am providing.
		- The extracted concessions must be in the exact wording that I am providing and not follow the wording in the document.
		- Some extracted concessions will be static and will not have any placeholder like "[x]". They will be written as is.
		- Some extracted concessions will have "[x]" or a similar placeholder. These concessions depend on one or more conditions like the travel dates and seasons etc., and these conditions are also mentioned along with such concessions in the document. You must replace the placeholder of such concessions with a value based on the conditions in the document.
		- Here is the concessions list and wording: ' . $leadHotel['brand']['concessions'] . '
		- An extracted concession must be a string element in the "concessions" array.
		- If any extracted concession has sub points in the wording that I provided, both the concession and those sub points must be a single sub-array inside the "concessions" array.
		- Keep the order of the "concessions" elements as provided in the list above and do not follow the order of concessions in the document.
12. Do NOT include comments, explanation text, trailing commas or any additional fields beyond the above schema in the returned JSON.
-----------------------------------------------------
INSTRUCTIONS FOR CLASSIC VACATIONS PROPOSALS END HERE
-----------------------------------------------------

If the supplier is TRAVEL IMPRESSIONS, follow these instructions.

--------------------------------------------------------
INSTRUCTIONS FOR TRAVEL IMPRESSIONS PROPOSALS START HERE
--------------------------------------------------------
Extract the rate information from the uploaded document using the following JSON schema.

{
		"type": "ti",
		"rooms": [
				{
						"room_category": "Deluxe Pool View Suite",
						"rates": {
								"Single": {
										"October 28, 2025": "$100",
										"October 29, 2025": "$100",
										"October 30, 2025": "$100"
								},
								"Double": {
										"October 28, 2025": "$50",
										"October 29, 2025": "$50",
										"October 30, 2025": "$50"
								}
						}
				}
		],
		"proposal_valid_until": "January 10, 2025",
		"min_nights": "3",
		"concessions": []
}

Follow these instructions:
1. The "type" will be "ti" which is the abbreviation for travel impressions.
2. There are multiple tables in the document - one for each room. Each table will have per night per person rates for the same occupancy types and the same dates.
3. The room name/category will be written on the top of each table along with the hotel name and the package start date.
4. Extract "rooms" array based on the following instructions:
		- Extract each table as an object inside "rooms" array.
		- "room_category" will have the room name.
		- Then extract each row (occupancy type) as an object inside the "rates" object with the occupancy type name as that object\'s key. Occupancy type should be in full forms and not abbreviations, i.e., "Single" instead of "SGL" etc. Similar occupancy types across rooms should be the same string inside each "rates" object.
		- Each occupancy type object will have a key value pair for each date as a key and the rate for that date as its value. The format for date will be "October 25, 2025". The format of the rates should be the same for each rate and it should be upto two decimal places like "100.00".
5. There is a date written below the page number on each page. This is the prepared on date for the proposal. Add 10 days to this date to get the "proposal_valid_until" date. The "proposal_valid_until" date must exactly be in the following format - "January 10, 2025".
6. Sometimes, "x Night Minimum Length of Stay Required" is mentioned on the first page, usually written right above where the rate tables start.
		- If the minimum length of stay is explicitly mentioned, write it in the "min_nights" field as a number like "4".
		- If it is not mentioned, set the "min_nights" to "3".
7. Here is how to extract the "concessions" array:
		- In the document, there is a section for "Group Hotel Amenities" and a section for "Group Booking Promotions". We will consider both of these sections for extracting concessions and also use the word concessions for both amenities and promotions.
		- I am also providing a list of concessions in the exact wording that I want below.
		- The document might have extra concessions that are not in the list that I am providing or it might have the concessions that I want to extract but the wording might not be correct. The document might also be missing any number of concessions that are present in the list that I am providing.
		- You must extract only those concessions that are present in both the document and the list that I am providing.
		- The extracted concessions must be in the exact wording that I am providing and not follow the wording in the document.
		- Some extracted concessions will be static and will not have any placeholder like "[x]". They will be written as is.
		- Some extracted concessions will have "[x]" or a similar placeholder. These concessions depend on one or more conditions like the travel dates and seasons etc., and these conditions are also mentioned along with such concessions in the document. You must replace the placeholder of such concessions with a value based on the conditions in the document.
		- Here is the concessions list and wording: ' . $leadHotel['brand']['concessions'] . '
		- An extracted concession must be a string element in the "concessions" array.
		- If any extracted concession has sub points in the wording that I provided, both the concession and those sub points must be a single sub-array inside the "concessions" array.
		- Keep the order of the "concessions" elements as provided in the list above and do not follow the order of concessions in the document.
8. Do NOT include comments, explanation text, trailing commas or any additional fields beyond the above schema in the returned JSON.
------------------------------------------------------
INSTRUCTIONS FOR TRAVEL IMPRESSIONS PROPOSALS END HERE
------------------------------------------------------

If the supplier is ENVOYAGE, follow these instructions.

----------------------------------------------
INSTRUCTIONS FOR ENVOYAGE PROPOSALS START HERE
----------------------------------------------
Extract the rates information from the uploaded document using the following JSON schema.

{
		"type": "envoyage",
		"travel_dates": "Oct 1, 2025 - Oct 5, 2025",
		"rooms": [
				{
						"room_category": "Deluxe Pool View Suite",
						"Single Rate": {
								"4 Night Package": "400.00",
								"Plus 1 Pre-night": "100.00",
								"Plus 2 Pre-nights": "200.00",
								"Plus 3 Pre-nights": "300.00",
								"Plus 1 Post-night": "100.00",
								"Plus 2 Post-nights": "200.00",
								"Plus 3 Post-nights": "300.00"
						},
						"Double Rate": {
								"4 Night Package": "200.00",
								"Plus 1 Pre-night": "50.00",
								"Plus 2 Pre-nights": "100.00",
								"Plus 3 Pre-nights": "150.00",
								"Plus 1 Post-night": "50.00",
								"Plus 2 Post-nights": "100.00",
								"Plus 3 Post-nights": "150.00"
						}
				}
		],
		"proposal_valid_until": "January 10, 2025"
}

Follow these instructions:
1. The type will be envoyage.
2. The travel date range will be at the top of the page. It should be extracted as a string in the following format - "Oct 1, 2025 - Oct 5, 2025".
3. There will be multiple tables in the document, one for each room. Make sure each table is extracted as a separate object in the "rooms" array. 
4. The room category is written on top of each table. Extract it as a string.
5. There will be multiple occupancy types for each room. Extract each occupancy type as a separate key inside the room object. Each occupancy type will be a separate object inside the room object.
6. Each occupancy type object will have rate key value pairs. The keys for these pairs will be the package types and their values will the corresponding rates of those package types for that occupancy.
7. How to extract proposal valid until date:
		- The date on which the proposal was sent is written at the very top of the first page of the document. 
		- On the first page the validity will be written as, "Quote is valid for x days.....".
		- Add x days to the proposal sent date to get the proposal valid until date.
		- Extract the proposal valid until date from the document exactly in the following format - "January 10, 2025".
8. Do NOT include comments, explanation text, trailing commas or any additional fields beyond the above schema in the returned JSON.
--------------------------------------------
INSTRUCTIONS FOR ENVOYAGE PROPOSALS END HERE
--------------------------------------------
';

        $signedUrl = Storage::disk('s3')->temporaryUrl($leadHotel['proposalDocument']['path'], now()->addMinutes(5));

				$response = $this->client->post('https://api.openai.com/v1/responses', [
						'headers' => [
								'Authorization' => 'Bearer ' . config('services.openai.api_key'),
								'Content-Type' => 'application/json',
						],
						'json' => [
								'model' => 'gpt-5.1',
								'input' => [
										[
												'role' => 'user',
												'content' => [
														[
																'type' => 'input_text',
																'text' => $prompt
														],
														[
																'type' => 'input_file',
																'file_url' => $signedUrl
														]
												]
										]
								],
								'instructions' => "You are an expert at reading hotel supplier rate proposal documents. Read the uploaded PDF document and provide structured JSON output according to the schema specified in the prompt. Make sure to extract information based on the conditions in the prompt.",
								'temperature' => config('services.openai.temperature', 0.2),
								'max_output_tokens' => config('services.openai.max_output_tokens', 1000),
						]
				]);

				$response = json_decode((string) $response->getBody(), true);

				$json_object = $response['output'][0]['content'][0]['text'] ?? null;

				if (!$json_object) {
						throw new \Exception('No response from OpenAI API');
				}

				$converted_object = $this->convertJsonObject(json_decode($json_object, true), $lead, $leadHotel);
				$converted_object->travel_agent = $lead->travelAgent ? $lead->travelAgent->email : null;

				Notification::route('mail', config('emails.operations'))->notify(new ProposalDocumentMail($converted_object));
    }

		public function convertJsonObject($json_object, $lead, $leadHotel)
		{
				if ($json_object['type'] === 'cv') {
						$converted_object = $this->convertCvJsonObject($json_object, $lead, $leadHotel);
				} else if ($json_object['type'] === 'ti') {
						$converted_object = $this->convertTiJsonObject($json_object, $lead, $leadHotel);
				} else if ($json_object['type'] === 'envoyage') {
						$converted_object = $this->convertEnvoyageJsonObject($json_object, $lead, $leadHotel);
				} else {
						throw new \Exception('Invalid Object Type');
				}

				return $converted_object;
		}

		public function convertCvJsonObject($json_object, $lead, $leadHotel)
		{
				$converted_object = new \stdClass();
				$converted_object->type = $json_object['type'];
				$converted_object->name = ($lead->bride_last_name && $lead->groom_last_name) ? $lead->bride_last_name . ' & ' . $lead->groom_last_name : $lead->name;
				$converted_object->wedding_date = Carbon::parse($leadHotel['weddingDate'])->format('F j, Y');
				$converted_object->resort = $leadHotel['hotel'];
				$converted_object->travel_dates = Carbon::parse($leadHotel['travelStartDate'])->format('F j, Y') . ' - ' . Carbon::parse($leadHotel['travelEndDate'])->format('F j, Y');

				foreach ($json_object['rates'] as $rate) {
						$rate_object = new \stdClass();
						$rate_object->rates_valid = $rate['rates_valid'];
						$rate_object->header = $rate['header'];

						foreach ($rate['body'] as $row_index => $row) {
								foreach ($row as $column_index => $rate_value) {
										if ($column_index == 0 || $rate_value == 'N/A') {
												continue;
										}

										$rate_value = str_replace(['$', ','], '', $rate_value);

										if (!is_numeric($rate_value)) {
												$rate['body'][$row_index][$column_index] = 'N/A';
												continue;
										}

										$rate_value = (float) $rate_value;

										if ($rate_value > 0) {
												$rate_value = round($rate_value) + 2;
										}

										$rate['body'][$row_index][$column_index] = '$' . $rate_value;
								}
						}

						$rate_object->body = $rate['body'];

						$converted_object->rates[] = $rate_object;
				}

				$converted_object->proposal_valid_until = $json_object['proposal_valid_until'];
				$converted_object->min_nights = $json_object['min_nights'];
				$converted_object->concessions = $json_object['concessions'];

				return $converted_object;
		}

		public function convertTiJsonObject($json_object, $lead, $leadHotel)
		{
				$converted_object = new \stdClass();
				$converted_object->type = $json_object['type'];
				$converted_object->name = ($lead->bride_last_name && $lead->groom_last_name) ? $lead->bride_last_name . ' & ' . $lead->groom_last_name : $lead->name;
				$converted_object->wedding_date = Carbon::parse($leadHotel['weddingDate'])->format('F j, Y');
				$converted_object->resort = $leadHotel['hotel'];
				$converted_object->travel_dates = Carbon::parse($leadHotel['travelStartDate'])->format('F j, Y') . ' - ' . Carbon::parse($leadHotel['travelEndDate'])->format('F j, Y');

				foreach ($json_object['rooms'] as &$room) {
						foreach ($room['rates'] as $occupancy => $rate) {
								$merged = [];
								$prevRate = null;
								$rangeStart = null;
								$rangeEnd = null;

								foreach ($rate as $date => $value) {
										if ($prevRate === null) {
												$rangeStart = $rangeEnd = $date;
												$prevRate = $value;
										} elseif ($value === $prevRate) {
												$rangeEnd = $date;
										} else {
												$key = "$rangeStart - $rangeEnd";
												$merged[$key] = $prevRate;
												$rangeStart = $rangeEnd = $date;
												$prevRate = $value;
										}
								}

								if ($rangeStart !== null) {
										$key = "$rangeStart - $rangeEnd";
										$merged[$key] = $prevRate;
								}

								$room['rates'][$occupancy] = $merged;
						}
				}

				unset($room);
				$boundaries = [];

				foreach ($json_object['rooms'] as $room) {
						foreach ($room['rates'] as $occRanges) {
								foreach ($occRanges as $range => $_) {
										[$start, $end] = array_map('trim', explode('-', $range, 2));
										$boundaries[] = Carbon::parse($start)->startOfDay();
										$boundaries[] = Carbon::parse($end)->endOfDay()->addDay();
								}
						}
				}

				$boundaries = collect($boundaries)->sort()->unique(fn($d) => $d->toDateString())->values();
				$canonicalRanges = [];

				for ($i = 0; $i < $boundaries->count() - 1; $i++) {
						$start = $boundaries[$i];
						$end = $boundaries[$i + 1]->copy()->subDay();

						if ($start->lte($end)) {
								$canonicalRanges[] = [
										'start' => $start,
										'end' => $end,
										'key' => $start->format('F j, Y') . ' - ' . $end->format('F j, Y'),
								];
						}
				}

				$normalizedRooms = [];

				foreach ($json_object['rooms'] as $room) {
						$normalized = [
								'room_category' => $room['room_category'],
								'rates' => [],
						];

						foreach ($room['rates'] as $occ => $occRanges) {
								foreach ($canonicalRanges as $canon) {
										foreach ($occRanges as $range => $value) {
												[$start, $end] = array_map('trim', explode('-', $range, 2));
												$rangeStart = Carbon::parse($start)->startOfDay();
												$rangeEnd = Carbon::parse($end)->endOfDay();

												if ($rangeStart->lte($canon['start']) && $rangeEnd->gte($canon['end'])) {
														$normalized['rates'][$occ][$canon['key']] = $value;
														break;
												}
										}
								}
						}

						$normalizedRooms[] = $normalized;
				}

				$sorted_rooms = $json_object['rooms'];

				usort($sorted_rooms, function($a, $b) {
						$countA = count($a['rates']);
						$countB = count($b['rates']);
						return $countB <=> $countA;
				});

				$allOccupancies = collect($sorted_rooms)	
						->flatMap(fn($r) => array_keys($r['rates']))
						->unique()
						->values()
						->toArray();

				$tables = [];

				foreach ($canonicalRanges as $canon) {
						$tables[] = [
								'rates_valid' => $canon['key'],
								'header' => array_merge(['Room Category'], $allOccupancies),
								'body' => [],
						];
				}

				foreach ($tables as &$table) {
						foreach ($normalizedRooms as $room) {
								$row = [$room['room_category']];

								foreach ($allOccupancies as $occ) {
										$row[] = $room['rates'][$occ][$table['rates_valid']] ?? 'N/A';
								}

								$table['body'][] = $row;
						}
				}

				unset($table);
				$converted_object->rates = [];

				foreach ($tables as $table) {
						$rate_object = new \stdClass();
						[$startDateStr, $endDateStr] = array_map('trim', explode('-', $table['rates_valid'], 2));
						$endDate = Carbon::parse($endDateStr)->addDay();
						$rate_object->rates_valid = $startDateStr . ' - ' . $endDate->format('F j, Y');
						$rate_object->header = $table['header'];
						$rate_object->body = [];

						foreach ($table['body'] as $row) {
								$newRow = [$row[0]];

								foreach ($row as $column_index => $rate_value) {
										if ($column_index === 0) continue;

										if ($rate_value === 'N/A') {
												$newRow[] = 'N/A';
												continue;
										}

										$rate_value = str_replace(['$', ','], '', $rate_value);

										if (!is_numeric($rate_value)) {
												$newRow[] = 'N/A';
												continue;
										}

										$rate_value = (float) $rate_value;

										if ($rate_value > 0) $rate_value = round($rate_value) + 2;

										$newRow[] = '$' . $rate_value;
								}

								$rate_object->body[] = $newRow;
						}

						$converted_object->rates[] = $rate_object;
				}

				$converted_object->proposal_valid_until = $json_object['proposal_valid_until'];
				$converted_object->min_nights = $json_object['min_nights'];
				$converted_object->concessions = $json_object['concessions'];

				return $converted_object;
		}

		public function convertEnvoyageJsonObject($json_object, $lead, $leadHotel)
		{
				$converted_object = new \stdClass();

				return $converted_object;
		}
}
