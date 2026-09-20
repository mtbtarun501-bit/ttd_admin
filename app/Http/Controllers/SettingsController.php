<?php

namespace App\Http\Controllers;

use App\Models\BookingType;
use App\Models\SevaType;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function pricing()
    {
        $bookingTypes = BookingType::all();
        $sevas = SevaType::orderBy('display_order')->get();
        return view('settings.pricing', compact('bookingTypes', 'sevas'));
    }

    public function updatePricing(Request $request)
    {
        $data = $request->validate([
            'prices' => 'required|array',
            'prices.*.id' => 'required|exists:booking_types,id',
            'prices.*.price' => 'required|numeric|min:0',
            'prices.*.in_partner_commission_rate' => 'required|numeric|min:0',
            'prices.*.out_partner_commission_rate' => 'required|numeric|min:0',
            'prices.*.seva_type_id' => 'nullable|exists:seva_types,id',
        ]);

        foreach ($data['prices'] as $item) {
            $bookingType = BookingType::find($item['id']);
            if ($bookingType) {
                $bookingType->price = $item['price'];
                $bookingType->in_partner_commission_rate = $item['in_partner_commission_rate'];
                $bookingType->out_partner_commission_rate = $item['out_partner_commission_rate'];
                $bookingType->seva_type_id = $item['seva_type_id'] ?: null;
                $bookingType->save();
            }
        }

        return redirect()->back()->with('success', 'Pricing settings updated successfully.');
    }
}
