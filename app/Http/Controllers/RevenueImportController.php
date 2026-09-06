<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\RevenueDataImport;
use App\Models\RevenueImport;
use App\Models\RevenueImportBooking;
use App\Models\RevenueImportMember;
use App\Models\Revenue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RevenueImportController extends Controller
{
    public function index()
    {
        return view('revenues.import');
    }

    public function preview(Request $request)
    {
        $request->validate([
            'excel_file' => 'required|file|mimes:xlsx,xls,csv',
            'batch_month' => 'required|string',
        ]);

        $file = $request->file('excel_file');
        $fileName = $file->getClientOriginalName();
        $batchMonth = $request->input('batch_month');

        $duplicateWarning = RevenueImport::where('batch_month', $batchMonth)->exists();

        $sheets = Excel::toCollection(new RevenueDataImport, $file);
        $rows = $sheets->first();

        $bookings = [];
        $errors = [];

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2; // Assuming row 1 is header

            // Filter out completely empty rows (including rows with only whitespace)
            $isEmptyRow = true;
            foreach ($row as $val) {
                if (!empty(trim($val))) {
                    $isEmptyRow = false;
                    break;
                }
            }
            if ($isEmptyRow) {
                continue;
            }

            // Detect columns flexibly
            $agent = $row['agent'] ?? $row['agent_name'] ?? null;
            $service = $row['service'] ?? $row['service_name'] ?? null;
            $bookingRef = $row['booking'] ?? $row['booking_reference'] ?? $row['reference'] ?? null;
            $memberName = $row['member_name'] ?? $row['member'] ?? $row['devotee'] ?? null;
            
            // Sanitize currency fields (remove ₹, commas, spaces)
            $rawTicketCost = $row['ticket_cost'] ?? 0;
            $rawServiceCharge = $row['service_charge'] ?? $row['revenue'] ?? 0;
            
            $ticketCost = preg_replace('/[^\d.]/', '', (string) $rawTicketCost);
            $serviceCharge = preg_replace('/[^\d.]/', '', (string) $rawServiceCharge);
            
            $ticketCost = $ticketCost === '' ? 0 : (float) $ticketCost;
            $serviceCharge = $serviceCharge === '' ? 0 : (float) $serviceCharge;

            // If the row lacks BOTH an Agent and a Service, it is a summary/TOTAL row or garbage row. Skip it entirely.
            if (empty($agent) && empty($service)) {
                continue;
            }

            if (empty($agent)) $errors[] = "Row $rowNumber: Agent is missing.";
            if (empty($service)) $errors[] = "Row $rowNumber: Service is missing.";
            if ($ticketCost < 0) $errors[] = "Row $rowNumber: Ticket Cost is invalid.";
            if ($serviceCharge < 0) $errors[] = "Row $rowNumber: Service Charge is invalid.";

            // Use booking ref if available, else fallback to agent+service unique string for grouping
            $groupKey = $bookingRef ? "ref_" . $bookingRef : "group_" . Str::slug($agent . '_' . $service);

            if (!isset($bookings[$groupKey])) {
                $bookings[$groupKey] = [
                    'agent_name' => $agent,
                    'booking_reference' => $bookingRef,
                    'service_name' => $service,
                    'ticket_cost' => (float) $ticketCost,
                    'service_charge' => (float) $serviceCharge,
                    'members' => [],
                ];
            } else {
                // If this row has a different ticket cost / service charge, we might take max or keep first. 
                // We'll trust the first row's financials or take the max if it was 0 initially.
                if ($bookings[$groupKey]['service_charge'] == 0 && $serviceCharge > 0) {
                    $bookings[$groupKey]['service_charge'] = (float) $serviceCharge;
                }
                if ($bookings[$groupKey]['ticket_cost'] == 0 && $ticketCost > 0) {
                    $bookings[$groupKey]['ticket_cost'] = (float) $ticketCost;
                }
            }

            if ($memberName) {
                // Handle comma separated members in a single cell
                $names = explode(',', $memberName);
                foreach ($names as $name) {
                    $name = trim($name);
                    if ($name) {
                        $bookings[$groupKey]['members'][] = $name;
                    }
                }
            }
        }

        $totalBookings = count($bookings);
        $totalMembers = 0;
        $totalTicketCost = 0;
        $totalRevenue = 0;

        foreach ($bookings as $key => $booking) {
            // Ensure at least 1 member if members array is empty
            $memberCount = count($booking['members']);
            if ($memberCount == 0) {
                $memberCount = 1;
            }
            $bookings[$key]['member_count'] = $memberCount;
            
            $totalMembers += $memberCount;
            $totalTicketCost += $booking['ticket_cost'];
            $totalRevenue += $booking['service_charge'];
        }

        // Store in session for confirmation
        $importData = [
            'batch_month' => $batchMonth,
            'file_name' => $fileName,
            'total_bookings' => $totalBookings,
            'total_members' => $totalMembers,
            'total_ticket_cost' => $totalTicketCost,
            'total_revenue' => $totalRevenue,
            'bookings' => $bookings
        ];
        
        $request->session()->put('revenue_import_data', $importData);

        return view('revenues.preview', compact('importData', 'errors', 'duplicateWarning'));
    }

    public function confirm(Request $request)
    {
        $importData = $request->session()->get('revenue_import_data');
        if (!$importData) {
            return redirect()->route('revenues.import')->withErrors('Session expired or no import data found.');
        }

        try {
            DB::beginTransaction();

            $revenueImport = RevenueImport::create([
                'batch_month' => $importData['batch_month'],
                'file_name' => $importData['file_name'],
                'total_bookings' => $importData['total_bookings'],
                'total_members' => $importData['total_members'],
                'total_ticket_cost' => $importData['total_ticket_cost'],
                'total_revenue' => $importData['total_revenue'],
                'imported_by' => auth()->id() ?? 1,
            ]);

            foreach ($importData['bookings'] as $b) {
                $booking = RevenueImportBooking::create([
                    'revenue_import_id' => $revenueImport->id,
                    'agent_name' => $b['agent_name'],
                    'booking_reference' => $b['booking_reference'],
                    'service_name' => $b['service_name'],
                    'ticket_cost' => $b['ticket_cost'],
                    'service_charge' => $b['service_charge'],
                    'member_count' => $b['member_count'],
                ]);

                foreach ($b['members'] as $memberName) {
                    RevenueImportMember::create([
                        'revenue_import_booking_id' => $booking->id,
                        'member_name' => $memberName,
                    ]);
                }

                // Create the actual revenue record
                Revenue::create([
                    'agent_name' => $b['agent_name'],
                    'source' => $b['agent_name'] . ' - ' . $b['service_name'] . ($b['booking_reference'] ? ' ('.$b['booking_reference'].')' : ''),
                    'amount' => $b['service_charge'],
                    'revenue_date' => now()->toDateString(), // or parse from excel if available, keeping simple as today or batch month start
                    'remarks' => "Imported via Excel Batch " . $importData['batch_month'] . ". Total Members: " . $b['member_count'],
                    'created_by' => auth()->id() ?? 1,
                    'revenue_import_booking_id' => $booking->id,
                ]);
            }

            DB::commit();
            $request->session()->forget('revenue_import_data');

            return redirect()->route('revenues.index')->with('success', 'Excel imported successfully. ' . $importData['total_bookings'] . ' bookings recorded.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('revenues.import')->withErrors('Import failed: ' . $e->getMessage());
        }
    }
}
