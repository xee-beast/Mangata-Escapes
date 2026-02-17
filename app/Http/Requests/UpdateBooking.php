<?php

namespace App\Http\Requests;

use App\Models\Room;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBooking extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'roomArrangements' => ['required', 'array', 'min:1'],
            'roomArrangements.*.hotel' => ['required', 'integer', Rule::exists('hotel_blocks', 'id')->where('group_id', $this->group->id)],
            'roomArrangements.*.room' => ['required', 'integer', Rule::exists('room_blocks', 'id')->where('hotel_block_id', $this->input('roomArrangements.*.hotel'))],
            'roomArrangements.*.bed' => [
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    $index = explode('.', $attribute)[1];
                    $roomId = $this->input("roomArrangements.$index.room");

                    $beds = (\App\Models\Room::whereHas('room_blocks', function ($query) use ($roomId) {
                        $query->where('id', $roomId);
                    })->first() ?? (object)['beds' => []])->beds;

                    if (!in_array($value, $beds)) {
                        $fail("The selected bed is invalid for room ID $roomId.");
                    }
                }
            ],
            'roomArrangements.*.dates' => ['required', 'array'],
            'roomArrangements.*.dates.start' => ['required', 'date', 'before:roomArrangements.*.dates.end'],
            'roomArrangements.*.dates.end' => ['required', 'date', 'after:roomArrangements.*.dates.start'],
            'deposit' => 'nullable|numeric|min:0',
            'depositType' => ['nullable', Rule::in(['fixed', 'percentage'])],
            'bookingId' => 'nullable|string|max:50',
            'specialRequests' => 'nullable|string',
            'notes' => 'nullable|string',
            'isBgCouple' => 'nullable|boolean',
        ];
    }
}
