<?php

declare(strict_types=1);

namespace App\Http\Requests\Facilities;

use Illuminate\Foundation\Http\FormRequest;

class StoreFacilityBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('facilities.book');
    }

    public function rules(): array
    {
        return [
            'facility_id'  => ['required', 'integer', 'exists:facilities,id'],
            'booking_date' => ['required', 'date', 'after_or_equal:today'],
            'start_time'   => ['required', 'date_format:H:i'],
            'end_time'     => ['required', 'date_format:H:i', 'after:start_time'],
            'guests'       => ['nullable', 'integer', 'min:0'],
            'flat_id'      => ['nullable', 'integer', 'exists:flats,id'],
            'notes'        => ['nullable', 'string', 'max:1000'],
        ];
    }
}
