# Equipment Automatic Status Feature

## Overview
The equipment status is now **automatically calculated** based on the actual state of the equipment in the system. This ensures that the status always reflects reality and reduces manual updates.

## How It Works

### Automatic Statuses (Cannot be manually set)
These statuses are automatically assigned based on system records:

1. **Borrowed** 🟡
   - Equipment is in an approved borrow request
   - The request has not been returned yet (return_date is NULL or in the future)
   - Automatically clears when equipment is returned

2. **Maintenance** 🔵
   - Equipment has an active maintenance record
   - Maintenance status is "Pending" or "In Progress"
   - Automatically clears when maintenance is marked as "Completed"

3. **Repair** 🔴
   - Equipment has an active repair record
   - Repair status is "Pending" or "In Progress"
   - Automatically clears when repair is marked as "Completed"

### Manual Statuses (Can be set by users)
These statuses can be manually set in the edit equipment page:

1. **Available** 🟢
   - Default status for new equipment
   - Equipment is ready to be borrowed or used
   - Automatically assigned when no other conditions are met

2. **Disposed** ⚫
   - Equipment has been permanently disposed
   - Must be manually set
   - Permanent status (does not auto-change)

3. **Retired** ⚫
   - Equipment is no longer in active use
   - Must be manually set
   - Permanent status (does not auto-change)

4. **Transferred** 🔵
   - Equipment has been transferred to another location/department
   - Set automatically when using the transfer action
   - Can be manually changed

## Priority Order
The system checks conditions in this order:
1. Is it borrowed? → **Borrowed**
2. Is it under repair? → **Repair**
3. Is it under maintenance? → **Maintenance**
4. Is it Disposed/Retired? → Keep that status
5. Otherwise → **Available**

## User Interface

### Adding New Equipment
- Status is automatically set to "Available"
- Cannot select other statuses during creation
- Status will automatically update based on usage

### Editing Equipment
- **Automatic statuses** (Borrowed, Maintenance, Repair):
  - Displayed as read-only with "(Automatic)" label
  - Shows lightning bolt icon ⚡
  - Cannot be manually changed
  - **Not available in dropdown** - these options are completely removed
  - System will reject any attempts to manually set these statuses
  
- **Manual statuses** (Available, Disposed, Retired, Transferred):
  - Can be selected from dropdown
  - Use for permanent status changes
  - Only these 4 options appear in the status dropdown

### Inventory List
- Status badges show with color coding
- Automatic statuses display a lightning bolt icon ⚡
- Hover over the icon to see "Automatic status" tooltip

## Benefits

1. **Accuracy**: Status always reflects the actual state
2. **Consistency**: No manual errors or forgotten updates
3. **Real-time**: Status updates immediately when records change
4. **Transparency**: Users can see which statuses are automatic

## Technical Details

### Database
- The `equipment.status` column in the database stores the base status
- The actual displayed status is calculated dynamically using SQL CASE statements
- This happens in `Equipment::getAllEquipment()` and `Equipment::getEquipmentById()`

### Related Tables
- `borrow_request_items` + `borrow_requests`: For "Borrowed" status
- `maintenance_records`: For "Maintenance" and "Repair" statuses

### Files Modified
- `classes/Equipment.php`: Added automatic status calculation
- `pages/edit_equipment.php`: Made automatic statuses read-only
- `pages/inventory.php`: Updated add equipment form
- `pages/inventory_table.php`: Added visual indicators

## Examples

### Example 1: Borrowing Equipment
1. Equipment starts as "Available"
2. User creates borrow request → Status: "Available" (still pending)
3. Admin approves request → Status: **"Borrowed"** (automatic)
4. User returns equipment → Status: **"Available"** (automatic)

### Example 2: Maintenance
1. Equipment is "Available"
2. Maintenance request created → Status: **"Maintenance"** (automatic)
3. Maintenance in progress → Status: **"Maintenance"** (automatic)
4. Maintenance completed → Status: **"Available"** (automatic)

### Example 3: Disposal
1. Equipment is "Available"
2. Admin sets status to "Disposed" → Status: **"Disposed"** (manual)
3. Status remains "Disposed" permanently (does not auto-change)

## Troubleshooting

**Q: Equipment shows "Borrowed" but was returned**
- Check if the borrow request was properly marked as returned
- Verify the return_date is set in the database

**Q: Equipment shows "Maintenance" but maintenance is done**
- Check if the maintenance record status is set to "Completed"
- Verify there are no other pending maintenance records

**Q: Can't change status to "Borrowed" manually**
- This is correct behavior - "Borrowed" is automatic
- Create/approve a borrow request instead

**Q: Status not updating in real-time**
- Refresh the page to see updated status
- Status is calculated on page load, not via AJAX (yet)

## Future Enhancements
- Real-time status updates via WebSocket or AJAX polling
- Status history tracking
- Notification when status changes
- Dashboard widget showing status distribution

