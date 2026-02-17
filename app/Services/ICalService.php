<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

use Illuminate\Support\Facades\Cache;

class ICalService
{
    /**
     * Fetch and parse iCal events from a URL.
     * Returns an array of events: [['uid' => ..., 'summary' => ..., 'dtstart' => ..., 'dtend' => ...], ...]
     */
    public function fetchEvents(string $url, $cacheKey = null, string $source = null): array
    {
        // Always include URL hash in key to ensure we don't serve stale data if URL changes
        $keySuffix = md5($url);
        $key = $cacheKey ? "ical_events_{$cacheKey}_{$keySuffix}" : "ical_events_{$keySuffix}";

        // Cache for 5 minutes (300 seconds) to allow faster updates while preventing spam
        return Cache::remember($key, 300, function () use ($url, $source) { 
            try {
                // Fetch content with a timeout
                $response = Http::timeout(10)->get($url);

                if ($response->failed()) {
                    Log::warning("Failed to fetch iCal from URL: {$url}. Status: " . $response->status());
                    return [];
                }

                $content = $response->body();
                return $this->parseICS($content, $source);

            } catch (\Exception $e) {
                Log::error("Exception fetching iCal from {$url}: " . $e->getMessage());
                return [];
            }
        });
    }

    /**
     * Parse raw ICS content into a structured array.
     */
    private function parseICS(string $content, string $source = null): array
    {
        $events = [];
        
        // 1. Unfold lines: lines starting with space or tab continue the previous line
        $content = preg_replace('/\r\n[ \t]+/', '', $content);
        $content = preg_replace('/\n[ \t]+/', '', $content);
        
        // Normalize line endings
        $content = str_replace("\r\n", "\n", $content);
        $lines = explode("\n", $content);

        $currentEvent = [];
        $inEvent = false;

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;
            
            if ($line === 'BEGIN:VEVENT') {
                $inEvent = true;
                $currentEvent = [];
                continue;
            }

            if ($line === 'END:VEVENT') {
                $inEvent = false;
                if (!empty($currentEvent['dtstart']) && !empty($currentEvent['dtend'])) {
                    $events[] = $currentEvent;
                }
                continue;
            }

            if ($inEvent) {
                // Split by first colon to separate key (with params) and value
                $parts = explode(':', $line, 2);
                if (count($parts) < 2) continue;

                $keyPart = $parts[0];
                $value = $parts[1];

                // Remove parameters from key (e.g. DTSTART;VALUE=DATE -> DTSTART)
                $keyBase = explode(';', $keyPart)[0];

                switch ($keyBase) {
                    case 'UID':
                        $currentEvent['uid'] = $value;
                        break;
                    case 'SUMMARY':
                        $currentEvent['summary'] = $value;
                        break;
                    case 'DTSTART':
                        $currentEvent['dtstart'] = $value;
                        // Check if it's explicitly all-day
                        if (str_contains($keyPart, 'VALUE=DATE')) {
                            $currentEvent['is_all_day'] = true;
                        } else {
                            // Implicit check: if length is 8 (YYYYMMDD), it's all day
                            $currentEvent['is_all_day'] = (strlen($value) === 8);
                        }
                        break;
                    case 'DTEND':
                        $currentEvent['dtend'] = $value;
                        break;
                }
            }
        }

        return $this->processEvents($events, $source);
    }

    /**
     * Post-process events: parse dates, sort, filter.
     */
    private function processEvents(array $rawEvents, string $source = null): array
    {
        $processed = [];
        $now = Carbon::today()->subDays(60); // Look back 60 days
        $future = Carbon::today()->addYear();

        foreach ($rawEvents as $event) {
            try {
                // Parse dates
                $start = $this->parseDate($event['dtstart']);
                $end = $this->parseDate($event['dtend']);

                if (!$start || !$end) continue;

                // Filter out obviously old or too far future
                if ($end->lt($now) || $end->gt($future)) continue;

                $processed[] = [
                    'uid' => $event['uid'] ?? uniqid(),
                    'summary' => $event['summary'] ?? 'Booking',
                    'start_date' => $start->toDateString(),
                    'end_date' => $end->toDateString(), // This is the checkout date
                    'source' => $source
                ];
            } catch (\Exception $e) {
                continue; // Skip malformed dates
            }
        }

        // Sort by date desc
        usort($processed, fn($a, $b) => strcmp($b['end_date'], $a['end_date']));

        return $processed;
    }

    private function parseDate($dateStr)
    {
        // Remove 'Z' if present, as we treat dates as local or handle manually
        $cleanDate = rtrim($dateStr, 'Z');

        // Handle basic YYYYMMDD
        if (preg_match('/^\d{8}$/', $cleanDate)) {
            return Carbon::createFromFormat('Ymd', $cleanDate)->startOfDay();
        }
        
        // Handle YYYYMMDDTHHMMSS
        if (preg_match('/^\d{8}T\d{6}$/', $cleanDate)) {
            return Carbon::createFromFormat('Ymd\THis', $cleanDate);
        }
        
        // Try standard Carbon parsing for other formats
        try {
            return Carbon::parse($dateStr);
        } catch (\Exception $e) {
            return null;
        }
    }
}
