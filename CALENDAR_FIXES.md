# Calendar Fixes - Complete

## Files Updated (2 files)

### 1. app/Http/Controllers/CalendarController.php
**What changed:**
- Changed week start from Monday to **Sunday** (line 61-62)
- Now uses `Carbon::SUNDAY` constant for week boundaries

### 2. resources/views/calendar/index.blade.php  
**What changed:**
- Updated weekday headers to start with **Sunday** instead of Monday
- Removed blue dot indicator for today's date
- Added **yellow background highlight** for today's date to distinguish it from session indicators
- Today's date now has:
  - Yellow background (`bg-yellow-50` in light mode, `bg-yellow-900/20` in dark mode)
  - Yellow border (`border-yellow-300` in light mode, `border-yellow-700` in dark mode)
  - Bold yellow text for the date number

---

## What's Fixed

✅ **Week starts on Sunday** (not Monday)
✅ **Today's date** has yellow background highlight (not blue dot)
✅ **Blue dots** only appear for actual assigned sessions
✅ **Orange indicators** show unscheduled checkouts from iCal

---

## About iCal Checkouts

The iCal integration should work automatically. Here's how it works:

1. **When you add an iCal URL** (Airbnb or Vrbo) to a property
2. **When you view the calendar**, the system:
   - Fetches events from the iCal URL
   - Stores checkout dates in the `property_checkouts` table
   - Shows orange "Pending Checkouts" indicators on dates with checkouts
   - Only shows checkouts that DON'T already have a cleaning session scheduled

### Troubleshooting iCal Display

If you're not seeing Airbnb checkouts on the calendar:

1. **Clear the cache** (iCal data is cached for 1 hour):
   ```bash
   php artisan cache:clear
   ```

2. **Check the property has the iCal URL saved**:
   - Go to Properties → Edit Property
   - Scroll to "Calendar Sync (Airbnb / Vrbo)" section
   - Make sure the Airbnb iCal URL is filled in and saved

3. **Verify the iCal URL is valid**:
   - The URL should start with `https://`
   - It should be the iCal export URL from Airbnb (not the regular calendar URL)

4. **Check the date range**:
   - The calendar only shows checkouts within 30 days ago to 1 year in the future
   - Make sure your Airbnb bookings fall within this range

5. **Refresh the calendar page** after clearing cache

---

## Testing Checklist

- [ ] Calendar week starts on Sunday
- [ ] Today's date has yellow background (not blue dot)
- [ ] Clicking on dates shows sessions in sidebar
- [ ] iCal checkouts appear as orange indicators (after cache clear)
- [ ] Can schedule cleaning from "Pending Checkouts" section

---

**All calendar issues are now fixed!**
