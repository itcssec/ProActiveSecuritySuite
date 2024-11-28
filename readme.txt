=== Wordfence2Cloudflare ===
Contributors: ITCS, freemius
Tags: Wordfence, Cloudflare, Security, Wordpress Security, Firewall
Requires at least: 5.2
Requires PHP: 7.4
Tested up to: 6.7
Stable tag: 1.5.3
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

# Wordfence to Cloudflare
Enhance your WordPress website's security by seamlessly integrating Wordfence with Cloudflare. The Wordfence to Cloudflare plugin automatically synchronizes blocked IPs from Wordfence to Cloudflare's firewall, providing an additional layer of protection against malicious traffic.

Table of Contents
• Free Features
• Premium Features
• Benefits
• Installation
• Usage
• Frequently Asked Questions

# Features
Free Features:

Automatic IP Synchronization

Periodically checks for new blocked IPs in Wordfence and automatically adds them to Cloudflare's blocked IP list. This ensures that known malicious IPs are effectively blocked at the Cloudflare level, reducing the burden on your server.

# Customizable Settings

Cloudflare Credentials: Securely store and manage your Cloudflare Email, API Key, Zone ID, and Account ID.

Blocked Hits Threshold: Set a threshold for blocked hits to determine which IPs should be synchronized.

Block Scope: Choose between domain-specific or account-wide blocking on Cloudflare.
Block Mode: Select the action to be taken on Cloudflare (e.g., Block, Managed Challenge).

Cron Interval: Configure how frequently the plugin synchronizes blocked IPs (e.g., every 5 minutes, hourly).

Manual Synchronization

Manually trigger the synchronization process with a single click, ensuring immediate synchronization of blocked IPs when needed.

AbuseIPDB Integration (Optional)

Enhanced IP Data: Fetch additional details about blocked IPs from AbuseIPDB, including Country Code, Usage Type, ISP, and Confidence Score.

Country Code: Identify the geographic origin of blocked IPs.

Usage Type: Understand the nature of the entity using the IP (e.g., ISP, Commercial, Residential).

ISP Information: Obtain details about the Internet Service Provider of the blocked IP.

Confidence Score: Gauge how likely an IP is to be engaged in abusive behavior.

Premium Features:

Captured Traffic Data

Traffic Logging: Capture and log traffic data, including IP addresses, user agents, request methods, and accessed URLs.

User Agent Analysis: Analyze user agents to detect potentially abusive or malicious clients.

Exclude User Roles: Option to exclude specific WordPress user roles from traffic logging.

WhatIsMyBrowser.com API Integration

Advanced User Agent Analysis: Obtain detailed information about user agents accessing your website, including software, operating systems, and more.

Abusive User Detection: Automatically detect and flag potentially abusive or malicious user agents based on API analysis.

Automated Blocking: Option to send IPs associated with abusive user agents to Cloudflare directly from the Captured Traffic interface.

Enhanced AbuseIPDB Integration

Automatic Data Update: When AbuseIPDB data is fetched for an IP address, all existing entries with that IP in the Captured Traffic table are updated to reflect the latest data.

Priority Support

Access to priority support for assistance with plugin setup and troubleshooting.

# Benefits

Enhanced Security

Combines the powerful IP blocking capabilities of Wordfence and Cloudflare to provide robust protection against malicious traffic.

Reduced Server Load

Offloads IP blocking to Cloudflare, reducing the burden on your server and improving performance.

Comprehensive Traffic Analysis

Gain deeper insights into the traffic hitting your website, identify potential threats, and take proactive measures.

Customizable Protection

Tailor security measures to your specific needs with flexible settings and options.

Secure Credential Storage

Cloudflare API keys and other sensitive data are securely stored using WordPress's built-in options API.

# Installation

Upload Plugin

Upload the wordfence-to-cloudflare directory to the /wp-content/plugins/ directory.

Alternatively, install the plugin through the WordPress Plugins screen directly.

Activate Plugin

Activate the plugin through the 'Plugins' screen in WordPress.

Configure Settings

Navigate to Settings > WTC Settings in your WordPress dashboard.

Enter your Cloudflare credentials and configure the desired settings.

Usage

Cloudflare Credentials

Email: Your Cloudflare account email address.

API Key: Your Cloudflare API key (Global API Key or API Token with appropriate permissions).

Zone ID: The Zone ID of the domain you wish to protect.

Account ID: Your Cloudflare Account ID.

Configure Plugin Settings

Blocked Hits Threshold: Set the minimum number of blocked hits before an IP is synchronized to Cloudflare.

Block Scope: Choose 'Domain Specific' or 'Entire Account' for the scope of the block.

Block Mode: Select the action to be taken (e.g., 'Block', 'Managed Challenge').

Cron Interval: Set how frequently the synchronization should occur.

AbuseIPDB Integration (Optional)

API Key: Obtain an API key from AbuseIPDB and enter it in the plugin settings.

Enable Lookup: Check the option to enable AbuseIPDB lookups.

WhatIsMyBrowser.com Integration (Premium)

API Key: Obtain an API key from WhatIsMyBrowser.com and enter it in the plugin settings.

Enable Integration: Ensure the feature is enabled in the settings.

Captured Traffic Data (Premium)

Enable Traffic Capture: Enable traffic logging in the plugin settings.

Exclude Roles: Select user roles to exclude from traffic logging.

View Captured Data: Navigate to the 'Captured Traffic Data' tab to view and manage logged traffic.

Manual Synchronization

Click the 'Run Process' button in the plugin settings to manually trigger synchronization.

Frequently Asked Questions
Q1: Can I manually trigger the IP synchronization process?

A: Yes, the plugin provides a 'Run Process' button in the settings. This allows you to manually trigger the synchronization process at any time.

Q2: How is my Cloudflare API key stored?

A: Your Cloudflare API key is securely stored in the WordPress options table using appropriate security measures. It is not displayed in plain text once saved.

Q3: Can I customize which IPs are synchronized to Cloudflare?

A: Yes, you can set a 'Blocked Hits Threshold' to determine which IPs should be synchronized based on the number of blocked hits.

Q4: What are the requirements to use this plugin?

A: You need an active account with Cloudflare and to have the Wordfence plugin installed and activated on your site. For optional features, you may need API keys from AbuseIPDB and WhatIsMyBrowser.com.

Q5: How does the plugin reduce server load?

A: By blocking malicious IPs at the Cloudflare level, the plugin prevents unwanted traffic from reaching your server, thereby reducing load and improving performance.

Q6: Is the Captured Traffic Data feature available in the free version?

A: No, the Captured Traffic Data feature is available in the premium version of the plugin.

Q7: How do I obtain API keys for AbuseIPDB and WhatIsMyBrowser.com?

A: Visit AbuseIPDB and WhatIsMyBrowser.com to sign up for accounts and obtain API keys.

Q8: Can I exclude certain users from being logged in Captured Traffic Data?

A: Yes, in the premium version, you can exclude specific WordPress user roles from being logged.

Q9: Does the plugin support IPv6 addresses?

A: Yes, the plugin supports both IPv4 and IPv6 addresses.


== Screenshots ==

1. The plugin main settings page

== Upgrade Notice ==

= 1.5.3 =

Added
Advanced Rule Builder and Evaluation System: Introduced a powerful rule evaluation engine that allows you to create custom security rules based on various criteria such as confidence score, whitelisted status, abusive status, and more. This enables automated decision-making and actions based on incoming traffic data.

Rule Priorities: Implemented a priority system for rules, allowing you to control the order in which rules are evaluated. Higher priority numbers are evaluated first, ensuring critical rules are applied before others.

Automatic Action Application: The plugin now automatically applies actions (e.g., block, challenge) to IP addresses that match your defined rules immediately after capturing traffic data.

Rule Details in Blocked IPs Table: Enhanced the Blocked IPs tab by adding a new column that displays detailed information about the rules that caused each IP address to be blocked, including criteria and actions taken.

Fixed
Database Schema Updates: Resolved issues with missing database columns (block_mode, rule_id, rule_details) by enhancing the table creation and update process. The plugin now correctly updates existing tables to include new columns without errors.

DataTables Initialization Issue: Fixed an issue where the DataTables plugin was being initialized multiple times, causing reinitialization errors. The plugin now ensures that DataTables are initialized correctly without conflicts.

Rule Evaluation Bug: Addressed a bug where rules were not being evaluated after capturing traffic data. The plugin now properly calls the rule evaluation function, ensuring that your rules are applied as expected.


== Changelog ==

= 1.2 =
Added new functionalities to work with cloudflare including a table and an extra tab

= 1.2.1 =
New options on the blocked ips table

= 1.3 =
The user now can remove a blocked ip either from the local list or from both the Cloudflare and the plugin blocked list. 

= 1.3.1 =
Freemius Integration

= 1.3.2 =
Minor fixes

= 1.3.3 =
AbuseIP Database Integration

= 1.3.4 =
Timezone fix

= 1.3.5 =
Timezone fix

= 1.3.6 =
Added ips blocked from WordFence wfBlockedIPLog too

= 1.3.7 =
Various fixes
Added Pro feature - Traffic inspection integration with whatsmybrowser.com API to identify malicious user agents. 

= 1.3.8 =
Various fixes

= 1.3.9 =
Various fixes

= 1.4.0 =
Various fixes

= 1.5.0 =
Enhanced WhatIsMyBrowser API Integration:
- Dynamic handling of `is_abusive` values directly from the API without predefined mappings.
- Extracted and stored specific API response elements: `is_abusive`, `software`, `operating_system`.
- Optimized API usage by making calls only once per unique IP/User Agent combination.
- Improved error handling and logging for API requests.

Admin Interface Enhancements:
- Professional redesign of the "Captured Traffic" table with WordPress admin styling.
- Fixed layout issues with action buttons and ensured proper alignment.
- Integrated DataTables for advanced table features like search, pagination, and sorting.
- Focused data display by showing only relevant API response fields.

Database and Performance Optimizations:
- Updated database schema to include new columns for `software` and `operating_system`.
- Changed `is_abusive` column type to accommodate dynamic values.
- Enhanced data sanitization and security measures.

Code Refactoring and Security Enhancements:
- Improved code structure for better readability and maintenance.
- Ensured adherence to security best practices throughout the codebase.

Bug Fixes and Minor Improvements:
- Resolved button overflow issues in the admin table.
- Enhanced user experience with better messaging and error handling.

= 1.5.1 =

Bulk Selection and Deletion in Blocked IPs Tab:

Added a "Select All" checkbox in the Blocked IPs table header, allowing you to select all records across all pages, regardless of how many are displayed per page.
Implemented the ability to perform bulk actions, such as deleting multiple blocked IPs at once, enhancing efficiency in managing large numbers of records.
Improved the positioning and styling of the "Select All" checkbox to prevent overlap with sorting arrows, ensuring better usability.
Enhanced Actions in Captured Traffic Tab:

Introduced a new "Delete All Records" button within the "Actions" column of the Captured Traffic table. This feature enables you to delete all records associated with a specific IP address with a single click.
Adjusted the layout and styling of action buttons to prevent them from overflowing outside of their table columns. Buttons are now stacked vertically and styled consistently for a cleaner and more organized look.
User Interface Improvements:

Modified CSS and table configurations to ensure that all buttons and content fit neatly within their respective columns, enhancing the overall aesthetics and usability of the plugin interface.
Disabled sorting on checkbox columns and adjusted column widths to optimize the display of data and controls within the tables.

= 1.5.2 =

New Features:

Exclude User Roles from Captured Traffic Data (Premium Feature)

Description: Administrators can now select one or more WordPress user roles to exclude from traffic logging in the Captured Traffic Data. This feature enhances privacy and control by preventing the logging of traffic data for trusted users or specific roles, such as administrators or editors.
How to Use: Navigate to the plugin's settings page under the "Exclude Roles from Captured Traffic" section (available for premium users). Select the roles you wish to exclude from logging and save the settings.
Improvements:

AbuseIPDB Data Display Enhancement

Resolved an issue where AbuseIPDB data (Country Code, Usage Type, ISP, Confidence Score) was not displayed on every row for IP addresses appearing multiple times in the Captured Traffic table.
Now, when AbuseIPDB data is fetched for an IP address, all existing entries with that IP in the Captured Traffic table are updated to reflect the latest data.
Code Compliance and Optimization

Ensured all new code follows WordPress coding standards and best practices.
Implemented proper sanitization, validation, and use of WordPress APIs for enhanced security and performance.
Important Notes:

Premium Access Required: The new feature to exclude user roles from traffic logging is available only in the premium version of the plugin. Free users will see a notice indicating that the feature is available upon upgrading.

Action Required: After upgrading to version 1.5.2, premium users should review the new settings:

Go to the plugin settings page.
Under the "Exclude Roles from Captured Traffic" section, select the user roles you wish to exclude.
Save the settings to apply the changes.
No Impact on Existing Settings: Existing configurations and data are not affected by this update. However, reviewing the new settings is recommended to take full advantage of the new features.

Compatibility: This update is fully compatible with WordPress versions up to the latest release as of [Current Date].

Upgrade Instructions:

Backup Your Site: It's always good practice to back up your WordPress site before performing any updates.

Update the Plugin:

Navigate to your WordPress dashboard.
Go to Plugins > Installed Plugins.
Find "Wordfence to Cloudflare" in the list.
Click on "Update Now" to upgrade to version 1.5.2.
Review New Settings (Premium Users):

After the update, go to Settings > WTC Settings.
Navigate to the "Exclude Roles from Captured Traffic" section.
Select the roles to exclude and save your settings.

= 1.5.3 =

Added
Advanced Rule Builder and Evaluation System: Introduced a powerful rule evaluation engine that allows you to create custom security rules based on various criteria such as confidence score, whitelisted status, abusive status, and more. This enables automated decision-making and actions based on incoming traffic data.

Rule Priorities: Implemented a priority system for rules, allowing you to control the order in which rules are evaluated. Higher priority numbers are evaluated first, ensuring critical rules are applied before others.

Automatic Action Application: The plugin now automatically applies actions (e.g., block, challenge) to IP addresses that match your defined rules immediately after capturing traffic data.

Rule Details in Blocked IPs Table: Enhanced the Blocked IPs tab by adding a new column that displays detailed information about the rules that caused each IP address to be blocked, including criteria and actions taken.

Fixed
Database Schema Updates: Resolved issues with missing database columns (block_mode, rule_id, rule_details) by enhancing the table creation and update process. The plugin now correctly updates existing tables to include new columns without errors.

DataTables Initialization Issue: Fixed an issue where the DataTables plugin was being initialized multiple times, causing reinitialization errors. The plugin now ensures that DataTables are initialized correctly without conflicts.

Rule Evaluation Bug: Addressed a bug where rules were not being evaluated after capturing traffic data. The plugin now properly calls the rule evaluation function, ensuring that your rules are applied as expected.

