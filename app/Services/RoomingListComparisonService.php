<?php

namespace App\Services;

use App\Models\Group;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Carbon\Carbon;

class RoomingListComparisonService
{
    /**
     * Compare uploaded rooming list with system data
     *
     * @param Group $group
     * @param string $filePath
     * @return array
     */
    public function compareRoomingList(Group $group, $filePath)
    {
        // Load the spreadsheet
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        
        // Parse spreadsheet data
        $spreadsheetBookings = $this->parseSpreadsheet($sheet);
        
        // Load system bookings
        $systemBookings = $this->loadSystemBookings($group);
        
        // Compare and find discrepancies
        $discrepancies = $this->findDiscrepancies($systemBookings, $spreadsheetBookings);
        
        // Build report
        return $this->buildReport($systemBookings, $spreadsheetBookings, $discrepancies);
    }

    /**
     * Parse spreadsheet into structured data
     *
     * @param \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet
     * @return array
     */
    protected function parseSpreadsheet($sheet)
    {
        $bookings = [];
        $currentBookingNumber = null;
        
        $highestRow = $sheet->getHighestRow();
        
        // Detect spreadsheet format and find data start row
        $dataStartRow = $this->findDataStartRow($sheet);
        
        // Start parsing from data row
        for ($row = $dataStartRow; $row <= $highestRow; $row++) {
            $bookingNumber = $sheet->getCell('A' . $row)->getValue();
            $guestName = $sheet->getCell('B' . $row)->getValue();
            $roomName = $sheet->getCell('C' . $row)->getValue();
            $bed = $sheet->getCell('D' . $row)->getValue();
            $colE = $sheet->getCell('E' . $row)->getValue();
            $colF = $sheet->getCell('F' . $row)->getValue();
            $colG = $sheet->getCell('G' . $row)->getValue();
            $colH = $sheet->getCell('H' . $row)->getValue();
            $insurance = $sheet->getCell('I' . $row)->getValue();
            $transfers = $sheet->getCell('J' . $row)->getValue();
            $total = $sheet->getCell('M' . $row)->getValue();
            $payments = $sheet->getCell('N' . $row)->getValue();
            $balance = $sheet->getCell('O' . $row)->getValue();
            
            // Skip empty rows
            if (!$bookingNumber && !$guestName) {
                continue;
            }
            
            // Skip cancelled bookings (strikethrough formatting in exported files)
            // Check columns A and B for strikethrough formatting
            $bookingCellFont = $sheet->getStyle('A' . $row)->getFont();
            $guestCellFont = $sheet->getStyle('B' . $row)->getFont();
            
            if ($bookingCellFont->getStrikethrough() || $guestCellFont->getStrikethrough()) {
                // This is a cancelled booking, skip it and reset current booking
                $currentBookingNumber = null;
                continue;
            }
            
            // Detect format and parse accordingly
            // Supplier format: Col3=NumGuests, Col4=RoomCategory, Col5=Arrival, Col6=Departure, Col7=Nights, Col8=Bedding
            // System format: Col3=RoomName, Col4=Bed, Col5=TravelDates, Col6=Occupancy, Col7=Nights, Col8=PerNight
            $isSupplierFormat = is_numeric($roomName) && $this->isDate($colE);
            
            if ($isSupplierFormat) {
                // Supplier format parsing
                $numGuests = $roomName;
                $roomName = $bed;
                $arrival = $this->parseDate($colE);
                $departure = $this->parseDate($colF);
                $travelDates = $arrival && $departure ? "$arrival - $departure" : '';
                $nights = $colG;
                $bed = $colH;
                $occupancy = null;
                $perNight = null;
            } else {
                // System export format parsing
                $travelDates = $colE;
                $occupancy = $colF;
                $nights = $colG;
                $perNight = $colH;
            }
            
            // New booking when we see a number in column A
            if (is_numeric($bookingNumber) && intval($bookingNumber) > 0 && $bookingNumber !== $currentBookingNumber) {
                $currentBookingNumber = intval($bookingNumber);
                $bookings[$currentBookingNumber] = [
                    'bookingNumber' => $currentBookingNumber,
                    'guests' => [],
                    'roomName' => $roomName,
                    'bed' => $bed,
                    'travelDates' => $travelDates,
                    'total' => $total,
                    'payments' => $payments,
                    'balance' => $balance,
                ];
            }
            
            // Add guest data (for both the booking row and subsequent guest rows)
            if ($guestName && $currentBookingNumber !== null) {
                // Clean guest name - remove age markers like (15), (10), etc.
                $cleanGuestName = preg_replace('/\s*\(\d+\)\s*$/', '', trim($guestName));
                
                if ($cleanGuestName) {
                    $bookings[$currentBookingNumber]['guests'][] = [
                        'name' => $cleanGuestName,
                        'insurance' => $this->parseInsurance($insurance),
                        'transfers' => $this->parseTransfers($transfers),
                        'travelDates' => $travelDates,
                    ];
                }
            }
        }
        
        return $bookings;
    }
    
    /**
     * Find the row where data starts (after headers and metadata)
     *
     * @param \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet
     * @return int
     */
    protected function findDataStartRow($sheet)
    {
        $highestRow = min(20, $sheet->getHighestRow()); // Check first 20 rows max
        
        // Look for common header patterns
        for ($row = 1; $row <= $highestRow; $row++) {
            $cellA = strtolower(trim($sheet->getCell('A' . $row)->getValue()));
            $cellB = strtolower(trim($sheet->getCell('B' . $row)->getValue()));
            
            // Pattern 1: System export format (header in row 1)
            if ($cellA === '#' && $cellB === 'guest name') {
                return $row + 1;
            }
            
            // Pattern 2: Supplier format (header with "Booking #" or "Booking#")
            if (($cellA === 'booking #' || $cellA === 'booking#') && 
                (strpos($cellB, 'client') !== false || strpos($cellB, 'guest') !== false || strpos($cellB, 'name') !== false)) {
                return $row + 1;
            }
        }
        
        // Default: assume row 2 (row 1 is header)
        return 2;
    }

    /**
     * Load system bookings with related data
     *
     * @param Group $group
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function loadSystemBookings(Group $group)
    {
        return $group->bookings()
            // Don't include soft-deleted (cancelled) bookings in comparison
            // as they should not be part of the final rooming list
            ->with([
                'clients.guests',
                'clients.payments',
                'roomBlocks.room'
            ])
            ->ordered()
            ->get();
    }

    /**
     * Find discrepancies between system and spreadsheet
     *
     * @param \Illuminate\Database\Eloquent\Collection $systemBookings
     * @param array $spreadsheetBookings
     * @return array
     */
    protected function findDiscrepancies($systemBookings, $spreadsheetBookings)
    {
        $discrepancies = [];
        
        foreach ($systemBookings as $booking) {
            $bookingNumber = $booking->order;
            $issues = [];
            
            // Check if booking exists in spreadsheet
            if (!isset($spreadsheetBookings[$bookingNumber])) {
                $discrepancies[] = [
                    'bookingNumber' => $bookingNumber,
                    'guestName' => $booking->clients->first()->name ?? 'Unknown',
                    'issues' => [[
                        'field' => 'Booking Existence',
                        'systemValue' => 'Exists',
                        'spreadsheetValue' => 'Not Found'
                    ]]
                ];
                continue;
            }
            
            $spreadsheetBooking = $spreadsheetBookings[$bookingNumber];
            
            // Compare guest count
            $systemGuestCount = $booking->guests->count();
            $spreadsheetGuestCount = count($spreadsheetBooking['guests']);
            
            if ($systemGuestCount !== $spreadsheetGuestCount) {
                $issues[] = [
                    'field' => 'Number of Guests',
                    'systemValue' => $systemGuestCount,
                    'spreadsheetValue' => $spreadsheetGuestCount
                ];
            }
            
            // Compare guest names
            $systemGuestNames = $booking->guests->pluck('name')->map(function($name) {
                return strtolower(trim($name));
            })->sort()->values()->toArray();
            
            $spreadsheetGuestNames = collect($spreadsheetBooking['guests'])->pluck('name')->map(function($name) {
                return strtolower(trim($name));
            })->sort()->values()->toArray();
            
            if ($systemGuestNames !== $spreadsheetGuestNames) {
                $issues[] = [
                    'field' => 'Guest Names',
                    'systemValue' => implode(', ', $systemGuestNames),
                    'spreadsheetValue' => implode(', ', $spreadsheetGuestNames)
                ];
            }
            
            // Compare room category
            $systemRoomName = $booking->roomBlocks->first()->room->name ?? 'Unknown';
            $spreadsheetRoomName = $spreadsheetBooking['roomName'] ?? 'Unknown';
            
            if (trim($systemRoomName) !== trim($spreadsheetRoomName)) {
                $issues[] = [
                    'field' => 'Room Category',
                    'systemValue' => $systemRoomName,
                    'spreadsheetValue' => $spreadsheetRoomName
                ];
            }
            
            // Compare bed type
            $systemBed = $booking->roomBlocks->first()->pivot->bed ?? 'Unknown';
            $spreadsheetBed = $spreadsheetBooking['bed'] ?? 'Unknown';
            
            if (trim($systemBed) !== trim($spreadsheetBed)) {
                $issues[] = [
                    'field' => 'Bed Type',
                    'systemValue' => $systemBed,
                    'spreadsheetValue' => $spreadsheetBed
                ];
            }
            
            // Compare travel dates
            $systemCheckIn = $booking->roomBlocks->first()->pivot->check_in ?? null;
            $systemCheckOut = $booking->roomBlocks->first()->pivot->check_out ?? null;
            
            if ($systemCheckIn && $systemCheckOut) {
                $systemDates = Carbon::parse($systemCheckIn)->format('m/d/Y') . ' - ' . Carbon::parse($systemCheckOut)->format('m/d/Y');
                $spreadsheetDates = $spreadsheetBooking['travelDates'] ?? 'Unknown';
                
                if (trim($systemDates) !== trim($spreadsheetDates)) {
                    $issues[] = [
                        'field' => 'Travel Dates (Room)',
                        'systemValue' => $systemDates,
                        'spreadsheetValue' => $spreadsheetDates
                    ];
                }
            }
            
            // Compare insurance count
            $systemInsuranceCount = $booking->guests->where('insurance', true)->count();
            $spreadsheetInsuranceCount = collect($spreadsheetBooking['guests'])->filter(function($guest) {
                return $guest['insurance'] > 0;
            })->count();
            
            if ($systemInsuranceCount !== $spreadsheetInsuranceCount) {
                $issues[] = [
                    'field' => 'Guests with Insurance',
                    'systemValue' => $systemInsuranceCount,
                    'spreadsheetValue' => $spreadsheetInsuranceCount
                ];
            }
            
            // Compare totals
            $systemTotal = round($booking->total, 2);
            $spreadsheetTotal = round(floatval($spreadsheetBooking['total']), 2);
            
            if (abs($systemTotal - $spreadsheetTotal) > 0.01) {
                $issues[] = [
                    'field' => 'Total Cost',
                    'systemValue' => '$' . number_format($systemTotal, 2),
                    'spreadsheetValue' => '$' . number_format($spreadsheetTotal, 2)
                ];
            }
            
            // Compare payments
            $systemPayments = round($booking->payment_total, 2);
            $spreadsheetPayments = round(floatval($spreadsheetBooking['payments']), 2);
            
            if (abs($systemPayments - $spreadsheetPayments) > 0.01) {
                $issues[] = [
                    'field' => 'Amount Paid',
                    'systemValue' => '$' . number_format($systemPayments, 2),
                    'spreadsheetValue' => '$' . number_format($spreadsheetPayments, 2)
                ];
            }
            
            // Compare balance
            $systemBalance = round($booking->total - $booking->payment_total, 2);
            $spreadsheetBalance = round(floatval($spreadsheetBooking['balance']), 2);
            
            if (abs($systemBalance - $spreadsheetBalance) > 0.01) {
                $issues[] = [
                    'field' => 'Balance Due',
                    'systemValue' => '$' . number_format($systemBalance, 2),
                    'spreadsheetValue' => '$' . number_format($spreadsheetBalance, 2)
                ];
            }
            
            // Add to discrepancies if there are issues
            if (!empty($issues)) {
                $discrepancies[] = [
                    'bookingNumber' => $bookingNumber,
                    'guestName' => $booking->clients->first()->name ?? 'Unknown',
                    'issues' => $issues
                ];
            }
        }
        
        // Check for bookings in spreadsheet but not in system
        foreach ($spreadsheetBookings as $bookingNumber => $spreadsheetBooking) {
            $found = $systemBookings->firstWhere('order', $bookingNumber);
            
            if (!$found) {
                $discrepancies[] = [
                    'bookingNumber' => $bookingNumber,
                    'guestName' => $spreadsheetBooking['guests'][0]['name'] ?? 'Unknown',
                    'issues' => [[
                        'field' => 'Booking Existence',
                        'systemValue' => 'Not Found',
                        'spreadsheetValue' => 'Exists'
                    ]]
                ];
            }
        }
        
        return $discrepancies;
    }

    /**
     * Build comparison report
     *
     * @param \Illuminate\Database\Eloquent\Collection $systemBookings
     * @param array $spreadsheetBookings
     * @param array $discrepancies
     * @return array
     */
    protected function buildReport($systemBookings, $spreadsheetBookings, $discrepancies)
    {
        $systemGuestCount = $systemBookings->reduce(function ($carry, $booking) {
            return $carry + $booking->guests->count();
        }, 0);
        
        $spreadsheetGuestCount = collect($spreadsheetBookings)->reduce(function ($carry, $booking) {
            return $carry + count($booking['guests']);
        }, 0);
        
        return [
            'hasDiscrepancies' => !empty($discrepancies),
            'summary' => [
                'totalBookings' => $systemBookings->count(),
                'systemGuestCount' => $systemGuestCount,
                'spreadsheetGuestCount' => $spreadsheetGuestCount,
                'guestCountMatch' => $systemGuestCount === $spreadsheetGuestCount,
                'bookingsWithDiscrepancies' => count($discrepancies)
            ],
            'discrepancies' => $discrepancies,
            'notes' => $this->generateNotes($discrepancies)
        ];
    }

    /**
     * Parse insurance value from spreadsheet
     *
     * @param mixed $value
     * @return float
     */
    protected function parseInsurance($value)
    {
        if (is_numeric($value)) {
            return floatval($value);
        }
        
        // Try to extract number from string like "$50.00"
        $cleaned = preg_replace('/[^0-9.]/', '', $value);
        return floatval($cleaned);
    }

    /**
     * Parse transfers value from spreadsheet
     *
     * @param mixed $value
     * @return float
     */
    protected function parseTransfers($value)
    {
        if (is_numeric($value)) {
            return floatval($value);
        }
        
        // Try to extract number from string like "$75.00"
        $cleaned = preg_replace('/[^0-9.]/', '', $value);
        return floatval($cleaned);
    }
    
    /**
     * Check if a value represents a date
     *
     * @param mixed $value
     * @return bool
     */
    protected function isDate($value)
    {
        if (!$value) {
            return false;
        }
        
        // Check if it's a numeric Excel date
        if (is_numeric($value) && $value > 40000 && $value < 60000) {
            return true;
        }
        
        // Check if it's a date string
        if (is_string($value)) {
            return strtotime($value) !== false;
        }
        
        // Check if it's a DateTime object
        return $value instanceof \DateTime;
    }
    
    /**
     * Parse and format date value
     *
     * @param mixed $value
     * @return string|null
     */
    protected function parseDate($value)
    {
        if (!$value) {
            return null;
        }
        
        try {
            // Handle Excel numeric dates
            if (is_numeric($value) && $value > 40000 && $value < 60000) {
                $date = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value);
                return $date->format('m/d/Y');
            }
            
            // Handle DateTime objects
            if ($value instanceof \DateTime) {
                return $value->format('m/d/Y');
            }
            
            // Handle date strings
            if (is_string($value)) {
                $timestamp = strtotime($value);
                if ($timestamp !== false) {
                    return date('m/d/Y', $timestamp);
                }
            }
            
            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Generate helpful notes based on discrepancies
     *
     * @param array $discrepancies
     * @return array
     */
    protected function generateNotes($discrepancies)
    {
        $notes = [];
        
        if (empty($discrepancies)) {
            $notes[] = 'The rooming list perfectly matches the system data.';
        } else {
            $notes[] = 'Review each discrepancy carefully before finalizing bookings.';
            $notes[] = 'Price differences may be due to recent rate updates or custom pricing.';
            $notes[] = 'Guest name differences might be due to spelling variations or updates.';
        }
        
        return $notes;
    }
}
