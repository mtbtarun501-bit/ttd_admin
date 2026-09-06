<?php

namespace App\Http\Controllers;

use App\Models\BookingType;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function pricing()
    {
        $bookingTypes = BookingType::all();
        return view('settings.pricing', compact('bookingTypes'));
    }

    public function updatePricing(Request $request)
    {
        $data = $request->validate([
            'prices' => 'required|array',
            'prices.*.id' => 'required|exists:booking_types,id',
            'prices.*.price' => 'required|numeric|min:0',
            'prices.*.commission_rate' => 'required|numeric|min:0',
        ]);

        foreach ($data['prices'] as $item) {
            $bookingType = BookingType::find($item['id']);
            if ($bookingType) {
                $bookingType->price = $item['price'];
                $bookingType->commission_rate = $item['commission_rate'];
                $bookingType->save();
            }
        }

        return redirect()->back()->with('success', 'Pricing settings updated successfully.');
    }
}
