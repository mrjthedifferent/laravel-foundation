# Activity Log Module

The Activity Log module provides a comprehensive audit trail system for monitoring and tracking all changes within the application.

## Features

- **Dashboard Visualization**: Interactive charts and graphs displaying activity trends over time, by user, by type, and by entity.
- **Advanced Filtering**: Filter logs by date range, event type, entity type, user ID, and search terms.
- **Export Capabilities**: Export filtered logs to Excel or CSV formats with background processing for large datasets.
- **IP Tracking**: View detailed information about IP addresses including geolocation data.
- **Bulk Operations**: Select and delete multiple logs at once.

## Usage

### Dashboard

Access the analytics dashboard at `/activity-logs/dashboard` to view visualizations of system activity.

### Activity Log List

View and filter logs at `/activity-logs`. The following filters are available:

- Search by values, IP, URL
- Date range (from/to)
- Event type (created, updated, deleted, etc.)
- Entity type
- User ID
- Items per page

### Exporting Logs

1. Apply any desired filters
2. Click the "Export" button
3. Exports are processed in the background to handle large datasets
4. You will receive a notification when the export is ready
5. Download from the Download Manager

### IP Tracking

Click on any IP address in the logs to view detailed information including:
- Country, region, city
- ISP information
- Geolocation on a map

## Permissions

The module includes the following permissions:

- **View Activity Log**: View the logs and dashboard
- **Delete Activity Log**: Delete individual or bulk logs
- **Export Activity Log**: Export logs to Excel/CSV
- **View Logs**: Access system logs

## Development

### Structures

- **Controllers**: Handle requests and responses
- **Jobs**: Background processing for exports
- **Helpers**: Utility functions for formatting and display
- **Views**: UI templates for all features

### Key Files

- `ActivityLogController.php`: Main controller for all actions
- `ActivityLogExportJob.php`: Handles background log exports
- `ActivityLogHelper.php`: Contains utility methods
- `dashboard.blade.php`: Dashboard view with charts
- `index.blade.php`: Main log listing with filters
- `ip_info.blade.php`: IP information display

### Dependencies

- **rap2hpoutre/fast-excel**: Used for efficient Excel exports
- **Chart.js**: For dashboard visualizations
- **Leaflet.js**: For IP location maps 
