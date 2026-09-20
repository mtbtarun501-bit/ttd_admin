<?php

namespace App\Imports;

use App\Models\Devotee;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\ToArray;

/**
 * Import the monthly single-column block workbook shared by partners.
 *
 * The workbook is a single column where every line is one row:
 *   - each family member is a 4-line record: Name / Age / Gender / Aadhaar
 *   - members of one family are separated by blank rows
 *   - each family is followed by a booking block that also marks the boundary
 *     to the next family, e.g.
 *
 *        ---------
 *        Virtual Seva
 *        08/12/2026 (Tuesday) tirumala darshan date
 *        03 persons
 *        Ravi selvarajan
 *        9701053220
 *
 * The booking block carries the family head / referred name (the name just
 * before the phone) and the family contact phone. Only the devotee data is
 * imported; booking/seva text is used purely to group families and enrich the
 * head record.
 */
class MonthlyDevoteeImport implements ToArray
{
    public int $familiesCount = 0;

    public int $createdCount = 0;

    public int $skippedCount = 0;

    public function array(array $rows)
    {
        $lines = $this->normalizeLines($rows);
        $families = $this->parseFamilies($lines);

        foreach ($families as $family) {
            $this->importFamily($family);
        }
    }

    public function summary(): array
    {
        return [
            'families' => $this->familiesCount,
            'created' => $this->createdCount,
            'skipped' => $this->skippedCount,
        ];
    }

    /**
     * Flatten every cell into one trimmed, non-empty line, stripping the
     * leading separator junk that sometimes prefixes the first member name
     * (e.g. "___________ ,Parmanand Sunki").
     */
    protected function normalizeLines(array $rows): array
    {
        $lines = [];

        foreach ($rows as $row) {
            $row = is_array($row) ? array_values($row) : [$row];

            $line = '';
            foreach ($row as $cell) {
                if ($cell === null) {
                    continue;
                }
                $cell = trim((string) $cell);
                if ($cell === '') {
                    continue;
                }
                $line = trim($line === '' ? $cell : $line . ' ' . $cell);
            }

            if ($line === '') {
                continue;
            }

            $line = preg_replace('/^[_\-\s=]+([,;]?\s*)+/', '', $line);
            $line = trim($line);

            if ($line !== '') {
                $lines[] = $line;
            }
        }

        return $lines;
    }

    /**
     * Split the raw lines into families: a list of member records plus the
     * head/referred name and phone extracted from the trailing booking block.
     */
    protected function parseFamilies(array $lines): array
    {
        $families = [];
        $members = [];
        $pendingBooking = null;

        $i = 0;
        $n = count($lines);

        while ($i < $n) {
            $line = trim($lines[$i]);

            if ($this->isSeparator($line)) {
                $i++;
                continue;
            }

            $member = $this->consumeMember($lines, $i);

            if ($member) {
                $members[] = $member;
                $i = $member['end'];
                continue;
            }

            if ($this->isBookingLikeLine($lines, $i)) {
                $booking = $this->parseBookingBlock($lines, $i);

                if ($booking) {
                    $i = $booking['end'];
                    $pendingBooking = $booking;
                } else {
                    $i++;
                    continue;
                }
            } else {
                // Stray / unrecognised line, skip it.
                $i++;
                continue;
            }

            if ($members) {
                $families[] = [
                    'head_name' => $pendingBooking['head_name'] ?? null,
                    'phone' => $pendingBooking['phone'] ?? null,
                    'members' => $members,
                ];
                $members = [];
                $pendingBooking = null;
            } else {
                // Booking block before any member: apply it to the next family.
                $pendingBooking = $booking;
            }
        }

        if ($members) {
            $families[] = [
                'head_name' => $pendingBooking['head_name'] ?? null,
                'phone' => $pendingBooking['phone'] ?? null,
                'members' => $members,
            ];
        }

        return $families;
    }

    /**
     * Try to consume a 4-line member record (Name / Age / Gender / Aadhaar).
     */
    protected function consumeMember(array $lines, int $i): ?array
    {
        if (!isset($lines[$i + 3])) {
            return null;
        }

        $name = trim($lines[$i]);
        $age = trim($lines[$i + 1]);
        $gender = trim($lines[$i + 2]);
        $aadhaar = trim($lines[$i + 3]);

        if (!$this->isNameLine($name)) {
            return null;
        }
        if (!$this->isAge($age)) {
            return null;
        }
        if (!$this->isGender($gender)) {
            return null;
        }

        $aadhaarDigits = $this->extractAadhaar($aadhaar);
        if ($aadhaarDigits === null) {
            return null;
        }

        return [
            'name' => $name,
            'age' => (int) $age,
            'gender' => $this->normalizeGender($gender),
            'aadhaar' => $aadhaarDigits,
            'end' => $i + 4,
        ];
    }

    /**
     * Classify a line that starts a booking block. A booking block starts on a
     * seva name, a darshan date, a "N persons" count, a phone, or a plain name
     * that is NOT followed by an age (i.e. the contact name in the block).
     */
    protected function isBookingLikeLine(array $lines, int $i): bool
    {
        $line = trim($lines[$i] ?? '');

        if ($line === '') {
            return false;
        }

        if ($this->isBookingKeyword($line)) {
            return true;
        }

        if (preg_match('/\d{1,2}[\/-]\d{1,2}[\/-]\d{4}/', $line)) {
            return true;
        }

        if (preg_match('/^\d{1,2}\s*(person|member|pax|nos?)/i', $line)) {
            return true;
        }

        if ($this->isPhone($line)) {
            return true;
        }

        if ($this->isNameLine($line)) {
            return !$this->isAge(trim($lines[$i + 1] ?? ''));
        }

        return false;
    }

    /**
     * Consume the lines of a booking block. Returns the contact phone and the
     * head/referred name (the name line directly above the phone). The block
     * ends when the next member record, a pure separator, or EOF is reached.
     */
    protected function parseBookingBlock(array $lines, int $i): ?array
    {
        $n = count($lines);
        $buffer = [];
        $j = $i;

        while ($j < $n) {
            $line = trim($lines[$j]);

            if ($this->isSeparator($line)) {
                break;
            }

            if ($this->consumeMember($lines, $j)) {
                break;
            }

            $buffer[] = $line;
            $j++;
        }

        $phone = null;
        $phoneIndex = null;

        for ($k = count($buffer) - 1; $k >= 0; $k--) {
            $digits = preg_replace('/\D+/', '', $buffer[$k]);
            if (strlen($digits) === 10) {
                $phone = $digits;
                $phoneIndex = $k;
                break;
            }
        }

        $headName = null;

        if ($phoneIndex !== null) {
            for ($k = $phoneIndex - 1; $k >= 0; $k--) {
                $candidate = trim($buffer[$k]);

                if (preg_match('/\d{1,2}[\/-]\d{1,2}[\/-]\d{4}/', $candidate)) {
                    continue;
                }
                if (preg_match('/^\d{1,2}\s*(person|member|pax|nos?)/i', $candidate)) {
                    continue;
                }
                if ($this->isAge($candidate)) {
                    continue;
                }
                if ($this->isNameLine($candidate)) {
                    $headName = $candidate;
                    break;
                }
            }
        }

        if ($phone === null && $headName === null) {
            return null;
        }

        return [
            'head_name' => $headName,
            'phone' => $phone,
            'end' => $j,
        ];
    }

    /**
     * Create (or update) the family head and attach every member to it.
     */
    protected function importFamily(array $family): void
    {
        $members = $family['members'];

        if (empty($members)) {
            return;
        }

        $headName = !empty($family['head_name'])
            ? trim($family['head_name'])
            : trim($members[0]['name']);
        $phone = !empty($family['phone']) ? $family['phone'] : null;

        // The head/referred person usually has their own record in the family
        // block; pull age/gender/aadhaar from it when the names match.
        $headMemberRecord = null;
        foreach ($members as $member) {
            if ($this->normalizeName($member['name']) === $this->normalizeName($headName)) {
                $headMemberRecord = $member;
                break;
            }
        }

        $head = $this->findOrCreateHead($headName, $phone, $headMemberRecord);

        foreach ($members as $member) {
            if ($this->normalizeName($member['name']) === $this->normalizeName($headName)) {
                continue;
            }

            if ($this->findExistingByUnique($member['aadhaar'])) {
                $this->skippedCount++;
                continue;
            }

            Devotee::create([
                'user_id' => Auth::id() ?? 1,
                'name' => $member['name'],
                'age' => $member['age'],
                'gender' => $member['gender'],
                'aadhaar' => $member['aadhaar'],
                'is_head_of_family' => false,
                'head_devotee_id' => $head->id,
            ]);
            $this->createdCount++;
        }

        $this->familiesCount++;
    }

    protected function findOrCreateHead(string $headName, ?string $phone, ?array $memberRecord): Devotee
    {
        $head = Devotee::whereNull('head_devotee_id')
            ->where('is_head_of_family', true)
            ->get()
            ->first(fn ($d) => $this->normalizeName($d->name) === $this->normalizeName($headName));

        if (!$head && !empty($memberRecord['aadhaar'])) {
            $head = Devotee::where('aadhaar', $memberRecord['aadhaar'])->first();
        }

        if (!$head) {
            $head = Devotee::create([
                'user_id' => Auth::id() ?? 1,
                'name' => $headName,
                'age' => $memberRecord['age'] ?? 30,
                'gender' => $memberRecord['gender'] ?? 'Unknown',
                'aadhaar' => $memberRecord['aadhaar'] ?? null,
                'phone' => $phone,
                'is_head_of_family' => true,
                'remarks' => 'Imported from monthly sheet',
            ]);
            $this->createdCount++;

            // The family is credited to its own head/referred contact.
            $head->update(['referred_devotee_id' => $head->id]);
            $head->refresh();
        } else {
            $updates = ['is_head_of_family' => true, 'head_devotee_id' => null];

            if (empty($head->name)) {
                $updates['name'] = $headName;
            }
            if (empty($head->phone) && $phone) {
                $updates['phone'] = $phone;
            }
            if (!empty($memberRecord['aadhaar']) && empty($head->aadhaar)) {
                $updates['aadhaar'] = $memberRecord['aadhaar'];
            }
            if (!empty($memberRecord['age']) && empty($head->age)) {
                $updates['age'] = $memberRecord['age'];
            }
            if (empty($head->gender) || $head->gender === 'Unknown') {
                if (!empty($memberRecord['gender'])) {
                    $updates['gender'] = $memberRecord['gender'];
                }
            }

            $updates['referred_devotee_id'] = $head->id;
            $head->update($updates);
            $head->refresh();
        }

        return $head;
    }

    protected function findExistingByUnique(string $aadhaar): ?Devotee
    {
        return Devotee::where('aadhaar', $aadhaar)->first();
    }

    protected function normalizeName(string $name): string
    {
        return strtolower(preg_replace('/[^a-z0-9]/i', '', $name));
    }

    protected function normalizeGender(string $gender): string
    {
        $g = strtolower(trim($gender));

        if ($g === 'm' || $g === 'male') {
            return 'Male';
        }
        if ($g === 'f' || $g === 'female') {
            return 'Female';
        }

        return ucfirst($g);
    }

    protected function extractAadhaar(string $value): ?string
    {
        $digits = preg_replace('/\D+/', '', $value);

        return strlen($digits) === 12 ? $digits : null;
    }

    protected function isNameLine(string $line): bool
    {
        $line = trim($line);

        if ($line === '') {
            return false;
        }
        if ($this->extractAadhaar($line) !== null) {
            return false;
        }
        if ($this->isAge($line)) {
            return false;
        }
        if ($this->isPhone($line)) {
            return false;
        }
        if (preg_match('/\d{1,2}[\/-]\d{1,2}[\/-]\d{4}/', $line)) {
            return false;
        }

        return (bool) preg_match('/[a-zA-Z].{2,}/', $line);
    }

    protected function isAge(string $value): bool
    {
        $value = trim($value);

        if (!preg_match('/^\d{1,3}$/', $value)) {
            return false;
        }

        $age = (int) $value;

        return $age >= 1 && $age <= 120;
    }

    protected function isGender(string $value): bool
    {
        return in_array(strtolower(trim($value)), ['male', 'female', 'm', 'f'], true);
    }

    protected function isPhone(string $value): bool
    {
        $digits = preg_replace('/\D+/', '', $value);

        return strlen($digits) === 10;
    }

    protected function isSeparator(string $value): bool
    {
        return (bool) preg_match('/^[_\-\s=·•]{3,}$/', trim($value));
    }

    protected function isBookingKeyword(string $line): bool
    {
        $hay = strtolower($line);

        foreach ([
            'virtual seva', 'special entry', 'arjitha', 'supatham', 'angapradakshana',
            'senior citizen', 'homam', 'accommodation', 'vaikunta ekadesi',
            'sarva darshan', 'break darshan', 'darshan', 'seva', 'tirumala',
            'booking', 'ticket',
        ] as $keyword) {
            if (str_contains($hay, $keyword)) {
                return true;
            }
        }

        return false;
    }
}